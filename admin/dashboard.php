<?php
session_start();
include "../includes/db.php";

// COUNTS
$examCount = $conn->query("SELECT COUNT(*) c FROM exams")->fetch_assoc()['c'];
$studentCount = $conn->query("SELECT COUNT(*) c FROM students")->fetch_assoc()['c'];
$resultCount = $conn->query("SELECT COUNT(*) c FROM results")->fetch_assoc()['c'];
?>
<!DOCTYPE html>
<html>
<head>
    <title>Admin Dashboard</title>

    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Custom CSS -->
    <link rel="stylesheet" href="../assets/css/dashboard.css">

    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>

<?php include "../includes/sidebar.php"; ?>

<div class="main-content">

    <!-- TOP HEADER -->
    <div class="topbar">
        <h4>Good Morning, Admin 👋</h4>
    </div>

    <!-- STAT CARDS -->
    <div class="row g-3 mt-3">
        <div class="col-md-3">
            <div class="stat-card purple">
                <h6>Total Exams</h6>
                <h2><?= $examCount ?></h2>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card green">
                <h6>Students</h6>
                <h2><?= $studentCount ?></h2>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card orange">
                <h6>Results</h6>
                <h2><?= $resultCount ?></h2>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card blue">
                <h6>Questions</h6>
                <h2>64</h2>
            </div>
        </div>
    </div>

    <!-- CHART -->
    <div class="row mt-4">
        <div class="col-md-8">
            <div class="card p-3">
                <h6>Exam Performance</h6>
                <canvas id="examChart"></canvas>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card p-3">
                <h6>Pass Percentage</h6>
                <h1 class="text-center mt-4">87%</h1>
            </div>
        </div>
    </div>

</div>

<script>
new Chart(document.getElementById('examChart'), {
    type: 'line',
    data: {
        labels: ['Jan','Feb','Mar','Apr','May','Jun'],
        datasets: [{
            label: 'Exams Taken',
            data: [10,20,15,30,25,40],
            borderWidth: 2
        }]
    }
});
</script>

</body>
</html>
