<?php
// FILE: register_process.php (Updated to redirect to payment)
// This script validates registration data and prepares it for the payment step.

session_start();
require_once 'db_connect.php';

// Check if the form was submitted using the POST method
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // --- 1. Retrieve and Validate Form Data ---
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $course_id = $_POST['course_id'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Basic validation
    if (empty($first_name) || empty($email) || empty($course_id) || empty($password)) {
        $_SESSION['message'] = "Please fill in all required fields.";
        $_SESSION['message_type'] = "error";
        header("Location: register.php");
        exit();
    }

    if ($password !== $confirm_password) {
        $_SESSION['message'] = "Passwords do not match.";
        $_SESSION['message_type'] = "error";
        header("Location: register.php");
        exit();
    }

    // Check if the email (which is used as the username) is already in use
    $stmt_check = $conn->prepare("SELECT username FROM tbl_login WHERE username = ?");
    $stmt_check->bind_param("s", $email);
    $stmt_check->execute();
    $stmt_check->store_result();

    if ($stmt_check->num_rows > 0) {
        $_SESSION['message'] = "An account with this email address already exists.";
        $_SESSION['message_type'] = "error";
        $stmt_check->close();
        header("Location: register.php");
        exit();
    }
    $stmt_check->close();

    // --- 2. Store Registration Data in Session ---
    // Instead of inserting into the DB directly, we save the data in the session.
    // This data will be used after the payment is "confirmed" on the next page.
    $_SESSION['registration_data'] = [
        'first_name' => $first_name,
        'last_name' => $last_name,
        'email' => $email,
        'phone' => $phone,
        'course_id' => $course_id,
        'password' => $password
    ];

    // --- 3. Redirect to the Payment Page ---
    // The registration is not complete until the payment is processed.
    header("Location: payment.php");
    exit();

} else {
    // If someone tries to access this page directly, send them back to the registration form.
    header("Location: register.php");
    exit();
}
?>
