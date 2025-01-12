<?php 
session_start();
include 'db.php';  // Database connection

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set default time zone to GMT
date_default_timezone_set('GMT');  // Time zone set to GMT

// Ensure the user is logged in
if (!isset($_SESSION['user_id'])) {
    // Redirect to login page if user is not logged in
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];  // Get the logged-in user's ID

// Pagination settings
$tasks_per_page = 5;  // Number of tasks per page
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;  // Current page number
$offset = ($page - 1) * $tasks_per_page;  // Calculate offset

// Check if the form is submitted to add, update or delete a task
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['task'])) {
    // Check if it's an update operation
    if (isset($_POST['task_id'])) {
        // Update task
        $task_id = $_POST['task_id'];
        $task = $_POST['task'];
        $description = isset($_POST['description']) ? $_POST['description'] : '';
        $status = $_POST['status'];

        if (!empty($task)) {
            // Update task in the database
            $sql = "UPDATE todos SET task = ?, description = ?, status = ? WHERE id = ? AND user_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sssii", $task, $description, $status, $task_id, $user_id);
            if ($stmt->execute()) {
                $_SESSION['success'] = "Task updated successfully!";
                // Redirect to clear the form
                header("Location: index.php");
                exit();
            } else {
                $_SESSION['error'] = "Error: " . $stmt->error;
            }
            $stmt->close();
        }
    } else {
        // Add new task
        $task = $_POST['task'];
        $description = isset($_POST['description']) ? $_POST['description'] : '';
        $status = $_POST['status'];

        if (!empty($task)) {
            // Insert new task into the database
            $sql = "INSERT INTO todos (task, description, status, user_id, created_at) VALUES (?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $created_at = date('Y-m-d H:i:s');  // Get the current timestamp in GMT
            $stmt->bind_param("sssis", $task, $description, $status, $user_id, $created_at); // Corrected bind_param

            if ($stmt->execute()) {
                $_SESSION['success'] = "Task added successfully!";
                // Redirect to clear the form
                header("Location: index.php");
                exit();
            } else {
                $_SESSION['error'] = "Error: " . $stmt->error;
            }
            $stmt->close();
        }
    }
}

// Check for delete request
if (isset($_GET['delete_id'])) {
    $task_id = $_GET['delete_id'];

    // Delete task from the database
    $sql = "DELETE FROM todos WHERE id = ? AND user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $task_id, $user_id);
    if ($stmt->execute()) {
        $_SESSION['success'] = "Task deleted successfully!";
    } else {
        $_SESSION['error'] = "Error: " . $stmt->error;
    }
    $stmt->close();

    // Redirect after delete
    header("Location: index.php");
    exit();
}

// Check if the toggle_status parameter is set
if (isset($_GET['toggle_status'])) {
    $task_id = $_GET['toggle_status'];

    // Get the current status of the task
    $sql = "SELECT status FROM todos WHERE id = ? AND user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $task_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $task = $result->fetch_assoc();
        // Toggle the status
        $new_status = ($task['status'] == 'Completed') ? 'Pending' : 'Completed';

        // Update the task's status in the database
        $sql = "UPDATE todos SET status = ? WHERE id = ? AND user_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sii", $new_status, $task_id, $user_id);
        if ($stmt->execute()) {
            $_SESSION['success'] = "Task status updated successfully!";
        } else {
            $_SESSION['error'] = "Error: " . $stmt->error;
        }
    } else {
        $_SESSION['error'] = "Task not found or access denied.";
    }

    $stmt->close();

    // Redirect after status update
    header("Location: index.php");
    exit();
}

// Fetch tasks for the logged-in user with pagination
$sql = "SELECT id, task, description, status, created_at FROM todos WHERE user_id = ? ORDER BY created_at DESC LIMIT ? OFFSET ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("iii", $user_id, $tasks_per_page, $offset);
$stmt->execute();
$result = $stmt->get_result();

// Check if any tasks are returned
$tasks = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $tasks[] = $row; // Collect all tasks into an array
    }
} else {
    $_SESSION['error'] = "No tasks found.";
}

$stmt->close();

// Get the total number of tasks for pagination
$sql = "SELECT COUNT(*) AS total_tasks FROM todos WHERE user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$total_tasks = $row['total_tasks'];
$total_pages = ceil($total_tasks / $tasks_per_page);

$stmt->close();

// Edit task logic - show task details for editing
$edit_task = null;
if (isset($_GET['edit_id'])) {
    $task_id = $_GET['edit_id'];
    $sql = "SELECT * FROM todos WHERE id = ? AND user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $task_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $edit_task = $result->fetch_assoc();  // Get task details to edit
    } else {
        $_SESSION['error'] = "Task not found or access denied.";
        header("Location: index.php");
        exit();
    }
    $stmt->close();
}

// Logout logic
if (isset($_POST['logout'])) {
    session_unset();
    session_destroy();
    header("Location: login.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>To-Do List</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body {
            background-image: url('https://media.istockphoto.com/id/2174867298/photo/rope-bows-on-fingers.jpg?s=1024x1024&w=is&k=20&c=-gj48THLPi11F5dRi92F4BBpXEY9zeIwQ1YJRm3S7h4='); /* Path to your background image */
            background-size: cover;
            background-position: center center;
            background-attachment: fixed;
            color: black;
        }

        .container {
            background-color: rgba(255, 255, 255, 0.8);
            border-radius: 10px;
            padding: 20px;
        }
        /* Custom styles for buttons in the actions column */
        .btn-group {
            display: flex;
            gap: 10px;  /* Adds space between buttons */
            flex-wrap: wrap;  /* Ensures buttons wrap in smaller screens */
        }

        /* Stack buttons vertically on smaller screens */
        @media (max-width: 576px) {
            .btn-group {
                flex-direction: column;  /* Stacks buttons vertically */
                align-items: flex-start;  /* Align buttons to the left */
            }

            .btn-group .btn {
                width: 100%;  /* Ensures both buttons have equal width on smaller screens */
            }
        }
    </style>
</head>
<body>
<div class="content-wrapper">
    <div class="container mt-5">
        <h2 class="text-center">To-Do List</h2>

        <!-- Success or Error Message -->
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success">
                <i class="fas fa-check"></i> <?= $_SESSION['success']; unset($_SESSION['success']); ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger">
                <?= $_SESSION['error']; unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>

        <!-- Form to Add or Update Task -->
        <form action="" method="POST">
            <div class="mb-3">
                <label for="task" class="form-label">Task</label>
                <input type="text" class="form-control" id="task" name="task" required value="<?= isset($edit_task) ? htmlspecialchars($edit_task['task']) : ''; ?>">
            </div>

            <div class="mb-3">
                <label for="description" class="form-label">Description</label>
                <textarea class="form-control" id="description" name="description"><?= isset($edit_task) ? htmlspecialchars($edit_task['description']) : ''; ?></textarea>
            </div>

            <div class="mb-3">
                <label for="status" class="form-label">Status</label>
                <select class="form-control" id="status" name="status">
                    <option value="Pending" <?= isset($edit_task) && $edit_task['status'] == 'Pending' ? 'selected' : ''; ?>>Pending</option>
                    <option value="Completed" <?= isset($edit_task) && $edit_task['status'] == 'Completed' ? 'selected' : ''; ?>>Completed</option>
                </select>
            </div>

            <?php if (isset($edit_task)): ?>
                <input type="hidden" name="task_id" value="<?= $edit_task['id']; ?>">
                <button type="submit" class="btn btn-warning">Update Task</button>
            <?php else: ?>
                <button type="submit" class="btn btn-success">Add Task</button>
            <?php endif; ?>
        </form>

        <!-- Logout Button -->
        <form action="" method="POST">
            <button type="submit" name="logout" class="btn btn-danger mt-3">Logout</button>
        </form>

        <hr>

        <!-- Task Table -->
        <table class="table table-bordered table-hover">
            <thead>
                <tr>
                    <th>Task</th>
                    <th>Description</th>
                    <th>Status</th>
                    <th>Created At</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($tasks as $task): ?>
                    <tr>
                        <td><?= htmlspecialchars($task['task']); ?></td>
                        <td><?= htmlspecialchars($task['description'] ?: 'No description'); ?></td>
                        <td>
                            <span class="badge <?= $task['status'] == 'Completed' ? 'bg-success' : 'bg-warning'; ?>">
                                <?= $task['status']; ?>
                            </span>
                        </td>
                        <td><?= date('Y-m-d H:i:s', strtotime($task['created_at'])); ?></td>
                        <td class="btn-group">
                            <a href="index.php?edit_id=<?= $task['id']; ?>" class="btn btn-warning btn-sm">Edit</a>
                            <a href="index.php?delete_id=<?= $task['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this task?')">Delete</a>
                            <a href="index.php?toggle_status=<?= $task['id']; ?>" class="btn btn-info btn-sm">
                                <?= $task['status'] == 'Completed' ? 'Mark as Pending' : 'Mark as Completed'; ?>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <!-- Pagination -->
        <nav>
            <ul class="pagination justify-content-center">
                <li class="page-item <?= $page <= 1 ? 'disabled' : ''; ?>">
                    <a class="page-link" href="?page=1">&laquo; First</a>
                </li>
                <li class="page-item <?= $page <= 1 ? 'disabled' : ''; ?>">
                    <a class="page-link" href="?page=<?= $page - 1; ?>">Previous</a>
                </li>
                <li class="page-item <?= $page >= $total_pages ? 'disabled' : ''; ?>">
                    <a class="page-link" href="?page=<?= $page + 1; ?>">Next</a>
                </li>
                <li class="page-item <?= $page >= $total_pages ? 'disabled' : ''; ?>">
                    <a class="page-link" href="?page=<?= $total_pages; ?>">Last &raquo;</a>
                </li>
            </ul>
        </nav>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Automatically hide success or error messages after 4 seconds
    setTimeout(function() {
        const successAlert = document.querySelector('.alert-success');
        const errorAlert = document.querySelector('.alert-danger');
        if (successAlert) {
            successAlert.style.display = 'none';
        }
        if (errorAlert) {
            errorAlert.style.display = 'none';
        }
    }, 4000);
</script>
</body>
</html>
