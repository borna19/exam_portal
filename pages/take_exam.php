<?php
// pages/take_exam.php
include __DIR__ . '/../includes/db.php';

$token = $_GET['token'] ?? '';
if (!$token) {
    echo "<p style='padding:20px'>Invalid or missing token.</p>";
    exit;
}

// Fetch exam by token
$stmt = $conn->prepare("SELECT * FROM exams WHERE token = ? LIMIT 1");
$stmt->bind_param('s', $token);
$stmt->execute();
$res = $stmt->get_result();
$exam = $res->fetch_assoc();
$stmt->close();

if (!$exam) {
    echo "<p style='padding:20px'>Exam not found or link expired.</p>";
    exit;
}

// Fetch questions
$qstmt = $conn->prepare("SELECT * FROM questions WHERE exam_id = ? ORDER BY id ASC");
$qstmt->bind_param('i', $exam['id']);
$qstmt->execute();
$qres = $qstmt->get_result();
$questions = [];
while ($r = $qres->fetch_assoc()) $questions[] = $r;
$qstmt->close();

// Handle submission
$resultMsg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $student_name = trim($_POST['student_name'] ?? 'Anonymous');
    $answers = $_POST['answers'] ?? [];

    $total = count($questions);
    $correctCount = 0;

    foreach ($questions as $q) {
        $qid = $q['id'];
        $submitted = isset($answers[$qid]) ? trim($answers[$qid]) : '';

        if ($q['question_type'] === 'MCQ') {
            if (strcasecmp($submitted, $q['correct_option']) === 0) {
                $correctCount++;
            }
        } else { // SAQ - simple exact match (case-insensitive)
            $correctText = trim($q['correct_text'] ?? '');
            if ($correctText !== '' && strcasecmp($submitted, $correctText) === 0) {
                $correctCount++;
            }
        }
    }

    $score = $correctCount;
    $totalMarks = $total; // 1 mark per question
    $percent = $total > 0 ? round(($score / $total) * 100, 2) : 0;
    $status = ($percent >= 50) ? 'Pass' : 'Fail';

    // Save result (basic)
    $ins = $conn->prepare("INSERT INTO results (student_name, exam_name, score, total_marks, result_status, created_at) VALUES (?,?,?,?,?,NOW())");
    $ins->bind_param('ssiis', $student_name, $exam['exam_name'], $score, $totalMarks, $status);
    $ins->execute();
    $ins->close();

    $resultMsg = "<div class=\"alert alert-info\">Thank you <strong>" . htmlspecialchars($student_name) . "</strong>. You scored <strong>$score</strong> out of <strong>$totalMarks</strong> ($percent%). Status: <strong>$status</strong></div>";
}

?>

<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($exam['exam_name']) ?> - Take Exam</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body style="padding:20px;">

<div class="container">
    <div class="card p-4 shadow-sm">
        <h4 class="mb-1"><?= htmlspecialchars($exam['exam_name']) ?></h4>
        <p class="text-muted">Duration: <?= (int)$exam['duration'] ?> minutes</p>

        <?php if($resultMsg) echo $resultMsg; ?>

        <?php if (count($questions) === 0): ?>
            <div class="alert alert-warning">No questions available for this exam.</div>
        <?php else: ?>

        <form method="POST">
            <div class="mb-3">
                <label>Student Name</label>
                <input type="text" name="student_name" class="form-control" required placeholder="Your name">
            </div>

            <?php foreach ($questions as $i => $q): ?>
                <div class="mb-3 border rounded p-3">
                    <strong>Q<?= $i+1 ?>.</strong> <?= htmlspecialchars($q['question']) ?>
                    <div class="mt-2">
                        <?php if ($q['question_type'] === 'MCQ'): ?>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="answers[<?= $q['id'] ?>]" id="q<?= $q['id'] ?>a" value="A">
                                <label class="form-check-label" for="q<?= $q['id'] ?>a"><?= htmlspecialchars($q['option_a']) ?></label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="answers[<?= $q['id'] ?>]" id="q<?= $q['id'] ?>b" value="B">
                                <label class="form-check-label" for="q<?= $q['id'] ?>b"><?= htmlspecialchars($q['option_b']) ?></label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="answers[<?= $q['id'] ?>]" id="q<?= $q['id'] ?>c" value="C">
                                <label class="form-check-label" for="q<?= $q['id'] ?>c"><?= htmlspecialchars($q['option_c']) ?></label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="answers[<?= $q['id'] ?>]" id="q<?= $q['id'] ?>d" value="D">
                                <label class="form-check-label" for="q<?= $q['id'] ?>d"><?= htmlspecialchars($q['option_d']) ?></label>
                            </div>
                        <?php else: ?>
                            <input type="text" name="answers[<?= $q['id'] ?>]" class="form-control" placeholder="Type your answer here">
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>

            <div class="d-flex justify-content-between">
                <a class="btn btn-outline-secondary" href="/exam_portal/index.php">Back</a>
                <button class="btn btn-primary" type="submit">Submit Exam</button>
            </div>
        </form>

        <?php endif; ?>
    </div>
</div>

</body>
</html>