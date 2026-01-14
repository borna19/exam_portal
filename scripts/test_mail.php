<?php
require_once __DIR__ . '/../includes/send_exam_mail.php';

$toEmail = "writjana03@gmail.com"; // put your own email here
$studentName = "Test Student";
$examTitle = "Demo Exam";
$examLink = "http://localhost/exam_portal/pages/take_exam.php?token=TEST123";

$result = sendExamMail($toEmail, $studentName, $examTitle, $examLink);

if ($result === true) {
    echo "✅ Mail sent successfully!";
} else {
    echo "❌ Mail failed: " . $result;
}
