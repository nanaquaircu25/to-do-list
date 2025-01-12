<?php
$servername = "localhost";  // The host provided by InfinityFree
$username = "root";  // The username you created
$password = "";  // The password for that user
$dbname = "todo_db";  // The database name you created (can be different from subdomain)

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
