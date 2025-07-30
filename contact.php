<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us - Learn German</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }
        .hero-bg {
            background-color: #f8f7ff;
        }
        .social-icon {
            transition: transform 0.3s ease, color 0.3s ease;
        }
        .social-icon:hover {
            transform: scale(1.2);
            color: #a78bfa; /* A lighter violet on hover */
        }
    </style>
</head>
<body class="bg-white">

    <!-- Header -->
    <header class="bg-white shadow-sm sticky top-0 z-50">
        <nav class="container mx-auto px-6 py-4 flex justify-between items-center">
            <a href="home.php" class="text-2xl font-bold text-violet-700">Learn German</a>
            <div class="hidden md:flex items-center space-x-8">
                <a href="home.php" class="text-gray-600 hover:text-violet-700">Home</a>
                <a href="home.php#courses" class="text-gray-600 hover:text-violet-700">Courses</a>
                <a href="contact.php" class="text-violet-700 font-semibold border-b-2 border-violet-700">Contact</a>
            </div>
            <div class="flex items-center space-x-4">
                <a href="login.php" class="hidden md:block text-gray-600 hover:text-violet-700">Login</a>
                <a href="register.php" class="bg-violet-600 hover:bg-violet-700 text-white px-4 py-2 rounded-lg transition-colors">Register</a>
            </div>
        </nav>
    </header>

    <!-- Main Content -->
    <main class="hero-bg py-16 md:py-24">
        <div class="container mx-auto px-6">
            <div class="text-center">
                <h1 class="text-4xl md:text-5xl font-bold text-gray-800">Contact Us</h1>
                <p class="text-lg text-gray-600 mt-4 max-w-2xl mx-auto">We are here to help. Reach out to us through any of the methods below.</p>
            </div>

            <div class="mt-16 max-w-4xl mx-auto grid md:grid-cols-2 gap-12 items-center">
                <!-- Contact Details -->
                <div class="bg-white p-8 rounded-xl shadow-lg">
                    <h3 class="text-2xl font-bold text-gray-800 mb-6">Our Information</h3>
                    <div class="space-y-6 text-gray-700">
                        <div class="flex items-start">
                            <i class="fas fa-map-marker-alt w-6 text-violet-600 mt-1 text-xl"></i>
                            <div>
                                <h4 class="font-semibold">Our Office</h4>
                                <p>123 Deutsch Lane, Berlin, 10115, Germany</p>
                            </div>
                        </div>
                        <div class="flex items-start">
                            <i class="fas fa-envelope w-6 text-violet-600 mt-1 text-xl"></i>
                            <div>
                                <h4 class="font-semibold">Email Us</h4>
                                <p>contact@learngerman.com</p>
                            </div>
                        </div>
                        <div class="flex items-start">
                            <i class="fas fa-phone w-6 text-violet-600 mt-1 text-xl"></i>
                            <div>
                                <h4 class="font-semibold">Call Us</h4>
                                <p>+49 30 12345678</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Map -->
                <div class="rounded-xl shadow-lg overflow-hidden h-full">
                    <iframe 
                        src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d2427.9079361223503!2d13.40230491566585!3d52.52000697981395!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x47a851e6b5d9b9b7%3A0x8f0f3ff2f2f7b8a!2sBerlin%2C%2GGermany!5e0!3m2!1sen!2sus!4v1658500000000!5m2!1sen!2sus" 
                        width="100%" 
                        height="100%" 
                        style="border:0; min-height: 300px;" 
                        allowfullscreen="" 
                        loading="lazy" 
                        referrerpolicy="no-referrer-when-downgrade">
                    </iframe>
                </div>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="bg-gray-800 text-white">
        <div class="container mx-auto px-6 py-10">
            <div class="grid md:grid-cols-3 gap-8">
                <div>
                    <h3 class="text-xl font-bold mb-2">Learn German</h3>
                    <p class="text-gray-400">Your partner in mastering the German language.</p>
                </div>
                <div>
                    <h3 class="font-bold mb-2">Quick Links</h3>
                    <ul>
                        <li class="mb-2"><a href="home.php" class="text-gray-400 hover:text-white">Home</a></li>
                        <li class="mb-2"><a href="home.php#courses" class="text-gray-400 hover:text-white">Courses</a></li>
                        <li class="mb-2"><a href="login.php" class="text-gray-400 hover:text-white">Login</a></li>
                        <li class="mb-2"><a href="register.php" class="text-gray-400 hover:text-white">Register</a></li>
                    </ul>
                </div>
                <div>
                    <h3 class="font-bold mb-2">Connect With Us</h3>
                    <div class="flex space-x-4">
                        <a href="#" class="text-gray-400 hover:text-white social-icon"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" class="text-gray-400 hover:text-white social-icon"><i class="fab fa-twitter"></i></a>
                        <a href="#" class="text-gray-400 hover:text-white social-icon"><i class="fab fa-instagram"></i></a>
                        <a href="#" class="text-gray-400 hover:text-white social-icon"><i class="fab fa-linkedin-in"></i></a>
                    </div>
                </div>
            </div>
            <div class="border-t border-gray-700 mt-8 pt-6 text-center text-gray-500">
                <p>&copy; 2024 Learn German. All Rights Reserved.</p>
            </div>
        </div>
    </footer>

</body>
</html>
