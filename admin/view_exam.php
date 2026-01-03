<?php
session_start();
include "../includes/db.php";

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    die('Invalid exam id');
}

$examRes = $conn->query("SELECT id, exam_name, duration FROM exams WHERE id='$id'");
$exam = $examRes ? $examRes->fetch_assoc() : null;
if (!$exam) {
    die('Exam not found');
}

$questions = $conn->query("SELECT * FROM questions WHERE exam_id='$id' ORDER BY id ASC");
?>
<!DOCTYPE html>
<html>
<head>
    <title>View Exam - <?= htmlspecialchars($exam['exam_name']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<?php include __DIR__ . "/../includes/sidebar.php"; ?>
<div class="main" style="margin-left:220px;padding:20px;">
    <h3>Exam: <?= htmlspecialchars($exam['exam_name']) ?></h3>
    <p><strong>Duration:</strong> <?= htmlspecialchars($exam['duration']) ?> min</p>

    <h5>Questions</h5>
    <?php if ($questions && $questions->num_rows > 0): ?>
        <table class="table table-bordered">
            <tr>
                <th>#</th>
                <th>Question</th>
                <th>Options</th>
                <th>Correct</th>
            </tr>
            <?php while ($q = $questions->fetch_assoc()): ?>
            <tr>
                <td><?= $q['id'] ?></td>
                <td><?= htmlspecialchars($q['question']) ?></td>
                <td>
                    A) <?= htmlspecialchars($q['option_a']) ?><br>
                    B) <?= htmlspecialchars($q['option_b']) ?><br>
                    C) <?= htmlspecialchars($q['option_c']) ?><br>
                    D) <?= htmlspecialchars($q['option_d']) ?>
                </td>
                <td><?= htmlspecialchars($q['correct_option']) ?></td>
            </tr>
            <?php endwhile; ?>
        </table>
    <?php else: ?>
        <div class="alert alert-info">No questions added for this exam yet.</div>
    <?php endif; ?>

    <a href="manage_questions.php" class="btn btn-secondary">Manage Questions</a>
    <a href="dashboard.php" class="btn btn-light">Back to Dashboard</a>
</div>
</body>
</html>