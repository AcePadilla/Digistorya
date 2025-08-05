<?php
include 'db_connection.php';

if (!isset($_GET['id'])) {
    die("Student ID is required.");
}

$id = $_GET['id'];

// Fetch student data
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

// Check if the query is successful
if (!$result) {
    die("Error in query: " . $conn->error);  // Show any error if query fails
}

$student = $result->fetch_assoc();

if (!$student) {
    die("Student not found.");
}

// Fetch sections
$sections = [];
$sectionQuery = $conn->query("SELECT id, section_name FROM section ORDER BY section_name");

if (!$sectionQuery) {
    die("Error in section query: " . $conn->error); // Show error if query fails
}

while ($section = $sectionQuery->fetch_assoc()) {
    $sections[] = $section; // Collecting all sections
}


// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $firstname = $_POST['firstname'];
    $lastname = $_POST['lastname'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $section_id = $_POST['section_id'];

    if (!empty($password)) {
        $hashed = password_hash($password, PASSWORD_DEFAULT);
        $updateStmt = $conn->prepare("UPDATE users SET firstname = ?, lastname = ?, email = ?, password = ?, section_id = ? WHERE id = ?");
        $updateStmt->bind_param("ssssii", $firstname, $lastname, $email, $hashed, $section_id, $id);
    } else {
        $updateStmt = $conn->prepare("UPDATE users SET firstname = ?, lastname = ?, email = ?, section_id = ? WHERE id = ?");
        $updateStmt->bind_param("sssii", $firstname, $lastname, $email, $section_id, $id);
    }

    if ($updateStmt->execute()) {
        header("Location: students.php");
        exit;
    } else {
        echo "Update failed.";
    }
}
?>

<!-- HTML & Tailwind CSS -->
<script src="https://cdn.tailwindcss.com"></script>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<style>
  body {
    font-family: 'Poppins', sans-serif;
    background-color: #f0e8e1;
  }
</style>

<div class="min-h-screen flex items-center justify-center px-4">
  <div class="w-full max-w-3xl bg-white p-10 rounded-2xl shadow-xl">
    <h2 class="text-3xl font-bold text-[#551a25] mb-8">Edit Student</h2>

    <form method="POST" class="space-y-6">
      <div class="grid md:grid-cols-2 gap-6">
        <!-- First Name -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">First Name</label>
          <input type="text" name="firstname" value="<?= htmlspecialchars($student['firstname']) ?>"
                 class="w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm focus:ring-[#551a25] focus:ring-2 focus:outline-none"
                 required>
        </div>

        <!-- Last Name -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Last Name</label>
          <input type="text" name="lastname" value="<?= htmlspecialchars($student['lastname']) ?>"
                 class="w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm focus:ring-[#551a25] focus:ring-2 focus:outline-none"
                 required>
        </div>
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
        <input type="text" name="email" value="<?= htmlspecialchars($student['email']) ?>"
               class="w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm focus:ring-[#551a25] focus:ring-2 focus:outline-none"
               required>
      </div>

      <!-- Password -->
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Password</label>
        <input type="password" name="password" placeholder="Leave blank to keep current password"
               class="w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm focus:ring-[#551a25] focus:ring-2 focus:outline-none">
      </div>

      <!-- Section Dropdown -->
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Section</label>
        <select name="section_id"
                class="w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm focus:ring-[#551a25] focus:ring-2 focus:outline-none"
                required>
          <option value="" disabled>Select a section</option>
          <?php foreach ($sections as $section): ?>
            <option value="<?= $section['id'] ?>" <?= $student['section_id'] == $section['id'] ? 'selected' : '' ?>>
              <?= htmlspecialchars($section['section_name']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <!-- Buttons -->
      <div class="flex justify-end space-x-4 pt-4">
        <a href="students.php"
           class="inline-block px-6 py-3 text-gray-600 hover:text-[#551a25] hover:underline transition font-medium">
          Cancel
        </a>
        <button type="submit"
                class="bg-[#551a25] text-white px-6 py-3 rounded-lg font-semibold hover:bg-[#6e2c33] transition duration-200">
          Update
        </button>
      </div>
    </form>
  </div>
</div>
