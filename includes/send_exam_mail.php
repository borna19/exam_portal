<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/PHPMailer/src/Exception.php';
require __DIR__ . '/PHPMailer/src/PHPMailer.php';
require __DIR__ . '/PHPMailer/src/SMTP.php';

function sendExamMail($toEmail, $studentName, $examTitle, $examLink)
{
    $mail = new PHPMailer(true);

    try {
        // SMTP SETTINGS
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'barnalibhowmik19@gmail.com'; // 
        $mail->Password   = 'jhnw bfdo hasx egpu';  //   
        $mail->SMTPSecure = 'tls';
        $mail->Port       = 587;

        // EMAIL SETTINGS
        $mail->setFrom('barnalibhowmik19@gmail.com', 'Exam Portal');
        $mail->addAddress($toEmail, $studentName);

        $mail->isHTML(true);
        $mail->Subject = "Exam Link - $examTitle";

        $mail->Body = "
        <h3>Hello $studentName,</h3>
        <p>Your exam link is ready.</p>

        <p><b>Exam:</b> $examTitle</p>

        <p>
            <a href='$examLink'
               style='padding:10px 15px;
               background:#198754;
               color:#fff;
               text-decoration:none;
               border-radius:5px;'>
               Start Exam
            </a>
        </p>

        <p>Good luck!<br><b>Exam Portal</b></p>
        ";

        $mail->send();
        return true;

    } catch (Exception $e) {
        return false;
    }
}
