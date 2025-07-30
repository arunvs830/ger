<?php
// FILE: logout.php (Updated with logging)
// This script updates the log table and then destroys the user's session.

session_start();
require_once 'db_connect.php';

// --- NEW: Record the logout time in tbl_log ---
// Check if a log_id was stored in the session when the user logged in.
if (isset($_SESSION['log_id'])) {
    $log_id = $_SESSION['log_id'];
    $logout_time = date("Y-m-d H:i:s"); // Get the current timestamp

    // Prepare and execute the UPDATE statement.
    $stmt = $conn->prepare("UPDATE tbl_log SET logout_time = ? WHERE log_id = ?");
    $stmt->bind_param("si", $logout_time, $log_id);
    $stmt->execute();
    $stmt->close();
}
// --- END of new logging code ---

// --- Standard Logout Procedure ---

// Unset all of the session variables.
$_SESSION = array();

// If it's desired to kill the session, also delete the session cookie.
// Note: This will destroy the session, and not just the session data!
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Finally, destroy the session.
session_destroy();

// Close the database connection.
$conn->close();

// Redirect the user back to the homepage after logging out.
header("Location: home.php");
exit();
?>
