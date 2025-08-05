<?php
session_start();
include 'db_connection.php'; 
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require __DIR__ . '/../../vendor/autoload.php';

// Fetch all sections for checkbox list (for form display)
$query = "SELECT * FROM section";
$result = $conn->query($query);
$sections = $result->fetch_all(MYSQLI_ASSOC);

// DELETE TEACHER
if (isset($_GET['delete_teacher'])) {
    $teacherId = $_GET['delete_teacher'];
    $stmt = $conn->prepare("DELETE FROM teachers WHERE id = ?");
    $stmt->bind_param("i", $teacherId);
    $stmt->execute();
    header("Location: teachers.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['create_teacher'])) {
    $firstname = $_POST['firstname'];
    $lastname = $_POST['lastname'];
    $email = $_POST['email'];

    // Generate temporary password & hashed password
    $tempPassword = bin2hex(random_bytes(4)); // 8 chars temp password
    $hashedPassword = password_hash($tempPassword, PASSWORD_DEFAULT);

    // Insert teacher into teachers table (with generated password)
    $stmt = $conn->prepare("INSERT INTO teachers (firstname, lastname, email, password) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $firstname, $lastname, $email, $hashedPassword);
    $stmt->execute();
    $teacherId = $stmt->insert_id;

    // Assign Sections
    if (isset($_POST['sections']) && is_array($_POST['sections'])) {
        foreach ($_POST['sections'] as $sectionId) {
            $stmt = $conn->prepare("INSERT INTO teacher_section (teacher_id, section_id) VALUES (?, ?)");
            $stmt->bind_param("ii", $teacherId, $sectionId);
            $stmt->execute();
        }
    }

    // Send email with PHPMailer
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'digistorya@gmail.com';
        $mail->Password = 'lvyu ooni neiw gxem'; // Make sure this is correct
        $mail->SMTPSecure = 'tls';
        $mail->Port = 587;

        $mail->setFrom('your_email@gmail.com', 'DIGIstorya Admin');
        $mail->addAddress($email, $firstname . ' ' . $lastname);
        $mail->isHTML(true);
        $mail->Subject = 'Your Teacher Account Credentials';
        $mail->Body = "
            <h2>Welcome to DIGIstorya!</h2>
            <p>Your temporary login credentials are:</p>
            <strong>Email:</strong> $email<br>
            <strong>Password:</strong> $tempPassword<br><br>
            <em>Please log in and change your password after logging in.</em>
        ";
        $mail->send();

        echo "<script>
            Swal.fire({
                title: 'Success!',
                text: 'Teacher created and email sent successfully!',
                icon: 'success',
                confirmButtonText: 'OK'
            }).then(() => {
                window.location.href = 'teachers.php';
            });
        </script>";
    } catch (Exception $e) {
        echo "<script>
            Swal.fire({
                title: 'Created but Email Failed',
                text: 'Teacher created but email failed: {$mail->ErrorInfo}',
                icon: 'warning',
                confirmButtonText: 'OK'
            });
        </script>";
    }
}

// Pagination and Sorting
$perPage = 10;
$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$offset = ($page - 1) * $perPage;

$validSortColumns = ['firstname', 'lastname'];
$sort = in_array($_GET['sort'] ?? '', $validSortColumns) ? $_GET['sort'] : 'firstname';
$order = (isset($_GET['order']) && strtolower($_GET['order']) === 'desc') ? 'DESC' : 'ASC';
$nextOrder = $order === 'ASC' ? 'desc' : 'asc';

// Count total teachers
$result = $conn->query("SELECT COUNT(*) as total FROM teachers");
$totalTeachers = $result ? $result->fetch_assoc()['total'] : 0;
$totalPages = ceil($totalTeachers / $perPage);

// Fetch sorted and paginated teachers
$query = "SELECT * FROM teachers ORDER BY $sort $order LIMIT ?, ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $offset, $perPage);
$stmt->execute();
$teachers = $stmt->get_result();
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
<div id="mainContent" class="flex-1 ml-64 p-8 overflow-y-auto transition-all duration-300 bg-[#f0e8e1]">
  <div class="flex items-center justify-between mb-6">
    <h1 class="text-4xl font-bold text-[#551a25]">Teachers</h1>

  </div>

  <div class="mb-8 mx-auto bg-white p-6 rounded-lg shadow-lg">
        <h2 class="text-2xl font-semibold text-[#551a25] mb-6">Create Teacher</h2>
        <form method="POST" class="space-y-6">

            <div class="flex flex-col sm:flex-row sm:space-x-6">
                <div class="w-full sm:w-1/2">
                    <label for="firstname" class="block text-lg font-semibold text-[#551a25] mb-2">First Name</label>
                    <input type="text" id="firstname" name="firstname" placeholder="Enter First Name" class="w-full p-4 border border-gray-300 rounded-lg" required />
                </div>
                <div class="w-full sm:w-1/2">
                    <label for="lastname" class="block text-lg font-semibold text-[#551a25] mb-2">Last Name</label>
                    <input type="text" id="lastname" name="lastname" placeholder="Enter Last Name" class="w-full p-4 border border-gray-300 rounded-lg" required />
                </div>
            </div>

            <div>
                <label for="email" class="block text-lg font-semibold text-[#551a25] mb-2">Email</label>
                <input type="text" id="email" name="email" placeholder="Enter email" class="w-full p-4 border border-gray-300 rounded-lg" required />
            </div>

            <!-- Section Checkboxes -->
            <div>
                <label class="block text-lg font-semibold text-[#551a25] mb-2">Assign Sections</label>
                <?php foreach ($sections as $section): ?>
                    <div>
                        <input type="checkbox" class="h-5 w-5 text-[#551a25] bg-white border-2 border-[#d79e4b] rounded-md focus:ring-2 focus:ring-[#551a25] transition duration-200 cursor-pointer"
                        id="section_<?= $section['id'] ?>" name="sections[]" value="<?= $section['id'] ?>">
                        <label for="section_<?= $section['id'] ?>" class="text-[#551a25]"><?= $section['section_name'] ?></label>
                    </div>
                <?php endforeach; ?>
            </div>
                  
            <!-- Submit Button -->
            <button type="submit" name="create_teacher" class="w-24 bg-[#551a25] text-white py-3 rounded-lg hover:bg-[#6e2c33]">
                Create
            </button>
        </form>
    </div>

<!-- Teacher Table -->
<div class="bg-white p-6 rounded-xl shadow-lg w-full mx-auto">
  <div class="flex justify-between items-center mb-4">
    <h2 class="text-2xl font-bold text-[#551a25]">Teacher List</h2>

    <!-- Styled Search Input -->
    <div class="relative w-72">
      <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-500">
        <i class="fas fa-search"></i>
      </span>
      <input type="text" id="searchInput" placeholder="Search teacher..." 
             class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg shadow-sm focus:ring-[#551a25] focus:ring-2 focus:outline-none transition">
    </div>
  </div>

  <div class="overflow-x-auto">
    <table class="w-full text-left border border-gray-300 mt-2" id="teacherTable">
    <thead class="bg-[#551a25] text-white">
    <tr>
       
        <th class="p-4">
            <a href="?sort=firstname&order=<?= $nextOrder ?>&page=<?= $page ?>" class="hover:underline">
                First Name <?= ($_GET['sort'] ?? '') === 'firstname' ? ($order === 'ASC' ? '▲' : '▼') : '▲▼' ?>
            </a>
        </th>
        <th class="p-4">
            <a href="?sort=lastname&order=<?= $nextOrder ?>&page=<?= $page ?>" class="hover:underline">
                Last Name <?= ($_GET['sort'] ?? '') === 'lastname' ? ($order === 'ASC' ? '▲' : '▼') : '▲▼' ?>
            </a>
        </th>
        <th class="p-4">
            <a href="?sort=lastname&order=<?= $nextOrder ?>&page=<?= $page ?>" class="hover:underline">
                Email <?= ($_GET['sort'] ?? '') === 'email' ? ($order === 'ASC' ? '▲' : '▼') : '▲▼' ?>
            </a>
        </th>
        <th class="p-4">Actions</th>
    </tr>
</thead>

      <tbody>
        <?php while($row = $teachers->fetch_assoc()): ?>
        <tr class="even:bg-[#f0e8e1] odd:bg-white border-t hover:bg-gray-100 transition">
          <td class="p-4"><?= htmlspecialchars($row['firstname']); ?></td>
          <td class="p-4"><?= htmlspecialchars($row['lastname']); ?></td>
          <td class="p-4"><?= htmlspecialchars($row['email']); ?></td>
          <td class="p-4 space-x-3">
            <a href="edit_teacher.php?id=<?= $row['id']; ?>" class="text-[#d79e4b] hover:text-[#6e2c33] transition">
              <i class="fas fa-edit"></i>
            </a>
            <a href="#" 
              onclick="confirmDelete(<?= $row['id']; ?>)" 
              class="text-red-600 hover:text-red-800 transition">
              <i class="fas fa-trash-alt"></i>
            </a>
          </td>
        </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
    <div class="flex justify-center mt-6 space-x-2">
    <?php if ($page > 1): ?>
        <a href="?page=<?= $page - 1 ?>" class="px-4 py-2 bg-[#551a25] text-white rounded hover:bg-[#6e2c33] transition">Previous</a>
    <?php endif; ?>

    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
        <a href="?page=<?= $i ?>" class="px-4 py-2 <?= $i == $page ? 'bg-[#6e2c33]' : 'bg-[#551a25]' ?> text-white rounded hover:bg-[#6e2c33] transition"><?= $i ?></a>
    <?php endfor; ?>

    <?php if ($page < $totalPages): ?>
        <a href="?page=<?= $page + 1 ?>" class="px-4 py-2 bg-[#551a25] text-white rounded hover:bg-[#6e2c33] transition">Next</a>
    <?php endif; ?>
</div>

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
    const tableRows = document.querySelectorAll('#teacherTable tbody tr');

    searchInput.addEventListener('keyup', function () {
        const searchValue = this.value.toLowerCase();

        tableRows.forEach(row => {
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(searchValue) ? '' : 'none';
        });
    });
  function confirmDelete(teacherId) {
    Swal.fire({
      title: 'Are you sure?',
      text: "This teacher will be deleted permanently.",
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#d33',
      cancelButtonColor: '#aaa',
      confirmButtonText: 'Yes, delete it!'
    }).then((result) => {
      if (result.isConfirmed) {
        // Redirect with query string (same as before)
        window.location.href = '?delete_teacher=' + teacherId + '&deleted=true';
      }
    });
  }

  // Show SweetAlert success after redirection
  <?php if (isset($_GET['deleted']) && $_GET['deleted'] == 'true'): ?>
    Swal.fire({
      icon: 'success',
      title: 'Deleted!',
      text: 'The teacher has been deleted.',
      timer: 2000,
      showConfirmButton: false
    });
    // Remove the query param to avoid repeat toast on refresh
    if (window.history.replaceState) {
      const url = new URL(window.location.href);
      url.searchParams.delete('deleted');
      window.history.replaceState({}, document.title, url.toString());
    }
  <?php endif; ?>

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