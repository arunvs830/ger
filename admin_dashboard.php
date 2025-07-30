<?php
// FILE: admin_dashboard.php (Complete and Final Version)
session_start();

// Security Check: Redirect if not logged in as admin
if (!isset($_SESSION['username']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: login.php");
    exit();
}

require_once 'db_connect.php';

// --- Fetch All Data Needed for the Dashboard ---

// Fetch Courses (both active and inactive)
$courses_result = $conn->query("SELECT * FROM tbl_course ORDER BY name");
$all_courses = $courses_result ? $courses_result->fetch_all(MYSQLI_ASSOC) : [];
$active_courses = array_filter($all_courses, function($course) { return $course['status'] === 'active'; });
$inactive_courses = array_filter($all_courses, function($course) { return $course['status'] === 'inactive'; });

// Fetch active Study Materials (Videos) for dropdowns
$materials_result = $conn->query("SELECT material_id, title FROM tbl_study_material WHERE status = 'active' ORDER BY title");
$materials = $materials_result ? $materials_result->fetch_all(MYSQLI_ASSOC) : [];

// Fetch all Videos (both active and inactive) for lists
$videos_list_result = $conn->query("SELECT m.*, c.name as course_name, c.level FROM tbl_study_material m JOIN tbl_course c ON m.course_id = c.course_id ORDER BY c.name, m.title");
$all_videos = $videos_list_result ? $videos_list_result->fetch_all(MYSQLI_ASSOC) : [];
$active_videos = array_filter($all_videos, function($video) { return $video['status'] === 'active'; });
$inactive_videos = array_filter($all_videos, function($video) { return $video['status'] === 'inactive'; });

// Fetch all Assignments (both active and inactive) for lists
$assignments_list_result = $conn->query("SELECT a.*, sm.title as material_title FROM tbl_assignment a JOIN tbl_study_material sm ON a.material_id = sm.material_id ORDER BY sm.title, a.title");
$all_assignments = $assignments_list_result ? $assignments_list_result->fetch_all(MYSQLI_ASSOC) : [];
$active_assignments = array_filter($all_assignments, function($assignment) { return $assignment['status'] === 'active'; });
$inactive_assignments = array_filter($all_assignments, function($assignment) { return $assignment['status'] === 'inactive'; });

// Fetch all Staff (both active and inactive) for lists
$staff_result = $conn->query("SELECT s.*, c.name as course_name, c.level FROM tbl_staff s JOIN tbl_course c ON s.course_id = c.course_id ORDER BY s.last_name");
$all_staff = $staff_result ? $staff_result->fetch_all(MYSQLI_ASSOC) : [];
$active_staff = array_filter($all_staff, function($staff) { return $staff['status'] === 'active'; });
$inactive_staff = array_filter($all_staff, function($staff) { return $staff['status'] === 'inactive'; });

// Fetch all students
$students_result = $conn->query("SELECT s.*, c.name AS course_name, c.level FROM tbl_student s JOIN tbl_course c ON s.course_id = c.course_id ORDER BY s.student_id DESC");

// Fetch all payments
$payments_result = $conn->query("
    SELECT p.payment_id, p.amount, p.payment_date, p.txn_reference, s.first_name, s.last_name, c.name AS course_name, c.level
    FROM tbl_payment p
    JOIN tbl_student s ON p.student_id = s.student_id
    JOIN tbl_course c ON s.course_id = c.course_id
    ORDER BY p.payment_id DESC
");

// Fetch all feedback
$feedback_result = $conn->query("
    SELECT f.comment, s.first_name, s.last_name, c.name AS course_name, c.level
    FROM tbl_feedback f
    JOIN tbl_student s ON f.student_id = s.student_id
    JOIN tbl_course c ON s.course_id = c.course_id
    ORDER BY f.feedback_id DESC
");

// Fetch stats for the overview
$total_students = $students_result ? $students_result->num_rows : 0;
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
    </style>
</head>
<body class="bg-gray-100">

    <div class="flex h-screen bg-gray-100">
        <!-- Sidebar -->
        <div class="w-64 bg-violet-800 text-white flex flex-col fixed h-full">
            <div class="px-6 py-4 border-b border-violet-700">
                <a href="home.php" class="text-2xl font-bold">Learn German</a>
                <span class="text-sm block text-violet-300">Admin Panel</span>
            </div>
            <nav class="flex-1 px-4 py-4 space-y-2 overflow-y-auto">
                <a href="#" class="sidebar-link active flex items-center px-4 py-2 rounded-lg" onclick="showTab('dashboard', this)"><i class="fas fa-tachometer-alt w-6 mr-2"></i> Dashboard</a>
                <a href="#" class="sidebar-link flex items-center px-4 py-2 rounded-lg" onclick="showTab('courses', this)"><i class="fas fa-book w-6 mr-2"></i> Manage Courses</a>
                <a href="#" class="sidebar-link flex items-center px-4 py-2 rounded-lg" onclick="showTab('videos', this)"><i class="fas fa-video w-6 mr-2"></i> Manage Videos</a>
                <a href="#" class="sidebar-link flex items-center px-4 py-2 rounded-lg" onclick="showTab('assignments', this)"><i class="fas fa-file-alt w-6 mr-2"></i> Manage Assignments</a>
                <a href="#" class="sidebar-link flex items-center px-4 py-2 rounded-lg" onclick="showTab('staff', this)"><i class="fas fa-users-cog w-6 mr-2"></i> Manage Staff</a>
                <a href="#" class="sidebar-link flex items-center px-4 py-2 rounded-lg" onclick="showTab('students', this)"><i class="fas fa-user-graduate w-6 mr-2"></i> View Students</a>
                <a href="#" class="sidebar-link flex items-center px-4 py-2 rounded-lg" onclick="showTab('payments', this)"><i class="fas fa-dollar-sign w-6 mr-2"></i> View Payments</a>
                <a href="#" class="sidebar-link flex items-center px-4 py-2 rounded-lg" onclick="showTab('feedback', this)"><i class="fas fa-comment-dots w-6 mr-2"></i> View Feedback</a>
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

            <!-- Dashboard Tab -->
            <div id="dashboard" class="tab-content active">
                <h1 class="text-3xl font-bold text-gray-800 mb-8">Dashboard Overview</h1>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <div class="bg-white p-6 rounded-lg shadow-md flex items-center"><i class="fas fa-graduation-cap text-4xl text-violet-500 mr-4"></i><div><p class="text-gray-500 text-sm">Total Students</p><p class="text-3xl font-bold text-gray-800"><?php echo $total_students; ?></p></div></div>
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
                            <div><label for="course_level" class="block text-gray-700">Level</label><select name="level" id="course_level" class="w-full mt-1 p-2 border rounded-md" required><option value="A1">A1</option><option value="A2">A2</option></select></div>
                        </div>
                        <div class="mb-4"><label for="course_fee" class="block text-gray-700">Fee (Rs)</label><input type="number" step="0.01" name="fee" id="course_fee" class="w-full mt-1 p-2 border rounded-md" required></div>
                        <div class="mb-4"><label for="course_description" class="block text-gray-700">Description</label><textarea name="description" id="course_description" rows="3" class="w-full mt-1 p-2 border rounded-md"></textarea></div>
                        <button type="submit" class="bg-violet-600 hover:bg-violet-700 text-white font-bold py-2 px-4 rounded-lg">Add Course</button>
                    </form>
                </div>
                <div class="bg-white p-6 rounded-lg shadow-md mb-8">
                    <h2 class="text-xl font-semibold mb-4">Active Courses</h2>
                    <table class="min-w-full bg-white"><thead class="bg-gray-200"><tr><th class="py-2 px-4 text-left">Name</th><th class="py-2 px-4 text-left">Level</th><th class="py-2 px-4 text-left">Fee</th><th class="py-2 px-4 text-center">Actions</th></tr></thead>
                        <tbody>
                            <?php if(!empty($active_courses)): ?>
                                <?php foreach($active_courses as $course): ?>
                                <tr class="border-b hover:bg-gray-50">
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
                                <tr><td colspan="4" class="text-center py-4 text-gray-500">No active courses found.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <div class="bg-white p-6 rounded-lg shadow-md">
                    <h2 class="text-xl font-semibold mb-4 text-gray-500">Inactive Courses</h2>
                    <table class="min-w-full bg-white"><thead class="bg-gray-200"><tr><th class="py-2 px-4 text-left">Name</th><th class="py-2 px-4 text-left">Level</th><th class="py-2 px-4 text-left">Fee</th><th class="py-2 px-4 text-center">Action</th></tr></thead>
                        <tbody>
                             <?php if(!empty($inactive_courses)): ?>
                                <?php foreach($inactive_courses as $course): ?>
                                <tr class="border-b bg-gray-50 text-gray-500">
                                    <td class="py-2 px-4"><?php echo htmlspecialchars($course['name']); ?></td>
                                    <td class="py-2 px-4"><?php echo htmlspecialchars($course['level']); ?></td>
                                    <td class="py-2 px-4">Rs <?php echo htmlspecialchars(number_format($course['fee'], 2)); ?></td>
                                    <td class="py-2 px-4 text-center">
                                        <a href="admin_actions.php?action=set_course_status&id=<?php echo $course['course_id']; ?>&status=active" class="text-green-500 hover:text-green-700 font-semibold">Reactivate</a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="4" class="text-center py-4 text-gray-500">No inactive courses found.</td></tr>
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
                    <table class="min-w-full bg-white"><thead class="bg-gray-200"><tr><th class="py-2 px-4 text-left">Title</th><th class="py-2 px-4 text-left">Course</th><th class="py-2 px-4 text-center">Actions</th></tr></thead>
                        <tbody>
                            <?php if(!empty($active_videos)): ?>
                                <?php foreach($active_videos as $video): ?>
                                <tr class="border-b hover:bg-gray-50">
                                    <td class="py-2 px-4"><?php echo htmlspecialchars($video['title']); ?></td>
                                    <td class="py-2 px-4"><?php echo htmlspecialchars($video['course_name'] . ' (' . $video['level'] . ')'); ?></td>
                                    <td class="py-2 px-4 text-center space-x-4">
                                        <button onclick='openEditVideoModal(<?php echo json_encode($video); ?>)' class="text-blue-500 hover:text-blue-700 font-semibold">Edit</button>
                                        <a href="admin_actions.php?action=set_video_status&id=<?php echo $video['material_id']; ?>&status=inactive" onclick="return confirm('Are you sure?')" class="text-red-500 hover:text-red-700 font-semibold">Deactivate</a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="3" class="text-center py-4 text-gray-500">No active videos found.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                 <div class="bg-white p-6 rounded-lg shadow-md">
                    <h2 class="text-xl font-semibold mb-4 text-gray-500">Inactive Videos</h2>
                    <table class="min-w-full bg-white"><thead class="bg-gray-200"><tr><th class="py-2 px-4 text-left">Title</th><th class="py-2 px-4 text-left">Course</th><th class="py-2 px-4 text-center">Action</th></tr></thead>
                        <tbody>
                            <?php if(!empty($inactive_videos)): ?>
                                <?php foreach($inactive_videos as $video): ?>
                                <tr class="border-b bg-gray-50 text-gray-500">
                                    <td class="py-2 px-4"><?php echo htmlspecialchars($video['title']); ?></td>
                                    <td class="py-2 px-4"><?php echo htmlspecialchars($video['course_name'] . ' (' . $video['level'] . ')'); ?></td>
                                    <td class="py-2 px-4 text-center">
                                        <a href="admin_actions.php?action=set_video_status&id=<?php echo $video['material_id']; ?>&status=active" class="text-green-500 hover:text-green-700 font-semibold">Reactivate</a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="3" class="text-center py-4 text-gray-500">No inactive videos found.</td></tr>
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
                    <table class="min-w-full bg-white"><thead class="bg-gray-200"><tr><th class="py-2 px-4 text-left">Title</th><th class="py-2 px-4 text-left">Based on Video</th><th class="py-2 px-4 text-center">Actions</th></tr></thead>
                        <tbody>
                            <?php if(!empty($active_assignments)): ?>
                                <?php foreach($active_assignments as $assignment): ?>
                                <tr class="border-b hover:bg-gray-50">
                                    <td class="py-2 px-4"><?php echo htmlspecialchars($assignment['title']); ?></td>
                                    <td class="py-2 px-4"><?php echo htmlspecialchars($assignment['material_title']); ?></td>
                                    <td class="py-2 px-4 text-center space-x-4">
                                        <button onclick='openEditAssignmentModal(<?php echo json_encode($assignment); ?>)' class="text-blue-500 hover:text-blue-700 font-semibold">Edit</button>
                                        <a href="admin_actions.php?action=set_assignment_status&id=<?php echo $assignment['assignment_id']; ?>&status=inactive" onclick="return confirm('Are you sure?')" class="text-red-500 hover:text-red-700 font-semibold">Deactivate</a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="3" class="text-center py-4 text-gray-500">No active assignments found.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                 <div class="bg-white p-6 rounded-lg shadow-md">
                    <h2 class="text-xl font-semibold mb-4 text-gray-500">Inactive Assignments</h2>
                    <table class="min-w-full bg-white"><thead class="bg-gray-200"><tr><th class="py-2 px-4 text-left">Title</th><th class="py-2 px-4 text-left">Based on Video</th><th class="py-2 px-4 text-center">Action</th></tr></thead>
                        <tbody>
                            <?php if(!empty($inactive_assignments)): ?>
                                <?php foreach($inactive_assignments as $assignment): ?>
                                <tr class="border-b bg-gray-50 text-gray-500">
                                    <td class="py-2 px-4"><?php echo htmlspecialchars($assignment['title']); ?></td>
                                    <td class="py-2 px-4"><?php echo htmlspecialchars($assignment['material_title']); ?></td>
                                    <td class="py-2 px-4 text-center">
                                        <a href="admin_actions.php?action=set_assignment_status&id=<?php echo $assignment['assignment_id']; ?>&status=active" class="text-green-500 hover:text-green-700 font-semibold">Reactivate</a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="3" class="text-center py-4 text-gray-500">No inactive assignments found.</td></tr>
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
                    <table class="min-w-full bg-white"><thead class="bg-gray-200"><tr><th class="py-2 px-4 text-left">Name</th><th class="py-2 px-4 text-left">Email</th><th class="py-2 px-4 text-left">Phone</th><th class="py-2 px-4 text-left">Course</th><th class="py-2 px-4 text-center">Action</th></tr></thead>
                        <tbody>
                            <?php if(!empty($active_staff)): ?>
                                <?php foreach($active_staff as $staff): ?>
                                <tr class="border-b hover:bg-gray-50">
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
                                <tr><td colspan="5" class="text-center py-4 text-gray-500">No active staff members found.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <div class="bg-white p-6 rounded-lg shadow-md">
                    <h2 class="text-xl font-semibold mb-4 text-gray-500">Inactive Staff</h2>
                    <table class="min-w-full bg-white"><thead class="bg-gray-200"><tr><th class="py-2 px-4 text-left">Name</th><th class="py-2 px-4 text-left">Email</th><th class="py-2 px-4 text-left">Course</th><th class="py-2 px-4 text-center">Action</th></tr></thead>
                        <tbody>
                            <?php if(!empty($inactive_staff)): ?>
                                <?php foreach($inactive_staff as $staff): ?>
                                <tr class="border-b bg-gray-50 text-gray-500">
                                    <td class="py-2 px-4"><?php echo htmlspecialchars($staff['first_name'] . ' ' . $staff['last_name']); ?></td>
                                    <td class="py-2 px-4"><?php echo htmlspecialchars($staff['email']); ?></td>
                                    <td class="py-2 px-4"><?php echo htmlspecialchars($staff['course_name'] . ' (' . $staff['level'] . ')'); ?></td>
                                    <td class="py-2 px-4 text-center">
                                        <a href="admin_actions.php?action=set_staff_status&id=<?php echo $staff['staff_id']; ?>&status=active" class="text-green-500 hover:text-green-700 font-semibold">Reactivate</a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="4" class="text-center py-4 text-gray-500">No inactive staff found.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- View Students Tab -->
            <div id="students" class="tab-content">
                <h1 class="text-3xl font-bold text-gray-800 mb-8">Student Details</h1>
                <div class="bg-white p-6 rounded-lg shadow-md">
                    <table class="min-w-full bg-white"><thead class="bg-gray-200"><tr><th class="py-2 px-4 text-left">Name</th><th class="py-2 px-4 text-left">Email</th><th class="py-2 px-4 text-left">Phone</th><th class="py-2 px-4 text-left">Course</th><th class="py-2 px-4 text-left">Registered On</th></tr></thead>
                        <tbody>
                            <?php if($students_result && $students_result->num_rows > 0): mysqli_data_seek($students_result, 0); ?>
                                <?php while($student = $students_result->fetch_assoc()): ?>
                                <tr class="border-b hover:bg-gray-50">
                                    <td class="py-2 px-4"><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></td>
                                    <td class="py-2 px-4"><?php echo htmlspecialchars($student['email']); ?></td>
                                    <td class="py-2 px-4"><?php echo htmlspecialchars($student['phone']); ?></td>
                                    <td class="py-2 px-4"><span class="bg-blue-100 text-blue-800 text-xs font-semibold mr-2 px-2.5 py-0.5 rounded"><?php echo htmlspecialchars($student['course_name'] . ' (' . $student['level'] . ')'); ?></span></td>
                                    <td class="py-2 px-4"><?php echo date("M d, Y", strtotime($student['registered_on'])); ?></td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="5" class="text-center py-4 text-gray-500">No students found.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- View Payments Tab -->
            <div id="payments" class="tab-content">
                <h1 class="text-3xl font-bold text-gray-800 mb-8">Payment Details</h1>
                <div class="bg-white p-6 rounded-lg shadow-md">
                    <div class="overflow-x-auto">
                        <table class="min-w-full bg-white">
                             <thead class="bg-gray-200">
                                <tr>
                                    <th class="py-2 px-4 text-left">Student Name</th>
                                    <th class="py-2 px-4 text-left">Course</th>
                                    <th class="py-2 px-4 text-left">Amount</th>
                                    <th class="py-2 px-4 text-left">Date</th>
                                    <th class="py-2 px-4 text-left">Transaction ID</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if($payments_result && $payments_result->num_rows > 0): ?>
                                    <?php while($payment = $payments_result->fetch_assoc()): ?>
                                    <tr class="border-b hover:bg-gray-50">
                                        <td class="py-2 px-4"><?php echo htmlspecialchars($payment['first_name'] . ' ' . $payment['last_name']); ?></td>
                                        <td class="py-2 px-4"><?php echo htmlspecialchars($payment['course_name'] . ' (' . $payment['level'] . ')'); ?></td>
                                        <td class="py-2 px-4 font-semibold">Rs <?php echo htmlspecialchars(number_format($payment['amount'], 2)); ?></td>
                                        <td class="py-2 px-4"><?php echo date("d M Y, g:i a", strtotime($payment['payment_date'])); ?></td>
                                        <td class="py-2 px-4 text-gray-600 text-sm"><?php echo htmlspecialchars($payment['txn_reference']); ?></td>
                                    </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr><td colspan="5" class="text-center py-4 text-gray-500">No payments have been recorded yet.</td></tr>
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
                    <table class="min-w-full bg-white"><thead class="bg-gray-200"><tr><th class="py-2 px-4 text-left">Student</th><th class="py-2 px-4 text-left">Course</th><th class="py-2 px-4 text-left w-2/3">Comment</th></tr></thead>
                        <tbody>
                            <?php if($feedback_result && $feedback_result->num_rows > 0): ?>
                                <?php while($feedback = $feedback_result->fetch_assoc()): ?>
                                <tr class="border-b hover:bg-gray-50">
                                    <td class="py-2 px-4 font-semibold"><?php echo htmlspecialchars($feedback['first_name'] . ' ' . $feedback['last_name']); ?></td>
                                    <td class="py-2 px-4"><?php echo htmlspecialchars($feedback['course_name'] . ' (' . $feedback['level'] . ')'); ?></td>
                                    <td class="py-2 px-4 text-gray-700"><?php echo nl2br(htmlspecialchars($feedback['comment'])); ?></td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="3" class="text-center py-4 text-gray-500">No feedback has been submitted yet.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Course Modal -->
    <div id="editCourseModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden modal-overlay">
        <div class="bg-white p-8 rounded-lg shadow-xl w-full max-w-2xl">
            <h2 class="text-2xl font-bold mb-6">Edit Course</h2>
            <form action="admin_actions.php" method="POST">
                <input type="hidden" name="action" value="edit_course">
                <input type="hidden" name="course_id" id="edit_course_id">
                <div class="grid md:grid-cols-2 gap-4 mb-4">
                    <div><label for="edit_course_name" class="block text-gray-700">Course Name</label><input type="text" name="name" id="edit_course_name" class="w-full mt-1 p-2 border rounded-md" required></div>
                    <div><label for="edit_course_level" class="block text-gray-700">Level</label><select name="level" id="edit_course_level" class="w-full mt-1 p-2 border rounded-md" required><option value="A1">A1</option><option value="A2">A2</option></select></div>
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
    <div id="editVideoModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden modal-overlay">
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
    <div id="editAssignmentModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden modal-overlay">
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
            document.querySelectorAll('.tab-content').forEach(tab => tab.classList.remove('active'));
            document.querySelectorAll('.sidebar-link').forEach(link => link.classList.remove('active'));
            document.getElementById(tabName).classList.add('active');
            element.classList.add('active');
        }

        // --- Course Modal Logic ---
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

        // --- Video Modal Logic ---
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

        // --- Assignment Modal Logic ---
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
