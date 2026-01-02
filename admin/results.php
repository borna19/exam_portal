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

$currentPage = 'results';

/* =====================
   FETCH RESULTS
===================== */
$sql = "SELECT * FROM results ORDER BY created_at DESC";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Results</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        body{margin:0;font-family:Arial;}
        .main{margin-left:220px;padding:20px;}
    </style>
</head>
<body>

<!-- SIDEBAR -->
<?php include __DIR__ . "/../includes/sidebar.php"; ?>

<!-- MAIN CONTENT -->
<div class="main">
    <h3>Exam Results</h3>

    <table class="table table-bordered table-striped">
        <thead class="table-dark">
        <tr>
            <th>ID</th>
            <th>Student Name</th>
            <th>Exam Name</th>
            <th>Score</th>
            <th>Total Marks</th>
            <th>Status</th>
            <th>Date</th>
        </tr>
        </thead>

        <tbody>
        <?php
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
        ?>
            <tr>
                <td><?= $row['id']; ?></td>
                <td><?= $row['student_name']; ?></td>
                <td><?= $row['exam_name']; ?></td>
                <td><?= $row['score']; ?></td>
                <td><?= $row['total_marks']; ?></td>
                <td>
                    <span class="badge <?= ($row['result_status']=='Pass')?'bg-success':'bg-danger'; ?>">
                        <?= $row['result_status']; ?>
                    </span>
                </td>
                <td><?= date("d M Y", strtotime($row['created_at'])); ?></td>
            </tr>
        <?php
            }
        } else {
        ?>
            <tr>
                <td colspan="7" class="text-center text-danger">
                    No results found
                </td>
            </tr>
        <?php } ?>
        </tbody>
    </table>

</div>

</body>
</html>
