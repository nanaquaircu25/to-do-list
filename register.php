<?php
// Start the session to handle messages
session_start();
include 'db.php';  // Include the database connection file

// Check if the form is submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get the form data
    $username = $_POST['username'];
    $password = trim($_POST['password']);  // Trim the password to remove any unnecessary whitespace

    // Validate input: Check if both fields are filled
    if (empty($username) || empty($password)) {
        $_SESSION['error'] = "Username and password are required.";
    } else {
        // Password strength validation (minimum 8 characters, must include uppercase, lowercase, number, and special character)
        $passwordPattern = "/^(?=.*[A-Z])(?=.*[a-z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/";

        if (!preg_match($passwordPattern, $password)) {
            $_SESSION['error'] = "Password must be at least 8 characters long, include one uppercase letter, one number, and one special character.";
        } else {
            // Hash the password securely using password_hash
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            // Prepare SQL query to check if the username already exists
            $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                $_SESSION['error'] = "Username already taken. Please choose another one.";
            } else {
                // Insert new user into the database
                $stmt = $conn->prepare("INSERT INTO users (username, password) VALUES (?, ?)");
                $stmt->bind_param("ss", $username, $hashed_password);

                if ($stmt->execute()) {
                    $_SESSION['success'] = "Registration successful!";
                    header("Location: login.php");
                    exit();
                } else {
                    $_SESSION['error'] = "Error: " . $stmt->error;
                }
            }
            $stmt->close();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-image: url('https://media.istockphoto.com/id/2174867298/photo/rope-bows-on-fingers.jpg?s=1024x1024&w=is&k=20&c=-gj48THLPi11F5dRi92F4BBpXEY9zeIwQ1YJRm3S7h4='); /* Replace with your image path */
            background-size: cover;
            background-position: center center;
            background-attachment: fixed;
            color: black;
        }

        .container {
            background-color: rgba(255, 255, 255, 0.8);
            border-radius: 10px;
            padding: 20px;
            max-width: 400px;  /* Set the maximum width of the form */
            margin: auto;  /* Center the form horizontally */
        }
    </style>
    <script>
        function validatePassword() {
            var password = document.getElementById('password').value;
            var passwordCriteria = document.getElementById('passwordCriteria');
            var regex = /^(?=.*[A-Z])(?=.*[a-z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/;

            var criteriaMet = [
                /[A-Z]/.test(password),
                /[a-z]/.test(password),
                /\d/.test(password),
                /[@$!%*?&]/.test(password)
            ];

            passwordCriteria.innerHTML = '';
            criteriaMet.forEach((met, index) => {
                var listItem = document.createElement('li');
                listItem.style.color = met ? 'green' : 'red';
                listItem.textContent = ['At least one uppercase letter', 'At least one lowercase letter', 'At least one number', 'At least one special character'][index];
                passwordCriteria.appendChild(listItem);
            });

            if (regex.test(password)) {
                document.getElementById('password').style.borderColor = 'green';
            } else {
                document.getElementById('password').style.borderColor = 'red';
            }
        }
    </script>
</head>
<body>
    <div class="container mt-5">
        <h2 class="text-center">Register</h2>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger"><?= $_SESSION['error']; ?></div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success"><?= $_SESSION['success']; ?></div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <form action="register.php" method="POST">
            <div class="mb-3">
                <label for="username" class="form-label">Username</label>
                <input type="text" class="form-control" id="username" name="username" required>
            </div>
            <div class="mb-3">
                <label for="password" class="form-label">Password</label>
                <input type="password" class="form-control" id="password" name="password" required onkeyup="validatePassword()">
                <small>Password must be at least 8 characters long, include one uppercase letter, one number, and one special character.</small>
                <ul id="passwordCriteria" style="list-style-type: none;"></ul>
            </div>
            <button type="submit" class="btn btn-primary">Register</button>
        </form>
        <p class="mt-3">Already have an account? <a href="login.php">Log in</a></p>
    </div>
</body>
</html>
