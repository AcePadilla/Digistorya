<?php
session_start();
include 'db_connection.php'; 
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'User not logged in']);
    exit();
}

$user_id = $_SESSION['user_id'];

// Get the JSON body
$data = json_decode(file_get_contents("php://input"), true);

if (!$data) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid input']);
    exit();
}

// Sanitize and extract data
$active_reflective = $data['active_reflective'] ?? '';
$sensing_intuitive = $data['sensing_intuitive'] ?? '';
$visual_verbal = $data['visual_verbal'] ?? '';
$sequential_global = $data['sequential_global'] ?? '';
$comments = $data['comments'] ?? '';




$stmt = $conn->prepare("INSERT INTO quiz_results (user_id, active_reflective, sensing_intuitive, visual_verbal, sequential_global, comments) VALUES (?, ?, ?, ?, ?, ?)");
$stmt->bind_param("isssss", $user_id, $active_reflective, $sensing_intuitive, $visual_verbal, $sequential_global, $comments);

if ($stmt->execute()) {
    echo json_encode(['status' => 'success']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Insert failed']);
}

$stmt->close();
$conn->close();
?>
