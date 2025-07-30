<?php
// FILE: student_dashboard.php (Final Version with Score Display)
session_start();

// Security Check: Redirect to login if not a student or not logged in.
if (!isset($_SESSION['username']) || $_SESSION['user_type'] !== 'student') {
    header("Location: login.php");
    exit();
}

require_once 'db_connect.php';

// Get the logged-in student's username from the session.
$username = $_SESSION['username'];

// --- 1. Fetch Student and Course Information ---
$stmt = $conn->prepare("
    SELECT s.student_id, s.first_name, s.last_name, s.course_id, c.name AS course_name, c.level AS course_level
    FROM tbl_student s
    JOIN tbl_course c ON s.course_id = c.course_id
    WHERE s.username = ?
");
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();
$student_info = $result->fetch_assoc();

if (!$student_info) {
    // This is a safeguard. If a logged-in user has no student record, something is wrong.
    echo "Error: Could not find your student data. Please contact support.";
    exit();
}

$student_id = $student_info['student_id'];
$course_id = $student_info['course_id'];
$course_name = $student_info['course_name'] . " (" . $student_info['course_level'] . ")";

// --- 2. Fetch Course Materials, Assignments, Submissions, and Evaluations ---
// This query now also fetches the 'score' from the evaluation table.
$materials_stmt = $conn->prepare("
    SELECT 
        sm.material_id, 
        sm.title AS video_title, 
        sm.video_url, 
        sm.description,
        a.assignment_id, 
        a.title AS assignment_title, 
        a.instructions,
        sub.submission_id,
        sub.submission_text,
        eval.feedback_text,
        eval.score
    FROM tbl_study_material sm
    LEFT JOIN tbl_assignment a ON sm.material_id = a.material_id AND a.status = 'active'
    LEFT JOIN tbl_assignment_submission sub ON a.assignment_id = sub.assignment_id AND sub.student_id = ?
    LEFT JOIN tbl_assignment_evaluation eval ON sub.submission_id = eval.submission_id
    WHERE sm.course_id = ? AND sm.status = 'active'
    ORDER BY sm.material_id
");
$materials_stmt->bind_param("ii", $student_id, $course_id);
$materials_stmt->execute();
$materials_result = $materials_stmt->get_result();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard - Learn German</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { font-family: 'Inter', sans-serif; }
        .sidebar-link { transition: background-color 0.2s, color 0.2s; }
        .sidebar-link:hover, .sidebar-link.active { background-color: #4c1d95; color: white; }
        .tab-content { display: none; }
        .tab-content.active { display: block; }
        .video-container { position: relative; padding-bottom: 56.25%; height: 0; overflow: hidden; }
        .video-container iframe { position: absolute; top: 0; left: 0; width: 100%; height: 100%; }
        .assignment-box { background: linear-gradient(135deg, #f5f3ff, #ede9fe); border-left: 4px solid #7c3aed; }
        textarea:focus { box-shadow: 0 0 0 2px #c4b5fd; }
        .btn-dark { background-color: #1f2937; color: white; transition: background-color 0.3s ease; }
        .btn-dark:hover { background-color: #111827; }
    </style>
</head>
<body class="bg-gray-100">

    <div class="flex h-screen bg-gray-100">
        <!-- Sidebar -->
        <div class="w-64 bg-violet-800 text-white flex flex-col fixed h-full">
            <div class="px-6 py-4 border-b border-violet-700">
                <a href="home.php" class="text-2xl font-bold">Learn German</a>
                <span class="text-sm block text-violet-300">Student Panel</span>
            </div>
            <nav class="flex-1 px-4 py-4 space-y-2">
                <a href="#" class="sidebar-link active flex items-center px-4 py-2 rounded-lg" onclick="showTab('my-course', this)"><i class="fas fa-book-open w-6 mr-2"></i> My Course</a>
                <a href="#" class="sidebar-link flex items-center px-4 py-2 rounded-lg" onclick="showTab('feedback', this)"><i class="fas fa-comment-dots w-6 mr-2"></i> Give Feedback</a>
            </nav>
             <div class="px-4 py-4 border-t border-violet-700">
                 <a href="logout.php" class="sidebar-link flex items-center px-4 py-2 rounded-lg"><i class="fas fa-sign-out-alt w-6 mr-2"></i> Logout</a>
            </div>
        </div>

        <!-- Main Content -->
        <div class="flex-1 p-10 overflow-y-auto ml-64">
            <?php if(isset($_SESSION['message'])): ?>
                <div class="mb-6 p-4 rounded-lg <?php echo $_SESSION['message_type'] === 'success' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>" role="alert">
                    <?php echo htmlspecialchars($_SESSION['message']); unset($_SESSION['message']); unset($_SESSION['message_type']); ?>
                </div>
            <?php endif; ?>

            <!-- My Course Tab -->
            <div id="my-course" class="tab-content active">
                <h1 class="text-3xl font-bold text-gray-800 mb-2">My Course: <?php echo htmlspecialchars($course_name); ?></h1>
                <p class="text-gray-600 mb-8">Welcome back, <?php echo htmlspecialchars($student_info['first_name']); ?>! Here are your lessons.</p>
                <div class="space-y-12">
                    <?php if ($materials_result->num_rows > 0): ?>
                        <?php while($item = $materials_result->fetch_assoc()): ?>
                            <div class="bg-white p-6 rounded-lg shadow-lg">
                                <h2 class="text-2xl font-bold text-gray-800 mb-4"><?php echo htmlspecialchars($item['video_title']); ?></h2>
                                <?php
                                    $embed_url = '';
                                    if (!empty($item['video_url'])) {
                                        parse_str(parse_url($item['video_url'], PHP_URL_QUERY), $query_params);
                                        if (isset($query_params['v'])) {
                                            $embed_url = 'https://www.youtube.com/embed/' . $query_params['v'];
                                        }
                                    }
                                ?>
                                <?php if ($embed_url): ?>
                                    <div class="video-container rounded-lg overflow-hidden mb-4">
                                        <iframe src="<?php echo htmlspecialchars($embed_url); ?>" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
                                    </div>
                                <?php endif; ?>
                                <p class="text-gray-600 mb-6"><?php echo htmlspecialchars($item['description']); ?></p>
                                
                                <?php if (!empty($item['assignment_id'])): ?>
                                    <div class="assignment-box p-6 rounded-lg mt-6">
                                        <h3 class="text-xl font-bold text-violet-800 mb-3"><i class="fas fa-pencil-alt mr-3"></i>Assignment: <?php echo htmlspecialchars($item['assignment_title']); ?></h3>
                                        <p class="text-gray-700 italic mb-4">"<?php echo nl2br(htmlspecialchars($item['instructions'])); ?>"</p>
                                        
                                        <?php if (!is_null($item['submission_id'])): // Student has already submitted ?>
                                            <div class="mt-4">
                                                <h4 class="font-bold text-gray-700 mb-2 flex items-center"><i class="fas fa-lock text-gray-500 mr-2"></i>Your Submission (Locked)</h4>
                                                <div class="bg-gray-100 border border-gray-200 p-4 rounded-lg min-h-[100px] text-gray-600 cursor-not-allowed">
                                                    <p><?php echo nl2br(htmlspecialchars($item['submission_text'])); ?></p>
                                                </div>
                                            </div>
                                            <?php if (!is_null($item['feedback_text'])): // Staff has evaluated ?>
                                                <div class="mt-6 bg-green-50 border-l-4 border-green-500 p-4 rounded-r-lg">
                                                    <h4 class="font-bold text-green-800 mb-2">Staff Evaluation:</h4>
                                                    <p class="text-lg text-green-900 mb-2"><strong>Score:</strong> <?php echo htmlspecialchars(rtrim(rtrim($item['score'], '0'), '.')); ?> / 100</p>
                                                    <p class="text-green-900"><strong>Feedback:</strong> <?php echo nl2br(htmlspecialchars($item['feedback_text'])); ?></p>
                                                </div>
                                            <?php else: // Submitted but not yet evaluated ?>
                                                <div class="mt-6 bg-blue-50 border-l-4 border-blue-500 p-4 rounded-r-lg">
                                                    <p class="font-semibold text-blue-800">Your assignment has been submitted and is awaiting evaluation.</p>
                                                </div>
                                            <?php endif; ?>
                                        <?php else: // Student has NOT submitted yet ?>
                                            <form action="student_actions.php" method="POST">
                                                <input type="hidden" name="action" value="submit_assignment">
                                                <input type="hidden" name="assignment_id" value="<?php echo $item['assignment_id']; ?>">
                                                <input type="hidden" name="student_id" value="<?php echo $student_id; ?>">
                                                <div>
                                                    <label for="submission_text_<?php echo $item['assignment_id']; ?>" class="block text-gray-700 font-semibold mb-2">Your Answer:</label>
                                                    <textarea name="submission_text" id="submission_text_<?php echo $item['assignment_id']; ?>" rows="5" class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-violet-500 transition" placeholder="Type your answer here..." required></textarea>
                                                </div>
                                                <div class="text-right mt-4">
                                                    <button type="submit" class="btn-dark font-bold py-2 px-5 rounded-lg">Submit Assignment</button>
                                                </div>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="bg-white p-8 rounded-lg shadow-md text-center"><h3 class="text-xl font-semibold text-gray-700">No course materials have been added yet.</h3><p class="text-gray-500 mt-2">Please check back later!</p></div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Give Feedback Tab -->
            <div id="feedback" class="tab-content">
                <h1 class="text-3xl font-bold text-gray-800 mb-8">Give Feedback on Your Course</h1>
                <div class="bg-white p-8 rounded-lg shadow-lg max-w-2xl mx-auto">
                    <p class="text-gray-600 mb-6">We value your opinion! Please let us know how we can improve your learning experience.</p>
                    <form action="student_actions.php" method="POST">
                        <input type="hidden" name="action" value="submit_feedback">
                        <input type="hidden" name="student_id" value="<?php echo $student_id; ?>">
                        <div>
                            <label for="feedback_comment" class="block text-gray-700 font-semibold mb-2">Your Comment:</label>
                            <textarea name="comment" id="feedback_comment" rows="6" class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-violet-500 transition" placeholder="Tell us about your experience..." required></textarea>
                        </div>
                        <div class="text-right mt-4">
                            <button type="submit" class="btn-dark font-bold py-2 px-6 rounded-lg">Submit Feedback</button>
                        </div>
                    </form>
                </div>
            </div>

        </div>
    </div>
    <script>
        function showTab(tabName, element) {
            document.querySelectorAll('.tab-content').forEach(tab => tab.classList.remove('active'));
            document.querySelectorAll('.sidebar-link').forEach(link => link.classList.remove('active'));
            document.getElementById(tabName).classList.add('active');
            element.classList.add('active');
        }
        
        const text = "Welcome, <?php echo addslashes(htmlspecialchars($student_info['first_name'])); ?>!";
        let i = 0;
        const headline = document.getElementById('typing-headline');
        function typeWriter() {
            if (headline && i < text.length) {
                headline.innerHTML = text.substring(0, i + 1) + '<span class="typing-cursor"></span>';
                i++;
                setTimeout(typeWriter, 100);
            } else if (headline) {
                 headline.innerHTML = text; 
            }
        }
        window.addEventListener('load', typeWriter);
    </script>
</body>
</html>
