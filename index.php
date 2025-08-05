<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>DIGIstorya | Login Portal</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="icon" href="../images/logo.png">
  <!-- Tailwind CSS CDN -->
  <script src="https://cdn.tailwindcss.com"></script>
  <!-- Font Awesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

  <!-- Custom Font -->
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">

  <style>
    body {
      background: #f0e8e1;
      font-family: 'Poppins', sans-serif;
      background-image: radial-gradient(circle at top right, #fefefe, #f0e8e1);
    }
  </style>
</head>
<body class="min-h-screen flex items-center justify-center p-6">

  <div class="bg-white border border-gray-200 shadow-2xl rounded-3xl max-w-2xl w-full px-10 py-12 text-center">
    <h1 class="text-4xl font-extrabold text-[#551a25] mb-4">
      Welcome to <span class="text-[#d79e4b]">DIGIstorya</span>
    </h1>
    <p class="text-gray-600 text-lg mb-10">Log in as an Admin, Teacher, or Student to start exploring and managing your DIGIstorya experience.</p>
    <div class="flex flex-col gap-6">
      <a href="views/admin/login.php" class="flex items-center justify-center gap-3 text-lg bg-[#551a25] hover:bg-[#3f141c] text-white font-semibold py-4 px-8 rounded-2xl transition duration-300 shadow-md">
        <i class="fas fa-user-shield text-[#d79e4b] text-2xl"></i> Admin Login
      </a>

      <a href="views/teacher/login.php" class="flex items-center justify-center gap-3 text-lg bg-[#551a25] hover:bg-[#3f141c] text-white font-semibold py-4 px-8 rounded-2xl transition duration-300 shadow-md">
        <i class="fas fa-chalkboard-teacher text-[#d79e4b] text-2xl"></i> Teacher Login
      </a>

      <a href="views/students/login.php" class="flex items-center justify-center gap-3 text-lg bg-[#551a25] hover:bg-[#3f141c] text-white font-semibold py-4 px-8 rounded-2xl transition duration-300 shadow-md">
        <i class="fas fa-user-graduate text-[#d79e4b] text-2xl"></i> Student Login
      </a>
    </div>
  </div>

</body>
</html>
