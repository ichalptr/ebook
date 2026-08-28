<?php
/**
 * Mailer SMTP minimal (tanpa Composer/PHPMailer) — cukup buat kirim email
 * verifikasi & reset password. Kalau SMTP_USER kosong di config/db.php,
 * fungsi ini otomatis return false (caller lalu menampilkan link di layar).
 */

/**
 * Kirim email lewat SMTP. Return true kalau berhasil, false kalau gagal
 * atau SMTP belum dikonfigurasi.
 */
function send_email(string $to, string $subject, string $bodyHtml): bool {
    if (!SMTP_USER || !SMTP_HOST) {
        return false; // Mode dev: belum dikonfigurasi, biar caller tampilkan link manual
    }

    try {
        $useSSL = (int)SMTP_PORT === 465;
        $transport = $useSSL ? 'ssl://' . SMTP_HOST : SMTP_HOST;
        $socket = @fsockopen($transport, (int)SMTP_PORT, $errno, $errstr, 10);
        if (!$socket) return false;

        $read = fn() => fgets($socket, 512);
        $write = function (string $cmd) use ($socket) { fwrite($socket, $cmd . "\r\n"); };

        $read(); // greeting
        $write('EHLO localhost'); while (($l = $read()) && str_contains($l, '-')) {}

        if (!$useSSL && (int)SMTP_PORT === 587) {
            $write('STARTTLS'); $read();
            stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
            $write('EHLO localhost'); while (($l = $read()) && str_contains($l, '-')) {}
        }

        $write('AUTH LOGIN'); $read();
        $write(base64_encode(SMTP_USER)); $read();
        $write(base64_encode(SMTP_PASS)); $resp = $read();
        if (!str_starts_with($resp, '235')) { fclose($socket); return false; }

        $write('MAIL FROM:<' . SMTP_USER . '>'); $read();
        $write('RCPT TO:<' . $to . '>'); $read();
        $write('DATA'); $read();

        $headers = "From: " . SMTP_FROM_NAME . " <" . SMTP_USER . ">\r\n";
        $headers .= "To: <$to>\r\n";
        $headers .= "Subject: $subject\r\n";
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";

        $write($headers . "\r\n" . $bodyHtml . "\r\n.");
        $resp = $read();
        $write('QUIT');
        fclose($socket);

        return str_starts_with($resp, '250');
    } catch (\Throwable $e) {
        return false;
    }
}
