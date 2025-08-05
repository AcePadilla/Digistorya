<?php
include 'db_connection.php';

if (!isset($_GET['id'])) {
    die("Teacher ID is required.");
}

$id = $_GET['id'];

// Fetch teacher info
$stmt = $conn->prepare("SELECT * FROM teachers WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$teacher = $result->fetch_assoc();

if (!$teacher) {
    die("Teacher not found.");
}

// Fetch all sections
$sectionsResult = $conn->query("SELECT id, section_name FROM section");
$sections = $sectionsResult->fetch_all(MYSQLI_ASSOC);

// Fetch assigned sections
$assignedSections = [];
$assignedStmt = $conn->prepare("SELECT section_id FROM teacher_section WHERE teacher_id = ?");
$assignedStmt->bind_param("i", $id);
$assignedStmt->execute();
$assignedData = $assignedStmt->get_result();
while ($row = $assignedData->fetch_assoc()) {
    $assignedSections[] = $row['section_id'];
}

// Handle update
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $firstname = $_POST['firstname'];
    $lastname = $_POST['lastname'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $selectedSections = $_POST['sections'] ?? [];

    if (!empty($password)) {
        $hashed = password_hash($password, PASSWORD_DEFAULT);
        $updateStmt = $conn->prepare("UPDATE teachers SET firstname = ?, lastname = ?, email = ?, password = ? WHERE id = ?");
        $updateStmt->bind_param("ssssi", $firstname, $lastname, $email, $hashed, $id);
    } else {
        $updateStmt = $conn->prepare("UPDATE teachers SET firstname = ?, lastname = ?, email = ? WHERE id = ?");
        $updateStmt->bind_param("sssi", $firstname, $lastname, $email, $id);
    }

    if ($updateStmt->execute()) {
        // Clear old assignments
        $deleteStmt = $conn->prepare("DELETE FROM teacher_section WHERE teacher_id = ?");
        $deleteStmt->bind_param("i", $id);
        $deleteStmt->execute();

        // Insert new assignments
        if (!empty($selectedSections)) {
            $insertStmt = $conn->prepare("INSERT INTO teacher_section (teacher_id, section_id) VALUES (?, ?)");
            foreach ($selectedSections as $section_id) {
                $insertStmt->bind_param("ii", $id, $section_id);
                $insertStmt->execute();
            }
        }

        header("Location: teachers.php");
        exit;
    } else {
        echo "Update failed: " . $conn->error;
    }
}
?>

<script src="https://cdn.tailwindcss.com"></script>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
  body {
    font-family: 'Poppins', sans-serif;
    background-color: #f0e8e1; 
  }
</style>

<div class="min-h-screen flex items-center justify-center px-4">
  <div class="w-full max-w-3xl bg-white p-10 rounded-2xl shadow-xl">
    <h2 class="text-3xl font-bold text-[#551a25] mb-8">Edit Teacher</h2>

    <form method="POST" class="space-y-6">
      <div class="grid md:grid-cols-2 gap-6">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">First Name</label>
          <input type="text" name="firstname" value="<?= htmlspecialchars($teacher['firstname']) ?>" class="w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm focus:ring-[#551a25] focus:ring-2 focus:outline-none" required>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Last Name</label>
          <input type="text" name="lastname" value="<?= htmlspecialchars($teacher['lastname']) ?>" class="w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm focus:ring-[#551a25] focus:ring-2 focus:outline-none" required>
        </div>
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
        <input type="text" name="email" value="<?= htmlspecialchars($teacher['email']) ?>" class="w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm focus:ring-[#551a25] focus:ring-2 focus:outline-none" required>
      </div>

      <div>
        <label class="block text-lg font-semibold text-[#551a25] mb-2">Assign Sections</label>
        <?php foreach ($sections as $section): ?>
          <div class="flex items-center mb-2">
            <input type="checkbox"
                   id="section_<?= $section['id'] ?>"
                   name="sections[]"
                   value="<?= $section['id'] ?>"
                   <?= in_array($section['id'], $assignedSections) ? 'checked' : '' ?>
                   class="h-5 w-5 text-[#551a25] bg-white border-2 border-[#d79e4b] rounded-md focus:ring-2 focus:ring-[#551a25] transition duration-200 cursor-pointer">
            <label for="section_<?= $section['id'] ?>" class="ml-2 text-[#551a25]"><?= htmlspecialchars($section['section_name']) ?></label>
          </div>
        <?php endforeach; ?>
      </div>

      <div class="flex justify-end space-x-4 pt-4">
        <a href="teachers.php" class="inline-block px-6 py-3 text-gray-600 hover:text-[#551a25] hover:underline transition font-medium">
          Cancel
        </a>
        <button type="submit" class="bg-[#551a25] text-white px-6 py-3 rounded-lg font-semibold hover:bg-[#6e2c33] transition duration-200">
          Update
        </button>
      </div>
    </form>
  </div>
</div>
