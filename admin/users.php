<?php
// admin/users.php
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/functions.php';

requireAdmin();

// Fetch all standard users
$stmt = $pdo->query("SELECT id, full_name, email, phone_number, created_at FROM users WHERE role = 'user' ORDER BY created_at DESC");
$users = $stmt->fetchAll();

?>
<?php require dirname(__DIR__) . '/includes/header.php'; ?>

<div class="mb-4">
    <h1>Manage Users</h1>
    <p>Directory of all registered standard users.</p>
</div>

<div class="card">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
        <h3 style="margin: 0;">User Directory</h3>
        <div style="position: relative; width: 300px;">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: var(--text-light);"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
            <input type="text" id="user-search" class="form-control" placeholder="Search by name, email, or phone..." style="padding-left: 38px; border-radius: 20px;">
        </div>
    </div>
    
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Full Name</th>
                    <th>Email Address</th>
                    <th>Phone Number</th>
                    <th>Registered On</th>
                </tr>
            </thead>
            <tbody id="user-table-body">
                <?php if (count($users) > 0): ?>
                    <?php foreach ($users as $user): ?>
                        <tr class="user-row">
                            <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                            <td><a href="mailto:<?php echo htmlspecialchars($user['email']); ?>"><?php echo htmlspecialchars($user['email']); ?></a></td>
                            <td><?php echo htmlspecialchars($user['phone_number']); ?></td>
                            <td><?php echo date('M d, Y h:i A', strtotime($user['created_at'])); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr id="no-users-row">
                        <td colspan="4" class="text-center">No users registered yet.</td>
                    </tr>
                <?php endif; ?>
                <tr id="no-results-row" style="display: none;">
                    <td colspan="4" class="text-center">No users match your search.</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('user-search');
    const userRows = document.querySelectorAll('.user-row');
    const noResultsRow = document.getElementById('no-results-row');
    const noUsersRow = document.getElementById('no-users-row');

    searchInput.addEventListener('input', function(e) {
        const searchTerm = e.target.value.toLowerCase();
        let visibleCount = 0;
        
        if (noUsersRow) return; // If completely empty, do nothing

        userRows.forEach(row => {
            const text = row.textContent.toLowerCase();
            if (text.includes(searchTerm)) {
                row.style.display = '';
                visibleCount++;
            } else {
                row.style.display = 'none';
            }
        });

        if (visibleCount === 0 && searchTerm !== '') {
            noResultsRow.style.display = '';
        } else {
            noResultsRow.style.display = 'none';
        }
    });
});
</script>

<?php require dirname(__DIR__) . '/includes/footer.php'; ?>
