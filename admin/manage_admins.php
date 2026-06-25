<?php
// admin/manage_admins.php
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/functions.php';

requireAdmin();

// Check if Super Admin (admin@stdc.com)
$stmtUser = $pdo->prepare("SELECT email FROM users WHERE id = ?");
$stmtUser->execute([$_SESSION['user_id']]);
$currentUserEmail = $stmtUser->fetchColumn();

if ($currentUserEmail !== 'admin@stdc.com') {
    $_SESSION['error'] = "Access Denied: Only the Super Admin can manage admin accounts.";
    redirect('/admin/index.php');
}

// Handle new admin creation
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = sanitizeInput($_POST['full_name']);
    $email = sanitizeInput($_POST['email']);
    $password = $_POST['password'];

    if (empty($full_name) || empty($email) || empty($password)) {
        $_SESSION['error'] = "All fields are required.";
    } else {
        // Check if email exists
        $checkStmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $checkStmt->execute([$email]);
        if ($checkStmt->fetch()) {
            $_SESSION['error'] = "Email is already registered.";
        } else {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $insertStmt = $pdo->prepare("INSERT INTO users (full_name, email, phone_number, password_hash, role) VALUES (?, ?, '', ?, 'admin')");
            if ($insertStmt->execute([$full_name, $email, $hashed])) {
                $_SESSION['success'] = "Admin account created successfully.";
            } else {
                $_SESSION['error'] = "Failed to create admin account.";
            }
        }
    }
    redirect('/admin/manage_admins.php');
}

// Fetch all admins
$stmt = $pdo->query("SELECT id, full_name, email, created_at FROM users WHERE role = 'admin' ORDER BY created_at ASC");
$admins = $stmt->fetchAll();

?>
<?php require dirname(__DIR__) . '/includes/header.php'; ?>

<div class="mb-4">
    <h1>Manage Admins</h1>
    <p>Create and view administrative accounts.</p>
</div>

<div class="grid grid-cols-2">
    <!-- List Admins -->
    <div class="card">
        <h3 class="mb-3">Administrative Accounts</h3>
        <table class="table">
            <thead>
                <tr>
                    <th>Full Name</th>
                    <th>Email Address</th>
                    <th>Created On</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($admins as $admin): ?>
                    <tr>
                        <td>
                            <?php echo htmlspecialchars($admin['full_name']); ?>
                            <?php if ($admin['email'] === 'admin@stdc.com'): ?>
                                <span class="badge badge-active ml-2">Super Admin</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo htmlspecialchars($admin['email']); ?></td>
                        <td><?php echo date('M d, Y', strtotime($admin['created_at'])); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Add Admin Form -->
    <div class="card">
        <h3 class="mb-3">Add New Admin</h3>
        <p class="text-light mb-3">Create a new account with administrative privileges.</p>
        <form action="/stdc-program-onboarding-system/admin/manage_admins.php" method="POST">
            <div class="form-group">
                <label class="form-label" for="full_name">Full Name</label>
                <input type="text" name="full_name" id="full_name" class="form-control" required>
            </div>
            
            <div class="form-group">
                <label class="form-label" for="email">Email Address</label>
                <input type="email" name="email" id="email" class="form-control" required>
            </div>
            
            <div class="form-group">
                <label class="form-label" for="password">Password</label>
                <input type="password" name="password" id="password" class="form-control" required minlength="6">
            </div>
            
            <button type="submit" class="btn btn-primary w-100 mt-2">Create Admin Account</button>
        </form>
    </div>
</div>

<?php require dirname(__DIR__) . '/includes/footer.php'; ?>
