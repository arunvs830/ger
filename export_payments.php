<?php
// FILE: export_payments.php
// This script generates a downloadable CSV file of payment transactions.
session_start();

// Security Check: Only admins can access this.
if (!isset($_SESSION['username']) || $_SESSION['user_type'] !== 'admin') {
    die("Access Denied.");
}

require_once 'db_connect.php';

// --- Handle Date Filter for Payments ---
$start_date = $_GET['start_date'] ?? '';
$end_date = $_GET['end_date'] ?? '';
$payment_where_clause = '';

if (!empty($start_date) && !empty($end_date)) {
    $payment_where_clause = " WHERE p.payment_date BETWEEN '{$start_date} 00:00:00' AND '{$end_date} 23:59:59'";
}

// Fetch payments based on the filter
$payments_query = "
    SELECT 
        p.payment_id,
        CONCAT(s.first_name, ' ', s.last_name) AS student_name,
        CONCAT(c.name, ' (', c.level, ')') AS course_name,
        p.amount,
        p.payment_date,
        p.txn_reference
    FROM tbl_payment p
    JOIN tbl_student s ON p.student_id = s.student_id
    JOIN tbl_course c ON s.course_id = c.course_id
    {$payment_where_clause}
    ORDER BY p.payment_id ASC
";
$payments_result = $conn->query($payments_query);

// --- Generate CSV File ---
$filename = "payment_report_" . date('Y-m-d') . ".csv";

// Set headers to force download
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=' . $filename);

// Create a file pointer connected to the output stream
$output = fopen('php://output', 'w');

// Output the column headings
fputcsv($output, ['Payment ID', 'Student Name', 'Course', 'Amount (Rs)', 'Payment Date', 'Transaction ID']);

// Loop through the rows and output them
if ($payments_result->num_rows > 0) {
    while ($row = $payments_result->fetch_assoc()) {
        fputcsv($output, $row);
    }
}

fclose($output);
$conn->close();
exit();
