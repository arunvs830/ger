<?php
// FILE: payment.php (with Stripe Checkout)
session_start();

// Security Check: Redirect if registration data is missing
if (!isset($_SESSION['registration_data'])) {
    header("Location: register.php");
    exit();
}

// Include required files
require_once 'config.php';
require_once 'db_connect.php';
require_once 'stripe-php/init.php'; // Include the Stripe library

// Set your secret key.
\Stripe\Stripe::setApiKey($stripe_secret_key);

// Get data from the session
$reg_data = $_SESSION['registration_data'];
$course_id = $reg_data['course_id'];

// Fetch course details from the database
$stmt = $conn->prepare("SELECT name, level, fee FROM tbl_course WHERE course_id = ?");
$stmt->bind_param("i", $course_id);
$stmt->execute();
$course = $stmt->get_result()->fetch_assoc();
$stmt->close();
$conn->close();

if (!$course) { die("Error: Course not found."); }

$amount_in_cents = $course['fee'] * 100; // Stripe requires amount in cents
$course_full_name = htmlspecialchars($course['name'] . ' (' . $course['level'] . ')');
$currency = 'eur'; // You can change this to 'usd', 'inr', etc.

// --- Create a Stripe Checkout Session ---
try {
    $checkout_session = \Stripe\Checkout\Session::create([
        'payment_method_types' => ['card'],
        'line_items' => [[
            'price_data' => [
                'currency' => $currency,
                'product_data' => [
                    'name' => $course_full_name,
                    'description' => 'Enrollment for the Learn German online course.',
                ],
                'unit_amount' => $amount_in_cents,
            ],
            'quantity' => 1,
        ]],
        'mode' => 'payment',
        'success_url' => $domain_url . '/payment_process.php?session_id={CHECKOUT_SESSION_ID}',
        'cancel_url' => $domain_url . '/payment.php?status=cancelled',
    ]);
} catch (Exception $e) {
    // Handle API error
    die('Error creating Stripe session: ' . $e->getMessage());
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirm Your Enrollment</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- Stripe.js library -->
    <script src="https://js.stripe.com/v3/"></script>
</head>
<body class="bg-gray-100">
    <div class="min-h-screen flex items-center justify-center">
        <div class="bg-white p-8 rounded-xl shadow-lg w-full max-w-lg text-center">
            <h2 class="text-3xl font-bold text-gray-800 mb-4">Complete Your Enrollment</h2>
            <p class="text-gray-500 mb-8">You will be redirected to our secure payment partner, Stripe.</p>
            
            <?php if(isset($_GET['status']) && $_GET['status'] === 'cancelled'): ?>
                <div class="bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded-lg relative mb-6" role="alert">
                    Payment was cancelled. You can try again.
                </div>
            <?php endif; ?>

            <div class="bg-gray-50 border rounded-lg p-6 text-left mb-8">
                <h3 class="text-xl font-semibold text-gray-800 mb-4">Order Summary</h3>
                <div class="flex justify-between items-center border-b pb-4">
                    <span class="text-gray-600">Course:</span>
                    <span class="font-bold text-gray-900"><?php echo $course_full_name; ?></span>
                </div>
                <div class="flex justify-between items-center pt-4">
                    <span class="text-gray-600 text-2xl">Total:</span>
                    <span class="font-bold text-violet-700 text-3xl">₹<?php echo htmlspecialchars(number_format($course['fee'], 2)); ?></span>
                </div>
            </div>
            
            <button id="pay-btn" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-3 px-6 rounded-lg">
                Proceed to Payment
            </button>
        </div>
    </div>

    <script>
        const stripe = Stripe('<?php echo $stripe_publishable_key; ?>');
        const payBtn = document.getElementById('pay-btn');

        payBtn.addEventListener('click', function () {
            stripe.redirectToCheckout({
                sessionId: '<?php echo $checkout_session->id; ?>'
            }).then(function (result) {
                // If `redirectToCheckout` fails due to a browser issue, display the error
                if (result.error) {
                    alert(result.error.message);
                }
            });
        });
    </script>
</body>
</html>
