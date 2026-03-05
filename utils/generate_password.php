<?php
/**
 * Password Hash Generator
 * Use this script to generate secure password hashes for new supervisors
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Password Hash Generator</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 50px 0;
        }
        .generator-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            max-width: 600px;
            margin: 0 auto;
        }
        .card-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px 15px 0 0 !important;
            padding: 20px;
        }
        .hash-output {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 15px;
            font-family: 'Courier New', monospace;
            word-break: break-all;
            font-size: 0.9rem;
        }
        .btn-generate {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            color: white;
        }
        .btn-generate:hover {
            opacity: 0.9;
            color: white;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="generator-card">
            <div class="card-header">
                <h3 class="mb-0"><i class="bi bi-key-fill me-2"></i>Password Hash Generator</h3>
                <p class="mb-0 mt-2" style="opacity: 0.9;">Generate secure password hashes for supervisor accounts</p>
            </div>
            <div class="card-body p-4">
                <?php
                $hash = '';
                $password = '';
                
                if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['password'])) {
                    $password = $_POST['password'];
                    if (!empty($password)) {
                        $hash = password_hash($password, PASSWORD_DEFAULT);
                    }
                }
                ?>
                
                <form method="POST" action="">
                    <div class="mb-3">
                        <label for="password" class="form-label">Enter Password</label>
                        <input type="text" class="form-control" id="password" name="password" 
                               placeholder="Enter the password to hash" required 
                               value="<?= htmlspecialchars($password) ?>">
                        <small class="text-muted">This will be hashed using bcrypt (PASSWORD_DEFAULT)</small>
                    </div>
                    
                    <button type="submit" class="btn btn-generate w-100">
                        <i class="bi bi-shield-lock me-2"></i>Generate Hash
                    </button>
                </form>
                
                <?php if ($hash): ?>
                    <div class="mt-4">
                        <label class="form-label fw-bold">Generated Hash:</label>
                        <div class="hash-output">
                            <?= htmlspecialchars($hash) ?>
                        </div>
                        
                        <div class="alert alert-info mt-3">
                            <h6 class="alert-heading"><i class="bi bi-info-circle me-2"></i>How to Use</h6>
                            <p class="mb-2">Copy the hash above and use it in your SQL INSERT statement:</p>
                            <pre class="mb-0 small"><code>INSERT INTO supervisors (username, password_hash, full_name, email, status) 
VALUES (
    'username',
    '<?= htmlspecialchars($hash) ?>',
    'Full Name',
    'email@company.com',
    'Active'
);</code></pre>
                        </div>
                        
                        <div class="alert alert-warning">
                            <i class="bi bi-exclamation-triangle me-2"></i>
                            <strong>Security Note:</strong> Each time you generate a hash for the same password, 
                            it will be different. This is normal and secure - bcrypt includes a random salt.
                        </div>
                    </div>
                <?php endif; ?>
                
                <div class="mt-4 p-3 bg-light rounded">
                    <h6 class="mb-2"><i class="bi bi-lightbulb me-2"></i>Tips</h6>
                    <ul class="mb-0 small">
                        <li>Use strong passwords (minimum 8 characters)</li>
                        <li>Include uppercase, lowercase, numbers, and symbols</li>
                        <li>Never reuse passwords across accounts</li>
                        <li>Store the original password securely (password manager)</li>
                    </ul>
                </div>
                
                <div class="text-center mt-3">
                    <a href="login.php" class="btn btn-outline-secondary btn-sm">
                        <i class="bi bi-arrow-left me-2"></i>Back to Login
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
