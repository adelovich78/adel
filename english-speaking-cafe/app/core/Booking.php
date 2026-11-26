<?php
/**
 * Service de réservation.
 * - Anti-surbooking : verrou ligne (SELECT ... FOR UPDATE) sur la séance dans une transaction.
 * - Liste d'attente : position FIFO, promotion automatique quand une place se libère.
 * - Place "pending" retenue pendant le paiement (hold), libérée à expiration.
 */
declare(strict_types=1);

namespace App\Core;

final class Booking
{
    /** Statuts qui occupent une place */
    public const SEATED = "('pending','confirmed','attended','no_show')";

    public static function holdMinutes(): int
    {
        return max(5, (int)setting('booking_hold_minutes', 20));
    }

    /** Libère les places dont le paiement n'a pas abouti à temps */
    public static function releaseExpired(): void
    {
        $expired = DB::all("SELECT id, session_id, payment_id FROM bookings WHERE status = 'pending' AND hold_expires_at IS NOT NULL AND hold_expires_at < NOW()");
        if (!$expired) return;
        $sessions = [];
        foreach ($expired as $b) {
            DB::update('bookings', ['status' => 'cancelled', 'updated_at' => date('Y-m-d H:i:s')], 'id = ? AND status = ?', [$b['id'], 'pending']);
            if ($b['payment_id']) DB::update('payments', ['status' => 'cancelled'], 'id = ? AND status = ?', [$b['payment_id'], 'pending']);
            $sessions[$b['session_id']] = true;
        }
        foreach (array_keys($sessions) as $sid) self::promoteWaitlist((int)$sid);
    }

    public static function seatsTaken(int $sessionId): int
    {
        return (int)DB::val('SELECT COUNT(*) FROM bookings WHERE session_id = ? AND status IN ' . self::SEATED, [$sessionId]);
    }

    public static function price(array $session): float
    {
        return (float)($session['price'] ?? $session['program_price'] ?? 0);
    }

    /** Abonnement actif utilisable (crédits > 0 ou illimité) pour le payeur ou le participant */
    public static function usableMembership(int $payerId, int $participantId): ?array
    {
        return DB::one(
            "SELECT m.*, p.credits AS plan_credits FROM memberships m JOIN membership_plans p ON p.id = m.plan_id
             WHERE m.user_id IN (?, ?) AND m.status = 'active' AND (m.ends_at IS NULL OR m.ends_at >= CURDATE())
               AND (p.credits = 0 OR m.credits_remaining > 0)
             ORDER BY m.user_id = ? DESC, m.ends_at ASC LIMIT 1",
            [$participantId, $payerId, $participantId]
        );
    }

    /**
     * Réserve une séance.
     * @return array{booking_id:int,status:string,payment_id:?int,position:?int}
     */
    public static function book(int $sessionId, int $participantId, int $payerId, string $method, ?string $level = null, ?string $goal = null): array
    {
        self::releaseExpired();

        return DB::tx(function () use ($sessionId, $participantId, $payerId, $method, $level, $goal) {
            // Verrou exclusif sur la séance : deux réservations simultanées sont sérialisées
            $s = DB::one(
                'SELECT s.*, p.price AS program_price, p.membership_eligible, p.name AS program_name
                 FROM sessions s JOIN programs p ON p.id = s.program_id WHERE s.id = ? FOR UPDATE',
                [$sessionId]
            );
            if (!$s || $s['status'] !== 'scheduled') throw new \DomainException('This session is no longer available.');
            if (strtotime($s['starts_at']) <= time()) throw new \DomainException('This session has already started.');

            $dup = DB::val("SELECT id FROM bookings WHERE session_id = ? AND user_id = ? AND status IN ('pending','confirmed','waitlist','attended')", [$sessionId, $participantId]);
            if ($dup) throw new \DomainException('This learner is already booked (or waitlisted) on this session.');

            $taken = (int)DB::val('SELECT COUNT(*) FROM bookings WHERE session_id = ? AND status IN ' . self::SEATED, [$sessionId]);
            $now = date('Y-m-d H:i:s');

            // Séance complète → liste d'attente
            if ($taken >= (int)$s['capacity']) {
                $pos = (int)DB::val("SELECT COALESCE(MAX(waitlist_position),0) FROM bookings WHERE session_id = ? AND status = 'waitlist'", [$sessionId]) + 1;
                $id = DB::insert('bookings', [
                    'session_id' => $sessionId, 'user_id' => $participantId, 'booked_by' => $payerId,
                    'status' => 'waitlist', 'waitlist_position' => $pos, 'level' => $level, 'goal' => $goal, 'created_at' => $now,
                ]);
                return ['booking_id' => $id, 'status' => 'waitlist', 'payment_id' => null, 'position' => $pos];
            }

            $price = self::price($s);
            $data = [
                'session_id' => $sessionId, 'user_id' => $participantId, 'booked_by' => $payerId,
                'level' => $level, 'goal' => $goal, 'created_at' => $now,
            ];

            if ($price <= 0) {
                $id = DB::insert('bookings', $data + ['status' => 'confirmed']);
                $pid = DB::insert('payments', ['user_id' => $payerId, 'purpose' => 'booking', 'reference_id' => $id, 'amount' => 0, 'currency' => setting('currency', 'EUR'), 'method' => 'free', 'status' => 'paid', 'paid_at' => $now]);
                DB::update('bookings', ['payment_id' => $pid], 'id = ?', [$id]);
                return ['booking_id' => $id, 'status' => 'confirmed', 'payment_id' => $pid, 'position' => null];
            }

            if ($method === 'credit') {
                if (!$s['membership_eligible']) throw new \DomainException('This program is not included in memberships.');
                $m = self::usableMembership($payerId, $participantId);
                if (!$m) throw new \DomainException('No membership credits available.');
                if ((int)$m['plan_credits'] > 0) {
                    DB::q('UPDATE memberships SET credits_remaining = credits_remaining - 1 WHERE id = ? AND credits_remaining > 0', [$m['id']]);
                }
                $id = DB::insert('bookings', $data + ['status' => 'confirmed', 'membership_id' => $m['id']]);
                $pid = DB::insert('payments', ['user_id' => $payerId, 'purpose' => 'booking', 'reference_id' => $id, 'amount' => 0, 'currency' => setting('currency', 'EUR'), 'method' => 'credit', 'status' => 'paid', 'paid_at' => $now]);
                DB::update('bookings', ['payment_id' => $pid], 'id = ?', [$id]);
                return ['booking_id' => $id, 'status' => 'confirmed', 'payment_id' => $pid, 'position' => null];
            }

            if ($method === 'onsite' && setting('allow_onsite_payment', '1') === '1') {
                // Payer au comptoir : place confirmée, paiement en attente
                $id = DB::insert('bookings', $data + ['status' => 'confirmed']);
                $pid = DB::insert('payments', ['user_id' => $payerId, 'purpose' => 'booking', 'reference_id' => $id, 'amount' => $price, 'currency' => setting('currency', 'EUR'), 'method' => 'onsite', 'status' => 'pending']);
                DB::update('bookings', ['payment_id' => $pid], 'id = ?', [$id]);
                return ['booking_id' => $id, 'status' => 'confirmed', 'payment_id' => $pid, 'position' => null];
            }

            // Paiement en ligne : place retenue pendant le paiement
            $id = DB::insert('bookings', $data + ['status' => 'pending', 'hold_expires_at' => date('Y-m-d H:i:s', time() + self::holdMinutes() * 60)]);
            $pid = DB::insert('payments', ['user_id' => $payerId, 'purpose' => 'booking', 'reference_id' => $id, 'amount' => $price, 'currency' => setting('currency', 'EUR'), 'method' => 'stripe', 'status' => 'pending']);
            DB::update('bookings', ['payment_id' => $pid], 'id = ?', [$id]);
            return ['booking_id' => $id, 'status' => 'pending', 'payment_id' => $pid, 'position' => null];
        });
    }

    /** Annule une réservation et promeut la liste d'attente */
    public static function cancel(int $bookingId, bool $force = false): void
    {
        $b = DB::one('SELECT b.*, s.starts_at FROM bookings b JOIN sessions s ON s.id = b.session_id WHERE b.id = ?', [$bookingId]);
        if (!$b || in_array($b['status'], ['cancelled', 'attended', 'no_show'], true)) throw new \DomainException('This booking cannot be cancelled.');
        $hours = (int)setting('cancellation_hours', 12);
        if (!$force && $b['status'] !== 'waitlist' && strtotime($b['starts_at']) - time() < $hours * 3600) {
            throw new \DomainException("Cancellations are possible up to {$hours}h before the session. Please contact us.");
        }
        DB::tx(function () use ($b) {
            DB::q('SELECT id FROM sessions WHERE id = ? FOR UPDATE', [$b['session_id']]);
            DB::update('bookings', ['status' => 'cancelled', 'waitlist_position' => null, 'updated_at' => date('Y-m-d H:i:s')], 'id = ?', [$b['id']]);
            // Restitution du crédit d'abonnement
            if ($b['membership_id']) DB::q('UPDATE memberships m JOIN membership_plans p ON p.id = m.plan_id SET m.credits_remaining = m.credits_remaining + 1 WHERE m.id = ? AND p.credits > 0', [$b['membership_id']]);
            if ($b['payment_id']) {
                $p = DB::one('SELECT * FROM payments WHERE id = ?', [$b['payment_id']]);
                if ($p && $p['status'] === 'pending') DB::update('payments', ['status' => 'cancelled'], 'id = ?', [$p['id']]);
                if ($p && $p['status'] === 'paid' && $p['method'] === 'stripe') log_line('refunds.log', "Refund to review: payment #{$p['id']} booking #{$b['id']}");
            }
            if ($b['status'] === 'waitlist') self::reorderWaitlist((int)$b['session_id']);
        });
        self::promoteWaitlist((int)$b['session_id']);
    }

    /** Fait monter la liste d'attente tant qu'il reste des places */
    public static function promoteWaitlist(int $sessionId): void
    {
        DB::tx(function () use ($sessionId) {
            $s = DB::one('SELECT s.*, p.price AS program_price, p.membership_eligible FROM sessions s JOIN programs p ON p.id = s.program_id WHERE s.id = ? FOR UPDATE', [$sessionId]);
            if (!$s || $s['status'] !== 'scheduled' || strtotime($s['starts_at']) <= time()) return;
            $free = (int)$s['capacity'] - (int)DB::val('SELECT COUNT(*) FROM bookings WHERE session_id = ? AND status IN ' . self::SEATED, [$sessionId]);
            while ($free-- > 0) {
                $w = DB::one("SELECT * FROM bookings WHERE session_id = ? AND status = 'waitlist' ORDER BY waitlist_position ASC, id ASC LIMIT 1", [$sessionId]);
                if (!$w) break;
                $now = date('Y-m-d H:i:s');
                $price = self::price($s);
                $m = $s['membership_eligible'] ? self::usableMembership((int)$w['booked_by'], (int)$w['user_id']) : null;
                if ($price <= 0 || $m) {
                    // Confirmation directe (gratuit ou crédit d'abonnement)
                    if ($m && (int)$m['plan_credits'] > 0) DB::q('UPDATE memberships SET credits_remaining = credits_remaining - 1 WHERE id = ?', [$m['id']]);
                    $pid = DB::insert('payments', ['user_id' => $w['booked_by'], 'purpose' => 'booking', 'reference_id' => $w['id'], 'amount' => 0, 'currency' => setting('currency', 'EUR'), 'method' => $m ? 'credit' : 'free', 'status' => 'paid', 'paid_at' => $now]);
                    DB::update('bookings', ['status' => 'confirmed', 'waitlist_position' => null, 'membership_id' => $m['id'] ?? null, 'payment_id' => $pid, 'updated_at' => $now], 'id = ?', [$w['id']]);
                } else {
                    // Place retenue 12 h pour payer
                    $pid = DB::insert('payments', ['user_id' => $w['booked_by'], 'purpose' => 'booking', 'reference_id' => $w['id'], 'amount' => $price, 'currency' => setting('currency', 'EUR'), 'method' => 'stripe', 'status' => 'pending']);
                    $hold = min(time() + 12 * 3600, strtotime($s['starts_at']) - 1800);
                    DB::update('bookings', ['status' => 'pending', 'waitlist_position' => null, 'payment_id' => $pid, 'hold_expires_at' => date('Y-m-d H:i:s', max($hold, time() + 900)), 'updated_at' => $now], 'id = ?', [$w['id']]);
                }
                Mailer::notifyPromotion((int)$w['id']);
            }
            self::reorderWaitlist($sessionId);
        });
    }

    private static function reorderWaitlist(int $sessionId): void
    {
        $rows = DB::all("SELECT id FROM bookings WHERE session_id = ? AND status = 'waitlist' ORDER BY waitlist_position, id", [$sessionId]);
        foreach ($rows as $i => $r) DB::update('bookings', ['waitlist_position' => $i + 1], 'id = ?', [$r['id']]);
    }

    /** Séances disponibles avec places restantes (pour l'assistant + API) */
    public static function availableSessions(array $f = []): array
    {
        self::releaseExpired();
        $where = ["s.status = 'scheduled'", 's.starts_at > NOW()', 'p.is_active = 1'];
        $params = [];
        if (!empty($f['program'])) { $where[] = 'p.slug = ?'; $params[] = $f['program']; }
        if (!empty($f['teacher'])) { $where[] = 's.teacher_id = ?'; $params[] = (int)$f['teacher']; }
        if (!empty($f['date'])) { $where[] = 'DATE(s.starts_at) = ?'; $params[] = $f['date']; }
        if (!empty($f['level']) && in_array($f['level'], CEFR, true)) {
            $idx = cefr_index($f['level']);
            $where[] = "(s.level IS NULL OR s.level = ?)";
            $params[] = $f['level'];
            $where[] = "FIELD(p.min_level,'A1','A2','B1','B2','C1','C2') <= ? AND FIELD(p.max_level,'A1','A2','B1','B2','C1','C2') >= ?";
            $params[] = $idx + 1; $params[] = $idx + 1;
        }
        $limit = min(200, max(1, (int)($f['limit'] ?? 80)));
        $sql = 'SELECT s.*, p.name AS program_name, p.slug AS program_slug, p.category, p.price AS program_price, p.duration_min,
                       t.display_name AS teacher_name, t.accent AS teacher_accent, t.photo AS teacher_photo,
                       (SELECT COUNT(*) FROM bookings b WHERE b.session_id = s.id AND b.status IN ' . self::SEATED . ') AS taken,
                       (SELECT COUNT(*) FROM bookings b WHERE b.session_id = s.id AND b.status = \'waitlist\') AS waiting
                FROM sessions s JOIN programs p ON p.id = s.program_id LEFT JOIN teachers t ON t.id = s.teacher_id
                WHERE ' . implode(' AND ', $where) . " ORDER BY s.starts_at ASC LIMIT $limit";
        $rows = DB::all($sql, $params);
        foreach ($rows as &$r) {
            $r['seats_left'] = max(0, (int)$r['capacity'] - (int)$r['taken']);
            $r['effective_price'] = self::price($r);
        }
        return $rows;
    }
}
