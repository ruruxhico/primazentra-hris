<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - PrimaZentra</title>
    <link rel="stylesheet" href="<?= base_url('public/css/styles.css') ?>">
</head>
<body class="auth-page">
    <div class="register-container">
        <h1>Executive Registration</h1>
        
        <?php if (session()->getFlashdata('success')): ?>
            <div class="success-message">
                <?= session()->getFlashdata('success') ?>
            </div>
        <?php endif; ?>
        
        <form action="<?= base_url('auth/processRegister') ?>" method="post">
            <!-- Personal Information Section -->
            <div class="form-section">
                <h3>Personal Information</h3>
                <div class="form-row">
                    <div class="form-group">
                        <input type="text" name="firstName" placeholder="First Name" required>
                    </div>
                    <div class="form-group">
                        <input type="text" name="middleName" placeholder="Middle Name">
                    </div>
                    <div class="form-group">
                        <input type="text" name="lastName" placeholder="Last Name" required>
                    </div>
                </div>
            </div>
            
            <!-- Account Credentials Section -->
            <div class="form-section">
                <h3>Account Credentials</h3>
                <div class="form-group">
                    <input type="email" name="email" placeholder="Email Address" required>
                </div>
                <div class="form-group">
                    <input type="password" name="password" placeholder="Password" required>
                </div>
            </div>
            
            <button type="submit" class="btn-register">Register</button>
        </form>
        
        <div class="login-link">
            Already have an account? <a href="<?= base_url('auth/login') ?>">Login here</a>
        </div>
    </div>
</body>
</html>