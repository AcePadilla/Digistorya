<?php
session_start();
require_once 'db_connection.php';

if (!isset($_SESSION['teacher_email'])) {
    header("Location: login.php");
    exit();
}

$email = $_SESSION['teacher_email'];
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

// Get sections assigned to this teacher
$sections_stmt = $conn->prepare("SELECT s.id, s.section_name FROM teacher_section ts JOIN section s ON ts.section_id = s.id WHERE ts.teacher_id = ?");
$sections_stmt->bind_param("i", $teacher_id);
$sections_stmt->execute();
$sections_result = $sections_stmt->get_result();
$sections = [];
while ($row = $sections_result->fetch_assoc()) {
    $sections[] = $row;
}
$sections_stmt->close();

// Handle DELETE
if (isset($_GET['delete'])) {
    $delete_id = intval($_GET['delete']);
    $stmt = $conn->prepare("SELECT id FROM activities WHERE id = ? AND teacher_id = ?");
    $stmt->bind_param("ii", $delete_id, $teacher_id);
    $stmt->execute();
    $check = $stmt->get_result();

    if ($check && $check->num_rows > 0) {
        $stmt = $conn->prepare("DELETE FROM activities WHERE id = ?");
        $stmt->bind_param("i", $delete_id);
        if ($stmt->execute()) {
            $_SESSION['success_message'] = "Question deleted successfully.";
        } else {
            $_SESSION['success_message'] = "Failed to delete question.";
        }
        $stmt->close();
    } else {
        $_SESSION['success_message'] = "You do not have permission to delete this question.";
    }
    header("Location: activities.php");
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $num_questions = count($_POST['question_text']);
    $section_id = intval($_POST['section_id'] ?? 0);

    // Check if section is assigned to teacher
    $section_check = $conn->prepare("SELECT * FROM teacher_section WHERE teacher_id = ? AND section_id = ?");
    $section_check->bind_param("ii", $teacher_id, $section_id);
    $section_check->execute();
    $check_result = $section_check->get_result();

    if ($check_result->num_rows === 0) {
        die("Invalid section assignment.");
    }
    
    $stmt = $conn->prepare("INSERT INTO activities (teacher_id, question_type, question_text, choices, correct_answer, created_at, section_id, title) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");

    if (!$stmt) {
        die("Prepare failed: " . $conn->error);
    }

    $now = date('Y-m-d H:i:s');

    for ($i = 0; $i < $num_questions; $i++) {
        $type = $_POST['question_type'][$i];
        $question = trim($_POST['question_text'][$i]);
        $correct = $_POST["correct_answer_$i"] ?? null;
        $title = trim($_POST['title'] ?? '');

        if (!$correct) {
            die("No correct answer selected for question $i.");
        }

        if ($type === 'mcq') {
            $choices = [
                'A' => trim($_POST["choice_{$i}_A"] ?? ''),
                'B' => trim($_POST["choice_{$i}_B"] ?? ''),
                'C' => trim($_POST["choice_{$i}_C"] ?? ''),
                'D' => trim($_POST["choice_{$i}_D"] ?? ''),
            ];

            foreach ($choices as $key => $val) {
                if ($val === '') {
                    die("Choice $key is empty for question $i.");
                }
            }

            $choices_json = json_encode($choices);
        } else {
            $choices_json = null;
        }

        $stmt->bind_param("isssssis", $teacher_id, $type, $question, json_encode($choices), $correct, $now, $section_id, $title);

        if (!$stmt->execute()) {
            die("Execute failed on question $i: " . $stmt->error);
        }
    }

    $stmt->close();

    $_SESSION['success_message'] = "Questions created successfully!";
    header("Location: activities.php");
    exit();
}

$stmt = $conn->prepare("
    SELECT a.*, s.section_name 
    FROM activities a 
    JOIN section s ON a.section_id = s.id 
    JOIN teacher_section ts ON s.id = ts.section_id 
    WHERE ts.teacher_id = ?
    ORDER BY a.title ASC, a.created_at DESC
");
$stmt->bind_param("i", $teacher_id);
$stmt->execute();
$result = $stmt->get_result();

// Group activities by title
$grouped_activities = [];
while ($row = $result->fetch_assoc()) {
    $grouped_activities[$row['title']][] = $row;
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

<h1 class="text-3xl font-extrabold mb-8 text-[#551a25] tracking-wide">Activities</h1>

<form method="POST" id="questionsForm" class="w-full max-w bg-white p-8 rounded-xl shadow-lg space-y-8 mx-auto">

 <h2 class="text-2xl font-bold text-[#551a25] mb-2">Create Your Activities Questions</h2>
<p class="text-gray-600 mb-6">
  Click the "Add Multiple Choice Question" button to add a question with several answer options, or "Add True or False Question" to add a simpler, two-choice question. You can add as many questions as you want before submitting.
</p>
<p class="text-gray-600 mb-6">
  When you're finished, click <strong>"Submit All Questions"</strong> to save your activities. Make sure to review your questions before submitting.
</p>


  
    <div class="mb-6">
  <label for="section_id" class="block text-lg font-medium text-[#551a25] mb-2">Select a section where you want to add the Activities.</label>
  <select name="section_id" id="section_id" required class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-[#d79e4b]">
    <option value=""></option>
    <?php foreach ($sections as $section): ?>
      <option value="<?= htmlspecialchars($section['id']) ?>"><?= htmlspecialchars($section['section_name']) ?></option>
    <?php endforeach; ?>
  </select>
</div>
<div class="mb-4">
  <label for="title" class="block text-gray-700 font-semibold mb-2">
    Activity Title:
  </label>
  <input 
    type="text" 
    id="title" 
    name="title" 
    required 
    placeholder="e.g., Introduction to Biology"
    class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 placeholder-gray-400 transition"
  />
  <div id="questionsContainer" class="space-y-6"></div>
</div>

  <div class="flex gap-6 flex-wrap items-center">
    <button
      type="button"
      onclick="addQuestion('mcq')"
      class="bg-[#551a25] text-[#f0e8e1] px-6 py-3 rounded-lg font-semibold shadow-md hover:bg-[#44141e] focus:outline-none focus:ring-2 focus:ring-[#d79e4b] transition-colors duration-300"
      aria-label="Add multiple choice question"
    >
      Add Multiple Choice Question
    </button>
    <button
      type="button"
      onclick="addQuestion('truefalse')"
      class="bg-[#551a25] text-[#f0e8e1] px-6 py-3 rounded-lg font-semibold shadow-md hover:bg-[#44141e] focus:outline-none focus:ring-2 focus:ring-[#d79e4b] transition-colors duration-300"
      aria-label="Add true or false question"
    >
      Add True or False Question
    </button>
  </div>

  <button
    type="submit"
    class="bg-[#d79e4b] text-[#551a25] font-bold px-8 py-4 rounded-xl shadow-lg hover:bg-[#b38339] focus:outline-none focus:ring-4 focus:ring-[#d79e4b] transition-colors duration-300 w-full mt-8"
  >
    Submit All Questions
  </button>
</form>

<script>
  function addQuestion(type) {
    // Your existing addQuestion logic here

    // After adding the question, scroll smoothly to it:
    setTimeout(() => {
      const container = document.getElementById('questionsContainer');
      container.lastElementChild?.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }, 100);
  }
</script>

<section class="mt-16 max-w bg-white p-8 rounded-xl shadow-lg overflow-x-auto">
  <h2 class="text-2xl font-extrabold mb-6 text-[#551a25] tracking-wide">Your Created Questions</h2>

  <?php if (empty($grouped_activities)): ?>
    <p class="text-[#551a25] opacity-80">You haven't created any questions yet.</p>
  <?php else: ?>
    <?php foreach ($grouped_activities as $title => $questions): ?>
      <h3 class="text-xl font-bold mb-4 text-[#551a25] border-b border-[#d79e4b] pb-2"><?= htmlspecialchars($title) ?></h3>

      <table class="min-w-full text-sm text-left border border-[#d79e4b] rounded-md overflow-hidden mb-10">
        <thead class="bg-[#551a25] text-[#f0e8e1] uppercase tracking-wide text-xs">
          <tr>
            <th class="px-6 py-3 border border-[#d79e4b]">Type</th>
            <th class="px-6 py-3 border border-[#d79e4b]">Question</th>
            <th class="px-6 py-3 border border-[#d79e4b]">Choices</th>
            <th class="px-6 py-3 border border-[#d79e4b]">Correct Answer</th>
            <th class="px-6 py-3 border border-[#d79e4b]">Section</th>
            <th class="px-6 py-3 border border-[#d79e4b] text-center">Action</th>
          </tr>
        </thead>
        <tbody class="bg-white divide-y divide-[#f0e8e1]">
          <?php foreach ($questions as $q): ?>
            <tr class="even:bg-[#f0e8e1] odd:bg-white border-t hover:bg-gray-100 transition">
              <td class="px-6 py-4 text-[#551a25] font-medium"><?= strtoupper(htmlspecialchars($q['question_type'])) ?></td>
              <td class="px-6 py-4 text-[#551a25]"><?= htmlspecialchars($q['question_text']) ?></td>
              <td class="px-6 py-4 text-[#551a25] whitespace-pre-wrap">
                <?php
                if ($q['question_type'] === 'mcq' && $q['choices']) {
                  $choices_arr = json_decode($q['choices'], true);
                  if (is_array($choices_arr)) {
                    foreach ($choices_arr as $key => $choice) {
                      echo "<strong>$key.</strong> " . htmlspecialchars($choice) . "<br>";
                    }
                  }
                } else {
                  echo '-';
                }
                ?>
              </td>
              <td class="px-6 py-4 font-semibold text-[#d79e4b]"><?= htmlspecialchars($q['correct_answer']) ?></td>
              <td class="px-6 py-4 text-[#551a25]"><?= htmlspecialchars($q['section_name']) ?></td>
              <td class="px-6 py-4 text-center">
                <a href="activities.php?delete=<?= $q['id'] ?>" class="text-red-600 hover:text-red-800 delete-link" title="Delete">
                  <i class="fas fa-trash-alt text-lg"></i>
                </a>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endforeach; ?>
  <?php endif; ?>
</section>


<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
  // Select all delete links
  const deleteLinks = document.querySelectorAll('.delete-link');

  deleteLinks.forEach(link => {
    link.addEventListener('click', function(e) {
      e.preventDefault(); // Prevent default navigation
      
      Swal.fire({
        title: 'Are you sure you want to delete this question?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: 'gray',
        confirmButtonText: 'Yes, delete it!',
        cancelButtonText: 'Cancel'
      }).then((result) => {
        if (result.isConfirmed) {
          // If user confirms, go to the delete link
          window.location.href = link.href;
        }
      });
    });
  });
});
</script>



<script>
  let questionCount = 0;

  function addQuestion(type) {
    const container = document.getElementById('questionsContainer');
    const qIndex = questionCount++;
    let html = '';

    if (type === 'mcq') {
      html = `
      <div class="mb-10  p-6 rounded-xl bg-white shadow-md">
        <input type="hidden" name="question_type[]" value="mcq" />
        <label class="block mb-3 font-extrabold text-[#551a25] text-lg">Question ${qIndex + 1} (MCQ):</label>
        <input
          type="text"
          name="question_text[]"
          required
          class="w-full p-3  rounded-md border border-[#551a25] focus:outline-none focus:ring-2 focus:ring-[#d79e4b] mb-6 text-[#551a25]"
          placeholder="Enter question text"
        />

        <div class="grid grid-cols-2 gap-6 mb-6">
          <div>
            <label class="block mb-1  font-semibold text-[#551a25]">Choice A:</label>
            <input
              type="text"
              name="choice_${qIndex}_A"
              required
              class="w-full p-3  rounded-md border border-[#551a25] focus:outline-none focus:ring-2 focus:ring-[#d79e4b] text-[#551a25]"
              placeholder="Choice A"
            />
          </div>
          <div>
            <label class="block mb-1  font-semibold text-[#551a25]">Choice B:</label>
            <input
              type="text"
              name="choice_${qIndex}_B"
              required
              class="w-full p-3  rounded-md border border-[#551a25] focus:outline-none focus:ring-2 focus:ring-[#d79e4b] text-[#551a25]"
              placeholder="Choice B"
            />
          </div>
          <div>
            <label class="block mb-1  font-semibold text-[#551a25]">Choice C:</label>
            <input
              type="text"
              name="choice_${qIndex}_C"
              required
              class="w-full p-3 border border-[#551a25] rounded-md focus:outline-none focus:ring-2 focus:ring-[#d79e4b] text-[#551a25]"
              placeholder="Choice C"
            />
          </div>
          <div>
            <label class="block mb-1 font-semibold text-[#551a25]">Choice D:</label>
            <input
              type="text"
              name="choice_${qIndex}_D"
              required
              class="w-full p-3 border border-[#551a25] rounded-md focus:outline-none focus:ring-2 focus:ring-[#d79e4b] text-[#551a25]"
              placeholder="Choice D"
            />
          </div>
        </div>

        <label class="block mb-2 font-extrabold text-[#551a25]">Correct Answer:</label>
        <select
          name="correct_answer_${qIndex}"
          required
          class="w-full max-w-xs p-3  border border-[#551a25] rounded-md focus:outline-none focus:ring-2 focus:ring-[#d79e4b] text-[#551a25]"
        >
          <option value="" disabled selected>Select correct answer</option>
          <option value="A">A</option>
          <option value="B">B</option>
          <option value="C">C</option>
          <option value="D">D</option>
        </select>
      </div>
      `;
    } else if (type === 'truefalse') {
      html = `
      <div class="mb-10 border-2  p-6 rounded-xl bg-white shadow-md">
        <input type="hidden" name="question_type[]" value="truefalse" />
        <label class="block mb-3 font-extrabold text-[#551a25] text-lg">Question ${qIndex + 1} (True/False):</label>
        <input
          type="text"
          name="question_text[]"
          required
          class="w-full p-3  border border-[#551a25] rounded-md focus:outline-none focus:ring-2 focus:ring-[#d79e4b] mb-6 text-[#551a25]"
          placeholder="Enter question text"
        />

        <label class="block mb-2 font-extrabold text-[#551a25]">Correct Answer:</label>
        <select
          name="correct_answer_${qIndex}"
          required
          class="w-full max-w-xs p-3 border border-[#551a25] rounded-md focus:outline-none focus:ring-2 focus:ring-[#d79e4b] text-[#551a25]"
        >
          <option value="" disabled selected>Select correct answer</option>
          <option value="True">True</option>
          <option value="False">False</option>
        </select>
      </div>
      `;
    }

    container.insertAdjacentHTML('beforeend', html);
  }

  // Sidebar toggle
  const toggleSidebarBtn = document.getElementById('toggleSidebar');
  const sidebar = document.getElementById('sidebar');
  const mainContent = document.getElementById('mainContent');

  toggleSidebarBtn.addEventListener('click', () => {
    sidebar.classList.toggle('collapsed');
    if (sidebar.classList.contains('collapsed')) {
      mainContent.classList.remove('ml-64');
      mainContent.classList.add('ml-16');
    } else {
      mainContent.classList.remove('ml-16');
      mainContent.classList.add('ml-64');
    }
  });
</script>

<?php if (isset($successMessage) && $successMessage): ?>
<script>
  Swal.fire({
    title: 'Success!',
    text: <?= json_encode($successMessage) ?>,
    icon: 'success',
    confirmButtonText: 'OK'
  });
</script>
<?php endif; ?>
<script>
(function(){if(!window.chatbase||window.chatbase("getState")!=="initialized"){window.chatbase=(...arguments)=>{if(!window.chatbase.q){window.chatbase.q=[]}window.chatbase.q.push(arguments)};window.chatbase=new Proxy(window.chatbase,{get(target,prop){if(prop==="q"){return target.q}return(...args)=>target(prop,...args)}})}const onLoad=function(){const script=document.createElement("script");script.src="https://www.chatbase.co/embed.min.js";script.id="F_d1NAFthV9gdiqKlr7ff";script.domain="www.chatbase.co";document.body.appendChild(script)};if(document.readyState==="complete"){onLoad()}else{window.addEventListener("load",onLoad)}})();
</script>
</body>
</html>
