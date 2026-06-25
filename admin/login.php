<?php
// admin/login.php
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/functions.php';

if (isLoggedIn()) {
    if (isAdmin()) {
        redirect('/admin/index.php');
    } else {
        redirect('/user/index.php');
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitizeInput($_POST['email']);
    $password = $_POST['password'];

    if (empty($email) || empty($password)) {
        $_SESSION['error'] = "Please fill in all fields.";
    } else {
        $stmt = $pdo->prepare("SELECT id, full_name, password_hash, role FROM users WHERE email = ? AND role IN ('admin', 'developer')");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && $user['password_hash'] && password_verify($password, $user['password_hash'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['role'] = $user['role'];
            
            $_SESSION['success'] = "Welcome back, Admin!";
            redirect('/admin/index.php');
        } else {
            $_SESSION['error'] = "Invalid admin credentials.";
        }
    }
}
?>

<?php require dirname(__DIR__) . '/includes/header.php'; ?>

<div class="auth-container">
    <div class="card auth-card">
        <div class="text-center mb-4">
            <img src="/stdc-program-onboarding-system/assets/img/LogoSTDC.png" alt="STDC Logo" style="max-width: 150px; margin: 0 auto; display: block;">
            <h2 class="mt-3">Admin Login</h2>
            <p>Access the STDC Control Panel</p>
        </div>
        
        <form action="/stdc-program-onboarding-system/admin/login.php" method="POST">
            <div class="form-group">
                <label class="form-label" for="email">Admin Email</label>
                <input type="email" name="email" id="email" class="form-control" required>
            </div>
            
            <div class="form-group">
                <label class="form-label" for="password">Password</label>
                <input type="password" name="password" id="password" class="form-control" required>
            </div>
            
            <button type="submit" class="btn btn-primary w-100">Login as Admin</button>
        </form>
    </div>
</div>

<?php require dirname(__DIR__) . '/includes/footer.php'; ?>
