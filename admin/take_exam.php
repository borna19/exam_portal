<?php
include '../includes/db.php';
if (empty($_GET['exam_id'])) { echo "No exam specified."; exit; }
$exam_id = (int)$_GET['exam_id'];
$exam = $conn->query("SELECT * FROM exams WHERE id = $exam_id")->fetch_assoc();
$questions = $conn->query("SELECT * FROM questions WHERE exam_id = $exam_id");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $score = 0; $total = 0;
  $qs = $conn->query("SELECT * FROM questions WHERE exam_id = $exam_id");
  while ($q = $qs->fetch_assoc()) {
    $total += $q['marks'];
    $given = $_POST['q'][$q['id']] ?? '';
    if ($given && strtoupper($given) === strtoupper($q['correct'])) $score += $q['marks'];
  }
  echo "<p>Your score: $score / $total</p>";
  exit;
}
?>
<h2><?=htmlspecialchars($exam['title'])?></h2>
<form method="post">
  <?php $i=1; while($q = $questions->fetch_assoc()): ?>
    <div>
      <p><strong><?=$i?>.</strong> <?=htmlspecialchars($q['question'])?></p>
      <label><input type="radio" name="q[<?=$q['id']?>]" value="A"> <?=htmlspecialchars($q['opt_a'])?></label><br>
      <label><input type="radio" name="q[<?=$q['id']?>]" value="B"> <?=htmlspecialchars($q['opt_b'])?></label><br>
      <label><input type="radio" name="q[<?=$q['id']?>]" value="C"> <?=htmlspecialchars($q['opt_c'])?></label><br>
      <label><input type="radio" name="q[<?=$q['id']?>]" value="D"> <?=htmlspecialchars($q['opt_d'])?></label><br>
    </div>
  <?php $i++; endwhile; ?>
  <button type="submit">Submit Exam</button>
</form>