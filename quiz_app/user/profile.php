<?php
require_once __DIR__ . '/../includes/config.php';
redirect_if_not_logged_in();

$page_title = "My Profile";
include __DIR__ . '/../includes/header.php';

// Get user data
$stmt = $conn->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
?>

<div class="container">
    <h1 class="mb-4">My Profile</h1>
    
    <div class="row">
        <div class="col-md-6">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Account Information</h5>
                    <table class="table">
                        <tr>
                            <th>Username:</th>
                            <td><?php echo htmlspecialchars($user['username']); ?></td>
                        </tr>
                        <tr>
                            <th>Email:</th>
                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                        </tr>
                        <tr>
                            <th>Account Created:</th>
                            <td><?php echo date('F j, Y', strtotime($user['created_at'])); ?></td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Quiz Statistics</h5>
                    <?php
                    $stats = $conn->prepare("
                        SELECT COUNT(*) as total_quizzes, 
                               AVG(percentage) as avg_score
                        FROM quiz_results 
                        WHERE user_id = ?
                    ");
                    $stats->execute([$_SESSION['user_id']]);
                    $stats = $stats->fetch();
                    ?>
                    <table class="table">
                        <tr>
                            <th>Quizzes Taken:</th>
                            <td><?php echo $stats['total_quizzes']; ?></td>
                        </tr>
                        <tr>
                            <th>Average Score:</th>
                            <td><?php echo round($stats['avg_score'], 2); ?>%</td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>