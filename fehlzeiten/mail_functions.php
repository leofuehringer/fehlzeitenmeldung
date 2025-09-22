<?php
// mail_functions.php

// Import PHPMailer classes into the global namespace
// These must be at the top of your script, not inside a function
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Load Composer's autoloader (created by composer, not included with PHPMailer)
require 'vendor/autoload.php';

function send_mail($to, $message, $cc)
{
    $smtp_log = ""; // Variable zum Speichern der SMTP-Debug-Ausgaben

    if (USE_SMTP) {
        // Create an instance; passing `true` enables exceptions
        $mail = new PHPMailer(true);

        try {
            // Server settings
            $mail->isSMTP();                                            // Send using SMTP
            $mail->Host       = SMTP_HOST;                              // Set the SMTP server to send through
            $mail->SMTPAuth   = true;                                   // Enable SMTP authentication
            $mail->Username   = SMTP_USER;                              // SMTP username
            $mail->Password   = SMTP_PASS;                              // SMTP password
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;            // Enable implicit TLS encryption
            $mail->Port       = SMTP_PORT;                              // TCP port to connect to

            // Aktivieren der Debug-Ausgabe und Speichern in der Variable
            $mail->SMTPDebug = 2; // 2 für Debug-Ausgabe ohne Popup
            $mail->Debugoutput = function($str, $level) use (&$smtp_log) {
                $smtp_log .= $str . "<br>\n";
            };

            // Recipients
            $mail->setFrom(MAIL_FROM, MAIL_FROM_NAME);
            $mail->addAddress($to);

            if ($cc) {
                $mail->addCC(MAIL_FROM);
            }

            // Content
            $mail->isHTML(false);
            $mail->Subject = MAIL_SUBJECT;
            $mail->Body    = $message;

            $mail->send();
            return "ok";
        } catch (Exception $e) {
            error_log("Fehler beim Senden der E-Mail: {$mail->ErrorInfo}");
            return "Fehler: " . $mail->ErrorInfo;
        }
    } else {
        $to = str_replace(';', ',', $to);
        echo "Mail an: $to: ";
        $header = "From: " . MAIL_FROM_NAME . " <" . MAIL_FROM . ">\n";
        if ($cc) {
            $header .= "Cc: " . MAIL_FROM . "\r\n";
        }
        $header .= "Reply-To: " . MAIL_FROM . "\nX-Mailer: PHP/fehlzeitentool.HHS\n";
        $header .= "Return-path: <" . MAIL_FROM . ">\n";
        $header .= "Mime-Version: 1.0\n";
        $header .= "Content-type: text/plain; charset=utf-8\n";

        if (mail($to, MAIL_SUBJECT, $message, $header)) {
            return "ok";
        } else {
            return "Fehler beim Senden der E-Mail";
        }
    }
}
