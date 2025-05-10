<?php
require_once __DIR__ . '/../includes/config.php';
redirect_if_not_logged_in();

$page_title = "User Dashboard";
include '../includes/header.php';

// Get available quizzes
$quizzes = $conn->query("
    SELECT q.*, 
           (SELECT COUNT(*) FROM questions WHERE quiz_id = q.quiz_id) as question_count,
           (SELECT COUNT(*) FROM quiz_results WHERE quiz_id = q.quiz_id AND user_id = {$_SESSION['user_id']}) as attempts
    FROM quizzes q
    ORDER BY q.created_at DESC
")->fetchAll(PDO::FETCH_ASSOC);

// Get recent results
$results = $conn->prepare("
    SELECT r.*, q.title 
    FROM quiz_results r
    JOIN quizzes q ON r.quiz_id = q.quiz_id
    WHERE r.user_id = ?
    ORDER BY r.completed_at DESC LIMIT 3
");
$results->execute([$_SESSION['user_id']]);
$results = $results->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container">
    <h1 class="mb-4">Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?></h1>
    
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card text-white bg-primary">
                <div class="card-body">
                    <h5 class="card-title">Quizzes Available</h5>
                    <h2 class="card-text"><?php echo count($quizzes); ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card text-white bg-success">
                <div class="card-body">
                    <h5 class="card-title">Quizzes Taken</h5>
                    <h2 class="card-text">
                        <?php 
                        $taken = array_reduce($quizzes, function($carry, $quiz) {
                            return $carry + ($quiz['attempts'] > 0 ? 1 : 0);
                        }, 0);
                        echo $taken;
                        ?>
                    </h2>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card text-white bg-info">
                <div class="card-body">
                    <h5 class="card-title">Average Score</h5>
                    <h2 class="card-text">
                        <?php 
                        $avg = $conn->query("
                            SELECT AVG(percentage) 
                            FROM quiz_results 
                            WHERE user_id = {$_SESSION['user_id']}
                        ")->fetchColumn();
                        echo $avg ? round($avg, 1) . '%' : 'N/A';
                        ?>
                    </h2>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row">
        <div class="col-md-8">
            <div class="card mb-4">
                <div class="card-header">
                    Available Quizzes
                </div>
                <div class="card-body">
                    <?php if(empty($quizzes)): ?>
                        <p>No quizzes available at the moment.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Quiz</th>
                                        <th>Questions</th>
                                        <th>Time Limit</th>
                                        <th>Attempts</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($quizzes as $quiz): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($quiz['title']); ?></td>
                                            <td><?php echo $quiz['question_count']; ?></td>
                                            <td><?php echo $quiz['time_limit']; ?> mins</td>
                                            <td><?php echo $quiz['attempts']; ?></td>
                                            <td>
                                                <a href="take_quiz.php?quiz_id=<?php echo $quiz['quiz_id']; ?>" 
                                                   class="btn btn-sm btn-primary">
                                                    <?php echo $quiz['attempts'] > 0 ? 'Retake' : 'Take Quiz'; ?>
                                                </a>
                                                <?php if($quiz['attempts'] > 0): ?>
                                                    <a href="quiz_results.php?quiz_id=<?php echo $quiz['quiz_id']; ?>" 
                                                       class="btn btn-sm btn-info">
                                                        View Results
                                                    </a>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    Recent Results
                </div>
                <div class="card-body">
                    <?php if(empty($results)): ?>
                        <p>No quiz results yet.</p>
                    <?php else: ?>
                        <ul class="list-group">
                            <?php foreach($results as $result): ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <div>
                                        <strong><?php echo htmlspecialchars($result['title']); ?></strong><br>
                                        <small><?php echo date('M d, Y', strtotime($result['completed_at'])); ?></small>
                                    </div>
                                    <span class="badge bg-<?php echo $result['percentage'] >= 70 ? 'success' : ($result['percentage'] >= 50 ? 'warning' : 'danger'); ?> rounded-pill">
                                        <?php echo $result['percentage']; ?>%
                                    </span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                        <div class="mt-3 text-center">
                            <a href="quiz_results.php" class="btn btn-sm btn-outline-primary">View All Results</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>