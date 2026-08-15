<?php
require_once __DIR__ . '/auth.php';
sev_auth_start_session();

if (!sev_auth_is_logged_in()) {
    header('Location: ./login.php');
    exit;
}

$record   = null;
$errorMsg = '';
$searched = false;

/* ── Handle search ── */
if (isset($_GET['keyword'])) {
    $searched = true;
    $kw = strtoupper(trim((string)$_GET['keyword']));
    if ($kw === '') {
        $errorMsg = 'Please enter a passport number or eVisa ID.';
    } else {
        try {
            $record = sev_find_by_keyword($kw);
        } catch (Throwable $err) {
            $errorMsg = 'Database error. Please try again later.';
        }
        if ($record === null && $errorMsg === '') {
            $errorMsg = 'No record found. Please check the details and try again.';
        }
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $kw = strtoupper(trim((string)($_POST['keyword'] ?? '')));
    if ($kw !== '') {
        $redirect = strtok($_SERVER['REQUEST_URI'] ?? 'my-visa.php', '?') . '?keyword=' . urlencode($kw);
        header('Location: ' . $redirect);
        exit;
    }
}

/* ── Extract fields if found ── */
$rm = [];
if ($record !== null) {
    $rm = json_decode((string)($record['remarks'] ?? ''), true);
    if (!is_array($rm)) $rm = [];
}
$applicationId    = htmlspecialchars(trim((string)($rm['applicationId'] ?? '')), ENT_QUOTES, 'UTF-8');
$dateOfApplication = sev_fmt_date((string)($rm['dateOfApplication'] ?? ''));
$surname          = htmlspecialchars(strtoupper(trim((string)($record['surname']         ?? ''))), ENT_QUOTES, 'UTF-8');
$givenName        = htmlspecialchars(strtoupper(trim((string)($record['name']            ?? ''))), ENT_QUOTES, 'UTF-8');
$dateOfBirth      = sev_fmt_date((string)($record['birthday']        ?? ''));
$sex              = htmlspecialchars(strtoupper(trim((string)($record['gender']          ?? ''))), ENT_QUOTES, 'UTF-8');
$nationality      = htmlspecialchars(strtoupper(trim((string)($record['nationality']     ?? ''))), ENT_QUOTES, 'UTF-8');
$passportNo       = htmlspecialchars(strtoupper(trim((string)($record['passport_number'] ?? ''))), ENT_QUOTES, 'UTF-8');
$evisaValidFrom   = sev_fmt_date((string)($record['issue_date']      ?? ''));
$evisaValidTo     = sev_fmt_date((string)($record['evisa_expire_date']?? ''));
$durationDays     = htmlspecialchars(trim((string)($rm['durationDays'] ?? '')), ENT_QUOTES, 'UTF-8');
$numberOfEntries  = htmlspecialchars(trim((string)($rm['numberOfEntries'] ?? '')), ENT_QUOTES, 'UTF-8');
$category         = strtoupper(trim((string)($rm['category'] ?? ($record['visa_type'] ?? ''))));
$decisionNumber   = htmlspecialchars(trim((string)($record['ref_number']  ?? '')), ENT_QUOTES, 'UTF-8');
$decisionDate     = sev_fmt_date((string)($rm['decisionDate'] ?? ''));
$evisaId          = htmlspecialchars(trim((string)($record['visa_number'] ?? '')), ENT_QUOTES, 'UTF-8');
$verificationCode = htmlspecialchars(trim((string)($rm['verificationCode'] ?? '')), ENT_QUOTES, 'UTF-8');
$photo            = trim((string)($record['applicant_image'] ?? ''));
$photoUrl         = $photo !== '' ? '../evisa/api/uploads/applicants/' . rawurlencode($photo) : '';

$categoryDisplay = $category;
if ($category === 'WORK PERMIT') $categoryDisplay = 'ДОЗВОЛА ЗА РАД / WORK PERMIT';
$entriesDisplay = strtoupper($numberOfEntries);
if ($entriesDisplay === 'MULTIPLES' || $entriesDisplay === 'MULTIPLE') $entriesDisplay = 'МУЛТИПЛЕ / MULTIPLES';
elseif ($entriesDisplay === 'SINGLE') $entriesDisplay = 'ЈЕДИНИЧНО / SINGLE';

$mrzLine1 = trim((string)($rm['mrzLine1'] ?? ''));
$mrzLine2 = trim((string)($rm['mrzLine2'] ?? ''));
if ($mrzLine1 === '' && isset($rm['mrzLine'])) {
    $parts = explode("\n", (string)$rm['mrzLine']);
    $mrzLine1 = trim($parts[0] ?? '');
    $mrzLine2 = trim($parts[1] ?? '');
}

$userName = sev_auth_user_name();
$activeNav = 'myvisa';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>My Visa – Serbia eVisa Verification Portal</title>
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;background:#f0f4fa;color:#1b2a45;min-height:100vh;display:flex;flex-direction:column}
a{text-decoration:none;color:inherit}
img{display:block;max-width:100%}

.page-wrap{flex:1;max-width:900px;margin:0 auto;width:100%;padding:32px 20px}

/* ── Welcome bar ── */
.welcome-bar{background:#1b2a45;color:#fff;border-radius:8px;padding:16px 24px;margin-bottom:24px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px}
.welcome-bar .wb-name{font-size:16px;font-weight:700}
.welcome-bar .wb-sub{font-size:13px;color:#c5d3e8;margin-top:2px}
.wb-logout{font-size:13px;font-weight:700;color:#f87171;padding:6px 14px;border:1px solid #f87171;border-radius:4px;transition:all .15s}
.wb-logout:hover{background:#f87171;color:#fff}

/* ── Search box ── */
.search-box{background:#fff;border-radius:10px;box-shadow:0 2px 12px rgba(0,0,0,.08);padding:28px 32px;margin-bottom:28px}
.search-box h2{font-size:20px;font-weight:800;margin-bottom:6px}
.search-box p{font-size:14px;color:#6b7280;margin-bottom:20px}
.form-row{display:flex;gap:10px}
.form-row input{flex:1;border:1.5px solid #d1d5db;border-radius:7px;padding:12px 14px;font-size:15px;color:#1b2a45;outline:none;transition:border-color .15s}
.form-row input:focus{border-color:#1a6bbf}
.form-row button{background:#c0392b;color:#fff;border:none;border-radius:7px;padding:12px 22px;font-size:15px;font-weight:700;cursor:pointer;transition:background .15s}
.form-row button:hover{background:#a93226}

/* ── Alerts ── */
.alert-err{background:#fef2f2;border:1px solid #fca5a5;color:#b91c1c;border-radius:7px;padding:13px 18px;margin-bottom:20px;font-size:14px}
.alert-ok{background:#f0fdf4;border:1px solid #86efac;color:#166534;border-radius:7px;padding:10px 18px;margin-bottom:16px;font-size:14px;display:flex;align-items:center;gap:8px;flex-wrap:wrap}

/* ── Verified banner ── */
.verified-banner{background:linear-gradient(135deg,#166534,#15803d);color:#fff;border-radius:10px 10px 0 0;padding:18px 28px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px}
.verified-banner .badge{display:inline-flex;align-items:center;gap:8px;font-size:19px;font-weight:800}
.verified-banner .badge span.icon{font-size:24px}
.verified-banner .evisa-code{font-size:14px;opacity:.9}

/* ── Document card ── */
.doc-card{background:#fff;border:1px solid #e5e7eb;border-top:none;border-radius:0 0 10px 10px;box-shadow:0 4px 20px rgba(0,0,0,.07)}
.doc-header-strip img{width:100%}
.doc-title-bar{text-align:center;padding:10px 16px;background:#fff;border-bottom:1px solid #e5e7eb}
.doc-title-sr{font-weight:800;font-size:14px;letter-spacing:.3px}
.doc-title-en{font-weight:700;font-size:13px}
.doc-body{padding:16px 24px 24px}

.sec-title{font-size:12px;font-weight:800;color:#1e3a8a;border-bottom:2px solid #bfdbfe;padding-bottom:4px;margin:16px 0 10px;text-transform:uppercase;letter-spacing:.4px}
.app-row{display:flex;gap:20px;margin-bottom:12px}
.app-fields{flex:1;min-width:0}
.app-photo-wrap{flex-shrink:0;width:120px;display:flex;flex-direction:column;align-items:center}
.app-photo-wrap img{width:120px;height:155px;object-fit:cover;border:1px solid #d1d5db;border-radius:4px}
.no-photo{width:120px;height:155px;border:2px dashed #d1d5db;border-radius:4px;display:flex;align-items:center;justify-content:center;color:#9ca3af;font-size:12px;text-align:center}
.field-row{display:flex;align-items:baseline;padding:5px 0;border-bottom:1px solid #f3f4f6;gap:12px;font-size:13px}
.field-row:last-child{border-bottom:none}
.f-lbl{min-width:220px;color:#64748b;font-size:12px;flex-shrink:0}
.f-val{font-weight:700;color:#111827;word-break:break-word}
.mrz-block{background:#f8fafc;border:1px solid #e2e8f0;border-radius:5px;padding:10px 14px;margin-top:14px;font-family:'Courier New',monospace;font-size:12px;letter-spacing:.5px;color:#334155;word-break:break-all}
.print-btn{display:inline-flex;align-items:center;gap:6px;background:#1a6bbf;color:#fff;border:none;border-radius:7px;padding:9px 18px;font-size:14px;font-weight:700;cursor:pointer}
.print-btn:hover{background:#155da0}

@media(max-width:640px){
    .page-wrap{padding:16px 10px}
    .search-box{padding:20px 16px}
    .form-row{flex-direction:column}
    .form-row button{width:100%}
    .welcome-bar{flex-direction:column;align-items:flex-start}
    .doc-body{padding:14px 14px 18px}
    .app-row{flex-direction:column-reverse;gap:14px}
    .app-photo-wrap{width:100%;justify-content:center;margin-bottom:8px}
    .field-row{flex-direction:column;align-items:flex-start;gap:2px;padding:6px 0}
    .f-lbl{min-width:100%;width:100%;font-size:11px}
    .f-val{font-size:13px}
    .verified-banner{flex-direction:column;padding:14px 16px}
    .mrz-block{font-size:11px;letter-spacing:0;overflow-x:auto;word-break:break-all}
}
@media print{
    .page-wrap > :not(.doc-card):not(.verified-banner):not(.alert-ok){display:none!important}
    body{background:#fff}.doc-card{box-shadow:none}.verified-banner{border-radius:0}
}
</style>
</head>
<body>

<?php require_once __DIR__ . '/partials/navbar.php'; ?>

<div class="page-wrap">

    <!-- Welcome bar -->
    <div class="welcome-bar">
        <div>
            <div class="wb-name">&#x1F44B; Welcome, <?php echo htmlspecialchars($userName, ENT_QUOTES, 'UTF-8'); ?>!</div>
            <div class="wb-sub">Search your eVisa by passport number or eVisa ID</div>
        </div>
        <a href="./logout.php" class="wb-logout">Sign Out</a>
    </div>

    <!-- Search box -->
    <div class="search-box">
        <h2>&#x1F50D; Search Your eVisa</h2>
        <p>Enter your passport number or eVisa ID to view your visa details.</p>
        <form method="GET">
            <div class="form-row">
                <input type="text" name="keyword"
                    value="<?php echo htmlspecialchars((string)($_GET['keyword'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                    placeholder="Passport number or eVisa ID" required autocomplete="off">
                <button type="submit">Search</button>
            </div>
        </form>
    </div>

    <?php if ($searched && $errorMsg !== ''): ?>
    <div class="alert-err">&#x26A0; <?php echo htmlspecialchars($errorMsg, ENT_QUOTES, 'UTF-8'); ?></div>

    <?php elseif ($record !== null): ?>
    <!-- Result -->
    <div class="alert-ok">
        <span>&#x2705;</span>
        <span>eVisa found and <strong>verified</strong> in the official Serbia eVisa database.</span>
        <button class="print-btn" onclick="window.print()">&#x1F5A8; Print</button>
    </div>

    <div class="verified-banner">
        <div class="badge"><span class="icon">&#x2705;</span> VERIFIED — REPUBLIC OF SERBIA</div>
        <div class="evisa-code">eVisa ID: <?php echo $evisaId; ?> &nbsp;|&nbsp; Passport: <?php echo $passportNo; ?></div>
    </div>

    <div class="doc-card">
        <div class="doc-header-strip">
            <img src="../evisa/images/servia-visa/top.png" alt="Serbia eVisa Header">
        </div>
        <div class="doc-title-bar">
            <div class="doc-title-sr">ОБАВЕШТЕЊЕ О ДОДАВАЊУ Е-ВИЗЕ</div>
            <div class="doc-title-en">NOTIFICATION OF GRANTING AN E-VISA</div>
        </div>
        <div class="doc-body">

            <div class="sec-title">Детали апликације / Application Details</div>
            <div class="field-row">
                <div class="f-lbl">ИД апликације / Application ID</div>
                <div class="f-val"><?php echo $applicationId !== '' ? $applicationId : '—'; ?></div>
            </div>
            <div class="field-row">
                <div class="f-lbl">Датум подношења захтева / Date of Application</div>
                <div class="f-val"><?php echo $dateOfApplication; ?></div>
            </div>

            <div class="sec-title">Лични подаци / Personal Details</div>
            <div class="app-row">
                <div class="app-fields">
                    <div class="field-row"><div class="f-lbl">Презиме / Surname</div><div class="f-val"><?php echo $surname; ?></div></div>
                    <div class="field-row"><div class="f-lbl">Ime / Given Name(s)</div><div class="f-val"><?php echo $givenName; ?></div></div>
                    <div class="field-row"><div class="f-lbl">Датум рођења / Date of Birth</div><div class="f-val"><?php echo $dateOfBirth; ?></div></div>
                    <div class="field-row"><div class="f-lbl">Сек / Sex</div><div class="f-val"><?php echo $sex; ?></div></div>
                    <div class="field-row"><div class="f-lbl">националност / Nationality</div><div class="f-val"><?php echo $nationality; ?></div></div>
                    <div class="field-row"><div class="f-lbl">Број путне исправе / Travel Document Number</div><div class="f-val"><?php echo $passportNo; ?></div></div>
                </div>
                <div class="app-photo-wrap">
                    <?php if ($photoUrl !== ''): ?>
                        <img src="<?php echo htmlspecialchars($photoUrl, ENT_QUOTES, 'UTF-8'); ?>" alt="Applicant Photo">
                    <?php else: ?>
                        <div class="no-photo">No Photo</div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="sec-title">Детали о е-визу / E-Visa Details</div>
            <div class="field-row"><div class="f-lbl">Важење е-визе / E-Visa Validity</div><div class="f-val"><?php echo $evisaValidFrom; ?> &nbsp;—&nbsp; <?php echo $evisaValidTo; ?></div></div>
            <div class="field-row"><div class="f-lbl">Трајање боравка (дана) / Duration of Stay (days)</div><div class="f-val"><?php echo $durationDays !== '' ? $durationDays : '—'; ?></div></div>
            <div class="field-row"><div class="f-lbl">Број уноса / Number of Entries</div><div class="f-val"><?php echo $entriesDisplay !== '' ? htmlspecialchars($entriesDisplay, ENT_QUOTES, 'UTF-8') : '—'; ?></div></div>
            <div class="field-row"><div class="f-lbl">Категорија / Category</div><div class="f-val"><?php echo htmlspecialchars($categoryDisplay, ENT_QUOTES, 'UTF-8'); ?></div></div>
            <div class="field-row"><div class="f-lbl">Број одлуке / Decision Number</div><div class="f-val"><?php echo $decisionNumber; ?></div></div>
            <div class="field-row"><div class="f-lbl">Датум одлуке / Decision Date</div><div class="f-val"><?php echo $decisionDate; ?></div></div>
            <div class="field-row"><div class="f-lbl">Е-виза ИД / E-Visa ID</div><div class="f-val"><?php echo $evisaId; ?></div></div>
            <div class="field-row"><div class="f-lbl">Код за верификацију / Verification Code</div><div class="f-val"><?php echo $verificationCode; ?></div></div>

            <?php if ($mrzLine1 !== '' || $mrzLine2 !== ''): ?>
            <div class="sec-title">Machine Readable Zone (MRZ)</div>
            <div class="mrz-block">
                <?php if ($mrzLine1 !== ''): ?><?php echo htmlspecialchars($mrzLine1, ENT_QUOTES, 'UTF-8'); ?><br><?php endif; ?>
                <?php if ($mrzLine2 !== ''): ?><?php echo htmlspecialchars($mrzLine2, ENT_QUOTES, 'UTF-8'); ?><?php endif; ?>
            </div>
            <?php endif; ?>

        </div>
    </div>

    <?php endif; ?>

</div>

<?php require_once __DIR__ . '/partials/footer.php'; ?>

</body>
</html>
