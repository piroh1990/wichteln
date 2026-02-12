<!-- Navigation -->
<nav class="main-nav">
    <div class="nav-container">
        <a href="index.php" class="nav-logo">
            <img src="images/logo.png" alt="Wichtlä.ch Logo" height="40">
        </a>
        
        <button class="nav-toggle" id="navToggle" aria-label="Menü öffnen">
            <span></span>
            <span></span>
            <span></span>
        </button>
        
        <ul class="nav-menu" id="navMenu">
            <li><a href="index.php" class="nav-link">Home</a></li>
            <li><a href="was-ist-wichteln.php" class="nav-link">Was ist Wichteln?</a></li>
            <li><a href="wichtel-ideen.php" class="nav-link">Geschenkideen</a></li>
            <li><a href="firmenwichteln-tipps.php" class="nav-link">Firmenwichteln</a></li>
            <li><a href="faq.php" class="nav-link">FAQ</a></li>
            <li><a href="create_group.php" class="nav-link nav-link-primary">Gruppe erstellen</a></li>
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
        });
        
        // Close menu when clicking outside
        document.addEventListener('click', function(event) {
            if (!navToggle.contains(event.target) && !navMenu.contains(event.target)) {
                navToggle.classList.remove('active');
                navMenu.classList.remove('active');
            }
        });
    }
});
</script>
