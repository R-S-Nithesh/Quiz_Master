<?php
require_once __DIR__ . '/../includes/config.php';
redirect_if_not_logged_in();
redirect_if_not_admin();

// Handle quiz deletion
if (isset($_GET['delete'])) {
    $quiz_id = $_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM quizzes WHERE quiz_id = ?");
    $stmt->execute([$quiz_id]);
    $_SESSION['message'] = "Quiz deleted successfully.";
    header("Location: manage_quizzes.php");
    exit();
}

$page_title = "Manage Quizzes";
include '../includes/header.php';

// Handle form submission for adding/editing quizzes
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $time_limit = intval($_POST['time_limit']);
    $quiz_id = isset($_POST['quiz_id']) ? intval($_POST['quiz_id']) : 0;
    
    if (empty($title)) {
        $error = "Title is required.";
    } else {
        if ($quiz_id > 0) {
            // Update existing quiz
            $stmt = $conn->prepare("UPDATE quizzes SET title = ?, description = ?, time_limit = ? WHERE quiz_id = ?");
            $stmt->execute([$title, $description, $time_limit, $quiz_id]);
            $message = "Quiz updated successfully.";
        } else {
            // Insert new quiz
            $stmt = $conn->prepare("INSERT INTO quizzes (title, description, time_limit, created_by) VALUES (?, ?, ?, ?)");
            $stmt->execute([$title, $description, $time_limit, $_SESSION['user_id']]);
            $message = "Quiz added successfully.";
        }
        
        $_SESSION['message'] = $message;
        header("Location: manage_quizzes.php");
        exit();
    }
}

// Get all quizzes
$quizzes = $conn->query("SELECT * FROM quizzes ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);

// Get quiz for editing
$edit_quiz = null;
if (isset($_GET['edit'])) {
    $quiz_id = $_GET['edit'];
    $stmt = $conn->prepare("SELECT * FROM quizzes WHERE quiz_id = ?");
    $stmt->execute([$quiz_id]);
    $edit_quiz = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>

<div class="container">
    <h1 class="mb-4">Manage Quizzes</h1>
    
    <?php if(isset($_SESSION['message'])): ?>
        <div class="alert alert-success"><?php echo $_SESSION['message']; unset($_SESSION['message']); ?></div>
    <?php endif; ?>
    
    <?php if(isset($error)): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <div class="card mb-4">
        <div class="card-header">
            <?php echo $edit_quiz ? 'Edit Quiz' : 'Add New Quiz'; ?>
        </div>
        <div class="card-body">
            <form method="POST">
                <input type="hidden" name="quiz_id" value="<?php echo $edit_quiz ? $edit_quiz['quiz_id'] : ''; ?>">
                <div class="mb-3">
                    <label for="title" class="form-label">Title</label>
                    <input type="text" class="form-control" id="title" name="title" 
                           value="<?php echo $edit_quiz ? htmlspecialchars($edit_quiz['title']) : ''; ?>" required>
                </div>
                <div class="mb-3">
                    <label for="description" class="form-label">Description</label>
                    <textarea class="form-control" id="description" name="description" rows="3"><?php 
                        echo $edit_quiz ? htmlspecialchars($edit_quiz['description']) : ''; 
                    ?></textarea>
                </div>
                <div class="mb-3">
                    <label for="time_limit" class="form-label">Time Limit (minutes)</label>
                    <input type="number" class="form-control" id="time_limit" name="time_limit" 
                           value="<?php echo $edit_quiz ? $edit_quiz['time_limit'] : '30'; ?>" min="1" required>
                </div>
                <button type="submit" class="btn btn-primary">
                    <?php echo $edit_quiz ? 'Update Quiz' : 'Add Quiz'; ?>
                </button>
                <?php if($edit_quiz): ?>
                    <a href="manage_quizzes.php" class="btn btn-secondary">Cancel</a>
                <?php endif; ?>
            </form>
        </div>
    </div>
    
    <div class="card">
        <div class="card-header">
            All Quizzes
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Description</th>
                            <th>Time Limit</th>
                            <th>Created At</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($quizzes as $quiz): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($quiz['title']); ?></td>
                                <td><?php echo htmlspecialchars($quiz['description']); ?></td>
                                <td><?php echo $quiz['time_limit']; ?> mins</td>
                                <td><?php echo date('M d, Y', strtotime($quiz['created_at'])); ?></td>
                                <td>
                                    <a href="manage_questions.php?quiz_id=<?php echo $quiz['quiz_id']; ?>" 
                                       class="btn btn-sm btn-info" title="Manage Questions">
                                        <i class="fas fa-question-circle"></i>
                                    </a>
                                    <a href="manage_quizzes.php?edit=<?php echo $quiz['quiz_id']; ?>" 
                                       class="btn btn-sm btn-warning" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="manage_quizzes.php?delete=<?php echo $quiz['quiz_id']; ?>" 
                                       class="btn btn-sm btn-danger" title="Delete"
                                       onclick="return confirm('Are you sure you want to delete this quiz?')">
                                        <i class="fas fa-trash-alt"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>