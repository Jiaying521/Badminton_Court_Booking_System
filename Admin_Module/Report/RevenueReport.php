<?php
session_start();

if(!isset($_SESSION['id']) || !in_array($_SESSION['role'],['Superadmin', 'Admin']))
{
    header("Location: ../LoginPage.php");
    exit();
}

$role         = $_SESSION['role'];
$username     = $_SESSION['username'];
$display_name = $username;
$base_path    = '../';

$db = mysqli_connect("localhost", "root", "", "badminton_hub");

$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01'); // default to first day of current month
$end_date   = isset($_GET['end_date'])   ? $_GET['end_date']   : date('Y-m-t');  // default to last day of current month

$s = mysqli_real_escape_string($db, $start_date); // start date (prevent sql injection)
$e = mysqli_real_escape_string($db, $end_date);   // end date (prevent sql injection)

// KPI Overview Query
$q_kpi = mysqli_query($db, "
    SELECT
        COUNT(DISTINCT b.id) AS total_bookings,            /* Distinct = 不重复; b = bookings */
        COALESCE(SUM(p.final_amount), 0) AS total_revenue, /* COALESCE = 如果SUM结果是空的就显示0; p = payments */
        COALESCE(AVG(p.final_amount), 0) AS avg_revenue
    FROM bookings b
    LEFT JOIN payments p ON p.booking_id = b.id AND p.payment_status = 'success'
    WHERE b.booking_date BETWEEN '$s' AND '$e'
");
$kpi = mysqli_fetch_assoc($q_kpi);

// Revenue by Court Query
$q_by_court = mysqli_query($db, "
    SELECT
        c.court_name,
        COUNT(b.id) AS bookings_count,
        COALESCE(SUM(p.final_amount), 0) AS revenue
    FROM bookings b
    JOIN courts c ON c.id = b.court_id
    LEFT JOIN payments p ON p.booking_id = b.id AND p.payment_status = 'success'
    WHERE b.booking_date BETWEEN '$s' AND '$e'
    GROUP BY c.id, c.court_name    /* c = courts */
    ORDER BY revenue DESC          /* DESC = Descending order */
");

// Revenue by Payment Method Query
$q_by_method = mysqli_query($db, "
    SELECT
        payment_method,
        COUNT(*) AS total_transactions,
        SUM(final_amount) AS revenue
    FROM payments
    WHERE payment_status = 'success'
    AND DATE(payment_date) BETWEEN '$s' AND '$e'
    GROUP BY payment_method
    ORDER BY revenue DESC
");

// Booking Status Breakdown Query
$q_by_status = mysqli_query($db, "
    SELECT
        status,
        COUNT(*) AS total
    FROM bookings
    WHERE booking_date BETWEEN '$s' AND '$e'
    GROUP BY status
");

// Daily Revenue Query (for chart)
$q_daily = mysqli_query($db, "
    SELECT
        b.booking_date,
        COALESCE(SUM(p.final_amount), 0) AS revenue
    FROM bookings b
    LEFT JOIN payments p ON p.booking_id = b.id AND p.payment_status = 'success'
    WHERE b.booking_date BETWEEN '$s' AND '$e'
    GROUP BY b.booking_date
    ORDER BY b.booking_date ASC
");

// Convert daily revenue to arrays for Chart.js
$chart_labels = [];
$chart_values = [];
while($row = mysqli_fetch_assoc($q_daily)) {
    $chart_labels[] = $row['booking_date'];
    $chart_values[] = (float)$row['revenue'];
}

// Pre-fetch rows into arrays so we can check if empty (for empty state display)
$court_rows = [];
while($r = mysqli_fetch_assoc($q_by_court))  $court_rows[]  = $r;

$method_rows = [];
while($r = mysqli_fetch_assoc($q_by_method)) $method_rows[] = $r;

$status_rows = [];
while($r = mysqli_fetch_assoc($q_by_status)) $status_rows[] = $r;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Revenue Report</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800;900&display=swap">
    <link rel="stylesheet" href="../Dashboard/Dashboard.css">
    <link rel="stylesheet" href="RevenueReport.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>

<?php include '../navbar.php'; ?>

<main class="content">

    <header class="dashboard-header">
        <div class="welcome-section">
            <h1><i class="fas fa-chart-line" style="color: var(--primary); margin-right: 10px;"></i>Revenue Report</h1>
            <p>Track bookings and revenue performance over time</p>
        </div>
    </header>

    <!-- KPI Cards -->
    <section class="stats-grid" style="margin-top: 28px;">
        <div class="stat-box">
            <div class="stat-icon orange"><i class="fas fa-dollar-sign"></i></div>
            <div class="stat-info">
                <h3>Total Revenue</h3>
                <p>RM <?php echo number_format($kpi['total_revenue'], 2); ?></p>
            </div>
        </div>

        <div class="stat-box">
            <div class="stat-icon blue"><i class="fas fa-calendar-check"></i></div>
            <div class="stat-info">
                <h3>Total Bookings</h3>
                <p><?php echo $kpi['total_bookings']; ?></p>
            </div>
        </div>

        <div class="stat-box">
            <div class="stat-icon green"><i class="fas fa-chart-bar"></i></div>
            <div class="stat-info">
                <h3>Avg per Booking</h3>
                <p>RM <?php echo number_format($kpi['avg_revenue'], 2); ?></p>
            </div>
        </div>
    </section>

    <!-- Date Filter Form -->
    <form method="GET" class="filter-form">
        <label>From: <input type="date" name="start_date" value="<?php echo $start_date; ?>" required></label>
        <label>To: <input type="date" name="end_date" value="<?php echo $end_date; ?>" required></label>
        <button type="submit"><i class="fas fa-search"></i> Generate</button>
        <button type="button" class="btn-export" onclick="openExportModal()">
            <i class="fas fa-file-pdf"></i> Export PDF
        </button>
    </form>

    <!-- Export PDF Modal -->
    <div id="exportModal" style="display:none; position:fixed; inset:0; background:rgba(15,23,42,0.45); z-index:2000; align-items:center; justify-content:center; padding:20px;">
        <div style="background:#fff; border-radius:12px; padding:10px; width:100%; max-width:460px; box-shadow:0 20px 60px rgba(0,0,0,0.15);">

            <div class="modal-header">
                <h2><i class="fas fa-file-pdf"></i> Export Report</h2>
                <button type="button" class="modal-close" onclick="closeExportModal()">&times;</button>
            </div>

            <div style="padding: 0 10px 10px;">

                <p class="export-section-label">1. Select Report Period</p>
                <div class="export-radio-group">
                    <label class="export-radio">
                        <input type="radio" name="exportScope" value="current" checked onchange="toggleCustomDate(false)">
                        Current Month (<?php echo date('M Y'); ?>)
                    </label>
                    <label class="export-radio">
                        <input type="radio" name="exportScope" value="custom" onchange="toggleCustomDate(true)">
                        Custom Date Range
                    </label>
                </div>

                <div id="customDateBox" class="export-custom-dates" style="display:none;">
                    <label>From
                        <input type="date" id="exportStart" value="<?php echo $start_date; ?>">
                    </label>
                    <label>To
                        <input type="date" id="exportEnd" value="<?php echo $end_date; ?>">
                    </label>
                </div>

                <p class="export-section-label">2. Choose Action</p>
                <div class="modal-actions">
                    <button type="button" class="btn-modal-cancel" onclick="closeExportModal()">Cancel</button>
                    <button type="button" class="btn-modal-print" onclick="runExport('print')">
                        <i class="fas fa-print"></i> Print
                    </button>
                    <button type="button" class="btn-modal-save" onclick="runExport('save')">
                        <i class="fas fa-download"></i> Save PDF
                    </button>
                </div>

            </div>
        </div>
    </div>

    <!-- Daily Revenue Chart -->
    <div class="data-section" style="margin-bottom: 22px;">
        <h2>Daily Revenue</h2>
        <div style="height: 300px; width: 100%;">
            <canvas id="dailyChart"></canvas>
        </div>
    </div>

    <!-- Revenue by Court Table -->
    <div class="data-section" style="margin-bottom: 22px;">
        <h2>Revenue by Court</h2>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Court</th>
                    <th>Bookings</th>
                    <th>Revenue (RM)</th>
                </tr>
            </thead>
            <tbody>
            <?php if(empty($court_rows)): ?>
                <tr class="empty-row">
                    <td colspan="3">
                        <i class="fas fa-inbox"></i>
                        No data found for this period.
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach($court_rows as $r): ?>
                <tr>
                    <td><?php echo htmlspecialchars($r['court_name']); ?></td>
                    <td><?php echo $r['bookings_count']; ?></td>
                    <td><?php echo number_format($r['revenue'], 2); ?></td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Revenue by Payment Method Table -->
    <div class="data-section" style="margin-bottom: 22px;">
        <h2>Revenue by Payment Method</h2>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Method</th>
                    <th>Transactions</th>
                    <th>Revenue (RM)</th>
                </tr>
            </thead>
            <tbody>
            <?php if(empty($method_rows)): ?>
                <tr class="empty-row">
                    <td colspan="3">
                        <i class="fas fa-inbox"></i>
                        No data found for this period.
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach($method_rows as $r): ?>
                <tr>
                    <td><?php echo htmlspecialchars($r['payment_method']); ?></td>
                    <td><?php echo $r['total_transactions']; ?></td>
                    <td><?php echo number_format($r['revenue'], 2); ?></td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Booking Status Breakdown Table -->
    <div class="data-section" style="margin-bottom: 22px;">
        <h2>Booking Status Breakdown</h2>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Status</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
            <?php if(empty($status_rows)): ?>
                <tr class="empty-row">
                    <td colspan="2">
                        <i class="fas fa-inbox"></i>
                        No data found for this period.
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach($status_rows as $r): ?>
                <tr>
                    <td>
                        <?php
                            $s_class = '';
                            if($r['status'] === 'Confirmed') $s_class = 'done';
                            elseif($r['status'] === 'Pending') $s_class = 'pending';
                            elseif($r['status'] === 'Completed') $s_class = 'success';
                        ?>
                        <span class="badge <?php echo $s_class; ?>"><?php echo htmlspecialchars($r['status']); ?></span>
                    </td>
                    <td><?php echo $r['total']; ?></td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

</main>

<!-- Pass PHP data to JS -->
<script>
    const chartLabels = <?php echo json_encode($chart_labels); ?>;
    const chartValues = <?php echo json_encode($chart_values); ?>;
</script>
<script src="RevenueReport.js"></script>

</body>
</html>