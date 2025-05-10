<?php
require_once __DIR__ . '/../includes/config.php';
redirect_if_not_logged_in();
redirect_if_not_admin();

$page_title = "Admin Dashboard";
include '../includes/header.php';

// Get stats
$users_count = $conn->query("SELECT COUNT(*) FROM users")->fetchColumn();
$quizzes_count = $conn->query("SELECT COUNT(*) FROM quizzes")->fetchColumn();
$results_count = $conn->query("SELECT COUNT(*) FROM quiz_results")->fetchColumn();
?>

<div class="container">
    <h1 class="mb-4">Admin Dashboard</h1>
    
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card text-white bg-primary">
                <div class="card-body">
                    <h5 class="card-title">Admin & Users</h5>
                    <h2 class="card-text"><?php echo $users_count; ?></h2>
                    <a href="manage_users.php" class="text-white">View Admin & Users</a>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card text-white bg-success">
                <div class="card-body">
                    <h5 class="card-title">Quizzes</h5>
                    <h2 class="card-text"><?php echo $quizzes_count; ?></h2>
                    <a href="manage_quizzes.php" class="text-white">Manage Quizzes</a>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card text-white bg-info">
                <div class="card-body">
                    <h5 class="card-title">Results</h5>
                    <h2 class="card-text"><?php echo $results_count; ?></h2>
                    <a href="results.php" class="text-white">View Results</a>
                </div>
            </div>
        </div>
    </div>
    
    <div class="card">
        <div class="card-header">
            Recent Quiz Results
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>User</th>
                            <th>Quiz</th>
                            <th>Score</th>
                            <th>Percentage</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $stmt = $conn->query("
                            SELECT r.*, u.username, q.title 
                            FROM quiz_results r
                            JOIN users u ON r.user_id = u.user_id
                            JOIN quizzes q ON r.quiz_id = q.quiz_id
                            ORDER BY r.completed_at DESC LIMIT 5
                        ");
                        
                        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                            echo "<tr>
                                <td>{$row['username']}</td>
                                <td>{$row['title']}</td>
                                <td>{$row['score']}/{$row['total_questions']}</td>
                                <td>{$row['percentage']}%</td>
                                <td>" . date('M d, Y h:i A', strtotime($row['completed_at'])) . "</td>
                            </tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>