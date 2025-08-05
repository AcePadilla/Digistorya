<?php
session_start();

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}


include 'db_connection.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/../../vendor/autoload.php';

function isLastWeekOfMonth() {
    $today = date('Y-m-d');
    $endOfMonth = date('Y-m-t');
    $diff = (strtotime($endOfMonth) - strtotime($today)) / (60 * 60 * 24);
    return $diff <= 7;
}

function sendReminderEmail($toEmail, $toName, $bodyMessage) {
    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'digistorya@gmail.com';
        $mail->Password = 'lvyu ooni neiw gxem';   // your app password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        $mail->setFrom('digistorya@gmail.com', 'DIGIstorya');
        $mail->addAddress($toEmail, $toName);

        $mail->isHTML(true);
        $mail->Subject = 'Monthly Survey Reminder';
        $mail->Body    = $bodyMessage;

        $mail->send();
        error_log("Email sent to $toEmail");
        return true; // success
    } catch (Exception $e) {
        error_log("Mailer Error: {$mail->ErrorInfo}");
        return false; // failed
    }
}

if (isset($_POST['send_all_otp'])) {
    $users_to_notify = [];
    $section_query = $conn->query("SELECT * FROM section");

    while ($section = $section_query->fetch_assoc()) {
        $section_id = (int)$section['id'];
        $users_query = $conn->query("
            SELECT users.id, users.firstname, users.lastname, users.email 
            FROM users 
            LEFT JOIN quiz_results 
              ON users.id = quiz_results.user_id 
              AND MONTH(quiz_results.created_at) = MONTH(CURRENT_DATE()) 
              AND YEAR(quiz_results.created_at) = YEAR(CURRENT_DATE())
            WHERE users.section_id = $section_id
              AND quiz_results.id IS NULL
        ");
        while ($user = $users_query->fetch_assoc()) {
            $users_to_notify[] = $user;
        }
    }

    $all_success = true;  // flag to track if all succeeded

    foreach ($users_to_notify as $user) {
        $message = isLastWeekOfMonth()
            ? "Hi {$user['firstname']}, please complete your monthly survey before the end of the month. Thank you!"
            : "Hi {$user['firstname']}, don't forget to take your monthly survey for this month!";

        $sent = sendReminderEmail($user['email'], $user['firstname'] . ' ' . $user['lastname'], $message);
        if (!$sent) {
            $all_success = false;
        }
    }

    $_SESSION['otp_send_status'] = $all_success ? 'success' : 'failed';
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}


// Fetch totals for dashboard
$total_users = $conn->query("SELECT COUNT(*) AS total FROM users")->fetch_assoc()['total'];
$total_teachers = $conn->query("SELECT COUNT(*) AS total FROM teachers")->fetch_assoc()['total'];
$total_sections = $conn->query("SELECT COUNT(*) AS total FROM section")->fetch_assoc()['total'];

// Fetch sections and users who haven't taken survey in current month
$sections = [];
$section_query = $conn->query("SELECT * FROM section");

while ($section = $section_query->fetch_assoc()) {
    $section_id = (int)$section['id'];

    // Get users in this section who have NOT taken the survey this month
    $users_query = $conn->query("
        SELECT users.id, users.firstname, users.lastname, users.email 
        FROM users 
        LEFT JOIN quiz_results 
          ON users.id = quiz_results.user_id 
          AND MONTH(quiz_results.created_at) = MONTH(CURRENT_DATE()) 
          AND YEAR(quiz_results.created_at) = YEAR(CURRENT_DATE())
        WHERE users.section_id = $section_id
          AND quiz_results.id IS NULL
    ");

    $users = [];
    while ($user = $users_query->fetch_assoc()) {
        $users[] = $user;
    }

    $section['users'] = $users;
    $sections[] = $section;
}
// Count of students who have not taken the survey this month
$not_taken_count_result = $conn->query("
    SELECT COUNT(*) AS count 
    FROM users 
    LEFT JOIN quiz_results 
      ON users.id = quiz_results.user_id 
      AND MONTH(quiz_results.created_at) = MONTH(CURRENT_DATE()) 
      AND YEAR(quiz_results.created_at) = YEAR(CURRENT_DATE())
    WHERE quiz_results.id IS NULL
");
$not_taken_count = $not_taken_count_result->fetch_assoc()['count'];

$sql = "
    SELECT u.id,
        sr.active_reflective, sr.sensing_intuitive, sr.visual_verbal, sr.sequential_global
    FROM users u
    JOIN (
        SELECT qr.*
        FROM quiz_results qr
        INNER JOIN (
            SELECT user_id, MAX(created_at) AS latest
            FROM quiz_results
            GROUP BY user_id
        ) latest_qr ON qr.user_id = latest_qr.user_id AND qr.created_at = latest_qr.latest
    ) sr ON u.id = sr.user_id
";

$result = $conn->query($sql);

$balancedCount = 0;
$unbalancedCount = 0;

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $active_reflective = explode('|', $row['active_reflective']);
        $sensing_intuitive = explode('|', $row['sensing_intuitive']);
        $visual_verbal = explode('|', $row['visual_verbal']);
        $sequential_global = explode('|', $row['sequential_global']);

        $balancedPairs = 0;
        if (abs($active_reflective[0] - $active_reflective[1]) <= 1) $balancedPairs++;
        if (abs($sensing_intuitive[0] - $sensing_intuitive[1]) <= 1) $balancedPairs++;
        if (abs($visual_verbal[0] - $visual_verbal[1]) <= 1) $balancedPairs++;
        if (abs($sequential_global[0] - $sequential_global[1]) <= 1) $balancedPairs++;

        if ($balancedPairs === 4) {
            $balancedCount++;
        } else {
            $unbalancedCount++;
        }
    }
}

// Function to get survey counts by date range and grouping
function getSurveyCounts($conn, $filterType) {
    $resultData = [];

    if ($filterType === 'week') {
        // Get current week's Sunday to Saturday dates
        $startOfWeek = date('Y-m-d', strtotime('last Sunday'));
        $endOfWeek = date('Y-m-d', strtotime('next Saturday'));

        $sql = "
            SELECT DATE(created_at) as day, COUNT(DISTINCT user_id) AS count
            FROM quiz_results
            WHERE DATE(created_at) BETWEEN '$startOfWeek' AND '$endOfWeek'
            GROUP BY DATE(created_at)
            ORDER BY DATE(created_at)
        ";

        $res = $conn->query($sql);
        while ($row = $res->fetch_assoc()) {
            $resultData[$row['day']] = (int)$row['count'];
        }

        // Fill missing days with zero counts for Sunday-Saturday
        $period = new DatePeriod(
            new DateTime($startOfWeek),
            new DateInterval('P1D'),
            (new DateTime($endOfWeek))->modify('+1 day')
        );
        foreach ($period as $date) {
            $day = $date->format('Y-m-d');
            if (!isset($resultData[$day])) $resultData[$day] = 0;
        }
        ksort($resultData);
    }
    else if ($filterType === 'month') {
        $year = date('Y');
        $month = date('m');

        $daysInMonth = date('t');
        $sql = "
            SELECT DAY(created_at) as day, COUNT(DISTINCT user_id) AS count
            FROM quiz_results
            WHERE YEAR(created_at) = $year AND MONTH(created_at) = $month
            GROUP BY DAY(created_at)
            ORDER BY DAY(created_at)
        ";

        $res = $conn->query($sql);
        while ($row = $res->fetch_assoc()) {
            $resultData[(int)$row['day']] = (int)$row['count'];
        }

        // Fill missing days with zero counts
        for ($d = 1; $d <= $daysInMonth; $d++) {
            if (!isset($resultData[$d])) $resultData[$d] = 0;
        }
        ksort($resultData);
    }
    else if ($filterType === 'year') {
        $year = date('Y');

        $sql = "
            SELECT MONTH(created_at) as month, COUNT(DISTINCT user_id) AS count
            FROM quiz_results
            WHERE YEAR(created_at) = $year
            GROUP BY MONTH(created_at)
            ORDER BY MONTH(created_at)
        ";

        $res = $conn->query($sql);
        while ($row = $res->fetch_assoc()) {
            $resultData[(int)$row['month']] = (int)$row['count'];
        }

        // Fill missing months with zero counts
        for ($m = 1; $m <= 12; $m++) {
            if (!isset($resultData[$m])) $resultData[$m] = 0;
        }
        ksort($resultData);
    }

    return $resultData;
}

// If ajax request for data
if (isset($_GET['get_survey_data'])) {
    $filter = $_GET['filter'] ?? 'week';
    $data = getSurveyCounts($conn, $filter);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
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
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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
    <a href="teachers.php" class="flex items-center gap-4 nav-item p-3 rounded-lg">
      <i class="fas fa-user-tie text-xl"></i><span class="nav-text text-lg">Teachers</span>
    </a>
    <a href="students.php" class="flex items-center gap-4 nav-item p-3 rounded-lg">
      <i class="fas fa-users text-xl"></i><span class="nav-text text-lg">Students</span>
    </a>
    <a href="section.php" class="flex items-center gap-4 nav-item p-3 rounded-lg">
      <i class="fas fa-folder"></i><span class="nav-text text-lg">Sections</span>
    </a>
    
    <!-- New Items -->
    <a href="insights.php" class="flex items-center gap-4 nav-item p-3 rounded-lg">
      <i class="fas fa-chart-line text-xl"></i><span class="nav-text text-lg">Insights</span>
    </a>
    <a href="lessons.php" class="flex items-center gap-4 nav-item p-3 rounded-lg">
      <i class="fas fa-book text-xl"></i><span class="nav-text text-lg">Lessons</span>
    </a>
     <a href="activities.php" class="flex items-center gap-4 nav-item p-3 rounded-lg">
      <i class="fas fa-tasks text-xl"></i><span class="nav-text text-lg">Activities</span>
    </a> 
  

    <a href="logout.php" class="flex items-center gap-4 nav-item p-3 rounded-lg mt-6">
      <i class="fas fa-sign-out-alt text-xl"></i><span class="nav-text text-lg">Logout</span>
    </a>
  </nav>
</aside>



 <div id="mainContent" class="flex-1 ml-64 p-8 overflow-y-auto transition-all duration-300">
  <h1 class="text-4xl font-bold text-[#551a25] mb-6">Welcome, Admin!</h1>

<div class="grid grid-cols-1 md:grid-cols-3 gap-8 mb-2">
  <!-- Card 1: Total Students -->
  <div
    class="bg-white rounded-3xl shadow-md p-7 flex items-center space-x-6 h-full"
  >
    <i class="fas fa-user-graduate text-4xl text-[#d79e4b] flex-shrink-0"></i>
    <div class="flex flex-col justify-center">
      <p class="text-gray-500 text-sm uppercase tracking-wide mb-1">Total Students</p>
      <h3 class="text-3xl font-extrabold text-[#551a25] leading-tight"><?php echo $total_users; ?></h3>
    </div>
  </div>

  <!-- Card 2: Total Teachers -->
  <div
    class="bg-white rounded-3xl shadow-md p-7 flex items-center space-x-6 h-full"
  >
    <i class="fas fa-chalkboard-teacher text-4xl text-[#d79e4b] flex-shrink-0"></i>
    <div class="flex flex-col justify-center">
      <p class="text-gray-500 text-sm uppercase tracking-wide mb-1">Total Teachers</p>
      <h3 class="text-3xl font-extrabold text-[#551a25] leading-tight"><?php echo $total_teachers; ?></h3>
    </div>
  </div>

  <!-- Card 3: Total Sections -->
  <div
    class="bg-white rounded-3xl shadow-md p-7 flex items-center space-x-6 h-full"
  >
    <i class="fas fa-layer-group text-4xl text-[#d79e4b] flex-shrink-0"></i>
    <div class="flex flex-col justify-center">
      <p class="text-gray-500 text-sm uppercase tracking-wide mb-1">Total Sections</p>
      <h3 class="text-3xl font-extrabold text-[#551a25] leading-tight"><?php echo $total_sections; ?></h3>
    </div>
  </div>
</div>

<?php
$no_section_users = $conn->query("
    SELECT id, firstname, lastname, email 
    FROM users 
    WHERE section_id IS NULL
");
?>
<div class="max-w mx-auto py-8">
  <div class="flex flex-col md:flex-row md:space-x-8 space-y-8 md:space-y-0 w-full">

    <!-- Line Chart Section -->
    <section
      class="flex-1 bg-white rounded-3xl shadow-md p-8 flex flex-col"
      aria-labelledby="surveyReportTitle"
    >
      <div
        class="flex flex-col md:flex-row md:items-center md:justify-between mb-6 space-y-4 md:space-y-0"
      >
        <h2
          id="surveyReportTitle"
          class="text-2xl font-bold text-[#551a25] flex items-center"
        >
          <i class="fas fa-chart-line mr-3 text-2xl text-[#d79e4b]"></i>
          Numbers of learning style assesment taken.
        </h2>

        <div class="flex items-center space-x-6">
          <label
            for="filter"
            class="text-lg font-semibold text-[#551a25]"
            >Filter by:</label
          >
          <select
            id="filter"
            aria-label="Filter Survey Report"
            class="bg-[#d79e4b] text-[#551a25] font-bold rounded-lg px-5 py-2 cursor-pointer
                   focus:outline-none focus:ring-4 focus:ring-[#551a25]/50
                   hover:bg-[#551a25] hover:text-[#f0e8e1] transition-colors duration-300"
          >
            <option value="week" selected>This Week (Sun-Sat)</option>
            <option value="month">This Month</option>
            <option value="year">This Year</option>
          </select>
        </div>
      </div>

      <div class="flex-grow flex justify-center items-center min-h-[420px] max-h-[420px]">
       <canvas id="surveyChart" class="w-full max-w-full" height="220"></canvas>
      </div>
    </section>
  </div>
</div>


<script>
  const ctx = document.getElementById('surveyChart').getContext('2d');
  let surveyChart;

  function fetchAndRenderChart(filter) {
    fetch(`dashboard.php?get_survey_data=1&filter=${filter}`)
      .then(res => res.json())
      .then(data => {
        let labels = [];
        let counts = [];

        if (filter === 'week') {
          labels = Object.keys(data).map(d => {
            let date = new Date(d);
            return date.toLocaleDateString('en-US', { weekday: 'short' });
          });
          counts = Object.values(data);
        } else if (filter === 'month') {
          labels = Object.keys(data).map(day => day);
          counts = Object.values(data);
        } else if (filter === 'year') {
          labels = Object.keys(data).map(m => {
            const monthNum = parseInt(m);
            return new Date(0, monthNum - 1).toLocaleString('default', { month: 'short' });
          });
          counts = Object.values(data);
        }

        if (surveyChart) surveyChart.destroy();

        surveyChart = new Chart(ctx, {
          type: 'line',
          data: {
            labels: labels,
            datasets: [{
              label: 'Number of Survey Taken',
              data: counts,
              fill: true,
              backgroundColor: 'rgba(215, 158, 75, 0.3)',  // light gold fill
              borderColor: '#d79e4b',                      // gold line
              borderWidth: 3,
              pointBackgroundColor: '#551a25',             // dark maroon points
              pointRadius: 6,
              pointHoverRadius: 8,
              tension: 0.3                                // smooth curves
            }]
          },
          options: {
            responsive: true,
            animation: {
              duration: 800,
              easing: 'easeOutQuart'
            },
            plugins: {
              legend: {
                labels: {
                  color: '#551a25',
                  font: {
                    size: 16,
                    weight: '700'
                  }
                }
              },
              tooltip: {
                backgroundColor: '#551a25',
                titleColor: '#d79e4b',
                bodyColor: '#f0e8e1',
                cornerRadius: 8,
                padding: 10,
                displayColors: false
              }
            },
            scales: {
              x: {
                ticks: {
                  color: '#551a25',
                  font: {
                    size: 14,
                    weight: '600'
                  },
                  maxRotation: 0,
                  autoSkip: false,
                  maxTicksLimit: 10
                },
                grid: {
                  display: false
                }
              },
              y: {
                beginAtZero: true,
                ticks: {
                  color: '#551a25',
                  font: {
                    size: 14,
                    weight: '600'
                  },
                  stepSize: 1
                },
                grid: {
                  color: 'rgba(215, 158, 75, 0.3)',
                  borderDash: [5, 5]
                }
              }
            }
          }
        });
      })
      .catch(err => console.error('Failed to load survey data:', err));
  }

  // Initial load
  fetchAndRenderChart('week');

  document.getElementById('filter').addEventListener('change', e => {
    fetchAndRenderChart(e.target.value);
  });
</script>

<div class="bg-white p-6 rounded-xl shadow-lg w-full mx-auto mt-8 mb-8">
  <div class="flex justify-between items-center mb-4">
    <h2 class="text-2xl font-bold text-[#551a25]">Students Without Assigned Section</h2>

    <!-- Search Input -->
    <div class="relative w-72">
      <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-500">
        <i class="fas fa-search"></i>
      </span>
      <input type="text" id="searchNoSection" placeholder="Search Student..."
        class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg shadow-sm focus:ring-[#551a25] focus:ring-2 focus:outline-none transition">
    </div>
  </div>

  <div class="overflow-x-auto">
    <table id="noSectionTable" class="min-w-full table-auto border-collapse">
      <thead>
        <tr class="bg-[#551a25] text-white">
          <th class="px-6 py-3 text-left">First Name</th>
          <th class="px-6 py-3 text-left">Last Name</th>
          <th class="px-6 py-3 text-left">Email</th>
          <th class="px-6 py-3 text-center">Action</th>
        </tr>
      </thead>
      <tbody>
        <?php if ($no_section_users->num_rows > 0): ?>
          <?php while ($user = $no_section_users->fetch_assoc()): ?>
            <tr class="even:bg-[#f0e8e1] odd:bg-white border-t hover:bg-gray-100 transition">
              <td class="px-6 py-3"><?= ucwords(htmlspecialchars($user['firstname'])) ?></td>
              <td class="px-6 py-3"><?= ucwords(htmlspecialchars($user['lastname'])) ?></td>
              <td class="px-6 py-3"><?= htmlspecialchars($user['email']) ?></td>
              <td class="px-6 py-3 text-center">
                <a href="edit_student.php?id=<?= $user['id'] ?>" 
                   class="bg-[#d79e4b] hover:bg-[#6e2c33] text-white py-1.5 px-4 rounded-lg text-sm font-semibold transition">
                  Assign Section
                </a>
              </td>
            </tr>
          <?php endwhile; ?>
        <?php else: ?>
          <tr>
            <td colspan="4" class="px-6 py-4 text-center text-gray-500 italic">All students are assigned to a section.</td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>




<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
  <?php foreach ($sections as $section): ?>
    <div class="bg-white p-6 rounded-3xl  shadow-sm hover:shadow-lg transition-all duration-300">

      <div class="flex items-center justify-between mb-4">
        <h2 class="text-2xl font-semibold text-[#551a25] transition-colors duration-200 group-hover:text-[#efac3a]">
          <?php echo htmlspecialchars($section['section_name']); ?>
        </h2>
      </div>

      <?php if (count($section['users']) === 0): ?>
        <div class="bg-green-50 border border-green-600 text-green-800 px-4 py-3 rounded-2xl text-center">
          <p class="text-sm font-medium">✅ All students have taken the survey this month.</p>
        </div>
      <?php else: ?>
        <div class="bg-[#fff5ec] border border-[#551a25] px-4 py-4 rounded-2xl text-[#551a25]">
          <div class="flex items-center justify-between">
            <p class="text-sm font-semibold">
              ❌ Students who haven't taken the survey
            </p>
            <span class=" text-[#efac3a] text-md font-semibold  rounded-full">
              <?php echo count($section['users']); ?> <?php echo count($section['users']) !== 1 ? 's' : ''; ?>
            </span>
          </div>
        </div>
      <?php endif; ?>

    </div>
  <?php endforeach; ?>
</div>

  <!-- Send OTP Button -->
  <?php
    // Check if there is at least one user without survey taken
    $hasUsersToNotify = false;
    foreach ($sections as $section) {
      if (count($section['users']) > 0) {
        $hasUsersToNotify = true;
        break;
      }
    }
  ?>
  <?php if ($hasUsersToNotify): ?>
    <form method="POST" class="text-center mt-6">
    <button type="submit" name="send_all_otp"
  class="bg-[#551a25] hover:bg-[#3f101a] text-white px-6 py-3 rounded-2xl text-base font-semibold shadow-md transition-all duration-300 ease-in-out focus:outline-none focus:ring-4 focus:ring-[#a25c64] focus:ring-opacity-50">
   <i class="fas fa-bell"></i>  Send Reminders
</button>

    </form>
  <?php else: ?>
    <p class="text-center text-green-600 font-semibold">All users have taken the survey this month.</p>
  <?php endif; ?>

  <?php if (isset($_SESSION['message'])): ?>
    <p class="text-center text-green-600 font-semibold mt-4"><?php echo $_SESSION['message']; unset($_SESSION['message']); ?></p>
  <?php endif; ?>
</div>
<?php if (isset($_SESSION['otp_send_status'])): ?>
<script>
  document.addEventListener('DOMContentLoaded', function() {
    const status = '<?php echo $_SESSION['otp_send_status']; ?>';

    if (status === 'success') {
      Swal.fire({
        icon: 'success',
        title: 'Success!',
        text: 'Reminders have been sent to all users who have not taken the survey.',
        confirmButtonColor: '#551a25',
      });
    } else if (status === 'failed') {
      Swal.fire({
        icon: 'error',
        title: 'Oops!',
        text: 'There was an error sending reminders. Please try again.',
        confirmButtonColor: '#551a25',
      });
    }
  });
</script>
<?php 
unset($_SESSION['otp_send_status']);
endif; 
?>

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

  </script>
<script>

 document.getElementById('searchNoSection').addEventListener('input', function() {
    const filter = this.value.toLowerCase();
    const table = document.getElementById('noSectionTable');
    const trs = table.tBodies[0].getElementsByTagName('tr');

    for (let i = 0; i < trs.length; i++) {
      const tds = trs[i].getElementsByTagName('td');
      const firstName = tds[0].textContent.toLowerCase();
      const lastName = tds[1].textContent.toLowerCase();
      const email = tds[2].textContent.toLowerCase();

      if (firstName.includes(filter) || lastName.includes(filter) || email.includes(filter)) {
        trs[i].style.display = '';
      } else {
        trs[i].style.display = 'none';
      }
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