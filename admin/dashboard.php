<?php
session_start();
include "../includes/db.php";

/* ======================
   CHART DATA QUERIES
====================== */

// Exams per Month
$examMonthData = ['labels'=>[], 'data'=>[]];
$res = $conn->query("
    SELECT DATE_FORMAT(created_at,'%b') AS month, COUNT(*) total
    FROM exams
    GROUP BY MONTH(created_at)
");
if ($res === false) {
    error_log('DB error fetching exams per month: ' . $conn->error);
} else {
    while($row = $res->fetch_assoc()){
        $examMonthData['labels'][] = $row['month'];
        $examMonthData['data'][]   = $row['total'];
    }
}

// Pass vs Fail
$passFail = ['Pass'=>0,'Fail'=>0];
$res = $conn->query("SELECT status, COUNT(*) total FROM results GROUP BY status");
if ($res === false) {
    error_log('DB error fetching pass/fail stats: ' . $conn->error);
} else {
    while($row = $res->fetch_assoc()){
        $passFail[$row['status']] = $row['total'];
    }
}

// Average Marks
$avgMarks = ['labels'=>[], 'data'=>[]];
$res = $conn->query("
    SELECT e.exam_name, ROUND(AVG(r.marks),2) avg_marks
    FROM results r
    JOIN exams e ON e.id = r.exam_id
    GROUP BY r.exam_id
");
if ($res === false) {
    error_log('DB error fetching avg marks: ' . $conn->error);
} else {
    while($row = $res->fetch_assoc()){
        $avgMarks['labels'][] = $row['exam_name'];
        $avgMarks['data'][]   = $row['avg_marks'];
    }
}

// Most Attempted Exams
$attempted = ['labels'=>[], 'data'=>[]];
$res = $conn->query("
    SELECT e.exam_name, COUNT(*) total
    FROM results r
    JOIN exams e ON e.id = r.exam_id
    GROUP BY r.exam_id
    ORDER BY total DESC
    LIMIT 5
");
if ($res === false) {
    error_log('DB error fetching most attempted exams: ' . $conn->error);
} else {
    while($row = $res->fetch_assoc()){
        $attempted['labels'][] = $row['exam_name'];
        $attempted['data'][]   = $row['total'];
    }
}


// Handle Add Exam form submission (from modal)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_exam'])) {
    $exam_name = trim($_POST['exam_name'] ?? '');
    $duration = (int)($_POST['duration'] ?? 0);

    if ($exam_name === '' || $duration <= 0) {
        $formError = 'Please provide a valid exam name and duration.';
    } else {
        $stmt = $conn->prepare("INSERT INTO exams (exam_name, duration) VALUES (?, ?)");
        if ($stmt) {
            $stmt->bind_param("si", $exam_name, $duration);
            if ($stmt->execute()) {
                $stmt->close();
                // Redirect to avoid form resubmission and show success message
                header('Location: dashboard.php?added=1');
                exit;
            } else {
                $formError = 'DB error inserting exam: ' . $stmt->error;
                error_log('DB insert exam error: ' . $stmt->error);
                $stmt->close();
            }
        } else {
            $formError = 'DB error preparing statement: ' . $conn->error;
            error_log('DB prepare error: ' . $conn->error);
        }
    }
}

$success_msg = '';
if (isset($_GET['added'])) {
    $success_msg = 'Exam added successfully.';
}
if (isset($_GET['updated'])) {
    $success_msg = 'Exam updated successfully.';
}

// Handle Add Question from dashboard modal
$questionSuccess = '';
$questionError = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_question_from_dashboard'])) {
    $exam_id = (int)($_POST['exam_id'] ?? 0);
    $question = trim($_POST['question'] ?? '');
    $opt_a = trim($_POST['opt_a'] ?? '');
    $opt_b = trim($_POST['opt_b'] ?? '');
    $opt_c = trim($_POST['opt_c'] ?? '');
    $opt_d = trim($_POST['opt_d'] ?? '');
    $correct = trim($_POST['correct_option'] ?? '');

    if ($exam_id <= 0 || $question === '' || $opt_a === '' || $opt_b === '' || $opt_c === '' || $opt_d === '' || !in_array($correct, ['A','B','C','D'])) {
        $questionError = 'Please fill all fields correctly for the question.';
    } else {
        $stmtQ = $conn->prepare("INSERT INTO questions (exam_id, question, option_a, option_b, option_c, option_d, correct_option) VALUES (?, ?, ?, ?, ?, ?, ?)");
        if ($stmtQ) {
            $stmtQ->bind_param('isssssi', $exam_id, $question, $opt_a, $opt_b, $opt_c, $opt_d, $correct);
            // Note: correct is stored as string in DB, ensure column type; if it's varchar, change bind_param accordingly
            // But to be safe, bind as string: change to 'issssss' and bind correct as string
            $stmtQ->close();
        }
    }
}

// NOTE: The above prepared statement initial attempt was incorrect for types; using corrected implementation below
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_question_from_dashboard'])) {
    // Re-validate and insert properly
    $exam_id = (int)($_POST['exam_id'] ?? 0);
    $question = trim($_POST['question'] ?? '');
    $opt_a = trim($_POST['opt_a'] ?? '');
    $opt_b = trim($_POST['opt_b'] ?? '');
    $opt_c = trim($_POST['opt_c'] ?? '');
    $opt_d = trim($_POST['opt_d'] ?? '');
    $correct = trim($_POST['correct_option'] ?? '');

    if ($exam_id <= 0 || $question === '' || $opt_a === '' || $opt_b === '' || $opt_c === '' || $opt_d === '' || !in_array($correct, ['A','B','C','D'])) {
        $questionError = 'Please fill all fields correctly for the question.';
    } else {
        $stmt = $conn->prepare("INSERT INTO questions (exam_id, question, option_a, option_b, option_c, option_d, correct_option) VALUES (?, ?, ?, ?, ?, ?, ?)");
        if ($stmt) {
            $stmt->bind_param('issssss', $exam_id, $question, $opt_a, $opt_b, $opt_c, $opt_d, $correct);
            if ($stmt->execute()) {
                $stmt->close();
                header('Location: dashboard.php?qadded=1');
                exit;
            } else {
                $questionError = 'DB error inserting question: ' . $stmt->error;
                error_log('DB insert question error: ' . $stmt->error);
                $stmt->close();
            }
        } else {
            $questionError = 'DB error preparing statement: ' . $conn->error;
            error_log('DB prepare question error: ' . $conn->error);
        }
    }
}

if (isset($_GET['qadded'])) {
    $questionSuccess = 'Question added successfully.';
}

// Handle Delete Exam from dashboard
$deleteMsg = '';
$deleteError = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_exam'])) {
    $del_id = (int)($_POST['delete_id'] ?? 0);
    if ($del_id <= 0) {
        $deleteError = 'Invalid exam id.';
    } else {
        // Delete related questions first to avoid orphaned data (if not using FK cascade)
        $conn->query("DELETE FROM questions WHERE exam_id='$del_id'");
        if ($conn->query("DELETE FROM exams WHERE id='$del_id'")) {
            $deleteMsg = 'Exam deleted successfully.';
            // Redirect to avoid resubmission
            header('Location: dashboard.php?deleted=1');
            exit;
        } else {
            $deleteError = 'DB error deleting exam: ' . $conn->error;
            error_log('DB delete exam error: ' . $conn->error);
        }
    }
}

if (isset($_GET['deleted'])) {
    $deleteMsg = 'Exam deleted successfully.';
}

// COUNTS
$examCount = 0;
$studentCount = 0;
$resultCount = 0; 

// Fetch exams for modal select
$examsList = $conn->query("SELECT id, exam_name FROM exams ORDER BY exam_name ASC");
if ($examsList === false) {
    error_log('DB error fetching exams list: ' . $conn->error);
    $examsList = null;
} else {
    // rewind pointer when reused for modal select
    $examsList->data_seek(0);
} 

// Safe count queries with fallbacks
$countRes = $conn->query("SELECT COUNT(*) c FROM exams");
if ($countRes && $row = $countRes->fetch_assoc()) {
    $examCount = (int) $row['c'];
} else {
    error_log('DB error counting exams: ' . $conn->error);
}

$studentRes = $conn->query("SELECT COUNT(*) c FROM students");
if ($studentRes && $row = $studentRes->fetch_assoc()) {
    $studentCount = (int) $row['c'];
} else {
    error_log('DB error counting students: ' . $conn->error);
}

$resultRes = $conn->query("SELECT COUNT(*) c FROM results");
if ($resultRes && $row = $resultRes->fetch_assoc()) {
    $resultCount = (int) $row['c'];
} else {
    error_log('DB error counting results: ' . $conn->error);
}

// Fetch latest exams (most recent 10)
$examError = null;
$examResult = $conn->query("SELECT id, exam_name, duration FROM exams ORDER BY id DESC LIMIT 10");
if ($examResult === false) {
    $examError = $conn->error;
    error_log('DB error fetching latest exams: ' . $examError);
    $examResult = null;
}

// Debug helpers: sample results and counts (visible when visiting ?debug=1)
$resultsCount = 0;
$resultsSample = [];
$cntRes = $conn->query("SELECT COUNT(*) c FROM results");
if ($cntRes && $r = $cntRes->fetch_assoc()) {
    $resultsCount = (int) $r['c'];
}
$sampleRes = $conn->query("SELECT r.marks, r.exam_id, e.exam_name FROM results r JOIN exams e ON e.id = r.exam_id LIMIT 5");
if ($sampleRes !== false) {
    while ($r = $sampleRes->fetch_assoc()) { $resultsSample[] = $r; }
} else {
    error_log('DB error fetching results sample: ' . $conn->error);
}
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
            <h4>Welcome, Barnali</h4>
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
                    <div style="height:360px; width:100%;">
                        <canvas id="examChart" style="width:100%; height:100%; display:block;"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card p-3">
                    <h6>Pass Percentage</h6>
                    <h1 class="text-center mt-4">87%</h1>
                </div>
            </div>
        </div>

        <!-- ANALYTICS CHARTS
<div class="row mt-4">
    <div class="col-md-6">
        <div class="card p-3">
            <h6>Exams Per Month</h6>
            <canvas id="examMonthChart"></canvas>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card p-3">
            <h6>Pass vs Fail</h6>
            <canvas id="passFailChart"></canvas>
        </div>
    </div>
</div>

<div class="row mt-4">
    <div class="col-md-6">
        <div class="card p-3">
            <h6>Average Marks</h6>
            <canvas id="avgMarksChart"></canvas>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card p-3">
            <h6>Most Attempted Exams</h6>
            <canvas id="attemptedChart"></canvas>
        </div>
    </div>
</div> -->


        <!-- RECENT EXAMS TABLE -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="card p-3">
                    <h6>Recent Activity / Latest Exams</h6>

                    <!-- QUICK ACTIONS BUTTONS -->
                    <div class="mt-3 mb-3 d-flex gap-2">
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addExamModal">Add Exam</button>
                        <button class="btn btn-secondary" data-bs-toggle="modal" data-bs-target="#addQuestionModal">Add Questions</button>
                        <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#viewResultsModal">View Results</button>
                        <button class="btn btn-info" data-bs-toggle="modal" data-bs-target="#exportDataModal">Export Data</button>
                    </div>

                    <?php if (!empty($success_msg)): ?>
                        <div class="alert alert-success mt-2"><?= htmlspecialchars($success_msg) ?></div>
                    <?php endif; ?>
                    <?php if (!empty($formError)): ?>
                        <div class="alert alert-danger mt-2"><?= htmlspecialchars($formError) ?></div>
                    <?php endif; ?>

                    <?php if (!empty($questionSuccess)): ?>
                        <div class="alert alert-success mt-2"><?= htmlspecialchars($questionSuccess) ?></div>
                    <?php endif; ?>
                    <?php if (!empty($questionError)): ?>
                        <div class="alert alert-danger mt-2"><?= htmlspecialchars($questionError) ?></div>
                    <?php endif; ?>

                    <?php if (!empty($deleteMsg)): ?>
                        <div class="alert alert-success mt-2"><?= htmlspecialchars($deleteMsg) ?></div>
                    <?php endif; ?>
                    <?php if (!empty($deleteError)): ?>
                        <div class="alert alert-danger mt-2"><?= htmlspecialchars($deleteError) ?></div>
                    <?php endif; ?>


                    <?php if (!empty($examError)): ?>
                        <div class="alert alert-danger mt-2">DB Error: <?= htmlspecialchars($examError) ?></div>
                    <?php endif; ?>

                    <div class="table-responsive mt-3">
                        <table class="table table-bordered table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Exam Name</th>
                                    <th>Duration (min)</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($examResult && $examResult->num_rows > 0): ?>
                                    <?php while ($row = $examResult->fetch_assoc()): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($row['exam_name']); ?></td>
                                            <td><?= isset($row['duration']) ? htmlspecialchars($row['duration']) . ' min' : '-' ?></td>
                                            <td>-</td>
                                            <td>
                                                <a href="manage_exam.php?edit=<?= $row['id']; ?>" class="btn btn-sm btn-primary">Edit</a>
                                                <a href="view_exam.php?id=<?= $row['id']; ?>" class="btn btn-sm btn-secondary">View</a>

                                                <form method="post" style="display:inline;" onsubmit="return confirm('Delete this exam and its questions?');">
                                                    <input type="hidden" name="delete_id" value="<?= $row['id']; ?>">
                                                    <button type="submit" name="delete_exam" class="btn btn-sm btn-danger">Delete</button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="4" class="text-center">No exams found</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <!-- Add Exam Modal -->
<div class="modal fade" id="addExamModal" tabindex="-1" aria-labelledby="addExamModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form action="" method="post" id="addExamForm">
        <div class="modal-header">
          <h5 class="modal-title" id="addExamModalLabel">Add Exam</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label>Exam Name</label>
            <input type="text" name="exam_name" class="form-control" required>
          </div>
          <div class="mb-3">
            <label>Duration (minutes)</label>
            <input type="number" name="duration" class="form-control" required>
          </div>
        </div>
        <div class="modal-footer">
          <button type="submit" name="add_exam" class="btn btn-primary">Add Exam</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Add Question Modal -->
<div class="modal fade" id="addQuestionModal" tabindex="-1" aria-labelledby="addQuestionModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="post" id="addQuestionForm">
        <div class="modal-header">
          <h5 class="modal-title" id="addQuestionModalLabel">Add Question</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label>Select Exam</label>
            <select name="exam_id" class="form-select" required>
                <option value="">Select exam</option>
                <?php if ($examsList): while($exL = $examsList->fetch_assoc()): ?>
                    <option value="<?= $exL['id'] ?>"><?= htmlspecialchars($exL['exam_name']) ?></option>
                <?php endwhile; endif; ?>
            </select>
          </div>
          <div class="mb-3">
            <label>Question Text</label>
            <textarea name="question" class="form-control" required></textarea>
          </div>
          <div class="mb-3">
            <label>Option A</label>
            <input type="text" name="opt_a" class="form-control" required>
          </div>
          <div class="mb-3">
            <label>Option B</label>
            <input type="text" name="opt_b" class="form-control" required>
          </div>
          <div class="mb-3">
            <label>Option C</label>
            <input type="text" name="opt_c" class="form-control" required>
          </div>
          <div class="mb-3">
            <label>Option D</label>
            <input type="text" name="opt_d" class="form-control" required>
          </div>
          <div class="mb-3">
            <label>Correct Option</label>
            <select name="correct_option" class="form-select" required>
              <option value="A">Option A</option>
              <option value="B">Option B</option>
              <option value="C">Option C</option>
              <option value="D">Option D</option>
            </select>
          </div>
        </div>
        <div class="modal-footer">
          <button type="submit" name="add_question_from_dashboard" class="btn btn-secondary">Add Question</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- View Results Modal -->
<div class="modal fade" id="viewResultsModal" tabindex="-1" aria-labelledby="viewResultsModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="viewResultsModalLabel">Results</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <p>Results table or stats can go here.</p>
      </div>
    </div>
  </div>
</div>

<!-- Export Data Modal -->
<div class="modal fade" id="exportDataModal" tabindex="-1" aria-labelledby="exportDataModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="exportDataModalLabel">Export Data</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <p>Choose export options here.</p>
        <button class="btn btn-success">Export CSV</button>
        <button class="btn btn-info">Export Excel</button>
      </div>
    </div>
  </div>
</div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

    <script>
    (function(){
        // Debug output for server-side chart data
        console.log('avgMarks.labels =', <?= json_encode($avgMarks['labels']) ?>);
        console.log('avgMarks.data   =', <?= json_encode($avgMarks['data']) ?>);

        // Exam Performance (avg marks)
        const examCanvas = document.getElementById('examChart');
        const examHasData = <?= json_encode(!empty($avgMarks['labels'])) ?>;
        if (examCanvas) {
            if (!examHasData) {
                // No DB data — render a large line demo chart (matches screenshot)
                const labels = ['Jan','Feb','Mar','Apr','May','Jun'];
                const randData = labels.map(() => Math.floor(Math.random() * 30) + 10);
                new Chart(examCanvas, {
                    type: 'line',
                    data: {
                        labels: labels,
                        datasets: [{
                            label: 'Demo Performance',
                            data: randData,
                            borderColor: '#0d6efd',
                            backgroundColor: 'rgba(13,110,253,0.08)',
                            tension: 0.3,
                            pointRadius: 4,
                            pointBackgroundColor: '#fff',
                            pointBorderColor: '#0d6efd',
                            borderWidth: 2
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: { legend: { display: false } },
                        scales: { y: { beginAtZero: true, grid: { color: 'rgba(0,0,0,0.06)' } }, x: { grid: { color: 'transparent' } } }
                    }
                });
            } else {
                new Chart(examCanvas, {
                    type: 'bar',
                    data: {
                        labels: <?= json_encode($avgMarks['labels']) ?>,
                        datasets: [{
                            label: 'Avg Marks',
                            data: <?= json_encode($avgMarks['data']) ?>,
                            backgroundColor: 'rgba(54, 162, 235, 0.6)'
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: { y: { beginAtZero: true } }
                    }
                });
            }
        }

        // Other charts — create only when their canvases exist
        const examMonthEl = document.getElementById('examMonthChart');
        if (examMonthEl) {
            new Chart(examMonthEl, { type: 'bar', data: { labels: <?= json_encode($examMonthData['labels']) ?>, datasets: [{ label: 'Exams', data: <?= json_encode($examMonthData['data']) ?> }] } });
        }

        const passFailEl = document.getElementById('passFailChart');
        if (passFailEl) {
            new Chart(passFailEl, { type: 'doughnut', data: { labels: ['Pass','Fail'], datasets: [{ data: <?= json_encode(array_values($passFail)) ?> }] } });
        }

        const avgMarksEl = document.getElementById('avgMarksChart');
        if (avgMarksEl) {
            new Chart(avgMarksEl, { type: 'line', data: { labels: <?= json_encode($avgMarks['labels']) ?>, datasets: [{ label: 'Avg Marks', data: <?= json_encode($avgMarks['data']) ?>, tension: 0.3 }] } });
        }

        const attemptedEl = document.getElementById('attemptedChart');
        if (attemptedEl) {
            new Chart(attemptedEl, { type: 'bar', data: { labels: <?= json_encode($attempted['labels']) ?>, datasets: [{ label: 'Attempts', data: <?= json_encode($attempted['data']) ?> }] } });
        }
    })();
    </script>

    <?php if (!empty($_GET['debug'])): ?>
    <div class="container mt-3">
        <div class="card p-3">
            <h6>Debug: Chart data</h6>
            <p><strong>avgMarks.labels</strong>: <?= htmlspecialchars(json_encode($avgMarks['labels'])) ?></p>
            <p><strong>avgMarks.data</strong>: <?= htmlspecialchars(json_encode($avgMarks['data'])) ?></p>
            <p><strong>results.count</strong>: <?= $resultsCount ?></p>
            <p><strong>results.sample (up to 5)</strong>:</p>
            <pre style="font-size:12px; white-space:pre-wrap;"><?= htmlspecialchars(json_encode($resultsSample, JSON_PRETTY_PRINT)) ?></pre>
        </div>
    </div>
    <?php endif; ?>


</body>

</html>