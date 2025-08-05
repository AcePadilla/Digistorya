<?php
include 'db_connection.php';

// Check if 'id' is passed in the URL
if (!isset($_GET['id'])) {
    die("Section ID is required.");
}

$id = $_GET['id'];

// Fetch the section data for the given ID
$stmt = $conn->prepare("SELECT * FROM section WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$section = $result->fetch_assoc();

// If section not found, terminate
if (!$section) {
    die("Section not found.");
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Ensure 'section_name' is set before using it
    if (isset($_POST['section_name']) && !empty($_POST['section_name'])) {
        $section_name = $_POST['section_name'];

        // Prepare and execute the update query
        $updateStmt = $conn->prepare("UPDATE section SET section_name = ? WHERE id = ?");
        $updateStmt->bind_param("si", $section_name, $id);

        // Check if update is successful
        if ($updateStmt->execute()) {
            // Redirect to sections page if successful
            header("Location: section.php");
            exit;
        } else {
            // Output error message if update fails
            echo "Update failed: " . $updateStmt->error;
        }
    } else {
        // If 'section_name' is empty, show an error message
        echo "Section name is required.";
    }
}
?>

<script src="https://cdn.tailwindcss.com"></script>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
  body { font-family: 'Poppins', sans-serif; background-color: #f0e8e1; }
</style>

<div class="min-h-screen flex items-center justify-center px-4">
  <div class="w-full max-w-2xl bg-white p-10 rounded-2xl shadow-xl">
    <h2 class="text-3xl font-bold text-[#551a25] mb-8">Edit Section</h2>

    <form method="POST" class="space-y-6">
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Section Name</label>
        <input type="text" name="section_name" value="<?= htmlspecialchars($section['section_name']) ?>"
               class="w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm focus:ring-[#551a25] focus:ring-2 focus:outline-none"
               required>
      </div>

      <div class="flex justify-end space-x-4 pt-4">
        <a href="section.php" 
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
