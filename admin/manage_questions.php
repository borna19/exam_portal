<?php
session_start();
include "../includes/db.php";

/* =====================
   ADMIN AUTH CHECK
===================== */
if (!isset($_SESSION['admin_id'])) {
    header("Location: dashboard.php");
    exit;
}

$currentPage = 'questions';

/* =====================
   ADD QUESTION
===================== */
if (isset($_POST['add_question'])) {
    $exam_id   = $_POST['exam_id'];
    $question  = $_POST['question'];
    $opt_a     = $_POST['opt_a'];
    $opt_b     = $_POST['opt_b'];
    $opt_c     = $_POST['opt_c'];
    $opt_d     = $_POST['opt_d'];
    $correct   = $_POST['correct_option'];

    $conn->query("INSERT INTO questions 
        (exam_id, question, option_a, option_b, option_c, option_d, correct_option)
        VALUES 
        ('$exam_id','$question','$opt_a','$opt_b','$opt_c','$opt_d','$correct')");

    header("Location: manage_questions.php");
    exit;
}

/* =====================
   DELETE QUESTION
===================== */
if (isset($_POST['delete_question'])) {
    $id = $_POST['delete_id'];
    $conn->query("DELETE FROM questions WHERE id='$id'");
}

/* =====================
   FETCH EXAMS
===================== */
$exams = $conn->query("SELECT * FROM exams ORDER BY exam_name ASC");

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
        body{margin:0;font-family:Arial;}
        .main{margin-left:220px;padding:20px;}
    </style>
</head>
<body>

<!-- SIDEBAR -->
<?php include __DIR__ . "/../includes/sidebar.php"; ?>

<div class="main">
    <h3>Manage Questions</h3>

    <!-- ADD QUESTION -->
    <div class="card p-3 mb-4">
        <form method="POST">

            <select name="exam_id" class="form-control mb-2" required>
                <option value="">Select Exam</option>
                <?php while($ex = $exams->fetch_assoc()) { ?>
                    <option value="<?= $ex['id'] ?>">
                        <?= $ex['exam_name'] ?>
                    </option>
                <?php } ?>
            </select>

            <textarea name="question" class="form-control mb-2"
                      placeholder="Enter question" required></textarea>

            <input type="text" name="opt_a" class="form-control mb-2" placeholder="Option A" required>
            <input type="text" name="opt_b" class="form-control mb-2" placeholder="Option B" required>
            <input type="text" name="opt_c" class="form-control mb-2" placeholder="Option C" required>
            <input type="text" name="opt_d" class="form-control mb-2" placeholder="Option D" required>

            <select name="correct_option" class="form-control mb-2" required>
                <option value="">Correct Answer</option>
                <option value="A">Option A</option>
                <option value="B">Option B</option>
                <option value="C">Option C</option>
                <option value="D">Option D</option>
            </select>

            <button type="submit" name="add_question" class="btn btn-success">
                Add Question
            </button>
        </form>
    </div>

    <!-- QUESTION LIST -->
    <table class="table table-bordered">
        <tr>
            <th>ID</th>
            <th>Exam</th>
            <th>Question</th>
            <th>Correct</th>
            <th>Action</th>
        </tr>

        <?php while($row = $questions->fetch_assoc()) { ?>
        <tr>
            <td><?= $row['id'] ?></td>
            <td><?= $row['exam_name'] ?></td>
            <td><?= $row['question'] ?></td>
            <td><?= $row['correct_option'] ?></td>
            <td>
                <form method="POST" style="display:inline;">
                    <input type="hidden" name="delete_id" value="<?= $row['id'] ?>">
                    <button type="submit" name="delete_question"
                            onclick="return confirm('Delete this question?')"
                            class="btn btn-danger btn-sm">
                        Delete
                    </button>
                </form>
            </td>
        </tr>
        <?php } ?>
    </table>
</div>

</body>
</html>
z