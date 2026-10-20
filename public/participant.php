<?php
/**
 * participant.php
 *
 * Teilnehmerbereich für Wichtel-Gruppen.
 * Token in der URL hat Vorrang vor dem Cookie. Die Seite hat kein Sprachpräfix.
 */

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/templates/public_shell.php';

if (!headers_sent()) {
    header('X-Robots-Tag: noindex, nofollow');
    header('Referrer-Policy: no-referrer');
}

start_secure_session();

$pdo = db_connect();

function save_token_to_cookie($token) {
    $tokens = get_tokens_from_cookie();
    if (!preg_match('/^[a-zA-Z0-9]+$/', $token)) {
        return false;
    }
    if (!in_array($token, $tokens)) {
        $tokens[] = $token;
    }
    $tokens = array_slice($tokens, -COOKIE_MAX_TOKENS);
    $cookie_options = array(
        'expires' => time() + COOKIE_LIFETIME,
        'path' => '/',
        'domain' => '',
        'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
        'httponly' => true,
        'samesite' => 'Lax',
    );
    return setcookie(COOKIE_NAME, json_encode($tokens), $cookie_options);
}

function get_tokens_from_cookie() {
    if (!isset($_COOKIE[COOKIE_NAME])) {
        return array();
    }
    $tokens = json_decode($_COOKIE[COOKIE_NAME], true);
    if (!is_array($tokens)) {
        return array();
    }
    return array_filter($tokens, function ($token) {
        return preg_match('/^[a-zA-Z0-9]+$/', $token);
    });
}

function load_participants_from_tokens($pdo, $tokens) {
    if (empty($tokens)) {
        return array();
    }
    $placeholders = str_repeat('?,', count($tokens) - 1) . '?';
    $stmt = $pdo->prepare("SELECT p.*, g.name as group_name, g.is_drawn
                           FROM `participants` p
                           JOIN `groups` g ON p.group_id = g.id
                           WHERE p.participant_token IN ($placeholders)
                           ORDER BY g.name, p.name");
    $stmt->execute($tokens);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$participant_token = isset($_GET['token']) ? (string) $_GET['token'] : '';
$show_group_selector = false;
$participant = null;
$group = null;
$assigned = null;
$reveal_pairs = array();

if (!empty($participant_token)) {
    if (!preg_match('/^[a-zA-Z0-9]+$/', $participant_token)) {
        i18n_boot_token(null);
        die(h(t('error.token')));
    }
    $stmt = $pdo->prepare("SELECT * FROM `participants` WHERE `participant_token` = ?");
    $stmt->execute(array($participant_token));
    $participant = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$participant) {
        i18n_boot_token(null);
        die(h(t('error.token')));
    }
    save_token_to_cookie($participant_token);
} else {
    $saved_tokens = get_tokens_from_cookie();
    $participants = array();
    if (!empty($saved_tokens)) {
        $participants = load_participants_from_tokens($pdo, $saved_tokens);
        if (empty($participants)) {
            $cookie_options = array(
                'expires' => time() - 3600,
                'path' => '/',
                'domain' => '',
                'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
                'httponly' => true,
                'samesite' => 'Lax',
            );
            setcookie(COOKIE_NAME, '', $cookie_options);
        }
    }

    if (empty($participants)) {
        i18n_apply_explicit_choice();
        i18n_boot_token(null);
        token_page_open(t('meta.participant.missing'));
        ?>
    <div class="container app-page">
        <header class="app-hero">
            <p class="app-kicker"><?php echo h(t('participant.kicker')); ?></p>
            <h1><?php echo h(t('participant.missing_title')); ?></h1>
            <p class="app-hero-lead"><?php echo h(t('participant.missing_lead')); ?></p>
        </header>
        <div class="section-card waiting-card">
            <div class="waiting-icon" aria-hidden="true">🏠</div>
            <h2><?php echo h(t('participant.missing_next')); ?></h2>
            <div style="display: flex; gap: 1rem; justify-content: center; margin-top: 1.5rem; flex-wrap: wrap;">
                <a href="<?php echo h(i18n_href('index')); ?>" class="cta-button cta-button-secondary">
                    <span><?php echo h(t('participant.missing_home')); ?></span>
                </a>
                <a href="<?php echo h(i18n_href('create_group')); ?>" class="cta-button cta-button-primary">
                    <span><?php echo h(t('participant.missing_create')); ?></span>
                </a>
            </div>
        </div>
        <div class="section-card">
            <div class="section-card-header">
                <span class="section-icon" aria-hidden="true">💡</span>
                <h2><?php echo h(t('participant.missing_how')); ?></h2>
            </div>
            <ul>
                <li><?php echo h(t('participant.missing_invite')); ?></li>
                <li><?php echo h(t('participant.missing_mail')); ?></li>
                <li><?php echo h(t('participant.missing_lost')); ?></li>
            </ul>
        </div>
    </div>
        <?php
        token_page_close();
        exit;
    }

    if (isset($_POST['select_group']) && isset($_POST['selected_token'])) {
        if (!verify_csrf_token(isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '')) {
            abort_invalid_csrf();
        }
        $selected_token = $_POST['selected_token'];
        if (!preg_match('/^[a-zA-Z0-9]+$/', $selected_token) || !in_array($selected_token, $saved_tokens)) {
            i18n_boot_token(null);
            die(h(t('error.token')));
        }
        $participant_token = $selected_token;
        $stmt = $pdo->prepare("SELECT * FROM `participants` WHERE `participant_token` = ?");
        $stmt->execute(array($participant_token));
        $participant = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$participant) {
            i18n_boot_token(null);
            die(h(t('error.token')));
        }
    } elseif (count($participants) > 1) {
        $show_group_selector = true;
    } else {
        $participant = $participants[0];
        $participant_token = $participant['participant_token'];
    }
}

if ($participant) {
    $stmt = $pdo->prepare("SELECT * FROM `groups` WHERE `id` = ?");
    $stmt->execute(array($participant['group_id']));
    $group = $stmt->fetch(PDO::FETCH_ASSOC);

    i18n_apply_explicit_choice($pdo, 'participants', (int) $participant['id']);
    $stmt = $pdo->prepare("SELECT * FROM `participants` WHERE `id` = ?");
    $stmt->execute(array($participant['id']));
    $reloaded = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($reloaded) {
        $participant = $reloaded;
    }
    i18n_boot_token(i18n_row_language($participant));

    if ($group && $group['is_drawn'] && !empty($participant['assigned_to'])) {
        $stmt = $pdo->prepare("SELECT * FROM `participants` WHERE `id` = ?");
        $stmt->execute(array($participant['assigned_to']));
        $assigned = $stmt->fetch(PDO::FETCH_ASSOC);
    }

    if ($group && !empty($group['is_drawn']) && !empty($group['reveal_sent_at'])) {
        $stmt = $pdo->prepare("SELECT `id`, `name`, `assigned_to` FROM `participants` WHERE `group_id` = ?");
        $stmt->execute(array($group['id']));
        $reveal_pairs = build_reveal_pairs($stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    if (isset($_POST['update_wishlist'])) {
        if (!verify_csrf_token(isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '')) {
            abort_invalid_csrf();
        }
        $wishlist = normalize_wishlist_text(isset($_POST['wishlist']) ? $_POST['wishlist'] : '');
        $wishlist_length_error = limit_text_error($wishlist, 5000, t('field.wishlist'));
        $old_wishlist = isset($participant['wishlist']) ? $participant['wishlist'] : '';
        if ($wishlist_length_error !== '') {
            $wishlist_error = $wishlist_length_error;
        } else {
            $stmt = $pdo->prepare("UPDATE `participants` SET `wishlist` = ? WHERE `id` = ?");
            $stmt->execute(array($wishlist, $participant['id']));
            $wishlist_status = dispatch_wishlist_giver_mail(
                $pdo,
                $participant,
                !empty($group['is_drawn']),
                $old_wishlist,
                $wishlist
            );
            $stmt = $pdo->prepare("SELECT * FROM `participants` WHERE `participant_token` = ?");
            $stmt->execute(array($participant_token));
            $participant = $stmt->fetch(PDO::FETCH_ASSOC);
            $wishlist_success = wishlist_update_notice($wishlist_status);
        }
    }
}

if ($show_group_selector) {
    i18n_apply_explicit_choice();
    i18n_boot_token(null);
    token_page_open(t('meta.participant.choose'));
    ?>
    <div class="container app-page">
        <header class="app-hero">
            <p class="app-kicker"><?php echo h(t('participant.kicker')); ?></p>
            <h1><?php echo h(t('participant.choose_title')); ?></h1>
            <p class="app-hero-lead"><?php echo h(t('participant.choose_lead')); ?></p>
        </header>
        <div class="group-selector app-selector">
            <form method="POST" id="group-selector-form">
                <?php echo csrf_input(); ?>
                <input type="hidden" name="select_group" value="1">
                <?php foreach ($participants as $p): ?>
                <label class="group-card">
                    <input type="radio" name="selected_token" value="<?php echo h($p['participant_token']); ?>" required aria-label="<?php echo h(t('participant.choose_label', array('{group}' => $p['group_name'], '{name}' => $p['name']))); ?>">
                    <div class="group-card-content">
                        <div class="group-card-header">
                            <div>
                                <h2 class="group-name"><?php echo h($p['group_name']); ?></h2>
                                <p class="group-participant"><?php echo h(t('participant.as', array('{name}' => $p['name']))); ?></p>
                            </div>
                            <span class="group-status <?php echo $p['is_drawn'] ? 'status-drawn' : 'status-pending'; ?>">
                                <?php echo $p['is_drawn'] ? h(t('participant.status_drawn')) : h(t('participant.status_pending')); ?>
                            </span>
                        </div>
                    </div>
                </label>
                <?php endforeach; ?>
                <div class="submit-container">
                    <button type="submit" name="select_group" class="group-submit-button" id="submit-button" disabled><?php echo h(t('participant.choose_submit')); ?></button>
                </div>
            </form>
        </div>
    </div>
    <script>
        document.querySelectorAll('.group-card').forEach(function(card) {
            card.addEventListener('click', function() {
                var radio = this.querySelector('input[type="radio"]');
                radio.checked = true;
                var submitButton = document.getElementById('submit-button');
                submitButton.disabled = false;
                submitButton.classList.add('enabled');
            });
        });
        document.querySelectorAll('input[type="radio"]').forEach(function(radio) {
            radio.addEventListener('change', function() {
                var submitButton = document.getElementById('submit-button');
                submitButton.disabled = false;
                submitButton.classList.add('enabled');
            });
        });
        document.addEventListener('DOMContentLoaded', function() {
            var form = document.getElementById('group-selector-form');
            if (form) {
                handleFormSubmit(form, <?php echo json_encode(t('js.opening'), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>);
            }
        });
    </script>
    <?php
    token_page_close();
    exit;
}

$presented = mail_present_group($group, i18n_current());
$premium_license = premium_license_for_group($pdo, $group);
$calendar_url = !empty($group['gift_exchange_date']) ? gift_ics_url($group, i18n_current()) : '';
$personal_url = get_display_url('/participant.php?token=' . urlencode($participant_token));
token_page_open(t('meta.participant.title', array('{name}' => $participant['name'])));
?>
    <div class="container app-page<?php echo premium_page_class($premium_license); ?>"<?php echo premium_page_style_attr($premium_license); ?>>
        <header class="app-hero">
            <p class="app-kicker"><?php echo h(t('participant.kicker')); ?></p>
            <h1><?php echo h(t('participant.welcome', array('{name}' => $participant['name']))); ?></h1>
            <p class="app-hero-lead"><?php echo h(t('participant.group', array('{group}' => $group['name']))); ?></p>
            <?php echo premium_hero_extras_html($premium_license); ?>
        </header>

        <?php if (empty($participant['email'])): ?>
            <div class="notification warning" role="alert">
                <span aria-hidden="true">⚠️</span>
                <div><?php echo h(t('participant.no_email')); ?></div>
            </div>
        <?php endif; ?>

        <?php if (isset($wishlist_error)): ?>
            <div class="notification error" role="alert" aria-live="assertive"><?php echo h($wishlist_error); ?></div>
        <?php endif; ?>
        <?php if (isset($wishlist_success)): ?>
            <div class="notification success" role="status" aria-live="polite">
                <span aria-hidden="true">✓</span> <?php echo h($wishlist_success); ?>
            </div>
        <?php endif; ?>

        <?php if ($group['is_drawn']): ?>
            <div class="partner-reveal-card">
                <?php if ($assigned): ?>
                    <div class="partner-name-container">
                        <p class="partner-kicker"><span aria-hidden="true">🎁</span> <?php echo h(t('participant.you_give')); ?></p>
                        <p class="partner-name"><?php echo h($assigned['name']); ?></p>
                        <p class="partner-spotlight-note"><?php echo h(t('participant.keep_secret')); ?></p>
                    </div>
                    <div class="partner-reveal-body">
                    <?php if (!empty($assigned['wishlist'])): ?>
                        <div class="partner-wishlist-section">
                            <div class="wishlist-header">
                                <h3 class="wishlist-heading">
                                    <span class="wishlist-icon" aria-hidden="true">📝</span>
                                    <?php echo h(t('participant.wishlist_of', array('{name}' => $assigned['name']))); ?>
                                </h3>
                                <button type="button" class="button secondary small copy-button" onclick="copyToClipboard('partner-wishlist', <?php echo json_encode(t('js.wishlist_copied'), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>)" aria-label="<?php echo h(t('participant.copy_wishlist', array('{name}' => $assigned['name']))); ?>">
                                    <span aria-hidden="true">📋</span> <?php echo h(t('participant.copy')); ?>
                                </button>
                            </div>
                            <?php
                            $affiliate_html = affiliate_of_participant_html($group, $assigned['wishlist']);
                            $affiliate_list = strpos($affiliate_html, 'affiliate-wish-list') !== false;
                            ?>
                            <div class="wishlist-display" id="partner-wishlist"<?php echo $affiliate_list ? ' data-url="' . h($assigned['wishlist']) . '"' : ''; ?>>
                                <?php if ($affiliate_list): ?>
                                    <?php echo $affiliate_html; ?>
                                <?php else: ?>
                                    <p><?php echo nl2br(h($assigned['wishlist'])); ?></p>
                                <?php endif; ?>
                            </div>
                            <?php if ($affiliate_list): ?>
                                <?php echo affiliate_of_note_html(); ?>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <?php $gift_html = affiliate_of_participant_html($group, ''); ?>
                        <div class="empty-wishlist">
                            <span class="empty-wishlist-icon" aria-hidden="true">📋</span>
                            <p><?php echo h(t('participant.no_wishlist', array('{name}' => $assigned['name']))); ?></p>
                            <?php echo $gift_html; ?>
                        </div>
                        <?php if ($gift_html !== ''): ?>
                            <?php echo affiliate_of_note_html('one'); ?>
                        <?php endif; ?>
                    <?php endif; ?>
                    </div>
                <?php else: ?>
                    <div class="notification error" role="alert" aria-live="assertive">
                        <span aria-hidden="true">⚠️</span> <?php echo h(t('participant.partner_missing')); ?>
                    </div>
                <?php endif; ?>
            </div>

            <?php if (!empty($reveal_pairs)): ?>
                <?php $reveal_sent_label = format_reveal_sent_at($group['reveal_sent_at']); ?>
                <section class="section-card group-reveal" aria-labelledby="reveal-list-heading">
                    <div class="section-card-header">
                        <span class="section-icon" aria-hidden="true">✨</span>
                        <h2 id="reveal-list-heading" class="group-reveal-title"><?php echo h(t('participant.reveal')); ?></h2>
                    </div>
                    <p class="section-description">
                        <?php if ($reveal_sent_label !== ''): ?>
                            <?php echo h(t('participant.reveal_sent', array('{when}' => $reveal_sent_label))); ?>
                        <?php else: ?>
                            <?php echo h(t('participant.reveal_sent_plain')); ?>
                        <?php endif; ?>
                        <?php echo h(t('participant.reveal_legend')); ?>
                    </p>
                    <ul class="reveal-pair-list">
                        <?php foreach ($reveal_pairs as $pair): ?>
                            <?php $is_you = ((string) $pair['giver_id'] === (string) $participant['id']); ?>
                            <li class="reveal-pair<?php echo $is_you ? ' is-you' : ''; ?>">
                                <span class="reveal-pair-person">
                                    <span class="reveal-pair-role"><?php echo h($is_you ? t('participant.giver_you') : t('participant.giver')); ?></span>
                                    <span class="reveal-pair-name"><?php echo h($pair['giver_name']); ?></span>
                                </span>
                                <span class="reveal-pair-arrow" aria-hidden="true">→</span>
                                <span class="reveal-pair-person">
                                    <span class="reveal-pair-role"><?php echo h(t('participant.receiver')); ?></span>
                                    <span class="reveal-pair-name"><?php echo h($pair['receiver_name']); ?></span>
                                </span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </section>
            <?php endif; ?>

            <?php if (!empty($group['reveal_sent_at'])): ?>
                <?php echo support_card_html('reveal'); ?>
            <?php endif; ?>
        <?php else: ?>
            <div class="section-card waiting-card">
                <div class="waiting-icon" aria-hidden="true">⏳</div>
                <h2><?php echo h(t('participant.waiting_title')); ?></h2>
                <p><?php echo h(t('participant.waiting')); ?></p>
            </div>
        <?php endif; ?>

        <div class="section-card">
            <div class="section-card-header">
                <span class="section-icon" aria-hidden="true">📝</span>
                <h2><?php echo h(t('participant.your_wishlist')); ?></h2>
            </div>
            <?php if (!$group['is_drawn']): ?>
                <p class="section-description"><?php echo h(t('participant.wishlist_before')); ?></p>
            <?php else: ?>
                <p class="section-description"><?php echo h(t('participant.wishlist_after')); ?></p>
            <?php endif; ?>
            <form method="POST" class="wishlist-form">
                <?php echo csrf_input(); ?>
                <div class="form-group">
                    <label for="wishlist" class="form-label">
                        <span><?php echo h(t('participant.wishes')); ?></span>
                        <span id="wishlist-hint" class="form-hint"><?php echo h(t('participant.wishes_hint')); ?></span>
                    </label>
                    <textarea id="wishlist" name="wishlist" rows="6" class="form-textarea" aria-describedby="wishlist-hint" placeholder="<?php echo h(t('participant.wishes_ph')); ?>"><?php echo h(isset($participant['wishlist']) ? $participant['wishlist'] : ''); ?></textarea>
                </div>
                <input type="hidden" name="update_wishlist" value="1">
                <button type="submit" class="button primary" aria-label="<?php echo h(t('participant.save_wishlist')); ?>">
                    <span aria-hidden="true">💾</span>
                    <?php echo h(t('participant.save_wishlist')); ?>
                </button>
            </form>
        </div>

        <div class="section-card">
            <div class="section-card-header">
                <span class="section-icon" aria-hidden="true">ℹ️</span>
                <h2><?php echo h(t('participant.details')); ?></h2>
            </div>
            <div class="group-info-grid">
                <div class="group-info-item">
                    <span class="info-label"><span aria-hidden="true">💰</span> <?php echo h(t('participant.budget')); ?></span>
                    <span class="info-value"><?php echo h($presented['budget']); ?></span>
                </div>
                <div class="group-info-item">
                    <span class="info-label"><span aria-hidden="true">📅</span> <?php echo h(t('participant.date')); ?></span>
                    <span class="info-value"><?php echo h($presented['gift_date']); ?></span>
                    <?php if ($calendar_url !== ''): ?>
                        <a class="button secondary small calendar-link" href="<?php echo h($calendar_url); ?>"><?php echo h(t('mail.calendar')); ?></a>
                    <?php endif; ?>
                </div>
                <?php if (!empty($group['description'])): ?>
                <div class="group-info-item full-width">
                    <span class="info-label"><span aria-hidden="true">📄</span> <?php echo h(t('participant.description')); ?></span>
                    <span class="info-value"><?php echo h($group['description']); ?></span>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="section-card">
            <div class="section-card-header">
                <span class="section-icon" aria-hidden="true">🔗</span>
                <h2><?php echo h(t('participant.your_link')); ?></h2>
            </div>
            <p class="section-description"><?php echo h(t('participant.your_link_help')); ?></p>
            <div class="link-box">
                <pre id="participant-link" class="link-display" data-url="<?php echo h($personal_url); ?>"><?php echo h($personal_url); ?></pre>
                <div class="link-actions">
                    <button type="button" class="button secondary copy-btn" onclick="copyToClipboard('participant-link')" aria-label="<?php echo h(t('participant.copy_link_label')); ?>">
                        <span aria-hidden="true">📋</span>
                        <?php echo h(t('participant.copy_link')); ?>
                    </button>
                </div>
            </div>
        </div>

        <?php
        $saved_tokens = get_tokens_from_cookie();
        if (count($saved_tokens) > 1):
        ?>
        <div class="multi-group-nav">
            <p class="multi-group-text">
                <span class="multi-group-icon" aria-hidden="true">🎁</span>
                <?php echo h(t('participant.multi')); ?>
            </p>
            <a href="/participant.php" class="button secondary">
                <span aria-hidden="true">🔄</span> <?php echo h(t('participant.switch')); ?>
            </a>
        </div>
        <?php endif; ?>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            handleFormSubmit(document.querySelector('.wishlist-form'), <?php echo json_encode(t('js.saving'), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>);
        });
    </script>
<?php token_page_close(); ?>
