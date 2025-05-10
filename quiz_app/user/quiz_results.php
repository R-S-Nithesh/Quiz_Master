<?php
require_once __DIR__ . '/../includes/config.php';
redirect_if_not_logged_in();

$page_title = "My Quiz Results";
include __DIR__ . '/../includes/header.php';

// Get all results for current user
$results = $conn->prepare("
    SELECT r.*, q.title 
    FROM quiz_results r
    JOIN quizzes q ON r.quiz_id = q.quiz_id
    WHERE r.user_id = ?
    ORDER BY r.completed_at DESC
");
$results->execute([$_SESSION['user_id']]);
$results = $results->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container">
    <h1 class="mb-4">My Quiz Results</h1>
    
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Quiz</th>
                            <th>Score</th>
                            <th>Percentage</th>
                            <th>Date Taken</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($results as $result): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($result['title']); ?></td>
                                <td><?php echo $result['score']; ?>/<?php echo $result['total_questions']; ?></td>
                                <td><?php echo $result['percentage']; ?>%</td>
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