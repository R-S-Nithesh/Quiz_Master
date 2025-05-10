<?php
require_once __DIR__ . '/../includes/config.php';
redirect_if_not_logged_in();

if (!isset($_SESSION['quiz_result'])) {
    header("Location: dashboard.php");
    exit();
}

$result = $_SESSION['quiz_result'];
unset($_SESSION['quiz_result']);

// Get quiz details
$quiz = $conn->prepare("SELECT * FROM quizzes WHERE quiz_id = ?");
$quiz->execute([$result['quiz_id']]);
$quiz = $quiz->fetch(PDO::FETCH_ASSOC);

$page_title = "Quiz Result: " . $quiz['title'];
include '../includes/header.php';

// Get questions and user answers
$questions = $conn->prepare("
    SELECT q.*, ua.option_id, ua.answer_text, o.option_text as selected_option_text, o.is_correct as selected_option_correct
    FROM questions q
    LEFT JOIN user_answers ua ON q.question_id = ua.question_id AND ua.user_id = ?
    LEFT JOIN options o ON ua.option_id = o.option_id
    WHERE q.quiz_id = ?
    ORDER BY q.question_id
");
$questions->execute([$_SESSION['user_id'], $result['quiz_id']]);
$questions = $questions->fetchAll(PDO::FETCH_ASSOC);

// For each question, get all options if it's multiple choice
foreach ($questions as &$question) {
    if ($question['question_type'] === 'multiple_choice') {
        $stmt = $conn->prepare("SELECT * FROM options WHERE question_id = ?");
        $stmt->execute([$question['question_id']]);
        $question['all_options'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
unset($question); // Break the reference
?>

<div class="container">
    <h1 class="mb-4">Quiz Result: <?php echo htmlspecialchars($quiz['title']); ?></h1>
    
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card text-white bg-primary">
                <div class="card-body">
                    <h5 class="card-title">Score</h5>
                    <h2 class="card-text"><?php echo $result['score']; ?>/<?php echo $result['total_questions']; ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card text-white <?php echo $result['percentage'] >= 70 ? 'bg-success' : ($result['percentage'] >= 50 ? 'bg-warning' : 'bg-danger'); ?>">
                <div class="card-body">
                    <h5 class="card-title">Percentage</h5>
                    <h2 class="card-text"><?php echo $result['percentage']; ?>%</h2>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card text-white bg-info">
                <div class="card-body">
                    <h5 class="card-title">Time Taken</h5>
                    <h2 class="card-text">
                        <?php 
                        $minutes = floor($result['time_taken'] / 60);
                        $seconds = $result['time_taken'] % 60;
                        echo "$minutes min $seconds sec";
                        ?>
                    </h2>
                </div>
            </div>
        </div>
    </div>
    
    <div class="card mb-4">
        <div class="card-header">
            Question Review
        </div>
        <div class="card-body">
            <?php foreach($questions as $index => $question): ?>
                <div class="card mb-3 border-<?php 
                    if ($question['question_type'] === 'multiple_choice') {
                        echo $question['selected_option_correct'] ? 'success' : 'danger';
                    } else {
                        echo 'primary';
                    }
                ?>">
                    <div class="card-header">
                        Question #<?php echo $index + 1; ?> 
                        (<?php echo $question['points']; ?> point<?php echo $question['points'] > 1 ? 's' : ''; ?>)
                    </div>
                    <div class="card-body">
                        <h5 class="card-title"><?php echo htmlspecialchars($question['question_text']); ?></h5>
                        
                        <?php if($question['question_type'] === 'multiple_choice'): ?>
                            <h6 class="mt-3">Your Answer:</h6>
                            <div class="alert alert-<?php echo $question['selected_option_correct'] ? 'success' : 'danger'; ?>">
                                <?php echo htmlspecialchars($question['selected_option_text']); ?>
                                <?php if($question['selected_option_correct']): ?>
                                    <i class="fas fa-check ms-2"></i>
                                <?php else: ?>
                                    <i class="fas fa-times ms-2"></i>
                                <?php endif; ?>
                            </div>
                            
                            <?php if(!$question['selected_option_correct']): ?>
                                <h6>Correct Answer:</h6>
                                <div class="alert alert-success">
                                    <?php 
                                    foreach($question['all_options'] as $option) {
                                        if ($option['is_correct']) {
                                            echo htmlspecialchars($option['option_text']);
                                            break;
                                        }
                                    }
                                    ?>
                                </div>
                            <?php endif; ?>
                        <?php elseif($question['question_type'] === 'true_false'): ?>
                            <h6 class="mt-3">Your Answer:</h6>
                            <div class="alert alert-primary">
                                <?php echo ucfirst($question['answer_text']); ?>
                            </div>
                        <?php else: ?>
                            <h6 class="mt-3">Your Answer:</h6>
                            <div class="alert alert-primary">
                                <?php echo htmlspecialchars($question['answer_text']); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    
    <div class="text-center">
        <a href="dashboard.php" class="btn btn-primary">Back to Dashboard</a>
        <a href="take_quiz.php?quiz_id=<?php echo $quiz['quiz_id']; ?>" class="btn btn-secondary">Retake Quiz</a>
    </div>
</div>

<?php include '../includes/footer.php'; ?>