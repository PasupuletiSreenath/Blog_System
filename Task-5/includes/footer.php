<?php if ($isLoggedIn): ?>
        </div> <!-- end container-fluid -->
        
        <!-- Footer for App -->
        <footer class="bg-glass border-top text-muted text-center py-4 mt-auto">
            <div class="container-fluid">
                <p class="mb-0 fw-medium">&copy; <?php echo date('Y'); ?> The Logbook. All rights reserved.</p>
            </div>
        </footer>
    </div> <!-- end page-content-wrapper -->
</div> <!-- end wrapper -->

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<!-- Sidebar Toggle Script -->
<script>
    document.addEventListener("DOMContentLoaded", function() {
        const toggleButton = document.getElementById("menu-toggle");
        if (toggleButton) {
            toggleButton.addEventListener("click", function(e) {
                e.preventDefault();
                document.getElementById("wrapper").classList.toggle("toggled");
            });
        }
    });
</script>
</body>
</html>
<?php else: ?>
    </div> <!-- end container -->
    
    <!-- Footer for Auth -->
    <footer class="bg-glass border-top text-muted text-center py-4 mt-auto">
        <div class="container">
            <p class="mb-0 fw-medium">&copy; <?php echo date('Y'); ?> The Logbook. All rights reserved.</p>
        </div>
    </footer>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php endif; ?>
