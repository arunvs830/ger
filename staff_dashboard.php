<?php
// FILE: staff_dashboard.php
// Main dashboard for staff to view and evaluate student assignment submissions.

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
// Get the staff_id and the course_id they are assigned to from tbl_staff.
$stmt_staff = $conn->prepare("SELECT staff_id, course_id, first_name FROM tbl_staff WHERE email = ?");
$stmt_staff->bind_param("s", $username);
$stmt_staff->execute();
$staff_info = $stmt_staff->get_result()->fetch_assoc();

// If for some reason staff info isn't found, handle it gracefully.
if (!$staff_info) {
    echo "Error: Could not find staff data. Please contact an administrator.";
    exit();
}

$staff_id = $staff_info['staff_id'];
$course_id = $staff_info['course_id'];
$staff_name = $staff_info['first_name'];
$stmt_staff->close();

// --- 2. Fetch Submissions for the Staff's Course ---
// This query gets all submissions from students in the staff's assigned course.
// It also checks if a submission has already been evaluated by joining the evaluation table.
$stmt_submissions = $conn->prepare("
    SELECT 
        sub.submission_id,
        sub.submission_text,
        stu.first_name,
        stu.last_name,
        a.title AS assignment_title,
        eval.evaluation_id
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
        .sidebar-link:hover, .sidebar-link.active { background-color: #4c1d95; color: white; }
        .btn-dark {
            background-color: #1f2937; /* Dark Gray */
            color: white;
            transition: background-color 0.3s ease;
        }
        .btn-dark:hover {
            background-color: #111827; /* Black */
        }
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
                <a href="#" class="sidebar-link active flex items-center px-4 py-2 rounded-lg"><i class="fas fa-tasks w-6 mr-2"></i> Submissions</a>
            </nav>
            <div class="px-4 py-4 border-t border-violet-700">
                 <a href="logout.php" class="sidebar-link flex items-center px-4 py-2 rounded-lg"><i class="fas fa-sign-out-alt w-6 mr-2"></i> Logout</a>
            </div>
        </div>

        <!-- Main Content -->
        <div class="flex-1 p-10 overflow-y-auto ml-64">
            <h1 class="text-3xl font-bold text-gray-800 mb-2">Assignment Submissions</h1>
            <p class="text-gray-600 mb-8">Welcome, <?php echo htmlspecialchars($staff_name); ?>! Here are the submissions for your course.</p>


            <!-- Success/Error Messages -->
            <?php if(isset($_SESSION['message'])): ?>
                <div class="mb-6 p-4 rounded-lg <?php echo $_SESSION['message_type'] === 'success' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>" role="alert">
                    <?php 
                        echo $_SESSION['message']; 
                        unset($_SESSION['message']);
                        unset($_SESSION['message_type']);
                    ?>
                </div>
            <?php endif; ?>

            <div class="bg-white p-6 rounded-lg shadow-md">
                <div class="overflow-x-auto">
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
                                        <a href="evaluate_submission.php?id=<?php echo $submission['submission_id']; ?>" class="btn-dark font-bold py-1 px-3 rounded-lg text-sm">
                                            View & Evaluate
                                        </a>
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
        </div>
    </div>
</body>
</html>
