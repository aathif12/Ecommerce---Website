<?php
require_once 'config/helpers.php';
if (isLoggedIn()) {
    header('Location: user/shop.php');
    exit;
}

$error = '';
$success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize($_POST['name'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $phone = sanitize($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm'] ?? '';

    if (!$name || !$email || !$password) {
        $error = 'Name, email, and password are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } else {
        $check = dbQuery("SELECT id FROM users WHERE email=?", [$email], 's')->get_result()->fetch_assoc();
        if ($check) {
            $error = 'An account with this email already exists.';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            dbQuery("INSERT INTO users (name,email,phone,password) VALUES (?,?,?,?)", [$name, $email, $phone, $hash], 'ssss');
            $success = 'Account created! You can now sign in.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Register — ElecStore</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" />
    <link rel="stylesheet" href="assets/css/style.css" />
    <style>
        body {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 24px;
        }

        body::before {
            content: '';
            position: fixed;
            inset: 0;
            background: radial-gradient(ellipse at 80% 50%, rgba(108, 99, 255, 0.12) 0%, transparent 60%),
                radial-gradient(ellipse at 20% 20%, rgba(255, 107, 107, 0.08) 0%, transparent 50%);
        }

        .auth-wrap {
            width: 100%;
            max-width: 460px;
            position: relative;
            z-index: 1;
        }

        .auth-logo {
            text-align: center;
            margin-bottom: 28px;
        }

        .auth-logo .logo-mark {
            width: 52px;
            height: 52px;
            background: linear-gradient(135deg, var(--primary), var(--accent));
            border-radius: 14px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin-bottom: 10px;
        }

        .auth-card {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: var(--radius-xl);
            padding: 32px;
            box-shadow: var(--shadow-lg);
        }

        .success-alert {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px 16px;
            background: rgba(46, 204, 113, 0.1);
            border: 1px solid rgba(46, 204, 113, 0.3);
            border-radius: var(--radius-sm);
            color: #6ee0a0;
            font-size: 0.88rem;
            margin-bottom: 20px;
        }

        .error-alert {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px 16px;
            background: rgba(231, 76, 60, 0.1);
            border: 1px solid rgba(231, 76, 60, 0.3);
            border-radius: var(--radius-sm);
            color: #e98585;
            font-size: 0.88rem;
            margin-bottom: 20px;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 14px;
        }

        .auth-footer {
            text-align: center;
            margin-top: 18px;
            font-size: 0.88rem;
            color: var(--text-muted);
        }

        .auth-footer a {
            color: var(--primary-light);
            font-weight: 600;
        }
    </style>
</head>

<body>
    <div class="auth-wrap">
        <div class="auth-logo">
            <div class="logo-mark">⚡</div>
            <h1 style="font-size:1.5rem">Create Account</h1>
            <p style="color:var(--text-muted);font-size:0.88rem">Join ElecStore today</p>
        </div>

        <div class="auth-card">
            <?php if ($success): ?>
                <div class="success-alert"><i class="bi bi-check-circle-fill"></i>
                    <?= $success ?> <a href="login.php" style="color:var(--success);font-weight:700;margin-left:8px">Sign in
                        →</a>
                </div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="error-alert"><i class="bi bi-exclamation-circle-fill"></i>
                    <?= $error ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Full Name *</label>
                        <input type="text" name="name" class="form-control" placeholder="John Doe"
                            value="<?= sanitize($_POST['name'] ?? '') ?>" required />
                    </div>
                    <div class="form-group">
                        <label class="form-label">Phone</label>
                        <input type="tel" name="phone" class="form-control" placeholder="+1 234 567 890"
                            value="<?= sanitize($_POST['phone'] ?? '') ?>" />
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Email Address *</label>
                    <input type="email" name="email" class="form-control" placeholder="you@example.com"
                        value="<?= sanitize($_POST['email'] ?? '') ?>" required />
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Password *</label>
                        <input type="password" name="password" id="reg-pw" class="form-control"
                            placeholder="Min. 6 chars" required />
                    </div>
                    <div class="form-group">
                        <label class="form-label">Confirm Password *</label>
                        <input type="password" name="confirm" id="reg-cpw" class="form-control"
                            placeholder="Repeat password" required />
                    </div>
                </div>

                <div id="pw-match" style="font-size:0.8rem;margin-top:-12px;margin-bottom:14px;display:none"></div>

                <button type="submit" class="btn btn-primary" style="width:100%;padding:13px">
                    <i class="bi bi-person-plus"></i> Create Account
                </button>
            </form>

            <div class="auth-footer">Already have an account? <a href="login.php">Sign in →</a></div>
        </div>
    </div>
    <script>
        const pw = document.getElementById('reg-pw'), cpw = document.getElementById('reg-cpw'), match = document.getElementById('pw-match');
        function checkMatch() {
            if (!cpw.value) { match.style.display = 'none'; return; }
            if (pw.value === cpw.value) { match.style.display = 'block'; match.innerHTML = '<span style="color:var(--success)"><i class="bi bi-check-circle-fill"></i> Passwords match</span>'; }
            else { match.style.display = 'block'; match.innerHTML = '<span style="color:var(--danger)"><i class="bi bi-x-circle-fill"></i> Passwords do not match</span>'; }
        }
        pw.addEventListener('input', checkMatch);
        cpw.addEventListener('input', checkMatch);
    </script>
</body>

</html>