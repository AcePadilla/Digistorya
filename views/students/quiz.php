<?php
session_start();
require_once 'db_connection.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$lessDominantStyles = [];

// Get user's less dominant styles
$sql = "
    SELECT active_reflective, sensing_intuitive, visual_verbal, sequential_global
    FROM quiz_results
    WHERE user_id = ?
    ORDER BY created_at DESC
    LIMIT 1
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result && $result->num_rows > 0) {
    $row = $result->fetch_assoc();

    $active_reflective = explode('|', $row['active_reflective']);
    $sensing_intuitive = explode('|', $row['sensing_intuitive']);
    $visual_verbal = explode('|', $row['visual_verbal']);
    $sequential_global = explode('|', $row['sequential_global']);

    $lessDominantStyles[] = $active_reflective[0] > $active_reflective[1] ? 'Reflective' : 'Active';
    $lessDominantStyles[] = $sensing_intuitive[0] > $sensing_intuitive[1] ? 'Intuitive' : 'Sensing';
    $lessDominantStyles[] = $visual_verbal[0] > $visual_verbal[1] ? 'Verbal' : 'Visual';
    $lessDominantStyles[] = $sequential_global[0] > $sequential_global[1] ? 'Global' : 'Sequential';
} else {
    echo "No quiz results found.";
    exit;
}

// Define questions
$questions = [
    "Active" => [
        "mc" => ["question" => "Ano ang mas gusto mong gawin sa klase?", "options" => ["A" => "Makinig sa lecture", "B" => "Gumawa ng group activity", "C" => "Manood ng video", "D" => "Gumawa ng worksheet"], "answer" => "B"],
        "tf" => ["question" => "Ang active learners ay natututo sa pamamagitan ng paggalaw at pakikilahok.", "answer" => "True"]
    ],
    "Reflective" => [
        "mc" => ["question" => "Paano mo pinoproseso ang bagong impormasyon?", "options" => ["A" => "Agad ko itong sinasabi", "B" => "Isinusulat ko ito at pinag-iisipan", "C" => "Ipinipinta ko ito", "D" => "Ibinabahagi ko sa grupo"], "answer" => "B"],
        "tf" => ["question" => "Ang reflective learners ay nangangailangan ng oras para mag-isip bago magsalita.", "answer" => "True"]
    ],
    "Sensing" => [
        "mc" => ["question" => "Alin sa mga ito ang gusto mong gamitin sa pag-aaral?", "options" => ["A" => "Data at real-life examples", "B" => "Imaginative stories", "C" => "Hula at predictions", "D" => "Conceptual theory"], "answer" => "A"],
        "tf" => ["question" => "Ang sensing learners ay gusto ng tiyak na impormasyon at facts.", "answer" => "True"]
    ],
    "Intuitive" => [
        "mc" => ["question" => "Alin ang gusto mong gawin?", "options" => ["A" => "Pag-aralan ang mga detalye", "B" => "Gumawa ng bagong paraan ng paglutas", "C" => "Sumunod sa mga instructions", "D" => "Ulitin ang eksaktong ginawa ng iba"], "answer" => "B"],
        "tf" => ["question" => "Mas interesado ang intuitive learners sa concepts kaysa sa facts.", "answer" => "True"]
    ],
    "Visual" => [
        "mc" => ["question" => "Ano ang nakakatulong sa iyong pag-unawa?", "options" => ["A" => "Pagbabasa ng mahabang teksto", "B" => "Diagram o larawan", "C" => "Pakikinig ng audio", "D" => "Discussion sa klase"], "answer" => "B"],
        "tf" => ["question" => "Mas natututo ang visual learners kapag may kulay o larawan ang impormasyon.", "answer" => "True"]
    ],
    "Verbal" => [
        "mc" => ["question" => "Anong activity ang gusto mo?", "options" => ["A" => "Pag-drawing", "B" => "Pagsulat ng sanaysay", "C" => "Pagbuo ng modelo", "D" => "Pagkulay ng mapa"], "answer" => "B"],
        "tf" => ["question" => "Ang verbal learners ay mas natututo sa pagsasalita at pagsusulat.", "answer" => "True"]
    ],
    "Sequential" => [
        "mc" => ["question" => "Paano ka mas natututo?", "options" => ["A" => "Sunod-sunod na steps", "B" => "Random na ideya", "C" => "Pagku-kwento", "D" => "Pag-guhit"], "answer" => "A"],
        "tf" => ["question" => "Gusto ng sequential learners ang organized at sunod-sunod na impormasyon.", "answer" => "True"]
    ],
    "Global" => [
        "mc" => ["question" => "Ano ang gusto mong malaman agad sa aralin?", "options" => ["A" => "Buod o kabuuang konsepto", "B" => "Detalyado agad", "C" => "Definition lang", "D" => "Timeline"], "answer" => "A"],
        "tf" => ["question" => "Global learners gusto muna makita ang kabuuan bago ang detalye.", "answer" => "True"]
    ],
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $score = 0;
    foreach ($lessDominantStyles as $style) {
        $mcKey = $style . "_mc";
        $tfKey = $style . "_tf";
        if ($_POST[$mcKey] == $questions[$style]['mc']['answer']) $score++;
        if ($_POST[$tfKey] == $questions[$style]['tf']['answer']) $score++;
    }

    $session_id = session_id();
    $stmt = $conn->prepare("INSERT INTO quiz_submissions (user_id, session_id, submission_date, score) VALUES (?, ?, NOW(), ?)");
    $stmt->bind_param("isi", $user_id, $session_id, $score);
    $stmt->execute();
    $stmt->close();

   echo "<script>
    // Show SweetAlert with the score
    window.parent.Swal.fire({
        title: 'Tapos na!',
        text: 'Exercise completed. Your score: $score',
        icon: 'success',
        confirmButtonText: 'Okay'
    }).then(() => {
        // After clicking 'Okay', hide the modal
        const modal = window.parent.document.getElementById('exerciseModal');
        if (modal) {
            modal.classList.add('hidden'); // Tailwind 'hidden' class to close
        }

        // Optional: clear iframe content
        const iframe = window.parent.document.getElementById('exerciseIframe');
        if (iframe) {
            iframe.src = '';
        }
    });
</script>";

    exit;
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>DIGIstorya</title>
  <link rel="icon" href="../../images/logo.png" />
  <script src="https://kit.fontawesome.com/aed89df169.js" crossorigin="anonymous"></script>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet" />
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet" />
  <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />

  <style>
    body {
      font-family: 'Poppins', sans-serif;
    }
  </style>
</head>
<body class="bg-[#f0e8e1] min-h-screen flex flex-col items-center py-10 ">

<h1 class="text-4xl font-extrabold mb-6 text-center text-[#551a25] tracking-wide">
  Strengthen Your Less <br>Dominant Learning Styles
</h1>
<p class="text-center text-gray-600 mb-10 max-w-2xl mx-auto">
  This activity is designed to help improve the learning styles that are less dominant for you. You have 10 minutes to answer 8 questions — a mix of multiple choice and true or false — for each learning style. Focus, take your time, and do your best!
</p>


 <form method="POST" class="space-y-12">

  <?php foreach ($lessDominantStyles as $style): ?>
    <div class="bg-[#fdf8f4] border-l-8 border-[#551a25] p-6 sm:p-8 rounded-2xl shadow-md hover:shadow-lg transition duration-300">

<!-- Header -->
<div class="">
   <div
    class="
      inline-flex items-center 
      bg-white bg-opacity-10 backdrop-blur-sm 
      border border-[#551a25] 
      text-[#d79e4b]
      text-lg font-semibold 
      px-6 py-2 rounded-xl 
      shadow-md
      select-none
      tracking-wide
      whitespace-nowrap
    "
  >
    <i class="fa-solid fa-brain text-2xl mr-3"></i>
    <span><?= $style ?></span>
  </div>
  <h2
    class="text-lg font-extrabold text-[#551a25] tracking-tight leading-tight relative my-2"
  >
    Multiple Choice

  </h2>
</div>


      <!-- Multiple Choice Question -->
      <p class="text-gray-700 mb-4"><?= $questions[$style]['mc']['question'] ?></p>
      <div class="space-y-3">
        <?php foreach ($questions[$style]['mc']['options'] as $key => $option): ?>
          <label class="flex items-center gap-3 p-3 rounded-lg border border-[#e0d6d0] bg-white hover:bg-[#fdf4e6] cursor-pointer transition">
            <input type="radio" name="<?= $style ?>_mc" value="<?= $key ?>" required class="accent-[#551a25] w-5 h-5">
            <span class="text-gray-800"><?= $key ?>. <?= $option ?></span>
          </label>
        <?php endforeach; ?>
      </div>

      <!-- True or False Section -->
      <div class="mt-8">
 <h2
       class="text-lg font-extrabold text-[#551a25] tracking-tight leading-tight relative my-2"
  >
    Tama o Mali

  </h2>
  
        <p class="text-gray-700 mb-4"><?= $questions[$style]['tf']['question'] ?></p>
        <div class="space-y-3">
          <label class="flex items-center gap-3 p-3 rounded-lg border border-[#e0d6d0] bg-white hover:bg-[#fdf4e6] cursor-pointer transition">
            <input type="radio" name="<?= $style ?>_tf" value="True" required class="accent-[#551a25] w-5 h-5">
            <span class="text-gray-800">Tama</span>
          </label>
          <label class="flex items-center gap-3 p-3 rounded-lg border border-[#e0d6d0] bg-white hover:bg-[#fdf4e6] cursor-pointer transition">
            <input type="radio" name="<?= $style ?>_tf" value="False" class="accent-[#551a25] w-5 h-5">
            <span class="text-gray-800">Mali</span>
          </label>
        </div>
      </div>
    </div>
  <?php endforeach; ?>

  <!-- Submit Button -->
  <div class="text-center mt-10">
    <button type="submit"
      class="bg-[#551a25] hover:bg-[#3d1018] text-white px-10 py-3 rounded-full text-lg font-semibold shadow-lg transition-all duration-200 hover:scale-105">
      Submit Your Answers
    </button>
  </div>
  <script>
document.getElementById('quizForm').addEventListener('submit', function(e) {
  e.preventDefault();

  // AJAX request to submit the form data
  fetch('submit_quiz.php', {
    method: 'POST',
    body: new FormData(this)
  })
  .then(response => response.json())
  .then(data => {
    if (data.success) {
      // Notify parent window that quiz is submitted
      window.parent.postMessage({ action: 'quizSubmitted' }, '*');
    } else {
      alert('Submission failed, try again.');
    }
  });
});
if (data.success) {
  // Sabihan ang parent na nagsubmit na
  window.parent.postMessage({ action: 'quizSubmitted' }, '*');
}

</script>
</form>


</body>

</html>
