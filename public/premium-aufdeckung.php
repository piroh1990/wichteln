<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/templates/public_shell.php';

if (!headers_sent()) {
    header('X-Robots-Tag: noindex, nofollow');
    header('Referrer-Policy: no-referrer');
}

if (!premium_feature_enabled()) {
    http_response_code(404);
    header('Content-Type: text/html; charset=UTF-8');
    echo premium_unavailable_body();
    exit;
}

start_secure_session();
$admin_token = isset($_GET['token']) ? (string) $_GET['token'] : '';
$pdo = db_connect();
$stmt = $pdo->prepare('SELECT * FROM `groups` WHERE `admin_token` = ?');
$stmt->execute(array($admin_token));
$group = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$group) {
    i18n_boot_token(null);
    http_response_code(404);
    die(h(t('error.token')));
}

i18n_boot_token(i18n_row_language($group));
$license = premium_license_for_group($pdo, $group);
if (!$license || !premium_reveal_allowed($_SESSION, $group['id'])) {
    header('Location: /premium-hr.php?token=' . urlencode($admin_token));
    exit;
}

$people = $pdo->prepare('SELECT `id`, `name`, `assigned_to` FROM `participants` WHERE `group_id` = ? ORDER BY `id` ASC');
$people->execute(array((int) $group['id']));
$steps = premium_reveal_steps($people->fetchAll(PDO::FETCH_ASSOC));
token_page_open(t('premium.hr.live'));
$payload = json_encode($steps, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
if (!is_string($payload)) {
    $payload = '[]';
}
?>
    <div class="container app-page premium-reveal-page<?php echo premium_page_class($license); ?>"<?php echo premium_page_style_attr($license); ?>>
        <div class="premium-reveal-stage" id="premium-reveal" tabindex="0">
            <p class="premium-reveal-progress" id="premium-reveal-progress"></p>
            <div class="premium-reveal-card" id="premium-reveal-card">
                <p id="premium-reveal-giver"></p>
                <p class="premium-reveal-verb" id="premium-reveal-verb"></p>
                <p id="premium-reveal-receiver"></p>
            </div>
            <p class="premium-reveal-hint"><?php echo h(t('premium.reveal.key')); ?></p>
            <button type="button" class="button secondary" id="premium-reveal-full"><?php echo h(t('premium.reveal.fullscreen')); ?></button>
        </div>
    </div>
    <script>
        (function () {
            var steps = <?php echo $payload; ?>;
            var index = -1;
            var giver = document.getElementById('premium-reveal-giver');
            var verb = document.getElementById('premium-reveal-verb');
            var receiver = document.getElementById('premium-reveal-receiver');
            var progress = document.getElementById('premium-reveal-progress');
            var card = document.getElementById('premium-reveal-card');
            var stage = document.getElementById('premium-reveal');
            var ready = <?php echo json_encode(t('premium.reveal.ready'), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
            var gives = <?php echo json_encode(t('premium.reveal.gives'), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
            var done = <?php echo json_encode(t('premium.reveal.done'), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
            var incomplete = <?php echo json_encode(t('premium.reveal.incomplete'), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
            var progressLabel = <?php echo json_encode(t('premium.reveal.progress'), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;

            function show(step) {
                card.classList.remove('is-in');
                void card.offsetWidth;
                card.classList.add('is-in');
                if (!steps.length) {
                    giver.textContent = incomplete;
                    verb.textContent = '';
                    receiver.textContent = '';
                    progress.textContent = '';
                    return;
                }
                if (step < 0) {
                    giver.textContent = ready;
                    verb.textContent = '';
                    receiver.textContent = '';
                    progress.textContent = '';
                    return;
                }
                if (step >= steps.length) {
                    giver.textContent = done;
                    verb.textContent = '';
                    receiver.textContent = '';
                    progress.textContent = '';
                    return;
                }
                giver.textContent = steps[step].giver;
                verb.textContent = gives;
                receiver.textContent = steps[step].receiver;
                progress.textContent = progressLabel.replace('{current}', String(step + 1)).replace('{total}', String(steps.length));
            }

            function next() {
                if (index < steps.length) {
                    index += 1;
                    show(index);
                }
            }

            stage.addEventListener('click', next);
            document.addEventListener('keydown', function (event) {
                if (event.key === ' ' || event.key === 'ArrowRight' || event.key === 'Enter') {
                    event.preventDefault();
                    next();
                }
            });
            var full = document.getElementById('premium-reveal-full');
            if (full && stage.requestFullscreen) {
                full.addEventListener('click', function (event) {
                    event.stopPropagation();
                    stage.requestFullscreen();
                });
            }
            show(-1);
        }());
    </script>
<?php
token_page_close();
