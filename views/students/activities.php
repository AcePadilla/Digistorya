<?php
session_start();
require_once 'db_connection.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Kunin user info kasama section
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

// Kunin ang mga activities na wala pang submission ng user (i.e. hindi pa niya tinake)
$activities_sql = "
   SELECT a.id, a.question_text, a.question_type, a.choices, a.title
   FROM activities a
   WHERE a.section_id = ?
   AND NOT EXISTS (
       SELECT 1 FROM activity_submissions s 
       WHERE s.user_id = ? AND s.activity_id = a.id
   )
   ORDER BY a.title, a.created_at
";

$act_stmt = mysqli_prepare($conn, $activities_sql);
if (!$act_stmt) {
    die("Prepare failed (activities_sql): " . mysqli_error($conn));
}
mysqli_stmt_bind_param($act_stmt, "ii", $section_id, $user_id);
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
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

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

<body class="text-gray-800">
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

<div class="p-10 max-w-5xl mx-auto  rounded-3xl">
  <div
  class="max-w-5xl mx-auto  rounded-3xl  mb-16 select-text"
>
  <h2
    class="text-4xl font-extrabold mb-6 text-[#551a25] border-b-4 border-[#d79e4b] pb-3 tracking-wide select-none"
  >
    Available Activities
  </h2>

  <p
    class="text-[#551a25cc] text-xl leading-relaxed text-justify font-medium"
  >
    Participating in these activities is essential for reinforcing your understanding of key concepts.
    They provide a hands-on opportunity to apply what you've learned, improve critical thinking skills,
    and prepare you for real-world challenges. Engaging regularly will help boost your confidence and
    ensure deeper retention of the material.
  </p>
</div>


  <?php if (empty($activities_by_title)): ?>
    <p class="text-center text-[#551a25] italic text-xl mt-24">No activities available for your section.</p>
  <?php else: ?>
    <div class="grid gap-10 sm:grid-cols-2">
      <?php foreach ($activities_by_title as $title => $activities): ?>
        <div
          class="flex flex-col justify-between bg-white rounded-3xl border-2 border-[#d79e4b] shadow-md
                 hover:shadow-lg hover:scale-[1.03] transition-transform duration-300 p-8 cursor-pointer"
          tabindex="0"
          onkeydown="if(event.key==='Enter') openIframe(<?php echo $activities[0]['id']; ?>)"
          onclick="openIframe(<?php echo $activities[0]['id']; ?>)"
          role="button"
          aria-label="Open activity <?php echo htmlspecialchars($title); ?>"
        >
          <div>
            <p class="font-semibold text-3xl mb-8 text-[#551a25] leading-tight tracking-wide select-text">
              <?php echo htmlspecialchars($title); ?>
            </p>
            <p class="text-[#551a2599] text-lg leading-relaxed mb-10">
              Engage with an interactive activity to enhance your understanding and skills.
            </p>
          </div>
          <button
            onclick="event.stopPropagation(); openIframe(<?php echo $activities[0]['id']; ?>)"
            class="bg-[#551a25] ] active:scale-95 text-white font-semibold rounded-full px-10 py-3
                   focus:outline-none focus:ring-4 focus:ring-[#d79e4b]/60 transition"
          >
            Take Activity
          </button>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>


<!-- Modal -->
<div id="modalOverlay" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
  <div class="bg-white rounded-lg shadow-lg w-full max-w-4xl relative flex flex-col" style="height: 650px;">

    <!-- Timer Bar -->
    <div class="flex items-center justify-between bg-[#551a25] text-[#f0e8e1] px-6 py-3 rounded-t-2xl select-none z-10">
      <div class="text-lg font-semibold">Time Left:</div>
      <div id="timer" class="text-lg font-mono tracking-wide">10:00</div>
    </div>
<button 
  onclick="closeIframeModal()" 
  class="absolute top-2 right-1 text-red-600 hover:text-red-800 text-3xl font-bold z-10 focus:outline-none" 
  aria-label="Close Modal"
>
  &times;
</button>
    <!-- Iframe -->
    <iframe
      id="activityIframe"
      src=""
      class="flex-grow w-full rounded-b-lg border-0"
      style="height: 100%;"
      sandbox="allow-scripts allow-same-origin allow-forms"
    ></iframe>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
  let timeLeft = 600; // 10 minutes in seconds
  let timerInterval;
  let alertShown = false; // To prevent multiple alerts on tab switch

  function startTimer() {
    const timerEl = document.getElementById('timer');
    timerInterval = setInterval(() => {
      let minutes = Math.floor(timeLeft / 60);
      let seconds = timeLeft % 60;
      if (seconds < 10) seconds = '0' + seconds;
      timerEl.textContent = `${minutes}:${seconds}`;

      if (timeLeft <= 0) {
        clearInterval(timerInterval);
        Swal.fire({
          icon: 'info',
          title: 'Time is up!',
          text: 'Your time for this activity has ended.',
          confirmButtonColor: '#551a25'
        }).then(() => {
          closeIframeModal();
        });
      }
      timeLeft--;
    }, 1000);
  }

  function openIframe(activityId) {
    const iframe = document.getElementById('activityIframe');
    const modal = document.getElementById('modalOverlay');

    iframe.src = 'iframeactivities.php?activity_id=' + activityId;
    modal.classList.remove('hidden');

    timeLeft = 600; // reset timer
    alertShown = false;
    startTimer();
  }

function closeIframeModal() {
  document.getElementById('modalOverlay').classList.add('hidden');
  const iframe = document.getElementById('activityIframe');
  iframe.src = ''; // clear the iframe
  clearInterval(timerInterval); // stop the timer
  timeLeft = 600; // reset timer
  alertShown = false; // reset alert flag
}

  // Detect tab/window switching
  document.addEventListener('visibilitychange', function() {
    const modal = document.getElementById('modalOverlay');
    if (!modal.classList.contains('hidden') && !alertShown) {
      if (document.hidden) {
        clearInterval(timerInterval);
        alertShown = true;
        Swal.fire({
          icon: 'error',
          title: 'Switching Tabs Not Allowed',
          text: 'You cannot switch tabs or minimize the browser while taking this activity.',
          confirmButtonColor: '#551a25',
          allowOutsideClick: false
        }).then(() => {
          closeIframeModal();
        });
      }
    }
  });



</script>




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


</body>
</html>
