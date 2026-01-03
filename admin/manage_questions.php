<?php
session_start();
include "../includes/db.php";

/* =====================
   ADMIN AUTH CHECK
===================== */
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../index.php");
    exit;
}

/* =====================
   ADD QUESTION
===================== */
if (isset($_POST['add_question'])) {

    $exam_id = (int)$_POST['exam_id'];
    $question = trim($_POST['question']);
    $type = $_POST['question_type'];

    if ($type === 'MCQ') {

        $opt_a = $_POST['opt_a'];
        $opt_b = $_POST['opt_b'];
        $opt_c = $_POST['opt_c'];
        $opt_d = $_POST['opt_d'];
        $correct = $_POST['correct_option'];

        $stmt = $conn->prepare("
            INSERT INTO questions
            (exam_id, question, question_type, option_a, option_b, option_c, option_d, correct_option)
            VALUES (?,?,?,?,?,?,?,?)
        ");
        $stmt->bind_param(
            "isssssss",
            $exam_id,
            $question,
            $type,
            $opt_a,
            $opt_b,
            $opt_c,
            $opt_d,
            $correct
        );
        $stmt->execute();
        $stmt->close();

    } else { // SAQ

        $correct_text = trim($_POST['correct_text']);

        $stmt = $conn->prepare("
            INSERT INTO questions
            (exam_id, question, question_type, correct_text)
            VALUES (?,?,?,?)
        ");
        $stmt->bind_param(
            "isss",
            $exam_id,
            $question,
            $type,
            $correct_text
        );
        $stmt->execute();
        $stmt->close();
    }

    header("Location: manage_questions.php");
    exit;
}

/* =====================
   DELETE QUESTION
===================== */
if (isset($_POST['delete_question'])) {
    $id = (int)$_POST['delete_id'];
    $conn->query("DELETE FROM questions WHERE id=$id");
}

/* =====================
   FETCH EXAMS
===================== */
$exams = $conn->query("SELECT * FROM exams ORDER BY exam_name");

/* =====================
   FETCH QUESTIONS
===================== */
$questions = $conn->query("
    SELECT q.*, e.exam_name
    FROM questions q
    JOIN exams e ON q.exam_id = e.id
    ORDER BY q.id DESC
");
?>

<!DOCTYPE html>
<html>
<head>
<title>Manage Questions</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

<style>
body { background:#f4f6f9; }
.main { margin-left:220px; padding:25px; }
.card { border-radius:14px; }
.hidden { display:none; }
</style>
</head>

<body>
<?php include "../includes/sidebar.php"; ?>

<div class="main">
<h4 class="fw-bold mb-3">📝 Manage Questions</h4>

<!-- ADD QUESTION -->
<div class="card p-4 mb-4 shadow-sm">
<form method="POST">

<select name="exam_id" class="form-select mb-2" required>
<option value="">Select Exam</option>
<?php while($ex = $exams->fetch_assoc()) { ?>
<option value="<?= $ex['id'] ?>"><?= $ex['exam_name'] ?></option>
<?php } ?>
</select>

<textarea name="question" class="form-control mb-2"
placeholder="Enter question" required></textarea>

<!-- QUESTION TYPE -->
<select name="question_type" id="questionType"
class="form-select mb-3" required>
<option value="MCQ">MCQ</option>
<option value="SAQ">Short Answer (SAQ)</option>
</select>

<!-- MCQ OPTIONS -->
<div id="mcqBox">
<input type="text" name="opt_a" class="form-control mb-2" placeholder="Option A">
<input type="text" name="opt_b" class="form-control mb-2" placeholder="Option B">
<input type="text" name="opt_c" class="form-control mb-2" placeholder="Option C">
<input type="text" name="opt_d" class="form-control mb-2" placeholder="Option D">

<select name="correct_option" class="form-select mb-2">
<option value="">Correct Option</option>
<option value="A">Option A</option>
<option value="B">Option B</option>
<option value="C">Option C</option>
<option value="D">Option D</option>
</select>
</div>

<!-- SAQ ANSWER -->
<div id="saqBox" class="hidden">
<input type="text" name="correct_text"
class="form-control mb-2"
placeholder="Correct Answer (Short)">
</div>

<button type="submit" name="add_question"
class="btn btn-success w-100">
Add Question
</button>

</form>
</div>

<!-- QUESTION LIST -->
<div class="card shadow-sm">
<div class="card-body">
<table class="table table-bordered align-middle">
<thead>
<tr>
<th>ID</th>
<th>Exam</th>
<th>Type</th>
<th>Question</th>
<th>Answer</th>
<th>Action</th>
</tr>
</thead>
<tbody>

<?php while($row = $questions->fetch_assoc()) { ?>
<tr>
<td><?= $row['id'] ?></td>
<td><?= $row['exam_name'] ?></td>
<td>
<span class="badge <?= $row['question_type']=='MCQ'?'bg-primary':'bg-warning text-dark' ?>">
<?= $row['question_type'] ?>
</span>
</td>
<td><?= htmlspecialchars($row['question']) ?></td>
<td>
<?php
if (isset($row['question_type']) && $row['question_type'] === 'MCQ') {
    echo htmlspecialchars($row['correct_option'] ?? '');
} else {
    echo htmlspecialchars($row['correct_text'] ?? '');
}
?>
</td>
<td>
<form method="POST" class="d-inline">
<input type="hidden" name="delete_id" value="<?= $row['id'] ?>">
<button name="delete_question"
class="btn btn-sm btn-danger"
onclick="return confirm('Delete this question?')">
Delete
</button>
</form>
</td>
</tr>
<?php } ?>

</tbody>
</table>
</div>
</div>

</div>

<script>
document.getElementById('questionType').addEventListener('change', function(){
    if(this.value === 'SAQ'){
        document.getElementById('mcqBox').classList.add('hidden');
        document.getElementById('saqBox').classList.remove('hidden');
    } else {
        document.getElementById('mcqBox').classList.remove('hidden');
        document.getElementById('saqBox').classList.add('hidden');
    }
});
</script>

</body>
</html>
