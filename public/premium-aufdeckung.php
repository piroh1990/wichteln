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
$brand = premium_brand_state($license);
$logo_alt = isset($license['company_name']) ? trim((string) $license['company_name']) : '';
token_page_open(t('premium.hr.live'));
$payload = json_for_script($steps);
if ($payload === 'null' || $payload === '') {
    $payload = '[]';
}
$fullscreen_label = t('premium.reveal.fullscreen');
?>
    <div class="container app-page premium-reveal-page<?php echo premium_page_class($license); ?>"<?php echo premium_page_style_attr($license); ?>>
        <div class="premium-reveal-stage" id="premium-reveal" tabindex="0"<?php echo premium_page_style_attr($license); ?>>
            <button type="button" class="premium-reveal-full" id="premium-reveal-full" aria-label="<?php echo h($fullscreen_label); ?>" title="<?php echo h($fullscreen_label); ?>">
                <svg class="premium-reveal-icon premium-reveal-icon-enter" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                    <path fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" d="M9 4H4v5M4 4l5 5M15 4h5v5M20 4l-5 5M9 20H4v-5M4 20l5-5M15 20h5v-5M20 20l-5-5"/>
                </svg>
                <svg class="premium-reveal-icon premium-reveal-icon-exit" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                    <path fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" d="M4 9h5V4M9 9L4 4M20 9h-5V4M15 9l5-5M4 15h5v5M9 15l-5 5M20 15h-5v5M15 15l5 5"/>
                </svg>
            </button>
            <div class="premium-reveal-frame">
            <?php if ($brand['logo'] !== ''): ?>
            <img class="premium-reveal-logo" src="<?php echo h($brand['logo']); ?>" alt="<?php echo h($logo_alt); ?>">
            <?php endif; ?>
            <p class="premium-reveal-progress" id="premium-reveal-progress"></p>
            <div class="premium-reveal-card" id="premium-reveal-card">
                <div class="premium-reveal-ribbon" aria-hidden="true"></div>
                <p class="premium-reveal-giver" id="premium-reveal-giver"></p>
                <div class="premium-reveal-gift" id="premium-reveal-gift" aria-hidden="true">
                    <span class="premium-reveal-lid"></span>
                    <span class="premium-reveal-bow"></span>
                </div>
                <p class="premium-reveal-verb" id="premium-reveal-verb"></p>
                <p class="premium-reveal-receiver" id="premium-reveal-receiver" aria-live="polite"></p>
                <div class="premium-reveal-burst" aria-hidden="true"><i></i><i></i></div>
            </div>
            <p class="premium-reveal-hint"><?php echo h(t('premium.reveal.key')); ?></p>
            </div>
        </div>
    </div>
    <script>
        (function () {
            var steps = <?php echo $payload; ?>;
            var index = -1;
            var timer = 0;
            var giver = document.getElementById('premium-reveal-giver');
            var verb = document.getElementById('premium-reveal-verb');
            var receiver = document.getElementById('premium-reveal-receiver');
            var progress = document.getElementById('premium-reveal-progress');
            var card = document.getElementById('premium-reveal-card');
            var stage = document.getElementById('premium-reveal');
            var full = document.getElementById('premium-reveal-full');
            var ready = <?php echo json_for_script(t('premium.reveal.ready')); ?>;
            var gives = <?php echo json_for_script(t('premium.reveal.gives')); ?>;
            var done = <?php echo json_for_script(t('premium.reveal.done')); ?>;
            var incomplete = <?php echo json_for_script(t('premium.reveal.incomplete')); ?>;
            var progressLabel = <?php echo json_for_script(t('premium.reveal.progress')); ?>;
            var reduceMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

            function clearTimer() {
                if (timer) {
                    window.clearTimeout(timer);
                    timer = 0;
                }
            }

            function rest(message) {
                clearTimer();
                giver.textContent = message;
                verb.textContent = '';
                receiver.textContent = '';
                progress.textContent = '';
                card.classList.remove('is-in', 'is-giver', 'is-open');
                card.classList.add('is-rest');
                window.requestAnimationFrame(function () {
                    card.classList.add('is-in');
                });
            }

            function show(step) {
                if (!steps.length) {
                    rest(incomplete);
                    return;
                }
                if (step < 0) {
                    rest(ready);
                    return;
                }
                if (step >= steps.length) {
                    rest(done);
                    return;
                }
                clearTimer();
                giver.textContent = steps[step].giver;
                verb.textContent = gives;
                receiver.textContent = steps[step].receiver;
                progress.textContent = progressLabel.replace('{current}', String(step + 1)).replace('{total}', String(steps.length));
                card.classList.remove('is-in', 'is-rest', 'is-open', 'is-giver');
                void card.offsetWidth;
                card.classList.add('is-in', 'is-giver');
                if (reduceMotion) {
                    card.classList.add('is-open');
                    return;
                }
                timer = window.setTimeout(function () {
                    card.classList.add('is-open');
                }, 1100);
            }

            function next() {
                if (index < steps.length) {
                    index += 1;
                    show(index);
                }
            }

            function prev() {
                if (index > -1) {
                    index -= 1;
                    show(index);
                }
            }

            function onFull(target) {
                return full && (target === full || (full.contains && full.contains(target)));
            }

            stage.addEventListener('click', function (event) {
                if (onFull(event.target)) {
                    return;
                }
                next();
            });
            document.addEventListener('keydown', function (event) {
                if (event.key === 'ArrowLeft') {
                    event.preventDefault();
                    prev();
                    return;
                }
                if (event.key === ' ' || event.key === 'ArrowRight' || event.key === 'Enter') {
                    if (onFull(event.target) && (event.key === ' ' || event.key === 'Enter')) {
                        return;
                    }
                    event.preventDefault();
                    next();
                }
            });
            function fullscreenElement() {
                return document.fullscreenElement || document.webkitFullscreenElement || null;
            }

            function syncFullscreenIcon() {
                if (fullscreenElement() === stage) {
                    stage.classList.add('is-fullscreen');
                } else {
                    stage.classList.remove('is-fullscreen');
                }
            }

            if (full) {
                var canFull = stage.requestFullscreen || stage.webkitRequestFullscreen;
                if (!canFull) {
                    full.hidden = true;
                } else {
                    full.addEventListener('click', function (event) {
                        event.stopPropagation();
                        var active = fullscreenElement();
                        if (active) {
                            if (document.exitFullscreen) {
                                document.exitFullscreen();
                            } else if (document.webkitExitFullscreen) {
                                document.webkitExitFullscreen();
                            }
                            return;
                        }
                        if (stage.requestFullscreen) {
                            stage.requestFullscreen();
                        } else if (stage.webkitRequestFullscreen) {
                            stage.webkitRequestFullscreen();
                        }
                    });
                    document.addEventListener('fullscreenchange', syncFullscreenIcon);
                    document.addEventListener('webkitfullscreenchange', syncFullscreenIcon);
                }
            }
            show(-1);
            if (stage.focus) {
                stage.focus();
            }
        }());
    </script>
<?php
token_page_close();
