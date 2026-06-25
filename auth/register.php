<?php
// auth/register.php
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/functions.php';

if (isLoggedIn()) {
    redirect('/index.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = sanitizeInput($_POST['full_name']);
    $email = sanitizeInput($_POST['email']);
    $phone_number = sanitizeInput($_POST['phone_number'] ?? '');
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if (empty($full_name) || empty($email) || empty($phone_number) || empty($password) || empty($confirm_password)) {
        $_SESSION['error'] = "Please fill in all fields.";
    } elseif ($password !== $confirm_password) {
        $_SESSION['error'] = "Passwords do not match.";
    } else {
        // Check if email exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $_SESSION['error'] = "Email is already registered.";
        } else {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (full_name, email, phone_number, password_hash, role) VALUES (?, ?, ?, ?, 'user')");
            if ($stmt->execute([$full_name, $email, $phone_number, $hashed])) {
                $_SESSION['success'] = "Registration successful. Please login.";
                redirect('/auth/login.php');
            } else {
                $_SESSION['error'] = "Something went wrong. Please try again.";
            }
        }
    }
}
?>

<?php require dirname(__DIR__) . '/includes/header.php'; ?>

<div class="auth-container">
    <div class="card auth-card">
        <div class="text-center mb-4">
            <img src="/stdc-program-onboarding-system/assets/img/LogoSTDC.png" alt="STDC Logo" style="max-width: 150px; margin: 0 auto; display: block;">
            <h2 class="mt-3">Create an Account</h2>
            <p>Register for STDC Programs</p>
        </div>
        
        <form action="/stdc-program-onboarding-system/auth/register.php" method="POST">
            <div class="form-group">
                <label class="form-label" for="full_name">Full Name</label>
                <input type="text" name="full_name" id="full_name" class="form-control" required>
            </div>
            
            <div class="form-group">
                <label class="form-label" for="email">Email Address</label>
                <input type="email" name="email" id="email" class="form-control" required>
            </div>
            
            <div class="form-group">
                <label class="form-label" for="phone_number">Phone Number</label>
                <input type="text" name="phone_number" id="phone_number" class="form-control" required>
            </div>
            
            <div class="form-group">
                <label class="form-label" for="password">Password</label>
                <input type="password" name="password" id="password" class="form-control" required>
            </div>
            
            <div class="form-group">
                <label class="form-label" for="confirm_password">Confirm Password</label>
                <input type="password" name="confirm_password" id="confirm_password" class="form-control" required>
            </div>
            
            <button type="submit" class="btn btn-primary w-100">Register</button>
        </form>
        
        <div class="mt-3 text-center">
            <p class="text-light mb-2">OR</p>
            <a href="/stdc-program-onboarding-system/auth/google_login.php" class="btn btn-outline w-100" style="display: flex; justify-content: center; align-items: center; gap: 10px;">
                <svg viewBox="0 0 24 24" width="20" height="20" xmlns="http://www.w3.org/2000/svg"><path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/><path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/><path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05"/><path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/></svg>
                Continue with Google
            </a>
        </div>
        
        <p class="text-center mt-4">
            Already have an account? <a href="/stdc-program-onboarding-system/auth/login.php">Login here</a>
        </p>
    </div>
</div>

<?php require dirname(__DIR__) . '/includes/footer.php'; ?>
