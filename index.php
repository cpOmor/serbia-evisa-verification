<?php
require_once __DIR__ . '/auth.php';
sev_auth_start_session();
$loggedIn  = sev_auth_is_logged_in();
$activeNav = 'home';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Serbia eVisa Verification ΓÇô eServices</title>
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
            background: #fff;
            color: #1b2a45;
        }

        a {
            text-decoration: none;
            color: inherit;
        }

        /* ΓöÇΓöÇ Hero ΓöÇΓöÇ */
        .hero {
            position: relative;
            min-height: 340px;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            overflow: hidden;
            background: url('images/hero.png') center/cover no-repeat;
        }

        .hero::before {
            content: '';
            position: absolute;
            inset: 0;
            background: rgba(15, 25, 50, .65);
        }

        .hero-content {
            position: relative;
            z-index: 1;
            padding: 60px 20px;
            max-width: 860px;
        }

        .hero-title {
            font-size: clamp(32px, 5vw, 56px);
            font-weight: 800;
            color: #fff;
            line-height: 1.15;
            margin-bottom: 20px;
            letter-spacing: -.5px;
        }

        .hero-sub {
            font-size: clamp(14px, 2vw, 18px);
            color: rgba(255, 255, 255, .88);
            font-weight: 600;
            line-height: 1.6;
            max-width: 640px;
            margin: 0 auto;
        }

        /* ΓöÇΓöÇ Services Section ΓöÇΓöÇ */
        .services-section {
            display: flex;
            gap: 0;
            padding: 60px 80px;
            align-items: flex-start;
            max-width: 1100px;
            margin: 0 auto;
        }

        .services-left {
            flex: 0 0 260px;
            padding-right: 48px;
        }

        .services-left h2 {
            font-size: 28px;
            font-weight: 800;
            color: #1b2a45;
            line-height: 1.25;
        }

        .services-divider {
            width: 1px;
            background: #d1d5db;
            align-self: stretch;
            flex-shrink: 0;
            margin: 0 48px;
        }

        .services-right {
            flex: 1;
        }

        .service-card {
            padding-bottom: 36px;
            margin-bottom: 36px;
            border-bottom: 1px solid #e5e7eb;
        }

        .service-card:last-child {
            border-bottom: none;
            padding-bottom: 0;
            margin-bottom: 0;
        }

        .service-card-inner {
            border-left: 4px solid #1d6abf;
            padding-left: 24px;
        }

        .service-card h3 {
            font-size: 20px;
            font-weight: 700;
            color: #1b2a45;
            margin-bottom: 16px;
            line-height: 1.3;
        }

        .service-card p {
            font-size: 14px;
            color: #374151;
            line-height: 1.65;
            margin-bottom: 14px;
        }

        .service-card p a {
            color: #1d6abf;
            font-weight: 500;
        }

        .service-card p a:hover {
            text-decoration: underline;
        }

        .sc-actions {
            display: flex;
            gap: 16px;
            align-items: center;
            margin-top: 20px;
            flex-wrap: wrap;
        }

        .btn-login {
            padding: 11px 28px;
            background: #1b2a45;
            color: #fff;
            border: none;
            border-radius: 8px;
            font-size: 15px;
            font-weight: 700;
            cursor: pointer;
            display: inline-block;
        }

        .btn-login:hover {
            background: #243857;
        }

        .btn-register-link {
            font-size: 15px;
            font-weight: 600;
            color: #1d6abf;
            text-decoration: underline;
            display: inline-block;
        }

        .btn-register-link:hover {
            color: #155da0;
        }

        .btn-open-portal {
            padding: 12px 28px;
            background: #1d6abf;
            color: #fff;
            border: none;
            border-radius: 8px;
            font-size: 15px;
            font-weight: 700;
            cursor: pointer;
            display: inline-block;
        }

        .btn-open-portal:hover {
            background: #155da0;
        }

        .btn-dashboard {
            padding: 11px 28px;
            background: #1b2a45;
            color: #fff;
            border: none;
            border-radius: 8px;
            font-size: 15px;
            font-weight: 700;
            cursor: pointer;
            display: inline-block;
        }

        .btn-dashboard:hover {
            background: #243857;
        }

        /* ΓöÇΓöÇ Golden Card ΓöÇΓöÇ */
        .golden-section {
            padding: 0 80px 60px;
            max-width: 1100px;
            margin: 0 auto;
        }

        .golden-card {
            background: #d4a933;
            border-radius: 16px;
            overflow: hidden;
            display: flex;
            align-items: center;
            padding: 44px 52px;
            gap: 52px;
        }

        .golden-img {
            flex: 0 0 200px;
            height: 200px;
            border: 4px solid rgba(255, 255, 255, .6);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(255, 255, 255, .15);
        }

        .golden-img svg {
            opacity: .7;
        }

        .golden-body {
            flex: 1;
        }

        .golden-body h3 {
            font-size: 26px;
            font-weight: 800;
            color: #1b2a45;
            margin-bottom: 14px;
            line-height: 1.3;
        }

        .golden-body p {
            font-size: 14px;
            color: #1b2a45;
            line-height: 1.65;
            margin-bottom: 24px;
            opacity: .85;
        }

        .btn-golden {
            padding: 13px 30px;
            background: #1b2a45;
            color: #fff;
            border: none;
            border-radius: 10px;
            font-size: 15px;
            font-weight: 700;
            cursor: pointer;
            display: inline-block;
        }

        .btn-golden:hover {
            background: #243857;
        }

        /* ΓöÇΓöÇ Footer ΓöÇΓöÇ */
        .lp-footer {
            background: #fff;
            border-top: 5px solid #1b2a45;
            border-bottom: 5px solid #1b2a45;
            padding: 28px 80px;
        }

        .lp-footer-inner {
            display: flex;
            gap: 28px;
            align-items: flex-start;
            max-width: 1100px;
            margin: 0 auto;
        }

        .lp-footer-title {
            font-size: 18px;
            font-weight: 800;
            color: #1b2a45;
        }

        .lp-footer-subtitle {
            font-size: 14px;
            color: #374151;
            margin-top: 2px;
            margin-bottom: 12px;
        }

        .lp-footer-hr {
            border: none;
            border-top: 1px solid #e5e7eb;
            margin: 12px 0;
        }

        .lp-footer-text {
            font-size: 13px;
            color: #6b7280;
            line-height: 1.65;
            max-width: 620px;
        }

        .lp-footer-link {
            display: inline-block;
            margin-top: 8px;
            color: #2563eb;
            font-size: 14px;
            font-weight: 600;
        }

        @media (max-width: 768px) {
            .hero-content {
                padding: 40px 16px;
            }

            .services-section {
                flex-direction: column;
                padding: 30px 16px;
            }

            .services-left {
                flex: none;
                padding-right: 0;
                margin-bottom: 24px;
            }

            .services-left h2 {
                font-size: 22px;
            }

            .services-divider {
                display: none;
            }

            .golden-section {
                padding: 0 16px 30px;
            }

            .golden-card {
                flex-direction: column;
                padding: 24px 20px;
                gap: 20px;
            }

            .golden-img {
                flex: none;
                width: 100%;
                height: 120px;
            }

            .golden-body h3 {
                font-size: 20px;
            }

            .lp-footer {
                padding: 24px 16px;
            }
        }

        @media (max-width: 480px) {
            .sc-actions {
                flex-direction: column;
                align-items: stretch;
                gap: 10px;
            }

            .btn-login, .btn-open-portal, .btn-dashboard, .btn-golden {
                width: 100%;
                text-align: center;
            }
        }
    </style>
</head>

<body>
    <?php require __DIR__ . '/partials/navbar.php'; ?>
    <!-- Hero -->
    <section class="hero">
        <div class="hero-content">
            <h1 class="hero-title">Welcome to eServices</h1>
            <p class="hero-sub">Here you can verify your Serbia eVisa and access electronic services of the Republic of Serbia.</p>
        </div>
    </section>
    <!-- Services -->
    <section class="services-section">
        <div class="services-left">
            <h2>All eServices in one place</h2>
        </div>
        <div class="services-divider"></div>
        <div class="services-right">
            <!-- For foreign citizens -->
            <div class="service-card">
                <div class="service-card-inner">
                    <h3>For foreign citizens</h3>
                    <p>Verify your Serbia eVisa status online instantly. Check validity, entry category and personal details linked to your travel document.</p>
                    <p>Full service list will be presented after you <a href="./login.php">login</a>.</p>
                    <div class="sc-actions">
                        <?php if ($loggedIn): ?>
                            <a href="./my-visa.php" class="btn-dashboard">Go to Dashboard</a>
                        <?php else: ?>
                            <a href="./login.php" class="btn-login">Login</a>
                            <a href="./register.php" class="btn-register-link">Register an account</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <!-- QR Verification -->
            <div class="service-card">
                <div class="service-card-inner">
                    <h3>QR Code & Instant Verification</h3>
                    <p>Border officials and transport companies can scan the QR code printed on the eVisa notification document to instantly verify traveller details.</p>
                    <p>No login required for QR scan verification. <a href="./check-status.php">Verify a visa now</a>.</p>
                    <div class="sc-actions">
                        <a href="./check-status.php" class="btn-open-portal">Verify eVisa</a>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!-- Golden Card -->
    <section class="golden-section">
        <div class="golden-card">
            <div class="golden-img">
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="130" viewBox="0 0 100 130">
                    <rect x="5" y="5" width="90" height="120" rx="4" fill="none" stroke="#fff" stroke-width="4" />
                    <rect x="15" y="20" width="70" height="8" rx="2" fill="#fff" />
                    <rect x="15" y="36" width="50" height="6" rx="2" fill="#fff" />
                    <rect x="15" y="50" width="60" height="6" rx="2" fill="#fff" />
                    <rect x="15" y="64" width="45" height="6" rx="2" fill="#fff" />
                    <rect x="15" y="85" width="70" height="28" rx="3" fill="#fff" opacity=".3" />
                </svg>
            </div>
            <div class="golden-body">
                <h3>Did you find the service you are looking for?</h3>
                <p>Learn more about the eVisa program. Find information, instructions and documentation needed on Entry &amp; Stay Regulations.</p>
                <a href="#" class="btn-golden">Entry &amp; Stay Regulations</a>
            </div>
        </div>
    </section>
    <?php require __DIR__ . '/partials/footer.php'; ?>
</body>

</html>