<?php
require_once __DIR__ . '/auth.php';
sev_auth_start_session();

$activeNav = 'check';

$record   = null;
$errorMsg = '';
$searched = false;

/* ── POST: both passport + visa serial submitted ── */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $passport = trim((string)($_POST['passport'] ?? ''));
    $evisa    = trim((string)($_POST['evisa']    ?? ''));

    if ($passport === '' || $evisa === '') {
        $errorMsg = 'Both fields are required.';
    } else {
        $searched = true;
        try {
            $record = sev_find_by_passport_and_evisa($passport, $evisa);
        } catch (Throwable $err) {
            $errorMsg = 'Database error. Please try again later.';
        }
        if ($record === null && $errorMsg === '') {
            $errorMsg = 'No record found. Please check your passport number and visa serial number and try again.';
        }
        /* ── Found → redirect to the full visa view page ── */
        if ($record !== null) {
            $passportRaw = trim((string)($record['passport_number'] ?? ''));
            $evisaRaw    = trim((string)($record['visa_number']     ?? ''));
            header('Location: ./track-your-visa-application.php'
                . '?passport=' . rawurlencode($passportRaw)
                . '&evisa='    . rawurlencode($evisaRaw));
            exit;
        }
    }
}

/* ── Extract fields ── */
$rm = [];
if ($record !== null) {
    $rm = json_decode((string)($record['remarks'] ?? ''), true);
    if (!is_array($rm)) $rm = [];
}

$applicationId     = sev_h(trim((string)($rm['applicationId']       ?? '')));
$dateOfApplication = sev_fmt_date((string)($rm['dateOfApplication']  ?? ''));
$surname           = sev_h(strtoupper(trim((string)($record['surname']         ?? ''))));
$givenName         = sev_h(strtoupper(trim((string)($record['name']            ?? ''))));
$dateOfBirth       = sev_fmt_date((string)($record['birthday']        ?? ''));
$sex               = sev_h(strtoupper(trim((string)($record['gender']          ?? ''))));
$nationality       = sev_h(strtoupper(trim((string)($record['nationality']     ?? ''))));
$passportNo        = sev_h(strtoupper(trim((string)($record['passport_number'] ?? ''))));
$evisaValidFrom    = sev_fmt_date((string)($record['issue_date']      ?? ''));
$evisaValidTo      = sev_fmt_date((string)($record['evisa_expire_date'] ?? ''));
$durationDays      = sev_h(trim((string)($rm['durationDays']          ?? ($record['visa_fee'] ?? ''))));
$numberOfEntries   = sev_h(trim((string)($rm['numberOfEntries']       ?? '')));
$category          = strtoupper(trim((string)($rm['category']          ?? ($record['visa_type'] ?? ''))));
$decisionNumber    = sev_h(trim((string)($record['ref_number']        ?? '')));
$decisionDate      = sev_fmt_date((string)($rm['decisionDate']        ?? ''));
$evisaId           = sev_h(trim((string)($record['visa_number']       ?? '')));
$verificationCode  = sev_h(trim((string)($rm['verificationCode']      ?? '')));
$photo             = trim((string)($record['applicant_image']          ?? ''));

$categoryDisplay = $category;
if ($category === 'WORK PERMIT') $categoryDisplay = 'ДОЗВОЛА ЗА РАД / WORK PERMIT';

$entriesDisplay = strtoupper($numberOfEntries);
if ($entriesDisplay === 'MULTIPLES' || $entriesDisplay === 'MULTIPLE') $entriesDisplay = 'МУЛТИПЛЕ / MULTIPLES';
elseif ($entriesDisplay === 'SINGLE') $entriesDisplay = 'ЈЕДИНИЧНО / SINGLE';

$mrzLine1 = trim((string)($rm['mrzLine1'] ?? ''));
$mrzLine2 = trim((string)($rm['mrzLine2'] ?? ''));
if ($mrzLine1 === '' && $mrzLine2 === '' && isset($rm['mrzLine'])) {
    $parts    = explode("\n", (string)$rm['mrzLine']);
    $mrzLine1 = trim($parts[0] ?? '');
    $mrzLine2 = trim($parts[1] ?? '');
}

$photoUrl = '';
if ($photo !== '') {
    $photoUrl = '../evisa/api/uploads/applicants/' . rawurlencode($photo);
}

/* Keep form values for re-display */
$fPassport = sev_h(trim((string)($_POST['passport'] ?? '')));
$fEvisa    = sev_h(trim((string)($_POST['evisa']    ?? '')));
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Track Your Visa Application – Serbia eVisa<?php echo $record !== null ? ' — ' . $evisaId : ''; ?></title>
    <style>
        *,
        *::before,
        *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f0f2f5;
            color: #1b2a45;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        a {
            text-decoration: none;
            color: inherit;
        }

        img {
            display: block;
            max-width: 100%;
        }

        /* ── Page wrap ── */
        .page-wrap {
            flex: 1;
            padding: 40px 120px 60px;
            margin: 0 auto;
            width: 100%;
        }

        /* ── Form card ── */
        .track-card {
            
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

        .track-title svg {
            flex-shrink: 0;
        }

        /* ── Field group ── */
        .field-group {
            margin-bottom: 22px;
        }

        .field-group label {
            display: block;
            font-size: 14px;
            color: #1b2a45;
            font-weight: 500;
            margin-bottom: 8px;
        }

        .field-group label span.req {
            color: #1b2a45;
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
            transition: border-color .15s;
        }

        .field-group input:focus {
            border-color: #1b2a45;
            box-shadow: 0 0 0 2px rgba(27, 42, 69, .1);
        }

        /* ── Mandatory note + button row ── */
        .form-footer-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 12px;
            margin-top: 8px;
        }

        .mandatory-note {
            font-size: 13px;
            color: #374151;
        }

        .btn-check {
            background: #1b2a45;
            color: #fff;
            border: none;
            border-radius: 6px;
            padding: 13px 32px;
            font-size: 15px;
            font-weight: 700;
            cursor: pointer;
            letter-spacing: .01em;
            transition: background .15s;
        }

        .btn-check:hover {
            background: #243857;
        }

        /* ── Alerts ── */
        .alert-err {
            background: #fef2f2;
            border: 1px solid #fca5a5;
            color: #b91c1c;
            border-radius: 6px;
            padding: 13px 16px;
            margin-bottom: 24px;
            font-size: 14px;
        }

        .alert-ok {
            background: #f0fdf4;
            border: 1px solid #86efac;
            color: #166534;
            border-radius: 8px;
            padding: 12px 18px;
            margin-bottom: 16px;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }

        /* ── Verified banner ── */
        .verified-banner {
            background: linear-gradient(135deg, #166534, #15803d);
            color: #fff;
            border-radius: 12px 12px 0 0;
            padding: 18px 28px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 12px;
            margin-top: 28px;
        }

        .verified-banner .badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 20px;
            font-weight: 800;
        }

        .verified-banner .evisa-code {
            font-size: 14px;
            opacity: .88;
        }

        /* ── Document card ── */
        .doc-card {
            background: #fff;
            border: 1px solid #e5e7eb;
            border-top: none;
            border-radius: 0 0 12px 12px;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0, 0, 0, .07);
        }

        .doc-header-strip img {
            width: 100%;
        }

        .doc-title-bar {
            text-align: center;
            padding: 10px 16px;
            background: #fff;
            border-bottom: 1px solid #e5e7eb;
        }

        .doc-title-sr {
            font-weight: 800;
            font-size: 14px;
            letter-spacing: .3px;
            color: #1b2a45;
        }

        .doc-title-en {
            font-weight: 700;
            font-size: 13px;
            color: #1b2a45;
        }

        .doc-body {
            padding: 16px 24px 20px;
        }

        .sec-title {
            font-size: 12px;
            font-weight: 800;
            color: #1e3a8a;
            border-bottom: 2px solid #bfdbfe;
            padding-bottom: 4px;
            margin: 14px 0 10px;
            text-transform: uppercase;
            letter-spacing: .4px;
        }

        .app-row {
            display: flex;
            gap: 20px;
            margin-bottom: 14px;
        }

        .app-fields {
            flex: 1;
            min-width: 0;
        }

        .app-photo-wrap {
            flex-shrink: 0;
            width: 120px;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 6px;
        }

        .app-photo-wrap img {
            width: 120px;
            height: 155px;
            object-fit: cover;
            border: 1px solid #d1d5db;
            border-radius: 4px;
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
            text-align: center;
        }

        .field-row {
            display: flex;
            align-items: baseline;
            padding: 5px 0;
            border-bottom: 1px solid #f3f4f6;
            gap: 12px;
            font-size: 13px;
        }

        .field-row:last-child {
            border-bottom: none;
        }

        .f-lbl {
            min-width: 220px;
            color: #64748b;
            font-size: 12px;
            line-height: 1.4;
            flex-shrink: 0;
        }

        .f-val {
            font-weight: 700;
            color: #111827;
            word-break: break-word;
        }

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
            word-break: break-all;
        }

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
        }

        .print-btn:hover {
            background: #155da0;
        }

        @media (max-width: 640px) {
            .track-card {
                padding: 28px 20px 24px;
            }

            .field-group input {
                max-width: 100%;
            }

            .form-footer-row {
                flex-direction: column;
                align-items: flex-start;
            }

            .btn-check {
                width: 100%;
                text-align: center;
            }

            .app-row {
                flex-direction: column;
            }

            .app-photo-wrap {
                width: 100%;
                flex-direction: row;
            }

            .f-lbl {
                min-width: 160px;
            }

            .verified-banner {
                flex-direction: column;
            }
        }

        @media print {

            .imm-navbar-wrap,
            .print-btn,
            .alert-ok,
            .track-card {
                display: none !important;
            }

            body {
                background: #fff;
            }

            .doc-card {
                box-shadow: none;
                border-radius: 0;
            }

            .verified-banner {
                border-radius: 0;
            }
        }
    </style>
</head>

<body>

    <?php require __DIR__ . '/partials/navbar.php'; ?>

    <div class="page-wrap">

        <!-- ── Search form (always shown; hides after result) ── -->
        <?php if ($record === null): ?>
            <div class="track-card">
                <h1 class="track-title">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none">
                        <circle cx="11" cy="11" r="7" stroke="#1b2a45" stroke-width="2" />
                        <line x1="16.5" y1="16.5" x2="21" y2="21" stroke="#1b2a45" stroke-width="2" stroke-linecap="round" />
                    </svg>
                    Check Visa Status
                </h1>

                <?php if ($errorMsg !== ''): ?>
                    <div class="alert-err">&#x26A0; <?php echo sev_h($errorMsg); ?></div>
                <?php endif; ?>

                <form method="POST" action="">
                    <div class="field-group">
                        <label for="passport">Passport number: <span class="req">*</span></label>
                        <input
                            type="text"
                            id="passport"
                            name="passport"
                            value="<?= $fPassport ?>"
                            autocomplete="off"
                            required>
                    </div>
                    <div class="field-group">
                        <label for="evisa">Visa serial number: <span class="req">*</span></label>
                        <input
                            type="text"
                            id="evisa"
                            name="evisa"
                            value="<?= $fEvisa ?>"
                            autocomplete="off"
                            required>
                    </div>
                    <div class="form-footer-row">
                        <span class="mandatory-note">All fields marked with * are mandatory</span>
                        <button type="submit" class="btn-check">Check Status</button>
                    </div>
                </form>
            </div>
        <?php endif; ?>

        <?php if ($record !== null): ?>
            <!-- ── Result ── -->
            <div class="alert-ok">
                <span>&#x2705;</span>
                <span>This eVisa is <strong>verified</strong> and was found in the official Serbia eVisa database.</span>
                <button class="print-btn" onclick="window.print()">&#x1F5A8; Print</button>
            </div>

            <div class="verified-banner">
                <div class="badge">&#x2705; VERIFIED — REPUBLIC OF SERBIA</div>
                <div class="evisa-code">eVisa ID: <?= $evisaId ?> &nbsp;|&nbsp; Passport: <?= $passportNo ?></div>
            </div>

            <div class="doc-card">
                <div class="doc-header-strip">
                    <img src="images/top.png" alt="Serbia eVisa Header">
                </div>
                <div class="doc-title-bar">
                    <div class="doc-title-sr">ОБАВЕШТЕЊЕ О ДОДАВАЊУ Е-ВИЗЕ</div>
                    <div class="doc-title-en">NOTIFICATION OF GRANTING AN E-VISA</div>
                </div>
                <div class="doc-body">

                    <div class="sec-title">Детали апликације / Application Details</div>
                    <div class="field-row">
                        <div class="f-lbl">ИД апликације / Application ID</div>
                        <div class="f-val"><?= $applicationId !== '' ? $applicationId : '—' ?></div>
                    </div>
                    <div class="field-row">
                        <div class="f-lbl">Датум подношења захтева / Date of Application</div>
                        <div class="f-val"><?= $dateOfApplication ?></div>
                    </div>

                    <div class="sec-title">Лични подаци / Personal Details</div>
                    <div class="app-row">
                        <div class="app-fields">
                            <div class="field-row">
                                <div class="f-lbl">Презиме / Surname</div>
                                <div class="f-val"><?= $surname ?></div>
                            </div>
                            <div class="field-row">
                                <div class="f-lbl">Ime / Given Name(s)</div>
                                <div class="f-val"><?= $givenName ?></div>
                            </div>
                            <div class="field-row">
                                <div class="f-lbl">Датум рођења / Date of Birth</div>
                                <div class="f-val"><?= $dateOfBirth ?></div>
                            </div>
                            <div class="field-row">
                                <div class="f-lbl">Сек / Sex</div>
                                <div class="f-val"><?= $sex ?></div>
                            </div>
                            <div class="field-row">
                                <div class="f-lbl">националност / Nationality</div>
                                <div class="f-val"><?= $nationality ?></div>
                            </div>
                            <div class="field-row">
                                <div class="f-lbl">Број путне исправе / Travel Document Number</div>
                                <div class="f-val"><?= $passportNo ?></div>
                            </div>
                        </div>
                        <div class="app-photo-wrap">
                            <?php if ($photoUrl !== ''): ?>
                                <img src="<?= sev_h($photoUrl) ?>" alt="Applicant Photo">
                            <?php else: ?>
                                <div class="no-photo">No Photo</div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="sec-title">Детали о е-визу / E-Visa Details</div>
                    <div class="field-row">
                        <div class="f-lbl">Важење е-визе / E-Visa Validity</div>
                        <div class="f-val"><?= $evisaValidFrom ?> &nbsp;—&nbsp; <?= $evisaValidTo ?></div>
                    </div>
                    <div class="field-row">
                        <div class="f-lbl">Трајање боравка (дана) / Duration of Stay (days)</div>
                        <div class="f-val"><?= $durationDays !== '' ? $durationDays : '—' ?></div>
                    </div>
                    <div class="field-row">
                        <div class="f-lbl">Број уноса / Number of Entries</div>
                        <div class="f-val"><?= $entriesDisplay !== '' ? htmlspecialchars($entriesDisplay, ENT_QUOTES, 'UTF-8') : '—' ?></div>
                    </div>
                    <div class="field-row">
                        <div class="f-lbl">Категорија / Category</div>
                        <div class="f-val"><?= htmlspecialchars($categoryDisplay, ENT_QUOTES, 'UTF-8') ?></div>
                    </div>
                    <div class="field-row">
                        <div class="f-lbl">Број одлуке / Decision Number</div>
                        <div class="f-val"><?= $decisionNumber ?></div>
                    </div>
                    <div class="field-row">
                        <div class="f-lbl">Датум одлуке / Decision Date</div>
                        <div class="f-val"><?= $decisionDate ?></div>
                    </div>
                    <div class="field-row">
                        <div class="f-lbl">Е-виза ИД / E-Visa ID</div>
                        <div class="f-val"><?= $evisaId ?></div>
                    </div>
                    <div class="field-row">
                        <div class="f-lbl">Код за верификацију / Verification Code</div>
                        <div class="f-val"><?= $verificationCode ?></div>
                    </div>

                    <?php if ($mrzLine1 !== '' || $mrzLine2 !== ''): ?>
                        <div class="sec-title">Machine Readable Zone (MRZ)</div>
                        <div class="mrz-block">
                            <?php if ($mrzLine1 !== ''): ?><?= sev_h($mrzLine1) ?><br><?php endif; ?>
                        <?php if ($mrzLine2 !== ''): ?><?= sev_h($mrzLine2) ?><?php endif; ?>
                        </div>
                    <?php endif; ?>

                </div>
            </div>

            <p style="margin-top:18px;">
                <a href="./track-your-visa-application.php" style="color:#1a6bbf;font-weight:600;font-size:14px;">&#8592; Search again</a>
            </p>
        <?php endif; ?>

    </div>

    <?php require __DIR__ . '/partials/footer.php'; ?>

</body>

</html>