<?php

// Step 1: Include the main library file from the unzipped folder.
// Update the path to where you placed the folder.
require_once 'razorpay-php-2.9.1/Razorpay.php';

// Step 2: Import the Api class.
use Razorpay\Api\Api;

// Step 3: Initialize the API with your Test Keys.
// You still need to get these from the Razorpay Dashboard.
$keyId = 'YOUR_TEST_KEY_ID';
$keySecret = 'YOUR_TEST_KEY_SECRET';

$api = new Api($keyId, $keySecret);

// Step 4: Now you can use the $api object to make calls.
// For example, fetching all orders.
try {
    $orders = $api->order->all(); // Fetches first 10 orders
    
    // The print_r function will display the contents of the response.
    echo '<pre>';
    print_r($orders);
    echo '</pre>';

} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage();
}

?>