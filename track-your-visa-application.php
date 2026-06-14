<?php
require_once __DIR__ . '/auth.php';
sev_auth_start_session();

$activeNav = 'check';
$record    = null;
$errorMsg  = '';

/* ── 1. QR scan: ?passport=...&evisa=... ── */
if (isset($_GET['passport'], $_GET['evisa'])) {
    $p = trim((string)$_GET['passport']);
    $e = trim((string)$_GET['evisa']);
    try {
        $record = sev_find_by_passport_and_evisa($p, $e);
    } catch (Throwable $err) {
        $errorMsg = 'Database error. Please try again later.';
    }
    if ($record === null && $errorMsg === '') {
        $errorMsg = 'No matching record found.';
    }

/* ── 2. Form POST: Application ID + eVisa ID ── */
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $appId   = trim((string)($_POST['app_id']  ?? ''));
    $evisaId = trim((string)($_POST['evisa_id'] ?? ''));
    if ($appId === '' || $evisaId === '') {
        $errorMsg = 'Both fields are required.';
    } else {
        try {
            $record = sev_find_by_application_and_evisa($appId, $evisaId);
        } catch (Throwable $err) {
            $errorMsg = 'Database error. Please try again later.';
        }
        if ($record === null && $errorMsg === '') {
            $errorMsg = 'No record found. Please check your Application ID and eVisa ID.';
        }
    }
}

/* ── Extract fields ── */
$rm = [];
if ($record !== null) {
    $rm = json_decode((string)($record['remarks'] ?? ''), true);
    if (!is_array($rm)) $rm = [];
}

$applicationId     = trim((string)($rm['applicationId']        ?? ''));
$dateOfApplication = trim((string)($rm['dateOfApplication']    ?? ''));
$surname           = trim((string)($record['surname']           ?? ''));
$givenName         = trim((string)($record['name']              ?? ''));
$dateOfBirth       = trim((string)($record['birthday']          ?? ''));
$sex               = trim((string)($record['gender']            ?? ''));
$nationality       = trim((string)($record['nationality']       ?? ''));
$travelDocNumber   = trim((string)($record['passport_number']   ?? ''));
$evisaValidFrom    = trim((string)($record['issue_date']        ?? ''));
$evisaValidTo      = trim((string)($record['evisa_expire_date'] ?? ''));
$durationDays      = trim((string)($rm['durationDays']          ?? ($record['visa_fee'] ?? '')));
$numberOfEntries   = trim((string)($rm['numberOfEntries']       ?? ''));
$category          = trim((string)($rm['category']              ?? ($record['visa_type'] ?? '')));
$decisionNumber    = trim((string)($record['ref_number']        ?? ''));
$decisionDate      = trim((string)($rm['decisionDate']          ?? ''));
$evisaId           = trim((string)($record['visa_number']       ?? ''));
$verificationCode  = trim((string)($rm['verificationCode']      ?? ''));
$photo             = trim((string)($record['applicant_image']   ?? ''));

$mrzLine1 = trim((string)($rm['mrzLine1'] ?? ''));
$mrzLine2 = trim((string)($rm['mrzLine2'] ?? ''));
if ($mrzLine1 === '' && $mrzLine2 === '' && isset($rm['mrzLine'])) {
    $parts    = explode("\n", (string)$rm['mrzLine']);
    $mrzLine1 = trim($parts[0] ?? '');
    $mrzLine2 = trim($parts[1] ?? '');
}

$categoryDisplay = strtoupper($category);
if ($categoryDisplay === 'WORK PERMIT') {
    $categoryDisplay = "&#1044;&#1054;&#1047;&#1042;&#1054;&#1051;&#1040; &#1047;&#1040; &#1056;&#1040;&#1044;/ WORK PERMIT";
}

$entriesDisplay = strtoupper($numberOfEntries);
if ($entriesDisplay === 'MULTIPLES' || $entriesDisplay === 'MULTIPLE') {
    $entriesDisplay = "&#1052;&#1059;&#1051;&#1058;&#1048;&#1055;&#1051;&#1045;/ MULTIPLES";
} elseif ($entriesDisplay === 'SINGLE') {
    $entriesDisplay = "&#1032;&#1045;&#1044;&#1048;&#1053;&#1048;&#1063;&#1053;&#1054;/ SINGLE";
}

/* ── QR URL: points back to this page via passport+evisa params ── */
$_scheme    = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$_host      = $_SERVER['HTTP_HOST'] ?? 'localhost';
$_scriptDir = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
$_verifyUrl = $_scheme . '://' . $_host . $_scriptDir
    . '/track-your-visa-application.php'
    . '?passport=' . rawurlencode($travelDocNumber)
    . '&evisa='    . rawurlencode($evisaId);
$qrUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=180x180&margin=2&data=' . rawurlencode($_verifyUrl);

/* ── Keep form values for re-display ── */
$fAppId = sev_h(trim((string)($_POST['app_id']  ?? '')));
$fEvisa = sev_h(trim((string)($_POST['evisa_id'] ?? '')));
?>
<!DOCTYPE html>
<html lang="sr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Track Your Visa Application<?= $record !== null ? ' &mdash; ' . sev_h($evisaId) : '' ?></title>
<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
body {
    font-family: Arial, Helvetica, sans-serif;
    background: <?= $record !== null ? '#888' : '#f0f2f5' ?>;
    color: #000;
    font-size: 13px;
    min-height: 100vh;
    display: flex;
    flex-direction: column;
}
a { text-decoration: none; color: inherit; }
<?php if ($record === null): ?>
/* ── FORM styles ── */
.page-wrap {
    flex: 1;
    padding: 40px 20px 60px;
    max-width: 900px;
    margin: 0 auto;
    width: 100%;
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
}
.track-card {
    background: #fff;
    border-top: 4px solid #1b2a45;
    border-bottom: 4px solid #1b2a45;
    padding: 40px 48px 36px;
}
.track-title {
    font-size: 22px;
    font-weight: 800;
    color: #1b2a45;
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 28px;
}
.field-group { margin-bottom: 22px; }
.field-group label {
    display: block;
    font-size: 14px;
    color: #1b2a45;
    font-weight: 500;
    margin-bottom: 8px;
}
.field-group input {
    width: 100%;
    max-width: 560px;
    border: 1.5px solid #b0bec5;
    border-radius: 6px;
    padding: 12px 14px;
    font-size: 15px;
    color: #1b2a45;
    outline: none;
    background: #fff;
}
.field-group input:focus {
    border-color: #1b2a45;
    box-shadow: 0 0 0 2px rgba(27,42,69,.1);
}
.form-footer-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 12px;
    margin-top: 8px;
}
.mandatory-note { font-size: 13px; color: #374151; }
.btn-check {
    background: #1b2a45;
    color: #fff;
    border: none;
    border-radius: 6px;
    padding: 13px 32px;
    font-size: 15px;
    font-weight: 700;
    cursor: pointer;
}
.btn-check:hover { background: #243857; }
.alert-err {
    background: #fef2f2;
    border: 1px solid #fca5a5;
    color: #b91c1c;
    border-radius: 6px;
    padding: 13px 16px;
    margin-bottom: 24px;
    font-size: 14px;
}
@media (max-width: 640px) {
    .track-card { padding: 28px 20px 24px; }
    .field-group input { max-width: 100%; }
    .form-footer-row { flex-direction: column; align-items: flex-start; }
    .btn-check { width: 100%; }
}
<?php else: ?>
/* ── ACTION BAR ── */
.screen-actions {
    display: flex;
    gap: 10px;
    justify-content: center;
    padding: 10px;
    background: #fff;
    border-bottom: 1px solid #ccc;
    position: sticky;
    top: 0;
    z-index: 100;
}
.act-btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 8px 18px;
    border-radius: 5px;
    font-weight: 700;
    font-size: 13px;
    cursor: pointer;
    border: none;
    text-decoration: none;
    color: #fff;
}
.act-back  { background: #495057; }
.act-print { background: #0d6efd; }
/* ── A4 PAGE ── */
.page {
    width: 210mm;
    margin: 14px auto;
    background: #fff;
    padding: 20px 40px;
    overflow: hidden;
}
.doc-header img { width: 100%; }
.title-bar { text-align: center; padding: 10px 16px; }
.title-sr { font-weight: 700; letter-spacing: .5px; margin-bottom: 3px; }
.title-en { font-weight: 700; }
.sec-hdr { font-weight: 700; margin: 0; }
.app-section { display: flex; gap: 0; }
.app-fields  { flex: 1; padding: 0 14px 1px 0; }
.app-photo {
    width: 120px;
    flex-shrink: 0;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 6px;
}
.app-photo img { width: 120px; height: 155px; object-fit: cover; display: block; }
.no-photo {
    width: 100px; height: 125px;
    border: 2px dashed #bbb;
    display: flex; align-items: center; justify-content: center;
    color: #aaa; font-size: 11px; text-align: center;
}
.field-row {
    display: flex;
    align-items: center;
    padding: 2px 0;
    gap: 20px;
    line-height: 1.4;
}
.f-lbl {
    width: 220px;
    flex-shrink: 0;
    background: #dadada;
    padding-left: 5px;
}
.f-lbl .sr { display: block; margin-top: -1px; }
.f-val { flex: 1; font-size: 13px; font-weight: 700; color: #111; }
.diver { padding: 2px; width: 100%; height: 2px; background: #000; }
.note-row { padding: 6px 0; }
.evisa-fields { padding: 1px 0; }
.bottom-section {
    display: flex;
    align-items: flex-start;
    gap: 14px;
    padding: 10px 0;
    background: #fff;
}
.qr-wrap { flex-shrink: 0; }
.qr-wrap img { width: 130px; height: 130px; display: block; }
.bottom-note { flex: 1; line-height: 1.7; color: #000; }
.bottom-note .sr-text { font-weight: 700; margin-bottom: 4px; display: block; }
@media print {
    .imm-navbar-wrap, .screen-actions { display: none !important; }
    body { background: #fff; }
    .page { margin: 0; box-shadow: none; width: 100%; }
}
@page { size: A4; margin: 0; }
<?php endif; ?>
</style>
</head>
<body>

<?php require __DIR__ . '/partials/navbar.php'; ?>

<?php if ($record === null): ?>
<!-- SEARCH FORM -->
<div class="page-wrap">
    <div class="track-card">
        <h1 class="track-title">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none">
                <circle cx="11" cy="11" r="7" stroke="#1b2a45" stroke-width="2"/>
                <line x1="16.5" y1="16.5" x2="21" y2="21" stroke="#1b2a45" stroke-width="2" stroke-linecap="round"/>
            </svg>
            Check Visa Status
        </h1>
        <?php if ($errorMsg !== ''): ?>
        <div class="alert-err">&#x26A0; <?= sev_h($errorMsg) ?></div>
        <?php endif; ?>
        <form method="POST" action="">
            <div class="field-group">
                <label for="app_id">Application ID <span style="color:#c00">*</span></label>
                <input type="text" id="app_id" name="app_id" value="<?= $fAppId ?>" autocomplete="off" required>
            </div>
            <div class="field-group">
                <label for="evisa_id">eVisa ID / Visa Serial Number <span style="color:#c00">*</span></label>
                <input type="text" id="evisa_id" name="evisa_id" value="<?= $fEvisa ?>" autocomplete="off" required>
            </div>
            <div class="form-footer-row">
                <span class="mandatory-note">All fields marked with * are mandatory</span>
                <button type="submit" class="btn-check">Check Status</button>
            </div>
        </form>
    </div>
</div>

<?php else: ?>
<!-- RESULT: exact serbia-evisa-view.php design -->

<div class="screen-actions">
    <a class="act-btn act-back" href="./track-your-visa-application.php">&#8592; Search Again</a>
    <button class="act-btn act-print" onclick="window.print()">&#128438; Print / PDF</button>
</div>

<div class="page">

    <!-- HEADER IMAGE -->
    <div class="doc-header">
        <img src="images/top.png" alt="">
    </div>

    <!-- TITLE BAR -->
    <div class="title-bar">
        <div class="title-sr">&#1054;&#1041;&#1040;&#1042;&#1045;&#1064;&#1058;&#1045;&#1034;&#1045; &#1054; &#1044;&#1054;&#1044;&#1040;&#1042;&#1040;&#1034;&#1059; &#1045;-&#1042;&#1048;&#1047;&#1045;</div>
        <div class="title-en">NOTIFICATION OF GRANTING AN E-VISA</div>
    </div>

    <!-- APPLICATION DETAILS SECTION -->
    <div class="sec-hdr">&#1044;&#1077;&#1090;&#1072;&#1083;&#1080; &#1072;&#1087;&#1083;&#1080;&#1082;&#1072;&#1094;&#1080;&#1112;&#1077;/ Application details</div>
    <div class="app-section">
        <div class="app-fields">

            <div class="field-row">
                <div class="f-lbl">&#1048;&#1044; &#1072;&#1087;&#1083;&#1080;&#1082;&#1072;&#1094;&#1080;&#1112;&#1077;/ <span class="sr">Application ID</span></div>
                <div class="f-val"><?= sev_h($applicationId) ?></div>
            </div>

            <div class="field-row">
                <div class="f-lbl">&#1044;&#1072;&#1090;&#1091;&#1084; &#1087;&#1086;&#1076;&#1085;&#1086;&#1096;&#1077;&#1114;&#1072; &#1079;&#1072;&#1093;&#1090;&#1077;&#1074;&#1072;/<span class="sr">Date of Application</span></div>
                <div class="f-val"><?= sev_h(sev_fmt_date($dateOfApplication)) ?></div>
            </div>

            <div class="note-row">
                <span>&#1054;&#1074;&#1086; &#1112;&#1077; &#1076;&#1072; &#1086;&#1073;&#1072;&#1074;&#1077;&#1089;&#1090;&#1080;&#1090;&#1077; &#1077;-&#1074;&#1080;&#1079;&#1091; &#1082;&#1086;&#1112;&#1072; &#1112;&#1077; &#1080;&#1079;&#1076;&#1072;&#1090;&#1072; /</span><br>
                This is to inform an e-visa issued to
            </div>

            <div class="field-row">
                <div class="f-lbl">&#1055;&#1088;&#1077;&#1079;&#1080;&#1084;&#1077;/ Surname</div>
                <div class="f-val"><?= sev_h(strtoupper($surname)) ?></div>
            </div>

            <div class="field-row">
                <div class="f-lbl">Ime/ Given name(s)</div>
                <div class="f-val"><?= sev_h(strtoupper($givenName)) ?></div>
            </div>

            <div class="field-row">
                <div class="f-lbl">&#1044;&#1072;&#1090;&#1091;&#1084; &#1088;&#1086;&#1106;&#1077;&#1114;&#1072;/ Date of birth</div>
                <div class="f-val"><?= sev_h(sev_fmt_date($dateOfBirth)) ?></div>
            </div>

            <div class="field-row">
                <div class="f-lbl">&#1057;&#1077;&#1082;/ Sex</div>
                <div class="f-val"><?= sev_h(strtoupper($sex)) ?></div>
            </div>

            <div class="field-row">
                <div class="f-lbl">&#1085;&#1072;&#1094;&#1080;&#1086;&#1085;&#1072;&#1083;&#1085;&#1086;&#1089;&#1090;/ Nationality</div>
                <div class="f-val"><?= sev_h(strtoupper($nationality)) ?></div>
            </div>

            <div class="field-row">
                <div class="f-lbl">&#1041;&#1088;&#1086;&#1112; &#1087;&#1091;&#1090;&#1085;&#1077; &#1080;&#1089;&#1087;&#1088;&#1072;&#1074;&#1077;<span class="sr">Travel document number</span></div>
                <div class="f-val"><?= sev_h(strtoupper($travelDocNumber)) ?></div>
            </div>

        </div><!-- /.app-fields -->

        <div class="app-photo">
            <?php if ($photo !== ''): ?>
                <img src="<?= sev_h('https://visaguro.com/api/uploads/applicants/' . $photo) ?>" alt="Applicant Photo">
            <?php else: ?>
                <div class="no-photo">No Photo</div>
            <?php endif; ?>
        </div>
    </div><!-- /.app-section -->

    <!-- E-VISA DETAILS SECTION -->
    <div class="sec-hdr">&#1044;&#1077;&#1090;&#1072;&#1083;&#1080; &#1086; &#1077;-&#1074;&#1080;&#1079;&#1091;/E-visa details</div>
    <div class="evisa-fields">

        <div class="field-row">
            <div class="f-lbl">&#1042;&#1072;&#1078;&#1077;&#1114;&#1077; &#1077;-&#1074;&#1080;&#1079;&#1077;/<span class="sr">E-visa validity</span></div>
            <div class="f-val"><?= sev_h(sev_fmt_date($evisaValidFrom)) ?> &nbsp;&mdash;&nbsp; <?= sev_h(sev_fmt_date($evisaValidTo)) ?></div>
        </div>

        <div class="field-row">
            <div class="f-lbl">&#1058;&#1088;&#1072;&#1112;&#1072;&#1114;&#1077; &#1073;&#1086;&#1088;&#1072;&#1074;&#1082;&#1072; (&#1076;&#1072;&#1085;&#1072;)/<span class="sr">Duration of stay (days)</span></div>
            <div class="f-val"><?= sev_h($durationDays) ?></div>
        </div>

        <div class="field-row">
            <div class="f-lbl">&#1041;&#1088;&#1086;&#1112; &#1091;&#1085;&#1086;&#1089;&#1072;/<span class="sr">Number of Entries</span></div>
            <div class="f-val"><?= $entriesDisplay ?></div>
        </div>

        <div class="field-row">
            <div class="f-lbl">&#1050;&#1072;&#1090;&#1077;&#1075;&#1086;&#1088;&#1080;&#1112;&#1072; &#1077;&#1083;&#1077;&#1082;&#1090;&#1088;&#1086;&#1085;&#1089;&#1082;&#1077; &#1074;&#1080;&#1079;&#1077;/<span class="sr">Category of electronic visa</span></div>
            <div class="f-val"><?= $categoryDisplay ?></div>
        </div>

        <div class="field-row">
            <div class="f-lbl">&#1041;&#1088;&#1086;&#1112; &#1086;&#1076;&#1083;&#1091;&#1082;&#1077; &#1086; &#1076;&#1086;&#1076;&#1077;&#1083;&#1080; &#1077;-&#1074;&#1080;&#1079;&#1077;/<span class="sr">E-visa decision number</span></div>
            <div class="f-val"><?= sev_h($decisionNumber) ?></div>
        </div>

        <div class="field-row">
            <div class="f-lbl">&#1044;&#1072;&#1090;&#1091;&#1084; &#1086;&#1076;&#1083;&#1091;&#1082;&#1077; &#1086; &#1076;&#1086;&#1076;&#1077;&#1083;&#1080; &#1077;-&#1074;&#1080;&#1079;&#1077;/<span class="sr">E-visa grant decision date</span></div>
            <div class="f-val"><?= sev_h(sev_fmt_date($decisionDate)) ?></div>
        </div>

        <div class="field-row">
            <div class="f-lbl">&#1045;-&#1074;&#1080;&#1079;&#1072; &#1048;&#1044;/ E-visa ID</div>
            <div class="f-val"><?= sev_h($evisaId) ?></div>
        </div>

        <div class="field-row">
            <div class="f-lbl">&#1050;&#1086;&#1076; &#1079;&#1072; &#1074;&#1077;&#1088;&#1080;&#1092;&#1080;&#1082;&#1072;&#1094;&#1080;&#1112;&#1091; &#1077;-&#1074;&#1080;&#1079;&#1077;/<span class="sr">E-visa Verification Code</span></div>
            <div class="f-val"><?= sev_h($verificationCode) ?></div>
        </div>

    </div><!-- /.evisa-fields -->

    <!-- BOTTOM: QR + NOTE + MRZ -->
    <div class="bottom-section">
        <div class="qr-wrap">
            <img src="<?= sev_h($qrUrl) ?>" alt="QR Code" width="130" height="130">
        </div>
        <div class="bottom-note">
            <span class="sr-text">&#1052;&#1086;&#1083;&#1080;&#1084;&#1086; &#1074;&#1072;&#1089; &#1076;&#1072; &#1086;&#1074;&#1086; &#1086;&#1073;&#1072;&#1074;&#1077;&#1096;&#1090;&#1077;&#1114;&#1077; &#1087;&#1086;&#1085;&#1077;&#1089;&#1077;&#1090;&#1077; &#1089;&#1072; &#1089;&#1086;&#1073;&#1086;&#1084; &#1080; &#1087;&#1086;&#1082;&#1072;&#1078;&#1077;&#1090;&#1077; &#1075;&#1072; &#1090;&#1088;&#1072;&#1085;&#1089;&#1087;&#1086;&#1088;&#1090;&#1085;&#1086;&#1112; &#1082;&#1086;&#1084;&#1087;&#1072;&#1085;&#1080;&#1112;&#1080; &#1088;&#1072;&#1076;&#1080; &#1087;&#1088;&#1086;&#1074;&#1077;&#1088;&#1077; &#1077;&#1083;&#1077;&#1082;&#1090;&#1088;&#1086;&#1085;&#1089;&#1082;&#1077; &#1074;&#1080;&#1079;&#1077;.</span>
            <span class="sr-text">Please bring this notification with you and show transport company for a e-visa check.</span>
            <?php if ($mrzLine1 !== ''): ?><span class="sr-text"><?= sev_h($mrzLine1) ?></span><?php endif; ?>
            <?php if ($mrzLine2 !== ''): ?><span class="sr-text"><?= sev_h($mrzLine2) ?></span><?php endif; ?>
        </div>
    </div>

    <div class="diver" style="background-color: red;"></div>
    <div class="diver"></div>

</div><!-- /.page -->

<?php endif; ?>

<?php require __DIR__ . '/partials/footer.php'; ?>

</body>
</html>
