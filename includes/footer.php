<?php
// includes/footer.php
$_prefix = (strpos($_SERVER['PHP_SELF'], '/dashboard/') !== false) ? '../' : '';
?>

<footer>
    <div class="container">
        <p>© 2026 Kimsreng Song. All rights reserved.</p>
    </div>
</footer>

<?php include __DIR__ . '/chat-widget.php'; ?>

<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="<?= $_prefix ?>assets/js/main.js?v=<?= time() ?>"></script>
    
</body>
</html>