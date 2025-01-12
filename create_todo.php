<?php
session_start();
include 'db.php';  // Database connection

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");  // Redirect to login if not logged in
    exit();
}

// Fetch tasks for the logged-in user
$user_id = $_SESSION['user_id'];
$sql = "SELECT * FROM todos WHERE user_id = ? ORDER BY created_at DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>To-Do List</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h2 class="text-center">Your To-Do List</h2>

        <!-- Display Error or Success Messages -->
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger"><?= $_SESSION['error']; ?></div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success"><?= $_SESSION['success']; ?></div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <!-- Task Add Form -->
        <form action="index.php" method="POST">
            <div class="mb-3">
                <label for="task" class="form-label">Task Name</label>
                <input type="text" class="form-control" id="task" name="task" required>
            </div>
            <div class="mb-3">
                <label for="description" class="form-label">Description</label>
                <textarea class="form-control" id="description" name="description"></textarea>
            </div>
            <button type="submit" class="btn btn-primary">Add Task</button>
        </form>

        <!-- Display To-Do Items -->
        <ul class="list-group mt-4">
            <?php while ($row = $result->fetch_assoc()): ?>
                <li class="list-group-item">
                    <strong><?= htmlspecialchars($row['task']); ?></strong><br>
                    <?= htmlspecialchars($row['description']); ?><br>
                    <small>Created on: <?= $row['created_at']; ?></small>
                </li>
            <?php endwhile; ?>
        </ul>
    </div>

    <?php
    $stmt->close();  // Close the prepared statement
    ?>
</body>
</html>

<?php
// Handle task insertion when the form is submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['task'])) {
    // Get task and description from the form
    $task = $_POST['task'];
    $description = isset($_POST['description']) ? $_POST['description'] : '';

    // Ensure task name is not empty
    if (!empty($task)) {
        $user_id = $_SESSION['user_id'];  // Get the logged-in user's ID

        // Insert the new task with description into the database
        $stmt = $conn->prepare("INSERT INTO todos (task, description, user_id) VALUES (?, ?, ?)");
        $stmt->bind_param("ssi", $task, $description, $user_id);

        if ($stmt->execute()) {
            $_SESSION['success'] = "Task added successfully!";
            header("Location: index.php");  // Redirect back to this page to display updated tasks
            exit();  // Prevent further code execution
        } else {
            $_SESSION['error'] = "Error: " . $stmt->error;
        }

        $stmt->close();  // Close the statement
    }
}
?>
