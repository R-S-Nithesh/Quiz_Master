<?php
require_once __DIR__ . '/../includes/config.php';
redirect_if_not_logged_in();
redirect_if_not_admin();

if (!isset($_GET['quiz_id'])) {
    header("Location: manage_quizzes.php");
    exit();
}

$quiz_id = $_GET['quiz_id'];
$quiz = $conn->prepare("SELECT * FROM quizzes WHERE quiz_id = ?");
$quiz->execute([$quiz_id]);
$quiz = $quiz->fetch(PDO::FETCH_ASSOC);

if (!$quiz) {
    header("Location: manage_quizzes.php");
    exit();
}

// Handle question deletion
if (isset($_GET['delete_question'])) {
    $question_id = $_GET['delete_question'];
    $stmt = $conn->prepare("DELETE FROM questions WHERE question_id = ?");
    $stmt->execute([$question_id]);
    $_SESSION['message'] = "Question deleted successfully.";
    header("Location: manage_questions.php?quiz_id=$quiz_id");
    exit();
}

// Handle option deletion
if (isset($_GET['delete_option'])) {
    $option_id = $_GET['delete_option'];
    $stmt = $conn->prepare("DELETE FROM options WHERE option_id = ?");
    $stmt->execute([$option_id]);
    $_SESSION['message'] = "Option deleted successfully.";
    header("Location: manage_questions.php?quiz_id=$quiz_id");
    exit();
}

$page_title = "Manage Questions - " . $quiz['title'];
include '../includes/header.php';

// Handle form submission for adding/editing questions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_question'])) {
        // Add new question
        $question_text = trim($_POST['question_text']);
        $question_type = $_POST['question_type'];
        $points = intval($_POST['points']);
        
        if (empty($question_text)) {
            $error = "Question text is required.";
        } else {
            $stmt = $conn->prepare("INSERT INTO questions (quiz_id, question_text, question_type, points) VALUES (?, ?, ?, ?)");
            $stmt->execute([$quiz_id, $question_text, $question_type, $points]);
            $question_id = $conn->lastInsertId();
            
            // Add options if multiple choice
            if ($question_type === 'multiple_choice') {
                foreach ($_POST['options'] as $index => $option_text) {
                    if (!empty(trim($option_text))) {
                        $is_correct = ($index == $_POST['correct_option']) ? 1 : 0;
                        $stmt = $conn->prepare("INSERT INTO options (question_id, option_text, is_correct) VALUES (?, ?, ?)");
                        $stmt->execute([$question_id, trim($option_text), $is_correct]);
                    }
                }
            }
            
            $_SESSION['message'] = "Question added successfully.";
            header("Location: manage_questions.php?quiz_id=$quiz_id");
            exit();
        }
    } elseif (isset($_POST['add_option'])) {
        // Add option to existing question
        $question_id = $_POST['question_id'];
        $option_text = trim($_POST['option_text']);
        $is_correct = isset($_POST['is_correct']) ? 1 : 0;
        
        if (empty($option_text)) {
            $error = "Option text is required.";
        } else {
            $stmt = $conn->prepare("INSERT INTO options (question_id, option_text, is_correct) VALUES (?, ?, ?)");
            $stmt->execute([$question_id, $option_text, $is_correct]);
            $_SESSION['message'] = "Option added successfully.";
            header("Location: manage_questions.php?quiz_id=$quiz_id");
            exit();
        }
    }
}

// Get all questions for this quiz
$questions = $conn->prepare("
    SELECT q.*, 
           (SELECT COUNT(*) FROM options WHERE question_id = q.question_id) as option_count
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
?>

<div class="container">
    <h1 class="mb-4">Manage Questions: <?php echo htmlspecialchars($quiz['title']); ?></h1>
    
    <?php if(isset($_SESSION['message'])): ?>
        <div class="alert alert-success"><?php echo $_SESSION['message']; unset($_SESSION['message']); ?></div>
    <?php endif; ?>
    
    <?php if(isset($error)): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <div class="card mb-4">
        <div class="card-header">
            Add New Question
        </div>
        <div class="card-body">
            <form method="POST">
                <div class="mb-3">
                    <label for="question_text" class="form-label">Question Text</label>
                    <textarea class="form-control" id="question_text" name="question_text" rows="3" required></textarea>
                </div>
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="question_type" class="form-label">Question Type</label>
                        <select class="form-select" id="question_type" name="question_type" required>
                            <option value="multiple_choice">Multiple Choice</option>
                            <option value="true_false">True/False</option>
                            <option value="short_answer">Short Answer</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label for="points" class="form-label">Points</label>
                        <input type="number" class="form-control" id="points" name="points" value="1" min="1" required>
                    </div>
                </div>
                
                <div id="multiple_choice_options">
                    <h5>Options</h5>
                    <div class="mb-3">
                        <label class="form-label">Option 1</label>
                        <div class="input-group">
                            <input type="text" class="form-control" name="options[]" required>
                            <div class="input-group-text">
                                <input class="form-check-input" type="radio" name="correct_option" value="0" required>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Option 2</label>
                        <div class="input-group">
                            <input type="text" class="form-control" name="options[]" required>
                            <div class="input-group-text">
                                <input class="form-check-input" type="radio" name="correct_option" value="1" required>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Option 3</label>
                        <div class="input-group">
                            <input type="text" class="form-control" name="options[]">
                            <div class="input-group-text">
                                <input class="form-check-input" type="radio" name="correct_option" value="2">
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Option 4</label>
                        <div class="input-group">
                            <input type="text" class="form-control" name="options[]">
                            <div class="input-group-text">
                                <input class="form-check-input" type="radio" name="correct_option" value="3">
                            </div>
                        </div>
                    </div>
                </div>
                
                <button type="submit" name="add_question" class="btn btn-primary">Add Question</button>
                <a href="manage_quizzes.php" class="btn btn-secondary">Back to Quizzes</a>
            </form>
        </div>
    </div>
    
    <div class="card">
        <div class="card-header">
            Questions for This Quiz
        </div>
        <div class="card-body">
            <?php if(empty($questions)): ?>
                <p>No questions added yet.</p>
            <?php else: ?>
                <?php foreach($questions as $question): ?>
                    <div class="card mb-3">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">Question #<?php echo $question['question_id']; ?> 
                                (<?php echo ucfirst(str_replace('_', ' ', $question['question_type'])); ?>, 
                                <?php echo $question['points']; ?> point<?php echo $question['points'] > 1 ? 's' : ''; ?>)
                            </h5>
                            <div>
                                <a href="?quiz_id=<?php echo $quiz_id; ?>&delete_question=<?php echo $question['question_id']; ?>" 
                                   class="btn btn-sm btn-danger" 
                                   onclick="return confirm('Are you sure you want to delete this question?')">
                                    <i class="fas fa-trash-alt"></i>
                                </a>
                            </div>
                        </div>
                        <div class="card-body">
                            <p><?php echo htmlspecialchars($question['question_text']); ?></p>
                            
                            <?php if($question['question_type'] === 'multiple_choice'): ?>
                                <h6>Options:</h6>
                                <ul class="list-group mb-3">
                                    <?php foreach($question['options'] as $option): ?>
                                        <li class="list-group-item d-flex justify-content-between align-items-center">
                                            <?php echo htmlspecialchars($option['option_text']); ?>
                                            <?php if($option['is_correct']): ?>
                                                <span class="badge bg-success">Correct</span>
                                            <?php endif; ?>
                                            <a href="?quiz_id=<?php echo $quiz_id; ?>&delete_option=<?php echo $option['option_id']; ?>" 
                                               class="btn btn-sm btn-outline-danger"
                                               onclick="return confirm('Are you sure you want to delete this option?')">
                                                <i class="fas fa-times"></i>
                                            </a>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                                
                                <form method="POST" class="mt-3">
                                    <input type="hidden" name="question_id" value="<?php echo $question['question_id']; ?>">
                                    <div class="input-group mb-3">
                                        <input type="text" class="form-control" name="option_text" placeholder="New option text">
                                        <div class="input-group-text">
                                            <input class="form-check-input" type="checkbox" name="is_correct">
                                            <label class="form-check-label ms-2">Correct?</label>
                                        </div>
                                        <button type="submit" name="add_option" class="btn btn-primary">Add Option</button>
                                    </div>
                                </form>
                            <?php elseif($question['question_type'] === 'true_false'): ?>
                                <p class="text-muted">True/False question - no options to manage.</p>
                            <?php else: ?>
                                <p class="text-muted">Short answer question - no options to manage.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
// Show/hide options based on question type
document.getElementById('question_type').addEventListener('change', function() {
    const optionsDiv = document.getElementById('multiple_choice_options');
    if (this.value === 'multiple_choice') {
        optionsDiv.style.display = 'block';
    } else {
        optionsDiv.style.display = 'none';
    }
});
</script>

<?php include '../includes/footer.php'; ?>