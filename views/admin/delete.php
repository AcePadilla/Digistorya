<?php
include 'db_connection.php';

if (isset($_GET['id'])) {
    $id = $_GET['id'];

    // Delete the record
    $stmt = $conn->prepare("DELETE FROM presentations WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();

    header("Location: lessons.php");
    exit;
} else {
    echo "No ID specified.";
}
?>
