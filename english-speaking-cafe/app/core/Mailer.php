<?php
/** Envoi d'e-mails via mail() (fonctionne nativement sur cPanel) */
declare(strict_types=1);

namespace App\Core;

final class Mailer
{
    public static function send(string $to, string $subject, string $html): bool
    {
        if (!filter_var($to, FILTER_VALIDATE_EMAIL)) return false;
        $from = (string)config('mail.from', 'hello@' . ($_SERVER['HTTP_HOST'] ?? 'localhost'));
        $name = (string)config('mail.from_name', 'XY Academy Speaking Café');
        $headers = [
            'MIME-Version: 1.0',
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . mb_encode_mimeheader($name) . " <$from>",
            'Reply-To: ' . $from,
        ];
        $body = '<div style="font-family:Arial,sans-serif;background:#f7f3ea;padding:32px"><div style="max-width:560px;margin:auto;background:#fff;border-radius:16px;padding:32px;color:#1f1a17">'
            . '<p style="font-family:Georgia,serif;font-size:22px;color:#0f4a37;margin:0 0 16px"><b>XY Academy</b> · Speaking Café</p>'
            . $html . '<p style="color:#8a7f72;font-size:12px;margin-top:32px">Speak English. For real.</p></div></div>';
        $ok = @mail($to, mb_encode_mimeheader($subject), $body, implode("\r\n", $headers));
        log_line('mail.log', ($ok ? 'SENT ' : 'FAIL ') . "$to — $subject");
        return $ok;
    }

    public static function notifyPromotion(int $bookingId): void
    {
        $b = DB::one('SELECT b.status, u.email, u.first_name, p.name, s.starts_at, payer.email AS payer_email FROM bookings b JOIN users u ON u.id = b.user_id JOIN users payer ON payer.id = b.booked_by JOIN sessions s ON s.id = b.session_id JOIN programs p ON p.id = s.program_id WHERE b.id = ?', [$bookingId]);
        if (!$b) return;
        $msg = $b['status'] === 'confirmed' ? 'Your seat is <b>confirmed</b>.' : 'A seat is held for you — please complete payment from your dashboard to confirm it.';
        self::send($b['payer_email'] ?: (string)$b['email'], 'Good news: a seat just opened up', '<p>Hi ' . e($b['first_name']) . ',</p><p>A place opened up in <b>' . e($b['name']) . '</b> on ' . e(fmt_date($b['starts_at'], 'l j F, H:i')) . '. ' . $msg . '</p><p><a href="' . e(full_url('dashboard')) . '">Open my dashboard</a></p>');
    }

    public static function bookingConfirmed(int $bookingId): void
    {
        $b = DB::one('SELECT u.first_name, payer.email, p.name, s.starts_at, t.display_name FROM bookings b JOIN users u ON u.id = b.user_id JOIN users payer ON payer.id = b.booked_by JOIN sessions s ON s.id = b.session_id JOIN programs p ON p.id = s.program_id LEFT JOIN teachers t ON t.id = s.teacher_id WHERE b.id = ?', [$bookingId]);
        if (!$b || !$b['email']) return;
        self::send($b['email'], 'Your seat is booked ☕', '<p>See you at the café!</p><p><b>' . e($b['name']) . '</b><br>' . e(fmt_date($b['starts_at'], 'l j F · H:i')) . ($b['display_name'] ? '<br>with ' . e($b['display_name']) : '') . '<br>Learner: ' . e($b['first_name']) . '</p>');
    }
}
