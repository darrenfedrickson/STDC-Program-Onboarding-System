<?php
// user/profile.php
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/functions.php';

requireLogin();

// Fetch current user details
$stmt = $pdo->prepare("SELECT full_name, email, phone_number FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = sanitizeInput($_POST['full_name']);
    $phone_number = sanitizeInput($_POST['phone_number']);
    
    if (empty($full_name) || empty($phone_number)) {
        $_SESSION['error'] = "Full Name and Phone Number are required.";
    } else {
        $updateStmt = $pdo->prepare("UPDATE users SET full_name = ?, phone_number = ? WHERE id = ?");
        if ($updateStmt->execute([$full_name, $phone_number, $_SESSION['user_id']])) {
            $_SESSION['full_name'] = $full_name;
            $_SESSION['success'] = "Profile updated successfully!";
            $user['full_name'] = $full_name;
            $user['phone_number'] = $phone_number;
        } else {
            $_SESSION['error'] = "Failed to update profile.";
        }
    }
}
?>

<?php require dirname(__DIR__) . '/includes/header.php'; ?>

<div class="mb-4">
    <h1>My Profile</h1>
    <p>Manage your account settings</p>
</div>

<div class="card" style="max-width: 600px;">
    <?php if (empty($user['phone_number'])): ?>
        <div class="alert alert-warning" style="background: var(--warning); padding: 1rem; border-radius: 0.5rem; margin-bottom: 1.5rem; color: #856404; background-color: #fff3cd; border-color: #ffeeba;">
            <strong>Action Required:</strong> Please provide your phone number. This is required before you can apply for any programs.
        </div>
    <?php endif; ?>

    <form action="/iDaftar@STDC/user/profile.php" method="POST">
        <div class="form-group">
            <label class="form-label" for="email">Email Address</label>
            <input type="email" name="email" id="email" class="form-control" value="<?php echo htmlspecialchars($user['email']); ?>" disabled>
            <small class="text-light" style="display: block; margin-top: 5px;">Email address cannot be changed.</small>
        </div>
        
        <div class="form-group">
            <label class="form-label" for="full_name">Full Name</label>
            <input type="text" name="full_name" id="full_name" class="form-control" value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
        </div>
        
        <div class="form-group">
            <label class="form-label" for="phone_number">Phone Number</label>
            <input type="text" name="phone_number" id="phone_number" class="form-control" value="<?php echo htmlspecialchars($user['phone_number']); ?>" required>
        </div>
        
        <div class="form-group mt-4">
            <button type="submit" class="btn btn-primary">Update Profile</button>
        </div>
    </form>
</div>

<?php require dirname(__DIR__) . '/includes/footer.php'; ?>
