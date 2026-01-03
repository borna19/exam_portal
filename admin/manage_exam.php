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
   DELETE EXAM
===================== */
$msg = "";
if (isset($_POST['delete_exam'])) {
    $id = (int)$_POST['delete_id'];
    $conn->query("DELETE FROM exams WHERE id=$id");
    $msg = "Exam deleted successfully!";
}

/* =====================
   ADD EXAM
===================== */
if (isset($_POST['add_exam'])) {
    $exam_name = trim($_POST['exam_name']);
    $duration  = (int)$_POST['duration'];

    if ($exam_name && $duration) {
        $conn->query("INSERT INTO exams (exam_name, duration)
                      VALUES ('$exam_name','$duration')");
        $msg = "Exam added successfully!";
    }
}

/* =====================
   UPDATE EXAM
===================== */
if (isset($_POST['update_exam'])) {
    $id = (int)$_POST['exam_id'];
    $exam_name = trim($_POST['exam_name']);
    $duration  = (int)$_POST['duration'];

    if ($id && $exam_name && $duration) {
        $stmt = $conn->prepare("UPDATE exams SET exam_name=?, duration=? WHERE id=?");
        $stmt->bind_param("sii", $exam_name, $duration, $id);
        $stmt->execute();
        $stmt->close();
        $msg = "Exam updated successfully!";
    }
}

/* =====================
   FETCH EXAMS
===================== */
$exams = [];
$result = $conn->query("SELECT * FROM exams ORDER BY id DESC");
while ($row = $result->fetch_assoc()) {
    $exams[] = $row;
}
$totalExams = count($exams);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Manage Exams</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        body {
            background:#f4f6f9;
        }
        .main {
            margin-left:220px;
            padding:25px;
        }
        .card {
            border-radius:14px;
        }
        .table th {
            background:#f8f9fa;
        }

        /* 🔥 Row Highlight */
        .table tbody tr {
            transition: all .25s ease;
        }
        .table tbody tr:hover {
            background:#f1f5ff;
            transform: scale(1.01);
        }

        /* 🔥 Button Glow */
        .btn-outline-primary:hover {
            box-shadow:0 0 8px rgba(13,110,253,.4);
        }
        .btn-outline-danger:hover {
            box-shadow:0 0 8px rgba(220,53,69,.4);
        }

        /* 🔥 Search Focus */
        #searchExam:focus {
            border-color:#0d6efd;
            box-shadow:0 0 6px rgba(13,110,253,.25);
        }

        /* 🔥 Gradient Card */
        .bg-gradient {
            background:linear-gradient(135deg,#e3f2fd,#ffffff);
        }
    </style>
</head>
<body>

<?php include "../includes/sidebar.php"; ?>

<div class="main">

    <!-- PAGE HEADER -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-bold">📘 Manage Exams</h4>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addExam">
            + Add Exam
        </button>
    </div>

    <?php if($msg): ?>
        <div class="alert alert-success"><?= $msg ?></div>
    <?php endif; ?>

    <!-- SUMMARY CARD -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card p-3 text-center shadow-sm bg-gradient">
                <h6 class="text-muted">Total Exams</h6>
                <h2 class="fw-bold text-primary"><?= $totalExams ?></h2>
            </div>
        </div>
    </div>

    <!-- SEARCH -->
    <input type="text" id="searchExam" class="form-control mb-3"
           placeholder="🔍 Search exam...">

    <!-- TABLE -->
    <div class="card shadow-sm">
        <div class="card-body">
            <table class="table table-bordered align-middle">
                <thead>
                    <tr>
                        <th width="60">ID</th>
                        <th>Exam Name</th>
                        <th width="150">Duration</th>
                        <th width="160">Action</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($exams as $row): ?>
                    <tr>
                        <td><?= $row['id'] ?></td>

                        <!-- 🔥 Exam Name Highlight -->
                        <td class="fw-semibold text-primary">
                            <?= htmlspecialchars($row['exam_name']) ?>
                        </td>

                        <!-- 🔥 Duration Badge -->
                        <td>
                            <span class="badge bg-info text-dark px-3 py-2">
                                <?= $row['duration'] ?> min
                            </span>
                        </td>

                        <td>
                            <button class="btn btn-sm btn-outline-primary"
                                    data-bs-toggle="modal"
                                    data-bs-target="#edit<?= $row['id'] ?>">
                                ✏ Edit
                            </button>

                            <form method="POST" class="d-inline">
                                <input type="hidden" name="delete_id" value="<?= $row['id'] ?>">
                                <button type="submit" name="delete_exam"
                                        onclick="return confirm('Delete this exam?')"
                                        class="btn btn-sm btn-outline-danger">
                                    🗑
                                </button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ADD MODAL -->
<div class="modal fade" id="addExam">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="POST">
        <div class="modal-header">
          <h5>Add New Exam</h5>
          <button class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <input type="text" name="exam_name" class="form-control mb-2"
                 placeholder="Exam Name" required>
          <input type="number" name="duration" class="form-control"
                 placeholder="Duration (minutes)" required>
        </div>
        <div class="modal-footer">
          <button type="submit" name="add_exam" class="btn btn-success">
            Save
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- EDIT MODALS -->
<?php foreach ($exams as $row): ?>
<div class="modal fade" id="edit<?= $row['id'] ?>">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="POST">
        <div class="modal-header">
          <h5>Edit Exam</h5>
          <button class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="exam_id" value="<?= $row['id'] ?>">
          <input type="text" name="exam_name" class="form-control mb-2"
                 value="<?= htmlspecialchars($row['exam_name']) ?>" required>
          <input type="number" name="duration" class="form-control"
                 value="<?= $row['duration'] ?>" required>
        </div>
        <div class="modal-footer">
          <button type="submit" name="update_exam" class="btn btn-primary">
            Update
          </button>
        </div>
      </form>
    </div>
  </div>
</div>
<?php endforeach; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
document.getElementById('searchExam').addEventListener('keyup', function(){
    let value = this.value.toLowerCase();
    document.querySelectorAll('tbody tr').forEach(row=>{
        row.style.display = row.innerText.toLowerCase().includes(value) ? '' : 'none';
    });
});
</script>

</body>
</html>
