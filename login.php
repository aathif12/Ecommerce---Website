<?php
require_once 'config/helpers.php';

if (isLoggedIn()) {
    header('Location: ' . (isAdmin() ? 'admin/dashboard.php' : 'user/shop.php'));
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!$email || !$password) {
        $error = 'Please fill in all fields.';
    } else {
        $stmt = dbQuery("SELECT * FROM users WHERE email=? AND is_active=1", [$email], 's');
        $user = $stmt->get_result()->fetch_assoc();
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['name'] = $user['name'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['role'] = $user['role'];
            header('Location: ' . ($user['role'] === 'admin' ? 'admin/dashboard.php' : 'user/shop.php'));
            exit;
        } else {
            $error = 'Invalid email or password.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Login — ElecStore</title>
    <meta name="description" content="Sign in to your ElecStore account to shop the latest electronics." />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" />
    <link rel="stylesheet" href="assets/css/style.css" />
    <style>
        body {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 24px;
            background: #f5f5f5;
        }

        .auth-wrap {
            width: 100%;
            max-width: 440px;
            position: relative;
            z-index: 1;
        }

        .auth-logo {
            text-align: center;
            margin-bottom: 32px;
        }

        .auth-logo .logo-mark {
            width: 56px;
            height: 56px;
            background: linear-gradient(135deg, var(--primary), var(--accent));
            border-radius: 16px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 1.6rem;
            margin-bottom: 12px;
        }

        .auth-logo h1 {
            font-size: 1.6rem;
            margin-bottom: 4px;
        }

        .auth-logo p {
            color: var(--text-muted);
            font-size: 0.9rem;
        }

        .auth-card {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: var(--radius-xl);
            padding: 36px;
            box-shadow: var(--shadow-lg);
        }

        .auth-card h2 {
            font-size: 1.3rem;
            margin-bottom: 6px;
        }

        .auth-card .subtitle {
            color: var(--text-muted);
            font-size: 0.88rem;
            margin-bottom: 28px;
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

        .auth-footer {
            text-align: center;
            margin-top: 20px;
            font-size: 0.88rem;
            color: var(--text-muted);
        }

        .auth-footer a {
            color: var(--primary-light);
            font-weight: 600;
        }

        .auth-footer a:hover {
            text-decoration: underline;
        }

        .demo-accounts {
            margin-top: 24px;
            padding: 16px;
            background: rgba(108, 99, 255, 0.06);
            border: 1px solid rgba(108, 99, 255, 0.15);
            border-radius: var(--radius-md);
        }

        .demo-accounts h4 {
            font-size: 0.8rem;
            color: var(--primary-light);
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 10px;
        }

        .demo-row {
            display: flex;
            justify-content: space-between;
            font-size: 0.82rem;
            color: var(--text-secondary);
            padding: 4px 0;
        }

        .demo-row code {
            color: var(--text-primary);
            font-size: 0.82rem;
        }
    </style>
</head>

<body>
    <div class="auth-wrap">
        <div class="auth-logo">
            <div class="logo-mark">⚡</div>
            <h1>Elec<span style="color:var(--primary-light)">Store</span></h1>
            <p>Premium Electronics, Delivered.</p>
        </div>

        <div class="auth-card">
            <h2>Welcome back</h2>
            <p class="subtitle">Sign in to your account to continue shopping</p>

            <?php if ($error): ?>
                <div class="error-alert"><i class="bi bi-exclamation-circle-fill"></i>
                    <?= $error ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="" id="login-form">
                <div class="form-group">
                    <label class="form-label" for="email">Email Address</label>
                    <input type="email" id="email" name="email" class="form-control" placeholder="you@example.com"
                        value="<?= sanitize($_POST['email'] ?? '') ?>" required autocomplete="email" />
                </div>

                <div class="form-group">
                    <label class="form-label" for="password">Password</label>
                    <div style="position:relative">
                        <input type="password" id="password" name="password" class="form-control"
                            placeholder="Your password" required autocomplete="current-password" />
                        <button type="button" id="toggle-pw"
                            style="position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;color:var(--text-muted);cursor:pointer;font-size:1rem">
                            <i class="bi bi-eye"></i>
                        </button>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary" style="width:100%;padding:13px" id="login-btn">
                    <i class="bi bi-box-arrow-in-right"></i> Sign In
                </button>
            </form>

            <div class="auth-footer">
                Don't have an account? <a href="register.php">Create one →</a>
            </div>

            <div class="demo-accounts">
                <h4><i class="bi bi-info-circle"></i> Demo Accounts</h4>
                <div class="demo-row"><span>Admin:</span> <code>admin@elecstore.com / password</code></div>
                <div class="demo-row"><span>User:</span> <code>Register a new account</code></div>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('toggle-pw').addEventListener('click', function () {
            const pw = document.getElementById('password');
            const icon = this.querySelector('i');
            pw.type = pw.type === 'password' ? 'text' : 'password';
            icon.className = pw.type === 'password' ? 'bi bi-eye' : 'bi bi-eye-slash';
        });
    </script>
</body>

</html>