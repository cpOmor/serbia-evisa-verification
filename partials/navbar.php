<?php
if (!isset($activeNav)) $activeNav = '';
if (!function_exists('sev_auth_is_logged_in')) require_once __DIR__ . '/../auth.php';
sev_auth_start_session();
$_sev_loggedIn = sev_auth_is_logged_in();
$_sev_userName = $_sev_loggedIn && function_exists('sev_auth_user_name') ? sev_auth_user_name() : '';
?>
<style>
.imm-navbar-wrap {
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
    background: #1b2a45;
    box-shadow: 0 2px 10px rgba(0,0,0,.25);
}
.imm-navbar-top {
    display: flex;
    align-items: stretch;
    height: 62px;
    border-bottom: 1px solid rgba(255,255,255,.10);
}
.imm-nav-stripe {
    width: 6px;
    background: #c8383a;
    flex-shrink: 0;
}
.imm-brand {
    display: flex;
    align-items: center;
    padding: 0 28px;
    text-decoration: none;
    border-right: 1px solid rgba(255,255,255,.12);
    gap: 0;
}
.imm-brand-icon {
    width: 120px;
    height: 44px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    margin-right: 10px;
}
.imm-navbar-right {
    display: flex;
    align-items: center;
    gap: 16px;
    margin-left: auto;
    padding: 0 28px;
}
.imm-user-label {
    font-size: 12px;
    color: #a0b0c8;
    font-weight: 500;
}
.imm-user-label strong { color: #fff; font-weight: 700; }
.imm-nav-login-link {
    font-size: 14px;
    font-weight: 600;
    color: #7eb3e8;
    background: transparent;
    text-decoration: none;
    padding: 20px 6px;
    letter-spacing: .01em;
}
.imm-nav-login-link:hover { color: #a8ccf0; text-decoration: underline; }
.imm-nav-btn {
    padding: 7px 18px;
    border-radius: 7px;
    font-size: 12px;
    font-weight: 700;
    cursor: pointer;
    text-decoration: none;
    display: inline-block;
    border: none;
}
.imm-nav-btn-logout { background: rgba(255,255,255,.08); color: #cbd5e1; border: 1px solid rgba(255,255,255,.12); }
.imm-nav-btn-logout:hover { background: rgba(255,255,255,.14); }
.imm-navbar-bottom {
    display: flex;
    align-items: center;
    padding: 0 6px 0 34px;
    height: 40px;
    justify-content: space-between;
}
.imm-nav-links-left { display: flex; align-items: center; }
.imm-nav-links-left a {
    text-decoration: none;
    font-size: 13px;
    font-weight: 600;
    letter-spacing: .04em;
    padding: 13px 18px;
    display: inline-block;
    color: #a0b0c8;
    border-bottom: 3px solid transparent;
    padding-bottom: 11px;
}
.imm-nav-links-left a:hover { color: #fff; border-bottom-color: rgba(200,169,81,.5); }
.imm-nav-links-left a.active { color: #fff; border-bottom: 3px solid #c8a951; padding-bottom: 11px; }
.imm-lang-bar {
    display: flex;
    align-items: center;
    gap: 2px;
    padding-right: 20px;
}
.imm-lang-bar a {
    font-size: 11px;
    font-weight: 700;
    color: #7eb3e8;
    text-decoration: none;
    padding: 4px 6px;
    border-radius: 4px;
    letter-spacing: .05em;
}
.imm-lang-bar a:hover { background: rgba(255,255,255,.08); color: #fff; }
.imm-lang-bar a.active { color: #fff; }
.imm-lang-divider { color: rgba(255,255,255,.2); font-size: 11px; padding: 0 1px; }
@media (max-width: 640px) {
    .imm-navbar-top { height: auto; }
    .imm-brand { padding: 10px 16px; }
    .imm-navbar-right { padding: 0 14px; gap: 10px; }
    .imm-navbar-bottom { padding: 0 8px 0 12px; }
    .imm-user-label { display: none; }
}
</style>
<div class="imm-navbar-wrap">
    <div class="imm-navbar-top">
        <div class="imm-nav-stripe"></div>

        <a class="imm-brand" href="./index.php">
            <div class="imm-brand-icon">
                <img style="width: 120px;" src="images/logo-2.png" alt="">
            </div>
            <div></div>
        </a>

        <div class="imm-navbar-right">
            <?php if ($_sev_loggedIn): ?>
                <span class="imm-user-label">
                    <strong><?= htmlspecialchars($_sev_userName, ENT_QUOTES, 'UTF-8') ?></strong>
                </span>
                <a class="imm-nav-login-link" href="./logout.php">Sign Out</a>
            <?php else: ?>
                <a class="imm-nav-login-link" href="./login.php">Log in</a>
            <?php endif; ?>
        </div>
    </div>

    <div class="imm-navbar-bottom">
        <div class="imm-nav-links-left">
            <a href="./index.php" class="<?= $activeNav === 'home' ? 'active' : '' ?>">Home page</a>
            <a href="./check-status.php" class="<?= $activeNav === 'check' ? 'active' : '' ?>">Verify eVisa</a>
            <?php if ($_sev_loggedIn): ?>
            <a href="./my-visa.php" class="<?= $activeNav === 'myvisa' ? 'active' : '' ?>">My Visa</a>
            <?php endif; ?>
        </div>
        <div class="imm-lang-bar">
            <a href="#">ЋИР</a>
            <span class="imm-lang-divider">|</span>
            <a href="#">LAT</a>
            <span class="imm-lang-divider">|</span>
            <a href="#" class="active">ENG</a>
        </div>
    </div>
</div>
