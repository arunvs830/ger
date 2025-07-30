<?php
// FILE: login_process.php (Updated with logging)
// This script handles the login logic and records the login event.

session_start(); 

require_once 'db_connect.php';

// --- Form Data Processing ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // --- User Authentication ---
    $stmt = $conn->prepare("SELECT username, user_type, password FROM tbl_login WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 1) {
        $user = $result->fetch_assoc();
        
        // Verify the plain text password
        if ($password === $user['password']) {
            // --- SUCCESSFUL LOGIN ---
            
            // Set session variables
            $_SESSION['user_id'] = session_id(); 
            $_SESSION['username'] = $user['username']; 
            $_SESSION['user_type'] = $user['user_type'];

            // --- NEW: Record the login event in tbl_log ---
            $login_time = date("Y-m-d H:i:s"); // Get current timestamp
            $log_stmt = $conn->prepare("INSERT INTO tbl_log (username, user_type, login_time) VALUES (?, ?, ?)");
            $log_stmt->bind_param("sss", $user['username'], $user['user_type'], $login_time);
            $log_stmt->execute();
            
            // Store the new log_id in the session to update it on logout
            $_SESSION['log_id'] = $conn->insert_id;
            
            $log_stmt->close();
            // --- END of new logging code ---

            // Redirect based on user type
            switch ($user['user_type']) {
                case 'admin':
                    header("Location: admin_dashboard.php");
                    exit();
                case 'staff':
                    header("Location: staff_dashboard.php");
                    exit();
                case 'student':
                    header("Location: student_dashboard.php");
                    exit();
                default:
                    header("Location: login.php?error=invalid_role");
                    exit();
            }
        } else {
            // Password is not valid
            header("Location: login.php?error=1");
            exit();
        }
    } else {
        // No user found with that username
        header("Location: login.php?error=1");
        exit();
    }

    $stmt->close();
    $conn->close();
} else {
    // Redirect if accessed directly
    header("Location: login.php");
    exit();
}
?>
