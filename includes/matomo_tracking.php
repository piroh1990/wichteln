<!-- Matomo -->
<?php
$configFile = __DIR__ . '/../config.php';
if (file_exists($configFile)) {
    require_once $configFile;
}
?>
<?php if (defined('MATOMO_URL') && defined('MATOMO_SITE_ID')): ?>
<script>
  var _paq = window._paq = window._paq || [];
  _paq.push(['trackPageView']);
  _paq.push(['enableLinkTracking']);
  (function() {
    var u="<?php echo MATOMO_URL; ?>";
    _paq.push(['setTrackerUrl', u+'matomo.php']);
    _paq.push(['setSiteId', '<?php echo MATOMO_SITE_ID; ?>']);
    var d=document, g=d.createElement('script'), s=d.getElementsByTagName('script')[0];
    g.async=true; g.src=u+'matomo.js'; s.parentNode.insertBefore(g,s);
  })();
</script>
<?php endif; ?>
<!-- End Matomo Code -->
