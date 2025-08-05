<?php
session_start();
include 'db_connection.php'; 
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}
// CREATE SECTION
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['create_section'])) {
    $section_name = $_POST['section_name'];

    $stmt = $conn->prepare("INSERT INTO section (section_name) VALUES (?)");
    $stmt->bind_param("s", $section_name);
    $stmt->execute();
}

if (isset($_POST['delete_section'])) {
  $sectionId = $_POST['id'];
  $stmt = $conn->prepare("DELETE FROM section WHERE id = ?");
  $stmt->bind_param("i", $sectionId);
  if ($stmt->execute()) {
      header("Location: section.php"); 
      exit();
  } else {
      echo "Error: " . $conn->error;
  }
}


// Pagination and Sorting
$perPage = 10;
$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$offset = ($page - 1) * $perPage;

$validSortColumns = ['section_name'];
$sort = in_array($_GET['sort'] ?? '', $validSortColumns) ? $_GET['sort'] : 'section_name';
$order = (isset($_GET['order']) && strtolower($_GET['order']) === 'desc') ? 'DESC' : 'ASC';
$nextOrder = $order === 'ASC' ? 'desc' : 'asc';

// Count total sections
$result = $conn->query("SELECT COUNT(*) as total FROM section");
if ($result) {
    $totalSections = $result->fetch_assoc()['total'];
    $totalPages = ceil($totalSections / $perPage);
} else {
    $totalSections = 0;
    $totalPages = 1;
}

// EDIT SECTION
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['edit_section'])) {
  $section_id = $_POST['section_id'];
  $new_section_name = $_POST['new_section_name'];

  $stmt = $conn->prepare("UPDATE section SET section_name = ? WHERE id = ?");
  if ($stmt) {
      $stmt->bind_param("si", $new_section_name, $section_id);
      $stmt->execute();
      header("Location: section.php");
      exit();
  } else {
      echo "Error: " . $conn->error;
  }
}
// Fetch sorted and paginated sections
$query = "SELECT * FROM section ORDER BY $sort $order LIMIT ?, ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $offset, $perPage);
$stmt->execute();
$sections = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>DIGIstorya Admin</title>
  <link rel="icon" href="../../images/logo.png">
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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
<div id="mainContent" class="flex-1 ml-64 p-8 overflow-y-auto bg-[#f0e8e1]">

  <div class="flex items-center justify-between mb-6">
    <h1 class="text-4xl font-bold text-[#551a25]">Sections</h1>
  </div>

  <!-- ADD SECTION FORM -->
  <div class="bg-white p-6 rounded-lg shadow-lg mb-8">
    <h2 class="text-2xl font-bold mb-4 text-[#551a25]">Add New Section</h2>
    <form method="POST" class="flex flex-col gap-4">
      <div>
        <label class="block mb-1 text-sm font-semibold text-gray-600">Section Name</label>
        <input type="text" name="section_name" required class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:border-[#551a25] focus:ring-2 focus:ring-[#551a25]">
      </div>
      <button type="submit" name="create_section" class="w-24 bg-[#551a25] text-white py-3 rounded-lg hover:bg-[#6e2c33] transition duration-300">
       </i>Create
      </button>

    </form>
  </div>
<!-- SECTIONS TABLE -->
<div class="bg-white p-6 rounded-lg shadow-lg">
  <div class="flex justify-between items-center mb-4">
    <h2 class="text-2xl font-bold text-[#551a25]">Section List</h2>

    <!-- Search bar -->
    <div class="relative w-72">
      <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-500">
        <i class="fas fa-search"></i>
      </span>
      <input type="text" id="searchInput" placeholder="Search Section..." 
             class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg shadow-sm focus:ring-[#551a25] focus:ring-2 focus:outline-none transition">
    </div>
  </div>

  <table class="w-full table-auto">
    <thead>
      <tr class="bg-[#551a25] text-white">
        <th class="p-3 text-left">
          <a href="?sort=section_name&order=<?= $nextOrder ?>" class="hover:underline">
            Section Name
          </a>
        </th>
        <th class="p-3 text-center">Action</th>
      </tr>
    </thead>
    <tbody id="sectionTable">
      <?php while ($row = $sections->fetch_assoc()): ?>
        <tr class="even:bg-[#f0e8e1] odd:bg-white border-t hover:bg-gray-100 transition">
          <td class="p-3"><?= htmlspecialchars($row['section_name']) ?></td>
          <td class="p-3 text-center">
            <div class="flex justify-center gap-4">
              <!-- Edit Button -->
              <a href="edit_section.php?id=<?= $row['id'] ?>" 
                class="text-[#d79e4b] hover:text-[#6e2c33] transition" title="Edit">
                <i class="fas fa-edit text-lg"></i>
              </a>

              <form method="POST" class="delete-section-form">
                <input type="hidden" name="id" value="<?= $row['id'] ?>"> <!-- Section ID to delete -->
                <input type="hidden" name="delete_section" value="1">
                <button type="submit" class="text-red-500 hover:text-red-700 transition delete-section-btn">
                  <i class="fas fa-trash-alt text-lg" title="Delete"></i>
                </button>
              </form>       
            </div>
          </td>
        </tr>
      <?php endwhile; ?>
    </tbody>
  </table>

  <!-- Pagination -->
  <div class="mt-6 flex justify-center space-x-2">
    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
      <a href="?page=<?= $i ?>&sort=<?= $sort ?>&order=<?= $order ?>" 
         class="px-4 py-2 rounded-lg border <?= $i == $page ? 'bg-[#551a25] text-white' : 'bg-white text-[#551a25] border-[#551a25]' ?> hover:bg-[#6e2c33] hover:text-white transition">
        <?= $i ?>
      </a>
    <?php endfor; ?>
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
const tableRows = document.querySelectorAll('#sectionTable tr');

searchInput.addEventListener('keyup', function() {
  const searchTerm = this.value.toLowerCase();
  tableRows.forEach(row => {
    const text = row.innerText.toLowerCase();
    row.style.display = text.includes(searchTerm) ? '' : 'none';
  });
});
  document.querySelectorAll('.delete-section-btn').forEach(button => {
  button.addEventListener('click', function (e) {
    e.preventDefault(); // Prevent form submission

    const form = this.closest('form');

    Swal.fire({
      title: 'Are you sure?',
      text: "This section will be deleted permanently.",
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#d33',
      cancelButtonColor: '#3085d6',
      confirmButtonText: 'Yes, delete it!',
      reverseButtons: true
    }).then((result) => {
      if (result.isConfirmed) {
        form.submit(); // Submit the form after confirmation
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
