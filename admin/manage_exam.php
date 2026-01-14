<?php
session_start();
include '../includes/db.php';
require_once '../includes/send_exam_mail.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: ../index.php");
    exit;
}

/* ================= CREATE EXAM ================= */
$successMsg = "";
$errorMsg   = "";
$examLink   = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_exam'])) {

    $title     = $_POST['title'];
    $desc      = $_POST['description'];
    $duration  = (int)$_POST['duration'];
    $questions = $_POST['questions'] ?? [];

    if (empty($questions)) {
        $errorMsg = "Please select at least one question.";
    } else {

        $token = bin2hex(random_bytes(16));

        $stmt = $conn->prepare(
            "INSERT INTO exams (title, description, duration_minutes, token)
             VALUES (?, ?, ?, ?)"
        );
        $stmt->bind_param("ssis", $title, $desc, $duration, $token);

        if ($stmt->execute()) {

            $exam_id = $stmt->insert_id;

            $link = $conn->prepare(
                "INSERT INTO exam_questions (exam_id, question_id) VALUES (?, ?)"
            );

            foreach ($questions as $qid) {
                $link->bind_param("ii", $exam_id, $qid);
                $link->execute();
            }

            $examLink = "http://localhost/exam_portal/pages/take_exam.php?token=" . $token;


            /* ================= SEND MAIL TO ALL STUDENTS ================= */

            $students = $conn->query("SELECT name, email FROM students");

            while ($s = $students->fetch_assoc()) {
                sendExamMail(
                    $s['email'],
                    $s['name'],
                    $title,
                    $examLink
                );
                sleep(1); // prevent gmail blocking
            }

            $successMsg = "Exam created & mail sent to all students!";
        } else {
            $errorMsg = "Something went wrong!";
        }
    }
}

/* ================= DELETE EXAM ================= */
if (isset($_POST['delete_exam'])) {
    $id = (int)$_POST['delete_id'];
    $conn->query("DELETE FROM exam_questions WHERE exam_id=$id");
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
        body {
            margin: 0;
            background: #f4f6f9;
            font-family: 'Segoe UI', sans-serif;
        }

        .wrapper {
            display: flex;
            min-height: 100vh;
        }

        .main-content {
            margin-left: 220px;
            padding: 30px;
            width: calc(100% - 220px);
        }

        .card {
            border-radius: 14px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.08);
            padding: 25px;
            margin-bottom: 25px;
        }

        .question-box {
            max-height: 260px;
            overflow-y: auto;
            border: 1px solid #ddd;
            padding: 12px;
            border-radius: 10px;
            background: #fafafa;
        }

        .table thead {
            background: #2e7d32;
            color: #fff;
        }

        @media(max-width:768px) {
            .main-content {
                margin-left: 0;
                width: 100%;
            }
        }
    </style>
</head>

<body>
    <div class="wrapper">

        <?php include '../includes/sidebar.php'; ?>

        <div class="main-content">

            <!-- CREATE EXAM -->
            <div class="card">
                <h4>Create New Exam</h4>

                <?php if ($successMsg): ?>
                    <div class="alert alert-success"><?= $successMsg ?></div>
                <?php endif; ?>

                <?php if ($errorMsg): ?>
                    <div class="alert alert-danger"><?= $errorMsg ?></div>
                <?php endif; ?>

                <?php if ($examLink): ?>
                    <div class="alert alert-info">
                        <strong>Exam Link:</strong><br>
                        <a href="<?= $examLink ?>" target="_blank"><?= $examLink ?></a>
                    </div>
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
                                <?php while ($q = $allQuestions->fetch_assoc()): ?>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="questions[]" value="<?= $q['id'] ?>">
                                        <label class="form-check-label"><?= htmlspecialchars($q['question']) ?></label>
                                    </div>
                                <?php endwhile; ?>
                            </div>
                        </div>

                    </div>
                    <button class="btn btn-success mt-3" name="create_exam">Create Exam</button>
                </form>
            </div>

            <!-- EXAM LIST -->
            <div class="card">
                <h4>Existing Exams</h4>

                <table class="table table-bordered align-middle">
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Duration</th>
                            <th>Exam Link</th>
                            <th width="160">Action</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php while ($e = $exams->fetch_assoc()): ?>
                            <tr>
                                <td><?= htmlspecialchars($e['title']) ?></td>
                                <td><?= $e['duration_minutes'] ?> min</td>
                                <td>
                                    <a href="/exam_portal/pages/take_exam.php?token=<?= $e['token'] ?>" target="_blank">
                                        Open Exam
                                    </a>

                                </td>
                                <td>
                                    <form method="post" onsubmit="return confirm('Delete exam?')">
                                        <input type="hidden" name="delete_id" value="<?= $e['id'] ?>">
                                        <button class="btn btn-danger btn-sm" name="delete_exam">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>

        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>