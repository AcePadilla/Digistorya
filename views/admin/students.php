<?php
session_start();
include 'db_connection.php';
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

// FETCH ALL SECTIONS
$sectionQuery = $conn->query("SELECT id, section_name AS name FROM section");
$sections = $sectionQuery->fetch_all(MYSQLI_ASSOC);

// CREATE STUDENT
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['create_student'])) {
    $firstname = $_POST['firstname'];
    $lastname = $_POST['lastname'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $section_id = $_POST['section_id'];

    $stmt = $conn->prepare("INSERT INTO users (firstname, lastname, email, password, section_id) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssi", $firstname, $lastname, $email, $password, $section_id);
    $stmt->execute();
    header("Location: students.php");
    exit();
}

// DELETE STUDENT
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_student']) && isset($_POST['id'])) {
    $id = $_POST['id'];
    $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    header("Location: students.php");
    exit();
}

// Pagination, Sorting, Search
$perPage = 10;
$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$offset = ($page - 1) * $perPage;

$validSortColumns = ['firstname', 'lastname', 'email'];
$sort = in_array($_GET['sort'] ?? '', $validSortColumns) ? $_GET['sort'] : 'firstname';
$order = (isset($_GET['order']) && strtolower($_GET['order']) === 'desc') ? 'DESC' : 'ASC';
$nextOrder = $order === 'ASC' ? 'desc' : 'asc';

$search = $_GET['search'] ?? '';
$searchTerm = "%$search%";

// Total count for pagination
$queryCount = "SELECT COUNT(*) as total FROM users 
               WHERE firstname LIKE ? OR lastname LIKE ? OR email LIKE ?";
$stmtCount = $conn->prepare($queryCount);
$stmtCount->bind_param("sss", $searchTerm, $searchTerm, $searchTerm);
$stmtCount->execute();
$totalStudents = $stmtCount->get_result()->fetch_assoc()['total'];
$totalPages = ceil($totalStudents / $perPage);

// Fetch students with section name
$query = "SELECT users.*, section.section_name AS section_name
          FROM users 
          LEFT JOIN section ON users.section_id = section.id
          WHERE users.firstname LIKE ? OR users.lastname LIKE ? OR users.email LIKE ?
          ORDER BY $sort $order 
          LIMIT ? OFFSET ?";

$stmt = $conn->prepare($query);
$stmt->bind_param("sssii", $searchTerm, $searchTerm, $searchTerm, $perPage, $offset);
$stmt->execute();
$students = $stmt->get_result();
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DIGIstorya Admin</title>
    <link rel="icon" href="../../images/logo.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <script src="https://kit.fontawesome.com/aed89df169.js" crossorigin="anonymous"></script>
    <link href="https://fonts.googleapis.com/css2?family=Island+Moments&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

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
        transform: tranzslateY(0);
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


  <!-- Main Content -->
<div id="mainContent" class="flex-1 ml-64 p-8 overflow-y-auto transition-all duration-300 bg-[#f0e8e1]">
  <div class="flex items-center justify-between mb-6">
    <h1 class="text-4xl font-bold text-[#551a25]">Students</h1>
  
  </div>


<!-- Students Table -->
    <div class="bg-white p-6 rounded-xl shadow-lg w-full mx-auto">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-2xl font-bold text-[#551a25]">Student List</h2>

            <!-- Styled Search Input -->
            <div class="relative w-72">
            <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-500">
                <i class="fas fa-search"></i>
            </span>
            <input type="text" id="searchInput" placeholder="Search Student..." 
                    class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg shadow-sm focus:ring-[#551a25] focus:ring-2 focus:outline-none transition">
            </div>
        </div>

    <table class="min-w-full table-auto border-collapse">
    <thead>
    <tr class="bg-[#551a25] text-white">
        <th class="px-6 py-3 text-left">
            <a href="?sort=firstname&order=<?= $nextOrder ?>&search=<?= $search ?>&page=<?= $page ?>" class="hover:underline">
                First Name <?= ($_GET['sort'] ?? '') === 'firstname' ? ($order === 'ASC' ? '▲' : '▼') : '▲▼' ?>
            </a>
        </th>
        <th class="px-6 py-3 text-left">
            <a href="?sort=lastname&order=<?= $nextOrder ?>&search=<?= $search ?>&page=<?= $page ?>" class="hover:underline">
                Last Name <?= ($_GET['sort'] ?? '') === 'lastname' ? ($order === 'ASC' ? '▲' : '▼') : '▲▼' ?>
            </a>
        </th>
        <th class="px-6 py-3 text-left">
            <a href="?sort=email&order=<?= $nextOrder ?>&search=<?= $search ?>&page=<?= $page ?>" class="hover:underline">
                Email <?= ($_GET['sort'] ?? '') === 'email' ? ($order === 'ASC' ? '▲' : '▼') : '▲▼' ?>
            </a>
        </th>
        <th class="px-6 py-3 text-left">
          <a href="?sort=section_name&order=<?= $nextOrder ?>&search=<?= $search ?>&page=<?= $page ?>" class="hover:underline">
            Section <?= ($_GET['sort'] ?? '') === 'section_name' ? ($order === 'ASC' ? '▲' : '▼') : '▲▼' ?>
          </a>
        </th>
        <th class="px-6 py-3 text-center">Actions</th>
    </tr>
    </thead>

        <tbody>
        <?php $count = 1; while($row = $students->fetch_assoc()): ?>
        <tr class="even:bg-[#f0e8e1] odd:bg-white border-t hover:bg-gray-100 transition">
            <td class="px-6 py-3"><?= htmlspecialchars($row['firstname']) ?></td>
            <td class="px-6 py-3"><?= htmlspecialchars($row['lastname']) ?></td>
            <td class="px-6 py-3"><?= htmlspecialchars($row['email']) ?></td>
            <td class="px-6 py-3"><?= htmlspecialchars($row['section_name'] ?? 'N/A') ?></td>
            <td class="px-6 py-4 text-center">
            <div class="flex justify-center space-x-4">
                <!-- Edit button -->
                <a href="edit_student.php?id=<?= $row['id'] ?>" class="text-[#d79e4b] hover:text-[#6e2c33] transition">
                <i class="fas fa-edit text-lg" title="Edit"></i>
                </a>

                <!-- Delete button -->
                <form method="POST" class="delete-student-form">
                    <input type="hidden" name="id" value="<?= $row['id'] ?>">
                    <input type="hidden" name="delete_student" value="1">
                    <button type="button" class="text-red-500 hover:text-red-700 transition delete-student-btn">
                        <i class="fas fa-trash-alt text-lg" title="Delete"></i>
                    </button>
                </form>
                </div>
            </td>
        </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
    <div class="flex justify-center mt-6 space-x-2">
        <?php if ($page > 1): ?>
            <a href="?page=<?= $page - 1 ?>&sort=<?= $sort ?>&order=<?= strtolower($order) ?>&search=<?= $search ?>" class="px-4 py-2 bg-[#551a25] text-white rounded hover:bg-[#6e2c33] transition">Previous</a>
        <?php endif; ?>

        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
            <a href="?page=<?= $i ?>&sort=<?= $sort ?>&order=<?= strtolower($order) ?>&search=<?= $search ?>" class="px-4 py-2 <?= $i == $page ? 'bg-[#6e2c33]' : 'bg-[#551a25]' ?> text-white rounded hover:bg-[#6e2c33] transition"><?= $i ?></a>
        <?php endfor; ?>

        <?php if ($page < $totalPages): ?>
            <a href="?page=<?= $page + 1 ?>&sort=<?= $sort ?>&order=<?= strtolower($order) ?>&search=<?= $search ?>" class="px-4 py-2 bg-[#551a25] text-white rounded hover:bg-[#6e2c33] transition">Next</a>
        <?php endif; ?>
    </div>
</div>
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
  
    const searchInput = document.getElementById('searchInput');

    searchInput.addEventListener('keydown', function (event) {
        if (event.key === 'Enter') {
            event.preventDefault();  // Prevents the default action (form submission if in a form)
            const searchValue = this.value.trim();
            
            // Redirect to the same page with the search parameter
            window.location.href = `?search=${encodeURIComponent(searchValue)}&page=1`;  // You can also keep other parameters (sort, order)
        }
    });


    document.querySelectorAll('.delete-student-btn').forEach(button => {
    button.addEventListener('click', function () {
        const form = this.closest('form');

        Swal.fire({
            title: 'Are you sure?',
            text: "This student will be deleted permanently.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, delete it!',
            reverseButtons: true
        }).then((result) => {
            if (result.isConfirmed) {
                form.submit();
            }
        });
    });
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