<?php
// FILE: student_actions.php
// This script handles form submissions from the student dashboard.

session_start();

// Security Check: Only students can submit assignments.
if (!isset($_SESSION['username']) || $_SESSION['user_type'] !== 'student') {
    header("Location: login.php");
    exit();
}

require_once 'db_connect.php';

// Check which action is being performed
$action = $_POST['action'] ?? '';

if ($action === 'submit_assignment') {
    // --- 1. Get Data from Form ---
    $assignment_id = $_POST['assignment_id'];
    $student_id = $_POST['student_id'];
    $submission_text = trim($_POST['submission_text']);

    // --- 2. Validation ---
    if (empty($submission_text)) {
        $_SESSION['message'] = "Your submission cannot be empty.";
        $_SESSION['message_type'] = "error";
        header("Location: student_dashboard.php");
        exit();
    }

    // --- 3. Insert into Database ---
    $stmt = $conn->prepare("INSERT INTO tbl_assignment_submission (assignment_id, student_id, submission_text) VALUES (?, ?, ?)");
    $stmt->bind_param("iis", $assignment_id, $student_id, $submission_text);

    if ($stmt->execute()) {
        $_SESSION['message'] = "Assignment submitted successfully!";
        $_SESSION['message_type'] = "success";
    } else {
        $_SESSION['message'] = "Error submitting assignment: " . $stmt->error;
        $_SESSION['message_type'] = "error";
    }

    $stmt->close();
    $conn->close();

    // --- 4. Redirect back to the dashboard ---
    header("Location: student_dashboard.php");
    exit();
} else {
    // If the action is unknown, just redirect back.
    header("Location: student_dashboard.php");
    exit();
}
?>
