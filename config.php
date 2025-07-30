<?php
// FILE: config.php
// Store your Stripe API Keys here.

// Replace these with your actual Test Keys from the Stripe dashboard
$stripe_publishable_key = 'pk_test_51RqAJuJCsX4MrSOyCexnrkNhDnkbUV9me4ICpdkbFzbt5y73NMbOOC02YgQhAsWuewvekxhLG1DqJb9VWdFAHSxO00gpycSCAn';
$stripe_secret_key = 'sk_test_51RqAJuJCsX4MrSOyY9cX8Yz6Hm3mcn2XPNJWxofatr2viN6qHgjfvtD5ySD7NdhoaMdAks6DLEKNslt0biWSl8cT00Szrx90Rr';

// This is the URL of your project on your local server
$domain_url = 'http://localhost/gr'; // Make sure this matches your XAMPP setup

// Error reporting for development
error_reporting(E_ALL);
ini_set('display_errors', 1);
?>
