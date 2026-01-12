<?php
session_start();
include '../includes/db.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: ../index.php");
    exit;
}

/* ================= CREATE EXAM ================= */
$successMsg = "";
$errorMsg = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_exam'])) {

    $title = $_POST['title'];
    $desc  = $_POST['description'];
    $duration = (int)$_POST['duration'];
    $questions = $_POST['questions'] ?? [];

    $stmt = $conn->prepare("INSERT INTO exams (title, description, duration_minutes) VALUES (?, ?, ?)");
    $stmt->bind_param("ssi", $title, $desc, $duration);

    if ($stmt->execute()) {
        $exam_id = $stmt->insert_id;

        $link = $conn->prepare("INSERT INTO exam_questions (exam_id, question_id) VALUES (?, ?)");
        foreach ($questions as $qid) {
            $link->bind_param("ii", $exam_id, $qid);
            $link->execute();
        }

        $successMsg = "Exam created successfully!";
    } else {
        $errorMsg = "Something went wrong!";
    }
}

/* ================= DELETE EXAM ================= */
if (isset($_POST['delete_exam'])) {
    $id = (int)$_POST['delete_id'];
    $conn->query("DELETE FROM exams WHERE id=$id");
}

/* ================= FETCH DATA ================= */
$exams = $conn->query("SELECT * FROM exams ORDER BY id DESC");
$allQuestions = $conn->query("SELECT id, question FROM questions ORDER BY id DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Manage Exams</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

<style>
body{
    margin:0;
    background:#f4f6f9;
    font-family:'Segoe UI',sans-serif;
}

/* Layout */
.wrapper{
    display:flex;
    min-height:100vh;
}

/* Main Content after sidebar */
.main-content{
    margin-left:220px;
    padding:30px;
    width:calc(100% - 220px);
}

/* Cards */
.card{
    border-radius:14px;
    box-shadow:0 10px 25px rgba(0,0,0,0.08);
    padding:25px;
    margin-bottom:25px;
}

/* Question box */
.question-box{
    max-height:260px;
    overflow-y:auto;
    border:1px solid #ddd;
    padding:12px;
    border-radius:10px;
    background:#fafafa;
}

/* Table */
.table{
    background:#fff;
    border-radius:12px;
    overflow:hidden;
}
.table thead{
    background:#2e7d32;
    color:#fff;
}
.table tbody tr:hover{
    background:#f1f8f5;
}

/* Buttons */
.btn-sm{
    border-radius:8px;
}

/* Mobile */
@media(max-width:768px){
    .main-content{
        margin-left:0;
        width:100%;
    }
}
</style>
</head>

<body>

<div class="wrapper">

    <!-- SIDEBAR -->
    <?php include '../includes/sidebar.php'; ?>

    <!-- MAIN CONTENT -->
    <div class="main-content">

        <!-- CREATE EXAM -->
        <div class="card">
            <h4>Create New Exam</h4>

            <?php if($successMsg): ?>
                <div class="alert alert-success"><?= $successMsg ?></div>
            <?php endif; ?>

            <?php if($errorMsg): ?>
                <div class="alert alert-danger"><?= $errorMsg ?></div>
            <?php endif; ?>

            <form method="post">
                <div class="row g-3">

                    <div class="col-md-6">
                        <label>Exam Title</label>
                        <input type="text" name="title" class="form-control" required>
                    </div>

                    <div class="col-md-6">
                        <label>Duration (minutes)</label>
                        <input type="number" name="duration" class="form-control" value="30" required>
                    </div>

                    <div class="col-12">
                        <label>Description</label>
                        <textarea name="description" class="form-control"></textarea>
                    </div>

                    <div class="col-12">
                        <label>Select Questions</label>
                        <div class="question-box">
                            <?php while($q = $allQuestions->fetch_assoc()): ?>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="questions[]" value="<?= $q['id'] ?>">
                                    <label class="form-check-label">
                                        <?= htmlspecialchars($q['question']) ?>
                                    </label>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    </div>

                </div>

                <button class="btn btn-success mt-3" name="create_exam">
                    Create Exam
                </button>
            </form>
        </div>

        <!-- EXAM LIST -->
        <div class="card">
            <h4>Existing Exams</h4>

            <table class="table table-bordered align-middle">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Description</th>
                        <th>Duration</th>
                        <th width="220">Actions</th>
                    </tr>
                </thead>

                <tbody>
                <?php while($e = $exams->fetch_assoc()): ?>
                    <tr>
                        <td><?= htmlspecialchars($e['title']) ?></td>
                        <td><?= htmlspecialchars($e['description']) ?></td>
                        <td><?= $e['duration_minutes'] ?> min</td>

                        <td class="d-flex gap-2">

                            <!-- EDIT -->
                            <button class="btn btn-warning btn-sm"
                                    data-bs-toggle="modal"
                                    data-bs-target="#edit<?= $e['id'] ?>">
                                Edit
                            </button>

                            <!-- VIEW -->
                            <button class="btn btn-info btn-sm"
                                    data-bs-toggle="modal"
                                    data-bs-target="#view<?= $e['id'] ?>">
                                View
                            </button>

                            <!-- DELETE -->
                            <form method="post" onsubmit="return confirm('Delete this exam?')">
                                <input type="hidden" name="delete_id" value="<?= $e['id'] ?>">
                                <button class="btn btn-danger btn-sm" name="delete_exam">
                                    Delete
                                </button>
                            </form>

                        </td>
                    </tr>

                    <!-- VIEW MODAL -->
                    <div class="modal fade" id="view<?= $e['id'] ?>">
                        <div class="modal-dialog modal-lg">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5>Questions - <?= htmlspecialchars($e['title']) ?></h5>
                                    <button class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body">
                                    <?php
                                    $qs = $conn->query("
                                        SELECT q.question
                                        FROM questions q
                                        JOIN exam_questions eq ON eq.question_id=q.id
                                        WHERE eq.exam_id=".$e['id']
                                    );

                                    if($qs->num_rows){
                                        echo "<ol>";
                                        while($row=$qs->fetch_assoc()){
                                            echo "<li>{$row['question']}</li>";
                                        }
                                        echo "</ol>";
                                    } else {
                                        echo "<p>No questions found.</p>";
                                    }
                                    ?>
                                </div>
                            </div>
                        </div>
                    </div>

                <?php endwhile; ?>
                </tbody>
            </table>
        </div>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
