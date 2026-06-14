<?php
require_once __DIR__ . '/config.php';

define('SEV_VERIFY_TABLE', 'SerbiaEVisa');

/* ── DB connection ── */
function sev_db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) return $pdo;

    $dbName  = sev_mysql_db();
    $charset = sev_mysql_charset();

    // Create database if not exists
    $dsnNoDB = sprintf('mysql:host=%s;port=%s;charset=%s',
        sev_mysql_host(), sev_mysql_port(), $charset);
    $tmp = new PDO($dsnNoDB, sev_mysql_user(), sev_mysql_pass(), [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
    $tmp->exec("CREATE DATABASE IF NOT EXISTS `"
        . str_replace('`', '``', $dbName) . "` CHARACTER SET {$charset}");
    unset($tmp);

    $dsn = sprintf(
        'mysql:host=%s;port=%s;dbname=%s;charset=%s',
        sev_mysql_host(),
        sev_mysql_port(),
        $dbName,
        $charset
    );
    $pdo = new PDO($dsn, sev_mysql_user(), sev_mysql_pass(), [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    // Auto-create the SergebiaEVisa table
    sev_ensure_table($pdo);

    return $pdo;
}

function sev_ensure_table(PDO $pdo): void
{
    $table = SEV_VERIFY_TABLE;

    $pdo->exec("CREATE TABLE IF NOT EXISTS `{$table}` (
        `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        `visa_number` VARCHAR(191) NOT NULL DEFAULT '',
        `full_name` VARCHAR(191) NOT NULL DEFAULT '',
        `name` VARCHAR(191) NOT NULL DEFAULT '',
        `surname` VARCHAR(191) NOT NULL DEFAULT '',
        `normalized_name` VARCHAR(191) NOT NULL DEFAULT '',
        `ref_number` VARCHAR(191) NOT NULL DEFAULT '',
        `birthday` DATE NULL,
        `issue_date` DATE NULL,
        `evisa_expire_date` DATE NULL,
        `place_of_issue` VARCHAR(191) NOT NULL DEFAULT '',
        `remarks` TEXT NOT NULL,
        `visa_fee` VARCHAR(50) NOT NULL DEFAULT '',
        `gender` VARCHAR(20) NOT NULL DEFAULT '',
        `nationality` VARCHAR(120) NOT NULL DEFAULT '',
        `travel_document` VARCHAR(120) NOT NULL DEFAULT '',
        `travel_doc_no` VARCHAR(191) NOT NULL DEFAULT '',
        `travel_doc_issue` DATE NULL,
        `travel_doc_expiry` DATE NULL,
        `visa_type` VARCHAR(50) NOT NULL DEFAULT 'LA-B2',
        `applicant_image` VARCHAR(255) NOT NULL DEFAULT '',
        `passport_number` VARCHAR(191) NOT NULL DEFAULT '',
        `destination_country` VARCHAR(191) NOT NULL DEFAULT '',
        `status` VARCHAR(50) NOT NULL DEFAULT 'Processing',
        `pdf_file` VARCHAR(255) NOT NULL DEFAULT '',
        `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        INDEX `idx_name_birthday` (`normalized_name`, `birthday`),
        INDEX `idx_visa_number` (`visa_number`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // Ensure columns exist (for migration from older structures)
    $cols = $pdo->query("SHOW COLUMNS FROM `{$table}`")->fetchAll(PDO::FETCH_COLUMN);

    if (!in_array('ref_number', $cols, true)) {
        $pdo->exec("ALTER TABLE `{$table}` ADD COLUMN `ref_number` VARCHAR(191) NOT NULL DEFAULT '' AFTER `normalized_name`");
    }
    if (!in_array('remarks', $cols, true)) {
        $pdo->exec("ALTER TABLE `{$table}` ADD COLUMN `remarks` TEXT NOT NULL AFTER `place_of_issue`");
    } else {
        // Ensure remarks is TEXT not VARCHAR for JSON storage
        $colInfo = $pdo->query("SHOW COLUMNS FROM `{$table}` LIKE 'remarks'")->fetch();
        if ($colInfo && stripos((string)($colInfo['Type'] ?? ''), 'text') === false) {
            $pdo->exec("ALTER TABLE `{$table}` MODIFY COLUMN `remarks` TEXT NOT NULL");
        }
    }
    if (!in_array('name', $cols, true)) {
        $pdo->exec("ALTER TABLE `{$table}` ADD COLUMN `name` VARCHAR(191) NOT NULL DEFAULT '' AFTER `full_name`");
    }
    if (!in_array('surname', $cols, true)) {
        $pdo->exec("ALTER TABLE `{$table}` ADD COLUMN `surname` VARCHAR(191) NOT NULL DEFAULT '' AFTER `name`");
    }
    if (!in_array('status', $cols, true)) {
        $pdo->exec("ALTER TABLE `{$table}` ADD COLUMN `status` VARCHAR(50) NOT NULL DEFAULT 'Processing' AFTER `destination_country`");
    }
}

/* ── Lookup by passport + evisa ID (QR scan) ── */
function sev_find_by_passport_and_evisa(string $passport, string $evisaId): ?array
{
    $passport = strtoupper(trim($passport));
    $evisaId  = strtoupper(trim($evisaId));
    if ($passport === '' || $evisaId === '') return null;

    $stmt = sev_db()->prepare(
        'SELECT * FROM `' . SEV_VERIFY_TABLE . '`
         WHERE UPPER(`passport_number`) = :p
           AND UPPER(`visa_number`)     = :e
         LIMIT 1'
    );
    $stmt->execute(['p' => $passport, 'e' => $evisaId]);
    $row = $stmt->fetch();
    return is_array($row) ? $row : null;
}

/* ── Lookup by keyword (passport OR evisa ID OR ref_number OR remarks LIKE) ── */
function sev_find_by_keyword(string $keyword): ?array
{
    $keyword = trim($keyword);
    if ($keyword === '') return null;

    $keywordUpper = strtoupper($keyword);
    $likeKeyword  = '%' . addcslashes(strtolower($keyword), '\\%_') . '%';

    $stmt = sev_db()->prepare(
        'SELECT * FROM `' . SEV_VERIFY_TABLE . '`
         WHERE UPPER(`passport_number`) = :kw
            OR UPPER(`visa_number`)     = :kw2
            OR UPPER(`ref_number`)      = :kw3
            OR UPPER(`full_name`)       = :kw4
            OR LOWER(`remarks`) LIKE :kw_like
         ORDER BY `id` DESC
         LIMIT 1'
    );
    $stmt->execute([
        'kw'      => $keywordUpper,
        'kw2'     => $keywordUpper,
        'kw3'     => $keywordUpper,
        'kw4'     => $keywordUpper,
        'kw_like' => $likeKeyword,
    ]);
    $row = $stmt->fetch();
    return is_array($row) ? $row : null;
}

/* ── Lookup by Application ID + eVisa ID ── */
function sev_find_by_application_and_evisa(string $appId, string $evisaId): ?array
{
    $evisaId = strtoupper(trim($evisaId));
    $appId   = trim($appId);
    if ($appId === '' || $evisaId === '') return null;

    $stmt = sev_db()->prepare(
        'SELECT * FROM `' . SEV_VERIFY_TABLE . '`
         WHERE UPPER(`visa_number`) = :e
         LIMIT 1'
    );
    $stmt->execute(['e' => $evisaId]);
    $row = $stmt->fetch();
    if (!is_array($row)) return null;

    // PHP-side verify applicationId from remarks JSON
    $rm = json_decode((string)($row['remarks'] ?? ''), true);
    if (!is_array($rm)) $rm = [];
    $stored = trim((string)($rm['applicationId'] ?? ''));
    return (strcasecmp($stored, $appId) === 0) ? $row : null;
}

/* ── Helpers ── */
function sev_fmt_date(string $d): string
{
    $d = trim($d);
    if ($d === '') return '—';
    $ts = strtotime($d);
    return $ts !== false ? date('d.m.Y', $ts) : $d;
}

function sev_h(string $v): string
{
    return htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
}
