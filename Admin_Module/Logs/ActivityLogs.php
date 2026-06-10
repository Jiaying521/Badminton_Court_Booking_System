<?php
session_start();

if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'Superadmin') {
    header("Location: ../LoginPage.php");
    exit();
}

header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

$conn         = mysqli_connect("localhost", "root", "", "badminton_hub");
$username     = $_SESSION['username'];
$role         = $_SESSION['role'];
$display_name = $username;
$base_path    = '../';

$f_search = isset($_GET['search']) ? trim($_GET['search']) : '';
$f_role   = isset($_GET['role'])   ? trim($_GET['role'])   : '';
$f_module = isset($_GET['module']) ? trim($_GET['module']) : '';
$f_from   = isset($_GET['from'])   ? trim($_GET['from'])   : '';
$f_to     = isset($_GET['to'])     ? trim($_GET['to'])     : '';

$has_filter = ($f_search !== '' || $f_role !== '' || $f_module !== '' || $f_from !== '' || $f_to !== '');

$allowed_sorts = ['created_at', 'username', 'module', 'action', 'description', 'ip_address'];
$sort_col = isset($_GET['sort']) && in_array($_GET['sort'], $allowed_sorts) ? $_GET['sort'] : 'created_at';
$sort_dir = isset($_GET['dir']) && $_GET['dir'] === 'asc' ? 'ASC' : 'DESC';
$next_dir = ($sort_dir === 'ASC') ? 'desc' : 'asc';

function buildWhere($conn, $search, $role, $module, $from, $to) {
    $parts = [];

    if ($search !== '') {
        $s = mysqli_real_escape_string($conn, $search);
        $parts[] = "(username LIKE '%$s%' OR description LIKE '%$s%' OR action LIKE '%$s%')";
    }

    if ($role   !== '') $parts[] = "role   = '" . mysqli_real_escape_string($conn, $role)   . "'";
    if ($module !== '') $parts[] = "module = '" . mysqli_real_escape_string($conn, $module) . "'";
    if ($from   !== '') $parts[] = "DATE(created_at) >= '" . mysqli_real_escape_string($conn, $from) . "'";
    if ($to     !== '') $parts[] = "DATE(created_at) <= '" . mysqli_real_escape_string($conn, $to)   . "'";

    return $parts ? 'WHERE ' . implode(' AND ', $parts) : '';
}

function logSortLink($label, $col, $current_sort, $current_dir, $next_dir, $f_search, $f_role, $f_module, $f_from, $f_to) {
    $is_active = ($current_sort === $col);
    $dir = $is_active ? $next_dir : 'desc';

    if ($is_active) {
        $arrow = $current_dir === 'ASC'
            ? ' <i class="fas fa-arrow-up sort-arrow active-arrow"></i>'
            : ' <i class="fas fa-arrow-down sort-arrow active-arrow"></i>';
    } else {
        $arrow = ' <i class="fas fa-sort sort-arrow"></i>';
    }

    $params = ['sort' => $col, 'dir' => $dir];
    if ($f_search !== '') $params['search'] = $f_search;
    if ($f_role   !== '') $params['role']   = $f_role;
    if ($f_module !== '') $params['module'] = $f_module;
    if ($f_from   !== '') $params['from']   = $f_from;
    if ($f_to     !== '') $params['to']     = $f_to;

    $qs = http_build_query($params);
    return "<a href='ActivityLogs.php?$qs' class='sort-link'>$label$arrow</a>";
}

function actionBadge($action) {
    $map = [
        'Login'          => ['#dcfce7', '#16a34a'],
        'Logout'         => ['#f1f5f9', '#475569'],
        'Create'         => ['#dbeafe', '#2563eb'],
        'Update'         => ['#fef3c7', '#d97706'],
        'Delete'         => ['#fee2e2', '#dc2626'],
        'Status Change'  => ['#ede9fe', '#7c3aed'],
        'Settings'       => ['#e0f2fe', '#0284c7'],
        'Login Failed'   => ['#fee2e2', '#dc2626'],
        'Password Reset' => ['#fdf4ff', '#a21caf'],
    ];
    $c = $map[$action] ?? ['#f1f5f9', '#64748b'];
    return "<span class='action-badge' style='background:{$c[0]};color:{$c[1]};'>" . htmlspecialchars($action) . "</span>";
}

/* PDF export */
if (isset($_GET['export']) && $_GET['export'] === 'pdf') {
    require('../Report/fpdf/fpdf.php');

    $where = buildWhere($conn, $f_search, $f_role, $f_module, $f_from, $f_to);
    $res   = mysqli_query($conn, "SELECT * FROM activity_logs $where ORDER BY $sort_col $sort_dir");

    $pdf = new FPDF('L', 'mm', 'A4');
    $pdf->AddPage();

    $pdf->SetFont('Arial', 'B', 14);
    $pdf->Cell(0, 10, 'Smash Arena - Activity Logs', 0, 1, 'L');

    $pdf->SetFont('Arial', '', 10);
    $pdf->Cell(0, 6, 'Generated: ' . date('d M Y H:i:s'), 0, 1, 'L');

    if ($has_filter) {
        $filters = [];
        if ($f_search !== '') $filters[] = 'Search: ' . $f_search;
        if ($f_role   !== '') $filters[] = 'Role: '   . $f_role;
        if ($f_module !== '') $filters[] = 'Module: ' . $f_module;
        if ($f_from   !== '') $filters[] = 'From: '   . $f_from;
        if ($f_to     !== '') $filters[] = 'To: '     . $f_to;
        $pdf->Cell(0, 6, 'Filters: ' . implode(' | ', $filters), 0, 1, 'L');
    }

    $pdf->Ln(4);
    $pdf->SetFillColor(241, 245, 249);
    $pdf->SetFont('Arial', 'B', 9);
    $pdf->Cell(38, 8, 'Date & Time', 1, 0, 'L', true);
    $pdf->Cell(30, 8, 'User',        1, 0, 'L', true);
    $pdf->Cell(22, 8, 'Role',        1, 0, 'L', true);
    $pdf->Cell(40, 8, 'Module',      1, 0, 'L', true);
    $pdf->Cell(30, 8, 'Action',      1, 0, 'L', true);
    $pdf->Cell(92, 8, 'Description', 1, 0, 'L', true);
    $pdf->Cell(25, 8, 'IP Address',  1, 1, 'L', true);

    $pdf->SetFont('Arial', '', 8);
    $fill = false;

    while ($row = mysqli_fetch_assoc($res)) {
        $pdf->SetFillColor($fill ? 248 : 255, $fill ? 250 : 255, $fill ? 252 : 255);
        $pdf->Cell(38, 7, date('d M Y H:i', strtotime($row['created_at'])), 1, 0, 'L', true);
        $pdf->Cell(30, 7, $row['username']   ?? '-', 1, 0, 'L', true);
        $pdf->Cell(22, 7, $row['role']       ?? '-', 1, 0, 'L', true);
        $pdf->Cell(40, 7, $row['module']     ?? '-', 1, 0, 'L', true);
        $pdf->Cell(30, 7, $row['action']     ?? '-', 1, 0, 'L', true);
        $pdf->Cell(92, 7, mb_substr($row['description'] ?? '-', 0, 65), 1, 0, 'L', true);
        $pdf->Cell(25, 7, $row['ip_address'] ?? '-', 1, 1, 'L', true);
        $fill = !$fill;
    }

    $pdf->Output('D', 'activity_logs_' . date('Ymd_His') . '.pdf');
    exit();
}

/* TXT export */
if (isset($_GET['export']) && $_GET['export'] === 'txt') {
    $where = buildWhere($conn, $f_search, $f_role, $f_module, $f_from, $f_to);
    $res   = mysqli_query($conn, "SELECT * FROM activity_logs $where ORDER BY $sort_col $sort_dir");

    header('Content-Type: text/plain; charset=utf-8');
    header('Content-Disposition: attachment; filename="activity_logs_' . date('Ymd_His') . '.txt"');

    echo "==============================================================\n";
    echo "  SMASH ARENA - ACTIVITY LOG\n";
    echo "  Generated : " . date('d M Y H:i:s') . "\n";

    if ($has_filter) {
        $filters = [];
        if ($f_search !== '') $filters[] = 'search=' . $f_search;
        if ($f_role   !== '') $filters[] = 'role='   . $f_role;
        if ($f_module !== '') $filters[] = 'module=' . $f_module;
        if ($f_from   !== '') $filters[] = 'from='   . $f_from;
        if ($f_to     !== '') $filters[] = 'to='     . $f_to;
        echo "  Filters   : " . implode(', ', $filters) . "\n";
    }

    echo "==============================================================\n\n";

    while ($row = mysqli_fetch_assoc($res)) {
        $ts   = date('Y-m-d H:i:s', strtotime($row['created_at']));
        $act  = $row['action']   ?? '-';
        $mod  = $row['module']   ?? '-';
        $uname = $row['username'] ?? 'unknown';
        $urole = $row['role']     ?? '?';
        $user  = (strtolower($uname) === strtolower($urole)) ? $uname : "$uname/$urole";
        $desc = $row['description'] ?? '-';
        $ip   = $row['ip_address']  ?? '-';
        echo "[$ts] [$act] [$mod] [$user] $desc  |  IP: $ip\n";
    }

    exit();
}

/* Pagination */
$per_page    = 15;
$page        = max(1, (int)($_GET['page'] ?? 1));
$where       = buildWhere($conn, $f_search, $f_role, $f_module, $f_from, $f_to);

$total_res   = mysqli_query($conn, "SELECT COUNT(*) AS cnt FROM activity_logs $where");
$total_rows  = (int)mysqli_fetch_assoc($total_res)['cnt'];
$total_pages = max(1, (int)ceil($total_rows / $per_page));
$page        = min($page, $total_pages);
$offset      = ($page - 1) * $per_page;

$logs_res    = mysqli_query($conn, "SELECT * FROM activity_logs $where ORDER BY $sort_col $sort_dir LIMIT $per_page OFFSET $offset");

$roles_res   = mysqli_query($conn, "SELECT DISTINCT role   FROM activity_logs WHERE role   IS NOT NULL ORDER BY role");
$modules_res = mysqli_query($conn, "SELECT DISTINCT module FROM activity_logs WHERE module IS NOT NULL ORDER BY module");

function pageQS($p, $sort, $dir, $search, $role, $module, $from, $to) {
    $params = ['page' => $p, 'sort' => $sort, 'dir' => $dir];
    if ($search !== '') $params['search'] = $search;
    if ($role   !== '') $params['role']   = $role;
    if ($module !== '') $params['module'] = $module;
    if ($from   !== '') $params['from']   = $from;
    if ($to     !== '') $params['to']     = $to;
    return http_build_query($params);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Activity Logs - Smash Arena</title>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800;900&display=swap">

    <link rel="stylesheet" href="../Dashboard/Dashboard.css">
    <link rel="stylesheet" href="../Superadmin/AdminManagement.css">
    <link rel="stylesheet" href="../Courts_Management/ManageCourts.css">
    <link rel="stylesheet" href="ActivityLogs.css">
</head>
<body>

<?php include '../navbar.php'; ?>

<main class="content">
    <div class="manage-container">

        <header class="management-header">
            <div>
                <h1>Activity Logs</h1>
                <p>Full audit trail of all system actions.</p>
            </div>
            <div class="btn-add-group">
                <button class="btn-filter-toggle" onclick="toggleFilter()">
                    <i class="fas fa-filter"></i> Filter
                    <?php if ($has_filter): ?>
                        <span class="filter-dot"></span>
                    <?php endif; ?>
                </button>
                <a href="ActivityLogs.php?export=pdf&search=<?php echo urlencode($f_search); ?>&role=<?php echo urlencode($f_role); ?>&module=<?php echo urlencode($f_module); ?>&from=<?php echo urlencode($f_from); ?>&to=<?php echo urlencode($f_to); ?>&sort=<?php echo $sort_col; ?>&dir=<?php echo strtolower($sort_dir); ?>" class="btn-export" onclick="showExportToast('PDF')">
                    <i class="fas fa-file-pdf"></i> PDF
                </a>
                <a href="ActivityLogs.php?export=txt&search=<?php echo urlencode($f_search); ?>&role=<?php echo urlencode($f_role); ?>&module=<?php echo urlencode($f_module); ?>&from=<?php echo urlencode($f_from); ?>&to=<?php echo urlencode($f_to); ?>&sort=<?php echo $sort_col; ?>&dir=<?php echo strtolower($sort_dir); ?>" class="btn-export" onclick="showExportToast('TXT')">
                    <i class="fas fa-file-lines"></i> TXT
                </a>
            </div>
        </header>

        <div class="filter-panel <?php echo $has_filter ? 'open' : ''; ?>" id="filterPanel">
            <form method="GET" class="filter-grid">
                <div class="filter-field">
                    <label>Search</label>
                    <input type="text" name="search" placeholder="User, action, description..." value="<?php echo htmlspecialchars($f_search); ?>">
                </div>
                <div class="filter-field">
                    <label>Role</label>
                    <select name="role">
                        <option value="">All Roles</option>
                        <?php while ($r = mysqli_fetch_assoc($roles_res)): ?>
                            <option value="<?php echo htmlspecialchars($r['role']); ?>" <?php echo $f_role === $r['role'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($r['role']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="filter-field">
                    <label>Module</label>
                    <select name="module">
                        <option value="">All Modules</option>
                        <?php while ($m = mysqli_fetch_assoc($modules_res)): ?>
                            <option value="<?php echo htmlspecialchars($m['module']); ?>" <?php echo $f_module === $m['module'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($m['module']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="filter-field">
                    <label>From</label>
                    <input type="date" name="from" value="<?php echo htmlspecialchars($f_from); ?>">
                </div>
                <div class="filter-field">
                    <label>To</label>
                    <input type="date" name="to" value="<?php echo htmlspecialchars($f_to); ?>">
                </div>
                <input type="hidden" name="sort" value="<?php echo $sort_col; ?>">
                <input type="hidden" name="dir"  value="<?php echo strtolower($sort_dir); ?>">
                <div class="filter-actions">
                    <button type="submit" class="btn-filter-apply"><i class="fas fa-search"></i> Apply</button>
                    <a href="ActivityLogs.php" class="btn-filter-clear">Clear</a>
                </div>
            </form>
        </div>


        <table class="data-table">
            <thead>
                <tr>
                    <th><?php echo logSortLink('Date & Time', 'created_at', $sort_col, $sort_dir, $next_dir, $f_search, $f_role, $f_module, $f_from, $f_to); ?></th>
                    <th><?php echo logSortLink('User',        'username',   $sort_col, $sort_dir, $next_dir, $f_search, $f_role, $f_module, $f_from, $f_to); ?></th>
                    <th><?php echo logSortLink('Module',      'module',     $sort_col, $sort_dir, $next_dir, $f_search, $f_role, $f_module, $f_from, $f_to); ?></th>
                    <th><?php echo logSortLink('Action',      'action',     $sort_col, $sort_dir, $next_dir, $f_search, $f_role, $f_module, $f_from, $f_to); ?></th>
                    <th><?php echo logSortLink('Description', 'description',$sort_col, $sort_dir, $next_dir, $f_search, $f_role, $f_module, $f_from, $f_to); ?></th>
                    <th><?php echo logSortLink('IP Address',  'ip_address', $sort_col, $sort_dir, $next_dir, $f_search, $f_role, $f_module, $f_from, $f_to); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if ($total_rows === 0): ?>
                    <tr>
                        <td colspan="6" style="text-align:center; padding:48px 20px; color:var(--text-muted);">
                            <i class="fas fa-inbox" style="font-size:28px; display:block; margin-bottom:10px; color:#cbd5e1;"></i>
                            No log entries found.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php while ($log = mysqli_fetch_assoc($logs_res)): ?>
                        <tr>
                            <td>
                                <strong><?php echo date('d M Y', strtotime($log['created_at'])); ?></strong><br>
                                <small style="color:var(--text-muted);"><?php echo date('H:i:s', strtotime($log['created_at'])); ?></small>
                            </td>
                            <td>
                                <small class="role-label"><?php echo htmlspecialchars($log['username'] ?? '-'); ?></small>
                            </td>
                            <td><?php echo htmlspecialchars($log['module'] ?? '-'); ?></td>
                            <td><?php echo actionBadge($log['action']); ?></td>
                            <td style="font-size:13px; color:var(--text-muted);"><?php echo htmlspecialchars($log['description'] ?? '-'); ?></td>
                            <td style="font-family:monospace; font-size:12px; color:var(--text-muted);"><?php echo htmlspecialchars($log['ip_address'] ?? '-'); ?></td>
                        </tr>
                    <?php endwhile; ?>
                <?php endif; ?>
            </tbody>
        </table>

        <?php if ($total_pages > 1): ?>
            <div class="log-pagination">
                <?php if ($page > 1): ?>
                    <a href="?<?php echo pageQS($page - 1, $sort_col, strtolower($sort_dir), $f_search, $f_role, $f_module, $f_from, $f_to); ?>" class="page-btn">
                        <i class="fas fa-chevron-left"></i>
                    </a>
                <?php else: ?>
                    <span class="page-btn disabled"><i class="fas fa-chevron-left"></i></span>
                <?php endif; ?>

                <form method="GET" class="page-jump-form">
                    <input type="number" name="page" class="page-jump-input"
                           value="<?php echo $page; ?>" min="1" max="<?php echo $total_pages; ?>">
                    <span class="page-jump-of">/ <?php echo $total_pages; ?></span>
                    <input type="hidden" name="sort"   value="<?php echo $sort_col; ?>">
                    <input type="hidden" name="dir"    value="<?php echo strtolower($sort_dir); ?>">
                    <?php if ($f_search !== '') echo '<input type="hidden" name="search" value="' . htmlspecialchars($f_search) . '">'; ?>
                    <?php if ($f_role   !== '') echo '<input type="hidden" name="role"   value="' . htmlspecialchars($f_role)   . '">'; ?>
                    <?php if ($f_module !== '') echo '<input type="hidden" name="module" value="' . htmlspecialchars($f_module) . '">'; ?>
                    <?php if ($f_from   !== '') echo '<input type="hidden" name="from"   value="' . htmlspecialchars($f_from)   . '">'; ?>
                    <?php if ($f_to     !== '') echo '<input type="hidden" name="to"     value="' . htmlspecialchars($f_to)     . '">'; ?>
                </form>

                <?php if ($page < $total_pages): ?>
                    <a href="?<?php echo pageQS($page + 1, $sort_col, strtolower($sort_dir), $f_search, $f_role, $f_module, $f_from, $f_to); ?>" class="page-btn">
                        <i class="fas fa-chevron-right"></i>
                    </a>
                <?php else: ?>
                    <span class="page-btn disabled"><i class="fas fa-chevron-right"></i></span>
                <?php endif; ?>
            </div>
        <?php endif; ?>

    </div>
</main>

<script>
    function toggleFilter() {
        const panel = document.getElementById('filterPanel');
        panel.classList.toggle('open');
    }

    function showExportToast(type) {
        setTimeout(function() {
            if (window.Toast) {
                Toast.show(type + ' exported successfully!', 'success');
            }
        }, 800);
    }
</script>

<?php include __DIR__ . '/../scroll_top.php'; ?>
<?php include __DIR__ . '/../toast/toast.php'; ?>

</body>
</html>
