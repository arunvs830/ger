<?php
// FILE: staff_actions.php (with Score functionality)
session_start();

if (!isset($_SESSION['username']) || $_SESSION['user_type'] !== 'staff') {
    header("Location: login.php");
    exit();
}

require_once 'db_connect.php';

$action = $_POST['action'] ?? '';

if ($action === 'evaluate_submission') {
    // --- 1. Get Data from Form ---
    $submission_id = $_POST['submission_id'];
    $staff_id = $_POST['staff_id'];
    $feedback_text = trim($_POST['feedback_text']);
    $score = $_POST['score']; // Get the score

    // --- 2. Validation ---
    if (empty($feedback_text) || !is_numeric($score)) {
        $_SESSION['message'] = "Feedback and a valid score are required.";
        $_SESSION['message_type'] = "error";
        header("Location: evaluate_submission.php?id=" . $submission_id);
        exit();
    }

    // --- 3. Check if an evaluation already exists ---
    $stmt_check = $conn->prepare("SELECT evaluation_id FROM tbl_assignment_evaluation WHERE submission_id = ?");
    $stmt_check->bind_param("i", $submission_id);
    $stmt_check->execute();
    $stmt_check->store_result();

    if ($stmt_check->num_rows > 0) {
        // Update existing evaluation
        $stmt = $conn->prepare("UPDATE tbl_assignment_evaluation SET feedback_text = ?, staff_id = ?, score = ? WHERE submission_id = ?");
        $stmt->bind_param("sidi", $feedback_text, $staff_id, $score, $submission_id);
    } else {
        // Insert new evaluation
        $stmt = $conn->prepare("INSERT INTO tbl_assignment_evaluation (submission_id, staff_id, score, feedback_text) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("iids", $submission_id, $staff_id, $score, $feedback_text);
    }
    $stmt_check->close();

    // --- 4. Execute and Redirect ---
    if ($stmt->execute()) {
        $_SESSION['message'] = "Evaluation submitted successfully!";
        $_SESSION['message_type'] = "success";
    } else {
        $_SESSION['message'] = "Error submitting evaluation: " . $stmt->error;
        $_SESSION['message_type'] = "error";
    }

    $stmt->close();
    $conn->close();

    header("Location: staff_dashboard.php");
    exit();
} else {
    header("Location: staff_dashboard.php");
    exit();
}
?>
