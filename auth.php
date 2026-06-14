<?php
require_once __DIR__ . '/api/common.php';

/* ── Table name ── */
const SEV_USERS_TABLE = 'SerbiaEVisaUsers';

/* ── Ensure users table ── */
function sev_auth_ensure_table(): void
{
    static $done = false;
    if ($done) return;
    $done = true;

    sev_db()->exec("CREATE TABLE IF NOT EXISTS `" . SEV_USERS_TABLE . "` (
        `id`            INT UNSIGNED     NOT NULL AUTO_INCREMENT,
        `name`          VARCHAR(200)     NOT NULL DEFAULT '',
        `email`         VARCHAR(200)     NOT NULL,
        `password_hash` VARCHAR(255)     NOT NULL,
        `created_at`    DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        UNIQUE KEY `uniq_sev_users_email` (`email`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}

/* ── Session ── */
function sev_auth_start_session(): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
    sev_auth_ensure_table();
}

function sev_auth_is_logged_in(): bool
{
    return !empty($_SESSION['sev_user_id']);
}

function sev_auth_user_id(): int
{
    return (int)($_SESSION['sev_user_id'] ?? 0);
}

function sev_auth_user_name(): string
{
    return (string)($_SESSION['sev_user_name'] ?? '');
}

function sev_auth_user_email(): string
{
    return (string)($_SESSION['sev_user_email'] ?? '');
}

/* ── Register ── */
function sev_auth_register(string $name, string $email, string $password): array
{
    $email = strtolower(trim($email));
    $name  = trim($name);

    if (strlen($password) < 6) {
        return ['ok' => false, 'error' => 'Password must be at least 6 characters.'];
    }

    $hash = password_hash($password, PASSWORD_BCRYPT);
    try {
        $st = sev_db()->prepare(
            "INSERT INTO `" . SEV_USERS_TABLE . "` (name, email, password_hash) VALUES (?,?,?)"
        );
        $st->execute([$name, $email, $hash]);
        return ['ok' => true];
    } catch (PDOException $e) {
        if ($e->getCode() === '23000') {
            return ['ok' => false, 'error' => 'An account with this email already exists.'];
        }
        return ['ok' => false, 'error' => 'Registration failed. Please try again.'];
    }
}

/* ── Login ── */
function sev_auth_login(string $email, string $password): array
{
    $email = strtolower(trim($email));

    $st = sev_db()->prepare(
        "SELECT * FROM `" . SEV_USERS_TABLE . "` WHERE email = ? LIMIT 1"
    );
    $st->execute([$email]);
    $user = $st->fetch();

    if (!$user || !password_verify($password, (string)$user['password_hash'])) {
        return ['ok' => false, 'error' => 'Invalid email or password.'];
    }

    sev_auth_start_session();
    $_SESSION['sev_user_id']    = (int)$user['id'];
    $_SESSION['sev_user_name']  = (string)$user['name'];
    $_SESSION['sev_user_email'] = (string)$user['email'];

    return ['ok' => true];
}

/* ── Logout ── */
function sev_auth_logout(): void
{
    sev_auth_start_session();
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}
