<?php
session_start();
include "../includes/db.php";

/* ======================
   COUNTS
====================== */
$examCount = $conn->query("SELECT COUNT(*) c FROM exams")->fetch_assoc()['c'] ?? 0;
$studentCount = $conn->query("SELECT COUNT(*) c FROM students")->fetch_assoc()['c'] ?? 0;
$resultCount = $conn->query("SELECT COUNT(*) c FROM results")->fetch_assoc()['c'] ?? 0;
$questionCount = $conn->query("SELECT COUNT(*) c FROM questions")->fetch_assoc()['c'] ?? 0;

/* ======================
   CHART DATA
====================== */

// Exams per month
$examMonth = ['labels'=>[], 'data'=>[]];
$res = $conn->query("
    SELECT DATE_FORMAT(created_at,'%b') m, COUNT(*) t
    FROM exams
    GROUP BY MONTH(created_at)
");
while($r=$res->fetch_assoc()){
    $examMonth['labels'][]=$r['m'];
    $examMonth['data'][]=$r['t'];
}

// Average Marks
$avgMarks = ['labels'=>[], 'data'=>[]];
$res = $conn->query("
    SELECT e.title, ROUND(AVG(r.marks),2) avgm
    FROM results r
    JOIN exams e ON e.id=r.exam_id
    GROUP BY r.exam_id
");
$res = $conn->query("SELECT COUNT(*) AS t FROM exams");
$row = $res->fetch_assoc();

// Pass Fail
$passFail = ['Pass'=>0,'Fail'=>0];
$res=$conn->query("SELECT status,COUNT(*) t FROM results GROUP BY status");
$res = $conn->query("SELECT COUNT(*) AS t FROM results");
$row = $res->fetch_assoc();

/* ======================
   ADD EXAM
====================== */
if(isset($_POST['add_exam'])){
    $title = trim($_POST['title']);
    $duration = (int)$_POST['duration'];

    $stmt=$conn->prepare("INSERT INTO exams (title,duration_minutes) VALUES (?,?)");
    $stmt->bind_param("si",$title,$duration);
    $stmt->execute();
    header("Location: dashboard.php");
    exit;
}

/* ======================
   ADD QUESTION
====================== */
if(isset($_POST['add_question'])){
    $stmt=$conn->prepare("
        INSERT INTO questions
        (exam_id,question,opt_a,opt_b,opt_c,opt_d,correct)
        VALUES (?,?,?,?,?,?,?)
    ");
    $stmt->bind_param(
        "issssss",
        $_POST['exam_id'],
        $_POST['question'],
        $_POST['opt_a'],
        $_POST['opt_b'],
        $_POST['opt_c'],
        $_POST['opt_d'],
        $_POST['correct']
    );
    $stmt->execute();
    header("Location: dashboard.php");
    exit;
}

/* ======================
   DELETE EXAM
====================== */
if(isset($_POST['delete_exam'])){
    $id=(int)$_POST['delete_id'];
    $conn->query("DELETE FROM questions WHERE exam_id=$id");
    $conn->query("DELETE FROM exams WHERE id=$id");
    header("Location: dashboard.php");
    exit;
}

/* ======================
   DATA FETCH
====================== */
$exams = $conn->query("SELECT id,title,duration_minutes FROM exams ORDER BY id DESC LIMIT 10");
$examList = $conn->query("SELECT id,title FROM exams");
?>

<!DOCTYPE html>
<html>
<head>
<title>Admin Dashboard</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<style>
/* ONLY CHANGE: BODY STARTS AFTER SIDEBAR */
.main-content{
    margin-left:260px; /* must match sidebar width */
    padding:20px;
}
</style>
</head>

<body>

<!-- SIDEBAR -->
<?php include "../includes/sidebar.php"; ?>

<!-- BODY STARTS AFTER SIDEBAR -->
<div class="main-content">

<h4 class="mb-4">Admin Dashboard</h4>

<!-- STATS -->
<div class="row g-3">
<div class="col-md-3"><div class="card p-3 text-center">Exams<br><h3><?= $examCount ?></h3></div></div>
<div class="col-md-3"><div class="card p-3 text-center">Students<br><h3><?= $studentCount ?></h3></div></div>
<div class="col-md-3"><div class="card p-3 text-center">Results<br><h3><?= $resultCount ?></h3></div></div>
<div class="col-md-3"><div class="card p-3 text-center">Questions<br><h3><?= $questionCount ?></h3></div></div>
</div>

<!-- CHART -->
<div class="row mt-4">
<div class="col-md-8">
<div class="card p-3">
<canvas id="avgChart"></canvas>
</div>
</div>
<div class="col-md-4">
<div class="card p-3">
<canvas id="pfChart"></canvas>
</div>
</div>
</div>

<!-- ACTIONS -->
<div class="mt-4">
<button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addExam">Add Exam</button>
<button class="btn btn-secondary" data-bs-toggle="modal" data-bs-target="#addQuestion">Add Question</button>
</div>

<!-- TABLE -->
<div class="card mt-3 p-3">
<table class="table">
<thead>
<tr>
    <th>Exam</th>
    <th>Duration</th>
    <th>Action</th>
</tr>
</thead>
<tbody>
<?php while($e=$exams->fetch_assoc()): ?>
<tr>
    <td><?= htmlspecialchars($e['title']) ?></td>
    <td><?= $e['duration_minutes'] ?> min</td>
    <td>
        <div class="d-flex gap-2">
            <!-- Edit Exam Button (Opens Modal) -->
            <button class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#editExam<?= $e['id'] ?>">Edit</button>

            <!-- View Results Button (Opens Modal) -->
            <button class="btn btn-info btn-sm" data-bs-toggle="modal" data-bs-target="#viewResults<?= $e['id'] ?>">View Results</button>

            <!-- Delete Exam Button -->
            <form method="post" onsubmit="return confirm('Are you sure you want to delete this exam?')" style="display:inline">
                <input type="hidden" name="delete_id" value="<?= $e['id'] ?>">
                <button class="btn btn-danger btn-sm" name="delete_exam">Delete</button>
            </form>
        </div>

        <!-- ===================== EDIT MODAL ===================== -->
        <div class="modal fade" id="editExam<?= $e['id'] ?>" tabindex="-1">
        <div class="modal-dialog">
            <form method="post" class="modal-content">
            <div class="modal-header">
                <h5>Edit Exam</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="edit_id" value="<?= $e['id'] ?>">
                <input name="title" class="form-control mb-2" value="<?= htmlspecialchars($e['title']) ?>" placeholder="Exam Title" required>
                <input name="duration" class="form-control" type="number" value="<?= $e['duration_minutes'] ?>" placeholder="Duration (min)" required>
            </div>
            <div class="modal-footer">
                <button class="btn btn-primary" name="edit_exam">Save Changes</button>
            </div>
            </form>
        </div>
        </div>

        <!-- ===================== VIEW RESULTS MODAL ===================== -->
        <div class="modal fade" id="viewResults<?= $e['id'] ?>" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
            <div class="modal-header">
                <h5>Results for <?= htmlspecialchars($e['title']) ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <?php
                $results = $conn->query("SELECT s.name, r.marks, r.status FROM results r JOIN students s ON s.id=r.student_id WHERE r.exam_id=".$e['id']);
                if($results->num_rows > 0):
                ?>
                <table class="table table-bordered">
                    <thead><tr><th>Student</th><th>Marks</th><th>Status</th></tr></thead>
                    <tbody>
                        <?php while($r=$results->fetch_assoc()): ?>
                        <tr>
                            <td><?= htmlspecialchars($r['name']) ?></td>
                            <td><?= $r['marks'] ?></td>
                            <td><?= $r['status'] ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
                <?php else: ?>
                    <p>No results found for this exam.</p>
                <?php endif; ?>
            </div>
            </div>
        </div>
        </div>

    </td>
</tr>
<?php endwhile; ?>
</tbody>
</table>
</div>


<!-- MODALS (UNCHANGED) -->
<!-- Add Exam Modal -->
 <!-- ADD EXAM MODAL -->
<div class="modal fade" id="addExam">
    <div class="modal-dialog">
        <form method="post" class="modal-content">
            <div class="modal-header">
                <h5>Add Exam</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <label for="examTitle" class="form-label">Exam Title</label>
                <input id="examTitle" name="title" class="form-control mb-2" placeholder="Enter Exam Title" required>

                <label for="examDuration" class="form-label">Duration (Minutes)</label>
                <input id="examDuration" name="duration" class="form-control" type="number" placeholder="Duration in minutes" required>
            </div>
            <div class="modal-footer">
                <button type="submit" class="btn btn-primary" name="add_exam">Save Exam</button>
            </div>
        </form>
    </div>
</div>

<!-- Add Question Modal -->
 <!-- ADD QUESTION MODAL -->
<div class="modal fade" id="addQuestion">
    <div class="modal-dialog">
        <form method="post" class="modal-content">
            <div class="modal-header">
                <h5>Add Question</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <label for="examSelect" class="form-label">Select Exam</label>
                <select id="examSelect" name="exam_id" class="form-select mb-2" required>
                    <?php while($x=$examList->fetch_assoc()): ?>
                        <option value="<?= $x['id'] ?>"><?= $x['title'] ?></option>
                    <?php endwhile; ?>
                </select>

                <label for="questionText" class="form-label">Question</label>
                <textarea id="questionText" name="question" class="form-control mb-2" placeholder="Enter Question" required></textarea>

                <label class="form-label">Options</label>
                <input name="opt_a" class="form-control mb-2" placeholder="Option A" required>
                <input name="opt_b" class="form-control mb-2" placeholder="Option B" required>
                <input name="opt_c" class="form-control mb-2" placeholder="Option C" required>
                <input name="opt_d" class="form-control mb-2" placeholder="Option D" required>

                <label for="correctOption" class="form-label">Correct Option</label>
                <select id="correctOption" name="correct" class="form-select" required>
                    <option value="A">A</option>
                    <option value="B">B</option>
                    <option value="C">C</option>
                    <option value="D">D</option>
                </select>
            </div>
            <div class="modal-footer">
                <button type="submit" class="btn btn-success" name="add_question">Add Question</button>
            </div>
        </form>
    </div>
</div>


<script>
new Chart(document.getElementById('avgChart'),{
type:'bar',
data:{labels:<?= json_encode($avgMarks['labels']) ?>,
datasets:[{label:'Average Marks',data:<?= json_encode($avgMarks['data']) ?>}]}
});

new Chart(document.getElementById('pfChart'),{
type:'doughnut',
data:{labels:['Pass','Fail'],datasets:[{data:<?= json_encode(array_values($passFail)) ?>}]}
});
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
