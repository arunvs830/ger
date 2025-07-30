<?php
// FILE: register.php (with Dynamic Price Display)
session_start();
require_once 'db_connect.php';

// Fetch courses with their fees to populate the dropdown menu
$courses_result = $conn->query("SELECT course_id, name, level, fee FROM tbl_course WHERE status = 'active' ORDER BY name");
$courses = [];
if ($courses_result && $courses_result->num_rows > 0) {
    while($row = $courses_result->fetch_assoc()) {
        $courses[] = $row;
    }
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Learn German</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }
        .register-bg {
            background-color: #f8f7ff;
        }
        #price-display {
            transition: opacity 0.3s ease-in-out;
        }
    </style>
</head>
<body class="register-bg">
    <div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="bg-white p-8 rounded-xl shadow-lg w-full max-w-lg">
            <h2 class="text-3xl font-bold text-center text-gray-800 mb-2">Create Your Account</h2>
            <p class="text-center text-gray-500 mb-8">Start your German learning journey today!</p>

            <?php
            if (isset($_SESSION['message'])) {
                $message_type_class = $_SESSION['message_type'] === 'success' 
                    ? 'bg-green-100 border-green-400 text-green-700' 
                    : 'bg-red-100 border-red-400 text-red-700';
                echo '<div class="border ' . $message_type_class . ' px-4 py-3 rounded-lg relative mb-6" role="alert">';
                echo '<span class="block sm:inline">' . htmlspecialchars($_SESSION['message']) . '</span>';
                echo '</div>';
                unset($_SESSION['message']);
                unset($_SESSION['message_type']);
            }
            ?>

            <form action="register_process.php" method="POST">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div>
                        <label for="first_name" class="block text-gray-700 text-sm font-bold mb-2">First Name</label>
                        <input type="text" name="first_name" id="first_name" class="shadow-sm appearance-none border rounded-lg w-full py-3 px-4 text-gray-700 leading-tight focus:outline-none focus:ring-2 focus:ring-violet-500" required>
                    </div>
                    <div>
                        <label for="last_name" class="block text-gray-700 text-sm font-bold mb-2">Last Name</label>
                        <input type="text" name="last_name" id="last_name" class="shadow-sm appearance-none border rounded-lg w-full py-3 px-4 text-gray-700 leading-tight focus:outline-none focus:ring-2 focus:ring-violet-500">
                    </div>
                </div>
                 <div class="mb-4">
                    <label for="email" class="block text-gray-700 text-sm font-bold mb-2">Email Address</label>
                    <input type="email" name="email" id="email" class="shadow-sm appearance-none border rounded-lg w-full py-3 px-4 text-gray-700 leading-tight focus:outline-none focus:ring-2 focus:ring-violet-500" required>
                </div>
                 <div class="mb-4">
                    <label for="phone" class="block text-gray-700 text-sm font-bold mb-2">Phone Number</label>
                    <input type="tel" name="phone" id="phone" class="shadow-sm appearance-none border rounded-lg w-full py-3 px-4 text-gray-700 leading-tight focus:outline-none focus:ring-2 focus:ring-violet-500">
                </div>
                 <div class="mb-4">
                    <label for="course_id" class="block text-gray-700 text-sm font-bold mb-2">Select a Course</label>
                    <select name="course_id" id="course_id" onchange="updatePrice()" class="shadow-sm appearance-none border rounded-lg w-full py-3 px-4 text-gray-700 leading-tight focus:outline-none focus:ring-2 focus:ring-violet-500" required>
                        <option value="" data-fee="0" disabled selected>Please choose a course...</option>
                        <?php foreach ($courses as $course): ?>
                            <option value="<?php echo $course['course_id']; ?>" data-fee="<?php echo htmlspecialchars($course['fee']); ?>">
                                <?php echo htmlspecialchars($course['name']) . ' (' . htmlspecialchars($course['level']) . ')'; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <!-- This is where the price will be displayed -->
                    <div id="price-display" class="hidden mt-2 p-3 bg-violet-50 rounded-lg text-center">
                        <span class="text-lg font-semibold text-violet-700"></span>
                    </div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                    <div>
                        <label for="password" class="block text-gray-700 text-sm font-bold mb-2">Password</label>
                        <input type="password" name="password" id="password" class="shadow-sm appearance-none border rounded-lg w-full py-3 px-4 text-gray-700 leading-tight focus:outline-none focus:ring-2 focus:ring-violet-500" required>
                    </div>
                    <div>
                        <label for="confirm_password" class="block text-gray-700 text-sm font-bold mb-2">Confirm Password</label>
                        <input type="password" name="confirm_password" id="confirm_password" class="shadow-sm appearance-none border rounded-lg w-full py-3 px-4 text-gray-700 leading-tight focus:outline-none focus:ring-2 focus:ring-violet-500" required>
                    </div>
                </div>
                <div class="flex items-center justify-between">
                    <button type="submit" class="bg-violet-600 hover:bg-violet-700 text-white font-bold py-3 px-6 rounded-lg focus:outline-none focus:shadow-outline w-full transition-transform transform hover:scale-105">
                        Proceed to Payment
                    </button>
                </div>
                <div class="text-center mt-6">
                    <p class="text-sm text-gray-600">
                        Already have an account? 
                        <a href="login.php" class="font-bold text-violet-600 hover:text-violet-800">
                            Log in here
                        </a>
                    </p>
                    <a href="home.php" class="inline-block mt-4 align-baseline font-bold text-sm text-violet-600 hover:text-violet-800">
                        &larr; Back to Home
                    </a>
                </div>
            </form>
        </div>
    </div>

    <script>
        function updatePrice() {
            const courseSelect = document.getElementById('course_id');
            const priceDisplay = document.getElementById('price-display');
            const priceText = priceDisplay.querySelector('span');
            
            // Get the selected option element
            const selectedOption = courseSelect.options[courseSelect.selectedIndex];
            
            // Get the fee from the data-fee attribute
            const fee = selectedOption.getAttribute('data-fee');

            if (fee && fee > 0) {
                priceText.textContent = `Course Fee: ₹${parseFloat(fee).toFixed(2)}`;
                priceDisplay.classList.remove('hidden');
            } else {
                priceDisplay.classList.add('hidden');
            }
        }
    </script>
</body>
</html>
