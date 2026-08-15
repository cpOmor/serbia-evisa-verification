<?php
require_once __DIR__ . '/auth.php';
sev_auth_start_session();

$base      = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
$indexPath = $base . '/index.php';

$record      = null;
$errorMsg    = '';
$showForm    = true;

/* ── 1. QR-scan: ?passport=...&evisa=... ── */
if (isset($_GET['passport'], $_GET['evisa'])) {
    $p = trim((string)$_GET['passport']);
    $e = trim((string)$_GET['evisa']);
    try {
        $record = sev_find_by_passport_and_evisa($p, $e);
    } catch (Throwable $err) {
        $errorMsg = 'Database error. Please try again later.';
    }
    if ($record === null && $errorMsg === '') {
        $errorMsg = 'No matching record found for the provided passport and eVisa ID.';
    }
    $showForm = false;

    /* ── 2. Keyword search: ?keyword=... or POST keyword ── */
} elseif (isset($_GET['keyword']) || $_SERVER['REQUEST_METHOD'] === 'POST') {
    $kw = trim((string)($_POST['keyword'] ?? $_GET['keyword'] ?? ''));
    if ($kw === '') {
        $errorMsg = 'Please enter a passport number or eVisa ID.';
        $showForm = true;
    } else {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $loc = (str_contains($_SERVER['REQUEST_URI'] ?? '', '?') ? strtok($_SERVER['REQUEST_URI'], '?') : ($_SERVER['REQUEST_URI'] ?? 'check-status.php'))
                . '?keyword=' . urlencode($kw);
            header('Location: ' . $loc);
            exit;
        }
        try {
            $record = sev_find_by_keyword($kw);
        } catch (Throwable $err) {
            $errorMsg = 'Database error. Please try again later.';
        }
        if ($record === null && $errorMsg === '') {
            $errorMsg = 'No record found. Please check the details and try again.';
        }
        $showForm = false;
    }
}

/* ── Extract fields ── */
$rm = [];
if ($record !== null) {
    $rm = json_decode((string)($record['remarks'] ?? ''), true);
    if (!is_array($rm)) $rm = [];
}

$applicationId    = sev_h(trim((string)($rm['applicationId']      ?? '')));
$dateOfApplication = sev_fmt_date((string)($rm['dateOfApplication'] ?? ''));
$surname          = sev_h(strtoupper(trim((string)($record['surname']          ?? ''))));
$givenName        = sev_h(strtoupper(trim((string)($record['name']             ?? ''))));
$dateOfBirth      = sev_fmt_date((string)($record['birthday']         ?? ''));
$sex              = sev_h(strtoupper(trim((string)($record['gender']           ?? ''))));
$nationality      = sev_h(strtoupper(trim((string)($record['nationality']      ?? ''))));
$passportNo       = sev_h(strtoupper(trim((string)($record['passport_number']  ?? ''))));
$evisaValidFrom   = sev_fmt_date((string)($record['issue_date']       ?? ''));
$evisaValidTo     = sev_fmt_date((string)($record['evisa_expire_date'] ?? ''));
$durationDays     = sev_h(trim((string)($rm['durationDays']           ?? ($record['visa_fee'] ?? ''))));
$numberOfEntries  = sev_h(trim((string)($rm['numberOfEntries']        ?? '')));
$category         = strtoupper(trim((string)($rm['category']           ?? ($record['visa_type'] ?? ''))));
$decisionNumber   = sev_h(trim((string)($record['ref_number']         ?? '')));
$decisionDate     = sev_fmt_date((string)($rm['decisionDate']         ?? ''));
$evisaId          = sev_h(trim((string)($record['visa_number']        ?? '')));
$verificationCode = sev_h(trim((string)($rm['verificationCode']       ?? '')));
$photo            = trim((string)($record['applicant_image']           ?? ''));

// Category / entries display
$categoryDisplay = $category;
if ($category === 'WORK PERMIT') $categoryDisplay = 'ДОЗВОЛА ЗА РАД / WORK PERMIT';
$entriesDisplay = strtoupper($numberOfEntries);
if ($entriesDisplay === 'MULTIPLES' || $entriesDisplay === 'MULTIPLE') $entriesDisplay = 'МУЛТИПЛЕ / MULTIPLES';
elseif ($entriesDisplay === 'SINGLE') $entriesDisplay = 'ЈЕДИНИЧНО / SINGLE';

// MRZ
$mrzLine1 = trim((string)($rm['mrzLine1'] ?? ''));
$mrzLine2 = trim((string)($rm['mrzLine2'] ?? ''));
if ($mrzLine1 === '' && $mrzLine2 === '' && isset($rm['mrzLine'])) {
    $parts = explode("\n", (string)$rm['mrzLine']);
    $mrzLine1 = trim($parts[0] ?? '');
    $mrzLine2 = trim($parts[1] ?? '');
}
 
$photoUrl = 'https://visaguro.com/api/uploads/applicants/'  . rawurlencode($photo);

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Serbia eVisa – Verification<?php echo $record !== null ? ' — ' . $evisaId : ''; ?></title>
    <style>
        *,
        *::before,
        *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f0f4fa;
            color: #1b2a45;
            min-height: 100vh;
            display: flex;
            flex-direction: column
        }

        a {
            text-decoration: none;
            color: inherit
        }

        img {
            display: block;
            max-width: 100%
        }


        /* ── Page wrap ── */
        .page-wrap {
            flex: 1;
            padding: 32px 20px;
            max-width: 900px;
            margin: 0 auto;
            width: 100%
        }

        /* ── Search form ── */
        .search-box {
            background: #fff;
            border-radius: 12px;
            padding: 36px;
            box-shadow: 0 2px 12px rgba(0, 0, 0, .08);
            margin-bottom: 24px
        }

        .search-box h1 {
            font-size: 22px;
            font-weight: 800;
            margin-bottom: 6px
        }

        .search-box p {
            color: #6b7280;
            font-size: 14px;
            margin-bottom: 20px
        }

        .form-row {
            display: flex;
            gap: 10px
        }

        .form-row input {
            flex: 1;
            border: 1.5px solid #d1d5db;
            border-radius: 8px;
            padding: 12px 14px;
            font-size: 15px;
            color: #1b2a45;
            outline: none
        }

        .form-row input:focus {
            border-color: #1a6bbf
        }

        .form-row button {
            background: #c0392b;
            color: #fff;
            border: none;
            border-radius: 8px;
            padding: 12px 22px;
            font-size: 15px;
            font-weight: 700;
            cursor: pointer
        }

        .form-row button:hover {
            background: #a93226
        }

        /* ── Error / alert ── */
        .alert-err {
            background: #fef2f2;
            border: 1px solid #fca5a5;
            color: #b91c1c;
            border-radius: 8px;
            padding: 14px 18px;
            margin-bottom: 20px;
            font-size: 15px
        }

        .alert-ok {
            background: #f0fdf4;
            border: 1px solid #86efac;
            color: #166534;
            border-radius: 8px;
            padding: 10px 18px;
            margin-bottom: 16px;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 8px
        }

        /* ── Verified badge ── */
        .verified-banner {
            background: linear-gradient(135deg, #166534, #15803d);
            color: #fff;
            border-radius: 12px 12px 0 0;
            padding: 18px 28px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 12px
        }

        .verified-banner .badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 20px;
            font-weight: 800
        }

        .verified-banner .badge span.icon {
            font-size: 26px
        }

        .verified-banner .evisa-code {
            font-size: 14px;
            opacity: .88
        }

        /* ── Document card ── */
        .doc-card {
            background: #fff;
            border: 1px solid #e5e7eb;
            border-top: none;
            border-radius: 0 0 12px 12px;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0, 0, 0, .07)
        }

        /* ── Doc header strip ── */
        .doc-header-strip {
            padding: 0
        }

        .doc-header-strip img {
            width: 100%
        }

        /* ── Title bar ── */
        .doc-title-bar {
            text-align: center;
            padding: 10px 16px;
            background: #fff;
            border-bottom: 1px solid #e5e7eb
        }

        .doc-title-sr {
            font-weight: 800;
            font-size: 14px;
            letter-spacing: .3px;
            color: #1b2a45
        }

        .doc-title-en {
            font-weight: 700;
            font-size: 13px;
            color: #1b2a45
        }

        /* ── Body ── */
        .doc-body {
            padding: 16px 24px 20px
        }

        .sec-title {
            font-size: 12px;
            font-weight: 800;
            color: #1e3a8a;
            border-bottom: 2px solid #e5e7eb;
            padding-bottom: 4px;
            margin: 14px 0 10px;
            text-transform: uppercase;
            letter-spacing: .4px
        }

        .app-row {
            display: flex;
            gap: 20px;
            margin-bottom: 14px
        }

        .app-fields {
            flex: 1;
            min-width: 0
        }

        .app-photo-wrap {
            flex-shrink: 0;
            width: 120px;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 6px
        }

        .app-photo-wrap img {
            width: 120px;
            height: 155px;
            object-fit: cover;
            border: 1px solid #d1d5db;
            border-radius: 4px
        }

        .no-photo {
            width: 120px;
            height: 155px;
            border: 2px dashed #d1d5db;
            border-radius: 4px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #9ca3af;
            font-size: 12px;
            text-align: center
        }

        /* ── Field rows ── */
        .field-row {
            display: flex;
            align-items: baseline;
            padding: 5px 0;
            border-bottom: 1px solid #f3f4f6;
            gap: 12px;
            font-size: 13px
        }

        .field-row:last-child {
            border-bottom: none
        }

        .f-lbl {
            min-width: 220px;
            color: #64748b;
            font-size: 12px;
            line-height: 1.4;
            flex-shrink: 0
        }

        .f-val {
            font-weight: 700;
            color: #111827;
            word-break: break-word
        }

        /* ── MRZ ── */
        .mrz-block {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 10px 14px;
            margin-top: 14px;
            font-family: 'Courier New', monospace;
            font-size: 12px;
            letter-spacing: .5px;
            color: #334155;
            word-break: break-all
        }

        /* ── Print ── */
        .print-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: #1a6bbf;
            color: #fff;
            border: none;
            border-radius: 8px;
            padding: 10px 20px;
            font-size: 14px;
            font-weight: 700;
            cursor: pointer;
            margin-top: 16px
        }

        .print-btn:hover {
            background: #155da0
        }

        @media(max-width:640px) {
            .page-wrap {
                padding: 16px 10px
            }

            .search-box {
                padding: 20px 16px
            }

            .search-box h1 {
                font-size: 18px
            }

            .doc-body {
                padding: 14px 14px 18px
            }

            .app-row {
                flex-direction: column-reverse;
                gap: 14px
            }

            .app-photo-wrap {
                width: 100%;
                justify-content: center;
                margin-bottom: 8px
            }

            .field-row {
                flex-direction: column;
                align-items: flex-start;
                gap: 2px;
                padding: 6px 0
            }

            .f-lbl {
                min-width: 100%;
                width: 100%;
                font-size: 11px
            }

            .f-val {
                font-size: 13px
            }

            .form-row {
                flex-direction: column
            }

            .form-row button {
                width: 100%
            }

            .verified-banner {
                flex-direction: column;
                padding: 14px 16px
            }

            .mrz-block {
                font-size: 11px;
                letter-spacing: 0;
                overflow-x: auto;
                word-break: break-all
            }
        }

        @media print {

            .navbar,
            .back-btn,
            .print-btn,
            .alert-ok {
                display: none !important
            }

            body {
                background: #fff
            }

            .doc-card {
                box-shadow: none;
                border-radius: 0
            }

            .verified-banner {
                border-radius: 0
            }
        }
    </style>
</head>

<body>

    <?php $activeNav = 'check';
    require_once __DIR__ . '/partials/navbar.php'; ?>

    <div class="page-wrap">

        <?php if ($showForm): ?>
            <!-- ── Search form ── -->
            <div class="search-box">
                <h1>&#x1F50D; Verify eVisa Status</h1>
                <p>Enter your passport number or eVisa ID to verify the status of your Serbia eVisa.</p>
                <?php if ($errorMsg !== ''): ?>
                    <div class="alert-err">&#x26A0;&#xFE0F; <?php echo sev_h($errorMsg); ?></div>
                <?php endif; ?>
                <form method="POST">
                    <div class="form-row">
                        <input type="text" name="keyword" placeholder="Passport number or eVisa ID" required autocomplete="off">
                        <button type="submit">Verify</button>
                    </div>
                </form>
            </div>

        <?php elseif ($errorMsg !== ''): ?>
            <!-- ── Error ── -->
            <div class="alert-err">&#x26A0;&#xFE0F; <?php echo sev_h($errorMsg); ?></div>
            <div class="search-box">
                <h1>&#x1F50D; Try Again</h1>
                <p>Check the passport number or eVisa ID and search again.</p>
                <form method="POST">
                    <div class="form-row">
                        <input type="text" name="keyword"
                            value="<?php echo sev_h((string)($_GET['keyword'] ?? $_GET['passport'] ?? '')); ?>"
                            placeholder="Passport number or eVisa ID" required autocomplete="off">
                        <button type="submit">Verify</button>
                    </div>
                </form>
            </div>

        <?php elseif ($record !== null): ?>
            

            <div class="doc-card">
                <!-- Header image -->
                <div class="doc-header-strip">
                    <img src="images/top.png" alt="Serbia eVisa Header">
                </div>

                <!-- Title -->
                <div class="doc-title-bar">
                    <div class="doc-title-sr">ОБАВЕШТЕЊЕ О ДОДАВАЊУ Е-ВИЗЕ</div>
                    <div class="doc-title-en">NOTIFICATION OF GRANTING AN E-VISA</div>
                </div>

                <div class="doc-body">

                    <!-- Application Details -->
                    <div class="sec-title">Детали апликације / Application Details</div>
                    <div class="field-row">
                        <div class="f-lbl">ИД апликације / Application ID</div>
                        <div class="f-val"><?php echo $applicationId !== '' ? $applicationId : '—'; ?></div>
                    </div>
                    <div class="field-row">
                        <div class="f-lbl">Датум подношења захтева / Date of Application</div>
                        <div class="f-val"><?php echo $dateOfApplication; ?></div>
                    </div>

                    <!-- Personal Details -->
                    <div class="sec-title">Лични подаци / Personal Details</div>
                    <div class="app-row">
                        <div class="app-fields">
                            <div class="field-row">
                                <div class="f-lbl">Презиме / Surname</div>
                                <div class="f-val"><?php echo $surname; ?></div>
                            </div>
                            <div class="field-row">
                                <div class="f-lbl">Ime / Given Name(s)</div>
                                <div class="f-val"><?php echo $givenName; ?></div>
                            </div>
                            <div class="field-row">
                                <div class="f-lbl">Датум рођења / Date of Birth</div>
                                <div class="f-val"><?php echo $dateOfBirth; ?></div>
                            </div>
                            <div class="field-row">
                                <div class="f-lbl">Сек / Sex</div>
                                <div class="f-val"><?php echo $sex; ?></div>
                            </div>
                            <div class="field-row">
                                <div class="f-lbl">националност / Nationality</div>
                                <div class="f-val"><?php echo $nationality; ?></div>
                            </div>
                            <div class="field-row">
                                <div class="f-lbl">Број путне исправе / Travel Document Number</div>
                                <div class="f-val"><?php echo $passportNo; ?></div>
                            </div>
                        </div>
                        <div class="app-photo-wrap">
                            <?php if ($photoUrl !== ''): ?>
                                <img src="<?php echo sev_h($photoUrl); ?>" alt="Applicant Photo">
                            <?php else: ?>
                                <div class="no-photo">No Photo</div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- eVisa Details -->
                    <div class="sec-title">Детали о е-визу / E-Visa Details</div>
                    <div class="field-row">
                        <div class="f-lbl">Важење е-визе / E-Visa Validity</div>
                        <div class="f-val"><?php echo $evisaValidFrom; ?> &nbsp;—&nbsp; <?php echo $evisaValidTo; ?></div>
                    </div>
                    <div class="field-row">
                        <div class="f-lbl">Трајање боравка (дана) / Duration of Stay (days)</div>
                        <div class="f-val"><?php echo $durationDays !== '' ? $durationDays : '—'; ?></div>
                    </div>
                    <div class="field-row">
                        <div class="f-lbl">Број уноса / Number of Entries</div>
                        <div class="f-val"><?php echo $entriesDisplay !== '' ? htmlspecialchars($entriesDisplay, ENT_QUOTES, 'UTF-8') : '—'; ?></div>
                    </div>
                    <div class="field-row">
                        <div class="f-lbl">Категорија / Category</div>
                        <div class="f-val"><?php echo htmlspecialchars($categoryDisplay, ENT_QUOTES, 'UTF-8'); ?></div>
                    </div>
                    <div class="field-row">
                        <div class="f-lbl">Број одлуке / Decision Number</div>
                        <div class="f-val"><?php echo $decisionNumber; ?></div>
                    </div>
                    <div class="field-row">
                        <div class="f-lbl">Датум одлуке / Decision Date</div>
                        <div class="f-val"><?php echo $decisionDate; ?></div>
                    </div>
                    <div class="field-row">
                        <div class="f-lbl">Е-виза ИД / E-Visa ID</div>
                        <div class="f-val"><?php echo $evisaId; ?></div>
                    </div>
                    <div class="field-row">
                        <div class="f-lbl">Код за верификацију / Verification Code</div>
                        <div class="f-val"><?php echo $verificationCode; ?></div>
                    </div>

                    <!-- MRZ -->
                    <?php if ($mrzLine1 !== '' || $mrzLine2 !== ''): ?>
                        <div class="sec-title">Machine Readable Zone (MRZ)</div>
                        <div class="mrz-block">
                            <?php if ($mrzLine1 !== ''): ?><?php echo sev_h($mrzLine1); ?><br><?php endif; ?>
                        <?php if ($mrzLine2 !== ''): ?><?php echo sev_h($mrzLine2); ?><?php endif; ?>
                        </div>
                    <?php endif; ?>

                </div><!-- /.doc-body -->
            </div><!-- /.doc-card -->

        <?php endif; ?>

    </div><!-- /.page-wrap -->

    <?php require_once __DIR__ . '/partials/footer.php'; ?>

</body>

</html>