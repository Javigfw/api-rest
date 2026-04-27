<?php

namespace App\Modules\Services;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class DifusionService
{
    private $mailer;

    public function __construct()
    {
        $this->mailer = new PHPMailer(true);
        $this->mailer->isSMTP();
        $this->mailer->Host = 'smtp.gmail.com';
        $this->mailer->SMTPAuth = true;
        $this->mailer->Username = 'almanxa13@gmail.com'; 
        $this->mailer->Password = 'ydww pwai ykzo nqmq'; // Tienes que generar una contraseña de aplicación en Google
        $this->mailer->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $this->mailer->Port = 587;
        $this->mailer->CharSet = 'UTF-8';
        $this->mailer->isHTML(true);

        $this->mailer->setFrom('almanxa13@gmail.com', 'Administración DuoBusinessLingo');
    }

    public function sendEmail($toEmail, $toName, $subject, $body)
    {
        try {
            $this->mailer->clearAddresses();
            $this->mailer->addAddress($toEmail, $toName);
            $this->mailer->Subject = $subject;
            $this->mailer->Body = $body;

            return $this->mailer->send();
        } catch (Exception $e) {
            // En producción, usar Logger
            error_log("Fallo envío a $toEmail: " . $this->mailer->ErrorInfo);
            return false;
        }
    }
}
