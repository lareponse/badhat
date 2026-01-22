<?php

if(empty($_POST))
    return;

$conf = [];
foreach (['SMTP_HOST','SMTP_PORT','SMTP_USER','SMTP_PASS'] as $k) {
    $raw = getenv($k);
    $v   = ($raw === false) ? '' : trim((string) $raw);
    $conf[$k] = ($v !== '') ? $v : throw new RuntimeException("Missing ENV for mailer: $k", 500);
}

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

require 'add/PHPMailer-master/src/Exception.php';
require 'add/PHPMailer-master/src/PHPMailer.php';
require 'add/PHPMailer-master/src/SMTP.php';

$mail = new PHPMailer(true);

$subject = 'Here is the subject';
$body_t  = $_POST['category'] .' : '. $_POST['message'];
$body    = $body_t;

$contact_email = $_POST['email'];
$contact_name  = $_POST['name'];

$destination_email = 'info@irsa.be';
$destination_name = 'IRSA Destinataire';

$destination_email = 'irsa@amstram.be';     // ------------ //////////

try {
    // Debug (turn off in prod)
    $mail->SMTPDebug = SMTP::DEBUG_SERVER; // or SMTP::DEBUG_OFF
    $mail->Debugoutput = 'error_log';      // avoids printing secrets to output
    
    $mail->SMTPDebug = SMTP::DEBUG_OFF;

    // SMTP
    $mail->isSMTP();
    $mail->Host       = $conf['SMTP_HOST'];
    $mail->Port       = (int) $conf['SMTP_PORT'];
    $mail->SMTPAuth   = true;
    $mail->Username   = $conf['SMTP_USER'];
    $mail->Password   = $conf['SMTP_PASS'];
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;

    // Mail headers
    $mail->setFrom('site@irsa.be', 'Contact IRSA.be');
    $mail->addAddress($destination_email, $destination_name);
    $mail->addReplyTo($contact_email, $contact_name);

    // Content
    $mail->isHTML(true);
    $mail->Subject = $subject;
    $mail->Body    = $body;
    $mail->AltBody = $body_t;

    $mail->send();
    $sent = true;
} catch (Exception $e) {
    // In production: log $e->getMessage() / $mail->ErrorInfo, don’t echo to users
    trigger_error($e->getMessage(), E_USER_WARNING);
    return false;
}