<?php
session_start();
require_once 'db_connection.php';

header('Content-Type: application/json');



$teacher_id = $_SESSION['teacher_id'] ?? null;
if (!$teacher_id) {
    http_response_code(403);
    echo json_encode(['error' => 'Access denied']);
    exit;
}

$filter = $_GET['filter'] ?? 'week';

$data = [];

switch ($filter) {
    case 'month':
        // Last 30 days data
        for ($i = 29; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-$i days"));
            $count = $conn->query("SELECT COUNT(*) AS total FROM quiz_results WHERE DATE(created_at) = '$date'")->fetch_assoc()['total'];
            $data[] = ['date' => $date, 'count' => (int)$count];
        }
        break;

    case 'year':
        // Last 12 months data
        for ($i = 11; $i >= 0; $i--) {
            $month = date('Y-m', strtotime("-$i months"));
            $result = $conn->query("SELECT COUNT(*) AS total FROM quiz_results WHERE DATE_FORMAT(created_at, '%Y-%m') = '$month'");
            $count = $result->fetch_assoc()['total'];
            $data[] = ['date' => $month, 'count' => (int)$count];
        }
        break;

    case 'week':
    default:
        // Last 7 days (Sunday to Saturday of this week)
        $start_of_week = date('Y-m-d', strtotime('sunday last week'));
        for ($i = 0; $i < 7; $i++) {
            $date = date('Y-m-d', strtotime("$start_of_week +$i days"));
            $count = $conn->query("SELECT COUNT(*) AS total FROM quiz_results WHERE DATE(created_at) = '$date'")->fetch_assoc()['total'];
            $data[] = ['date' => $date, 'count' => (int)$count];
        }
        break;
}

$conn->close();
echo json_encode($data);
?>