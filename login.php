<?php
// FILE: login.php (with Registration Message)
session_start(); // Start session to check for messages
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Learn German</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }
        .login-bg {
            background-color: #f8f7ff;
        }
    </style>
</head>
<body class="login-bg">
    <div class="min-h-screen flex items-center justify-center">
        <div class="bg-white p-8 rounded-xl shadow-lg w-full max-w-md">
            
            <!-- Dynamic Welcome Message -->
            <?php if (isset($_SESSION['message']) && $_SESSION['message_type'] === 'success'): ?>
                <h2 class="text-3xl font-bold text-center text-green-600 mb-2">Registration Successful!</h2>
                <p class="text-center text-gray-500 mb-8">Please log in to continue.</p>
                <?php
                    // Clear the message after displaying it
                    unset($_SESSION['message']);
                    unset($_SESSION['message_type']);
                ?>
            <?php else: ?>
                <h2 class="text-3xl font-bold text-center text-gray-800 mb-2">Welcome Back!</h2>
                <p class="text-center text-gray-500 mb-8">Log in to your account</p>
            <?php endif; ?>


            <?php
            // Display an error message if login fails
            if (isset($_GET['error'])) {
                echo '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg relative mb-6" role="alert">';
                echo '<strong class="font-bold">Error:</strong>';
                echo '<span class="block sm:inline"> Invalid username or password. Please try again.</span>';
                echo '</div>';
            }
            ?>

            <form action="login_process.php" method="POST">
                <div class="mb-4">
                    <label for="username" class="block text-gray-700 text-sm font-bold mb-2">Username (Your Email)</label>
                    <input type="text" name="username" id="username" class="shadow-sm appearance-none border rounded-lg w-full py-3 px-4 text-gray-700 leading-tight focus:outline-none focus:ring-2 focus:ring-violet-500" placeholder="you@example.com" required>
                </div>
                <div class="mb-6">
                    <label for="password" class="block text-gray-700 text-sm font-bold mb-2">Password</label>
                    <input type="password" name="password" id="password" class="shadow-sm appearance-none border rounded-lg w-full py-3 px-4 text-gray-700 mb-3 leading-tight focus:outline-none focus:ring-2 focus:ring-violet-500" placeholder="******************" required>
                </div>
                <div class="flex items-center justify-between">
                    <button type="submit" class="bg-violet-600 hover:bg-violet-700 text-white font-bold py-3 px-6 rounded-lg focus:outline-none focus:shadow-outline w-full transition-transform transform hover:scale-105">
                        Log In
                    </button>
                </div>
                <div class="text-center mt-6">
                    <a href="home.php" class="inline-block align-baseline font-bold text-sm text-violet-600 hover:text-violet-800">
                        &larr; Back to Home
                    </a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
