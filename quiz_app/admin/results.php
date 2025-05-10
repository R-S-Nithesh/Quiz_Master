<?php
require_once __DIR__ . '/../includes/config.php';
redirect_if_not_logged_in();
redirect_if_not_admin();

$page_title = "Quiz Results";
include __DIR__ . '/../includes/header.php';

// Get all results
$results = $conn->query("
    SELECT r.*, u.username, q.title 
    FROM quiz_results r
    JOIN users u ON r.user_id = u.user_id
    JOIN quizzes q ON r.quiz_id = q.quiz_id
    ORDER BY r.completed_at DESC
")->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container">
    <h1 class="mb-4">All Quiz Results</h1>
    
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>User</th>
                            <th>Quiz</th>
                            <th>Score</th>
                            <th>Percentage</th>
                            <th>Time Taken</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($results as $result): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($result['username']); ?></td>
                                <td><?php echo htmlspecialchars($result['title']); ?></td>
                                <td><?php echo $result['score']; ?>/<?php echo $result['total_questions']; ?></td>
                                <td><?php echo $result['percentage']; ?>%</td>
                                <td>
                                    <?php
                                    $minutes = floor($result['time_taken'] / 60);
                                    $seconds = $result['time_taken'] % 60;
                                    echo "$minutes min $seconds sec";
                                    ?>
                                </td>
                                <td><?php echo date('M d, Y h:i A', strtotime($result['completed_at'])); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>