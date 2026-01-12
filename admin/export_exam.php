<?php
// admin/export_exam.php
session_start();
include "../includes/db.php";
header('Content-Type: application/json');

if (!isset($_SESSION['admin_id'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Forbidden']);
    exit;
}

$exam_id = isset($_GET['exam_id']) ? (int)$_GET['exam_id'] : 0;
if (!$exam_id) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing exam_id']);
    exit;
}

$stmt = $conn->prepare("SELECT id, exam_name, duration FROM exams WHERE id = ? LIMIT 1");
$stmt->bind_param('i', $exam_id);
$stmt->execute();
$res = $stmt->get_result();
$exam = $res->fetch_assoc();
$stmt->close();

if (!$exam) {
    http_response_code(404);
    echo json_encode(['error' => 'Exam not found']);
    exit;
}

$qstmt = $conn->prepare("SELECT * FROM questions WHERE exam_id = ? ORDER BY id ASC");
$qstmt->bind_param('i', $exam_id);
$qstmt->execute();
$qres = $qstmt->get_result();
$questions = [];
while ($r = $qres->fetch_assoc()) {
    $questions[] = [
        'id' => (int)$r['id'],
        'question' => $r['question'],
        'question_type' => $r['question_type'],
        'options' => [
            'A' => $r['option_a'] ?? '',
            'B' => $r['option_b'] ?? '',
            'C' => $r['option_c'] ?? '',
            'D' => $r['option_d'] ?? ''
        ],
        'correct_option' => $r['correct_option'] ?? null,
        'correct_text' => $r['correct_text'] ?? null
    ];
}
$qstmt->close();

echo json_encode(['exam' => $exam, 'questions' => $questions]);
