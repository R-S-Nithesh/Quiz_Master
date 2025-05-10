// General DOM ready function
document.addEventListener('DOMContentLoaded', function() {
    // Enable Bootstrap tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // Confirm before delete actions
    document.querySelectorAll('.confirm-before-delete').forEach(link => {
        link.addEventListener('click', function(e) {
            if (!confirm('Are you sure you want to delete this item?')) {
                e.preventDefault();
            }
        });
    });
    
    // Add fade-in animation to all cards
    document.querySelectorAll('.card').forEach((card, index) => {
        card.classList.add('fade-in');
        card.style.animationDelay = `${index * 0.1}s`;
    });
    
    // Handle quiz form submission to prevent accidental submissions
    const quizForm = document.getElementById('quizForm');
    if (quizForm) {
        let formSubmitted = false;
        
        quizForm.addEventListener('submit', function(e) {
            if (formSubmitted) {
                e.preventDefault();
                return;
            }
            
            // Check if all required questions are answered
            const unanswered = [];
            document.querySelectorAll('[data-question-required]').forEach(q => {
                const name = q.getAttribute('name');
                if (!document.querySelector(`[name="${name}"]:checked`)) {
                    unanswered.push(name);
                }
            });
            
            if (unanswered.length > 0) {
                e.preventDefault();
                alert(`Please answer all questions. You have ${unanswered.length} unanswered questions.`);
                // Scroll to first unanswered question
                document.querySelector(`[name="${unanswered[0]}"]`).scrollIntoView({
                    behavior: 'smooth',
                    block: 'center'
                });
            } else {
                formSubmitted = true;
                // Disable submit button to prevent double submission
                const submitBtn = quizForm.querySelector('[type="submit"]');
                if (submitBtn) {
                    submitBtn.disabled = true;
                    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Submitting...';
                }
            }
        });
    }
    
    // Enhance radio and checkbox styling
    document.querySelectorAll('.form-check-input').forEach(input => {
        input.addEventListener('change', function() {
            const parent = this.closest('.list-group-item, .form-check-label');
            if (parent) {
                document.querySelectorAll('.list-group-item, .form-check-label').forEach(el => {
                    el.classList.remove('active');
                });
                if (this.checked) {
                    parent.classList.add('active');
                }
            }
        });
    });
});

// Timer functionality for quizzes
function startQuizTimer(duration, display) {
    let timer = duration, minutes, seconds;
    setInterval(function () {
        minutes = parseInt(timer / 60, 10);
        seconds = parseInt(timer % 60, 10);

        minutes = minutes < 10 ? "0" + minutes : minutes;
        seconds = seconds < 10 ? "0" + seconds : seconds;

        display.textContent = minutes + ":" + seconds;

        if (--timer < 0) {
            timer = duration;
        }
    }, 1000);
}

// Initialize timer if on quiz page
window.onload = function () {
    const timerDisplay = document.getElementById('timer');
    if (timerDisplay) {
        const timeLimit = parseInt(timerDisplay.dataset.timeLimit) || 1800; // Default 30 minutes
        startQuizTimer(timeLimit, timerDisplay);
    }
};