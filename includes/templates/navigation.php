<?php
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!-- Navigation -->
<nav class="main-nav">
    <div class="nav-container">
        <a href="index.php" class="nav-logo">
            <img src="images/logo.png" alt="Wichtlä.ch Logo" height="40">
        </a>
        
        <button class="nav-toggle" id="navToggle" aria-label="Menü öffnen" aria-expanded="false" aria-controls="navMenu">
            <span></span>
            <span></span>
            <span></span>
        </button>
        
        <ul class="nav-menu" id="navMenu">
            <li><a href="index.php" class="nav-link" <?php echo $current_page == 'index.php' ? 'aria-current="page"' : ''; ?>>Home</a></li>
            <li><a href="was-ist-wichteln.php" class="nav-link" <?php echo $current_page == 'was-ist-wichteln.php' ? 'aria-current="page"' : ''; ?>>Was ist Wichteln?</a></li>
            <li><a href="wichtel-ideen.php" class="nav-link" <?php echo $current_page == 'wichtel-ideen.php' ? 'aria-current="page"' : ''; ?>>Geschenkideen</a></li>
            <li><a href="firmenwichteln-tipps.php" class="nav-link" <?php echo $current_page == 'firmenwichteln-tipps.php' ? 'aria-current="page"' : ''; ?>>Firmenwichteln</a></li>
            <li><a href="faq.php" class="nav-link" <?php echo $current_page == 'faq.php' ? 'aria-current="page"' : ''; ?>>FAQ</a></li>
            <li><a href="ueber-uns.php" class="nav-link" <?php echo $current_page == 'ueber-uns.php' ? 'aria-current="page"' : ''; ?>>Über uns</a></li>
            <li><a href="create_group.php" class="nav-link nav-link-primary" <?php echo $current_page == 'create_group.php' ? 'aria-current="page"' : ''; ?>>Gruppe erstellen</a></li>
        </ul>
    </div>
</nav>

<script>
// Mobile Navigation Toggle
document.addEventListener('DOMContentLoaded', function() {
    const navToggle = document.getElementById('navToggle');
    const navMenu = document.getElementById('navMenu');
    
    if (navToggle && navMenu) {
        navToggle.addEventListener('click', function() {
            navToggle.classList.toggle('active');
            navMenu.classList.toggle('active');

            const isExpanded = navToggle.classList.contains('active');
            navToggle.setAttribute('aria-expanded', isExpanded);
            navToggle.setAttribute('aria-label', isExpanded ? 'Menü schließen' : 'Menü öffnen');
        });
        
        // Close menu when clicking outside
        document.addEventListener('click', function(event) {
            if (!navToggle.contains(event.target) && !navMenu.contains(event.target)) {
                navToggle.classList.remove('active');
                navMenu.classList.remove('active');
                navToggle.setAttribute('aria-expanded', 'false');
                navToggle.setAttribute('aria-label', 'Menü öffnen');
            }
        });
    }
});
</script>
