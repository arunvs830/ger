<?php
// FILE: payment_process.php (with Stripe Verification)
// This script is the SUCCESS URL. It verifies the payment and finalizes registration.

session_start();
require_once 'config.php'; // Contains your Stripe keys and domain URL
require_once 'db_connect.php';
require_once 'stripe-php/init.php'; // The Stripe PHP library

// Security Check: Ensure registration data and a Stripe session ID exist.
if (!isset($_SESSION['registration_data']) || !isset($_GET['session_id'])) {
    // If not, something is wrong, so send them back to the start.
    header("Location: register.php");
    exit();
}

// Initialize the Stripe API with your secret key from config.php
\Stripe\Stripe::setApiKey($stripe_secret_key);
$checkout_session_id = $_GET['session_id'];

try {
    // Retrieve the session from Stripe's servers to verify it's a valid, successful payment
    $session = \Stripe\Checkout\Session::retrieve($checkout_session_id);

    // Check if the payment status is 'paid'.
    if ($session->payment_status == 'paid') {
        // --- PAYMENT IS VERIFIED ---
        
        // 1. Retrieve the student's registration data from the session
        $reg_data = $_SESSION['registration_data'];
        $first_name = $reg_data['first_name'];
        $last_name = $reg_data['last_name'];
        $email = $reg_data['email'];
        $phone = $reg_data['phone'];
        $course_id = $reg_data['course_id'];
        $password = $reg_data['password'];
        $username = $email;
        
        // 2. Get the transaction details from the Stripe session object
        $txn_reference = $session->payment_intent; // This is the unique Stripe transaction ID
        $amount_paid = $session->amount_total / 100; // Stripe provides amount in cents, so we convert it

        // 3. Perform all database insertions within a single transaction
        $conn->begin_transaction();
        try {
            // Step A: Insert into tbl_login
            $stmt_login = $conn->prepare("INSERT INTO tbl_login (username, password, user_type, status) VALUES (?, ?, 'student', 'active')");
            $stmt_login->bind_param("ss", $username, $password);
            $stmt_login->execute();
            
            // Step B: Insert into tbl_student
            $registered_on = date('Y-m-d');
            $stmt_student = $conn->prepare("INSERT INTO tbl_student (course_id, first_name, last_name, email, phone, registered_on, username, password) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt_student->bind_param("isssssss", $course_id, $first_name, $last_name, $email, $phone, $registered_on, $username, $password);
            $stmt_student->execute();
            $student_id = $conn->insert_id; // Get the ID of the new student for the payment record

            // Step C: Insert into tbl_payment
            $payment_date = date("Y-m-d H:i:s");
            $stmt_payment = $conn->prepare("INSERT INTO tbl_payment (student_id, amount, payment_date, txn_reference) VALUES (?, ?, ?, ?)");
            $stmt_payment->bind_param("idss", $student_id, $amount_paid, $payment_date, $txn_reference);
            $stmt_payment->execute();

            // If all database queries were successful, commit the changes
            $conn->commit();

            // 4. Clean Up and Redirect to Login
            unset($_SESSION['registration_data']); // Clear the temporary registration data
            $_SESSION['message'] = "Registration and payment successful! You can now log in.";
            $_SESSION['message_type'] = "success";
            header("Location: login.php");
            exit();

        } catch (mysqli_sql_exception $exception) {
            $conn->rollback(); // If any database query failed, undo all changes
            // In a real application, you would also issue a refund via the Stripe API here.
            $_SESSION['message'] = "A database error occurred after your payment was processed. Please contact support. Error: " . $exception->getMessage();
            $_SESSION['message_type'] = "error";
            header("Location: register.php");
            exit();
        }
    } else {
        // This case handles if the payment status is not 'paid' for some reason.
        $_SESSION['message'] = "Your payment was not successful. Please try again.";
        $_SESSION['message_type'] = "error";
        header("Location: register.php");
        exit();
    }
} catch (Exception $e) {
    // This handles errors like an invalid session ID or other Stripe API issues.
    $_SESSION['message'] = "There was an error verifying your payment: " . $e->getMessage();
    $_SESSION['message_type'] = "error";
    header("Location: register.php");
    exit();
}
?>
