<?php
// FILE: admin_dashboard.php (Complete with Help Section)
session_start();

if (!isset($_SESSION['username']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: login.php");
    exit();
}

require_once 'db_connect.php';

// --- Handle Active Tab State ---
$active_tab = $_POST['tab'] ?? $_GET['tab'] ?? 'dashboard';

// --- Handle Filters ---
$start_date = $_POST['start_date'] ?? '';
$end_date = $_POST['end_date'] ?? '';
$student_course_filter = $_POST['student_course_filter'] ?? '';

// --- Build WHERE clauses for queries ---
$payment_where_clause = '';
$date_query_string = '';
if (!empty($start_date) && !empty($end_date)) {
    $start_date_safe = $conn->real_escape_string($start_date);
    $end_date_safe = $conn->real_escape_string($end_date);
    $payment_where_clause = " WHERE p.payment_date BETWEEN '{$start_date_safe} 00:00:00' AND '{$end_date_safe} 23:59:59'";
    $date_query_string = "&start_date=$start_date&end_date=$end_date";
}

$student_where_clause = '';
$student_course_query_string = '';
if (!empty($student_course_filter)) {
    $student_where_clause = " WHERE s.course_id = " . intval($student_course_filter);
    $student_course_query_string = "&course_filter=" . intval($student_course_filter);
}

// --- Fetch All Data Needed for the Dashboard ---
$courses_result = $conn->query("SELECT * FROM tbl_course ORDER BY name");
$all_courses = $courses_result ? $courses_result->fetch_all(MYSQLI_ASSOC) : [];
$active_courses = array_filter($all_courses, function($c) { return $c['status'] === 'active'; });
$inactive_courses = array_filter($all_courses, function($c) { return $c['status'] === 'inactive'; });

$materials_result = $conn->query("SELECT material_id, title FROM tbl_study_material WHERE status = 'active' ORDER BY title");
$materials = $materials_result ? $materials_result->fetch_all(MYSQLI_ASSOC) : [];

$videos_list_result = $conn->query("SELECT m.*, c.name as course_name, c.level FROM tbl_study_material m JOIN tbl_course c ON m.course_id = c.course_id ORDER BY c.name, m.title");
$all_videos = $videos_list_result ? $videos_list_result->fetch_all(MYSQLI_ASSOC) : [];
$active_videos = array_filter($all_videos, function($v) { return $v['status'] === 'active'; });
$inactive_videos = array_filter($all_videos, function($v) { return $v['status'] === 'inactive'; });

$assignments_list_result = $conn->query("SELECT a.*, sm.title as material_title FROM tbl_assignment a JOIN tbl_study_material sm ON a.material_id = sm.material_id ORDER BY sm.title, a.title");
$all_assignments = $assignments_list_result ? $assignments_list_result->fetch_all(MYSQLI_ASSOC) : [];
$active_assignments = array_filter($all_assignments, function($a) { return $a['status'] === 'active'; });
$inactive_assignments = array_filter($all_assignments, function($a) { return $a['status'] === 'inactive'; });

$staff_result = $conn->query("SELECT s.*, c.name as course_name, c.level FROM tbl_staff s JOIN tbl_course c ON s.course_id = c.course_id ORDER BY s.last_name");
$all_staff = $staff_result ? $staff_result->fetch_all(MYSQLI_ASSOC) : [];
$active_staff = array_filter($all_staff, function($s) { return $s['status'] === 'active'; });
$inactive_staff = array_filter($all_staff, function($s) { return $s['status'] === 'inactive'; });

$students_query = "SELECT s.*, c.name AS course_name, c.level FROM tbl_student s JOIN tbl_course c ON s.course_id = c.course_id" . $student_where_clause . " ORDER BY s.student_id DESC";
$students_result = $conn->query($students_query);

$payments_query = "SELECT p.payment_id, p.amount, p.payment_date, p.txn_reference, s.first_name, s.last_name, c.name AS course_name, c.level FROM tbl_payment p JOIN tbl_student s ON p.student_id = s.student_id JOIN tbl_course c ON s.course_id = c.course_id" . $payment_where_clause . " ORDER BY p.payment_id DESC";
$payments_result = $conn->query($payments_query);

$total_revenue_query = "SELECT SUM(amount) AS total_revenue FROM tbl_payment p" . str_replace('p.', '', $payment_where_clause);
$total_revenue_result = $conn->query($total_revenue_query);
$total_revenue = $total_revenue_result ? $total_revenue_result->fetch_assoc()['total_revenue'] : 0;

$course_revenue_query = "SELECT c.name, c.level, SUM(p.amount) AS course_total FROM tbl_payment p JOIN tbl_student s ON p.student_id = s.student_id JOIN tbl_course c ON s.course_id = c.course_id" . $payment_where_clause . " GROUP BY c.course_id ORDER BY course_total DESC";
$course_revenue_result = $conn->query($course_revenue_query);
$course_revenue_data = $course_revenue_result ? $course_revenue_result->fetch_all(MYSQLI_ASSOC) : [];

$feedback_result = $conn->query("SELECT f.comment, s.first_name, s.last_name, c.name AS course_name, c.level FROM tbl_feedback f JOIN tbl_student s ON f.student_id = s.student_id JOIN tbl_course c ON s.course_id = c.course_id ORDER BY f.feedback_id DESC");

$student_progress_query = "SELECT s.student_id, s.first_name, s.last_name, c.course_id, c.name AS course_name, c.level, (SELECT COUNT(a.assignment_id) FROM tbl_assignment a JOIN tbl_study_material sm ON a.material_id = sm.material_id WHERE sm.course_id = c.course_id AND a.status = 'active') AS total_assignments, (SELECT COUNT(sub.submission_id) FROM tbl_assignment_submission sub JOIN tbl_assignment a_sub ON sub.assignment_id = a_sub.assignment_id JOIN tbl_study_material sm_sub ON a_sub.material_id = sm_sub.material_id WHERE sub.student_id = s.student_id AND sm_sub.course_id = c.course_id) AS submitted_assignments FROM tbl_student s JOIN tbl_course c ON s.course_id = c.course_id WHERE c.status = 'active' ORDER BY c.name, s.last_name";
$student_progress_result = $conn->query($student_progress_query);
$student_progress_data = $student_progress_result ? $student_progress_result->fetch_all(MYSQLI_ASSOC) : [];

$course_staff_query = "SELECT c.course_id, GROUP_CONCAT(CONCAT(s.first_name, ' ', s.last_name) SEPARATOR ', ') AS staff_names FROM tbl_course c LEFT JOIN tbl_staff s ON c.course_id = s.course_id AND s.status = 'active' WHERE c.status = 'active' GROUP BY c.course_id";
$course_staff_result = $conn->query($course_staff_query);
$course_staff_map = [];
if($course_staff_result) { while($row = $course_staff_result->fetch_assoc()) { $course_staff_map[$row['course_id']] = $row['staff_names']; } }

$course_reports = [];
foreach ($active_courses as $course) {
    $cid = $course['course_id'];
    $course_reports[$cid] = ['name' => $course['name'] . ' (' . $course['level'] . ')', 'staff' => $course_staff_map[$cid] ?? 'Not Assigned', 'total_students' => 0, 'completed_students' => 0];
}
foreach ($student_progress_data as $student) {
    $cid = $student['course_id'];
    if (isset($course_reports[$cid])) {
        $course_reports[$cid]['total_students']++;
        if ($student['total_assignments'] > 0 && $student['submitted_assignments'] >= $student['total_assignments']) {
            $course_reports[$cid]['completed_students']++;
        }
    }
}

$staff_performance_query = "SELECT s.staff_id, s.first_name, s.last_name, c.name AS course_name, c.level, (SELECT COUNT(sub.submission_id) FROM tbl_assignment_submission sub JOIN tbl_assignment a ON sub.assignment_id = a.assignment_id JOIN tbl_study_material sm ON a.material_id = sm.material_id WHERE sm.course_id = s.course_id) AS total_submissions_for_course, (SELECT COUNT(eval.evaluation_id) FROM tbl_assignment_evaluation eval WHERE eval.staff_id = s.staff_id) AS evaluated_by_staff FROM tbl_staff s JOIN tbl_course c ON s.course_id = c.course_id WHERE s.status = 'active' ORDER BY s.last_name";
$staff_performance_result = $conn->query($staff_performance_query);
$staff_performance_data = $staff_performance_result ? $staff_performance_result->fetch_all(MYSQLI_ASSOC) : [];

$logs_result = $conn->query("SELECT * FROM tbl_log ORDER BY login_time DESC");

$total_students_count = $conn->query("SELECT COUNT(*) as count FROM tbl_student")->fetch_assoc()['count'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Learn German</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { font-family: 'Inter', sans-serif; }
        .sidebar-link { transition: background-color 0.2s, color 0.2s; }
        .sidebar-link:hover, .sidebar-link.active { background-color: #4c1d95; color: white; }
        .tab-content { display: none; }
        .tab-content.active { display: block; }
        .modal-overlay { transition: opacity 0.3s ease; }
        
        @media print {
            body * { visibility: hidden; }
            .printable-area, .printable-area * { visibility: visible; }
            .printable-area { position: absolute; left: 0; top: 0; width: 100%; padding: 20px; }
            .no-print { display: none !important; }
            .print-header { display: block !important; }
            .bg-white { box-shadow: none !important; border: 1px solid #ddd; }
            table { border-collapse: collapse !important; width: 100% !important; font-size: 12px; }
            th, td { border: 1px solid #ddd !important; padding: 8px !important; }
            .bg-gray-200 { background-color: #f3f4f6 !important; -webkit-print-color-adjust: exact; }
            .bg-green-100 { background-color: #d1fae5 !important; -webkit-print-color-adjust: exact; }
            .bg-yellow-100 { background-color: #fef9c3 !important; -webkit-print-color-adjust: exact; }
            .text-green-800 { color: #065f46 !important; }
            .text-yellow-800 { color: #854d0e !important; }
            span { display: inline-block; }
        }
    </style>
</head>
<body class="bg-gray-100">

    <div class="flex h-screen bg-gray-100">
        <!-- Sidebar -->
        <div class="w-64 bg-violet-800 text-white flex flex-col fixed h-full no-print">
            <div class="px-6 py-4 border-b border-violet-700">
                <a href="home.php" class="text-2xl font-bold">Learn German</a>
                <span class="text-sm block text-violet-300">Admin Panel</span>
            </div>
            <nav class="flex-1 px-4 py-4 space-y-2 overflow-y-auto">
                <a href="#dashboard" class="sidebar-link flex items-center px-4 py-2 rounded-lg" onclick="showTab('dashboard', this)"><i class="fas fa-tachometer-alt w-6 mr-2"></i> Dashboard</a>
                <a href="#courses" class="sidebar-link flex items-center px-4 py-2 rounded-lg" onclick="showTab('courses', this)"><i class="fas fa-book w-6 mr-2"></i> Manage Courses</a>
                <a href="#videos" class="sidebar-link flex items-center px-4 py-2 rounded-lg" onclick="showTab('videos', this)"><i class="fas fa-video w-6 mr-2"></i> Manage Videos</a>
                <a href="#assignments" class="sidebar-link flex items-center px-4 py-2 rounded-lg" onclick="showTab('assignments', this)"><i class="fas fa-file-alt w-6 mr-2"></i> Manage Assignments</a>
                <a href="#staff" class="sidebar-link flex items-center px-4 py-2 rounded-lg" onclick="showTab('staff', this)"><i class="fas fa-users-cog w-6 mr-2"></i> Manage Staff</a>
                <a href="#students" class="sidebar-link flex items-center px-4 py-2 rounded-lg" onclick="showTab('students', this)"><i class="fas fa-user-graduate w-6 mr-2"></i> View Students</a>
                <a href="#payments" class="sidebar-link flex items-center px-4 py-2 rounded-lg" onclick="showTab('payments', this)"><i class="fas fa-dollar-sign w-6 mr-2"></i> View Payments</a>
                <a href="#feedback" class="sidebar-link flex items-center px-4 py-2 rounded-lg" onclick="showTab('feedback', this)"><i class="fas fa-comment-dots w-6 mr-2"></i> View Feedback</a>
                <a href="#reports" class="sidebar-link flex items-center px-4 py-2 rounded-lg" onclick="showTab('reports', this)"><i class="fas fa-chart-pie w-6 mr-2"></i> Reports</a>
                <a href="#logs" class="sidebar-link flex items-center px-4 py-2 rounded-lg" onclick="showTab('logs', this)"><i class="fas fa-history w-6 mr-2"></i> View Logs</a>
                <a href="#help" class="sidebar-link flex items-center px-4 py-2 rounded-lg" onclick="showTab('help', this)"><i class="fas fa-question-circle w-6 mr-2"></i> Help</a>
            </nav>
            <div class="px-4 py-4 border-t border-violet-700">
                 <a href="logout.php" class="sidebar-link flex items-center px-4 py-2 rounded-lg"><i class="fas fa-sign-out-alt w-6 mr-2"></i> Logout</a>
            </div>
        </div>

        <!-- Main Content -->
        <div class="flex-1 p-10 overflow-y-auto ml-64">
            
            <?php if(isset($_SESSION['message'])): ?>
                <div class="mb-6 p-4 rounded-lg <?php echo $_SESSION['message_type'] === 'success' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?> no-print" role="alert">
                    <?php echo htmlspecialchars($_SESSION['message']); unset($_SESSION['message']); unset($_SESSION['message_type']); ?>
                </div>
            <?php endif; ?>

            <!-- Dashboard Tab -->
            <div id="dashboard" class="tab-content">
                <h1 class="text-3xl font-bold text-gray-800 mb-8">Dashboard Overview</h1>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <div class="bg-white p-6 rounded-lg shadow-md flex items-center"><i class="fas fa-graduation-cap text-4xl text-violet-500 mr-4"></i><div><p class="text-gray-500 text-sm">Total Students</p><p class="text-3xl font-bold text-gray-800"><?php echo $total_students_count; ?></p></div></div>
                    <div class="bg-white p-6 rounded-lg shadow-md flex items-center"><i class="fas fa-chalkboard-teacher text-4xl text-green-500 mr-4"></i><div><p class="text-gray-500 text-sm">Active Staff</p><p class="text-3xl font-bold text-gray-800"><?php echo count($active_staff); ?></p></div></div>
                    <div class="bg-white p-6 rounded-lg shadow-md flex items-center"><i class="fas fa-book text-4xl text-blue-500 mr-4"></i><div><p class="text-gray-500 text-sm">Active Courses</p><p class="text-3xl font-bold text-gray-800"><?php echo count($active_courses); ?></p></div></div>
                </div>
            </div>

            <!-- Manage Courses Tab -->
            <div id="courses" class="tab-content">
                <h1 class="text-3xl font-bold text-gray-800 mb-8">Manage Courses</h1>
                <div class="bg-white p-6 rounded-lg shadow-md mb-8">
                    <h2 class="text-xl font-semibold mb-4">Add New Course</h2>
                    <form action="admin_actions.php" method="POST">
                        <input type="hidden" name="action" value="add_course">
                        <div class="grid md:grid-cols-2 gap-4 mb-4">
                            <div><label for="course_name" class="block text-gray-700">Course Name</label><input type="text" name="name" id="course_name" class="w-full mt-1 p-2 border rounded-md" required></div>
                            <div><label for="course_level" class="block text-gray-700">Level</label><select name="level" id="course_level" class="w-full mt-1 p-2 border rounded-md" required><option value="A1">A1</option><option value="A2">A2</option><option value="B1">B1</option><option value="B2">B2</option></select></div>
                        </div>
                        <div class="mb-4"><label for="course_fee" class="block text-gray-700">Fee (Rs)</label><input type="number" step="0.01" name="fee" id="course_fee" class="w-full mt-1 p-2 border rounded-md" required></div>
                        <div class="mb-4"><label for="course_description" class="block text-gray-700">Description</label><textarea name="description" id="course_description" rows="3" class="w-full mt-1 p-2 border rounded-md"></textarea></div>
                        <button type="submit" class="bg-violet-600 hover:bg-violet-700 text-white font-bold py-2 px-4 rounded-lg">Add Course</button>
                    </form>
                </div>
                <div class="bg-white p-6 rounded-lg shadow-md mb-8">
                    <h2 class="text-xl font-semibold mb-4">Active Courses</h2>
                    <table class="min-w-full bg-white"><thead class="bg-gray-200"><tr><th class="py-2 px-4 text-left">Sl. No.</th><th class="py-2 px-4 text-left">Name</th><th class="py-2 px-4 text-left">Level</th><th class="py-2 px-4 text-left">Fee</th><th class="py-2 px-4 text-center">Actions</th></tr></thead>
                        <tbody>
                            <?php if(!empty($active_courses)): $i = 1; ?>
                                <?php foreach($active_courses as $course): ?>
                                <tr class="border-b hover:bg-gray-50">
                                    <td class="py-2 px-4"><?php echo $i++; ?></td>
                                    <td class="py-2 px-4"><?php echo htmlspecialchars($course['name']); ?></td>
                                    <td class="py-2 px-4"><?php echo htmlspecialchars($course['level']); ?></td>
                                    <td class="py-2 px-4">Rs <?php echo htmlspecialchars(number_format($course['fee'], 2)); ?></td>
                                    <td class="py-2 px-4 text-center space-x-4">
                                        <button onclick='openEditCourseModal(<?php echo json_encode($course); ?>)' class="text-blue-500 hover:text-blue-700 font-semibold">Edit</button>
                                        <a href="admin_actions.php?action=set_course_status&id=<?php echo $course['course_id']; ?>&status=inactive" onclick="return confirm('Deactivate this course?')" class="text-red-500 hover:text-red-700 font-semibold">Deactivate</a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="5" class="text-center py-4 text-gray-500">No active courses found.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <div class="bg-white p-6 rounded-lg shadow-md">
                    <h2 class="text-xl font-semibold mb-4 text-gray-500">Inactive Courses</h2>
                    <table class="min-w-full bg-white"><thead class="bg-gray-200"><tr><th class="py-2 px-4 text-left">Sl. No.</th><th class="py-2 px-4 text-left">Name</th><th class="py-2 px-4 text-left">Level</th><th class="py-2 px-4 text-left">Fee</th><th class="py-2 px-4 text-center">Action</th></tr></thead>
                        <tbody>
                             <?php if(!empty($inactive_courses)): $i = 1; ?>
                                <?php foreach($inactive_courses as $course): ?>
                                <tr class="border-b bg-gray-50 text-gray-500">
                                    <td class="py-2 px-4"><?php echo $i++; ?></td>
                                    <td class="py-2 px-4"><?php echo htmlspecialchars($course['name']); ?></td>
                                    <td class="py-2 px-4"><?php echo htmlspecialchars($course['level']); ?></td>
                                    <td class="py-2 px-4">Rs <?php echo htmlspecialchars(number_format($course['fee'], 2)); ?></td>
                                    <td class="py-2 px-4 text-center">
                                        <a href="admin_actions.php?action=set_course_status&id=<?php echo $course['course_id']; ?>&status=active" class="text-green-500 hover:text-green-700 font-semibold">Reactivate</a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="5" class="text-center py-4 text-gray-500">No inactive courses found.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Manage Videos Tab -->
            <div id="videos" class="tab-content">
                <h1 class="text-3xl font-bold text-gray-800 mb-8">Manage Videos</h1>
                <div class="bg-white p-6 rounded-lg shadow-md mb-8">
                    <h2 class="text-xl font-semibold mb-4">Add New Video</h2>
                    <form action="admin_actions.php" method="POST">
                        <input type="hidden" name="action" value="add_video">
                        <div class="grid md:grid-cols-2 gap-4 mb-4">
                            <div><label for="video_title" class="block text-gray-700">Video Title</label><input type="text" name="title" id="video_title" class="w-full mt-1 p-2 border rounded-md" required></div>
                            <div><label for="video_course" class="block text-gray-700">Course</label><select name="course_id" id="video_course" class="w-full mt-1 p-2 border rounded-md" required><option value="">Select a course...</option><?php foreach($active_courses as $course): ?><option value="<?php echo $course['course_id']; ?>"><?php echo htmlspecialchars($course['name']) . ' (' . htmlspecialchars($course['level']) . ')'; ?></option><?php endforeach; ?></select></div>
                        </div>
                        <div class="mb-4"><label for="video_url" class="block text-gray-700">Video URL</label><input type="url" name="video_url" id="video_url" class="w-full mt-1 p-2 border rounded-md" placeholder="https://www.youtube.com/watch?v=..." required></div>
                        <div class="mb-4"><label for="video_desc" class="block text-gray-700">Description</label><textarea name="description" id="video_desc" rows="3" class="w-full mt-1 p-2 border rounded-md"></textarea></div>
                        <button type="submit" class="bg-violet-600 hover:bg-violet-700 text-white font-bold py-2 px-4 rounded-lg">Add Video</button>
                    </form>
                </div>
                <div class="bg-white p-6 rounded-lg shadow-md mb-8">
                    <h2 class="text-xl font-semibold mb-4">Active Videos</h2>
                    <table class="min-w-full bg-white"><thead class="bg-gray-200"><tr><th class="py-2 px-4 text-left">Sl. No.</th><th class="py-2 px-4 text-left">Title</th><th class="py-2 px-4 text-left">Course</th><th class="py-2 px-4 text-center">Actions</th></tr></thead>
                        <tbody>
                            <?php if(!empty($active_videos)): $i = 1; ?>
                                <?php foreach($active_videos as $video): ?>
                                <tr class="border-b hover:bg-gray-50">
                                    <td class="py-2 px-4"><?php echo $i++; ?></td>
                                    <td class="py-2 px-4"><?php echo htmlspecialchars($video['title']); ?></td>
                                    <td class="py-2 px-4"><?php echo htmlspecialchars($video['course_name'] . ' (' . $video['level'] . ')'); ?></td>
                                    <td class="py-2 px-4 text-center space-x-4">
                                        <button onclick='openEditVideoModal(<?php echo json_encode($video); ?>)' class="text-blue-500 hover:text-blue-700 font-semibold">Edit</button>
                                        <a href="admin_actions.php?action=set_video_status&id=<?php echo $video['material_id']; ?>&status=inactive" onclick="return confirm('Are you sure?')" class="text-red-500 hover:text-red-700 font-semibold">Deactivate</a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="4" class="text-center py-4 text-gray-500">No active videos found.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                 <div class="bg-white p-6 rounded-lg shadow-md">
                    <h2 class="text-xl font-semibold mb-4 text-gray-500">Inactive Videos</h2>
                    <table class="min-w-full bg-white"><thead class="bg-gray-200"><tr><th class="py-2 px-4 text-left">Sl. No.</th><th class="py-2 px-4 text-left">Title</th><th class="py-2 px-4 text-left">Course</th><th class="py-2 px-4 text-center">Action</th></tr></thead>
                        <tbody>
                            <?php if(!empty($inactive_videos)): $i = 1; ?>
                                <?php foreach($inactive_videos as $video): ?>
                                <tr class="border-b bg-gray-50 text-gray-500">
                                    <td class="py-2 px-4"><?php echo $i++; ?></td>
                                    <td class="py-2 px-4"><?php echo htmlspecialchars($video['title']); ?></td>
                                    <td class="py-2 px-4"><?php echo htmlspecialchars($video['course_name'] . ' (' . $video['level'] . ')'); ?></td>
                                    <td class="py-2 px-4 text-center">
                                        <a href="admin_actions.php?action=set_video_status&id=<?php echo $video['material_id']; ?>&status=active" class="text-green-500 hover:text-green-700 font-semibold">Reactivate</a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="4" class="text-center py-4 text-gray-500">No inactive videos found.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Manage Assignments Tab -->
            <div id="assignments" class="tab-content">
                <h1 class="text-3xl font-bold text-gray-800 mb-8">Manage Assignments</h1>
                <div class="bg-white p-6 rounded-lg shadow-md mb-8">
                    <h2 class="text-xl font-semibold mb-4">Add New Assignment</h2>
                    <form action="admin_actions.php" method="POST">
                        <input type="hidden" name="action" value="add_assignment">
                         <div class="grid md:grid-cols-2 gap-4 mb-4">
                            <div><label for="assignment_title" class="block text-gray-700">Assignment Title</label><input type="text" name="title" id="assignment_title" class="w-full mt-1 p-2 border rounded-md" required></div>
                            <div><label for="material_id" class="block text-gray-700">Based on Video</label><select name="material_id" id="material_id" class="w-full mt-1 p-2 border rounded-md" required><option value="">Select a video...</option><?php foreach($materials as $material): ?><option value="<?php echo $material['material_id']; ?>"><?php echo htmlspecialchars($material['title']); ?></option><?php endforeach; ?></select></div>
                        </div>
                        <div class="mb-4"><label for="assignment_instructions" class="block text-gray-700">Instructions</label><textarea name="instructions" id="assignment_instructions" rows="4" class="w-full mt-1 p-2 border rounded-md"></textarea></div>
                        <button type="submit" class="bg-violet-600 hover:bg-violet-700 text-white font-bold py-2 px-4 rounded-lg">Add Assignment</button>
                    </form>
                </div>
                <div class="bg-white p-6 rounded-lg shadow-md mb-8">
                    <h2 class="text-xl font-semibold mb-4">Active Assignments</h2>
                    <table class="min-w-full bg-white"><thead class="bg-gray-200"><tr><th class="py-2 px-4 text-left">Sl. No.</th><th class="py-2 px-4 text-left">Title</th><th class="py-2 px-4 text-left">Based on Video</th><th class="py-2 px-4 text-center">Actions</th></tr></thead>
                        <tbody>
                            <?php if(!empty($active_assignments)): $i = 1; ?>
                                <?php foreach($active_assignments as $assignment): ?>
                                <tr class="border-b hover:bg-gray-50">
                                    <td class="py-2 px-4"><?php echo $i++; ?></td>
                                    <td class="py-2 px-4"><?php echo htmlspecialchars($assignment['title']); ?></td>
                                    <td class="py-2 px-4"><?php echo htmlspecialchars($assignment['material_title']); ?></td>
                                    <td class="py-2 px-4 text-center space-x-4">
                                        <button onclick='openEditAssignmentModal(<?php echo json_encode($assignment); ?>)' class="text-blue-500 hover:text-blue-700 font-semibold">Edit</button>
                                        <a href="admin_actions.php?action=set_assignment_status&id=<?php echo $assignment['assignment_id']; ?>&status=inactive" onclick="return confirm('Are you sure?')" class="text-red-500 hover:text-red-700 font-semibold">Deactivate</a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="4" class="text-center py-4 text-gray-500">No active assignments found.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                 <div class="bg-white p-6 rounded-lg shadow-md">
                    <h2 class="text-xl font-semibold mb-4 text-gray-500">Inactive Assignments</h2>
                    <table class="min-w-full bg-white"><thead class="bg-gray-200"><tr><th class="py-2 px-4 text-left">Sl. No.</th><th class="py-2 px-4 text-left">Title</th><th class="py-2 px-4 text-left">Based on Video</th><th class="py-2 px-4 text-center">Action</th></tr></thead>
                        <tbody>
                            <?php if(!empty($inactive_assignments)): $i = 1; ?>
                                <?php foreach($inactive_assignments as $assignment): ?>
                                <tr class="border-b bg-gray-50 text-gray-500">
                                    <td class="py-2 px-4"><?php echo $i++; ?></td>
                                    <td class="py-2 px-4"><?php echo htmlspecialchars($assignment['title']); ?></td>
                                    <td class="py-2 px-4"><?php echo htmlspecialchars($assignment['material_title']); ?></td>
                                    <td class="py-2 px-4 text-center">
                                        <a href="admin_actions.php?action=set_assignment_status&id=<?php echo $assignment['assignment_id']; ?>&status=active" class="text-green-500 hover:text-green-700 font-semibold">Reactivate</a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="4" class="text-center py-4 text-gray-500">No inactive assignments found.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Manage Staff Tab -->
            <div id="staff" class="tab-content">
                <h1 class="text-3xl font-bold text-gray-800 mb-8">Manage Staff</h1>
                 <div class="bg-white p-6 rounded-lg shadow-md mb-8">
                    <h2 class="text-xl font-semibold mb-4">Add New Staff Member</h2>
                    <form action="admin_actions.php" method="POST">
                        <input type="hidden" name="action" value="add_staff">
                        <div class="grid md:grid-cols-2 gap-4 mb-4"><input type="text" name="first_name" placeholder="First Name" class="w-full p-2 border rounded-md" required><input type="text" name="last_name" placeholder="Last Name" class="w-full p-2 border rounded-md"></div>
                        <div class="grid md:grid-cols-2 gap-4 mb-4"><input type="email" name="email" placeholder="Email Address" class="w-full p-2 border rounded-md" required><input type="text" name="phone" placeholder="Phone Number" class="w-full p-2 border rounded-md"></div>
                         <div class="mb-4"><label for="staff_password" class="block text-gray-700">Password</label><input type="password" name="password" id="staff_password" placeholder="Create a password" class="w-full mt-1 p-2 border rounded-md" required></div>
                         <div class="mb-4"><label for="staff_course" class="block text-gray-700">Assign to Course</label><select name="course_id" id="staff_course" class="w-full mt-1 p-2 border rounded-md" required><option value="">Select a course...</option><?php foreach($active_courses as $course): ?><option value="<?php echo $course['course_id']; ?>"><?php echo htmlspecialchars($course['name']) . ' (' . htmlspecialchars($course['level']) . ')'; ?></option><?php endforeach; ?></select></div>
                        <button type="submit" class="bg-violet-600 hover:bg-violet-700 text-white font-bold py-2 px-4 rounded-lg">Add Staff Member</button>
                    </form>
                </div>
                <div class="bg-white p-6 rounded-lg shadow-md mb-8">
                    <h2 class="text-xl font-semibold mb-4">Active Staff</h2>
                    <table class="min-w-full bg-white"><thead class="bg-gray-200"><tr><th class="py-2 px-4 text-left">Sl. No.</th><th class="py-2 px-4 text-left">Name</th><th class="py-2 px-4 text-left">Email</th><th class="py-2 px-4 text-left">Phone</th><th class="py-2 px-4 text-left">Course</th><th class="py-2 px-4 text-center">Action</th></tr></thead>
                        <tbody>
                            <?php if(!empty($active_staff)): $i = 1; ?>
                                <?php foreach($active_staff as $staff): ?>
                                <tr class="border-b hover:bg-gray-50">
                                    <td class="py-2 px-4"><?php echo $i++; ?></td>
                                    <td class="py-2 px-4"><?php echo htmlspecialchars($staff['first_name'] . ' ' . $staff['last_name']); ?></td>
                                    <td class="py-2 px-4"><?php echo htmlspecialchars($staff['email']); ?></td>
                                    <td class="py-2 px-4"><?php echo htmlspecialchars($staff['phone']); ?></td>
                                    <td class="py-2 px-4"><?php echo htmlspecialchars($staff['course_name'] . ' (' . $staff['level'] . ')'); ?></td>
                                    <td class="py-2 px-4 text-center">
                                        <a href="admin_actions.php?action=set_staff_status&id=<?php echo $staff['staff_id']; ?>&status=inactive" onclick="return confirm('Are you sure?')" class="text-red-500 hover:text-red-700 font-semibold">Deactivate</a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="6" class="text-center py-4 text-gray-500">No active staff members found.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <div class="bg-white p-6 rounded-lg shadow-md">
                    <h2 class="text-xl font-semibold mb-4 text-gray-500">Inactive Staff</h2>
                    <table class="min-w-full bg-white"><thead class="bg-gray-200"><tr><th class="py-2 px-4 text-left">Sl. No.</th><th class="py-2 px-4 text-left">Name</th><th class="py-2 px-4 text-left">Email</th><th class="py-2 px-4 text-left">Course</th><th class="py-2 px-4 text-center">Action</th></tr></thead>
                        <tbody>
                            <?php if(!empty($inactive_staff)): $i = 1; ?>
                                <?php foreach($inactive_staff as $staff): ?>
                                <tr class="border-b bg-gray-50 text-gray-500">
                                    <td class="py-2 px-4"><?php echo $i++; ?></td>
                                    <td class="py-2 px-4"><?php echo htmlspecialchars($staff['first_name'] . ' ' . $staff['last_name']); ?></td>
                                    <td class="py-2 px-4"><?php echo htmlspecialchars($staff['email']); ?></td>
                                    <td class="py-2 px-4"><?php echo htmlspecialchars($staff['course_name'] . ' (' . $staff['level'] . ')'); ?></td>
                                    <td class="py-2 px-4 text-center">
                                        <a href="admin_actions.php?action=set_staff_status&id=<?php echo $staff['staff_id']; ?>&status=active" class="text-green-500 hover:text-green-700 font-semibold">Reactivate</a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="5" class="text-center py-4 text-gray-500">No inactive staff found.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- View Students Tab -->
            <div id="students" class="tab-content">
                <div class="printable-area">
                    <div class="flex justify-between items-center mb-8 no-print">
                        <h1 class="text-3xl font-bold text-gray-800">Student Details</h1>
                        <div class="flex items-center space-x-2">
                            <a id="download-students-excel-btn" href="download_report.php?type=students<?php echo $student_course_query_string; ?>" class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded-lg flex items-center">
                                <i class="fas fa-file-excel mr-2"></i> Download Excel (CSV)
                            </a>
                            <button onclick="printContent('students')" class="bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-4 rounded-lg flex items-center">
                                <i class="fas fa-file-pdf mr-2"></i> Download PDF
                            </button>
                        </div>
                    </div>
                     <div class="print-header hidden text-center mb-8">
                        <h1 class="text-2xl font-bold">Learn German - Student Report</h1>
                        <p class="text-sm text-gray-600">Generated on: <?php echo date("F j, Y, g:i a"); ?></p>
                    </div>
                    <form action="admin_dashboard.php" method="POST" class="bg-white p-4 rounded-lg shadow-md mb-8 no-print">
                        <input type="hidden" name="tab" value="students">
                        <div class="flex items-end space-x-4">
                            <div>
                                <label for="student_course_filter" class="text-sm font-medium text-gray-700">Filter by Course:</label>
                                <select name="student_course_filter" id="student_course_filter" class="mt-1 p-2 border rounded-md">
                                    <option value="">All Courses</option>
                                    <?php foreach($all_courses as $course): ?>
                                        <option value="<?php echo $course['course_id']; ?>" <?php if ($student_course_filter == $course['course_id']) echo 'selected'; ?>>
                                            <?php echo htmlspecialchars($course['name']) . ' (' . htmlspecialchars($course['level']) . ')'; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <button type="submit" class="bg-violet-600 hover:bg-violet-700 text-white font-bold py-2 px-4 rounded-lg">Filter</button>
                            <a href="admin_dashboard.php?tab=students" class="text-gray-600 hover:text-gray-800 ml-2">Clear Filter</a>
                        </div>
                    </form>
                    <div class="bg-white p-6 rounded-lg shadow-md">
                        <table class="min-w-full bg-white"><thead class="bg-gray-200"><tr><th class="py-2 px-4 text-left">Sl. No.</th><th class="py-2 px-4 text-left">Name</th><th class="py-2 px-4 text-left">Email</th><th class="py-2 px-4 text-left">Phone</th><th class="py-2 px-4 text-left">Course</th><th class="py-2 px-4 text-left">Registered On</th></tr></thead>
                            <tbody>
                                <?php if($students_result && $students_result->num_rows > 0): $i = 1; ?>
                                    <?php while($student = $students_result->fetch_assoc()): ?>
                                    <tr class="border-b hover:bg-gray-50">
                                        <td class="py-2 px-4"><?php echo $i++; ?></td>
                                        <td class="py-2 px-4"><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></td>
                                        <td class="py-2 px-4"><?php echo htmlspecialchars($student['email']); ?></td>
                                        <td class="py-2 px-4"><?php echo htmlspecialchars($student['phone']); ?></td>
                                        <td class="py-2 px-4"><span class="bg-blue-100 text-blue-800 text-xs font-semibold mr-2 px-2.5 py-0.5 rounded"><?php echo htmlspecialchars($student['course_name'] . ' (' . $student['level'] . ')'); ?></span></td>
                                        <td class="py-2 px-4"><?php echo date("M d, Y", strtotime($student['registered_on'])); ?></td>
                                    </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr><td colspan="6" class="text-center py-4 text-gray-500">No students found for the selected filter.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- View Payments Tab -->
            <div id="payments" class="tab-content">
                 <div class="printable-area">
                    <div class="flex justify-between items-center mb-8 no-print">
                        <h1 class="text-3xl font-bold text-gray-800">Payment Reports</h1>
                        <div class="flex items-center space-x-2">
                            <a id="download-excel-btn" href="download_report.php?type=payments<?php echo $date_query_string; ?>" class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded-lg flex items-center">
                                <i class="fas fa-file-excel mr-2"></i> Download Excel (CSV)
                            </a>
                            <button onclick="printContent('payments')" class="bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-4 rounded-lg flex items-center">
                                <i class="fas fa-file-pdf mr-2"></i> Download PDF
                            </button>
                        </div>
                    </div>
                    <div class="print-header hidden text-center mb-8">
                        <h1 class="text-2xl font-bold">Learn German - Payment Report</h1>
                        <p class="text-sm text-gray-600">
                            <?php if ($start_date) echo "From: " . date("d M Y", strtotime($start_date)) . " To: " . date("d M Y", strtotime($end_date)); else echo "All Time"; ?>
                        </p>
                        <p class="text-sm text-gray-600">Generated on: <?php echo date("F j, Y, g:i a"); ?></p>
                    </div>

                    <form action="admin_dashboard.php" method="POST" class="bg-white p-4 rounded-lg shadow-md mb-8 no-print">
                        <input type="hidden" name="tab" value="payments">
                        <div class="flex items-end space-x-4">
                            <div>
                                <label for="start_date" class="text-sm font-medium text-gray-700">Start Date:</label>
                                <input type="date" name="start_date" id="start_date" value="<?php echo htmlspecialchars($start_date); ?>" class="mt-1 p-2 border rounded-md">
                            </div>
                            <div>
                                <label for="end_date" class="text-sm font-medium text-gray-700">End Date:</label>
                                <input type="date" name="end_date" id="end_date" value="<?php echo htmlspecialchars($end_date); ?>" class="mt-1 p-2 border rounded-md">
                            </div>
                            <button type="submit" class="bg-violet-600 hover:bg-violet-700 text-white font-bold py-2 px-4 rounded-lg">Filter</button>
                            <a href="admin_dashboard.php?tab=payments" class="text-gray-600 hover:text-gray-800 ml-2">Clear Filter</a>
                        </div>
                    </form>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                        <div class="bg-white p-6 rounded-lg shadow-md">
                            <h2 class="text-xl font-semibold mb-4 text-gray-700">Total Revenue <?php if($start_date) echo "(Filtered)"; ?></h2>
                            <p class="text-4xl font-bold text-green-600">Rs <?php echo number_format($total_revenue ?? 0, 2); ?></p>
                        </div>
                        <div class="bg-white p-6 rounded-lg shadow-md">
                            <h2 class="text-xl font-semibold mb-4 text-gray-700">Revenue by Course <?php if($start_date) echo "(Filtered)"; ?></h2>
                            <ul class="space-y-2">
                                <?php if(!empty($course_revenue_data)): ?>
                                    <?php foreach($course_revenue_data as $course_rev): ?>
                                    <li class="flex justify-between items-center text-gray-600">
                                        <span><?php echo htmlspecialchars($course_rev['name'] . ' (' . $course_rev['level'] . ')'); ?></span>
                                        <span class="font-semibold">Rs <?php echo number_format($course_rev['course_total'], 2); ?></span>
                                    </li>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <p class="text-gray-500">No revenue in this period.</p>
                                <?php endif; ?>
                            </ul>
                        </div>
                    </div>

                    <div class="bg-white p-6 rounded-lg shadow-md">
                        <h2 class="text-xl font-semibold mb-4">Transactions <?php if($start_date) echo "(Filtered)"; ?></h2>
                        <table class="min-w-full bg-white"><thead class="bg-gray-200"><tr><th class="py-2 px-4 text-left">Sl. No.</th><th class="py-2 px-4 text-left">Student Name</th><th class="py-2 px-4 text-left">Course</th><th class="py-2 px-4 text-left">Amount</th><th class="py-2 px-4 text-left">Date</th><th class="py-2 px-4 text-left">Transaction ID</th></tr></thead>
                            <tbody>
                                <?php if($payments_result && $payments_result->num_rows > 0): $i = 1; mysqli_data_seek($payments_result, 0); ?>
                                    <?php while($payment = $payments_result->fetch_assoc()): ?>
                                    <tr class="border-b hover:bg-gray-50">
                                        <td class="py-2 px-4"><?php echo $i++; ?></td>
                                        <td class="py-2 px-4"><?php echo htmlspecialchars($payment['first_name'] . ' ' . $payment['last_name']); ?></td>
                                        <td class="py-2 px-4"><?php echo htmlspecialchars($payment['course_name'] . ' (' . $payment['level'] . ')'); ?></td>
                                        <td class="py-2 px-4 font-semibold">Rs <?php echo htmlspecialchars(number_format($payment['amount'], 2)); ?></td>
                                        <td class="py-2 px-4"><?php echo date("d M Y, g:i a", strtotime($payment['payment_date'])); ?></td>
                                        <td class="py-2 px-4 text-gray-600 text-sm"><?php echo htmlspecialchars($payment['txn_reference']); ?></td>
                                    </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr><td colspan="6" class="text-center py-4 text-gray-500">No payments found for the selected period.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <!-- View Feedback Tab -->
            <div id="feedback" class="tab-content">
                <h1 class="text-3xl font-bold text-gray-800 mb-8">Student Feedback</h1>
                <div class="bg-white p-6 rounded-lg shadow-md">
                    <table class="min-w-full bg-white"><thead class="bg-gray-200"><tr><th class="py-2 px-4 text-left">Sl. No.</th><th class="py-2 px-4 text-left">Student</th><th class="py-2 px-4 text-left">Course</th><th class="py-2 px-4 text-left w-1/2">Comment</th></tr></thead>
                        <tbody>
                            <?php if($feedback_result && $feedback_result->num_rows > 0): $i = 1; ?>
                                <?php while($feedback = $feedback_result->fetch_assoc()): ?>
                                <tr class="border-b hover:bg-gray-50">
                                    <td class="py-2 px-4"><?php echo $i++; ?></td>
                                    <td class="py-2 px-4 font-semibold"><?php echo htmlspecialchars($feedback['first_name'] . ' ' . $feedback['last_name']); ?></td>
                                    <td class="py-2 px-4"><?php echo htmlspecialchars($feedback['course_name'] . ' (' . $feedback['level'] . ')'); ?></td>
                                    <td class="py-2 px-4 text-gray-700"><?php echo nl2br(htmlspecialchars($feedback['comment'])); ?></td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="4" class="text-center py-4 text-gray-500">No feedback has been submitted yet.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Reports Tab -->
            <div id="reports" class="tab-content">
                <div class="printable-area">
                    <div class="flex justify-between items-center mb-8 no-print">
                        <h1 class="text-3xl font-bold text-gray-800">Reports</h1>
                        <button onclick="printContent('reports')" class="bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-4 rounded-lg flex items-center">
                            <i class="fas fa-file-pdf mr-2"></i> Download PDF
                        </button>
                    </div>
                    <div class="print-header hidden text-center mb-8">
                        <h1 class="text-2xl font-bold">Learn German - Progress Report</h1>
                        <p class="text-sm text-gray-600">Generated on: <?php echo date("F j, Y, g:i a"); ?></p>
                    </div>
                    <div class="bg-white p-6 rounded-lg shadow-md mb-8">
                        <h2 class="text-xl font-semibold mb-4">Course Completion Summary</h2>
                        <table class="min-w-full bg-white"><thead class="bg-gray-200"><tr><th class="py-2 px-4 text-left">Sl. No.</th><th class="py-2 px-4 text-left">Course Name</th><th class="py-2 px-4 text-left">Assigned Staff</th><th class="py-2 px-4 text-center">Total Students</th><th class="py-2 px-4 text-center">Completed</th><th class="py-2 px-4 text-center">Rate</th></tr></thead>
                            <tbody>
                                <?php if(!empty($course_reports)): $i = 1; ?>
                                    <?php foreach($course_reports as $report): ?>
                                    <tr class="border-b hover:bg-gray-50">
                                        <td class="py-2 px-4"><?php echo $i++; ?></td>
                                        <td class="py-2 px-4 font-semibold"><?php echo htmlspecialchars($report['name']); ?></td>
                                        <td class="py-2 px-4"><?php echo htmlspecialchars($report['staff']); ?></td>
                                        <td class="py-2 px-4 text-center"><?php echo $report['total_students']; ?></td>
                                        <td class="py-2 px-4 text-center"><?php echo $report['completed_students']; ?></td>
                                        <td class="py-2 px-4 text-center"><?php $rate = ($report['total_students'] > 0) ? ($report['completed_students'] / $report['total_students']) * 100 : 0; echo number_format($rate, 1) . '%'; ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr><td colspan="6" class="text-center py-4 text-gray-500">No data to generate reports.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="bg-white p-6 rounded-lg shadow-md mb-8">
                        <h2 class="text-xl font-semibold mb-4">Detailed Student Progress</h2>
                        <table class="min-w-full bg-white">
                            <thead class="bg-gray-200"><tr><th class="py-2 px-4 text-left">Sl. No.</th><th class="py-2 px-4 text-left">Student Name</th><th class="py-2 px-4 text-left">Course</th><th class="py-2 px-4 text-center">Progress</th><th class="py-2 px-4 text-center">Status</th></tr></thead>
                            <tbody>
                                <?php if(!empty($student_progress_data)): $i = 1; ?>
                                    <?php foreach($student_progress_data as $student): ?>
                                    <tr class="border-b hover:bg-gray-50">
                                        <td class="py-2 px-4"><?php echo $i++; ?></td>
                                        <td class="py-2 px-4"><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></td>
                                        <td class="py-2 px-4"><?php echo htmlspecialchars($student['course_name'] . ' (' . $student['level'] . ')'); ?></td>
                                        <td class="py-2 px-4 text-center"><?php echo $student['submitted_assignments']; ?> / <?php echo $student['total_assignments']; ?> assignments</td>
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
                                    <tr><td colspan="5" class="text-center py-4 text-gray-500">No student progress to report.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="bg-white p-6 rounded-lg shadow-md">
                        <h2 class="text-xl font-semibold mb-4">Staff Performance Report</h2>
                        <table class="min-w-full bg-white">
                            <thead class="bg-gray-200"><tr><th class="py-2 px-4 text-left">Sl. No.</th><th class="py-2 px-4 text-left">Staff Name</th><th class="py-2 px-4 text-left">Assigned Course</th><th class="py-2 px-4 text-center">Total Submissions</th><th class="py-2 px-4 text-center">Evaluated</th><th class="py-2 px-4 text-center">Pending</th></tr></thead>
                            <tbody>
                                <?php if(!empty($staff_performance_data)): $i = 1; ?>
                                    <?php foreach($staff_performance_data as $staff): ?>
                                    <tr class="border-b hover:bg-gray-50">
                                        <td class="py-2 px-4"><?php echo $i++; ?></td>
                                        <td class="py-2 px-4"><?php echo htmlspecialchars($staff['first_name'] . ' ' . $staff['last_name']); ?></td>
                                        <td class="py-2 px-4"><?php echo htmlspecialchars($staff['course_name'] . ' (' . $staff['level'] . ')'); ?></td>
                                        <td class="py-2 px-4 text-center"><?php echo $staff['total_submissions_for_course']; ?></td>
                                        <td class="py-2 px-4 text-center"><?php echo $staff['evaluated_by_staff']; ?></td>
                                        <td class="py-2 px-4 text-center font-bold text-orange-600"><?php echo $staff['total_submissions_for_course'] - $staff['evaluated_by_staff']; ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr><td colspan="6" class="text-center py-4 text-gray-500">No staff performance data to report.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <!-- View Logs Tab -->
            <div id="logs" class="tab-content">
                <h1 class="text-3xl font-bold text-gray-800 mb-8">User Activity Logs</h1>
                <div class="bg-white p-6 rounded-lg shadow-md">
                    <table class="min-w-full bg-white"><thead class="bg-gray-200"><tr><th class="py-2 px-4 text-left">Sl. No.</th><th class="py-2 px-4 text-left">Username</th><th class="py-2 px-4 text-left">User Type</th><th class="py-2 px-4 text-left">Login Time</th><th class="py-2 px-4 text-left">Logout Time / Status</th></tr></thead>
                        <tbody>
                            <?php if($logs_result && $logs_result->num_rows > 0): $i = 1; mysqli_data_seek($logs_result, 0); ?>
                                <?php while($log = $logs_result->fetch_assoc()): ?>
                                <tr class="border-b hover:bg-gray-50">
                                    <td class="py-2 px-4"><?php echo $i++; ?></td>
                                    <td class="py-2 px-4 font-semibold"><?php echo htmlspecialchars($log['username']); ?></td>
                                    <td class="py-2 px-4 capitalize"><?php echo htmlspecialchars($log['user_type']); ?></td>
                                    <td class="py-2 px-4"><?php echo date("d M Y, g:i:s a", strtotime($log['login_time'])); ?></td>
                                    <td class="py-2 px-4">
                                        <?php if(is_null($log['logout_time'])): ?>
                                            <span class="bg-green-100 text-green-800 text-xs font-semibold px-2.5 py-0.5 rounded-full">Active Session</span>
                                        <?php else: ?>
                                            <?php echo date("d M Y, g:i:s a", strtotime($log['logout_time'])); ?>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="5" class="text-center py-4 text-gray-500">No log entries found.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- Help Tab -->
            <div id="help" class="tab-content">
                <h1 class="text-3xl font-bold text-gray-800 mb-8">Admin Help Guide</h1>
                <div class="space-y-6 bg-white p-8 rounded-lg shadow-md">
                    <div>
                        <h2 class="text-xl font-semibold text-violet-700 mb-2">Dashboard</h2>
                        <p>This is your main overview. It shows quick statistics like the total number of students, active staff, and active courses.</p>
                    </div>
                    <div class="border-t pt-6">
                        <h2 class="text-xl font-semibold text-violet-700 mb-2">Manage Courses</h2>
                        <p>Here you can add new courses (e.g., C1), edit existing ones, or deactivate them. Deactivated courses won't be visible to new students but the data is kept.</p>
                    </div>
                    <div class="border-t pt-6">
                        <h2 class="text-xl font-semibold text-violet-700 mb-2">Manage Videos</h2>
                        <p>Add new video lessons and assign them to a specific course. You can also edit or deactivate videos from this section.</p>
                    </div>
                    <div class="border-t pt-6">
                        <h2 class="text-xl font-semibold text-violet-700 mb-2">Manage Assignments</h2>
                        <p>Create new assignments and link them to a video lesson. Deactivated assignments will not be visible to students.</p>
                    </div>
                    <div class="border-t pt-6">
                        <h2 class="text-xl font-semibold text-violet-700 mb-2">Manage Staff</h2>
                        <p>Add new staff members, assign them to a course, and set their passwords. Deactivating a staff member will prevent them from logging in.</p>
                    </div>
                    <div class="border-t pt-6">
                        <h2 class="text-xl font-semibold text-violet-700 mb-2">View Students</h2>
                        <p>See a list of all registered students. You can filter this list by course and download the data as a PDF or an Excel (CSV) file.</p>
                    </div>
                    <div class="border-t pt-6">
                        <h2 class="text-xl font-semibold text-violet-700 mb-2">View Payments</h2>
                        <p>Review all payment transactions. You can filter the list by a date range to see revenue for specific periods and download the report as a PDF or Excel file.</p>
                    </div>
                    <div class="border-t pt-6">
                        <h2 class="text-xl font-semibold text-violet-700 mb-2">View Feedback</h2>
                        <p>Read comments and feedback submitted by students about their course experience.</p>
                    </div>
                    <div class="border-t pt-6">
                        <h2 class="text-xl font-semibold text-violet-700 mb-2">Reports</h2>
                        <p>Get detailed reports on student completion rates and staff performance. These reports can be printed to PDF.</p>
                    </div>
                    <div class="border-t pt-6">
                        <h2 class="text-xl font-semibold text-violet-700 mb-2">View Logs</h2>
                        <p>Monitor all login and logout activity across the system for security and tracking purposes.</p>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <!-- Edit Course Modal -->
    <div id="editCourseModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden modal-overlay no-print">
        <div class="bg-white p-8 rounded-lg shadow-xl w-full max-w-2xl">
            <h2 class="text-2xl font-bold mb-6">Edit Course</h2>
            <form action="admin_actions.php" method="POST">
                <input type="hidden" name="action" value="edit_course">
                <input type="hidden" name="course_id" id="edit_course_id">
                <div class="grid md:grid-cols-2 gap-4 mb-4">
                    <div><label for="edit_course_name" class="block text-gray-700">Course Name</label><input type="text" name="name" id="edit_course_name" class="w-full mt-1 p-2 border rounded-md" required></div>
                    <div><label for="edit_course_level" class="block text-gray-700">Level</label><select name="level" id="edit_course_level" class="w-full mt-1 p-2 border rounded-md" required><option value="A1">A1</option><option value="A2">A2</option><option value="B1">B1</option><option value="B2">B2</option></select></div>
                </div>
                <div class="mb-4"><label for="edit_course_fee" class="block text-gray-700">Fee (Rs)</label><input type="number" step="0.01" name="fee" id="edit_course_fee" class="w-full mt-1 p-2 border rounded-md" required></div>
                <div class="mb-4"><label for="edit_course_description" class="block text-gray-700">Description</label><textarea name="description" id="edit_course_description" rows="3" class="w-full mt-1 p-2 border rounded-md"></textarea></div>
                <div class="flex justify-end space-x-4">
                    <button type="button" onclick="closeEditCourseModal()" class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold py-2 px-4 rounded-lg">Cancel</button>
                    <button type="submit" class="bg-violet-600 hover:bg-violet-700 text-white font-bold py-2 px-4 rounded-lg">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Edit Video Modal -->
    <div id="editVideoModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden modal-overlay no-print">
         <div class="bg-white p-8 rounded-lg shadow-xl w-full max-w-2xl">
            <h2 class="text-2xl font-bold mb-6">Edit Video</h2>
            <form action="admin_actions.php" method="POST">
                <input type="hidden" name="action" value="edit_video">
                <input type="hidden" name="material_id" id="edit_material_id">
                <div class="grid md:grid-cols-2 gap-4 mb-4">
                    <div><label for="edit_video_title" class="block text-gray-700">Video Title</label><input type="text" name="title" id="edit_video_title" class="w-full mt-1 p-2 border rounded-md" required></div>
                    <div><label for="edit_video_course" class="block text-gray-700">Course</label><select name="course_id" id="edit_video_course" class="w-full mt-1 p-2 border rounded-md" required><?php foreach($active_courses as $course): ?><option value="<?php echo $course['course_id']; ?>"><?php echo htmlspecialchars($course['name']) . ' (' . htmlspecialchars($course['level']) . ')'; ?></option><?php endforeach; ?></select></div>
                </div>
                <div class="mb-4"><label for="edit_video_url" class="block text-gray-700">Video URL</label><input type="url" name="video_url" id="edit_video_url" class="w-full mt-1 p-2 border rounded-md" required></div>
                <div class="mb-4"><label for="edit_video_desc" class="block text-gray-700">Description</label><textarea name="description" id="edit_video_desc" rows="3" class="w-full mt-1 p-2 border rounded-md"></textarea></div>
                <div class="flex justify-end space-x-4">
                    <button type="button" onclick="closeEditVideoModal()" class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold py-2 px-4 rounded-lg">Cancel</button>
                    <button type="submit" class="bg-violet-600 hover:bg-violet-700 text-white font-bold py-2 px-4 rounded-lg">Save Changes</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Assignment Modal -->
    <div id="editAssignmentModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden modal-overlay no-print">
        <div class="bg-white p-8 rounded-lg shadow-xl w-full max-w-2xl">
            <h2 class="text-2xl font-bold mb-6">Edit Assignment</h2>
            <form action="admin_actions.php" method="POST">
                <input type="hidden" name="action" value="edit_assignment">
                <input type="hidden" name="assignment_id" id="edit_assignment_id">
                <div class="grid md:grid-cols-2 gap-4 mb-4">
                    <div><label for="edit_assignment_title" class="block text-gray-700">Assignment Title</label><input type="text" name="title" id="edit_assignment_title" class="w-full mt-1 p-2 border rounded-md" required></div>
                    <div><label for="edit_material_id_select" class="block text-gray-700">Based on Video</label><select name="material_id" id="edit_material_id_select" class="w-full mt-1 p-2 border rounded-md" required><?php foreach($materials as $material): ?><option value="<?php echo $material['material_id']; ?>"><?php echo htmlspecialchars($material['title']); ?></option><?php endforeach; ?></select></div>
                </div>
                <div class="mb-4"><label for="edit_assignment_instructions" class="block text-gray-700">Instructions</label><textarea name="instructions" id="edit_assignment_instructions" rows="4" class="w-full mt-1 p-2 border rounded-md"></textarea></div>
                <div class="flex justify-end space-x-4">
                    <button type="button" onclick="closeEditAssignmentModal()" class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold py-2 px-4 rounded-lg">Cancel</button>
                    <button type="submit" class="bg-violet-600 hover:bg-violet-700 text-white font-bold py-2 px-4 rounded-lg">Save Changes</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function showTab(tabName, element) {
            // Update URL hash without reloading, making the active tab bookmarkable
            history.pushState(null, null, 'admin_dashboard.php#' + tabName);
            
            document.querySelectorAll('.tab-content').forEach(tab => tab.classList.remove('active'));
            document.querySelectorAll('.sidebar-link').forEach(link => link.classList.remove('active'));
            
            document.getElementById(tabName).classList.add('active');
            if(element) {
                element.classList.add('active');
            } else {
                // If activated by URL hash on load, find the corresponding link
                const link = document.querySelector(`a[href="#${tabName}"]`);
                if (link) link.classList.add('active');
            }
        }

        function printContent(tabId) {
            const link = document.querySelector(`a[href="#${tabId}"]`);
            if (link) showTab(tabId, link);
            setTimeout(() => { window.print(); }, 150); // Delay to allow tab to render
        }

        // --- Logic to stay on the correct tab after form submission or on page load ---
        window.addEventListener('load', () => {
            let tabName = window.location.hash.substring(1);
            if (!tabName) {
                // If a form was submitted (like the date filter), use the hidden input value
                tabName = '<?php echo $active_tab; ?>';
            }
            const link = document.querySelector(`a[href="#${tabName}"]`);
            if (link) {
                showTab(tabName, link);
            } else {
                // Default to dashboard if hash is invalid or not present
                showTab('dashboard', document.querySelector('a[href="#dashboard"]'));
            }
        });

        // --- Modal Logic (remains the same) ---
        const editCourseModal = document.getElementById('editCourseModal');
        function openEditCourseModal(data) {
            document.getElementById('edit_course_id').value = data.course_id;
            document.getElementById('edit_course_name').value = data.name;
            document.getElementById('edit_course_level').value = data.level;
            document.getElementById('edit_course_fee').value = data.fee;
            document.getElementById('edit_course_description').value = data.description;
            editCourseModal.classList.remove('hidden');
        }
        function closeEditCourseModal() { editCourseModal.classList.add('hidden'); }

        const editVideoModal = document.getElementById('editVideoModal');
        function openEditVideoModal(data) {
            document.getElementById('edit_material_id').value = data.material_id;
            document.getElementById('edit_video_title').value = data.title;
            document.getElementById('edit_video_course').value = data.course_id;
            document.getElementById('edit_video_url').value = data.video_url;
            document.getElementById('edit_video_desc').value = data.description;
            editVideoModal.classList.remove('hidden');
        }
        function closeEditVideoModal() { editVideoModal.classList.add('hidden'); }

        const editAssignmentModal = document.getElementById('editAssignmentModal');
        function openEditAssignmentModal(data) {
            document.getElementById('edit_assignment_id').value = data.assignment_id;
            document.getElementById('edit_assignment_title').value = data.title;
            document.getElementById('edit_material_id_select').value = data.material_id;
            document.getElementById('edit_assignment_instructions').value = data.instructions;
            editAssignmentModal.classList.remove('hidden');
        }
        function closeEditAssignmentModal() { editAssignmentModal.classList.add('hidden'); }
    </script>
</body>
</html>
