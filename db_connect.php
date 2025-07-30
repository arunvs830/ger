<?php
// FILE: db_connect.php
// This file contains the database connection settings and establishes the connection.

// --- Database Configuration ---
$db_host = 'localhost'; // Your database host (usually 'localhost' for XAMPP)
$db_user = 'root';      // Your database username (default for XAMPP)
$db_pass = '';          // Your database password (default for XAMPP is empty)
$db_name = 'glst';      // The name of your database

// --- Establish Database Connection ---
$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

// Check if the connection was successful. If not, stop the script and show an error.
if ($conn->connect_error) {
    die("Database Connection Failed: " . $conn->connect_error);
}

// Set the character set to utf8mb4 for proper handling of all characters.
$conn->set_charset("utf8mb4");
?>