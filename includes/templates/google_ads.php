<?php
/**
 * Google Ads Template
 *
 * @var int $position The position number (1, 2, or 3)
 * @var string $label The label for the ad position (e.g., "Position 1: Nach Wichtelpartner")
 */

if (!isset($position) || !isset($label)) {
    return;
}

$show_const = "GOOGLE_ADS_SHOW_OPTION$position";
$slot_const = "GOOGLE_ADS_SLOT_OPTION$position";

if (defined('GOOGLE_ADS_ENABLED') && GOOGLE_ADS_ENABLED &&
    defined($show_const) && constant($show_const)):
    $is_testing = defined('GOOGLE_ADS_TESTING') && GOOGLE_ADS_TESTING;
?>
<div class="ad-container">
    <div class="ad-label"><?php echo $is_testing ? "Test-Anzeige (Position $position)" : 'Anzeige'; ?></div>
    <?php if ($is_testing): ?>
        <div class="ad-test-placeholder">
            📊 Google Ad Placeholder<br>
            <?php echo htmlspecialchars($label); ?><br>
            Responsive Display Ad
        </div>
    <?php else: ?>
        <script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=<?php echo htmlspecialchars(GOOGLE_ADS_CLIENT); ?>"
             crossorigin="anonymous"></script>
        <ins class="adsbygoogle"
             style="display:block"
             data-ad-client="<?php echo htmlspecialchars(GOOGLE_ADS_CLIENT); ?>"
             data-ad-slot="<?php echo htmlspecialchars(constant($slot_const)); ?>"
             data-ad-format="auto"
             data-full-width-responsive="true"></ins>
        <script>
             (adsbygoogle = window.adsbygoogle || []).push({});
        </script>
    <?php endif; ?>
</div>
<?php endif; ?>
