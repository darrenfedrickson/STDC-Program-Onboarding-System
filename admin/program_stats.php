<?php
// admin/program_stats.php
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/functions.php';

requireAdmin();

$program_id = isset($_GET['program_id']) ? (int)$_GET['program_id'] : 0;

if (!$program_id) {
    $_SESSION['error'] = "No program specified.";
    redirect('/admin/programs.php');
}

// Fetch program info
$stmt = $pdo->prepare("SELECT * FROM programs WHERE id = ?");
$stmt->execute([$program_id]);
$program = $stmt->fetch();

if (!$program) {
    $_SESSION['error'] = "Program not found.";
    redirect('/admin/programs.php');
}

// Parse schema and find categorical fields
$schemaStmt = $pdo->prepare("SELECT * FROM program_fields WHERE program_id = ? AND (type = 'select' OR type = 'radio')");
$schemaStmt->execute([$program_id]);
$categorical_fields = $schemaStmt->fetchAll();

// Get total registrations
$regCountStmt = $pdo->prepare("SELECT COUNT(*) FROM registrations WHERE program_id = ?");
$regCountStmt->execute([$program_id]);
$total_registrations = $regCountStmt->fetchColumn();

// Process stats
$stats = [];
foreach ($categorical_fields as $field) {
    $stats[$field['id']] = [
        'label' => $field['label'],
        'counts' => []
    ];
    
    // Use SQL GROUP BY to count answers for this field
    $countStmt = $pdo->prepare("
        SELECT COALESCE(answer_value, '') as val, COUNT(*) as cnt 
        FROM registration_answers ra 
        JOIN registrations r ON ra.registration_id = r.id 
        WHERE ra.field_id = ? AND r.program_id = ? 
        GROUP BY answer_value
    ");
    $countStmt->execute([$field['id'], $program_id]);
    $counts = $countStmt->fetchAll();
    
    $total_answers = 0;
    foreach ($counts as $row) {
        $val = $row['val'] === '' ? 'No Answer' : $row['val'];
        if (!isset($stats[$field['id']]['counts'][$val])) {
            $stats[$field['id']]['counts'][$val] = 0;
        }
        $stats[$field['id']]['counts'][$val] += $row['cnt'];
        $total_answers += $row['cnt'];
    }
    
    // Account for missing answers
    if ($total_registrations > $total_answers) {
        if (!isset($stats[$field['id']]['counts']['No Answer'])) {
            $stats[$field['id']]['counts']['No Answer'] = 0;
        }
        $stats[$field['id']]['counts']['No Answer'] += ($total_registrations - $total_answers);
    }
}

?>
<?php require dirname(__DIR__) . '/includes/header.php'; ?>

<!-- Include Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<div class="mb-4">
    <a href="<?php echo BASE_URL; ?>/admin/attendees.php?program_id=<?php echo $program_id; ?>" class="btn btn-sm btn-outline mb-3">&larr; Back to Attendees</a>
    <h1>Data Visualization</h1>
    <p>Program: <?php echo htmlspecialchars($program['title']); ?> (<?php echo $total_registrations; ?> Total Applicants)</p>
</div>

<?php if (empty($categorical_fields)): ?>
    <div class="card">
        <p>No categorical fields (like Dropdowns or Radio buttons) were found in this program's form to generate charts.</p>
    </div>
<?php else: ?>
    <div class="grid grid-cols-2">
        <?php foreach ($stats as $field_name => $data): ?>
            <div class="card">
                <h3 class="mb-3 text-center" style="font-size: 1.1rem; color: var(--text-color); font-weight: 600;"><?php echo htmlspecialchars($data['label']); ?></h3>
                <div style="position: relative; height: 300px; width: 100%;">
                    <canvas id="chart_<?php echo htmlspecialchars($field_name); ?>"></canvas>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <script>
        // Use a nice color palette
        const colors = [
            '#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF', '#FF9F40', 
            '#E7E9ED', '#8AC926', '#FF595E', '#1982C4', '#6A4C93'
        ];

        <?php foreach ($stats as $field_name => $data): ?>
            <?php
                $labels = array_keys($data['counts']);
                $values = array_values($data['counts']);
            ?>
            new Chart(document.getElementById('chart_<?php echo htmlspecialchars($field_name); ?>'), {
                type: 'pie',
                data: {
                    labels: <?php echo json_encode($labels); ?>,
                    datasets: [{
                        data: <?php echo json_encode($values); ?>,
                        backgroundColor: colors.slice(0, Math.max(<?php echo count($values); ?>, colors.length)),
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    let label = context.label || '';
                                    if (label) {
                                        label += ': ';
                                    }
                                    let total = context.dataset.data.reduce((a, b) => a + b, 0);
                                    let value = context.raw;
                                    let percentage = Math.round((value / total) * 100) + '%';
                                    return label + value + ' (' + percentage + ')';
                                }
                            }
                        }
                    }
                }
            });
        <?php endforeach; ?>
    </script>
<?php endif; ?>

<?php require dirname(__DIR__) . '/includes/footer.php'; ?>
