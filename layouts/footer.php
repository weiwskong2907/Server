<footer class="footer mt-auto py-4 bg-dark text-white">
    <div class="container">
        <div class="row">
            <div class="col-md-6 text-center text-md-start">
                <h5><?php echo SITE_NAME; ?></h5>
                <p class="mb-0">A place to share and discuss ideas.</p>
            </div>
            <div class="col-md-6 text-center text-md-end">
                <div class="mb-2">
                    <a href="#" class="text-white me-2"><i class="fab fa-facebook-f"></i></a>
                    <a href="#" class="text-white me-2"><i class="fab fa-twitter"></i></a>
                    <a href="#" class="text-white"><i class="fab fa-github"></i></a>
                </div>
                <span>&copy; <?php echo date('Y'); ?> <?php echo SITE_NAME; ?>. All rights reserved.</span>
            </div>
        </div>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Initialize tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl)
    })
</script>
</body>
</html>