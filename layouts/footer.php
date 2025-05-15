<footer class="footer mt-auto py-4 bg-dark text-white">
    <div class="container">
        <div class="row gy-4">
            <div class="col-md-4 text-center text-md-start">
                <h5 class="mb-3"><i class="fas fa-comments me-2"></i><?php echo SITE_NAME; ?></h5>
                <p class="mb-3">A place to share and discuss ideas with our growing community.</p>
                <div class="social-links mb-3">
                    <a href="#" class="text-white me-2" title="Facebook"><i class="fab fa-facebook-f fa-lg"></i></a>
                    <a href="#" class="text-white me-2" title="Twitter"><i class="fab fa-twitter fa-lg"></i></a>
                    <a href="#" class="text-white me-2" title="Instagram"><i class="fab fa-instagram fa-lg"></i></a>
                    <a href="#" class="text-white" title="GitHub"><i class="fab fa-github fa-lg"></i></a>
                </div>
            </div>
            <div class="col-md-4 text-center text-md-end">
                <p class="small mb-3">&copy; <?php echo date('Y'); ?> <?php echo SITE_NAME; ?>. All rights reserved.</p>
                
                <!-- Quick Links Section -->
                <h6 class="mb-2">Quick Links</h6>
                <ul class="list-unstyled small">
                    <li class="mb-1"><a href="index.php" class="text-white text-decoration-none">Home</a></li>
                    <li class="mb-1"><a href="about.php" class="text-white text-decoration-none">About Us</a></li>
                    <li class="mb-1"><a href="privacy.php" class="text-white text-decoration-none">Privacy Policy</a></li>
                    <li><a href="contact.php" class="text-white text-decoration-none">Contact Us</a></li>
                </ul>
            </div>
        </div>
    </div>
</footer>

<!-- Back to top button -->
<a id="back-to-top" href="#" class="btn btn-primary btn-sm position-fixed rounded-circle" style="display: none; bottom: 20px; right: 20px; z-index: 99;">
    <i class="fas fa-arrow-up"></i>
</a>

<!-- Bootstrap JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>

<!-- Custom JavaScript -->
<script>
    // Initialize tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl)
    })
    
    // Back to top button
    var backToTopButton = document.getElementById("back-to-top");
    
    window.onscroll = function() {
        if (document.body.scrollTop > 20 || document.documentElement.scrollTop > 20) {
            backToTopButton.style.display = "block";
        } else {
            backToTopButton.style.display = "none";
        }
    };
    
    backToTopButton.addEventListener("click", function(e) {
        e.preventDefault();
        document.body.scrollTop = 0; // For Safari
        document.documentElement.scrollTop = 0; // For Chrome, Firefox, IE and Opera
    });
    
    // Function to show notifications
    function showNotification(message, type = 'success') {
        const container = document.getElementById('notification-container');
        const toast = document.createElement('div');
        toast.className = `toast align-items-center text-white bg-${type} border-0`;
        toast.setAttribute('role', 'alert');
        toast.setAttribute('aria-live', 'assertive');
        toast.setAttribute('aria-atomic', 'true');
        
        toast.innerHTML = `
            <div class="d-flex">
                <div class="toast-body">
                    ${message}
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        `;
        
        container.appendChild(toast);
        const bsToast = new bootstrap.Toast(toast);
        bsToast.show();
        
        // Remove toast after it's hidden
        toast.addEventListener('hidden.bs.toast', function() {
            container.removeChild(toast);
        });
    }
</script>
</body>
</html>