    </div> <!-- content -->
</div> <!-- layout -->

<div class="sidebar-overlay" id="sidebarOverlay"></div>

<footer class="main-footer">
    © <?php echo date("Y"); ?> Vigyaan Institute | Admin Panel
</footer>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const mobileMenuBtn = document.getElementById('mobileMenuBtn');
    const sidebar = document.querySelector('.sidebar');
    const overlay = document.getElementById('sidebarOverlay');

    if (mobileMenuBtn && sidebar && overlay) {
        function toggleMenu() {
            sidebar.classList.toggle('open');
            overlay.classList.toggle('active');
        }
        mobileMenuBtn.addEventListener('click', toggleMenu);
        overlay.addEventListener('click', toggleMenu);
    }
});
</script>

</body>
</html>