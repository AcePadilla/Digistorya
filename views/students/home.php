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

    @keyframes slide {
      0% {
        transform: translateX(0);
      }
      33% {
        transform: translateX(-100%);
      }
      66% {
        transform: translateX(-200%);
      }
      100% {
        transform: translateX(0);
      }
    }

    .animate-slide {
      animation: slide 15s infinite ease-in-out;
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



 <main>
    <section class="py-20 px-6">
      <div class="max-w-7xl mx-auto flex flex-col lg:flex-row items-center justify-between space-y-10 lg:space-y-0 lg:space-x-12">
        

        <div class="lg:w-1/2 xl:w-1/3 drop-shadow-2xl">
        <img src="../../images/Des.png" alt="Descriptive Image" class="w-full h-auto animate-fade-in" />
        </div>

        <div class="flex-1 text-center lg:text-left space-y-6 animate-fade-in">
          <h1 class="text-4xl xl:text-5xl font-extrabold leading-tight tracking-tight">
            <span class="text-[#551a25]">DIGIstorya is a digital platform for modern learning. </span><br>
            <span class="text-[#d79e4b]">Aims to make learning in all subjects clearer, more interactive, and more meaningful.</span>
          </h1>
          <p class="text-lg text-justify leading-relaxed">
            Discover. Experience. Learn—in a way that works for you.
          </p>
        </div>
      </div>
    </section>


   <section class="bg-[#fffaf5] py-20">
  <div class="max-w-7xl mx-auto px-6 grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
    
    <!-- TEXT CONTENT -->
    <div class="space-y-6 animate-fade-in">
      <h2 class="text-4xl lg:text-5xl font-extrabold text-[#551a25] border-l-8 border-[#d79e4b] pl-4">
        Learning Style Dimensions
      </h2>
      <p class="text-lg lg:text-xl text-[#551a25] opacity-90 leading-relaxed text-justify">
      The <strong>Felder-Silverman Learning Styles Model</strong> is a theory that explains how each student learns in different ways. According to this model, there are four main dimensions of learning: <em>Active-Reflective</em>, <em>Sensing-Intuitive</em>, <em>Visual-Verbal</em>, and <em>Sequential-Global</em>.
      </p>
    </div>

    <!-- VIDEO EMBED -->
    <div class="w-full animate-fade-in delay-200">
      <div class="w-full aspect-video rounded-2xl overflow-hidden shadow-xl">
        <iframe
          class="w-full h-full"
          src="https://www.youtube.com/embed/QEkj9qMyAVQ"
          title="Felder-Silverman Explanation Video"
          frameborder="0"
          allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
          allowfullscreen>
        </iframe>
      </div>
    </div>

  </div>
</section>

</main>

</body>


<section id="latest-lessons" data-aos="fade-up" data-aos-duration="1000" >
<div class="flex items-center justify-evenly bg-[#551a25] p-6">
  <!--<img src="../../images/turi.png" alt="Left Icon" class="w-64 h-64 object-contain drop-shadow-2xl hover:scale-105 transition-transform duration-300">-->
  
  <h1 class="text-2xl font-extrabold text-[#d79e4b]  drop-shadow-md">POWERPOINT PRESENTATIONS</h1>
  
  <!--<img src="../../images/imissu.png" alt="Right Icon" class="w-64 h-64 object-contain drop-shadow-2xl hover:scale-105 transition-transform duration-300">-->
</div>

<div class="grid md:grid-cols-2 lg:grid-cols-3 gap-12 max-w-7xl mx-auto px-4 my-8" data-aos="fade-up" data-aos-delay="100">
    <?php
    include 'db_connection.php';
    $query = "SELECT * FROM presentations WHERE file_type = 'PPT' ORDER BY uploaded_at DESC LIMIT 6";
    $result = mysqli_query($conn, $query);

    if (!$result) {
        die("Query failed: " . mysqli_error($conn));
    }

    if (mysqli_num_rows($result) > 0) {
      while ($row = mysqli_fetch_assoc($result)) {
        $title = htmlspecialchars($row['title']);
        $file_link = '../../assets/uploads/' . htmlspecialchars($row['file']);
        $image_path = '../../assets/images/' . htmlspecialchars($row['image']);
    ?>
      <div class="shadow-md hover:shadow-2xl transition-all duration-300 overflow-hidden group bg-[#fffaf5]">
        <a a href="view.php?id=<?= $row['id'] ?>" target="_blank" class="block relative">
          <img src="<?= $image_path ?>" alt="Lesson Thumbnail" class="w-full  object-cover group-hover:scale-105 transition-transform duration-300">
          <div class="absolute inset-0 bg-gradient-to-t from-black/60 to-transparent opacity-60 group-hover:opacity-40 transition-opacity duration-300"></div>
        </a>
        <div class="p-5">
          <h3 class="text-xl text-center font-semibold text-[#551a25] mb-1"><?= $title ?></h3>
        </div>
      </div>
    <?php
      }
    } else {
      echo "<p class='col-span-3 text-center text-gray-500'>No PPT lessons uploaded yet.</p>";
    }
    ?>
  </div>

  <!-- PDF Lessons -->
  <div class="flex items-center justify-evenly bg-[#551a25] p-6">
 
  <h1 class="text-2xl font-extrabold text-[#d79e4b]  drop-shadow-md">EDUCATIONAL LEARNING MATERIALS</h1>
  

</div>
<div class="grid md:grid-cols-2 lg:grid-cols-3 gap-12 max-w-7xl mx-auto px-4 mt-8" data-aos="fade-up" data-aos-delay="100">
    <?php
    $query = "SELECT * FROM presentations WHERE file_type = 'PDF' ORDER BY uploaded_at DESC LIMIT 6";
    $result = mysqli_query($conn, $query);

    if (!$result) {
        die("Query failed: " . mysqli_error($conn));
    }

    if (mysqli_num_rows($result) > 0) {
      while ($row = mysqli_fetch_assoc($result)) {
        $title = htmlspecialchars($row['title']);
        $file_link = '../../assets/uploads/' . htmlspecialchars($row['file']);
        $image_path = '../../assets/images/' . htmlspecialchars($row['image']);
    ?>
      <div class=" shadow-md hover:shadow-2xl transition-all duration-300 overflow-hidden group bg-[#fffaf5]">
        <a a href="view.php?id=<?= $row['id'] ?>" target="_blank" class="block relative">
          <img src="<?= $image_path ?>" alt="Lesson Thumbnail" class="w-full  object-cover group-hover:scale-105 transition-transform duration-300">
          <div class="absolute inset-0 bg-gradient-to-t from-black/60 to-transparent opacity-60 group-hover:opacity-40 transition-opacity duration-300"></div>
        </a>
        <div class="p-5">
          <h3 class="text-xl text-center font-semibold text-[#551a25] mb-1"><?= $title ?></h3>
        </div>
      </div>
    <?php
      }
    } else {
      echo "<p class='col-span-3 text-center text-gray-500'>No PDF lessons uploaded yet.</p>";
    }
    ?>
  </div>
</section>  
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

const fadeTextElements = document.querySelectorAll('.animate-fade-in');

const heroImage = document.querySelector('img[src*="Des.png"]');

const observerOptions = {
  threshold: 0.1,
  rootMargin: '0px 0px -30px 0px'
};

const textObserver = new IntersectionObserver((entries, observer) => {
  entries.forEach(entry => {
    if (entry.isIntersecting) {
      entry.target.style.opacity = 1;
      entry.target.style.transform = 'translateY(0)';
      observer.unobserve(entry.target);
    }
  });
}, observerOptions);

fadeTextElements.forEach(el => {
  el.style.opacity = 0;
  el.style.transform = 'translateY(40px)';
  el.style.transition = 'opacity 0.8s ease-out, transform 0.8s ease-out';
  textObserver.observe(el);
});

const imageObserver = new IntersectionObserver((entries, observer) => {
  entries.forEach(entry => {
    if (entry.isIntersecting) {
      entry.target.style.opacity = 1;
      entry.target.style.transform = 'rotate(0deg) scale(1)';
      observer.unobserve(entry.target);
    }
  });
}, observerOptions);

if (heroImage) {
  heroImage.style.opacity = 0;
  heroImage.style.transform = 'rotate(-3deg) scale(0.95)';
  heroImage.style.transition = 'opacity 1s ease, transform 1s ease';
  imageObserver.observe(heroImage);
}

let currentSlide = 0;
const slides = document.querySelectorAll('.slide');

function showSlide(index) {
  slides.forEach((slide, i) => {
    slide.style.opacity = i === index ? '1' : '0';
    slide.style.zIndex = i === index ? '1' : '0';
  });
}

function nextSlide() {
  currentSlide = (currentSlide + 1) % slides.length;
  showSlide(currentSlide);
}

function prevSlide() {
  currentSlide = (currentSlide - 1 + slides.length) % slides.length;
  showSlide(currentSlide);
}

showSlide(currentSlide);

const toggleBtn = document.getElementById('menu-toggle');
    const mobileMenu = document.getElementById('mobile-menu');

    toggleBtn.addEventListener('click', () => {
      mobileMenu.classList.toggle('hidden');
    });

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

</script>
<script>
(function(){if(!window.chatbase||window.chatbase("getState")!=="initialized"){window.chatbase=(...arguments)=>{if(!window.chatbase.q){window.chatbase.q=[]}window.chatbase.q.push(arguments)};window.chatbase=new Proxy(window.chatbase,{get(target,prop){if(prop==="q"){return target.q}return(...args)=>target(prop,...args)}})}const onLoad=function(){const script=document.createElement("script");script.src="https://www.chatbase.co/embed.min.js";script.id="mHgpNSB7Zpe_aU0HLJ3i9";script.domain="www.chatbase.co";document.body.appendChild(script)};if(document.readyState==="complete"){onLoad()}else{window.addEventListener("load",onLoad)}})();
</script>
</body>
</html>
