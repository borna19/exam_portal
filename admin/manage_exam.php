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

$currentPage = 'manage_exams';

/* =====================
   DELETE EXAM
===================== */
$delete_msg = "";
if (isset($_POST['delete_exam'])) {
    $id = $_POST['delete_id'];
    $conn->query("DELETE FROM exams WHERE id='$id'");
    $delete_msg = "Exam deleted successfully!";
}

/* =====================
   ADD EXAM
===================== */
if (isset($_POST['add_exam'])) {
    $exam_name = $_POST['exam_name'];
    $duration  = $_POST['duration'];

    $conn->query("INSERT INTO exams (exam_name, duration)
                  VALUES ('$exam_name','$duration')");
    header("Location: manage_exams.php");
    exit;
}

/* =====================
   UPDATE EXAM
===================== */
if (isset($_POST['update_exam'])) {
    $id        = $_POST['exam_id'];
    $exam_name = $_POST['exam_name'];
    $duration  = $_POST['duration'];

    $conn->query("UPDATE exams
                  SET exam_name='$exam_name', duration='$duration'
                  WHERE id='$id'");
}

/* =====================
   FETCH EXAMS
===================== */
$examData = [];
$result = $conn->query("SELECT * FROM exams ORDER BY id DESC");
while ($row = $result->fetch_assoc()) {
    $examData[] = $row;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Manage Exams</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body{margin:0;font-family:Arial;}
        .main{margin-left:220px;padding:20px;}
    </style>
</head>
<body>

<!-- COMMON SIDEBAR -->
<?php include __DIR__ . "/../includes/sidebar.php"; ?>

<!-- MAIN CONTENT -->
<div class="main">
    <h3>Manage Exams</h3>

    <?php if($delete_msg){ ?>
        <div class="alert alert-success"><?= $delete_msg ?></div>
    <?php } ?>

    <!-- ADD EXAM -->
    <div class="card p-3 mb-3">
        <form method="POST">
            <input type="text" name="exam_name" class="form-control mb-2"
                   placeholder="Exam Name" required>
            <input type="number" name="duration" class="form-control mb-2"
                   placeholder="Duration (minutes)" required>
            <button type="submit" name="add_exam" class="btn btn-success">
                Add Exam
            </button>
        </form>
    </div>

    <!-- EXAM TABLE -->
    <table class="table table-bordered">
        <tr>
            <th>ID</th>
            <th>Exam Name</th>
            <th>Duration</th>
            <th>Action</th>
        </tr>

        <?php foreach ($examData as $row) { ?>
        <tr>
            <td><?= $row['id'] ?></td>
            <td><?= $row['exam_name'] ?></td>
            <td><?= $row['duration'] ?> min</td>
            <td>
                <button class="btn btn-primary btn-sm"
                        data-bs-toggle="modal"
                        data-bs-target="#edit<?= $row['id'] ?>">
                    Edit
                </button>

                <form method="POST" style="display:inline;">
                    <input type="hidden" name="delete_id"
                           value="<?= $row['id'] ?>">
                    <button type="submit" name="delete_exam"
                            onclick="return confirm('Delete this exam?')"
                            class="btn btn-danger btn-sm">
                        Delete
                    </button>
                </form>
            </td>
        </tr>
        <?php } ?>
    </table>
</div>

<!-- EDIT MODALS -->
<?php foreach ($examData as $row) { ?>
<div class="modal fade" id="edit<?= $row['id'] ?>">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="POST">
        <div class="modal-header">
          <h5>Edit Exam</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>

        <div class="modal-body">
          <input type="hidden" name="exam_id" value="<?= $row['id'] ?>">
          <input type="text" name="exam_name" class="form-control mb-2"
                 value="<?= $row['exam_name'] ?>" required>
          <input type="number" name="duration" class="form-control"
                 value="<?= $row['duration'] ?>" required>
        </div>

        <div class="modal-footer">
          <button type="submit" name="update_exam" class="btn btn-success">
            Update
          </button>
        </div>
      </form>
    </div>
  </div>
</div>
<?php } ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
