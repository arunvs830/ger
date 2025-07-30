<?php
// FILE: admin_actions.php (Complete and Final Version)
// This script handles all form submissions and actions from the admin dashboard.

session_start();

// Security Check: Only admins can perform these actions.
if (!isset($_SESSION['username']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: login.php");
    exit();
}

require_once 'db_connect.php';

// Determine the action from the form (POST) or link (GET)
$action = $_POST['action'] ?? $_GET['action'] ?? '';

// --- Handle POST requests (for adding and editing data) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    switch ($action) {
        // --- Add Course ---
        case 'add_course':
            $name = $_POST['name'];
            $level = $_POST['level'];
            $fee = $_POST['fee'];
            $description = $_POST['description'];

            $stmt = $conn->prepare("INSERT INTO tbl_course (name, level, fee, description) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssds", $name, $level, $fee, $description);
            if ($stmt->execute()) {
                $_SESSION['message'] = "Course added successfully!";
                $_SESSION['message_type'] = "success";
            } else {
                $_SESSION['message'] = "Error adding course: " . $stmt->error;
                $_SESSION['message_type'] = "error";
            }
            $stmt->close();
            break;

        // --- Edit Course ---
        case 'edit_course':
            $course_id = $_POST['course_id'];
            $name = $_POST['name'];
            $level = $_POST['level'];
            $fee = $_POST['fee'];
            $description = $_POST['description'];

            $stmt = $conn->prepare("UPDATE tbl_course SET name = ?, level = ?, fee = ?, description = ? WHERE course_id = ?");
            $stmt->bind_param("ssdsi", $name, $level, $fee, $description, $course_id);
            if ($stmt->execute()) {
                $_SESSION['message'] = "Course updated successfully!";
                $_SESSION['message_type'] = "success";
            } else {
                $_SESSION['message'] = "Error updating course: " . $stmt->error;
                $_SESSION['message_type'] = "error";
            }
            $stmt->close();
            break;

        // --- Add Video ---
        case 'add_video':
            $title = $_POST['title'];
            $course_id = $_POST['course_id'];
            $video_url = $_POST['video_url'];
            $description = $_POST['description'];

            $stmt = $conn->prepare("INSERT INTO tbl_study_material (course_id, title, video_url, description) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("isss", $course_id, $title, $video_url, $description);
            
            if ($stmt->execute()) {
                $_SESSION['message'] = "Video added successfully!";
                $_SESSION['message_type'] = "success";
            } else {
                $_SESSION['message'] = "Error adding video: " . $stmt->error;
                $_SESSION['message_type'] = "error";
            }
            $stmt->close();
            break;

        // --- Edit Video ---
        case 'edit_video':
            $material_id = $_POST['material_id'];
            $title = $_POST['title'];
            $course_id = $_POST['course_id'];
            $video_url = $_POST['video_url'];
            $description = $_POST['description'];

            $stmt = $conn->prepare("UPDATE tbl_study_material SET title = ?, course_id = ?, video_url = ?, description = ? WHERE material_id = ?");
            $stmt->bind_param("sissi", $title, $course_id, $video_url, $description, $material_id);
            
            if ($stmt->execute()) {
                $_SESSION['message'] = "Video updated successfully!";
                $_SESSION['message_type'] = "success";
            } else {
                $_SESSION['message'] = "Error updating video: " . $stmt->error;
                $_SESSION['message_type'] = "error";
            }
            $stmt->close();
            break;

        // --- Add Assignment ---
        case 'add_assignment':
            $title = $_POST['title'];
            $material_id = $_POST['material_id'];
            $instructions = $_POST['instructions'];

            $stmt = $conn->prepare("INSERT INTO tbl_assignment (material_id, title, instructions) VALUES (?, ?, ?)");
            $stmt->bind_param("iss", $material_id, $title, $instructions);

            if ($stmt->execute()) {
                $_SESSION['message'] = "Assignment added successfully!";
                $_SESSION['message_type'] = "success";
            } else {
                $_SESSION['message'] = "Error adding assignment: " . $stmt->error;
                $_SESSION['message_type'] = "error";
            }
            $stmt->close();
            break;

        // --- Edit Assignment ---
        case 'edit_assignment':
            $assignment_id = $_POST['assignment_id'];
            $title = $_POST['title'];
            $material_id = $_POST['material_id'];
            $instructions = $_POST['instructions'];

            $stmt = $conn->prepare("UPDATE tbl_assignment SET title = ?, material_id = ?, instructions = ? WHERE assignment_id = ?");
            $stmt->bind_param("sisi", $title, $material_id, $instructions, $assignment_id);

            if ($stmt->execute()) {
                $_SESSION['message'] = "Assignment updated successfully!";
                $_SESSION['message_type'] = "success";
            } else {
                $_SESSION['message'] = "Error updating assignment: " . $stmt->error;
                $_SESSION['message_type'] = "error";
            }
            $stmt->close();
            break;

        // --- Add Staff ---
        case 'add_staff':
            $first_name = $_POST['first_name'];
            $last_name = $_POST['last_name'];
            $email = $_POST['email'];
            $phone = $_POST['phone'];
            $password = $_POST['password'];
            $course_id = $_POST['course_id'];
            $username = $email; // Using email as the username for login

            // Check if username already exists
            $stmt_check = $conn->prepare("SELECT username FROM tbl_login WHERE username = ?");
            $stmt_check->bind_param("s", $username);
            $stmt_check->execute();
            if ($stmt_check->get_result()->num_rows > 0) {
                $_SESSION['message'] = "Error: A user with this email already exists.";
                $_SESSION['message_type'] = "error";
                header("Location: admin_dashboard.php");
                exit();
            }
            $stmt_check->close();

            $conn->begin_transaction();
            try {
                // 1. Insert into tbl_login
                $stmt_login = $conn->prepare("INSERT INTO tbl_login (username, password, user_type) VALUES (?, ?, 'staff')");
                $stmt_login->bind_param("ss", $username, $password);
                $stmt_login->execute();

                // 2. Insert into tbl_staff
                $stmt_staff = $conn->prepare("INSERT INTO tbl_staff (course_id, first_name, last_name, email, phone, password) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt_staff->bind_param("isssss", $course_id, $first_name, $last_name, $email, $phone, $password);
                $stmt_staff->execute();

                $conn->commit();
                $_SESSION['message'] = "Staff member added successfully!";
                $_SESSION['message_type'] = "success";

            } catch (mysqli_sql_exception $exception) {
                $conn->rollback();
                $_SESSION['message'] = "Error adding staff: " . $exception->getMessage();
                $_SESSION['message_type'] = "error";
            }
            break;
    }
}

// --- Handle GET requests (for deactivating and reactivating) ---
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $id = $_GET['id'] ?? 0;
    $status = $_GET['status'] ?? ''; // 'inactive' or 'active'

    switch ($action) {
        // --- Course Status Change ---
        case 'set_course_status':
            $stmt = $conn->prepare("UPDATE tbl_course SET status = ? WHERE course_id = ?");
            $stmt->bind_param("si", $status, $id);
            $stmt->execute();
            $_SESSION['message'] = "Course status updated.";
            $_SESSION['message_type'] = "success";
            break;

        // --- Video Status Change ---
        case 'set_video_status':
            $stmt = $conn->prepare("UPDATE tbl_study_material SET status = ? WHERE material_id = ?");
            $stmt->bind_param("si", $status, $id);
            $stmt->execute();
            $_SESSION['message'] = "Video status updated.";
            $_SESSION['message_type'] = "success";
            break;

        // --- Assignment Status Change ---
        case 'set_assignment_status':
            $stmt = $conn->prepare("UPDATE tbl_assignment SET status = ? WHERE assignment_id = ?");
            $stmt->bind_param("si", $status, $id);
            $stmt->execute();
            $_SESSION['message'] = "Assignment status updated.";
            $_SESSION['message_type'] = "success";
            break;
        
        // --- Staff Status Change ---
        case 'set_staff_status':
            $stmt_get_email = $conn->prepare("SELECT email FROM tbl_staff WHERE staff_id = ?");
            $stmt_get_email->bind_param("i", $id);
            $stmt_get_email->execute();
            $result = $stmt_get_email->get_result();
            if ($result->num_rows > 0) {
                $staff_email = $result->fetch_assoc()['email'];

                $conn->begin_transaction();
                try {
                    $stmt_staff = $conn->prepare("UPDATE tbl_staff SET status = ? WHERE staff_id = ?");
                    $stmt_staff->bind_param("si", $status, $id);
                    $stmt_staff->execute();

                    $stmt_login = $conn->prepare("UPDATE tbl_login SET status = ? WHERE username = ?");
                    $stmt_login->bind_param("ss", $status, $staff_email);
                    $stmt_login->execute();
                    
                    $conn->commit();
                    $_SESSION['message'] = "Staff member status updated.";
                    $_SESSION['message_type'] = "success";
                } catch (Exception $e) {
                    $conn->rollback();
                    $_SESSION['message'] = "Error updating staff status.";
                    $_SESSION['message_type'] = "error";
                }
            }
            break;
    }
}

// Redirect back to the dashboard after the action is complete
header("Location: admin_dashboard.php");
exit();
?>
