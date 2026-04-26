<?php
declare(strict_types=1);

/**
 * Enviament de correu amb PHPMailer i SMTP real.
 *
 * @return array{ok:bool,error:?string}
 */
function app_mail_send_with_attachment(
    string $to,
    string $subject,
    string $bodyText,
    string $attachmentAbsolutePath,
    string $attachmentFileName
): array {
    $to = trim($to);
    if ($to === '' || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
        return ['ok' => false, 'error' => 'Destinatari no vàlid.'];
    }
    if (!is_readable($attachmentAbsolutePath) || !is_file($attachmentAbsolutePath)) {
        return ['ok' => false, 'error' => 'Fitxer adjunt no trobat.'];
    }

    $vendorAutoload = APP_ROOT . '/vendor/autoload.php';
    if (!is_readable($vendorAutoload)) {
        return ['ok' => false, 'error' => 'Falta vendor/autoload.php. Executeu "composer install".'];
    }
    require_once $vendorAutoload;

    if (!class_exists(\PHPMailer\PHPMailer\PHPMailer::class)) {
        return ['ok' => false, 'error' => 'PHPMailer no disponible al vendor. Executeu "composer install".'];
    }

    $host = defined('MAIL_HOST') ? trim((string) MAIL_HOST) : '';
    $port = defined('MAIL_PORT') ? (int) MAIL_PORT : 0;
    $user = defined('MAIL_USER') ? trim((string) MAIL_USER) : '';
    $pass = defined('MAIL_PASS') ? (string) MAIL_PASS : '';
    $fromAddr = defined('MAIL_FROM_ADDRESS') ? trim((string) MAIL_FROM_ADDRESS) : '';
    $fromName = defined('MAIL_FROM_NAME') ? trim((string) MAIL_FROM_NAME) : 'Formació';
    $secure = defined('MAIL_SECURE') ? strtolower(trim((string) MAIL_SECURE)) : '';

    if ($host === '' || $port < 1 || $fromAddr === '' || !filter_var($fromAddr, FILTER_VALIDATE_EMAIL)) {
        return ['ok' => false, 'error' => 'Configuració SMTP incompleta: reviseu MAIL_HOST, MAIL_PORT i MAIL_FROM_ADDRESS.'];
    }
    if ($user === '' || $pass === '') {
        return ['ok' => false, 'error' => 'Configuració SMTP incompleta: reviseu MAIL_USER i MAIL_PASS.'];
    }

    $secureMap = [
        'tls' => \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS,
        'starttls' => \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS,
        'ssl' => \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS,
        'smtps' => \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS,
        '' => $port === 465
            ? \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS
            : \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS,
    ];
    if (!array_key_exists($secure, $secureMap)) {
        return ['ok' => false, 'error' => 'MAIL_SECURE no vàlid. Valors admesos: tls, starttls, ssl, smtps o buit.'];
    }

    try {
        $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
        $mail->isSMTP();
        $mail->Host = $host;
        $mail->Port = $port;
        $mail->SMTPAuth = true;
        $mail->Username = $user;
        $mail->Password = $pass;
        $mail->SMTPSecure = $secureMap[$secure];
        $mail->Timeout = 30;
        $mail->CharSet = 'UTF-8';

        $mail->setFrom($fromAddr, $fromName);
        $mail->addAddress($to);
        $mail->Subject = $subject;
        $mail->isHTML(true);
        // Cos HTML senzill i segur a partir del text pla del flux actual.
        $mail->Body = nl2br(htmlspecialchars(trim($bodyText), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'));
        $mail->AltBody = $bodyText;
        $mail->addAttachment($attachmentAbsolutePath, $attachmentFileName);
        $mail->send();
    } catch (\Throwable $e) {
        $detail = $e->getMessage();
        if (isset($mail) && $mail instanceof \PHPMailer\PHPMailer\PHPMailer && $mail->ErrorInfo !== '') {
            $detail = $mail->ErrorInfo;
        }
        return ['ok' => false, 'error' => 'Error SMTP: ' . $detail];
    }

    return ['ok' => true, 'error' => null];
}
