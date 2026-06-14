<?php
require_once __DIR__ . '/auth.php';
sev_auth_start_session();
sev_auth_logout();
header('Location: ./index.php');
exit;
