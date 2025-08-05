<?php
session_start();
require_once 'db_connection.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
$user_id = $_SESSION['user_id'];

$dominantStyles = null;
$errorMsg = null;

if ($user_id) {
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

     $dominantStyles = [];
$lessDominantStyles = [];

$lessDominantStyles['Active vs Reflective'] = $active_reflective[0] < $active_reflective[1] ? 'Active' : 'Reflective';
$lessDominantStyles['Sensing vs Intuitive'] = $sensing_intuitive[0] < $sensing_intuitive[1] ? 'Sensing' : 'Intuitive';
$lessDominantStyles['Visual vs Verbal'] = $visual_verbal[0] < $visual_verbal[1] ? 'Visual' : 'Verbal';
$lessDominantStyles['Sequential vs Global'] = $sequential_global[0] < $sequential_global[1] ? 'Sequential' : 'Global';


    } else {
        $errorMsg = "No quiz results found. Please complete the learning style quiz first.";
    }

    $stmt->close();
} else {
    $errorMsg = "User not logged in.";
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
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <style>
    body {
      font-family: 'Poppins', sans-serif;
    }
  </style>
</head>

<body class="bg-[#f0e8e1] text-[#551a25]">
 <header class="bg-[#551a25] shadow-lg z-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 flex items-center justify-between">
      <div class="flex items-center gap-3">
        <img src="../../images/logo.png" alt="DIGIstorya Logo" class="h-10 w-auto" />
        <span class="text-[#f0e8e1] font-bold text-2xl tracking-wide hidden sm:inline">DIGIstorya</span>
      </div>

      <nav class="hidden md:flex items-center gap-8">
        <a href="home.php" class="text-[#f0e8e1] font-semibold tracking-wide hover:text-[#d79e4b] hover:scale-105 transition-all duration-200">HOME</a>
        <a href="about.html" class="text-[#f0e8e1] font-semibold tracking-wide hover:text-[#d79e4b] hover:scale-105 transition-all duration-200">ABOUT</a>
        <a href="more.html" class="text-[#f0e8e1] font-semibold tracking-wide hover:text-[#d79e4b] hover:scale-105 transition-all duration-200">MORE</a>
         <a href="activities.php" class="text-[#f0e8e1] font-semibold tracking-wide hover:text-[#d79e4b] hover:scale-105 transition-all duration-200">ACTIVITIES</a>
        <a href="exercise.php" class="block py-2 text-[#f0e8e1] font-semibold hover:text-[#d79e4b]">EXERCISES</a>
      </nav>

      <div class="flex items-center gap-5">
        <a href="profile.php" class="text-[#f0e8e1] text-xl hover:text-[#d79e4b] transition">
          <i class="fas fa-user-circle"></i>
        </a>
        <a href="logout.php" class="text-[#f0e8e1] text-xl hover:text-[#d79e4b] transition">
          <i class="fas fa-sign-out-alt"></i>
        </a>
        <button id="menu-toggle" class="md:hidden text-[#f0e8e1] focus:outline-none">
          <svg class="w-7 h-7" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
          </svg>
        </button>
      </div>
    </div>

    <div id="mobile-menu" class="md:hidden px-6 pb-4 hidden transition-all duration-300">
      <a href="home.php" class="block py-2 text-[#f0e8e1] font-semibold hover:text-[#d79e4b]">HOME</a>
      <a href="about.html" class="block py-2 text-[#f0e8e1] font-semibold hover:text-[#d79e4b]">ABOUT</a>
      <a href="more.html" class="block py-2 text-[#f0e8e1] font-semibold hover:text-[#d79e4b]">MORE</a>
      <a href="activities.php" class="block py-2 text-[#f0e8e1] font-semibold hover:text-[#d79e4b]">ACTIVITES</a>
      <a href="exercise.php" class="block py-2 text-[#f0e8e1] font-semibold hover:text-[#d79e4b]">EXERCISES</a>
    </div>
  </header>
<?php 

$lessons = [
   'Active' => [
    'title' => 'Active',
    'goal' => 'Magsanay ng aktibong pakikilahok sa bawat aralin upang mas madali at mas malalim ang pagkatuto. Layunin nitong hikayatin kang makilahok sa mga talakayan, gumawa ng mga aktibidad, at matutong sa pamamagitan ng karanasan at interaksyon.',
    'explanation' => '
      <p>Ang pag-unlad sa pagiging isang Active learner ay makakatulong sa iyo na mas makisali sa klase, mas madaling matuto sa pamamagitan ng paggawa, at makabuo ng mas matibay na koneksyon sa mga aralin. Mahalaga ito lalo na sa mga sitwasyong kailangan ng kolaborasyon, pagsubok ng ideya, at pakikilahok sa aktibidad. Kahit hindi ito ang natural mong estilo, ang paglinang nito ay makakatulong sa iyong interpersonal skills at sa mas epektibong pagkatuto.</p>
      <ul class="list-disc ml-6 space-y-1">
        <li>Sumali sa mga study groups at aktibong makipagpalitan ng ideya.</li>
        <li>Gumawa ng role-playing o simulation ng isang sitwasyon sa aralin.</li>
        <li>Ipaliwanag sa iba ang natutunan mo gamit ang sariling salita.</li>
        <li>Subukan ang mga educational games o interactive tools na may kaugnayan sa aralin.</li>
      </ul>',
    'exercises' => '
      <ol class="list-decimal ml-6 space-y-1 mb-4">
        <li>Maghanda ng 5 tanong para sa isang group discussion.</li>
        <li>I-record ang sarili habang nagtuturo ng konsepto.</li>
        <li>Ipaliwanag ang topic sa kaibigan o pamilya.</li>
      </ol>',
    'motivation' => '<p><em>Tip:</em> Mas maaalala mo ang aralin kapag ikaw mismo ang aktibo sa pagkatuto.</p>',
  ],
 'Reflective' => [
    'title' => 'Reflective',
    'goal' => 'Palalimin ang pag-unawa sa mga aralin sa pamamagitan ng tahimik na pagmumuni-muni, pagsusuri, at pagtatala ng mga natutunan. Layunin nito ang mas epektibong internalisasyon ng kaalaman.',
    'explanation' => '
      <p>Ang pagsasanay sa pagiging Reflective ay nagbibigay daan sa mas malalim na pag-unawa, dahil natututo kang suriin ang mga karanasan, matuto mula sa mga pagkakamali, at kilalanin ang sariling proseso ng pagkatuto. Mahalaga ito para sa mga aralin na nangangailangan ng masusing pag-iisip at personal na koneksyon, tulad ng pagsusulat, pananaliksik, at pag-unawa sa masalimuot na konsepto.</p>
      <ul class="list-disc ml-6 space-y-1">
        <li>Panatilihin ang isang learning journal para sa iyong mga natutunan araw-araw.</li>
        <li>Gumawa ng tahimik na oras bawat araw para mag-isip at suriin ang aralin.</li>
        <li>I-relate ang mga konsepto sa sariling karanasan o sitwasyon sa buhay.</li>
      </ul>',
    'exercises' => '
      <ol class="list-decimal ml-6 space-y-1 mb-4">
        <li>Isulat ang iyong reflection pagkatapos ng bawat aralin.</li>
        <li>Gumawa ng mind map ng natutunan mo.</li>
      </ol>',
    'motivation' => '<p><em>Tip:</em> Ang pagninilay ay nakakatulong upang mas tumagal sa memorya ang kaalaman.</p>',
  ],
  'Sensing' => [
    'title' => 'Sensing Learning',
    'goal' => 'Maging mas maingat sa mga detalye at praktikal na aplikasyon ng mga konsepto.',
    'explanation' => '
      <p>Kung hindi ka sanay sa sensing style, gawin mo ito:</p>
      <ul class="list-disc ml-6 space-y-1">
        <li>Mag-focus sa step-by-step na pagtuturo at detalyadong paliwanag.</li>
        <li>Gumamit ng konkretong halimbawa sa bawat topic.</li>
        <li>Mag-apply ng natutunan sa totoong buhay.</li>
        <li>Gumawa ng checklist o guide.</li>
      </ul>',
    'exercises' => '
      <ol class="list-decimal ml-6 space-y-1 mb-4">
        <li>Pag-aralan ang isang case study at i-highlight ang detalye.</li>
        <li>Gumawa ng flowchart ng isang proseso.</li>
        <li>Gamitin ang konsepto sa isang praktikal na sitwasyon.</li>
      </ol>',
    'motivation' => '<p><em>Tip:</em> Ang pagtuon sa detalye ay mahalaga sa mga tunay na aplikasyon ng natutunan.</p>',
  ],
  'Intuitive' => [
    'title' => 'Intuitive Learning',
    'goal' => 'Palawakin ang pag-iisip para makakita ng mas malalim na koneksyon ng mga konsepto.',
    'explanation' => '
      <p>Kung intuitive learning ang less dominant mo, subukan mong:</p>
      <ul class="list-disc ml-6 space-y-1">
        <li>Mag-brainstorming at magtanong ng “what if.”</li>
        <li>Mag-explore ng mga bagong ideya at posibilidad.</li>
        <li>Iugnay ang mga konsepto mula sa iba’t ibang aralin.</li>
        <li>Gumamit ng metaphors o analogies.</li>
      </ul>',
    'exercises' => '
      <ol class="list-decimal ml-6 space-y-1 mb-4">
        <li>Gumawa ng mind map para sa isang paksa.</li>
        <li>Sumulat ng essay tungkol sa future use ng isang konsepto.</li>
        <li>I-apply ang konsepto sa ibang disiplina.</li>
      </ol>',
    'motivation' => '<p><em>Tip:</em> Ang malikhain at malawak na pag-iisip ay nagbubukas ng maraming posibilidad sa pagkatuto.</p>',
  ],
  'Visual' => [
    'title' => 'Visual Learning',
    'goal' => 'Gumamit ng mga imahe, graphs, at diagrams para mas madali ang pag-intindi.',
    'explanation' => '
      <p>Kung hindi dominant ang visual learning mo, subukan mong:</p>
      <ul class="list-disc ml-6 space-y-1">
        <li>Gumuhit ng mga diagrams o concept maps.</li>
        <li>Gamitin ang kulay sa pag-highlight ng impormasyon.</li>
        <li>Manood ng educational videos.</li>
        <li>Gumamit ng flashcards o infographics.</li>
      </ul>',
    'exercises' => '
      <ol class="list-decimal ml-6 space-y-1 mb-4">
        <li>Gumawa ng concept map para sa isang aralin.</li>
        <li>Gamitin ang flashcards na may larawan at kulay.</li>
        <li>I-annotate ang notes gamit ang visual symbols.</li>
      </ol>',
    'motivation' => '<p><em>Tip:</em> Ang visual learning ay nakakatulong sa mabilisang pag-intindi at memorya.</p>',
  ],
  'Verbal' => [
    'title' => 'Verbal Learning',
    'goal' => 'Pahusayin ang pagkatuto sa pamamagitan ng pagbasa, pagsusulat, at pakikipag-usap.',
    'explanation' => '
      <p>Kung hindi ka verbal learner, subukan mong:</p>
      <ul class="list-disc ml-6 space-y-1">
        <li>Magbasa ng mga artikulo o aklat tungkol sa aralin.</li>
        <li>Sumulat ng notes, summaries, o blog tungkol sa paksa.</li>
        <li>Makipag-usap tungkol sa natutunan sa isang kaibigan.</li>
        <li>Mag-record ng sarili habang nag-eexplika ng konsepto.</li>
      </ul>',
    'exercises' => '
      <ol class="list-decimal ml-6 space-y-1 mb-4">
        <li>Sumulat ng buod ng isang lesson.</li>
        <li>Gumawa ng speech o tula tungkol sa aralin.</li>
        <li>Makipagdebate tungkol sa isang isyu na konektado sa topic.</li>
      </ol>',
    'motivation' => '<p><em>Tip:</em> Ang paggamit ng wika ay nakakatulong sa mas malalim na pag-unawa at pag-retain ng impormasyon.</p>',
  ],
  'Sequential' => [
    'title' => 'Sequential Learning',
    'goal' => 'Unawain ang mga konsepto sa isang sunod-sunod at lohikong paraan.',
    'explanation' => '
      <p>Kung hindi ka sanay sa sequential style, subukan mong:</p>
      <ul class="list-disc ml-6 space-y-1">
        <li>Ayusin ang mga aralin mula basic hanggang advanced.</li>
        <li>Gumawa ng timeline ng mga events o proseso.</li>
        <li>I-review ang mga steps bago mag-move sa susunod.</li>
        <li>Gumamit ng numbering o bulleted lists sa notes.</li>
      </ul>',
    'exercises' => '
      <ol class="list-decimal ml-6 space-y-1 mb-4">
        <li>Gumawa ng step-by-step guide ng isang topic.</li>
        <li>I-chronicle ang development ng isang konsepto.</li>
        <li>Gamitin ang outline format sa paggawa ng notes.</li>
      </ol>',
    'motivation' => '<p><em>Tip:</em> Ang pagkakasunod-sunod ng ideya ay nakakatulong sa mas malinaw na pag-unawa at organisasyon ng kaalaman.</p>',
  ],
  'Global' => [
    'title' => 'Global Learning',
    'goal' => 'Makita ang kabuuang larawan ng mga konsepto bago pumasok sa detalye.',
    'explanation' => '
      <p>Kung hindi ka sanay sa global learning, subukan mong:</p>
      <ul class="list-disc ml-6 space-y-1">
        <li>Alamin muna ang overview ng paksa bago ang detalye.</li>
        <li>Gamitin ang analogies o real-life stories para sa mas malawak na perspektibo.</li>
        <li>I-connect ang mga bagong aralin sa mga dating natutunan.</li>
        <li>Magbasa ng summary bago ang buong content.</li>
      </ul>',
    'exercises' => '
      <ol class="list-decimal ml-6 space-y-1 mb-4">
        <li>Gumawa ng summary diagram ng buong unit o lesson.</li>
        <li>Sumulat ng narrative kung paano konektado ang mga aralin.</li>
        <li>I-reflect kung paano makakatulong ang kabuuang konsepto sa buhay mo.</li>
      </ol>',
    'motivation' => '<p><em>Tip:</em> Kapag nauunawaan mo ang big picture, mas madali mong mailalapat ang detalye sa tamang konteksto.</p>',
  ],
];

?>
<main class="max-w-4xl mx-auto p-6">
<div class="relative bg-[#fdf8f4] border border-[#f0e8e1] rounded-3xl overflow-hidden shadow-xl mb-12 p-10 sm:p-14">
  <!-- Decorative Circles -->
  <div class="absolute -top-10 -left-10 w-40 h-40 bg-[#d79e4b] opacity-10 rounded-full z-0"></div>
  <div class="absolute -bottom-10 -right-10 w-52 h-52 bg-[#551a25] opacity-10 rounded-full z-0"></div>

  <!-- Content -->
  <div class="relative z-10">
    <div class="inline-block bg-[#d79e4b] text-[#551a25] text-sm font-semibold px-6 py-2 rounded-full shadow mb-5">
      <i class="fa-solid fa-lightbulb mr-2"></i>
      Para sa'yo ito!
    </div>
    <h1 class="text-4xl sm:text-5xl font-extrabold text-[#551a25] leading-snug tracking-wide mb-4">
      Mga Learning Style na Kailangan Mo Pang I-practice
    </h1>
    <p class="text-lg sm:text-xl text-[#4a2d2d] max-w-4xl">
      Ito ang mga learning style na hindi mo madalas gamitin. Pero hindi ibig sabihin mahina ka rito. Kaya mo itong pag-aralan at sanayin para mas gumaling ka sa pag-intindi at pag-aaral. Subukan mo ang mga exercise at tingnan kung ano ang matututunan mo!
    </p>
  </div>
</div>




<?php if (!empty($lessDominantStyles)): ?>
  <div class="mt-2 ">
    <?php foreach ($lessDominantStyles as $style): ?>
      <?php if (isset($lessons[$style])): ?>
        <section class="bg-[#fdf8f4] border-l-8 border-[#551a25] p-6 sm:p-8 rounded-2xl shadow-md hover:shadow-lg transition duration-300 mb-10">
          
          <!-- Header -->
         <div class="mb-4">
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
</div>


          <!-- Goal -->
          <p class="text-lg font-semibold text-[#551a25] mb-2">
            Goal: <span class="font-normal text-gray-800"><?= $lessons[$style]['goal'] ?></span>
          </p>

          <!-- Explanation -->
          <details class="bg-white rounded-xl p-5 border border-[#e0d6d0] hover:bg-[#fdf4e6] transition duration-200 shadow-inner">
            <summary class="cursor-pointer font-semibold text-[#551a25] hover:text-[#3d1018] select-none">
            Tips
            </summary>
            <div class="mt-3 text-gray-800 leading-relaxed">
              <?= $lessons[$style]['explanation'] ?>
            </div>
          </details>

          <!-- Motivation -->
          <details class="bg-white rounded-xl p-5 border border-[#e0d6d0] hover:bg-[#fdf4e6] transition duration-200 shadow-inner mt-4">
            <summary class="cursor-pointer font-semibold text-[#551a25] hover:text-[#3d1018] select-none">
              Motivation
            </summary>
            <div class="mt-3 italic text-gray-700 leading-relaxed">
              <?= $lessons[$style]['motivation'] ?>
            </div>
          </details>
        </section>
      <?php endif; ?>
    <?php endforeach; ?>

    <div class="text-center">
     <button id="takeExerciseBtn" class="bg-[#551a25] hover:bg-[#3d1018] text-white font-semibold px-10 py-3 rounded-full shadow-lg text-lg transition-all duration-200 hover:scale-105">
  Take Exercise
</button>

  </div>
<?php endif; ?>
<!-- Modal -->
<div id="exerciseModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
  <div class="bg-white w-full max-w-4xl h-[90vh] rounded-2xl shadow-lg relative overflow-hidden flex flex-col">
    <!-- Timer bar -->
    <div class="flex items-center justify-between bg-[#551a25] text-[#f0e8e1] px-6 py-3 rounded-t-2xl select-none">
      <div class="text-lg font-semibold">Time Left:</div>
      <div id="timer" class="text-lg font-mono tracking-wide">10:00</div>
    </div>
    <!-- iframe -->
    <iframe id="quizIframe" src="quiz.php" class="flex-grow w-full border-0 rounded-b-2xl"></iframe>
  </div>
</div>
</main>
        
<footer class="bg-[#551a25] text-white pt-12 pb-6 border-t-2 border-[#d79e4b]">
          <div class="max-w-6xl mx-auto px-6 grid grid-cols-1 md:grid-cols-3 gap-12 text-center md:text-left">
            <div>
              <div class="flex justify-center md:justify-start items-center gap-3 mb-6">
                <img src="../../images/logo.png" alt="DIGIstorya Logo" class="h-12 " />
                <span class="text-secondary text-3xl font-extrabold tracking-wide">DIGIstorya</span>
              </div> 
            </div>
            <div>
              <h3 class="text-secondary font-semibold text-lg mb-6 tracking-wide">Quick Links</h3>
              <ul class="space-y-2 text-sm">
                <li><a href="home.php" class="hover:underline hover:text-secondary transition duration-300">Home</a></li>
                <li><a href="about.html" class="hover:underline hover:text-secondary transition duration-300">About</a></li>
                <li><a href="more.html" class="hover:underline hover:text-secondary transition duration-300">More</a></li>
                <li><a href="activities.php" class="hover:underline hover:text-secondary transition duration-300">Activites</a></li>
                <li><a href="exercise.php" class="hover:underline hover:text-secondary transition duration-300">Exercises</a></li>
              </ul>
            </div>
            <div>
              <h3 class="text-secondary font-semibold text-lg mb-6 tracking-wide">Contact Us</h3>
              <p class="text-sm text-gray-200 opacity-80 mb-3 flex items-center gap-2">
                <i class="fas fa-envelope text-secondary"></i>
                Email: <a href="mailto:digistorya@gmail.com" class=" hover:text-secondary transition duration-300">digistorya@gmail.com</a>
              </p>
              <p class="text-sm text-gray-200 opacity-80 flex items-center gap-2">
                <i class="fab fa-facebook text-secondary"></i>
                Facebook: <a href="#" class=" hover:text-secondary transition duration-300">facebook.com/digistorya</a>
              </p>
            </div>
          </div>
          <div class="mt-12 border-t border-white/20 pt-4 text-sm text-center text-gray-300">
            &copy; 2025 <span class="font-semibold">DIGIstorya</span>. All rights reserved.
          </div>
</footer>

  <script>
    document.getElementById('menu-toggle').addEventListener('click', function () {
      const menu = document.getElementById('mobile-menu');
      menu.classList.toggle('hidden');
    });
  </script>
  <script>
    document.getElementById('takeExerciseBtn').addEventListener('click', function() {
    if (!this.disabled) {
        openModal();
    }
});

   // Timer code (same as before) ...
  let timeLeft = 600; // 10 minutes in seconds
  let timerInterval;

  function startTimer() {
    const timerEl = document.getElementById('timer');
    timerInterval = setInterval(() => {
      let minutes = Math.floor(timeLeft / 60);
      let seconds = timeLeft % 60;
      if (seconds < 10) seconds = '0' + seconds;
      timerEl.textContent = `${minutes}:${seconds}`;

      if (timeLeft <= 0) {
        clearInterval(timerInterval);
        alert('Tapos na ang oras mo sa quiz!');
        // You can add extra logic here (auto-submit quiz)
        closeModal();
      }

      timeLeft--;
    }, 1000);
  }

  function openModal() {
    const modal = document.getElementById('exerciseModal');
    modal.classList.remove('hidden');
    timeLeft = 600;
    startTimer();
  }

 function closeModal() {
  const modal = document.getElementById('exerciseModal');
  modal.classList.add('hidden');
  clearInterval(timerInterval); // Stop the timer
}

 window.addEventListener('message', function(event) {
  if (event.data.action === 'quizSubmitted') {
    closeModal(); // isara ang modal
    Swal.fire({
      icon: 'success',
      title: 'Tagumpay!',
      text: 'Natapos mo na ang quiz. Salamat sa iyong pagsagot!',
      confirmButtonColor: '#551a25'
    });
  }
});

document.addEventListener('visibilitychange', function() {
  const modal = document.getElementById('exerciseModal');
  // Check if the modal is open
  if (!modal.classList.contains('hidden')) {
    // If the user switches tabs or minimizes (page becomes hidden)
    if (document.hidden) {
      clearInterval(timerInterval); // Stop the timer
      Swal.fire({
        icon: 'error',
        title: 'Alt-Tab Not Allowed!',
        text: 'You cannot switch tabs while taking the exercise!',
        confirmButtonColor: '#551a25'
      }).then(() => {
        closeModal(); // Close the exercise modal
      });
    }
  }
});

</script>

</body>
</html>
