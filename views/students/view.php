<?php
include "db_connection.php";
function getGoogleDriveViewLink($url) {

    preg_match('/(?:drive|docs)\.google\.com.*d\/([a-zA-Z0-9_-]+).*$/', $url, $matches);
    if (isset($matches[1])) {
        return "https://drive.google.com/file/d/" . $matches[1] . "/preview";
    } else {
        return ''; 
    }
}

if (isset($_GET['id'])) {
    $id = $_GET['id'];
    $stmt = $conn->prepare("SELECT * FROM presentations WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $file = $result->fetch_assoc();

    if (!$file) {
        echo "File not found.";
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>DIGIstorya</title>
  <link rel="icon" href="../../images/logo.png">
  <script src="https://kit.fontawesome.com/aed89df169.js" crossorigin="anonymous"></script>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
  <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
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
    </div>
  </header>




  <div class="max-w-5xl mx-auto mt-10 bg-white p-8 rounded-2xl shadow-2xl transition-all duration-300">
    <h2 class="text-4xl font-extrabold mb-8 text-[#551a25] tracking-wide"><?= htmlspecialchars($file['title']) ?></h2>

    <div class="rounded-lg overflow-hidden border border-gray-300 shadow-md mb-6">
        <iframe 
            src="<?= getGoogleDriveViewLink($file['file']) ?>" 
            width="100%" 
            height="600" 
            frameborder="0"
            class="w-full"
        ></iframe>
    </div>

    <div class="flex justify-end">
        <a href="home.php" class="px-6 py-3 bg-[#551a25] text-white font-semibold rounded-xl shadow hover:bg-[#6e2b3a] transition duration-300">
            ← Back to List
        </a>
    </div>
</div>

</body>
<footer class="bg-[#551a25] text-white pt-12 pb-6 border-t-2 border-[#d79e4b]">
            <div class="max-w-6xl mx-auto px-6 grid grid-cols-1 md:grid-cols-3 gap-12 text-center md:text-left">

              <div>
                <div class="flex justify-center md:justify-start items-center gap-3 mb-6">
                  <img src="../../images/logo.png" alt="DIGIstorya Logo" class="h-12 " />
                  <span class="text-secondary text-3xl font-extrabold tracking-wide">DIGIstorya</span>
                </div>
                <p class="text-sm text-gray-200 leading-relaxed opacity-80">
                Tuklasin ang Kolonyalismo at Imperyalismo sa Timog-Silangang Asya!
                </p>
              </div>

              <div>
                <h3 class="text-secondary font-semibold text-lg mb-6 tracking-wide">Quick Links</h3>
                <ul class="space-y-2 text-sm">
                  <li><a href="home.html" class="hover:underline hover:text-secondary transition duration-300">Home</a></li>
                  <li><a href="about.html" class="hover:underline hover:text-secondary transition duration-300">About</a></li>
                  <li><a href="FAQ.html" class="hover:underline hover:text-secondary transition duration-300">FAQ</a></li>
                  <li><a href="activities.php" class="hover:underline hover:text-secondary transition duration-300">Activites</a></li>
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
    const toggleBtn = document.getElementById('menu-toggle');
    const mobileMenu = document.getElementById('mobile-menu');

    toggleBtn.addEventListener('click', () => {
      mobileMenu.classList.toggle('hidden');
    });
</script>
<script>
(function(){if(!window.chatbase||window.chatbase("getState")!=="initialized"){window.chatbase=(...arguments)=>{if(!window.chatbase.q){window.chatbase.q=[]}window.chatbase.q.push(arguments)};window.chatbase=new Proxy(window.chatbase,{get(target,prop){if(prop==="q"){return target.q}return(...args)=>target(prop,...args)}})}const onLoad=function(){const script=document.createElement("script");script.src="https://www.chatbase.co/embed.min.js";script.id="wcMihxu_8x23a9qItackv";script.domain="www.chatbase.co";document.body.appendChild(script)};if(document.readyState==="complete"){onLoad()}else{window.addEventListener("load",onLoad)}})();
</script>
</html>
