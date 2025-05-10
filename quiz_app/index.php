<?php
require_once __DIR__ . '/includes/config.php';

// Only redirect to dashboard if user is logged in
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] === 'admin') {
        header("Location: admin/dashboard.php");
    } else {
        header("Location: user/dashboard.php");
    }
    exit();
}

// Show public homepage if not logged in
include __DIR__ . '/includes/header.php';
?>

<div class="container text-center py-5">
    <h1>Welcome to Quiz Master</h1>
    <p class="lead">Please login or register to take quizzes</p>
    <div class="mt-4">
        <a href="login.php" class="btn btn-primary btn-lg mx-2">Login</a>
        <a href="register.php" class="btn btn-secondary btn-lg mx-2">Register</a>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>