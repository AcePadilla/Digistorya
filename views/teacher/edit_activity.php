<?php
session_start();
require_once 'db_connection.php';

if (!isset($_SESSION['teacher_email'])) {
    header("Location: login.php");
    exit();
}

$email = $_SESSION['teacher_email'];

// Get the activity ID from GET
$id = $_GET['id'] ?? null;
if (!$id || !is_numeric($id)) {
    echo "Invalid activity ID.";
    exit();
}

// Get teacher info
$stmt = $conn->prepare("SELECT id, firstname FROM teachers WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result && $result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $teacher_id = $row['id'];
    $firstname = htmlspecialchars($row['firstname']);
} else {
    session_destroy();
    header("Location: login.php");
    exit();
}

// Fetch the activity from DB
$stmt = $conn->prepare("SELECT * FROM activities WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$activity = $result->fetch_assoc();

if (!$activity) {
    echo "Activity not found.";
    exit();
}

// Decode choices JSON into indexed array
$choices_indexed = json_decode($activity['choices'], true);
if (!is_array($choices_indexed)) {
    $choices_indexed = ["", "", "", ""];
}

// Map to associative array with keys A-D
$choices = [
    'A' => $choices_indexed[0] ?? '',
    'B' => $choices_indexed[1] ?? '',
    'C' => $choices_indexed[2] ?? '',
    'D' => $choices_indexed[3] ?? '',
];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $question = $_POST['question'] ?? '';
    $question_type = $_POST['question_type'] ?? '';
    // Collect choices from inputs
    $choice_a = $_POST['choice_a'] ?? '';
    $choice_b = $_POST['choice_b'] ?? '';
    $choice_c = $_POST['choice_c'] ?? '';
    $choice_d = $_POST['choice_d'] ?? '';
    $correct_answer = $_POST['correct_answer'] ?? '';

    // Validate correct_answer for letters (A-D) or True/False
    $valid_answers = ['A', 'B', 'C', 'D', 'True', 'False'];
    if (!in_array($correct_answer, $valid_answers)) {
        $correct_answer = '';
    }

    // Recreate choices array as indexed for JSON encode
    $updated_choices = [$choice_a, $choice_b, $choice_c, $choice_d];
    $choices_json = json_encode($updated_choices);

    // Update the activity in DB
    $stmt = $conn->prepare("UPDATE activities SET question_text = ?, question_type = ?, choices = ?, correct_answer = ? WHERE id = ?");
    $stmt->bind_param("ssssi", $question, $question_type, $choices_json, $correct_answer, $id);
    $stmt->execute();

    header("Location: activities.php?updated=1");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>DIGIstorya</title>
  <link rel="icon" href="../../images/logo.png">
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://kit.fontawesome.com/aed89df169.js" crossorigin="anonymous"></script>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <link href="https://fonts.googleapis.com/css2?family=Island+Moments&display=swap" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@700&display=swap" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900&display=swap" rel="stylesheet">
  <style>
     body {
      font-family: 'Poppins', sans-serif;
    }
  </style>
</head>
<body class="flex justify-center items-start bg-[#f0e8e1] min-h-screen py-10 px-4">

  <form method="POST" class="bg-white p-8 rounded-lg shadow-lg max-w-xl w-full space-y-6">

    <h1 class="text-3xl font-bold mb-6 text-[#551a25]">Edit Activity</h1>

    <div>
      <label for="question" class="block mb-2 font-semibold text-[#551a25]">Question:</label>
      <textarea name="question" id="question" required
        class="w-full p-3 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-[#d79e4b]"
        rows="4"><?= htmlspecialchars($activity['question_text']) ?></textarea>
    </div>

    <div>
      <label for="question_type" class="block mb-2 font-semibold text-[#551a25]">Question Type:</label>
      <select name="question_type" id="question_type" required
        class="w-full max-w-xs p-3 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-[#d79e4b]">
        <option value="mcq" <?= $activity['question_type'] === 'mcq' ? 'selected' : '' ?>>Multiple Choice (A-D)</option>
        <option value="truefalse" <?= $activity['question_type'] === 'truefalse' ? 'selected' : '' ?>>True / False</option>
      </select>
    </div>

    <div id="mcqChoices" class="<?= $activity['question_type'] === 'mcq' ? '' : 'hidden' ?> space-y-4">
      <div>
        <label for="choice_a" class="block mb-1 font-semibold text-[#551a25]">Choice A:</label>
        <input type="text" name="choice_a" id="choice_a" value="<?= htmlspecialchars($choices['A']) ?>"
          class="w-full max-w-xl p-3 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-[#d79e4b]" />
      </div>
      <div>
        <label for="choice_b" class="block mb-1 font-semibold text-[#551a25]">Choice B:</label>
        <input type="text" name="choice_b" id="choice_b" value="<?= htmlspecialchars($choices['B']) ?>"
          class="w-full max-w-xl p-3 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-[#d79e4b]" />
      </div>
      <div>
        <label for="choice_c" class="block mb-1 font-semibold text-[#551a25]">Choice C:</label>
        <input type="text" name="choice_c" id="choice_c" value="<?= htmlspecialchars($choices['C']) ?>"
          class="w-full max-w-xl p-3 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-[#d79e4b]" />
      </div>
      <div>
        <label for="choice_d" class="block mb-1 font-semibold text-[#551a25]">Choice D:</label>
        <input type="text" name="choice_d" id="choice_d" value="<?= htmlspecialchars($choices['D']) ?>"
          class="w-full max-w-xl p-3 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-[#d79e4b]" />
      </div>
    </div>

    <div>
      <label for="correct_answer" class="block mb-2 font-semibold text-[#551a25]">Correct Answer:</label>
      <select name="correct_answer" id="correctAnswerSelect" required
        class="w-full max-w-xs p-3 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-[#d79e4b]">
        <!-- Options added dynamically by JS -->
      </select>
    </div>

    <button type="submit"
      class="w-full bg-[#551a25] text-white font-semibold py-3 rounded hover:bg-[#3f101a] transition">Update
      Activity</button>
  </form>



<script>
function toggleChoices(type) {
    const mcqDiv = document.getElementById('mcqChoices');
    if (type === 'mcq') {
        mcqDiv.classList.remove('hidden');
    } else {
        mcqDiv.classList.add('hidden');
    }
    populateCorrectAnswerOptions(type);
}

function populateCorrectAnswerOptions(type) {
    const select = document.getElementById('correctAnswerSelect');
    const currentValue = select.getAttribute('data-current') || '';

    select.innerHTML = '';

    let options = [];

    if (type === 'mcq') {
        options = ['A', 'B', 'C', 'D'];
    } else if (type === 'truefalse') {
        options = ['True', 'False'];
    }

    // Placeholder option
    const placeholder = document.createElement('option');
    placeholder.value = '';
    placeholder.textContent = 'Select correct answer';
    select.appendChild(placeholder);

    options.forEach(opt => {
        const optionElem = document.createElement('option');
        optionElem.value = opt;
        optionElem.textContent = opt;
        if (opt === currentValue) {
            optionElem.selected = true;
        }
        select.appendChild(optionElem);
    });

    if (!options.includes(currentValue)) {
        select.value = '';
    }
}

document.addEventListener('DOMContentLoaded', () => {
    const questionTypeSelect = document.getElementById('question_type');
    const correctAnswerSelect = document.getElementById('correctAnswerSelect');

    // Set current correct answer for selection
    correctAnswerSelect.setAttribute('data-current', <?= json_encode($activity['correct_answer']) ?>);

    toggleChoices(questionTypeSelect.value);

    questionTypeSelect.addEventListener('change', (e) => {
        toggleChoices(e.target.value);
    });
});
</script>
</body>
</html>
