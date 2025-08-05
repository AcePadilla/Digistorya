<?php 
session_start();
include 'db_connection.php'; 

if (!isset($_SESSION['teacher_email'])) {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $title = $_POST['title'];
  $fileType = $_POST['file_type'];
  $fileLink = $_POST['file_link'];
  $image = $_FILES['image']['name'];

  $targetDir = "../../assets/images";
  $targetFile = $targetDir . basename($image);
  $imageFileType = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));

  $validImageTypes = array("jpg", "jpeg", "png", "gif");
  if (!in_array($imageFileType, $validImageTypes)) {
      echo "Only JPG, JPEG, PNG, and GIF files are allowed.";
      exit;
  }

  if (move_uploaded_file($_FILES['image']['tmp_name'], $targetFile)) {
      $stmt = $conn->prepare("INSERT INTO presentations (title, file, image, file_type, uploaded_at) VALUES (?, ?, ?, ?, NOW())");
      $stmt->bind_param("ssss", $title, $fileLink, $targetFile, $fileType);
      $stmt->execute();
      header("Location: lessons.php");
      exit;
  } else {
      echo "Error uploading image.";
  }
}

$pdfResult = $conn->query("SELECT * FROM presentations WHERE file_type = 'pdf' ORDER BY uploaded_at DESC");
$pptResult = $conn->query("SELECT * FROM presentations WHERE file_type = 'ppt' ORDER BY uploaded_at DESC");

function getGoogleDriveViewLink($url) {
  preg_match('/(?:drive|docs)\.google\.com.*d\/(.*?)(?:[\/?]|$)/', $url, $matches);
  return isset($matches[1]) ? "https://drive.google.com/file/d/" . $matches[1] . "/preview" : $url;
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
<div id="mainContent" class="flex-1 ml-64 p-8 overflow-y-auto">
  <div class="flex items-center justify-between mb-6">
    <h1 class="text-4xl font-bold text-[#551a25]">Lessons</h1>
  </div>

    <!-- Upload New Lesson Form -->
    <div class="mb-8 mx-auto bg-white p-6 rounded-lg shadow-lg">
        <h2 class="text-2xl font-semibold text-[#551a25] mb-6">Upload New Lesson</h2>
        <form method="POST" enctype="multipart/form-data" class="space-y-6">

            <div>
                <label for="title" class="block text-lg font-semibold text-[#551a25] mb-2">Title</label>
                <input type="text" id="title" name="title" placeholder="Enter Lesson Title" class="w-full p-4 border border-gray-300 rounded-lg" required />
            </div>

            <div>
                <label for="file_type" class="block text-lg font-semibold text-[#551a25] mb-2">File Type</label>
                <select name="file_type" id="file_type" class="w-full p-4 border border-gray-300 rounded-lg" required>
                    <option value="pdf">PDF</option>
                    <option value="ppt">PPT</option>
                </select>
            </div>

            <div>
                <label for="file_link" class="block text-lg font-semibold text-[#551a25] mb-2">File URL</label>
                <input type="url" id="file_link" name="file_link" placeholder="Enter the URL to the file" class="w-full p-4 border border-gray-300 rounded-lg" required />
            </div>

            <div>
                <label for="image" class="block text-lg font-semibold text-[#551a25] mb-2">Upload Image</label>
                <input type="file" name="image" id="image" class="w-full p-4  rounded-lg file:bg-[#551a25] file:text-white file:px-4 file:py-2 file:border-none file:rounded hover:file:bg-[#3d111c]" required />
            </div>

            <button type="submit" name="upload_lesson" class="w-24 bg-[#551a25] text-white py-3 rounded-lg hover:bg-[#6e2c33] transition">
                Upload
            </button>
        </form>
    </div>

   <!-- Uploaded PDF Lessons Table -->
<div class="bg-white p-6 rounded-xl shadow-lg w-full mx-auto mb-8">
    <h2 class="text-2xl font-bold text-[#551a25] mb-6">Uploaded PDF Lessons</h2>
    <div class="overflow-x-auto">
        <table class="w-full text-left border border-gray-300 mt-2">
            <thead class="bg-[#551a25] text-white">
                <tr>
                    <th class="p-4">Image</th>
                    <th class="p-4">Title</th>
                    <th class="p-4">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while($row = $pdfResult->fetch_assoc()): ?>
                <tr class="even:bg-[#f0e8e1] odd:bg-white border-t hover:bg-gray-100 transition">
                    <td class="p-4">
                        <img src="<?= $row['image'] ?>" alt="Preview" class="w-24 h-24 object-cover rounded shadow">
                    </td>
                    <td class="p-4"><?= htmlspecialchars($row['title']); ?></td>
                    <td class="p-4 space-x-3">
                        <a href="edit_lesson.php?id=<?= $row['id']; ?>" class="text-[#d79e4b] hover:text-[#6e2c33] transition">
                            <i class="fas fa-edit"></i>
                        </a>
                        <a href="delete.php?id=<?= $row['id']; ?>" class="text-red-600 hover:text-red-800 transition delete-btn" data-id="<?= $row['id']; ?>">
  <i class="fas fa-trash-alt"></i>
</a>


                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Uploaded PPT Lessons Table -->
<div class="bg-white p-6 rounded-xl shadow-lg w-full mx-auto">
    <h2 class="text-2xl font-bold text-[#551a25] mb-6">Uploaded PPT Lessons</h2>
    <div class="overflow-x-auto">
        <table class="w-full text-left border border-gray-300 mt-2">
            <thead class="bg-[#551a25] text-white">
                <tr>
                    <th class="p-4">Image</th>
                    <th class="p-4">Title</th>
                    <th class="p-4">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while($row = $pptResult->fetch_assoc()): ?>
                <tr class="even:bg-[#f0e8e1] odd:bg-white border-t hover:bg-gray-100 transition">
                    <td class="p-4">
                        <img src="<?= $row['image'] ?>" alt="Preview" class="w-24 h-24 object-cover rounded shadow">
                    </td>
                    <td class="p-4"><?= htmlspecialchars($row['title']); ?></td>
                    <td class="p-4 space-x-3">
                        <a href="edit_lesson.php?id=<?= $row['id']; ?>" class="text-[#d79e4b] hover:text-[#6e2c33] transition">
                            <i class="fas fa-edit"></i>
                        </a>
                        <a href="delete.php?id=<?= $row['id']; ?>" class="text-red-600 hover:text-red-800 transition delete-btn" data-id="<?= $row['id']; ?>">
  <i class="fas fa-trash-alt"></i>
</a>


                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
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

 

// Close dropdown when clicking outside
window.addEventListener("click", function (e) {
  if (!notifBtn.contains(e.target) && !notifDropdown.contains(e.target)) {
    notifDropdown.classList.add("hidden");
  }
});
const dropArea = document.getElementById('drop-area');
const fileInput = document.getElementById('fileElem');

dropArea.addEventListener('click', () => fileInput.click());

['dragenter', 'dragover'].forEach(eventName => {
  dropArea.addEventListener(eventName, e => {
    e.preventDefault();
    e.stopPropagation();
    dropArea.classList.add('bg-gray-200');
  }, false);
});

['dragleave', 'drop'].forEach(eventName => {
  dropArea.addEventListener(eventName, e => {
    e.preventDefault();
    e.stopPropagation();
    dropArea.classList.remove('bg-gray-200');
  }, false);
});

dropArea.addEventListener('drop', e => {
  fileInput.files = e.dataTransfer.files;
});

  </script>
<script>
  document.querySelectorAll('.delete-btn').forEach(button => {
    button.addEventListener('click', function(e) {
      e.preventDefault(); // stop the link from navigating
      const lessonId = this.getAttribute('data-id');
      const deleteUrl = `delete.php?id=${lessonId}`;

      Swal.fire({
        title: 'Are you sure?',
        text: "This lesson will be permanently deleted!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#aaa',
        confirmButtonText: 'Yes, delete it!',
        reverseButtons: true
      }).then((result) => {
        if (result.isConfirmed) {
          window.location.href = deleteUrl;
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