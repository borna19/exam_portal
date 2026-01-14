<?php
include __DIR__ . '/../includes/db.php';

$token = $_GET['token'] ?? '';

if (!$token) {
    die("Invalid exam link.");
}

/* Fetch exam by token */
$stmt = $conn->prepare("SELECT * FROM exams WHERE token = ? LIMIT 1");
$stmt->bind_param("s", $token);
$stmt->execute();
$exam = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$exam) {
    die("Exam not found or link expired.");
}

/* Fetch questions for this exam */
$qstmt = $conn->prepare("
    SELECT q.*
    FROM questions q
    JOIN exam_questions eq ON eq.question_id = q.id
    WHERE eq.exam_id = ?
");
$qstmt->bind_param("i", $exam['id']);
$qstmt->execute();
$questions = $qstmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title><?= htmlspecialchars($exam['title']) ?> | Take Exam</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">
<div class="container mt-4">

    <div class="card shadow p-4">
        <h4><?= htmlspecialchars($exam['title']) ?></h4>
        <p class="text-muted">
            Duration: <?= (int)$exam['duration_minutes'] ?> minutes
        </p>

        <?php if ($questions->num_rows == 0): ?>
            <div class="alert alert-warning">No questions found for this exam.</div>
        <?php else: ?>

        <form method="post" action="">
            <div class="mb-3">
                <label class="form-label">Your Name</label>
                <input type="text" name="student_name" class="form-control" required>
            </div>

            <?php $i=1; while($q = $questions->fetch_assoc()): ?>
                <div class="border rounded p-3 mb-3">
                    <strong>Q<?= $i++ ?>.</strong> <?= htmlspecialchars($q['question']) ?>

                    <div class="mt-2">
                        <div class="form-check">
                            <input class="form-check-input" type="radio"
                                   name="answers[<?= $q['id'] ?>]" value="A" required>
                            <label class="form-check-label"><?= htmlspecialchars($q['opt_a']) ?></label>
                        </div>

                        <div class="form-check">
                            <input class="form-check-input" type="radio"
                                   name="answers[<?= $q['id'] ?>]" value="B">
                            <label class="form-check-label"><?= htmlspecialchars($q['opt_b']) ?></label>
                        </div>

                        <div class="form-check">
                            <input class="form-check-input" type="radio"
                                   name="answers[<?= $q['id'] ?>]" value="C">
                            <label class="form-check-label"><?= htmlspecialchars($q['opt_c']) ?></label>
                        </div>

                        <div class="form-check">
                            <input class="form-check-input" type="radio"
                                   name="answers[<?= $q['id'] ?>]" value="D">
                            <label class="form-check-label"><?= htmlspecialchars($q['opt_d']) ?></label>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>

            <button class="btn btn-primary">Submit Exam</button>
        </form>

        <?php endif; ?>
    </div>
</div>
</body>
</html>
