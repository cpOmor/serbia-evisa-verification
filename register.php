<?php
require_once __DIR__ . '/auth.php';
sev_auth_start_session();

if (sev_auth_is_logged_in()) {
    header('Location: ./my-visa.php');
    exit;
}

$error   = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name     = trim((string)($_POST['name']     ?? ''));
    $email    = trim((string)($_POST['email']    ?? ''));
    $password = trim((string)($_POST['password'] ?? ''));
    $confirm  = trim((string)($_POST['confirm']  ?? ''));

    if ($name === '' || $email === '' || $password === '') {
        $error = 'All fields are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } else {
        $result = sev_auth_register($name, $email, $password);
        if ($result['ok']) {
            header('Location: ./login.php?registered=1');
            exit;
        }
        $error = $result['error'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Register – Serbia eVisa Verification Portal</title>
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;background:#f0f4fa;color:#1b2a45;min-height:100vh;display:flex;flex-direction:column}
a{text-decoration:none;color:inherit}

.top-bar{background:#1b2a45;border-bottom:4px solid #c8383a;padding:0 32px;height:64px;display:flex;align-items:center;justify-content:space-between}
.top-bar-brand{display:flex;align-items:center;gap:14px}
.top-bar-emblem{width:44px;height:44px;border-radius:50%;overflow:hidden;background:#253d5f;border:2px solid #3d5a80;display:flex;align-items:center;justify-content:center}
.top-bar-emblem img{width:40px;height:40px;object-fit:contain}
.top-bar-title .tb-to{font-size:10px;font-weight:700;color:#c8383a;text-transform:uppercase;letter-spacing:.06em}
.top-bar-title .tb-main{font-size:18px;font-weight:900;color:#fff}
.top-bar-nav{display:flex}
.top-bar-nav a{font-size:12px;font-weight:600;color:rgba(255,255,255,.8);padding:6px 14px;border-left:1px solid rgba(255,255,255,.1)}
.top-bar-nav a:first-child{border-left:none}
.top-bar-nav a:hover{color:#fff}

.reg-page{flex:1;display:flex;align-items:center;justify-content:center;padding:40px 20px}
.reg-wrap{width:100%;max-width:460px}

.person-icon{display:flex;justify-content:center;margin-bottom:24px}
.person-circle{width:80px;height:80px;border-radius:50%;border:3px solid #1a6bbf;background:#fff;display:flex;align-items:center;justify-content:center}
.person-circle svg{width:50px;height:50px;fill:#1a6bbf}

.reg-card{background:#fff;border-radius:8px;box-shadow:0 4px 28px rgba(0,0,0,.10);overflow:hidden}
.reg-card-header{background:#1b2a45;padding:18px 32px}
.reg-card-header h2{font-size:18px;font-weight:800;color:#fff;margin:0}
.reg-card-header p{font-size:13px;color:#c5d3e8;margin-top:4px}
.reg-card-body{padding:28px 32px 32px}

.form-group{margin-bottom:18px}
.form-group label{display:block;font-size:12px;font-weight:700;color:#374151;margin-bottom:6px;text-transform:uppercase;letter-spacing:.04em}
.form-group input{width:100%;border:1.5px solid #d1d5db;border-radius:6px;padding:12px 14px;font-size:15px;color:#1b2a45;outline:none;transition:border-color .15s}
.form-group input:focus{border-color:#1a6bbf}
.pass-wrap{position:relative}
.pass-wrap input{padding-right:70px}
.pass-toggle{position:absolute;right:10px;top:50%;transform:translateY(-50%);background:none;border:none;font-size:12px;font-weight:700;color:#1a6bbf;cursor:pointer;padding:4px 6px}
.btn-register{width:100%;background:#2d7a2d;color:#fff;border:none;border-radius:6px;padding:14px;font-size:16px;font-weight:800;cursor:pointer;margin-top:8px;letter-spacing:.02em;transition:background .15s}
.btn-register:hover{background:#256025}
.form-footer{margin-top:18px;text-align:center;font-size:13px;color:#6b7280}
.form-footer a{color:#1a6bbf;font-weight:600}
.form-footer a:hover{text-decoration:underline}

.alert-err{background:#fef2f2;border:1px solid #fca5a5;color:#b91c1c;border-radius:6px;padding:12px 16px;margin-bottom:18px;font-size:14px}

.site-footer{background:#1b2a45;border-top:4px solid #c8383a;padding:20px 32px;text-align:center;font-size:12px;color:#5a7a9a}

@media(max-width:480px){.reg-card-body,.reg-card-header{padding:20px}}
</style>
</head>
<body>

<div class="top-bar">
    <a href="./index.php" class="top-bar-brand">
        <div class="top-bar-emblem">
            <img src="images/logo.png" alt="Serbia">
        </div>
        <div class="top-bar-title">
            <span class="tb-to">Welcome to</span>
            <span class="tb-main">Serbia</span>
        </div>
    </a>
    <nav class="top-bar-nav">
        <a href="./index.php">Home</a>
        <a href="./check-status.php">Verify Visa</a>
        <a href="./login.php">Log in</a>
    </nav>
</div>

<div class="reg-page">
    <div class="reg-wrap">

        <div class="person-icon">
            <div class="person-circle">
                <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path d="M12 12c2.7 0 4.8-2.1 4.8-4.8S14.7 2.4 12 2.4 7.2 4.5 7.2 7.2 9.3 12 12 12zm0 2.4c-3.2 0-9.6 1.6-9.6 4.8v2.4h19.2v-2.4c0-3.2-6.4-4.8-9.6-4.8z"/>
                </svg>
            </div>
        </div>

        <div class="reg-card">
            <div class="reg-card-header">
                <h2>Create Account</h2>
                <p>Register to access the Serbia eVisa verification portal</p>
            </div>
            <div class="reg-card-body">
                <?php if ($error !== ''): ?>
                <div class="alert-err">&#x26A0; <?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
                <?php endif; ?>

                <form method="POST">
                    <div class="form-group">
                        <label for="name">Full Name</label>
                        <input type="text" id="name" name="name" placeholder="Enter your full name"
                            value="<?php echo htmlspecialchars((string)($_POST['name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                            required autocomplete="name">
                    </div>
                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" id="email" name="email" placeholder="Enter your email"
                            value="<?php echo htmlspecialchars((string)($_POST['email'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                            required autocomplete="email">
                    </div>
                    <div class="form-group">
                        <label for="password">Password</label>
                        <div class="pass-wrap">
                            <input type="password" id="password" name="password"
                                placeholder="Minimum 6 characters" required autocomplete="new-password">
                            <button type="button" class="pass-toggle"
                                onclick="var i=document.getElementById('password');i.type=i.type==='password'?'text':'password';this.textContent=i.type==='password'?'SHOW':'HIDE'">SHOW</button>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="confirm">Confirm Password</label>
                        <div class="pass-wrap">
                            <input type="password" id="confirm" name="confirm"
                                placeholder="Re-enter your password" required autocomplete="new-password">
                            <button type="button" class="pass-toggle"
                                onclick="var i=document.getElementById('confirm');i.type=i.type==='password'?'text':'password';this.textContent=i.type==='password'?'SHOW':'HIDE'">SHOW</button>
                        </div>
                    </div>
                    <button type="submit" class="btn-register">Create Account</button>
                </form>
                <div class="form-footer">
                    Already have an account? <a href="./login.php">Log in here</a>
                </div>
            </div>
        </div>

    </div>
</div>

<footer class="site-footer">
    &copy; <?php echo date('Y'); ?> Republic of Serbia — eVisa Verification Portal
</footer>

</body>
</html>
