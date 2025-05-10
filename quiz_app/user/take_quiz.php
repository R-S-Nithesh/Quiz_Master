<?php
require_once __DIR__ . '/../includes/config.php';
redirect_if_not_logged_in();

if (!isset($_GET['quiz_id'])) {
    header("Location: dashboard.php");
    exit();
}

$quiz_id = $_GET['quiz_id'];

// Get quiz details
$quiz = $conn->prepare("SELECT * FROM quizzes WHERE quiz_id = ?");
$quiz->execute([$quiz_id]);
$quiz = $quiz->fetch(PDO::FETCH_ASSOC);

if (!$quiz) {
    header("Location: dashboard.php");
    exit();
}

// Check if user already has results for this quiz
$existing_result = $conn->prepare("
    SELECT * FROM quiz_results 
    WHERE quiz_id = ? AND user_id = ?
    ORDER BY completed_at DESC LIMIT 1
");
$existing_result->execute([$quiz_id, $_SESSION['user_id']]);
$existing_result = $existing_result->fetch(PDO::FETCH_ASSOC);

$page_title = "Take Quiz: " . $quiz['title'];
include '../includes/header.php';

// Get questions for this quiz
$questions = $conn->prepare("
    SELECT q.* 
    FROM questions q
    WHERE q.quiz_id = ?
    ORDER BY q.question_id
");
$questions->execute([$quiz_id]);
$questions = $questions->fetchAll(PDO::FETCH_ASSOC);

// Get options for each question
foreach ($questions as &$question) {
    if ($question['question_type'] === 'multiple_choice') {
        $stmt = $conn->prepare("SELECT * FROM options WHERE question_id = ?");
        $stmt->execute([$question['question_id']]);
        $question['options'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
unset($question); // Break the reference

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $start_time = $_POST['start_time'];
    $end_time = time();
    $time_taken = $end_time - $start_time;
    
    // Calculate score
    $score = 0;
    $total_questions = count($questions);
    
    // Process each question
    foreach ($questions as $question) {
        $question_id = $question['question_id'];
        
        if ($question['question_type'] === 'multiple_choice') {
            $selected_option_id = $_POST['question_' . $question_id] ?? null;
            
            if ($selected_option_id) {
                // Check if the selected option is correct
                $stmt = $conn->prepare("SELECT is_correct FROM options WHERE option_id = ?");
                $stmt->execute([$selected_option_id]);
                $is_correct = $stmt->fetchColumn();
                
                if ($is_correct) {
                    $score += $question['points'];
                }
                
                // Save user answer
                $stmt = $conn->prepare("
                    INSERT INTO user_answers (user_id, question_id, option_id)
                    VALUES (?, ?, ?)
                ");
                $stmt->execute([$_SESSION['user_id'], $question_id, $selected_option_id]);
            }
        } elseif ($question['question_type'] === 'true_false') {
            $answer = $_POST['question_' . $question_id] ?? null;
            
            if ($answer !== null) {
                // For simplicity, we'll consider 'true' as correct for all true/false questions
                // In a real app, you'd store the correct answer in the database
                if ($answer === 'true') {
                    $score += $question['points'];
                }
                
                // Save user answer
                $stmt = $conn->prepare("
                    INSERT INTO user_answers (user_id, question_id, answer_text)
                    VALUES (?, ?, ?)
                ");
                $stmt->execute([$_SESSION['user_id'], $question_id, $answer]);
            }
        } elseif ($question['question_type'] === 'short_answer') {
            $answer = trim($_POST['question_' . $question_id] ?? '');
            
            if (!empty($answer)) {
                // For simplicity, we'll give full points for any non-empty answer
                // In a real app, you'd compare with a correct answer or implement manual grading
                $score += $question['points'];
                
                // Save user answer
                $stmt = $conn->prepare("
                    INSERT INTO user_answers (user_id, question_id, answer_text)
                    VALUES (?, ?, ?)
                ");
                $stmt->execute([$_SESSION['user_id'], $question_id, $answer]);
            }
        }
    }
    
    // Calculate percentage
    $percentage = round(($score / $total_questions) * 100, 2);
    
    // Save quiz result
    $stmt = $conn->prepare("
        INSERT INTO quiz_results (user_id, quiz_id, score, total_questions, percentage, time_taken)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $_SESSION['user_id'],
        $quiz_id,
        $score,
        $total_questions,
        $percentage,
        $time_taken
    ]);
    
    $_SESSION['quiz_result'] = [
        'quiz_id' => $quiz_id,
        'score' => $score,
        'total_questions' => $total_questions,
        'percentage' => $percentage,
        'time_taken' => $time_taken
    ];
    
    header("Location: quiz_result.php");
    exit();
}
?>

<div class="container">
    <h1 class="mb-4"><?php echo htmlspecialchars($quiz['title']); ?></h1>
    
    <?php if($existing_result): ?>
        <div class="alert alert-info">
            You previously scored <?php echo $existing_result['score']; ?>/<?php echo $existing_result['total_questions']; ?> 
            (<?php echo $existing_result['percentage']; ?>%) on this quiz.
        </div>
    <?php endif; ?>
    
    <div class="card mb-4">
        <div class="card-header">
            Quiz Instructions
        </div>
        <div class="card-body">
            <p><?php echo htmlspecialchars($quiz['description']); ?></p>
            <ul>
                <li>Time Limit: <?php echo $quiz['time_limit']; ?> minutes</li>
                <li>Total Questions: <?php echo count($questions); ?></li>
                <li>Points: Vary per question</li>
            </ul>
        </div>
    </div>
    
    <form method="POST" id="quizForm">
        <input type="hidden" name="start_time" value="<?php echo time(); ?>">
        
        <?php foreach($questions as $index => $question): ?>
            <div class="card mb-4">
                <div class="card-header">
                    Question #<?php echo $index + 1; ?> 
                    (<?php echo $question['points']; ?> point<?php echo $question['points'] > 1 ? 's' : ''; ?>)
                </div>
                <div class="card-body">
                    <h5 class="card-title"><?php echo htmlspecialchars($question['question_text']); ?></h5>
                    
                    <?php if($question['question_type'] === 'multiple_choice'): ?>
                        <div class="list-group">
                            <?php foreach($question['options'] as $option): ?>
                                <label class="list-group-item">
                                    <input class="form-check-input me-1" 
                                           type="radio" 
                                           name="question_<?php echo $question['question_id']; ?>" 
                                           value="<?php echo $option['option_id']; ?>" required>
                                    <?php echo htmlspecialchars($option['option_text']); ?>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    <?php elseif($question['question_type'] === 'true_false'): ?>
                        <div class="list-group">
                            <label class="list-group-item">
                                <input class="form-check-input me-1" 
                                       type="radio" 
                                       name="question_<?php echo $question['question_id']; ?>" 
                                       value="true" required>
                                True
                            </label>
                            <label class="list-group-item">
                                <input class="form-check-input me-1" 
                                       type="radio" 
                                       name="question_<?php echo $question['question_id']; ?>" 
                                       value="false" required>
                                False
                            </label>
                        </div>
                    <?php else: ?>
                        <div class="form-floating">
                            <textarea class="form-control" 
                                      name="question_<?php echo $question['question_id']; ?>" 
                                      id="question_<?php echo $question['question_id']; ?>" 
                                      style="height: 100px" required></textarea>
                            <label for="question_<?php echo $question['question_id']; ?>">Your answer</label>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
        
        <div class="d-grid">
            <button type="submit" class="btn btn-primary btn-lg">Submit Quiz</button>
        </div>
    </form>
</div>

<script>
// Timer functionality
const timeLimit = <?php echo $quiz['time_limit'] * 60; ?>;
let timeLeft = timeLimit;

function updateTimer() {
    const minutes = Math.floor(timeLeft / 60);
    let seconds = timeLeft % 60;
    seconds = seconds < 10 ? '0' + seconds : seconds;
    document.getElementById('timer').textContent = `${minutes}:${seconds}`;
    
    if (timeLeft <= 0) {
        document.getElementById('quizForm').submit();
    } else {
        timeLeft--;
    }
}

// Update timer every second
setInterval(updateTimer, 1000);
updateTimer(); // Initial call
</script>

<?php include '../includes/footer.php'; ?>