<?php
if (!function_exists('sev_mysql_host')) {
    $_sev_env = __DIR__ . '/.env';
    if (is_file($_sev_env)) {
        $lines = file($_sev_env, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || $line[0] === '#') continue;
            if (strpos($line, '=') === false) continue;
            [$k, $v] = explode('=', $line, 2);
            $k = trim($k);
            $v = trim($v, " \t\r\n\"'");
            if ($k !== '' && !array_key_exists($k, $_ENV)) {
                $_ENV[$k] = $v;
                putenv("$k=$v");
            }
        }
    }
    unset($_sev_env, $lines, $line, $k, $v);

    function sev_mysql_host(): string    { return $_ENV['SEV_MYSQL_HOST'] ?? getenv('SEV_MYSQL_HOST') ?: '127.0.0.1'; }
    function sev_mysql_port(): string    { return $_ENV['SEV_MYSQL_PORT'] ?? getenv('SEV_MYSQL_PORT') ?: '3306'; }
    function sev_mysql_db(): string      { return $_ENV['SEV_MYSQL_DB']   ?? getenv('SEV_MYSQL_DB')   ?: 'e_visa'; }
    function sev_mysql_user(): string    { return $_ENV['SEV_MYSQL_USER'] ?? getenv('SEV_MYSQL_USER') ?: 'root'; }
    function sev_mysql_pass(): string    { return $_ENV['SEV_MYSQL_PASS'] ?? getenv('SEV_MYSQL_PASS') ?: ''; }
    function sev_mysql_charset(): string { return 'utf8mb4'; }

    function sev_site_base(): string
    {
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $dir    = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
        return $scheme . '://' . $host . $dir;
    }
}
