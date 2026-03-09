<?php
// ElecStore Database Installer
// Run this ONCE at: http://localhost/your-path/install.php
// Then DELETE this file!

$host = 'localhost';
$user = 'root';
$pass = '';  // <== Change if your MySQL has a password

// Step 1: Connect without DB
$conn = new mysqli($host, $user, $pass);
if ($conn->connect_error) {
    die("<h2 style='color:red'>❌ Connection failed: " . $conn->connect_error . "</h2><p>Please check your MySQL credentials in this file.</p>");
}

// Step 2: Run SQL
$sql = file_get_contents(__DIR__ . '/config/setup.sql');
$queries = array_filter(array_map('trim', explode(';', $sql)));
$errors = [];
foreach ($queries as $q) {
    if (!empty($q) && $conn->query($q) === FALSE) {
        $errors[] = $conn->error . " — Query: " . substr($q, 0, 60);
    }
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <title>ElecStore Installer</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" />
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Segoe UI', sans-serif;
            background: #0a0e1a;
            color: #f0f4ff;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }

        .installer {
            background: #111827;
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 24px;
            padding: 40px;
            max-width: 560px;
            width: 100%;
        }

        h1 {
            font-size: 1.5rem;
            margin-bottom: 6px;
        }

        h1 span {
            color: #8B85FF;
        }

        .step {
            background: #1a2235;
            border-radius: 12px;
            padding: 16px 20px;
            margin: 16px 0;
            display: flex;
            gap: 14px;
            align-items: flex-start;
        }

        .step-icon {
            font-size: 1.4rem;
            flex-shrink: 0;
        }

        .step h3 {
            font-size: 0.95rem;
            margin-bottom: 4px;
        }

        .step p {
            font-size: 0.85rem;
            color: #8892A4;
        }

        .success {
            border: 1px solid rgba(46, 204, 113, 0.3);
            background: rgba(46, 204, 113, 0.06);
        }

        .error {
            border: 1px solid rgba(231, 76, 60, 0.3);
            background: rgba(231, 76, 60, 0.06);
        }

        .warning {
            border: 1px solid rgba(243, 156, 18, 0.3);
            background: rgba(243, 156, 18, 0.06);
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 24px;
            border-radius: 50px;
            font-weight: 600;
            font-size: 0.9rem;
            cursor: pointer;
            border: none;
            text-decoration: none;
            margin-top: 20px;
        }

        .btn-primary {
            background: linear-gradient(135deg, #6C63FF, #5A52D5);
            color: #fff;
        }

        .err-list {
            font-size: 0.78rem;
            color: #e98585;
            margin-top: 8px;
            font-family: monospace;
        }

        .creds {
            background: #0d1525;
            border-radius: 8px;
            padding: 12px;
            font-size: 0.85rem;
            margin-top: 12px;
        }

        .creds div {
            margin-bottom: 4px;
            color: #8892A4;
        }

        .creds span {
            color: #8B85FF;
            font-weight: 700;
        }
    </style>
</head>

<body>
    <div class="installer">
        <div style="display:flex;align-items:center;gap:12px;margin-bottom:28px">
            <div
                style="width:44px;height:44px;background:linear-gradient(135deg,#6C63FF,#FF6B6B);border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:1.3rem">
                ⚡</div>
            <div>
                <h1>Elec<span>Store</span></h1>
                <p style="color:#8892A4;font-size:0.82rem">Database Installer</p>
            </div>
        </div>

        <?php if (empty($errors)): ?>
            <div class="step success">
                <div class="step-icon">✅</div>
                <div>
                    <h3>Database installed successfully!</h3>
                    <p>Tables created and sample data seeded. Your store is ready!</p>
                </div>
            </div>

            <div class="creds">
                <div><span>Admin Login:</span></div>
                <div>📧 Email: <span>admin@elecstore.com</span></div>
                <div>🔑 Password: <span>password</span></div>
            </div>

            <div class="step warning" style="margin-top:16px">
                <div class="step-icon">⚠️</div>
                <div>
                    <h3>Delete install.php</h3>
                    <p>For security, please delete this file after installation is complete.</p>
                </div>
            </div>

            <div style="display:flex;gap:10px;flex-wrap:wrap">
                <a href="index.php" class="btn btn-primary"><i class="bi bi-house"></i> Go to Homepage</a>
                <a href="login.php" class="btn btn-primary" style="background:linear-gradient(135deg,#FF6B6B,#E05555)"><i
                        class="bi bi-box-arrow-in-right"></i> Admin Login</a>
            </div>

        <?php else: ?>
            <div class="step error">
                <div class="step-icon">❌</div>
                <div>
                    <h3>Installation encountered errors</h3>
                    <p>Some queries failed. This is usually okay if the database is already set up.</p>
                    <div class="err-list">
                        <?php foreach (array_slice($errors, 0, 5) as $e): ?>
                            <div style="margin-bottom:4px">•
                                <?= htmlspecialchars($e) ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <div class="step warning" style="margin-top:0">
                <div class="step-icon">💡</div>
                <div>
                    <h3>Possible fix</h3>
                    <p>If the database already exists, errors are expected. Try proceeding to the homepage.</p>
                </div>
            </div>

            <a href="index.php" class="btn btn-primary"><i class="bi bi-house"></i> Continue to Homepage</a>
        <?php endif; ?>
    </div>
</body>

</html>