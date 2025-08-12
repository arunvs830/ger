<?php
// FILE: staff_dashboard.php (with Help Section)
session_start();

// Security Check: Redirect if not logged in as staff
if (!isset($_SESSION['username']) || $_SESSION['user_type'] !== 'staff') {
    header("Location: login.php");
    exit();
}

require_once 'db_connect.php';

// Get the logged-in staff member's username (which is their email)
$username = $_SESSION['username'];

// --- 1. Fetch Staff Information ---
$stmt_staff = $conn->prepare("SELECT staff_id, course_id, first_name FROM tbl_staff WHERE email = ?");
$stmt_staff->bind_param("s", $username);
$stmt_staff->execute();
$staff_info = $stmt_staff->get_result()->fetch_assoc();

if (!$staff_info) {
    echo "Error: Could not find staff data. Please contact an administrator.";
    exit();
}

$staff_id = $staff_info['staff_id'];
$course_id = $staff_info['course_id'];
$staff_name = $staff_info['first_name'];
$stmt_staff->close();

// --- 2. Fetch Submissions for the Staff's Course ---
$stmt_submissions = $conn->prepare("
    SELECT 
        sub.submission_id, stu.first_name, stu.last_name, a.title AS assignment_title, eval.evaluation_id
    FROM tbl_assignment_submission AS sub
    JOIN tbl_student AS stu ON sub.student_id = stu.student_id
    JOIN tbl_assignment AS a ON sub.assignment_id = a.assignment_id
    JOIN tbl_study_material AS sm ON a.material_id = sm.material_id
    LEFT JOIN tbl_assignment_evaluation AS eval ON sub.submission_id = eval.submission_id
    WHERE sm.course_id = ?
    ORDER BY sub.submission_id DESC
");
$stmt_submissions->bind_param("i", $course_id);
$stmt_submissions->execute();
$submissions_result = $stmt_submissions->get_result();

// --- 3. Fetch data for Student Progress Reports (only for this staff's course) ---
$student_progress_query = "
    SELECT 
        s.student_id, s.first_name, s.last_name,
        (SELECT COUNT(a.assignment_id) FROM tbl_assignment a JOIN tbl_study_material sm ON a.material_id = sm.material_id WHERE sm.course_id = ? AND a.status = 'active') AS total_assignments,
        (SELECT COUNT(sub.submission_id) FROM tbl_assignment_submission sub JOIN tbl_assignment a_sub ON sub.assignment_id = a_sub.assignment_id JOIN tbl_study_material sm_sub ON a_sub.material_id = sm_sub.material_id WHERE sub.student_id = s.student_id AND sm_sub.course_id = ?) AS submitted_assignments
    FROM tbl_student s
    WHERE s.course_id = ?
    ORDER BY s.last_name
";
$stmt_progress = $conn->prepare($student_progress_query);
$stmt_progress->bind_param("iii", $course_id, $course_id, $course_id);
$stmt_progress->execute();
$student_progress_result = $stmt_progress->get_result();
$student_progress_data = $student_progress_result ? $student_progress_result->fetch_all(MYSQLI_ASSOC) : [];

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Dashboard - Learn German</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { font-family: 'Inter', sans-serif; }
        .sidebar-link { transition: background-color 0.2s, color 0.2s; }
        .sidebar-link:hover, .sidebar-link.active { background-color: #4c1d95; color: white; }
        .tab-content { display: none; }
        .tab-content.active { display: block; }
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
                <span class="text-sm block text-violet-300">Staff Panel</span>
            </div>
            <nav class="flex-1 px-4 py-4 space-y-2">
                <a href="#" class="sidebar-link active flex items-center px-4 py-2 rounded-lg" onclick="showTab('submissions', this)"><i class="fas fa-tasks w-6 mr-2"></i> Submissions</a>
                <a href="#" class="sidebar-link flex items-center px-4 py-2 rounded-lg" onclick="showTab('reports', this)"><i class="fas fa-chart-bar w-6 mr-2"></i> Student Reports</a>
                <a href="#" class="sidebar-link flex items-center px-4 py-2 rounded-lg" onclick="showTab('help', this)"><i class="fas fa-question-circle w-6 mr-2"></i> Help</a>
            </nav>
            <div class="px-4 py-4 border-t border-violet-700">
                 <a href="logout.php" class="sidebar-link flex items-center px-4 py-2 rounded-lg"><i class="fas fa-sign-out-alt w-6 mr-2"></i> Logout</a>
            </div>
        </div>

        <!-- Main Content -->
        <div class="flex-1 p-10 overflow-y-auto ml-64">
            <!-- Success/Error Messages -->
            <?php if(isset($_SESSION['message'])): ?>
                <div class="mb-6 p-4 rounded-lg <?php echo $_SESSION['message_type'] === 'success' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>" role="alert">
                    <?php echo $_SESSION['message']; unset($_SESSION['message']); unset($_SESSION['message_type']); ?>
                </div>
            <?php endif; ?>

            <!-- Submissions Tab -->
            <div id="submissions" class="tab-content active">
                <h1 class="text-3xl font-bold text-gray-800 mb-2">Assignment Submissions</h1>
                <p class="text-gray-600 mb-8">Welcome, <?php echo htmlspecialchars($staff_name); ?>! Here are the submissions for your course.</p>
                <div class="bg-white p-6 rounded-lg shadow-md">
                    <table class="min-w-full bg-white">
                         <thead class="bg-gray-200">
                            <tr>
                                <th class="py-2 px-4 text-left">Student Name</th>
                                <th class="py-2 px-4 text-left">Assignment</th>
                                <th class="py-2 px-4 text-left">Status</th>
                                <th class="py-2 px-4 text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if($submissions_result && $submissions_result->num_rows > 0): ?>
                                <?php while($submission = $submissions_result->fetch_assoc()): ?>
                                <tr class="border-b hover:bg-gray-50">
                                    <td class="py-2 px-4"><?php echo htmlspecialchars($submission['first_name'] . ' ' . $submission['last_name']); ?></td>
                                    <td class="py-2 px-4"><?php echo htmlspecialchars($submission['assignment_title']); ?></td>
                                    <td class="py-2 px-4">
                                        <?php if (is_null($submission['evaluation_id'])): ?>
                                            <span class="bg-orange-100 text-orange-800 text-xs font-semibold px-2.5 py-0.5 rounded-full">Pending</span>
                                        <?php else: ?>
                                            <span class="bg-green-100 text-green-800 text-xs font-semibold px-2.5 py-0.5 rounded-full">Evaluated</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="py-2 px-4 text-center">
                                        <a href="evaluate_submission.php?id=<?php echo $submission['submission_id']; ?>" class="btn-dark font-bold py-1 px-3 rounded-lg text-sm">View & Evaluate</a>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="4" class="text-center py-4 text-gray-500">No submissions found for your course.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Reports Tab -->
            <div id="reports" class="tab-content">
                <h1 class="text-3xl font-bold text-gray-800 mb-8">Student Progress Report</h1>
                <div class="bg-white p-6 rounded-lg shadow-md">
                    <h2 class="text-xl font-semibold mb-4">Progress for Your Course</h2>
                    <table class="min-w-full bg-white">
                        <thead class="bg-gray-200">
                            <tr>
                                <th class="py-2 px-4 text-left">Student Name</th>
                                <th class="py-2 px-4 text-center">Progress</th>
                                <th class="py-2 px-4 text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(!empty($student_progress_data)): ?>
                                <?php foreach($student_progress_data as $student): ?>
                                <tr class="border-b hover:bg-gray-50">
                                    <td class="py-2 px-4"><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></td>
                                    <td class="py-2 px-4 text-center">
                                        <?php echo $student['submitted_assignments']; ?> / <?php echo $student['total_assignments']; ?> assignments
                                    </td>
                                    <td class="py-2 px-4 text-center">
                                        <?php if($student['total_assignments'] > 0 && $student['submitted_assignments'] >= $student['total_assignments']): ?>
                                            <span class="bg-green-100 text-green-800 text-xs font-semibold px-2.5 py-0.5 rounded-full">Completed</span>
                                        <?php else: ?>
                                            <span class="bg-yellow-100 text-yellow-800 text-xs font-semibold px-2.5 py-0.5 rounded-full">In Progress</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="3" class="text-center py-4 text-gray-500">No students are enrolled in your course yet.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- Help Tab -->
            <div id="help" class="tab-content">
                <h1 class="text-3xl font-bold text-gray-800 mb-8">Staff Help Guide</h1>
                <div class="space-y-6 bg-white p-8 rounded-lg shadow-md">
                    <div>
                        <h2 class="text-xl font-semibold text-violet-700 mb-2">
                            <i class="fas fa-tasks w-6 mr-2"></i>Submissions
                        </h2>
                        <p>This is your main work area. It shows a list of all assignment submissions from students in your assigned course. You can see the student's name, the assignment title, and the status:</p>
                        <ul class="list-disc list-inside mt-2 ml-4 text-gray-700">
                            <li><span class="font-semibold text-orange-600">Pending:</span> The student has submitted their work, and it is ready for you to review.</li>
                            <li><span class="font-semibold text-green-600">Evaluated:</span> You have already provided a score and feedback for this submission.</li>
                        </ul>
                        <p class="mt-2">Click the <span class="font-bold">"View & Evaluate"</span> button to see the student's full submission, provide a score, and write feedback.</p>
                    </div>
                    <div class="border-t pt-6">
                        <h2 class="text-xl font-semibold text-violet-700 mb-2">
                            <i class="fas fa-chart-bar w-6 mr-2"></i>Student Reports
                        </h2>
                        <p>This tab gives you a high-level overview of the progress of all students in your course. You can quickly see who is on track and who may need a reminder.</p>
                        <ul class="list-disc list-inside mt-2 ml-4 text-gray-700">
                            <li><span class="font-semibold">Progress:</span> Shows how many of the total active assignments a student has submitted (e.g., "3 / 5 assignments").</li>
                            <li><span class="font-semibold">Status:</span> Indicates if a student is "In Progress" or has "Completed" all the required assignments for the course.</li>
                        </ul>
                    </div>
                    <div class="border-t pt-6">
                        <h2 class="text-xl font-semibold text-violet-700 mb-2">
                            <i class="fas fa-sign-out-alt w-6 mr-2"></i>Logout
                        </h2>
                        <p>Click the "Logout" button at the bottom of the sidebar to securely end your session.</p>
                    </div>
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
    </script>
</body>
</html>
