<?php
include "db_connection.php";

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Invalid ID.");
}

$id = intval($_GET['id']);

// Fetch existing record
$stmt = $conn->prepare("SELECT * FROM presentations WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$presentation = $result->fetch_assoc();

if (!$presentation) {
    die("Presentation not found.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'];
    $fileType = $_POST['file_type'];
    $fileLink = $_POST['file_link'];

    $image = $presentation['image']; // Keep existing image by default

    // Handle new image upload if a new file is provided
    if (!empty($_FILES['image']['name'])) {
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        $uploadedType = $_FILES['image']['type'];

        if (!in_array($uploadedType, $allowedTypes)) {
            die("Only JPG, PNG, and GIF files are allowed.");
        }

        // Make sure folder exists
        $uploadDir = "assets/images/";
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        // Delete old image if it exists
        if (!empty($presentation['image']) && file_exists($presentation['image'])) {
            unlink($presentation['image']);
        }

        // Generate new file name
        $imageName = time() . '_' . basename($_FILES['image']['name']);
        $imagePath = $uploadDir . $imageName;

        if (!move_uploaded_file($_FILES['image']['tmp_name'], $imagePath)) {
            die("Failed to upload image.");
        }

        $image = $imagePath;
    }

    // Update database
    $updateStmt = $conn->prepare("UPDATE presentations SET title = ?, file = ?, image = ?, file_type = ?, uploaded_at = NOW() WHERE id = ?");
    $updateStmt->bind_param("ssssi", $title, $fileLink, $image, $fileType, $id);
    $updateStmt->execute();

    header("Location: lessons.php");
    exit;
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Lesson</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f0e8e1;
        }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center p-6">

    <div class="bg-white shadow-xl rounded-2xl max-w-3xl w-full p-10 space-y-8 transition-all duration-300">
        <h2 class="text-3xl font-bold text-[#551a25]">Edit Lesson</h2>

        <form method="POST" enctype="multipart/form-data" class="space-y-6">
            <!-- Title -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Title</label>
                <input type="text" name="title" value="<?= htmlspecialchars($presentation['title']) ?>" required
                    class="w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm focus:ring-[#551a25] focus:ring-2 focus:outline-none">
            </div>

            <!-- File Type -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">File Type</label>
                <select name="file_type" required
                    class="w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm focus:ring-[#551a25] focus:ring-2 focus:outline-none">
                    <option value="pdf" <?= $presentation['file_type'] == 'pdf' ? 'selected' : '' ?>>PDF</option>
                    <option value="ppt" <?= $presentation['file_type'] == 'ppt' ? 'selected' : '' ?>>PPT</option>
                </select>
            </div>

            <!-- File Link -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">File Link</label>
                <input type="url" name="file_link" value="<?= htmlspecialchars($presentation['file']) ?>" required
                    class="w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm focus:ring-[#551a25] focus:ring-2 focus:outline-none">
            </div>

            <!-- Current Image & Upload -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Current Image</label>
                <?php
                    $imagePath = htmlspecialchars($presentation['image']);
                    if (!file_exists($imagePath)) {
                        $imagePath = 'images/default.jpg'; // fallback
                    }
                ?>
                <img src="<?= $imagePath ?>" alt="Current Image"
                    class="w-40 h-24 object-cover rounded-lg shadow mb-3">
                <input type="file" name="image" accept="image/*"
                    class="block w-full text-sm text-gray-700 border border-gray-300 rounded-lg cursor-pointer bg-white focus:ring-[#551a25] focus:ring-2 focus:outline-none">
            </div>

            <!-- Buttons -->
            <div class="flex justify-end space-x-4 pt-4">
                <a href="lessons.php"
                   class="inline-block px-6 py-3 text-gray-600 hover:text-[#551a25] hover:underline transition font-medium">
                  Cancel
                </a>
                <button type="submit"
                    class="bg-[#551a25] text-white px-6 py-3 rounded-lg font-semibold hover:bg-[#6e2c33] transition duration-200">
                    Save
                </button>
            </div>
        </form>
    </div>

</body>
</html>
