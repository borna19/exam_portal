<?php

$currentPage = $currentPage ?? '';
$isAdmin = isset($_SESSION['admin_id']);

$logoutLink = ($currentPage === 'dashboard')
    ? 'logout_to_index.php'
    : 'logout_to_dashboard.php';
?>

<style>
    .sidebar {
        position: fixed;
        left: 0;
        top: 0;
        width: 220px;
        height: 100%;
        background: #2e7d32;
        color: #fff;
        padding-top: 20px;
    }

    .sidebar h2 {
        text-align: center;
        margin-bottom: 30px;
    }

    .sidebar a {
    display: block;
    padding: 10px 20px;
    color: #fff;
    text-decoration: none;
}
    .sidebar a:hover {
        background: #1b5e20;
    }
</style>

<div class="sidebar">
    <h2>Exam Portal</h2>

    <a href="dashboard.php">Dashboard</a>

    <?php if ($isAdmin) { ?>
        <a href="manage_exam.php">Manage Exams</a>
        <a href="/exam_portal/admin/manage_questions.php"
            class="<?= ($currentPage == 'questions') ? 'active' : '' ?>">
            Questions
        </a>
        <a href="/exam_portal/admin/students.php"
            class="<?= ($currentPage == 'students') ? 'active' : '' ?>">
            Students
        </a>

        <a href="/exam_portal/admin/results.php"
            class="<?= ($currentPage == 'results') ? 'active' : '' ?>">
            Results
        </a>


        <a href="/exam_portal/admin/logout.php"
            onclick="return confirm('Are you sure you want to logout?')">
            Logout
        </a>
    <?php } else { ?>
        <a href="../index.php">Login</a>
    <?php } ?>
</div>