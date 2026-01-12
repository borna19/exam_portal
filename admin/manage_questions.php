<?php
session_start();
include '../includes/db.php';

$currentPage = 'questions';
$isAdmin = isset($_SESSION['admin_id']);

$exams = $conn->query("SELECT id, title FROM exams");

$message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_question'])) {
    $exam_id = (int)$_POST['exam_id'];
    $question = $_POST['question'];
    $opt_a = $_POST['opt_a'];
    $opt_b = $_POST['opt_b'];
    $opt_c = $_POST['opt_c'];
    $opt_d = $_POST['opt_d'];
    $correct = $_POST['correct'];

    $stmt = $conn->prepare(
        "INSERT INTO questions 
        (exam_id, question, opt_a, opt_b, opt_c, opt_d, correct)
        VALUES (?, ?, ?, ?, ?, ?, ?)"
    );
    $stmt->bind_param(
        "issssss",
        $exam_id,
        $question,
        $opt_a,
        $opt_b,
        $opt_c,
        $opt_d,
        $correct
    );
    if ($stmt->execute()) $message = "Question added successfully!";
}

$questions = $conn->query("
    SELECT q.id, q.question, q.opt_a, q.opt_b, q.opt_c, q.opt_d, q.correct, e.title AS exam_title
    FROM questions q
    JOIN exams e ON e.id = q.exam_id
    ORDER BY q.id DESC
");

/* DELETE QUESTION (SAME PAGE) */
if (isset($_POST['delete_question'])) {
    $id = (int)$_POST['question_id'];
    $conn->query("DELETE FROM questions WHERE id = $id");
    header("Location: manage_questions.php");
    exit;
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Manage Questions</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        body {
            background: #f0f2f5;
            font-family: 'Segoe UI', sans-serif;
            margin: 0;
        }

        /* Wrapper */
        .wrapper {
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar */
        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            width: 220px;
            height: 100%;
            background: #2e7d32;
            color: #fff;
            padding-top: 25px;
        }

        .sidebar h2 {
            text-align: center;
            margin-bottom: 30px;
            font-size: 22px;
            font-weight: 600;
        }

        .sidebar a {
            display: block;
            padding: 12px 20px;
            color: #fff;
            text-decoration: none;
            border-radius: 6px;
            margin-bottom: 6px;
            transition: 0.2s;
        }

        .sidebar a:hover,
        .sidebar a.active {
            background: #1b5e20;
        }

        /* Main Content */
        .main {
            flex: 1;
            padding: 30px;
            margin-left: 220px;
        }

        /* Page Title */
        .page-title {
            font-size: 28px;
            font-weight: 600;
            margin-bottom: 25px;
        }

        /* Card */
        .card {
            background: #fff;
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 25px;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.05);
            transition: 0.3s;
        }

        .card:hover {
            box-shadow: 0 12px 30px rgba(0, 0, 0, 0.08);
        }

        /* Form */
        .form-group {
            margin-bottom: 20px;
        }

        label {
            font-weight: 600;
            margin-bottom: 8px;
            display: block;
        }

        input,
        select,
        textarea {
            width: 100%;
            padding: 12px;
            border-radius: 8px;
            border: 1px solid #ddd;
            font-size: 14px;
            transition: 0.2s;
        }

        input:focus,
        select:focus,
        textarea:focus {
            border-color: #10b981;
            outline: none;
        }

        textarea {
            height: 100px;
            resize: none;
        }

        .options {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
        }

        .btn {
            background: #10b981;
            color: #fff;
            border: none;
            padding: 12px 25px;
            border-radius: 8px;
            font-size: 15px;
            cursor: pointer;
            transition: 0.2s;
        }

        .btn:hover {
            background: #059669;
        }

        /* Success Message */
        .success {
            background: #d1fae5;
            color: #065f46;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-weight: 500;
        }

        /* Table */
        .table th,
        .table td {
            vertical-align: middle;
        }

        .table thead {
            background: #10b981;
            color: #fff;
        }

        .table tbody tr:hover {
            background: #f3fdf7;
        }

        .action-btn {
            margin-right: 5px;
            border-radius: 6px;
        }

        /* Responsive */
        @media(max-width:768px) {
            .wrapper {
                flex-direction: column;
            }

            .main {
                margin-left: 0;
                padding: 20px;
            }
        }
    </style>
</head>

<body>
    <div class="wrapper">

        <!-- Sidebar -->
        <div class="sidebar">
            <h2>Exam Portal</h2>
            <a href="dashboard.php">Dashboard</a>
            <?php if ($isAdmin) { ?>
                <a href="manage_exam.php">Manage Exams</a>
                <a href="manage_questions.php" class="active">Questions</a>
                <a href="students.php">Students</a>
                <a href="results.php">Results</a>
                <a href="logout.php" onclick="return confirm('Are you sure you want to logout?')">Logout</a>
            <?php } else { ?>
                <a href="../index.php">Login</a>
            <?php } ?>
        </div>

        <!-- Main Content -->
        <div class="main">
            <div class="page-title">Manage Questions</div>

            <!-- Add Question Form -->
            <div class="card">
                <?php if ($message): ?>
                    <div class="success"><?= $message ?></div>
                <?php endif; ?>

                <form method="post">
                    <div class="form-group">
                        <label>Select Exam</label>
                        <select name="exam_id" required>
                            <?php while ($row = $exams->fetch_assoc()): ?>
                                <option value="<?= $row['id'] ?>"><?= htmlspecialchars($row['title']) ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Question</label>
                        <textarea name="question" required></textarea>
                    </div>

                    <div class="form-group">
                        <label>Options</label>
                        <div class="options">
                            <input type="text" name="opt_a" placeholder="Option A" required>
                            <input type="text" name="opt_b" placeholder="Option B" required>
                            <input type="text" name="opt_c" placeholder="Option C" required>
                            <input type="text" name="opt_d" placeholder="Option D" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Correct Answer</label>
                        <select name="correct">
                            <option value="A">A</option>
                            <option value="B">B</option>
                            <option value="C">C</option>
                            <option value="D">D</option>
                        </select>
                    </div>

                    <button type="submit" name="add_question" class="btn">Add Question</button>
                </form>
            </div>

            <!-- Questions Table -->
            <div class="card">
                <h4>All Questions</h4>
                <div class="table-responsive mt-3">
                    <table class="table table-bordered align-middle">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Exam</th>
                                <th>Question</th>
                                <th>Options</th>
                                <th>Correct</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($questions->num_rows > 0): $i = 1;
                                while ($q = $questions->fetch_assoc()): ?>
                                    <tr>
                                        <td><?= $i++ ?></td>
                                        <td><?= htmlspecialchars($q['exam_title']) ?></td>
                                        <td><?= htmlspecialchars($q['question']) ?></td>
                                        <td>
                                            A: <?= htmlspecialchars($q['opt_a']) ?><br>
                                            B: <?= htmlspecialchars($q['opt_b']) ?><br>
                                            C: <?= htmlspecialchars($q['opt_c']) ?><br>
                                            D: <?= htmlspecialchars($q['opt_d']) ?>
                                        </td>
                                        <td><?= $q['correct'] ?></td>
                                        <td>
                                            <!-- Edit Button triggers modal -->
                                            <button class="btn btn-sm btn-primary action-btn" data-bs-toggle="modal" data-bs-target="#editModal<?= $q['id'] ?>">Edit</button>

                                            <!-- Delete Button (direct) -->
                                            <form method="post" style="display:inline;">
                                                <input type="hidden" name="question_id" value="<?= $q['id'] ?>">
                                                <button type="submit"
                                                    name="delete_question"
                                                    class="btn btn-sm btn-danger action-btn"
                                                    onclick="return confirm('Delete this question?')">
                                                    Delete
                                                </button>
                                            </form>

                                        </td>
                                    </tr>

                                    <!-- Edit Modal -->
                                    <div class="modal fade" id="editModal<?= $q['id'] ?>" tabindex="-1" aria-labelledby="editModalLabel<?= $q['id'] ?>" aria-hidden="true">
                                        <div class="modal-dialog modal-lg">
                                            <div class="modal-content">
                                                <form method="post">
                                                    <input type="hidden" name="question_id" value="<?= $q['id'] ?>">

                                                    <div class="modal-header">
                                                        <h5 class="modal-title" id="editModalLabel<?= $q['id'] ?>">Edit Question</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <div class="form-group mb-3">
                                                            <label>Question</label>
                                                            <textarea name="question" class="form-control" required><?= htmlspecialchars($q['question']) ?></textarea>
                                                        </div>
                                                        <div class="form-group mb-3">
                                                            <label>Options</label>
                                                            <div class="row g-2">
                                                                <div class="col"><input type="text" name="opt_a" class="form-control" value="<?= htmlspecialchars($q['opt_a']) ?>" required></div>
                                                                <div class="col"><input type="text" name="opt_b" class="form-control" value="<?= htmlspecialchars($q['opt_b']) ?>" required></div>
                                                                <div class="col"><input type="text" name="opt_c" class="form-control" value="<?= htmlspecialchars($q['opt_c']) ?>" required></div>
                                                                <div class="col"><input type="text" name="opt_d" class="form-control" value="<?= htmlspecialchars($q['opt_d']) ?>" required></div>
                                                            </div>
                                                        </div>
                                                        <div class="form-group mb-3">
                                                            <label>Correct Answer</label>
                                                            <select name="correct" class="form-control">
                                                                <option value="A" <?= $q['correct'] == 'A' ? 'selected' : '' ?>>A</option>
                                                                <option value="B" <?= $q['correct'] == 'B' ? 'selected' : '' ?>>B</option>
                                                                <option value="C" <?= $q['correct'] == 'C' ? 'selected' : '' ?>>C</option>
                                                                <option value="D" <?= $q['correct'] == 'D' ? 'selected' : '' ?>>D</option>
                                                            </select>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                        <button type="submit" name="update_question" class="btn btn-success">
                                                            Save Changes
                                                        </button>

                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>

                                <?php endwhile;
                            else: ?>
                                <tr>
                                    <td colspan="6" class="text-center">No questions found.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>


            <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>