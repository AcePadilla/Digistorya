<?php 
session_start();
require_once 'db_connection.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Get user info and section
$user_sql = "
    SELECT users.firstname, users.lastname, users.section_id, section.section_name
    FROM users
    JOIN section ON users.section_id = section.id
    WHERE users.id = ?
";
$stmt = mysqli_prepare($conn, $user_sql);
if (!$stmt) {
    die("Prepare failed (user_sql): " . mysqli_error($conn));
}
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($result);

$section_id = $user['section_id'] ?? null;

if (!$section_id) {
    die("Walang nakuhang section_id. Baka may problema sa account data.");
}

// Handle answer submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['answers'])) {
    foreach ($_POST['answers'] as $activity_id => $student_answer) {
        // Check if already answered
        $check_sql = "SELECT id FROM activity_submissions WHERE user_id = ? AND activity_id = ?";
        $check_stmt = mysqli_prepare($conn, $check_sql);
        if (!$check_stmt) {
            die("Prepare failed (check_sql): " . mysqli_error($conn));
        }
        mysqli_stmt_bind_param($check_stmt, "ii", $user_id, $activity_id);
        mysqli_stmt_execute($check_stmt);
        $check_result = mysqli_stmt_get_result($check_stmt);

        if (mysqli_num_rows($check_result) === 0) {
            // Get correct answer
            $answer_sql = "SELECT correct_answer FROM activities WHERE id = ?";
            $ans_stmt = mysqli_prepare($conn, $answer_sql);
            if (!$ans_stmt) {
                die("Prepare failed (answer_sql): " . mysqli_error($conn));
            }
            mysqli_stmt_bind_param($ans_stmt, "i", $activity_id);
            mysqli_stmt_execute($ans_stmt);
            $activity = mysqli_fetch_assoc(mysqli_stmt_get_result($ans_stmt));

            $correct_answer = trim(strtolower($activity['correct_answer']));
            $given_answer = trim(strtolower($student_answer));
            $is_correct = ($correct_answer === $given_answer) ? 1 : 0;

            // Insert submission
            $insert_sql = "
                INSERT INTO activity_submissions (user_id, activity_id, answer, is_correct)
                VALUES (?, ?, ?, ?)
            ";
            $insert_stmt = mysqli_prepare($conn, $insert_sql);
            if (!$insert_stmt) {
                die("Prepare failed (insert_sql): " . mysqli_error($conn));
            }
            mysqli_stmt_bind_param($insert_stmt, "iisi", $user_id, $activity_id, $student_answer, $is_correct);
            mysqli_stmt_execute($insert_stmt);
        }
    }

    header("Location: " . $_SERVER['PHP_SELF'] . "?submitted=1");
    exit;
}

$activities_sql = "
    SELECT id, question_text, question_type, choices, title
    FROM activities
    WHERE section_id = ?
    ORDER BY title, created_at
";

$act_stmt = mysqli_prepare($conn, $activities_sql);
if (!$act_stmt) {
    die("Prepare failed (activities_sql): " . mysqli_error($conn));
}
mysqli_stmt_bind_param($act_stmt, "i", $section_id);
mysqli_stmt_execute($act_stmt);
$activities_result = mysqli_stmt_get_result($act_stmt);

$activities_by_title = [];

while ($activity = mysqli_fetch_assoc($activities_result)) {
    $title = $activity['title'] ?? 'No Title';
    if (!isset($activities_by_title[$title])) {
        $activities_by_title[$title] = [];
    }
    $activities_by_title[$title][] = $activity;
}

// After inserting all answers, calculate summary score for the user
// Count correct answers
$score_sql = "
    SELECT COUNT(*) AS total_score
    FROM activity_submissions
    WHERE user_id = ? AND is_correct = 1
";
$score_stmt = mysqli_prepare($conn, $score_sql);
mysqli_stmt_bind_param($score_stmt, "i", $user_id);
mysqli_stmt_execute($score_stmt);
$score_result = mysqli_stmt_get_result($score_stmt);
$score_data = mysqli_fetch_assoc($score_result);
$total_score = $score_data['total_score'] ?? 0;

// Count total activities for the user's section
$total_items_sql = "
    SELECT COUNT(*) AS total_items
    FROM activities
    WHERE section_id = ?
";
$total_stmt = mysqli_prepare($conn, $total_items_sql);
mysqli_stmt_bind_param($total_stmt, "i", $section_id);
mysqli_stmt_execute($total_stmt);
$total_result = mysqli_stmt_get_result($total_stmt);
$total_data = mysqli_fetch_assoc($total_result);
$total_items = $total_data['total_items'] ?? 0;

// Compute percentage
$percentage_score = ($total_items > 0) ? round(($total_score / $total_items) * 100, 2) : 0.00;

// Check if summary record exists
$check_summary_sql = "
    SELECT id 
    FROM activity_submissions 
    WHERE user_id = ? AND section_id = ? AND total_score IS NOT NULL
";
$check_summary_stmt = mysqli_prepare($conn, $check_summary_sql);
mysqli_stmt_bind_param($check_summary_stmt, "ii", $user_id, $section_id);
mysqli_stmt_execute($check_summary_stmt);
$check_summary_result = mysqli_stmt_get_result($check_summary_stmt);

if (mysqli_num_rows($check_summary_result) > 0) {
    // Update existing summary record
    $update_sql = "
        UPDATE activity_submissions
        SET total_score = ?, total_items = ?, percentage_score = ?, submitted_at = NOW()
        WHERE user_id = ? AND section_id = ? AND total_score IS NOT NULL
    ";
    $update_stmt = mysqli_prepare($conn, $update_sql);
    if (!$update_stmt) {
        die("Prepare failed (update_sql): " . mysqli_error($conn));
    }
    mysqli_stmt_bind_param($update_stmt, "iiiii", $total_score, $total_items, $percentage_score, $user_id, $section_id);
    mysqli_stmt_execute($update_stmt);
} else {
    // Insert summary record
    $insert_summary_sql = "
        INSERT INTO activity_submissions (user_id, section_id, total_score, total_items, percentage_score, submitted_at)
        VALUES (?, ?, ?, ?, ?, NOW())
    ";
    $insert_summary_stmt = mysqli_prepare($conn, $insert_summary_sql);
    if (!$insert_summary_stmt) {
        die("Prepare failed (insert_summary_sql): " . mysqli_error($conn));
    }
    mysqli_stmt_bind_param($insert_summary_stmt, "iiiid", $user_id, $section_id, $total_score, $total_items, $percentage_score);
    mysqli_stmt_execute($insert_summary_stmt);
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>DIGIstorya</title>
  <link rel="icon" href="../../images/logo.png" />
  <script src="https://kit.fontawesome.com/aed89df169.js" crossorigin="anonymous"></script>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet" />
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet" />
  <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>

  <style>
    body {
      font-family: 'Poppins', sans-serif;
      background: linear-gradient(to bottom right, #fffaf6, #f0e8e1);
      overflow-x: hidden;
    }

    .bg-primary {
      background-color: #551a25;
    }

    .text-primary {
      color: #551a25;
    }

    .text-secondary {
      color: #d79e4b;
    }

    .bg-secondary {
      background-color: #d79e4b;
    }

    .hover-card:hover {
      transform: translateY(-8px) scale(1.02);
      box-shadow: 0 20px 30px rgba(0, 0, 0, 0.15);
    }

    .glass {
      background: rgba(255, 255, 255, 0.7);
      backdrop-filter: blur(10px);
      border: 1px solid rgba(255, 255, 255, 0.3);
    }

    .search-btn {
      transition: all 0.3s ease;
    }

    .search-btn:hover {
      background-color: #d79e4b;
      transform: scale(1.1);
    }
  </style>
</head>

<div class="bg-[#f0e8e1] min-h-screen p-8">


    <div class="bg-[#f0e8e1] min-h-screen p-8">
    <div class="max-w-4xl mx-auto bg-white shadow-xl rounded-xl p-8 border-l-[6px] border-[#d79e4b]">
        
    <h2 class="text-3xl font-extrabold mb-8 text-[#551a25] text-center md:text-left border-b-4 border-[#d79e4b] inline-block pb-2">
        Activities for Section: <?= htmlspecialchars($user['section_name']) ?>
    </h2>

    <form method="POST" onsubmit="return validateAnswers()" class="space-y-10">
        <?php foreach ($activities_by_title as $title => $activities): ?>
            <div class="bg-white rounded-xl shadow-md p-6 ">
                <h3 class="text-2xl font-bold text-[#551a25] mb-4">
                    <?= htmlspecialchars($title) ?>
                </h3>

                <?php foreach ($activities as $activity): ?>
                    <div class="bg-[#fdf9f6] rounded-lg border border-[#e2cbb0] p-5 mb-6">
                        <p class="text-lg font-semibold text-[#551a25] mb-2">
                            <?= htmlspecialchars($activity['question_text']) ?>
                        </p>
                        <p class="text-sm text-gray-600 italic mb-3">
                            (<?= strtoupper($activity['question_type']) ?>)
                        </p>

                        <div data-question-id="<?= $activity['id'] ?>">
                            <?php if ($activity['question_type'] === 'mcq'): ?>
                                <?php
                                    $choices = json_decode($activity['choices'], true);
                                    foreach ($choices as $letter => $choice):
                                ?>
                                    <label class="flex items-center gap-2 mb-3 cursor-pointer hover:bg-[#f0e8e1] p-2 rounded transition">
                                        <input type="radio" name="answers[<?= $activity['id'] ?>]" value="<?= htmlspecialchars($letter) ?>" class="accent-[#d79e4b] w-4 h-4">
                                        <span class="text-[#551a25]"><?= htmlspecialchars($letter) ?>. <?= htmlspecialchars($choice) ?></span>
                                    </label>
                                <?php endforeach; ?>
                            <?php elseif ($activity['question_type'] === 'truefalse'): ?>
                                <label class="flex items-center gap-2 mb-2 cursor-pointer hover:bg-[#f0e8e1] p-2 rounded transition">
                                    <input type="radio" name="answers[<?= $activity['id'] ?>]" value="true" class="accent-[#d79e4b] w-4 h-4"> 
                                    <span class="text-[#551a25]">True</span>
                                </label>
                                <label class="flex items-center gap-2 mb-2 cursor-pointer hover:bg-[#f0e8e1] p-2 rounded transition">
                                    <input type="radio" name="answers[<?= $activity['id'] ?>]" value="false" class="accent-[#d79e4b] w-4 h-4"> 
                                    <span class="text-[#551a25]">False</span>
                                </label>
                            <?php else: ?>
                                <p class="text-red-600">Unknown question type.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endforeach; ?>

        <div class="text-right">
            <button type="submit" class="bg-[#551a25] text-white font-bold text-lg px-6 py-3 rounded-lg shadow-md hover:bg-[#c5893f] hover:scale-105 transition transform duration-200">
                Submit Answers
            </button>
        </div>
    </form>
</div>


    <!-- SweetAlert2 CDN -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- Show SweetAlert if submitted -->
    <?php if (isset($_GET['submitted'])): ?>
        <script>
            Swal.fire({
                icon: 'success',
                title: 'Submitted!',
                text: 'Answers submitted successfully!',
                confirmButtonColor: '#d79e4b'
            });
        </script>
    <?php endif; ?>

    <!-- Validation script -->
    <script>
        function validateAnswers() {
            const questions = document.querySelectorAll('[data-question-id]');
            let allAnswered = true;

            questions.forEach(q => {
                const questionId = q.getAttribute('data-question-id');
                const options = document.querySelectorAll(`input[name="answers[${questionId}]"]`);
                const oneChecked = Array.from(options).some(opt => opt.checked);
                if (!oneChecked) {
                    allAnswered = false;
                }
            });

            if (!allAnswered) {
                Swal.fire({
                    icon: 'error',
                    title: 'Incomplete',
                    text: 'Please answer all questions before submitting.',
                    confirmButtonColor: '#d79e4b'
                });
                return false;
            }

            return true;
        }
    </script>
</div>



<script>

AOS.init();

document.addEventListener('contextmenu', event => event.preventDefault());

document.addEventListener('keydown', function (event) {
  if (
    event.key === 'F12' ||
    (event.ctrlKey && event.shiftKey && (event.key === 'I' || event.key === 'C' || event.key === 'J')) ||
    (event.ctrlKey && event.key === 'U')
  ) {
    event.preventDefault();
  }
});

document.getElementById("menu-toggle").addEventListener("click", function () {
      const menu = document.getElementById("mobile-menu");
      menu.classList.toggle("hidden");
    });

</script>
<script>
  // I-call ito pag tapos na ang activity
  window.parent.postMessage('activityDone', '*');
</script>

<?php if (isset($_GET['submitted'])): ?>
    <script>
        Swal.fire({
            icon: 'success',
            title: 'Submitted!',
            text: 'Your answers have been recorded.',
            timer: 1500,
            showConfirmButton: false
        });

        // Notify parent window to close modal
        window.parent.postMessage({ type: 'closeModal' }, '*');
    </script>
<?php endif; ?>

</body>
</html>
