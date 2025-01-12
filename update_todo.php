<?php
session_start();
include 'db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['id']) && isset($_POST['task'])) {
    $id = $_POST['id'];
    $task = $_POST['task'];
    $description = isset($_POST['description']) ? $_POST['description'] : '';
    $is_completed = isset($_POST['is_completed']) ? 1 : 0;

    $sql = "UPDATE todos SET task = '$task', description = '$description', is_completed = $is_completed WHERE id = $id AND user_id = {$_SESSION['user_id']}";

    if ($conn->query($sql) === TRUE) {
        echo "Task updated successfully!";
    } else {
        echo "Error: " . $conn->error;
    }
}
?>
