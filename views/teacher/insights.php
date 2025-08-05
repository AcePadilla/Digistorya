<?php
session_start();
include 'db_connection.php';

if (!isset($_SESSION['teacher_email'])) {
    header("Location: login.php");
    exit();
}

$teacherEmail = $_SESSION['teacher_email'];

// Get teacher ID first (assuming table 'teachers' has email column)
$stmt = $conn->prepare("SELECT id FROM teachers WHERE email = ?");
$stmt->bind_param("s", $teacherEmail);
$stmt->execute();
$stmt->bind_result($teacherId);
$stmt->fetch();
$stmt->close();

if (!$teacherId) {
    // No teacher found, block access
    die("Teacher not found.");
}

// Get assigned sections for this teacher (assuming 'teacher_section' table with teacher_id, section_id)
$stmt = $conn->prepare("SELECT section_id FROM teacher_section WHERE teacher_id = ?");
$stmt->bind_param("i", $teacherId);
$stmt->execute();
$result = $stmt->get_result();

$assignedSections = [];
while ($row = $result->fetch_assoc()) {
    $assignedSections[] = $row['section_id'];
}
$stmt->close();

if (empty($assignedSections)) {
    die("No sections assigned to this teacher.");
}

// Prepare placeholders for IN clause
$placeholders = implode(',', array_fill(0, count($assignedSections), '?'));

// Build main SQL with WHERE u.section_id IN (...)
$sql = "
    SELECT 
        u.firstname, u.lastname, u.gender,
        sr.active_reflective, sr.sensing_intuitive, 
        sr.visual_verbal, sr.sequential_global,
        u.section_id,
        qs.score AS exercise_score
    FROM users u
    JOIN (
        SELECT qr.*
        FROM quiz_results qr
        INNER JOIN (
            SELECT user_id, MAX(created_at) AS latest
            FROM quiz_results
            GROUP BY user_id
        ) latest_qr 
        ON qr.user_id = latest_qr.user_id AND qr.created_at = latest_qr.latest
    ) sr ON u.id = sr.user_id
    LEFT JOIN (
        SELECT user_id, MAX(submission_date) AS latest_submission, score
        FROM quiz_submissions
        GROUP BY user_id
    ) qs ON u.id = qs.user_id
    WHERE u.section_id IN ($placeholders)
    ORDER BY u.section_id, u.lastname, u.firstname
";

// Prepare statement dynamically with section IDs as parameters
$stmt = $conn->prepare($sql);

// Dynamically bind the section IDs as integers
$types = str_repeat('i', count($assignedSections));
$stmt->bind_param($types, ...$assignedSections);

$stmt->execute();
$result = $stmt->get_result();

$data = [];
$totals = [
    'Active' => 0, 'Reflective' => 0,
    'Sensing' => 0, 'Intuitive' => 0,
    'Visual' => 0, 'Verbal' => 0,
    'Sequential' => 0, 'Global' => 0
];

// Gender-separated totals
$genderTotals = [
    'Male' => [
        'Active' => 0, 'Reflective' => 0,
        'Sensing' => 0, 'Intuitive' => 0,
        'Visual' => 0, 'Verbal' => 0,
        'Sequential' => 0, 'Global' => 0
    ],
    'Female' => [
        'Active' => 0, 'Reflective' => 0,
        'Sensing' => 0, 'Intuitive' => 0,
        'Visual' => 0, 'Verbal' => 0,
        'Sequential' => 0, 'Global' => 0
    ]
];

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $gender = ucfirst(strtolower($row['gender'] ?? 'Unknown'));
        $active_reflective = explode('|', $row['active_reflective']);
        $sensing_intuitive = explode('|', $row['sensing_intuitive']);
        $visual_verbal = explode('|', $row['visual_verbal']);
        $sequential_global = explode('|', $row['sequential_global']);

        $totals['Active'] += (int)$active_reflective[0];
        $totals['Reflective'] += (int)$active_reflective[1];
        $totals['Sensing'] += (int)$sensing_intuitive[0];
        $totals['Intuitive'] += (int)$sensing_intuitive[1];
        $totals['Visual'] += (int)$visual_verbal[0];
        $totals['Verbal'] += (int)$visual_verbal[1];
        $totals['Sequential'] += (int)$sequential_global[0];
        $totals['Global'] += (int)$sequential_global[1];

        if (isset($genderTotals[$gender])) {
            $genderTotals[$gender]['Active'] += (int)$active_reflective[0];
            $genderTotals[$gender]['Reflective'] += (int)$active_reflective[1];
            $genderTotals[$gender]['Sensing'] += (int)$sensing_intuitive[0];
            $genderTotals[$gender]['Intuitive'] += (int)$sensing_intuitive[1];
            $genderTotals[$gender]['Visual'] += (int)$visual_verbal[0];
            $genderTotals[$gender]['Verbal'] += (int)$visual_verbal[1];
            $genderTotals[$gender]['Sequential'] += (int)$sequential_global[0];
            $genderTotals[$gender]['Global'] += (int)$sequential_global[1];
        }

        $dominantStyles = [
            'Active vs Reflective' => $active_reflective[0] > $active_reflective[1] ? 'Active' : 'Reflective',
            'Sensing vs Intuitive' => $sensing_intuitive[0] > $sensing_intuitive[1] ? 'Sensing' : 'Intuitive',
            'Visual vs Verbal' => $visual_verbal[0] > $visual_verbal[1] ? 'Visual' : 'Verbal',
            'Sequential vs Global' => $sequential_global[0] > $sequential_global[1] ? 'Sequential' : 'Global',
        ];
        $exerciseScore = $row['exercise_score'] ?? 'N/A'; 

        $data[] = [
    'name' => $row['firstname'] . ' ' . $row['lastname'],
    'gender' => $gender,
    'scores' => array_merge(
        $active_reflective,
        $sensing_intuitive,
        $visual_verbal,
        $sequential_global
    ),
    'dominant' => $dominantStyles,  // <-- add comma here
    'exercise_score' => $exerciseScore
];

    }
}

$overallStyles = [];
foreach (['Active', 'Reflective', 'Sensing', 'Intuitive', 'Visual', 'Verbal', 'Sequential', 'Global'] as $style) {
    $overallStyles[$style] = $totals[$style];
}

arsort($overallStyles);
$dominantOverall = array_slice(array_keys($overallStyles), 0, 4);
$lessDominantOverall = array_slice(array_keys($overallStyles), -4);


$suggestions = [
    'Active' => [
        'tips' => [
            "Use group activities such as debates, team projects, and peer teaching to keep learners engaged.",
            "Incorporate interactive tasks like role-playing and problem-solving sessions to help learners learn by doing.",
            "Encourage physical involvement through hands-on experiments and fieldwork.",
            "Include frequent opportunities for movement breaks during lessons to sustain energy and focus."
        ],
        'decision' => "Prioritize hands-on and social learning opportunities; avoid long passive lectures and instead keep learners physically and mentally active."
    ],
    'Reflective' => [
        'tips' => [
            "Provide time for individual thinking, journaling, and self-assessment.",
            "Encourage learners to pause and reflect on what they’ve learned before moving on.",
            "Use prompts for self-questioning and assign reflective essays or portfolios.",
            "Allow quiet time after activities for processing and integration."
        ],
        'decision' => "Balance active discussions with quiet reflective periods; avoid rushing students and encourage depth over speed."
    ],
    'Sensing' => [
        'tips' => [
            "Incorporate concrete facts, real-world examples, and demonstrations.",
            "Use structured assignments with clear, step-by-step instructions.",
            "Provide case studies and encourage detailed note-taking.",
            "Incorporate hands-on practice to apply theoretical concepts."
        ],
        'decision' => "Focus on clear, practical, and detailed explanations; use tangible examples rather than abstract theories."
    ],
    'Intuitive' => [
        'tips' => [
            "Focus on theories, abstract concepts, and innovation-based tasks.",
            "Use open-ended questions to encourage exploration and critical thinking.",
            "Include brainstorming sessions and creative problem-solving activities.",
            "Encourage learners to make connections between ideas and generate hypotheses."
        ],
        'decision' => "Provide space for creativity and big-picture thinking; avoid overly rigid structures and promote exploration."
    ],
    'Visual' => [
        'tips' => [
            "Use diagrams, flowcharts, videos, infographics, and mind maps.",
            "Incorporate color coding and spatial organization in teaching materials.",
            "Use slideshows and visual summaries to recap key points.",
            "Encourage students to create their own visual notes or sketches."
        ],
        'decision' => "Maximize use of visual aids and encourage learners to represent information graphically."
    ],
    'Verbal' => [
        'tips' => [
            "Use spoken and written explanations, lectures, storytelling, and discussions.",
            "Provide reading materials and word-based exercises like summaries and debates.",
            "Encourage students to explain concepts verbally or write about them.",
            "Incorporate oral presentations and group discussions."
        ],
        'decision' => "Emphasize language-based activities; avoid purely visual or hands-on methods alone."
    ],
    'Sequential' => [
        'tips' => [
            "Present material in clear, logical, step-by-step order.",
            "Use outlines, checklists, and progress trackers.",
            "Build new concepts gradually on previous knowledge.",
            "Summarize key points regularly to reinforce structure."
        ],
        'decision' => "Organize lessons carefully to maintain a clear flow; avoid jumping between unrelated topics."
    ],
    'Global' => [
        'tips' => [
            "Give overviews before diving into details.",
            "Use concept maps and relate topics to the big picture.",
            "Allow freedom to explore topics holistically and interdisciplinarily.",
            "Use project-based learning that integrates multiple subjects."
        ],
        'decision' => "Start lessons with broad context and allow flexible exploration; avoid overly rigid sequencing."
    ],
];

$dominantSuggestions = array_map(fn($s) => $suggestions[$s], $dominantOverall);
$lessDominantSuggestions = array_map(fn($s) => $suggestions[$s], $lessDominantOverall);
// The desired order of keys
$order = array_keys($suggestions);

// Sort $lessDominantOverall based on the order in $suggestions
usort($lessDominantOverall, function($a, $b) use ($order) {
    return array_search($a, $order) - array_search($b, $order);
});


$percentageMale = [];
$percentageFemale = [];

foreach ($totals as $key => $totalValue) {
    $male = $genderTotals['Male'][$key] ?? 0;
    $female = $genderTotals['Female'][$key] ?? 0;
    $percentageMale[$key] = $totalValue > 0 ? round(($male / $totalValue) * 100, 2) : 0;
    $percentageFemale[$key] = $totalValue > 0 ? round(($female / $totalValue) * 100, 2) : 0;
}

$balancedCount = 0;
$unbalancedCount = 0;

foreach ($data as $user) {
    $scores = $user['scores'];
    $balancedPairs = 0;

    if (abs($scores[0] - $scores[1]) <= 1) $balancedPairs++; // Active vs Reflective
    if (abs($scores[2] - $scores[3]) <= 1) $balancedPairs++; // Sensing vs Intuitive
    if (abs($scores[4] - $scores[5]) <= 1) $balancedPairs++; // Visual vs Verbal
    if (abs($scores[6] - $scores[7]) <= 1) $balancedPairs++; // Sequential vs Global

    if ($balancedPairs === 4) {
        $balancedCount++;
    } else {
        $unbalancedCount++;
    }
}
$sql = "
    SELECT 
        DATE_FORMAT(created_at, '%Y') AS year,
        DATE_FORMAT(created_at, '%m') AS month,
        active_reflective,
        sensing_intuitive,
        visual_verbal,
        sequential_global
    FROM quiz_results qr
    INNER JOIN (
        SELECT user_id, MAX(created_at) AS latest
        FROM quiz_results
        GROUP BY user_id, DATE_FORMAT(created_at, '%Y-%m')
    ) latest_qr 
    ON qr.user_id = latest_qr.user_id AND qr.created_at = latest_qr.latest
    ORDER BY year ASC, month ASC
";

$stmt = $conn->prepare($sql);
$stmt->execute();
$result = $stmt->get_result();

$yearlyData = [];

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $year = $row['year'];
        $month = (int)$row['month'];

        $ar = explode('|', $row['active_reflective']);
        $si = explode('|', $row['sensing_intuitive']);
        $vv = explode('|', $row['visual_verbal']);
        $sg = explode('|', $row['sequential_global']);

        $balancedCount = 0;
        if (abs($ar[0] - $ar[1]) <= 1) $balancedCount++;
        if (abs($si[0] - $si[1]) <= 1) $balancedCount++;
        if (abs($vv[0] - $vv[1]) <= 1) $balancedCount++;
        if (abs($sg[0] - $sg[1]) <= 1) $balancedCount++;

        $isBalanced = $balancedCount >= 3;

        if (!isset($yearlyData[$year])) {
            $yearlyData[$year] = [
                'balanced' => array_fill(1, 12, 0),
                'unbalanced' => array_fill(1, 12, 0)
            ];
        }

        if ($isBalanced) {
            $yearlyData[$year]['balanced'][$month]++;
        } else {
            $yearlyData[$year]['unbalanced'][$month]++;
        }
    }
}

echo "<script>const yearlyCompareData = " . json_encode($yearlyData) . ";</script>";

$stmt->close();
$conn->close();
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DIGIstorya</title>
    <link rel="icon" href="../../images/logo.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://kit.fontawesome.com/aed89df169.js" crossorigin="anonymous"></script>
    <link href="https://fonts.googleapis.com/css2?family=Island+Moments&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>

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
        transform: tranzslateY(0);
      }
    }
  </style>
</head>
<body class="flex bg-[#f0e8e1] h-screen overflow-hidden">

  <!-- Sidebar -->
  <aside id="sidebar" class="sidebar w-64 bg-[#551a25] text-white fixed h-full overflow-y-auto z-40">
      <!-- Toggle Button -->
  <button id="toggleSidebar" class="sidebar-toggle-btn">☰</button>

  <div class="logo">
  <!-- Full logo for expanded -->
  <img src="../../images/logo.png" alt="Logo" class="logo-full w-36 h-36 mx-auto rounded-full  transition-all duration-300">


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



  <!-- Main Content -->
<div id="mainContent" class="flex-1 ml-64 p-8 overflow-y-auto transition-all duration-300 bg-[#f0e8e1]">
  <!-- Then display suggestions somewhere in your HTML below -->
  <div class="flex items-center justify-between mb-6">
    <h1 class="text-4xl font-bold text-[#551a25]">Students</h1>
  </div>
<div class="mt-10 mb-16 max-w-7xl mx-auto px-6">
    <h3 class="text-3xl font-bold mb-4 text-[#d79e4b] text-center tracking-tight">Areas for Growth: Less Dominant Learning Styles</h3>
    <p class="mb-10 text-[#551a25] max-w-2xl mx-auto text-center text-lg leading-relaxed">
        These are the learning styles where your students show less strength. Use the guidance below to adjust your teaching strategies and create a more inclusive learning environment.
    </p>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-10">
        <?php 
        $stylesToShow = array_slice($lessDominantOverall, 0, 4);
        foreach ($stylesToShow as $style): ?>
            <div class="relative bg-[#551a25] border-l-8 border-[#d79e4b] rounded-2xl p-6 shadow-md hover:shadow-xl transition-all duration-300 group">
                <div class="absolute -top-4 left-4 bg-[#d79e4b] text-white text-sm font-bold px-3 py-1 rounded-full shadow-md uppercase tracking-wide">
                    <?= htmlspecialchars($style) ?>
                </div>

                <div class="mt-6">
                    <h5 class="font-semibold text-[#f0e8e1] text-lg mb-3">Teaching Suggestions</h5>
                    <ul class="list-disc list-inside text-white/90 space-y-2">
                        <?php foreach ($suggestions[$style]['tips'] as $tip): ?>
                            <li><?= htmlspecialchars($tip) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>

                <div class="mt-5">
                    <h5 class="font-semibold text-[#f0e8e1] text-lg mb-3">Decision-Making Guidance</h5>
                    <p class="text-white/80 text-base leading-relaxed border-l-4 border-[#d79e4b] pl-4">
                        <?= htmlspecialchars($suggestions[$style]['decision']) ?>
                    </p>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>


    <?php if (!empty($data)): ?>
    <div class="bg-white p-6 rounded shadow mb-10">
    <div id="printSection">
    <h1 class="text-2xl font-bold text-[#551a25] ml-4 "><i class="fas fa-chart-line mr-2" style="color: #d79e4b;"></i> Overall Learning Styles of Students</h1>
  <p class="text-gray-600 mb-4 text-sm ml-4">
    This report shows the number of learning style surveys taken by students, helping track engagement and trends.
  </p>

     <canvas id="genderChart" height="100"></canvas>
<script>
    const genderCtx = document.getElementById('genderChart').getContext('2d');
    new Chart(genderCtx, {
        type: 'bar',
        data: {
            labels: ['Active', 'Reflective', 'Sensing', 'Intuitive', 'Visual', 'Verbal', 'Sequential', 'Global'],
            datasets: [
                {
                    label: 'Total',
                    data: <?php echo json_encode(array_values($totals)); ?>,
                    backgroundColor: '#d79e4b'
                },
                {
                    label: 'Male',
                    data: <?php echo json_encode(array_values($genderTotals['Male'] ?? [])); ?>,
                    backgroundColor: '#1e3a8a',
                    percentage: <?php echo json_encode(array_values($percentageMale)); ?>
                },
                {
                    label: 'Female',
                    data: <?php echo json_encode(array_values($genderTotals['Female'] ?? [])); ?>,
                    backgroundColor: '#f43f5e',
                    percentage: <?php echo json_encode(array_values($percentageFemale)); ?>
                }
            ]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    display: true,
                    position: 'top'
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const label = context.dataset.label || '';
                            const value = context.formattedValue;
                            const percentage = context.dataset.percentage?.[context.dataIndex];
                            if (label === 'Male' || label === 'Female') {
                                return `${label}: ${value} (${percentage}%)`;
                            }
                            return `${label}: ${value}`;
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Count'
                    }
                }
            }
        }
    });
</script>


<div class="flex flex-wrap md:flex-nowrap gap-6 px-8 py-10 bg-gray-50 min-h-[420px]">
  <!-- Pie Chart Section -->
  <section
    class="w-full md:w-1/2 bg-white rounded-2xl p-6 flex flex-col justify-between
           hover:shadow-2xl transition-shadow duration-300"
  >
    <header class="mb-5 flex items-center gap-3">
      <i class="fas fa-chart-pie text-3xl" style="color: #d79e4b;"></i>
      <h1 class="text-3xl font-extrabold text-[#551a25] tracking-wide leading-tight">
        Learning Balance Overview
      </h1>
    </header>
    <p class="text-gray-600 text-sm mb-6 leading-relaxed max-w-prose">
      This chart shows all of the students categorized as balanced or unbalanced learners based on their survey responses.
    </p>

    <div class="flex justify-center items-center flex-grow max-h-[300px]">
      <canvas
        id="balanceChart"
        class="w-full max-w-md h-[280px]"
        aria-label="Pie chart showing balanced vs unbalanced learners"
        role="img"
      ></canvas>
    </div>
  </section>

  <!-- Line Chart Section -->
  <section
    class="w-full md:w-1/2 bg-white  rounded-2xl p-6 flex flex-col justify-between
           hover:shadow-2xl transition-shadow duration-300"
  >
    <header class="mb-5 flex items-center gap-3">
      <i class="fas fa-chart-line text-3xl" style="color: #d79e4b;"></i>
      <h2 class="text-3xl font-extrabold text-[#551a25] tracking-wide leading-tight">
        Balanced vs Unbalanced Learners
      </h2>
    </header>
 
    <div class="flex justify-center items-center flex-grow">
      <canvas
        id="compareBalancedTrend"
        class="w-full max-w-xl h-[360px]"
        aria-label="Line chart comparing balanced and unbalanced learners monthly trend"
        role="img"
      ></canvas>
    </div>
  </section>
</div>

<script>
   const ctx = document.getElementById('compareBalancedTrend').getContext('2d');
const labels = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

const datasets = [];

for (const year in yearlyCompareData) {
    const data = yearlyCompareData[year];

    datasets.push({
        label: `Balanced - ${year}`,
        data: labels.map((_, i) => data.balanced[i + 1] || 0),
        borderColor: '#d79e4b',      // GOLDEN BROWN for Balanced
        backgroundColor: '#d79e4b33', // translucent for fill (optional)
        tension: 0.4,
        pointRadius: 4,
        fill: false,
    });

    datasets.push({
        label: `Unbalanced - ${year}`,
        data: labels.map((_, i) => data.unbalanced[i + 1] || 0),
        borderColor: '#551a25',      // DARK MAROON for Unbalanced
        backgroundColor: '#551a2533',
        borderDash: [5, 5],          // dashed line
        tension: 0.4,
        pointRadius: 4,
        fill: false,
    });
}

new Chart(ctx, {
    type: 'line',
    data: {
        labels: labels,
        datasets: datasets
    },
    options: {
        responsive: true,
        plugins: {
            legend: { position: 'top' },
            title: {
                display: true,
                text: 'Monthly Comparison of Balanced vs Unbalanced Learners by Year',
                font: { size: 18 }
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                title: { display: true, text: 'Number of Learners' }
            },
            x: {
                title: { display: true, text: 'Month' }
            }
        }
    }
});

</script>

<script>
  const balanceCtx = document.getElementById('balanceChart').getContext('2d');
new Chart(balanceCtx, {
  type: 'pie',
  data: {
    labels: ['Balanced Learners', 'Unbalanced Learners'],
    datasets: [{
      data: [<?= $balancedCount; ?>, <?= $unbalancedCount; ?>],
      backgroundColor: ['#551a25', '#d79e4b']
    }]
  },
  options: {
    responsive: true,
    plugins: {
      legend: {
        display: true,
        position: 'top',
        labels: {
          color: '#333',
          font: { size: 14 }
        }
      },
      tooltip: {
        callbacks: {
          label: function(context) {
            const dataset = context.dataset;
            const total = dataset.data.reduce((a, b) => a + b, 0);
            const currentValue = context.parsed;
            const percentage = total ? ((currentValue / total) * 100).toFixed(2) : 0;
            return percentage + '%';
          }
        }
      }
    }
  }
});

</script>
<?php endif; ?>
<?php
$styleKeys = [];
foreach ($data as $student) {
    foreach ($student['dominant'] as $key => $value) {
        $styleKeys[$key] = true;
    }
}
$styleKeys = array_keys($styleKeys);
?>
</div>
   
<div class="overflow-x-auto  shadow-lg">
  <table id="resultTable" class="min-w-full text-sm text-left border-collapse">
    <thead style="background-color: #551a25;" class="text-white uppercase">
        <tr>
            <th class="px-6 py-4 border-b border-[#f0e8e1]">#</th>
            <th class="px-6 py-4 border-b border-[#f0e8e1]">Student Name</th>
            <?php foreach ($styleKeys as $styleKey): ?>
                <th class="px-6 py-4 border-b border-[#f0e8e1]"><?php echo htmlspecialchars($styleKey); ?></th>
            <?php endforeach; ?>
            <th class="px-6 py-4 border-b border-[#f0e8e1]">Exercise Score</th>
        </tr>
    </thead>
    <tbody style="background-color: #f0e8e1; color: #551a25;">
        <?php foreach ($data as $index => $student): ?>
            <tr class="even:bg-[#f0e8e1] odd:bg-white border-t hover:bg-gray-100 transition">
                <td class="px-6 py-4 border-b border-[#d9cfc7] font-medium"><?php echo $index + 1; ?></td>
                <td class="px-6 py-4 border-b border-[#d9cfc7]"><?php echo htmlspecialchars($student['name']); ?></td>
                <?php foreach ($styleKeys as $styleKey): ?>
                    <td class="px-6 py-4 border-b border-[#d9cfc7]">
                        <?php echo isset($student['dominant'][$styleKey]) ? htmlspecialchars($student['dominant'][$styleKey]) : 'N/A'; ?>
                    </td>
                <?php endforeach; ?>
                <td class="px-6 py-4 border-b border-[#d9cfc7]">
    <?= htmlspecialchars($student['exercise_score'] ?? 'N/A') ?>
</td>

            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

</div>

</div>
<div class="text-right mt-6">
<button onclick="printCharts()" class="mb-6 px-4 py-2 bg-[#551a25] text-white rounded hover:bg-[#6e2a36] transition">🖨️ Print</button>

</div>


    <?php if (empty($data)): ?>
        <p class="text-center text-red-600 font-semibold">No survey results found.</p>
    <?php endif; ?>
</body>
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
function printCharts() {
  const section = document.getElementById('printSection');
  html2canvas(section).then(canvas => {
    const imageData = canvas.toDataURL('image/png');
    const printWindow = window.open('', '_blank', 'width=800,height=600');
    printWindow.document.write(`
      <html>
        <head>
          <title>Print Charts</title>
          <style>
            body { text-align: center; margin: 0; padding: 20px; }
            img { max-width: 100%; height: auto; }
          </style>
        </head>
        <body>
          <img src="${imageData}" />
        </body>
      </html>
    `);
    printWindow.document.close();
    printWindow.focus();
    setTimeout(() => {
      printWindow.print();
      printWindow.close();
    }, 500);
  });
}
</script>
<script>
(function(){if(!window.chatbase||window.chatbase("getState")!=="initialized"){window.chatbase=(...arguments)=>{if(!window.chatbase.q){window.chatbase.q=[]}window.chatbase.q.push(arguments)};window.chatbase=new Proxy(window.chatbase,{get(target,prop){if(prop==="q"){return target.q}return(...args)=>target(prop,...args)}})}const onLoad=function(){const script=document.createElement("script");script.src="https://www.chatbase.co/embed.min.js";script.id="F_d1NAFthV9gdiqKlr7ff";script.domain="www.chatbase.co";document.body.appendChild(script)};if(document.readyState==="complete"){onLoad()}else{window.addEventListener("load",onLoad)}})();
</script>
</html>
