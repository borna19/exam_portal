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

$currentPage = 'students';

/* =====================
   DELETE STUDENT
===================== */
$msg = "";
if (isset($_POST['delete_student'])) {
    $id = $_POST['student_id'];
    $conn->query("DELETE FROM students WHERE id='$id'");
    $msg = "Student deleted successfully!";
}

/* =====================
   ADD STUDENT
===================== */
if (isset($_POST['add_student'])) {
    $name  = $_POST['name'];
    $email = $_POST['email'];

    $conn->query("INSERT INTO students (name, email) VALUES ('$name','$email')");
    header("Location: students.php");
    exit;
}

/* =====================
   FETCH STUDENTS
===================== */
$students = [];
$result = $conn->query("SELECT * FROM students ORDER BY id DESC");
while ($row = $result->fetch_assoc()) {
    $students[] = $row;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Students</title>
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
    <h3>Students</h3>

    <?php if($msg){ ?>
        <div class="alert alert-success"><?= $msg ?></div>
    <?php } ?>

    <!-- ADD STUDENT -->
    <div class="card p-3 mb-3">
        <form method="POST">
            <input type="text" name="name" class="form-control mb-2"
                   placeholder="Student Name" required>

            <input type="email" name="email" class="form-control mb-2"
                   placeholder="Student Email" required>

            <button type="submit" name="add_student" class="btn btn-success">
                Add Student
            </button>
        </form>
    </div>

    <!-- STUDENT TABLE -->
    <table class="table table-bordered">
        <tr>
            <th>ID</th>
            <th>Name</th>
            <th>Email</th>
            <th>Joined</th>
            <th>Action</th>
        </tr>

        <?php foreach ($students as $s) { ?>
        <tr>
            <td><?= $s['id'] ?></td>
            <td><?= htmlspecialchars($s['name']) ?></td>
            <td><?= htmlspecialchars($s['email']) ?></td>
            <td><?= date("d M Y", strtotime($s['created_at'])) ?></td>
            <td>
                <form method="POST" style="display:inline;">
                    <input type="hidden" name="student_id" value="<?= $s['id'] ?>">
                    <button type="submit"
                            name="delete_student"
                            onclick="return confirm('Delete this student?')"
                            class="btn btn-danger btn-sm">
                        Delete
                    </button>
                </form>
            </td>
        </tr>
        <?php } ?>
    </table>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
