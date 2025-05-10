    </div> <!-- Close container from header -->
    <!-- <footer class="bg-dark text-white mt-5">
        <div class="container py-4">
            <div class="row">
                <div class="col-md-6">
                    <h5>About Quiz App</h5>
                    <p>An interactive platform for creating and taking quizzes.</p>
                </div>
                <div class="col-md-3">
                    <h5>Quick Links</h5>
                    <ul class="list-unstyled">
                        <li><a href="../index.php" class="text-white">Home</a></li>
                        <?php if(isset($_SESSION['user_id'])): ?>
                            <li><a href="../user/dashboard.php" class="text-white">Dashboard</a></li>
                        <?php else: ?>
                            <li><a href="../login.php" class="text-white">Login</a></li>
                        <?php endif; ?>
                    </ul>
                </div>
                <div class="col-md-3">
                    <h5>Contact</h5>
                    <ul class="list-unstyled">
                        <li><i class="fas fa-envelope"></i> support@quizapp.com</li>
                        <li><i class="fas fa-phone"></i> +1 (123) 456-7890</li>
                    </ul>
                </div>
            </div>
            <hr>
            <div class="text-center">
                <p>&copy; <?php echo date('Y'); ?> Quiz App. All rights reserved.</p>
            </div>
        </div>
    </footer> -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/script.js"></script>
</body>
</html>