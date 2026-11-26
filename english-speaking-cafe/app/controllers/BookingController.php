<?php
/** Assistant de réservation, paiements, abonnements, inscriptions aux événements */
declare(strict_types=1);

namespace App\Controllers;

use App\Core\{DB, View, Auth, Booking, Payment, Mailer};

final class BookingController
{
    /** Personnes pour qui le compte peut réserver */
    public static function learnersFor(array $u): array
    {
        $list = [];
        if ($u['role'] !== 'corporate' && $u['role'] !== 'parent') $list[] = $u;
        if ($u['role'] === 'parent') $list = array_merge($list, DB::all('SELECT * FROM users WHERE parent_id = ? AND is_active = 1 ORDER BY first_name', [$u['id']]));
        if ($u['role'] === 'corporate') $list = array_merge($list, DB::all('SELECT * FROM users WHERE company_id = ? AND is_active = 1 AND id <> ? ORDER BY first_name', [$u['company_id'], $u['id']]));
        if (!$list) $list[] = $u;
        return $list;
    }

    public function wizard(): void
    {
        $u = Auth::user();
        View::render('booking/wizard', [
            'title' => 'Book your seat',
            'programs' => DB::all('SELECT * FROM programs WHERE is_active = 1 ORDER BY sort_order'),
            'teachers' => DB::all('SELECT id, display_name, accent, origin, specialties, photo FROM teachers WHERE is_active = 1 ORDER BY sort_order'),
            'learners' => $u ? self::learnersFor($u) : [],
            'membership' => $u ? Booking::usableMembership((int)$u['id'], (int)$u['id']) : null,
            'pre' => ['program' => input('program'), 'level' => input('level', $u['cefr_level'] ?? null), 'teacher' => input('teacher'), 'goal' => input('goal', $u['goal'] ?? null)],
            'bodyClass' => 'is-booking',
            'scripts' => ['js/booking.js'],
        ]);
    }

    public function store(): void
    {
        $u = Auth::require();
        $sessionId = (int)input('session_id');
        $participant = (int)input('participant_id', $u['id']);
        $method = in_array(input('method'), ['stripe', 'onsite', 'credit'], true) ? input('method') : 'stripe';
        $level = in_array(input('level'), CEFR, true) ? input('level') : null;
        $goal = array_key_exists((string)input('goal'), GOALS) ? input('goal') : null;

        if (!Auth::canActFor($participant)) abort(403, 'You cannot book for this learner.');
        try {
            $r = Booking::book($sessionId, $participant, (int)$u['id'], $method, $level, $goal);
        } catch (\DomainException $e) {
            flash('error', $e->getMessage());
            redirect('/book?' . http_build_query(['level' => $level, 'goal' => $goal]));
        }
        if ($r['status'] === 'waitlist') {
            flash('info', "This session is full — you are #{$r['position']} on the waitlist. We’ll email you the moment a seat opens.");
            redirect('/dashboard');
        }
        if ($r['status'] === 'pending') {
            try {
                redirect(Payment::checkoutUrl((int)$r['payment_id']));
            } catch (\Throwable $e) {
                flash('error', $e->getMessage());
                redirect('/dashboard');
            }
        }
        Mailer::bookingConfirmed($r['booking_id']);
        flash('success', 'Your seat is confirmed. See you at the café!');
        redirect('/dashboard');
    }

    public function cancel(string $id): void
    {
        $u = Auth::require();
        $b = DB::one('SELECT * FROM bookings WHERE id = ?', [(int)$id]);
        if (!$b || ((int)$b['booked_by'] !== (int)$u['id'] && !Auth::canActFor((int)$b['user_id']))) abort(403);
        try {
            Booking::cancel((int)$id, $u['role'] === 'admin');
            flash('success', 'Booking cancelled.');
        } catch (\DomainException $e) {
            flash('error', $e->getMessage());
        }
        back();
    }

    public function pay(string $id): void
    {
        $u = Auth::require();
        $p = DB::one('SELECT * FROM payments WHERE id = ? AND user_id = ?', [(int)$id, $u['id']]);
        if (!$p) abort(404);
        try {
            if ($p['method'] === 'onsite') DB::update('payments', ['method' => 'stripe'], 'id = ?', [$p['id']]);
            redirect(Payment::checkoutUrl((int)$p['id']));
        } catch (\DomainException $e) {
            flash('error', $e->getMessage());
            redirect('/dashboard');
        }
    }

    /** Simulateur de paiement (uniquement si Stripe n'est pas configuré) */
    public function demoCheckout(string $id): void
    {
        $u = Auth::require();
        if (!Payment::demoEnabled()) abort(404);
        $p = DB::one('SELECT * FROM payments WHERE id = ? AND user_id = ?', [(int)$id, $u['id']]);
        if (!$p) abort(404);
        View::render('booking/checkout', ['title' => 'Checkout', 'p' => $p, 'label' => Payment::label($p)], 'auth');
    }

    public function demoConfirm(string $id): void
    {
        $u = Auth::require();
        if (!Payment::demoEnabled()) abort(404);
        $p = DB::one("SELECT * FROM payments WHERE id = ? AND user_id = ? AND status = 'pending'", [(int)$id, $u['id']]);
        if (!$p) { flash('error', 'This payment is no longer pending.'); redirect('/dashboard'); }
        Payment::markPaid((int)$p['id'], 'demo_' . bin2hex(random_bytes(6)));
        flash('success', 'Payment received — thank you! ' . ($p['purpose'] === 'booking' ? 'Your seat is confirmed.' : ''));
        redirect('/dashboard');
    }

    public function success(): void
    {
        Auth::require();
        $pid = (int)input('pid');
        $ok = Payment::verifyStripe($pid, (string)input('cs'));
        flash($ok ? 'success' : 'info', $ok ? 'Payment received — you’re all set!' : 'Payment is being confirmed. It will appear in your dashboard shortly.');
        redirect('/dashboard');
    }

    public function cancelled(): void
    {
        flash('info', 'Payment cancelled. Your seat is held for a few minutes — you can retry from your dashboard.');
        redirect('/dashboard');
    }

    public function joinMembership(string $planId): void
    {
        $u = Auth::require();
        $plan = DB::one('SELECT * FROM membership_plans WHERE id = ? AND is_active = 1', [(int)$planId]);
        if (!$plan) abort(404);
        $mid = DB::insert('memberships', ['user_id' => $u['id'], 'plan_id' => $plan['id'], 'status' => 'pending']);
        $pid = DB::insert('payments', ['user_id' => $u['id'], 'purpose' => 'membership', 'reference_id' => $mid, 'amount' => $plan['price'], 'currency' => setting('currency', 'EUR'), 'method' => 'stripe', 'status' => 'pending']);
        if ((float)$plan['price'] <= 0) { Payment::markPaid($pid, 'free'); flash('success', 'Membership activated!'); redirect('/dashboard'); }
        redirect(Payment::checkoutUrl($pid));
    }

    public function registerEvent(string $id): void
    {
        $u = Auth::require();
        try {
        $result = DB::tx(function () use ($id, $u) {
            $e = DB::one('SELECT * FROM events WHERE id = ? AND is_published = 1 FOR UPDATE', [(int)$id]);
            if (!$e || strtotime($e['starts_at']) < time()) throw new \DomainException('This event is not available.');
            $ex = DB::one('SELECT * FROM event_registrations WHERE event_id = ? AND user_id = ?', [$e['id'], $u['id']]);
            if ($ex && $ex['status'] !== 'cancelled') throw new \DomainException('You are already registered for this event.');
            $taken = (int)DB::val("SELECT COUNT(*) FROM event_registrations WHERE event_id = ? AND status IN ('pending','confirmed','attended')", [$e['id']]);
            $full = $taken >= (int)$e['capacity'];
            $memberFree = (bool)DB::val("SELECT m.id FROM memberships m JOIN membership_plans p ON p.id = m.plan_id WHERE m.user_id = ? AND m.status = 'active' AND p.includes_events = 1 AND (m.ends_at IS NULL OR m.ends_at >= CURDATE())", [$u['id']]);
            $free = (float)$e['price'] <= 0 || $memberFree;
            $status = $full ? 'waitlist' : ($free ? 'confirmed' : 'pending');
            if ($ex) { DB::update('event_registrations', ['status' => $status, 'payment_id' => null], 'id = ?', [$ex['id']]); $rid = (int)$ex['id']; }
            else $rid = DB::insert('event_registrations', ['event_id' => $e['id'], 'user_id' => $u['id'], 'status' => $status]);
            $pid = null;
            if ($status === 'pending') {
                $pid = DB::insert('payments', ['user_id' => $u['id'], 'purpose' => 'event', 'reference_id' => $rid, 'amount' => $e['price'], 'currency' => setting('currency', 'EUR'), 'method' => 'stripe', 'status' => 'pending']);
                DB::update('event_registrations', ['payment_id' => $pid], 'id = ?', [$rid]);
            }
            return [$status, $pid];
        });
        } catch (\DomainException $ex) {
            flash('error', $ex->getMessage());
            back();
        }
        [$status, $pid] = $result;
        if ($status === 'pending') redirect(Payment::checkoutUrl($pid));
        flash($status === 'waitlist' ? 'info' : 'success', $status === 'waitlist' ? 'This event is full — you are on the waitlist.' : 'You’re on the list! See you there.');
        back();
    }

    public function webhook(): void
    {
        Payment::handleWebhook((string)file_get_contents('php://input'), (string)($_SERVER['HTTP_STRIPE_SIGNATURE'] ?? ''));
    }
}
