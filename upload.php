<?php
include 'db.php';

if ($conn) {
    echo "Successfully connected to the database!";
} else {
    echo "Failed to connect to the database!";
}
?>
