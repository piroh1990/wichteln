<?php
/**
 * Google-Ads-Slot.
 *
 * Erwartet $position (1 oder 2). $label beschreibt die Stelle nur im Testmodus.
 * Sichtbare Kennzeichnung ist immer «Anzeige».
 *
 * Testmodus (GOOGLE_ADS_TESTING): Platzhalter, kein adsbygoogle.js.
 * Produktiv: nur das <ins>-Markup, und nur wenn die Google-CMP mit echter
 * ca-pub-ID konfiguriert ist. Das Skript lädt includes/templates/matomo_tracking.php
 * erst nach Einwilligung. Ohne CMP erscheint hier nichts.
 */

$configFile = __DIR__ . '/../config.php';
if (file_exists($configFile)) {
    require_once $configFile;
}
require_once __DIR__ . '/../consent.php';

if (!isset($position)) {
    return;
}

$position = (int) $position;
if (!google_ads_slot_active($position)) {
    return;
}

$is_testing = google_ads_testing();
$cmp_ready = google_cmp_is_configured();

if (!$is_testing && !$cmp_ready) {
    return;
}

$slot_const = 'GOOGLE_ADS_SLOT_OPTION' . $position;
$slot_id = defined($slot_const) ? (string) constant($slot_const) : '';
$place_label = isset($label) ? (string) $label : '';
?>
<aside class="public-ad-slot" aria-label="Anzeige" data-ad-position="<?php echo $position; ?>">
    <div class="ad-container">
        <div class="ad-label">Anzeige</div>
        <?php if ($is_testing): ?>
            <div class="ad-test-placeholder">
                Anzeige (Testmodus)<br>
                <?php if ($place_label !== ''): ?>
                    <?php echo htmlspecialchars($place_label, ENT_QUOTES, 'UTF-8'); ?><br>
                <?php endif; ?>
                Kein AdSense-Skript
            </div>
        <?php else: ?>
            <ins class="adsbygoogle"
                 style="display:block"
                 data-ad-client="<?php echo htmlspecialchars(GOOGLE_ADS_CLIENT, ENT_QUOTES, 'UTF-8'); ?>"
                 data-ad-slot="<?php echo htmlspecialchars($slot_id, ENT_QUOTES, 'UTF-8'); ?>"
                 data-ad-format="auto"
                 data-full-width-responsive="true"></ins>
        <?php endif; ?>
    </div>
</aside>
<?php
