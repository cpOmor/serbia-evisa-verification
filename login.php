<?php
require_once __DIR__ . '/auth.php';
sev_auth_start_session();

if (sev_auth_is_logged_in()) {
    header('Location: ./my-visa.php');
    exit;
}

$error = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim((string) ($_POST['email']    ?? ''));
    $password = (string) ($_POST['password'] ?? '');

    if ($email === '' || $password === '') {
        $error = 'Email and password are required.';
    } else {
        $result = sev_auth_login($email, $password);
        if ($result['ok']) {
            header('Location: ./my-visa.php');
            exit;
        } else {
            $error = $result['error'];
        }
    }
}

$success = trim((string)($_GET['registered'] ?? ''));
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login – Serbia eVisa Verification Portal</title>
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
            color: #222;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        a {
            text-decoration: none;
            color: inherit;
        }

        .eid-topbar {
            background: #1b2a45;
            height: 36px;
            display: flex;
            align-items: center;
            justify-content: flex-end;
            padding: 0 28px;
        }

        .eid-topbar-lang {
            font-size: 13px;
            color: #a0b8d8;
            display: flex;
            align-items: center;
            gap: 6px;
            cursor: pointer;
        }

        .eid-header {
            background: #fff;
            border-bottom: 1px solid #dde2ea;
            padding: 14px 28px 0;
        }

        .eid-header-top {
            display: flex;
            align-items: center;
            gap: 14px;
            margin-bottom: 12px;
        }

        .eid-logo-icon {
            width: 42px;
            height: 42px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .eid-logo-text h1 {
            font-size: 18px;
            font-weight: 700;
            color: #1b2a45;
            line-height: 1.2;
        }

        .eid-logo-text p {
            font-size: 12px;
            color: #6b7280;
            margin-top: 1px;
        }

        .eid-nav {
            display: flex;
            gap: 0;
        }

        .eid-nav a {
            font-size: 18px;
            color: #374151;
            padding: 10px 16px;
            display: inline-block;
            border-bottom: 3px solid transparent;
            font-weight: 500;
        }

        .eid-nav a:hover {
            color: #1b2a45;
            border-bottom-color: #1b2a45;
        }

        .eid-page {
            flex: 1;
            padding: 0 28px 60px;
            background: #f0f2f5;
        }

        .eid-page-title {
            font-size: 22px;
            font-weight: 700;
            color: #1b2a45;
            padding: 24px 0 20px;
        }

        .eid-tabs {
            display: flex;
            gap: 0;
            border: 1px solid #c8d3e0;
            border-radius: 6px 6px 0 0;
            overflow: hidden;
            margin-bottom: 0;
        }

        .eid-tab {
            flex: 1;
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 14px 20px;
            background: #fff;
            cursor: pointer;
            border-right: 1px solid #c8d3e0;
            font-size: 14px;
            color: #374151;
            font-weight: 500;
        }

        .eid-tab:last-child {
            border-right: none;
        }

        .eid-tab.active {
            background: #1b2a45;
            color: #fff;
        }

        .eid-tab svg {
            flex-shrink: 0;
            opacity: .7;
        }

        .eid-tab.active svg {
            opacity: 1;
        }

        .eid-card {
            background: #fff;
            border: 1px solid #c8d3e0;
            border-top: none;
            padding: 36px 0 32px;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .eid-person-icon {
            margin-bottom: 16px;
            color: #1b2a45;
            opacity: .7;
        }

        .eid-trust-text {
            font-size: 13px;
            color: #374151;
            margin-bottom: 28px;
            text-align: center;
        }

        .eid-trust-text a {
            color: #1d6abf;
            text-decoration: underline;
        }

        .eid-form {
            width: 100%;
            max-width: 380px;
            padding: 0 20px;
        }

        .eid-field {
            margin-bottom: 18px;
        }

        .eid-field label {
            display: block;
            font-size: 14px;
            font-weight: 600;
            color: #1b2a45;
            margin-bottom: 4px;
        }

        .eid-field .hint {
            font-size: 12px;
            color: #6b7280;
            margin-bottom: 6px;
        }

        .eid-field input[type=email],
        .eid-field input[type=password],
        .eid-field input[type=text] {
            width: 100%;
            padding: 10px 12px;
            border: 1.5px solid #b0bec5;
            border-radius: 4px;
            font-size: 14px;
            color: #111;
            background: #fff;
        }

        .eid-field input:focus {
            outline: none;
            border-color: #1b2a45;
            box-shadow: 0 0 0 2px rgba(27, 42, 69, .1);
        }

        .eid-pw-wrap {
            position: relative;
        }

        .eid-pw-wrap input {
            padding-right: 64px;
        }

        .eid-show-btn {
            position: absolute;
            right: 0;
            top: 0;
            bottom: 0;
            padding: 0 14px;
            background: none;
            border: none;
            font-size: 11px;
            font-weight: 700;
            color: #6b7280;
            letter-spacing: .06em;
            cursor: pointer;
            text-transform: uppercase;
        }

        .eid-show-btn:hover {
            color: #1b2a45;
        }

        .btn-eid-login {
            width: 100%;
            padding: 14px;
            background: #2e7d32;
            color: #fff;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            margin-top: 8px;
            margin-bottom: 14px;
        }

        .btn-eid-login:hover {
            background: #256328;
        }

        .eid-register-link {
            text-align: center;
            font-size: 13px;
            color: #6b7280;
            margin-top: 6px;
        }

        .eid-register-link a {
            color: #1d6abf;
            font-weight: 600;
            text-decoration: underline;
        }

        .eid-error {
            background: #fef2f2;
            border: 1px solid #fecaca;
            border-radius: 6px;
            padding: 10px 14px;
            color: #991b1b;
            font-size: 13px;
            margin-bottom: 16px;
        }

        .eid-success {
            background: #f0fdf4;
            border: 1px solid #86efac;
            border-radius: 6px;
            padding: 10px 14px;
            color: #166534;
            font-size: 13px;
            margin-bottom: 16px;
        }

        .eid-bottom-bar {
            background: #1b2a45;
            padding: 10px 28px;
            display: flex;
            justify-content: flex-end;
        }

        .eid-top-link {
            font-size: 13px;
            color: #7eb3e8;
        }

        .eid-top-link:hover {
            color: #fff;
        }

        .eid-footer {
            background: #f8f9fb;
            border-top: 1px solid #dde2ea;
            padding: 24px 28px;
        }

        .eid-footer-top {
            display: flex;
            align-items: flex-start;
            gap: 16px;
            margin-bottom: 12px;
        }

        .eid-footer-text {
            font-size: 12px;
            color: #6b7280;
            line-height: 1.6;
            margin-top: 40px;
        }

        .eid-footer-text a {
            color: #1d6abf;
        }

        .eid-footer-links {
            display: flex;
            gap: 0;
            font-size: 12px;
        }

        .eid-footer-links a {
            color: #374151;
            padding: 0 10px;
            border-right: 1px solid #c8d3e0;
        }

        .eid-footer-links a:first-child {
            padding-left: 0;
        }

        .eid-footer-links a:last-child {
            border-right: none;
        }

        .eid-footer-links a:hover {
            color: #1b2a45;
            text-decoration: underline;
        }

        @media (max-width: 600px) {
            .eid-tabs {
                flex-direction: column;
            }

            .eid-tab {
                border-right: none;
                border-bottom: 1px solid #c8d3e0;
            }

            .eid-header {
                padding: 12px 16px 0;
            }

            .eid-footer {
                padding: 16px;
            }

            .eid-footer-top {
                flex-direction: column;
                gap: 12px;
            }

            .eid-footer-links {
                flex-wrap: wrap;
                gap: 6px;
            }

            .eid-footer-links a {
                border-right: none;
                padding: 2px 4px;
            }
        }
    </style>
</head>

<body>

    <!-- Topbar -->
    <div class="eid-topbar">
        <div class="eid-topbar-lang">
            Cyrillic
            <svg width="12" height="12" viewBox="0 0 12 12" fill="none">
                <path d="M3 4.5L6 7.5L9 4.5" stroke="#a0b8d8" stroke-width="1.5" stroke-linecap="round" />
            </svg>
        </div>
    </div>

    <!-- Header -->
    <div class="eid-header">
        <div class="eid-header-top">
            <div class="eid-logo-icon">
                <svg xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:cc="http://creativecommons.org/ns#" xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#" xmlns:svg="http://www.w3.org/2000/svg" xmlns="http://www.w3.org/2000/svg" id="svg4179" version="1.1" viewBox="0 0 14.110937 19.049999" height="19.049999mm" width="14.110937mm">
                    <defs id="defs4173">
                        <clipPath id="clipPath3104" clipPathUnits="userSpaceOnUse">
                            <path style="clip-rule:evenodd" id="path3102" d="m 132.4412,2477.641 c -0.7243,0 -1.312,0.445 -1.3127,0.994 -7e-4,0.549 0.5863,0.995 1.312,0.996 l 15.3739,0.01 h 0.001 c 0.7243,0 1.312,-0.446 1.3127,-0.995 7e-4,-0.549 -0.587,-0.994 -1.312,-0.995 l -15.3746,-0.01 h -7e-4 z" />
                        </clipPath>
                        <clipPath id="clipPath3110" clipPathUnits="userSpaceOnUse">
                            <path id="path3108" d="M 0,0 H 1440 V 2581 H 0 Z" />
                        </clipPath>
                        <clipPath id="clipPath3088" clipPathUnits="userSpaceOnUse">
                            <path style="clip-rule:evenodd" id="path3086" d="m 132.4413,2482.641 c -0.7243,0 -1.3121,0.444 -1.3128,0.993 -7e-4,0.549 0.5863,0.994 1.312,0.995 l 15.3745,0.012 h 8e-4 c 0.725,0 1.312,-0.445 1.3127,-0.994 7e-4,-0.549 -0.5863,-0.995 -1.3113,-0.995 l -15.3752,-0.011 z" />
                        </clipPath>
                        <clipPath id="clipPath3094" clipPathUnits="userSpaceOnUse">
                            <path id="path3092" d="M 0,0 H 1440 V 2581 H 0 Z" />
                        </clipPath>
                        <clipPath id="clipPath3072" clipPathUnits="userSpaceOnUse">
                            <path style="clip-rule:evenodd" id="path3070" d="m 132.4412,2487.641 c -0.7243,0 -1.312,0.444 -1.3127,0.993 -7e-4,0.55 0.5863,0.995 1.312,0.996 l 15.3746,0.011 h 7e-4 c 0.725,0 1.312,-0.445 1.3127,-0.994 7e-4,-0.55 -0.5862,-0.995 -1.3112,-0.995 l -15.3754,-0.011 z" />
                        </clipPath>
                        <clipPath id="clipPath3078" clipPathUnits="userSpaceOnUse">
                            <path id="path3076" d="M 0,0 H 1440 V 2581 H 0 Z" />
                        </clipPath>
                        <clipPath id="clipPath3056" clipPathUnits="userSpaceOnUse">
                            <path style="clip-rule:evenodd" id="path3054" d="m 140.1267,2491.641 c -11.0187,0 -19.9879,8.959 -19.9982,19.981 -0.01,11.029 8.9537,20.007 19.9812,20.019 h 0.0192 c 11.0186,0 19.9878,-8.941 19.9981,-19.941 l 10e-4,-1.524 h -31.6037 c -0.8411,0 -1.5231,0.681 -1.5231,1.522 0,0.842 0.682,1.524 1.5231,1.524 h 28.4874 c -0.7822,8.608 -8.0579,15.374 -16.8833,15.374 h -0.0162 c -9.3481,-0.01 -16.9468,-7.622 -16.9387,-16.971 0.009,-9.343 7.6127,-16.939 16.9527,-16.939 h 0.0162 c 5.0567,0 9.8096,2.242 13.0401,6.138 0.5375,0.648 1.4966,0.737 2.1447,0.201 0.6472,-0.537 0.7364,-1.498 0.1997,-2.145 -3.8107,-4.596 -9.4174,-7.234 -15.3816,-7.239 h -0.0191 z" />
                        </clipPath>
                        <clipPath id="clipPath3062" clipPathUnits="userSpaceOnUse">
                            <path id="path3060" d="M 0,0 H 1440 V 2581 H 0 Z" />
                        </clipPath>
                    </defs>
                    <metadata id="metadata4176">
                        <rdf:RDF>
                            <cc:Work rdf:about="">
                                <dc:format>image/svg+xml</dc:format>
                                <dc:type rdf:resource="http://purl.org/dc/dcmitype/StillImage" />
                                <dc:title />
                            </cc:Work>
                        </rdf:RDF>
                    </metadata>
                    <g transform="translate(-26.962388,-39.522624)" id="layer1">
                        <g id="g3050" transform="matrix(0.35277777,0,0,-0.35277777,-15.416273,932.62929)">
                            <g clip-path="url(#clipPath3056)" id="g3052">
                                <g clip-path="url(#clipPath3062)" id="g3058">
                                    <path id="path3064" style="fill:#253965;fill-opacity:1;fill-rule:nonzero;stroke:none" d="m 115.1285,2536.641 h 50 v -50 h -50 z" />
                                </g>
                            </g>
                        </g>
                        <g id="g3066" transform="matrix(0.35277777,0,0,-0.35277777,-15.416273,932.62929)">
                            <g clip-path="url(#clipPath3072)" id="g3068">
                                <g clip-path="url(#clipPath3078)" id="g3074">
                                    <path id="path3080" style="fill:#a33943;fill-opacity:1;fill-rule:nonzero;stroke:none" d="m 126.1285,2494.641 h 28 v -12 h -28 z" />
                                </g>
                            </g>
                        </g>
                        <g id="g3082" transform="matrix(0.35277777,0,0,-0.35277777,-15.416273,932.62929)">
                            <g clip-path="url(#clipPath3088)" id="g3084">
                                <g clip-path="url(#clipPath3094)" id="g3090">
                                    <path id="path3096" style="fill:#253965;fill-opacity:1;fill-rule:nonzero;stroke:none" d="m 126.1285,2489.641 h 28 v -12 h -28 z" />
                                </g>
                            </g>
                        </g>
                        <g id="g3098" transform="matrix(0.35277777,0,0,-0.35277777,-15.416273,932.62929)">
                            <g clip-path="url(#clipPath3104)" id="g3100">
                                <g clip-path="url(#clipPath3110)" id="g3106">
                                    <path id="path3112" style="fill:#d7d8d6;fill-opacity:1;fill-rule:nonzero;stroke:none" d="m 126.1285,2484.641 h 28 v -12 h -28 z" />
                                </g>
                            </g>
                        </g>
                    </g>
                </svg>
            </div>
            <div class="eid-logo-text">
                <h2 style="color: #253965; font-size: 24px;">eID.gov.rs</h2>
                <p style="font-size: 18px; color: #253965;">Portal for electronic identification</p>
            </div>
        </div>
        <nav class="eid-nav">
            <a href="#">eCitizen</a>
            <a href="#">Cloud Signature</a>
            <a href="#">Help</a>
            <a href="./index.php">Contact</a>
            <a href="./index.php">eGovernment Portal</a>
            <a href="./index.php">Back</a>
        </nav>
    </div>

    <!-- Page body -->
    <div class="eid-page">
        <div class="eid-page-title">Application</div>

        <!-- Tabs -->
        <div class="eid-tabs">
            <div class="eid-tab active">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none">
                    <circle cx="12" cy="8" r="4" stroke="#fff" stroke-width="1.6" />
                    <path d="M4 20c0-4 3.6-7 8-7s8 3 8 7" stroke="#fff" stroke-width="1.6" stroke-linecap="round" />
                </svg>
                Username and password
            </div>
            <div class="eid-tab">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none">
                    <rect x="3" y="5" width="18" height="14" rx="2" stroke="#6b7280" stroke-width="1.6" />
                    <line x1="3" y1="10" x2="21" y2="10" stroke="#6b7280" stroke-width="1.2" />
                    <rect x="6" y="13" width="5" height="3" rx=".5" fill="#6b7280" />
                </svg>
                Qualified electronic certificate
            </div>
            <div class="eid-tab">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none">
                    <rect x="7" y="2" width="10" height="20" rx="2" stroke="#6b7280" stroke-width="1.6" />
                    <circle cx="12" cy="18" r="1" fill="#6b7280" />
                </svg>
                Mobile application
            </div>
        </div>

        <!-- Card -->
        <div class="eid-card">
            <div class="eid-person-icon">
                <svg width="56" height="56" viewBox="0 0 56 56" fill="none">
                    <circle cx="28" cy="28" r="26" stroke="#1b2a45" stroke-width="2" />
                    <circle cx="28" cy="21" r="9" stroke="#1b2a45" stroke-width="2" />
                    <path d="M8 48c0-11 9-18 20-18s20 7 20 18" stroke="#1b2a45" stroke-width="2" stroke-linecap="round" />
                </svg>
            </div>
            <p class="eid-trust-text">Login with username and password is a basic level of trust. <a href="#">Learn more.</a></p>

            <div class="eid-form">
                <?php if ($success === '1'): ?>
                    <div class="eid-success">&#x2705; Account created successfully! You can now log in.</div>
                <?php endif; ?>
                <?php if ($error !== ''): ?>
                    <div class="eid-error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
                <?php endif; ?>

                <form method="POST">
                    <div class="eid-field">
                        <label>Username:</label>
                        <div class="hint">(Email address)</div>
                        <input type="email" name="email" value="<?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?>" required autocomplete="email">
                    </div>
                    <div class="eid-field">
                        <label>Your password:</label>
                        <div class="eid-pw-wrap">
                            <input type="password" name="password" id="pw-field" required autocomplete="current-password">
                            <button type="button" class="eid-show-btn" onclick="var f=document.getElementById('pw-field');f.type=f.type==='password'?'text':'password';this.textContent=f.type==='password'?'SHOW':'HIDE'">SHOW</button>
                        </div>
                    </div>
                    <button type="submit" class="btn-eid-login">
                        <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
                            <circle cx="10" cy="10" r="8" stroke="#fff" stroke-width="1.5" />
                            <ellipse cx="10" cy="10" rx="4" ry="8" stroke="#fff" stroke-width="1" />
                            <line x1="2" y1="10" x2="18" y2="10" stroke="#fff" stroke-width="1" />
                        </svg>
                        Log in
                    </button>
                </form>
                <div class="eid-register-link">
                    Don't have an account? <a href="./register.php">Register here</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Bottom bar -->
    <div class="eid-bottom-bar">
        <a class="eid-top-link" href="#">Top of page &#8593;</a>
    </div>

    <!-- Footer -->
    <footer class="eid-footer">
        <div class="eid-footer-top">
            <img style="width: 50px;" src="images/dagon.png" alt="">
            <div>
                <h2 style="font-size:16px;font-weight:700;color:#1b2a45;">eid.gov.rs</h2>
                <p style="font-size:12px;color:#6b7280;margin-top:2px;">Portal for electronic identification</p>
            </div>
        </div>
        <p class="eid-footer-text">
            The web presentation is licensed under the terms of the Creative Commons Attribution-NonCommercial-NoDerivs 3.0 Serbia license.
            Web project <a href="#">immigration.portal.gov</a>
        </p>
        <div class="eid-footer-links">
            <a href="#">Privacy Statement</a>
            <a href="#">Terms of use</a>
        </div>
    </footer>

</body>

</html>
