<?php
// FILE: evaluate_submission.php (with Score functionality)
session_start();

if (!isset($_SESSION['username']) || $_SESSION['user_type'] !== 'staff') {
    header("Location: login.php");
    exit();
}

require_once 'db_connect.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: staff_dashboard.php");
    exit();
}

$submission_id = $_GET['id'];

// Get staff ID
$username = $_SESSION['username'];
$stmt_staff = $conn->prepare("SELECT staff_id FROM tbl_staff WHERE email = ?");
$stmt_staff->bind_param("s", $username);
$stmt_staff->execute();
$staff_id = $stmt_staff->get_result()->fetch_assoc()['staff_id'];
$stmt_staff->close();

// Fetch submission details including the score
$stmt = $conn->prepare("
    SELECT 
        sub.submission_text, a.title AS assignment_title, a.instructions,
        stu.first_name, stu.last_name, eval.feedback_text, eval.score
    FROM tbl_assignment_submission AS sub
    JOIN tbl_assignment AS a ON sub.assignment_id = a.assignment_id
    JOIN tbl_student AS stu ON sub.student_id = stu.student_id
    LEFT JOIN tbl_assignment_evaluation AS eval ON sub.submission_id = eval.submission_id
    WHERE sub.submission_id = ?
");
$stmt->bind_param("i", $submission_id);
$stmt->execute();
$submission = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$submission) {
    echo "Submission not found.";
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Evaluate Submission</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        .btn-dark { background-color: #1f2937; color: white; transition: background-color 0.3s ease; }
        .btn-dark:hover { background-color: #111827; }
    </style>
</head>
<body class="bg-gray-100">
    <div class="container mx-auto p-8">
        <div class="bg-white p-8 rounded-xl shadow-lg max-w-4xl mx-auto">
            <div class="flex justify-between items-center mb-6 border-b pb-4">
                <h1 class="text-3xl font-bold text-gray-800">Evaluate Assignment</h1>
                <a href="staff_dashboard.php" class="text-violet-600 hover:text-violet-800">&larr; Back to Dashboard</a>
            </div>

            <div class="mb-6">
                <h2 class="text-xl font-semibold text-gray-700"><?php echo htmlspecialchars($submission['assignment_title']); ?></h2>
                <p class="text-sm text-gray-500">Submitted by: <?php echo htmlspecialchars($submission['first_name'] . ' ' . $submission['last_name']); ?></p>
            </div>

            <div class="mb-6 bg-gray-50 p-4 rounded-lg">
                <h3 class="font-bold text-gray-700 mb-2">Instructions:</h3>
                <p class="text-gray-600 italic">"<?php echo nl2br(htmlspecialchars($submission['instructions'])); ?>"</p>
            </div>

            <div class="mb-6">
                <h3 class="font-bold text-gray-700 mb-2">Student's Answer:</h3>
                <div class="bg-white border border-gray-200 p-4 rounded-lg min-h-[150px]">
                    <p class="text-gray-800"><?php echo nl2br(htmlspecialchars($submission['submission_text'])); ?></p>
                </div>
            </div>

            <hr class="my-8">

            <div>
                <h2 class="text-2xl font-bold text-gray-800 mb-4">Your Evaluation</h2>
                <?php if (!is_null($submission['feedback_text'])): ?>
                    <div class="bg-green-100 p-4 rounded-lg">
                        <h3 class="font-bold text-green-800 mb-2">Evaluation Submitted:</h3>
                        <p class="text-green-900 mb-2"><strong>Score:</strong> <?php echo htmlspecialchars($submission['score']); ?> / 100</p>
                        <p class="text-green-900"><strong>Feedback:</strong> <?php echo nl2br(htmlspecialchars($submission['feedback_text'])); ?></p>
                    </div>
                <?php else: ?>
                    <form action="staff_actions.php" method="POST">
                        <input type="hidden" name="action" value="evaluate_submission">
                        <input type="hidden" name="submission_id" value="<?php echo $submission_id; ?>">
                        <input type="hidden" name="staff_id" value="<?php echo $staff_id; ?>">
                        
                        <div class="mb-4">
                            <label for="score" class="block text-gray-700 font-semibold mb-2">Score (out of 100)</label>
                            <input type="number" name="score" id="score" step="0.5" min="0" max="100" class="w-full md:w-1/3 p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-violet-500 transition" placeholder="e.g., 85.5" required>
                        </div>

                        <div>
                            <label for="feedback_text" class="block text-gray-700 font-semibold mb-2">Feedback:</label>
                            <textarea name="feedback_text" id="feedback_text" rows="6" class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-violet-500 transition" placeholder="Provide constructive feedback for the student..." required></textarea>
                        </div>
                        <div class="text-right mt-4">
                            <button type="submit" class="btn-dark font-bold py-2 px-6 rounded-lg">Submit Evaluation</button>
                        </div>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
