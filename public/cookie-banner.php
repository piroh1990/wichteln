<!-- Cookie Banner -->
<div id="cookie-banner" class="cookie-banner" style="display: none;">
    <div class="cookie-banner-content">
        <span class="cookie-banner-text">🍪 Diese Website verwendet Cookies. <a href="datenschutz.php">Mehr erfahren</a></span>
        <button class="cookie-banner-close" onclick="acceptCookies()" aria-label="Schließen">×</button>
    </div>
</div>

<style>
.cookie-banner {
    position: fixed;
    bottom: 20px;
    left: 20px;
    background: rgba(43, 45, 66, 0.95);
    color: white;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.2);
    z-index: 9999;
    padding: 0.75rem 1rem;
    max-width: 400px;
    font-size: 0.85rem;
    animation: fadeIn 0.3s ease-out;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

.cookie-banner-content {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.cookie-banner-text {
    flex: 1;
    line-height: 1.4;
}

.cookie-banner-text a {
    color: #06ffa5;
    text-decoration: underline;
}

.cookie-banner-close {
    background: none;
    border: none;
    color: white;
    font-size: 1.5rem;
    line-height: 1;
    cursor: pointer;
    padding: 0;
    width: 24px;
    height: 24px;
    display: flex;
    align-items: center;
    justify-content: center;
    opacity: 0.7;
    transition: opacity 0.2s;
    flex-shrink: 0;
}

.cookie-banner-close:hover {
    opacity: 1;
}

@media (max-width: 768px) {
    .cookie-banner {
        left: 10px;
        right: 10px;
        max-width: none;
        bottom: 10px;
        font-size: 0.8rem;
    }
}
</style>

<script>
// Cookie Banner Logic
(function() {
    function getCookie(name) {
        const value = `; ${document.cookie}`;
        const parts = value.split(`; ${name}=`);
        if (parts.length === 2) return parts.pop().split(';').shift();
    }
    
    function setCookie(name, value, days) {
        const date = new Date();
        date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
        const expires = `expires=${date.toUTCString()}`;
        document.cookie = `${name}=${value};${expires};path=/;SameSite=Lax`;
    }
    
    if (!getCookie('cookie_consent')) {
        document.getElementById('cookie-banner').style.display = 'block';
    }
    
    window.acceptCookies = function() {
        setCookie('cookie_consent', 'accepted', 365);
        const banner = document.getElementById('cookie-banner');
        banner.style.opacity = '0';
        setTimeout(() => banner.style.display = 'none', 300);
    };
})();
</script>
