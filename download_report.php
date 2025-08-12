<?php
// FILE: download_report.php
// This script generates and downloads reports as CSV files for Excel.
session_start();

// Security Check: Only admins can access this.
if (!isset($_SESSION['username']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: login.php");
    exit();
}

require_once 'db_connect.php';

$report_type = $_GET['type'] ?? '';

// --- Generate Payments CSV ---
if ($report_type === 'payments') {
    $start_date = $_GET['start_date'] ?? '';
    $end_date = $_GET['end_date'] ?? '';
    $where_clause = '';

    if (!empty($start_date) && !empty($end_date)) {
        $start_date_safe = $conn->real_escape_string($start_date);
        $end_date_safe = $conn->real_escape_string($end_date);
        $where_clause = " WHERE p.payment_date BETWEEN '{$start_date_safe} 00:00:00' AND '{$end_date_safe} 23:59:59'";
    }

    $query = "SELECT p.payment_id, s.first_name, s.last_name, c.name AS course_name, c.level, p.amount, p.payment_date, p.txn_reference FROM tbl_payment p JOIN tbl_student s ON p.student_id = s.student_id JOIN tbl_course c ON s.course_id = c.course_id" . $where_clause . " ORDER BY p.payment_id DESC";
    $result = $conn->query($query);

    $filename = "payment_report_" . date('Y-m-d') . ".csv";
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');

    $output = fopen('php://output', 'w');
    fputcsv($output, ['Payment ID', 'Student Name', 'Course', 'Amount (Rs)', 'Payment Date', 'Transaction ID']);

    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            fputcsv($output, [
                $row['payment_id'],
                $row['first_name'] . ' ' . $row['last_name'],
                $row['course_name'] . ' (' . $row['level'] . ')',
                number_format($row['amount'], 2),
                date("d-m-Y H:i:s", strtotime($row['payment_date'])),
                $row['txn_reference']
            ]);
        }
    }
    fclose($output);
    exit();
}

// --- Generate Students CSV ---
if ($report_type === 'students') {
    $course_filter = $_GET['course_filter'] ?? '';
    $where_clause = '';
    if (!empty($course_filter)) {
        $where_clause = " WHERE s.course_id = " . intval($course_filter);
    }

    $query = "SELECT s.student_id, s.first_name, s.last_name, s.email, s.phone, c.name AS course_name, c.level, s.registered_on FROM tbl_student s JOIN tbl_course c ON s.course_id = c.course_id" . $where_clause . " ORDER BY s.student_id DESC";
    $result = $conn->query($query);
    
    $filename = "student_report_" . date('Y-m-d') . ".csv";
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');

    $output = fopen('php://output', 'w');
    fputcsv($output, ['Student ID', 'Full Name', 'Email', 'Phone', 'Course', 'Registered On']);

    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            fputcsv($output, [
                $row['student_id'],
                $row['first_name'] . ' ' . $row['last_name'],
                $row['email'],
                $row['phone'],
                $row['course_name'] . ' (' . $row['level'] . ')',
                date("d-m-Y", strtotime($row['registered_on']))
            ]);
        }
    }
    fclose($output);
    exit();
}

// Redirect if report type is invalid
header("Location: admin_dashboard.php");
exit();
?>
