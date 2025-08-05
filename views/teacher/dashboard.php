<?php  
session_start();
require_once 'db_connection.php';

if (!isset($_SESSION['teacher_email'])) {
    header("Location: login.php");
    exit();
}

$email = $_SESSION['teacher_email'];

// Fetch teacher info (id and firstname)
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

// Fetch sections assigned to this teacher
$stmt = $conn->prepare("
    SELECT s.id, s.section_name
    FROM section s
    JOIN teacher_section ts ON s.id = ts.section_id
    WHERE ts.teacher_id = ?
");
$stmt->bind_param("i", $teacher_id);
$stmt->execute();
$sections_result = $stmt->get_result();

$sections = [];
while ($section = $sections_result->fetch_assoc()) {
    $sections[] = $section;
}

$section_ids = array_column($sections, 'id');
$placeholders = implode(',', array_fill(0, count($section_ids), '?'));
$types = str_repeat('i', count($section_ids));

// =========== Creative Data Analysis ==============

// 1. Student engagement per section: average quizzes taken per student
$engagement_data = [];
foreach ($sections as $section) {
    $section_id = $section['id'];

    // Count students in section
    $stmt = $conn->prepare("SELECT COUNT(*) as student_count FROM users WHERE section_id = ?");
    $stmt->bind_param("i", $section_id);
    $stmt->execute();
    $student_count = $stmt->get_result()->fetch_assoc()['student_count'];

    // Count quizzes taken by students in section
    $stmt = $conn->prepare("
        SELECT COUNT(qs.id) AS total_quizzes
        FROM quiz_submissions qs
        JOIN users u ON qs.user_id = u.id
        WHERE u.section_id = ?
    ");
    $stmt->bind_param("i", $section_id);
    $stmt->execute();
    $total_quizzes = $stmt->get_result()->fetch_assoc()['total_quizzes'];

    $avg_quizzes_per_student = $student_count > 0 ? round($total_quizzes / $student_count, 2) : 0;

    $engagement_data[] = [
        'section_name' => $section['section_name'],
        'student_count' => $student_count,
        'avg_quizzes_per_student' => $avg_quizzes_per_student,
    ];
}

$top_performers = [];
foreach ($sections as $section) {
    $section_id = $section['id'];
    if ($section_id) {
        // Get latest quiz submission date for the section
$date_sql = "
    SELECT MAX(qs.submission_date) as latest_date
    FROM quiz_submissions qs
    JOIN users u ON qs.user_id = u.id
    WHERE u.section_id = ?
";
$date_stmt = $conn->prepare($date_sql);
$date_stmt->bind_param("i", $section_id);
$date_stmt->execute();
$date_result = $date_stmt->get_result();
$latest_date = null;
if ($date_result && $date_result->num_rows > 0) {
    $latest_date = $date_result->fetch_assoc()['latest_date'];
}

if ($latest_date) {
    $sql = "
        SELECT u.firstname, u.lastname, qs.score
        FROM quiz_submissions qs
        JOIN users u ON qs.user_id = u.id
        WHERE u.section_id = ?
        AND qs.submission_date = ?
        ORDER BY qs.score DESC
        LIMIT 3
    ";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("is", $section_id, $latest_date);
    $stmt->execute();
    $performers = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
} else {
    // No submissions found
    $performers = [];
}

$top_performers[$section['section_name']] = $performers;

    }
}

// 5. Recent comments from quiz results for students in teacher's sections
if (count($section_ids) > 0) {
    $sql_comments = "
        SELECT qr.comments, u.firstname, u.lastname, qr.created_at
        FROM quiz_results qr
        JOIN users u ON qr.user_id = u.id
        WHERE u.section_id IN ($placeholders)
        AND qr.comments IS NOT NULL AND qr.comments != ''
        ORDER BY qr.created_at DESC
        LIMIT 5
    ";
    $stmt = $conn->prepare($sql_comments);
    $stmt->bind_param($types, ...$section_ids);
    $stmt->execute();
    $recent_comments = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
} else {
    $recent_comments = [];
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

    .sidebar {
      transition: width 0.3s ease;
    }

    .sidebar.collapsed {
      width: 4.5rem;
    }

    .sidebar.collapsed .nav-text {
      display: none;
      
    }
    .sidebar.collapsed .logo {
  display: flex;
  justify-content: center;
  align-items: center;
  height: 6.5rem;
  padding: 0.5rem 0;
  overflow: visible;
}

.sidebar.collapsed .logo img {
  width: 3rem;
  height: 3rem;
  object-fit: cover;
  border-width: 2px;
  border-radius: 9999px;
}

.logo-mini {
  display: none;
}

.sidebar.collapsed .logo-full {
  display: none;
}

.sidebar.collapsed .logo-mini {
  display: block;
}


    .sidebar.collapsed .logo {
      padding: 1rem 0;
    }


    .nav-item {
      transition: all 0.2s ease;
    }

    .nav-item:hover {
      background-color: #f0e8e1;
      color: #551a25;
      transform: translateX(5px);
      box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    }

    .sidebar-toggle-btn {
      position: absolute;
      top: 1rem;
      left: 1rem;
      z-index: 50;
      background-color: #551a25;
      color: white;
      padding: 0.75rem 1rem;
      border-radius: 9999px;
      font-size: 1.25rem;
      box-shadow: 0 4px 6px rgba(0,0,0,0.1);
      transition: all 0.3s ease;
    }

    .chart-container {
      width: 100%;
      height: 400px;
    }

    #mainContent {
      animation: fadeInUp 0.5s ease-in-out;
    }

    @keyframes fadeInUp {
      from {
        opacity: 0;
        transform: translateY(20px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }
  </style>
</head>
<body class="flex bg-[#f0e8e1] h-screen overflow-hidden">

<aside id="sidebar" class="sidebar w-64 bg-[#551a25] text-white fixed h-full overflow-y-auto z-40">
  <button id="toggleSidebar" class="sidebar-toggle-btn">☰</button>
  <div class="logo">
    <img src="../../images/logo.png" alt="Logo" class="logo-full w-36 h-36 mx-auto rounded-full transition-all duration-300">
  </div>
  <nav class="flex-1 px-4 space-y-2">
    <a href="dashboard.php" class="flex items-center gap-4 nav-item p-3 rounded-lg">
      <i class="fas fa-home text-xl"></i><span class="nav-text text-lg">Dashboard</span>
    </a>
    <a href="students.php" class="flex items-center gap-4 nav-item p-3 rounded-lg">
      <i class="fas fa-users text-xl"></i><span class="nav-text text-lg">Students</span>
    </a>
    <a href="lessons.php" class="flex items-center gap-4 nav-item p-3 rounded-lg">
      <i class="fas fa-book text-xl"></i><span class="nav-text text-lg">Lessons</span>
    </a>
      <a href="activities.php" class="flex items-center gap-4 nav-item p-3 rounded-lg">
      <i class="fas fa-tasks text-xl"></i><span class="nav-text text-lg">Activities</span>
    </a>
    <a href="insights.php" class="flex items-center gap-4 nav-item p-3 rounded-lg">
      <i class="fas fa-chart-line text-xl"></i><span class="nav-text text-lg">Insights</span>
    </a>
    <a href="logout.php" class="flex items-center gap-4 nav-item p-3 rounded-lg mt-6">
      <i class="fas fa-sign-out-alt text-xl"></i><span class="nav-text text-lg">Logout</span>
    </a>
  </nav>
</aside>
<div id="mainContent" class="flex-1 ml-64 p-8 overflow-y-auto transition-all duration-300">
  <div class="flex items-center justify-between mb-6">
    <h1 class="text-4xl font-bold text-[#551a25]">Welcome, Teacher <?php echo $firstname ?>!</h1>
  </div>
<!-- Engagement Cards -->
<section class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-8 mb-12 px-4">
  <?php foreach($engagement_data as $data): ?>
  <div class="bg-white p-8 rounded-xl shadow-lg text-center hover:shadow-xl transition-shadow duration-300">
    <h3 class="text-2xl font-bold text-[#551a25] mb-3 tracking-wide flex items-center justify-center gap-3">
      <i class="fas fa-layer-group text-[#8b2e2e] text-xl"></i>
      <?php echo htmlspecialchars($data['section_name']); ?>
    </h3>
    <p class="text-lg font-semibold text-gray-700 flex items-center justify-center gap-2">
      Students Enrolled
    </p>
    <p class="text-4xl font-extrabold text-[#8b2e2e] mt-1 flex items-center justify-center gap-2">
    <?php echo $data['student_count']; ?>
    </p>
  </div>
  <?php endforeach; ?>
</section>

<!-- Top Performers -->
<section class="mb-12 px-4">
  <h2 class="text-3xl font-extrabold text-[#551a25] mb-6 border-b-4 border-[#8b2e2e] pb-2 flex items-center gap-3">
    <i class="fas fa-trophy text-[#8b2e2e] text-2xl"></i> Top Performers Per Section
  </h2>
  <?php foreach ($top_performers as $section_name => $performers): ?>
    <div class="bg-white p-6 rounded-xl shadow-md mb-8 hover:shadow-lg transition-shadow duration-300">
      <h3 class="text-2xl font-semibold text-[#551a25] mb-4 border-b border-gray-200 pb-2 flex items-center gap-2">
        <?php echo htmlspecialchars($section_name); ?>
      </h3>
      <?php if (count($performers) > 0): ?>
      <div class="overflow-x-auto">
      <table class="min-w-full text-left text-base sm:text-lg">
        <thead>
          <tr class="bg-[#f9eaea] border-b border-[#e0b3b3]">
            <th class="py-3 px-6 font-semibold text-[#551a25]">
               Name
            </th>
            <th class="py-3 px-6 font-semibold text-[#551a25]">
              Score
            </th>
          </tr>
        </thead>
        <tbody>
          <?php foreach($performers as $p): ?>
          <tr class="border-b border-gray-100 hover:bg-[#f7d8d8] transition-colors duration-200">
            <td class="py-3 px-6"><?php echo htmlspecialchars($p['firstname'] . ' ' . $p['lastname']); ?></td>
            <td class="py-3 px-6 font-semibold text-[#8b2e2e]"><?php echo $p['score']; ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      </div>
      <?php else: ?>
        <p class="text-gray-600 italic flex items-center gap-2">
          <i class="fas fa-info-circle"></i> No quiz submissions yet.
        </p>
      <?php endif; ?>
    </div>
  <?php endforeach; ?>
</section>

<!-- Recent Comments -->
<section class="mb-12 px-4 bg-white p-8 rounded-xl shadow-lg">
  <h2 class="text-3xl font-extrabold text-[#551a25] mb-6 border-b-4 border-[#8b2e2e] pb-2 flex items-center gap-3">
    Recent Student Comments
  </h2>
  <?php if (count($recent_comments) > 0): ?>
    <ul class="space-y-6">
    <?php foreach($recent_comments as $comment): ?>
      <li class="border-l-4 border-[#8b2e2e] pl-5">
        <p class="italic text-gray-700 text-lg flex items-center gap-2">
          <i class="fas fa-quote-left text-[#8b2e2e]"></i>
          "<?php echo htmlspecialchars($comment['comments']); ?>"
          <i class="fas fa-quote-right text-[#8b2e2e]"></i>
        </p>
        <p class="text-sm mt-2 text-gray-500 font-medium flex items-center gap-2">
          <i class="fas fa-user-circle"></i> <?php echo htmlspecialchars($comment['firstname'] . ' ' . $comment['lastname']); ?>,
          <time datetime="<?php echo htmlspecialchars($comment['created_at']); ?>" class="ml-1">
            <?php echo date('M d, Y', strtotime($comment['created_at'])); ?>
          </time>
        </p>
      </li>
    <?php endforeach; ?>
    </ul>
  <?php else: ?>
    <p class="text-gray-600 italic flex items-center gap-2">
      <i class="fas fa-info-circle"></i> No comments yet.
    </p>
  <?php endif; ?>
</section>



  <script>
    const sidebar = document.getElementById('sidebar');
    const toggleBtn = document.getElementById('toggleSidebar');
    const mainContent = document.getElementById('mainContent');

    toggleBtn.addEventListener('click', () => {
      sidebar.classList.toggle('collapsed');
      if (sidebar.classList.contains('collapsed')) {
        mainContent.classList.remove('ml-64');
        mainContent.classList.add('ml-20');
      } else {
        mainContent.classList.remove('ml-20');
        mainContent.classList.add('ml-64');
      }
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
(function(){if(!window.chatbase||window.chatbase("getState")!=="initialized"){window.chatbase=(...arguments)=>{if(!window.chatbase.q){window.chatbase.q=[]}window.chatbase.q.push(arguments)};window.chatbase=new Proxy(window.chatbase,{get(target,prop){if(prop==="q"){return target.q}return(...args)=>target(prop,...args)}})}const onLoad=function(){const script=document.createElement("script");script.src="https://www.chatbase.co/embed.min.js";script.id="F_d1NAFthV9gdiqKlr7ff";script.domain="www.chatbase.co";document.body.appendChild(script)};if(document.readyState==="complete"){onLoad()}else{window.addEventListener("load",onLoad)}})();
</script>
</body>
</html>