<?php
session_start();

// Include the database connection
require_once 'db_connection.php';  // Adjust the path to your actual folder structure

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Fetch user info with section name using MySQLi
$sql = "
    SELECT users.firstname, users.lastname, users.email, users.gender, section.section_name
    FROM users
    JOIN section ON users.section_id = section.id
    WHERE users.id = ?
";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($result);

// Fetch latest quiz result using MySQLi
$sql_quiz = "
    SELECT active_reflective, sensing_intuitive, visual_verbal, sequential_global, created_at
    FROM quiz_results
    WHERE user_id = ?
    ORDER BY created_at DESC
    LIMIT 1
";
$stmt_quiz = mysqli_prepare($conn, $sql_quiz);
mysqli_stmt_bind_param($stmt_quiz, "i", $user_id);
mysqli_stmt_execute($stmt_quiz);
$result_quiz = mysqli_stmt_get_result($stmt_quiz);
$latest_quiz = mysqli_fetch_assoc($result_quiz);



?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>DIGIstorya</title>
  <link rel="icon" href="../../images/logo.png" />
  <script src="https://kit.fontawesome.com/aed89df169.js" crossorigin="anonymous"></script>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet" />
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet" />
  <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
  <style>
    body {
      font-family: 'Poppins', sans-serif;
    }
    .section-heading {
      font-size: 2.5rem;
      font-weight: 600;
      color: #551a25;
      margin-bottom: 1rem;
    }
    .section-subheading {
      font-size: 1.125rem;
      font-weight: 500;
      color: #d79e4b;
      margin-bottom: 0.5rem;
    }
    .card {
      background-color: #d79e4b;
      border-radius: 0.5rem;
      padding: 1.5rem;
      box-shadow: 0px 10px 20px rgba(0, 0, 0, 0.1);
      margin-bottom: 1.5rem;
    }
    .card-header {
      font-size: 1.25rem;
      font-weight: 600;
      color: #551a25;
    }
    .card-body {
      font-size: 1.125rem;
      color: #551a25;
      font-weight: 500;
    }
    .card-footer {
      font-size: 1rem;
      color: #f0e8e1;
      font-weight: 400;
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
<main class="min-h-screen flex items-center justify-center p-10 bg-gradient-to-br from-[#f7f2ef] to-[#fbeee6]">
  <div class="w-full max-w-6xl grid grid-cols-1 md:grid-cols-2 gap-10">
    
    <!-- Profile Info -->
    <div class="bg-white rounded-2xl shadow-lg border border-[#ecd7cc] p-8">
    <h2 class="text-3xl font-bold text-[#7b2d3b] mb-6 flex items-center gap-3">
  <?php if (strtolower($user['gender']) === 'male'): ?>
    <i class="fa-solid fa-mars text-blue-600 text-4xl"></i>
  <?php elseif (strtolower($user['gender']) === 'female'): ?>
    <i class="fa-solid fa-venus text-pink-600 text-4xl"></i>
  <?php endif; ?>
  Student Profile
</h2>

      
      <div class="mb-4">
        <p class="text-gray-600 text-sm uppercase tracking-wide">Full Name</p>
        <p class="text-xl text-[#551a25] font-semibold"><?= htmlspecialchars($user['firstname'] . ' ' . $user['lastname']) ?></p>
      </div>

      <div class="mb-4">
        <p class="text-gray-600 text-sm uppercase tracking-wide">Email</p>
        <p class="text-xl text-[#551a25] font-semibold"><?= htmlspecialchars($user['email']) ?></p>
      </div>

      <div>
        <p class="text-gray-600 text-sm uppercase tracking-wide">Section</p>
        <p class="text-xl text-[#551a25] font-semibold"><?= htmlspecialchars($user['section_name']) ?></p>
      </div>


    </div>

    <!-- Learning Styles -->
    <div class="bg-white rounded-2xl shadow-lg border border-[#ecd7cc] p-8">
      <h2 class="text-3xl font-bold text-center text-[#7b2d3b] mb-6">🧠 Learning Styles</h2>

      <?php if ($latest_quiz): ?>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div class="bg-[#fff3f0] p-4 rounded-xl border border-[#f0d6cc] hover:shadow-md transition">
            <p class="text-sm text-gray-500">Active vs Reflective</p>
            <p class="text-lg font-bold text-[#6e1f2a]"><?= htmlspecialchars($latest_quiz['active_reflective']) ?></p>
          </div>
          <div class="bg-[#fff3f0] p-4 rounded-xl border border-[#f0d6cc] hover:shadow-md transition">
            <p class="text-sm text-gray-500">Sensing vs Intuitive</p>
            <p class="text-lg font-bold text-[#6e1f2a]"><?= htmlspecialchars($latest_quiz['sensing_intuitive']) ?></p>
          </div>
          <div class="bg-[#fff3f0] p-4 rounded-xl border border-[#f0d6cc] hover:shadow-md transition">
            <p class="text-sm text-gray-500">Visual vs Verbal</p>
            <p class="text-lg font-bold text-[#6e1f2a]"><?= htmlspecialchars($latest_quiz['visual_verbal']) ?></p>
          </div>
          <div class="bg-[#fff3f0] p-4 rounded-xl border border-[#f0d6cc] hover:shadow-md transition">
            <p class="text-sm text-gray-500">Sequential vs Global</p>
            <p class="text-lg font-bold text-[#6e1f2a]"><?= htmlspecialchars($latest_quiz['sequential_global']) ?></p>
          </div>
        </div>

        <div class="mt-6 text-center text-sm text-gray-500 italic">
          📅 Taken on <?= date('F j, Y g:i A', strtotime($latest_quiz['created_at'])) ?>
        </div>
      <?php else: ?>
        <div class="text-center text-xl text-[#7b2d3b]">No results available.</div>
      <?php endif; ?>
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
          <li><a href="activites.php" class="hover:underline hover:text-secondary transition duration-300">Activities</a></li>
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
    // Mobile menu toggle
    document.getElementById("menu-toggle").addEventListener("click", () => {
      document.getElementById("mobile-menu").classList.toggle("hidden");
    });
  </script>
<script>
(function(){if(!window.chatbase||window.chatbase("getState")!=="initialized"){window.chatbase=(...arguments)=>{if(!window.chatbase.q){window.chatbase.q=[]}window.chatbase.q.push(arguments)};window.chatbase=new Proxy(window.chatbase,{get(target,prop){if(prop==="q"){return target.q}return(...args)=>target(prop,...args)}})}const onLoad=function(){const script=document.createElement("script");script.src="https://www.chatbase.co/embed.min.js";script.id="mHgpNSB7Zpe_aU0HLJ3i9";script.domain="www.chatbase.co";document.body.appendChild(script)};if(document.readyState==="complete"){onLoad()}else{window.addEventListener("load",onLoad)}})();
</script>
</body>

</html>
