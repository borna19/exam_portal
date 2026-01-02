<?php
$conn = new mysqli("localhost", "root", "", "exam_portal");

if ($conn->connect_error) {
    die("Database connection failed");
}
?>
