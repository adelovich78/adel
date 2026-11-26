<?php
/**
 * Paiements : Stripe Checkout via cURL (aucune dépendance) + mode démo + paiement sur place.
 */
declare(strict_types=1);

namespace App\Core;

final class Payment
{
    public static function stripeEnabled(): bool
    {
        return (string)config('stripe.secret_key', '') !== '';
    }

    public static function demoEnabled(): bool
    {
        return !self::stripeEnabled() && (bool)config('app.payments_demo', true);
    }

    /** Retourne l'URL vers laquelle rediriger le client pour payer */
    public static function checkoutUrl(int $paymentId): string
    {
        $p = DB::one('SELECT * FROM payments WHERE id = ?', [$paymentId]);
        if (!$p || $p['status'] !== 'pending') throw new \DomainException('This payment is not payable.');
        if (!self::stripeEnabled()) return url('checkout/' . $paymentId);

        $label = self::label($p);
        $res = self::stripe('POST', 'checkout/sessions', [
            'mode' => 'payment',
            'client_reference_id' => (string)$p['id'],
            'metadata[payment_id]' => (string)$p['id'],
            'line_items[0][quantity]' => 1,
            'line_items[0][price_data][currency]' => strtolower($p['currency']),
            'line_items[0][price_data][unit_amount]' => (int)round($p['amount'] * 100),
            'line_items[0][price_data][product_data][name]' => $label,
            'success_url' => full_url('payment/success') . '?pid=' . $p['id'] . '&cs={CHECKOUT_SESSION_ID}',
            'cancel_url' => full_url('payment/cancel') . '?pid=' . $p['id'],
            'expires_at' => time() + 31 * 60,
        ]);
        if (empty($res['url'])) throw new \RuntimeException('Payment provider unavailable. Please try again or pay at the café.');
        DB::update('payments', ['provider_ref' => $res['id']], 'id = ?', [$p['id']]);
        return $res['url'];
    }

    public static function label(array $p): string
    {
        return match ($p['purpose']) {
            'booking' => 'Session — ' . (DB::val("SELECT CONCAT(pr.name, ' · ', DATE_FORMAT(s.starts_at, '%d/%m %H:%i')) FROM bookings b JOIN sessions s ON s.id = b.session_id JOIN programs pr ON pr.id = s.program_id WHERE b.id = ?", [$p['reference_id']]) ?: 'Booking'),
            'membership' => 'Membership — ' . (DB::val('SELECT mp.name FROM memberships m JOIN membership_plans mp ON mp.id = m.plan_id WHERE m.id = ?', [$p['reference_id']]) ?: 'Plan'),
            'event' => 'Event — ' . (DB::val('SELECT e.title FROM event_registrations r JOIN events e ON e.id = r.event_id WHERE r.id = ?', [$p['reference_id']]) ?: 'Event'),
            default => 'XY Academy',
        };
    }

    /** Vérifie une session Stripe Checkout et valide le paiement */
    public static function verifyStripe(int $paymentId, string $checkoutId): bool
    {
        if (!self::stripeEnabled() || !preg_match('/^cs_[A-Za-z0-9_]+$/', $checkoutId)) return false;
        $res = self::stripe('GET', 'checkout/sessions/' . $checkoutId);
        if (($res['payment_status'] ?? '') === 'paid' && (int)($res['metadata']['payment_id'] ?? 0) === $paymentId) {
            self::markPaid($paymentId, (string)($res['payment_intent'] ?? $checkoutId));
            return true;
        }
        return false;
    }

    /** Webhook Stripe signé (checkout.session.completed) */
    public static function handleWebhook(string $payload, string $sigHeader): void
    {
        $secret = (string)config('stripe.webhook_secret', '');
        if ($secret === '') json_response(['error' => 'webhook not configured'], 400);
        $parts = [];
        foreach (explode(',', $sigHeader) as $kv) { [$k, $v] = array_pad(explode('=', $kv, 2), 2, ''); $parts[$k][] = $v; }
        $t = (int)($parts['t'][0] ?? 0);
        $expected = hash_hmac('sha256', $t . '.' . $payload, $secret);
        $valid = false;
        foreach ($parts['v1'] ?? [] as $sig) if (hash_equals($expected, $sig)) $valid = true;
        if (!$valid || abs(time() - $t) > 600) json_response(['error' => 'invalid signature'], 400);

        $event = json_decode($payload, true);
        if (($event['type'] ?? '') === 'checkout.session.completed') {
            $obj = $event['data']['object'] ?? [];
            if (($obj['payment_status'] ?? '') === 'paid') self::markPaid((int)($obj['metadata']['payment_id'] ?? 0), (string)($obj['payment_intent'] ?? $obj['id'] ?? ''));
        }
        json_response(['received' => true]);
    }

    /** Valide un paiement et applique ses effets (idempotent) */
    public static function markPaid(int $paymentId, string $ref = ''): void
    {
        DB::tx(function () use ($paymentId, $ref) {
            $p = DB::one('SELECT * FROM payments WHERE id = ? FOR UPDATE', [$paymentId]);
            if (!$p || $p['status'] === 'paid') return;
            DB::update('payments', ['status' => 'paid', 'paid_at' => date('Y-m-d H:i:s'), 'provider_ref' => $ref ?: $p['provider_ref']], 'id = ?', [$paymentId]);

            if ($p['purpose'] === 'booking') {
                $b = DB::one('SELECT * FROM bookings WHERE id = ?', [$p['reference_id']]);
                if (!$b) return;
                if ($b['status'] === 'pending') {
                    DB::update('bookings', ['status' => 'confirmed', 'hold_expires_at' => null, 'updated_at' => date('Y-m-d H:i:s')], 'id = ?', [$b['id']]);
                } elseif ($b['status'] === 'cancelled') {
                    // Paiement arrivé après expiration : on tente de replacer, sinon liste d'attente
                    $s = DB::one('SELECT * FROM sessions WHERE id = ? FOR UPDATE', [$b['session_id']]);
                    $taken = (int)DB::val('SELECT COUNT(*) FROM bookings WHERE session_id = ? AND status IN ' . Booking::SEATED, [$b['session_id']]);
                    if ($s && $taken < (int)$s['capacity']) {
                        DB::update('bookings', ['status' => 'confirmed', 'hold_expires_at' => null], 'id = ?', [$b['id']]);
                    } else {
                        $pos = (int)DB::val("SELECT COALESCE(MAX(waitlist_position),0) FROM bookings WHERE session_id = ? AND status='waitlist'", [$b['session_id']]) + 1;
                        DB::update('bookings', ['status' => 'waitlist', 'waitlist_position' => $pos], 'id = ?', [$b['id']]);
                        log_line('refunds.log', "Paid after expiry, waitlisted: booking #{$b['id']} payment #$paymentId");
                    }
                }
            } elseif ($p['purpose'] === 'membership') {
                $m = DB::one('SELECT m.*, pl.credits, pl.period FROM memberships m JOIN membership_plans pl ON pl.id = m.plan_id WHERE m.id = ?', [$p['reference_id']]);
                if ($m && $m['status'] === 'pending') {
                    $len = ['month' => '+1 month', 'quarter' => '+3 months', 'year' => '+1 year'][$m['period']] ?? '+1 month';
                    $mult = ['month' => 1, 'quarter' => 3, 'year' => 12][$m['period']] ?? 1;
                    DB::update('memberships', [
                        'status' => 'active', 'starts_at' => date('Y-m-d'), 'ends_at' => date('Y-m-d', strtotime($len)),
                        'credits_remaining' => (int)$m['credits'] * $mult,
                    ], 'id = ?', [$m['id']]);
                }
            } elseif ($p['purpose'] === 'event') {
                DB::update('event_registrations', ['status' => 'confirmed'], 'id = ? AND status = ?', [$p['reference_id'], 'pending']);
            }
        });
        $p = DB::one('SELECT purpose, reference_id FROM payments WHERE id = ?', [$paymentId]);
        if ($p && $p['purpose'] === 'booking') Mailer::bookingConfirmed((int)$p['reference_id']);
    }

    private static function stripe(string $method, string $path, array $data = []): array
    {
        $ch = curl_init('https://api.stripe.com/v1/' . $path . ($method === 'GET' && $data ? '?' . http_build_query($data) : ''));
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_USERPWD => config('stripe.secret_key') . ':',
            CURLOPT_TIMEOUT => 20,
        ]);
        if ($method === 'POST') { curl_setopt($ch, CURLOPT_POST, true); curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data)); }
        $raw = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        $res = json_decode((string)$raw, true) ?: [];
        if ($code >= 400) log_line('stripe.log', "HTTP $code $path " . substr((string)$raw, 0, 500));
        return $res;
    }
}
