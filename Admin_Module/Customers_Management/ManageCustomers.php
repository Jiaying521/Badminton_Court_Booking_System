<?php 
    //LOGIN Check
    session_start();
    if(!isset($_SESSION['username'])){
        header("Location: ../LoginPage.php");
        exit();
    }

    //Role check
    if(!in_array($_SESSION['role'], ['Superadmin', 'Admin'])){
        header("Location: ../LoginPage.php");
        exit();
    }

    //Prevent browser cache
    header("Cache-Control: no-cache, no-store, must-revalidate");
    header("Pragma: no-cache");
    header("Expires: 0");

    //Database connection
    $conn = mysqli_connect("localhost", "root", "", "badminton_hub");

    $username     = $_SESSION['username'];
    $role         = $_SESSION['role'];
    $display_name = $username;

    // This page sits at Admin_Module root, so navbar links don't need a prefix.
    $base_path = '../';

    $message = "";

    // ── AJAX: fetch customer details ──────────────────────────────────────────
    if(isset($_GET['fetch_details'])){
        $id = intval($_GET['fetch_details']);

        $user = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM users WHERE id = $id"));

        $stats = mysqli_fetch_assoc(mysqli_query($conn, "
            SELECT COUNT(*) AS total_bookings,
                   SUM(CASE WHEN status != 'Cancelled' THEN total_price ELSE 0 END) AS total_spent
            FROM bookings WHERE user_id = $id
        "));

        $recentRes = mysqli_query($conn, "
            SELECT b.id, b.booking_date, b.start_time, b.end_time, b.status, c.court_name
            FROM bookings b
            LEFT JOIN courts c ON b.court_id = c.id
            WHERE b.user_id = $id
            ORDER BY b.booking_date DESC LIMIT 10
        ");
        $recent = [];
        while($r = mysqli_fetch_assoc($recentRes)) $recent[] = $r;

        unset($user['password']);
        header('Content-Type: application/json');
        echo json_encode(['user' => $user, 'stats' => $stats, 'recent' => $recent]);
        exit();
    }

    // ── Handle edit customer ──────────────────────────────────────────────────
    if(isset($_POST['update_customer'])){
        $user_id        = intval($_POST['user_id']);
        $name           = mysqli_real_escape_string($conn, $_POST['customer_name']);
        $phone          = mysqli_real_escape_string($conn, $_POST['phone']);
        $gender         = mysqli_real_escape_string($conn, $_POST['gender'] ?? '');
        $wallet         = floatval($_POST['wallet_balance']);
        $loyalty_points = intval($_POST['loyalty_points']);

        $img_sql = "";
        if(!empty($_POST['cropped_img_data'])){
            $img_data    = $_POST['cropped_img_data'];
            $img_data    = str_replace('data:image/png;base64,', '', $img_data);
            $img_data    = str_replace(' ', '+', $img_data);
            $img_decoded = base64_decode($img_data);
            $img_name    = time() . '_user.png';
            $upload_path = '../../Pictures/Admin_Module/users/' . $img_name;
            if(file_put_contents($upload_path, $img_decoded)){
                $img_sql = ", profile_picture = '$img_name'";
            }
        }

        $gender_sql = $gender ? ", gender = '$gender'" : "";

        $result_upd = mysqli_query($conn, "
            UPDATE users SET
                name           = '$name',
                phone          = '$phone',
                wallet_balance = $wallet,
                loyalty_points = $loyalty_points
                $gender_sql
                $img_sql
            WHERE id = $user_id
        ");

        if($result_upd){
            header("Location: ManageCustomers.php?success=1");
        } else {
            $message = "<div class='badge pending' style='width:100%; padding:15px; margin-bottom:20px;'>Database Error: " . mysqli_error($conn) . "</div>";
        }
        exit();
    }

    // ── Handle wallet refund / deduct ─────────────────────────────────────────
    if(isset($_POST['adjust_wallet'])){
        $user_id = intval($_POST['wallet_user_id']);
        $amount  = floatval($_POST['adjust_amount']);
        $action  = $_POST['adjust_action'];

        if($action === 'refund'){
            mysqli_query($conn, "UPDATE users SET wallet_balance = wallet_balance + $amount WHERE id = $user_id");
        } elseif($action === 'deduct'){
            mysqli_query($conn, "UPDATE users SET wallet_balance = GREATEST(0, wallet_balance - $amount) WHERE id = $user_id");
        }

        header("Location: ManageCustomers.php?wallet_updated=1");
        exit();
    }

    // ── Sort handling ─────────────────────────────────────────────────────────
    $allowed_sorts = ['id', 'name', 'email', 'wallet_balance', 'loyalty_points', 'created_at'];
    $sort_col = isset($_GET['sort']) && in_array($_GET['sort'], $allowed_sorts) ? $_GET['sort'] : 'id';
    $sort_dir = isset($_GET['dir']) && $_GET['dir'] === 'asc' ? 'ASC' : 'DESC';
    $next_dir = ($sort_dir === 'ASC') ? 'desc' : 'asc';

    // ── Filter values ─────────────────────────────────────────────────────────
    $filter_name        = isset($_GET['search_name'])  ? mysqli_real_escape_string($conn, $_GET['search_name'])  : '';
    $filter_phone       = isset($_GET['search_phone']) ? mysqli_real_escape_string($conn, $_GET['search_phone']) : '';
    $filter_email       = isset($_GET['search_email']) ? mysqli_real_escape_string($conn, $_GET['search_email']) : '';
    $filter_gender      = isset($_GET['gender'])       ? mysqli_real_escape_string($conn, $_GET['gender'])       : '';
    $filter_joined_from = isset($_GET['joined_from'])  ? mysqli_real_escape_string($conn, $_GET['joined_from'])  : '';
    $filter_joined_to   = isset($_GET['joined_to'])    ? mysqli_real_escape_string($conn, $_GET['joined_to'])    : '';

    $has_filter = ($filter_name || $filter_phone || $filter_email || $filter_gender || $filter_joined_from || $filter_joined_to);

    // ── Build WHERE ───────────────────────────────────────────────────────────
    $where_parts = [];
    if($filter_name)        $where_parts[] = "name LIKE '%$filter_name%'";
    if($filter_phone)       $where_parts[] = "phone LIKE '%$filter_phone%'";
    if($filter_email)       $where_parts[] = "email LIKE '%$filter_email%'";
    if($filter_gender)      $where_parts[] = "gender = '$filter_gender'";
    if($filter_joined_from) $where_parts[] = "DATE(created_at) >= '$filter_joined_from'";
    if($filter_joined_to)   $where_parts[] = "DATE(created_at) <= '$filter_joined_to'";
    $where_sql = count($where_parts) > 0 ? "WHERE " . implode(" AND ", $where_parts) : "";

    // ── Fetch customers ───────────────────────────────────────────────────────
    $result = mysqli_query($conn, "
        SELECT id, name, email, phone, gender, created_at,
               wallet_balance, loyalty_points, profile_picture
        FROM users
        $where_sql
        ORDER BY $sort_col $sort_dir
    ");

    // ── Stats ─────────────────────────────────────────────────────────────────
    $total_customers = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS cnt FROM users"))['cnt'] ?? 0;
    $new_this_month  = mysqli_fetch_assoc(mysqli_query($conn, "
        SELECT COUNT(*) AS cnt FROM users
        WHERE MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE())
    "))['cnt'] ?? 0;
    $total_revenue = number_format(
        mysqli_fetch_assoc(mysqli_query($conn, "
            SELECT SUM(final_amount) AS rev FROM payments
            WHERE payment_status = 'success' AND payment_method != 'Refund'
        "))['rev'] ?? 0, 2
    );

    // ── Sort link helper ──────────────────────────────────────────────────────
    function customerSortLink($label, $col, $sort_col, $sort_dir, $next_dir,
                               $fn, $fp, $fe, $fg, $fjf, $fjt) {
        $is_active = ($sort_col === $col);
        $dir   = $is_active ? $next_dir : 'desc';
        $arrow = $is_active
            ? ($sort_dir === 'ASC'
                ? ' <i class="fas fa-arrow-up sort-arrow active-arrow"></i>'
                : ' <i class="fas fa-arrow-down sort-arrow active-arrow"></i>')
            : ' <i class="fas fa-sort sort-arrow"></i>';
        $params = http_build_query([
            'sort'        => $col,
            'dir'         => $dir,
            'search_name' => $fn,
            'search_phone'=> $fp,
            'search_email'=> $fe,
            'gender'      => $fg,
            'joined_from' => $fjf,
            'joined_to'   => $fjt,
        ]);
        return "<a href='ManageCustomers.php?$params' class='sort-link'>$label$arrow</a>";
    }

    // ── Chart data (no date limit — JS handles 12-month window) ──────────────
    $chart_users_raw = mysqli_query($conn, "
        SELECT DATE_FORMAT(created_at,'%Y-%m') AS mo, COUNT(*) AS cnt
        FROM users
        GROUP BY mo ORDER BY mo ASC
    ");
    $chart_user_labels = []; $chart_user_data = [];
    while($r = mysqli_fetch_assoc($chart_users_raw)){
        $chart_user_labels[] = date('M Y', strtotime($r['mo'].'-01'));
        $chart_user_data[]   = (int)$r['cnt'];
    }

    $chart_rev_raw = mysqli_query($conn, "
        SELECT DATE_FORMAT(payment_date,'%Y-%m') AS mo, SUM(final_amount) AS total
        FROM payments
        WHERE payment_status = 'success' AND payment_method != 'Refund'
        GROUP BY mo ORDER BY mo ASC
    ");
    $chart_rev_labels = []; $chart_rev_data = [];
    while($r = mysqli_fetch_assoc($chart_rev_raw)){
        $chart_rev_labels[] = date('M Y', strtotime($r['mo'].'-01'));
        $chart_rev_data[]   = (float)$r['total'];
    }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Badminton Hub - Customer Management</title>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800;900&display=swap">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.css">

    <link rel="stylesheet" href="../Dashboard/Dashboard.css">
    <link rel="stylesheet" href="../Superadmin/AdminManagement.css">
    <link rel="stylesheet" href="ManageCustomers.css">

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>

<body>

    <?php include '../navbar.php'; ?>

    <main class="content">
        <div class="manage-container">

            <header class="management-header">
                <div>
                    <h1>Customer Management</h1>
                    <p>View and manage customer profiles, wallet balances and details.</p>
                </div>
            </header>

            <?php if($message !== "") echo $message; ?>

            <?php if(isset($_GET['success'])): ?>
                <div class="badge success" style="width:100%; padding:15px; margin-bottom:20px;">Customer updated successfully!</div>
            <?php endif; ?>

            <?php if(isset($_GET['wallet_updated'])): ?>
                <div class="badge success" style="width:100%; padding:15px; margin-bottom:20px;">Wallet balance updated successfully!</div>
            <?php endif; ?>

            <!-- Summary Stats -->
            <div class="customer-stats">
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-users"></i></div>
                    <div class="stat-info">
                        <span class="stat-value"><?php echo $total_customers; ?></span>
                        <span class="stat-label">Total Customers</span>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon" style="background:rgba(16,185,129,0.12); color:#10b981;">
                        <i class="fas fa-user-plus"></i>
                    </div>
                    <div class="stat-info">
                        <span class="stat-value"><?php echo $new_this_month; ?></span>
                        <span class="stat-label">New This Month</span>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon" style="background:rgba(99,102,241,0.12); color:#6366f1;">
                        <i class="fas fa-money-bill-wave"></i>
                    </div>
                    <div class="stat-info">
                        <span class="stat-value">RM <?php echo $total_revenue; ?></span>
                        <span class="stat-label">Total Revenue</span>
                    </div>
                </div>
            </div>

            <!-- Chart Block -->
            <div class="chart-block">
                <div class="chart-header">
                    <div>
                        <h3 class="chart-title" id="chartTitle">New Customers</h3>
                        <p class="chart-subtitle" id="chartSubtitle">Monthly registrations over past 12 months</p>
                    </div>
                    <div class="chart-header-right">
                        <div class="chart-date-filter">
                            <label>From</label>
                            <input type="date" id="chartFrom">
                            <label>To</label>
                            <input type="date" id="chartTo">
                            <button class="btn-chart-apply" onclick="applyChartFilter()">
                                <i class="fas fa-search"></i>
                            </button>
                            <button class="btn-chart-reset" onclick="resetChartFilter()" title="Reset">
                                <i class="fas fa-rotate-left"></i>
                            </button>
                        </div>
                        <div class="chart-toggle">
                            <button class="chart-btn active" id="btnUsers" onclick="switchChart('users')">
                                <i class="fas fa-users"></i> Customers
                            </button>
                            <button class="chart-btn" id="btnRevenue" onclick="switchChart('revenue')">
                                <i class="fas fa-money-bill-wave"></i> Revenue
                            </button>
                        </div>
                    </div>
                </div>
                <div class="chart-wrap">
                    <canvas id="customerChart"></canvas>
                </div>
            </div>

            <!-- Filter Button Below Chart -->
            <div class="filter-btn-wrapper">
                <button class="btn-filter-toggle" onclick="toggleFilter()">
                    <i class="fas fa-filter"></i> Filter
                    <?php if($has_filter): ?>
                        <span class="filter-dot"></span>
                    <?php endif; ?>
                </button>
            </div>

            <!-- Collapsible Filter Panel -->
            <div class="filter-panel <?php echo $has_filter ? 'open' : ''; ?>" id="filterPanel">
                <form method="GET" class="filter-grid">
                    <div class="filter-field">
                        <label>Name</label>
                        <input type="text" name="search_name" placeholder="Customer name..."
                               value="<?php echo htmlspecialchars($filter_name); ?>">
                    </div>
                    <div class="filter-field">
                        <label>Phone</label>
                        <input type="text" name="search_phone" placeholder="+601XXXXXXXX"
                               value="<?php echo htmlspecialchars($filter_phone); ?>">
                    </div>
                    <div class="filter-field">
                        <label>Email</label>
                        <input type="text" name="search_email" placeholder="email@..."
                               value="<?php echo htmlspecialchars($filter_email); ?>">
                    </div>
                    <div class="filter-field">
                        <label>Gender</label>
                        <select name="gender">
                            <option value="">All</option>
                            <option value="Male"   <?php echo $filter_gender === 'Male'   ? 'selected' : ''; ?>>Male</option>
                            <option value="Female" <?php echo $filter_gender === 'Female' ? 'selected' : ''; ?>>Female</option>
                        </select>
                    </div>
                    <div class="filter-field">
                        <label>Joined From</label>
                        <input type="date" name="joined_from"
                               value="<?php echo htmlspecialchars($filter_joined_from); ?>">
                    </div>
                    <div class="filter-field">
                        <label>Joined To</label>
                        <input type="date" name="joined_to"
                               value="<?php echo htmlspecialchars($filter_joined_to); ?>">
                    </div>
                    <div class="filter-actions">
                        <button type="submit" class="btn-filter-apply">
                            <i class="fas fa-search"></i> Apply
                        </button>
                        <a href="ManageCustomers.php" class="btn-filter-clear">Clear</a>
                    </div>
                </form>
            </div>

            <!-- Customers Table -->
            <table class="data-table">
                <thead>
                    <tr>
                        <th><?php echo customerSortLink('ID', 'id', $sort_col, $sort_dir, $next_dir, $filter_name, $filter_phone, $filter_email, $filter_gender, $filter_joined_from, $filter_joined_to); ?></th>
                        <th><?php echo customerSortLink('Customer', 'name', $sort_col, $sort_dir, $next_dir, $filter_name, $filter_phone, $filter_email, $filter_gender, $filter_joined_from, $filter_joined_to); ?></th>
                        <th><?php echo customerSortLink('Email', 'email', $sort_col, $sort_dir, $next_dir, $filter_name, $filter_phone, $filter_email, $filter_gender, $filter_joined_from, $filter_joined_to); ?></th>
                        <th>Phone</th>
                        <th><?php echo customerSortLink('Wallet', 'wallet_balance', $sort_col, $sort_dir, $next_dir, $filter_name, $filter_phone, $filter_email, $filter_gender, $filter_joined_from, $filter_joined_to); ?></th>
                        <th><?php echo customerSortLink('Points', 'loyalty_points', $sort_col, $sort_dir, $next_dir, $filter_name, $filter_phone, $filter_email, $filter_gender, $filter_joined_from, $filter_joined_to); ?></th>
                        <th><?php echo customerSortLink('Joined', 'created_at', $sort_col, $sort_dir, $next_dir, $filter_name, $filter_phone, $filter_email, $filter_gender, $filter_joined_from, $filter_joined_to); ?></th>
                        <th>Actions</th>
                    </tr>
                </thead>

                <tbody>
                    <?php
                    $row_count = 0;
                    while($row = mysqli_fetch_assoc($result)):
                        $row_count++;
                        $avatar = $row['profile_picture']
                            ? '../../Pictures/Admin_Module/users/' . htmlspecialchars($row['profile_picture'])
                            : '../../Pictures/Admin_Module/users/default_avatar.png';
                        $initials    = strtoupper(substr($row['name'], 0, 1));
                        $joined      = date('d M Y', strtotime($row['created_at']));
                        $wallet      = number_format($row['wallet_balance'], 2);
                        $wallet_class = $row['wallet_balance'] > 0 ? 'wallet-positive' : 'wallet-zero';
                        $points      = intval($row['loyalty_points']);
                        $gender_val  = htmlspecialchars($row['gender'] ?? '');
                    ?>
                    <tr class="main-row" onclick="openDetailsModal(<?php echo $row['id']; ?>)" style="cursor:pointer;">
                        <td><span class="cust-id">#<?php echo $row['id']; ?></span></td>
                        <td>
                            <div class="customer-info-cell">
                                <div class="cust-avatar">
                                    <?php if($row['profile_picture']): ?>
                                        <img src="<?php echo $avatar; ?>" alt="<?php echo htmlspecialchars($row['name']); ?>">
                                    <?php else: ?>
                                        <span><?php echo $initials; ?></span>
                                    <?php endif; ?>
                                </div>
                                <strong><?php echo htmlspecialchars($row['name']); ?></strong>
                            </div>
                        </td>
                        <td><span class="cust-email"><?php echo htmlspecialchars($row['email']); ?></span></td>
                        <td><?php echo htmlspecialchars($row['phone'] ?: '—'); ?></td>
                        <td>
                            <span class="wallet-badge <?php echo $wallet_class; ?>">
                                <i class="fas fa-wallet"></i> RM <?php echo $wallet; ?>
                            </span>
                        </td>
                        <td>
                            <span class="points-badge">
                                <i class="fas fa-star"></i> <?php echo $points; ?>
                            </span>
                        </td>
                        <td><span class="joined-date"><?php echo $joined; ?></span></td>
                        <td onclick="event.stopPropagation()">
                            <div class="action-btns">
                                <button class="btn-action-edit"
                                    onclick="openCustomerEditModal(
                                        <?php echo $row['id']; ?>,
                                        '<?php echo addslashes($row['name']); ?>',
                                        '<?php echo addslashes($row['phone'] ?? ''); ?>',
                                        '<?php echo $gender_val; ?>',
                                        <?php echo $row['wallet_balance']; ?>,
                                        <?php echo $points; ?>,
                                        '<?php echo $row['profile_picture'] ?? ''; ?>'
                                    )" title="Edit Customer">
                                    <i class="fas fa-pen"></i>
                                </button>
                                <button class="btn-action-wallet"
                                    onclick="openWalletModal(<?php echo $row['id']; ?>, '<?php echo addslashes($row['name']); ?>', <?php echo $row['wallet_balance']; ?>)"
                                    title="Refund / Deduct Wallet">
                                    <i class="fas fa-wallet"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile; ?>

                    <?php if($row_count === 0): ?>
                    <tr>
                        <td colspan="8" style="text-align:center; padding:40px; color:#94a3b8;">
                            <i class="fas fa-users" style="font-size:28px; display:block; margin-bottom:10px; opacity:0.4;"></i>
                            No customers found.
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>

        </div>
    </main>

    <!-- ============================================================ -->
    <!-- View Details Modal                                           -->
    <!-- ============================================================ -->
    <div class="modal-overlay" id="detailsModal">
        <div class="modal-card" style="max-width:640px;">
            <div class="modal-header">
                <h2><i class="fas fa-user"></i> Customer Details</h2>
                <button class="modal-close" onclick="closeDetailsModal()">✕</button>
            </div>
            <div id="detailsModalBody" style="padding:24px 28px;">
                <p style="color:#94a3b8; text-align:center; padding:40px 0;">Loading...</p>
            </div>
        </div>
    </div>

    <!-- ============================================================ -->
    <!-- Edit Customer Modal                                          -->
    <!-- ============================================================ -->
    <div class="modal-overlay" id="customerEditModal">

        <!-- Edit Panel -->
        <div class="modal-card" id="custEditPanel">
            <div class="modal-header">
                <h2><i class="fas fa-pen"></i> Edit Customer Profile</h2>
                <button class="modal-close" onclick="closeCustomerEditModal()">✕</button>
            </div>

            <form action="ManageCustomers.php" method="POST">
                <input type="hidden" name="user_id"          id="cust-modal-id">
                <input type="hidden" name="cropped_img_data" id="cust-cropped-img-data">

                <div class="modal-grid">

                    <!-- Profile Image -->
                    <div class="modal-field full-width" style="display:flex; flex-direction:column; align-items:center;">
                        <img id="cust-modal-img-preview"
                            src="../../Pictures/Admin_Module/users/default_avatar.png"
                            style="width:80px; height:80px; border-radius:50%; object-fit:cover; margin-bottom:8px; border:3px solid var(--primary);">
                        <label class="btn-create" style="cursor:pointer; padding:8px 16px; font-size:13px;">
                            <i class="fas fa-camera"></i> Change Photo
                            <input type="file" id="cust-img-input" accept="image/*" style="display:none;">
                        </label>
                    </div>

                    <!-- Name -->
                    <div class="modal-field full-width">
                        <label>Full Name</label>
                        <input type="text" name="customer_name" id="cust-modal-name" required>
                    </div>

                    <!-- Phone -->
                    <div class="modal-field">
                        <label>Phone Number</label>
                        <input type="text" name="phone" id="cust-modal-phone" placeholder="+601XXXXXXXX">
                    </div>

                    <!-- Gender -->
                    <div class="modal-field">
                        <label>Gender</label>
                        <select name="gender" id="cust-modal-gender">
                            <option value="">— Select —</option>
                            <option value="Male">Male</option>
                            <option value="Female">Female</option>
                        </select>
                    </div>

                    <!-- Wallet Balance -->
                    <div class="modal-field">
                        <label>Wallet Balance (RM)</label>
                        <input type="number" name="wallet_balance" id="cust-modal-wallet" step="0.01" min="0">
                    </div>

                    <!-- Loyalty Points -->
                    <div class="modal-field">
                        <label>Loyalty Points</label>
                        <input type="number" name="loyalty_points" id="cust-modal-points" min="0">
                    </div>

                </div>

                <div class="modal-actions">
                    <button type="button" class="btn-modal-cancel" onclick="closeCustomerEditModal()">Cancel</button>
                    <button type="submit" name="update_customer" class="btn-modal-save">Save Changes</button>
                </div>
            </form>
        </div>

        <!-- Crop Panel -->
        <div class="modal-card" id="custCropPanel" style="display:none;">
            <div class="modal-header">
                <h2><i class="fas fa-crop-alt"></i> Crop Photo</h2>
            </div>
            <div id="cust-crop-area">
                <img id="cust-crop-img" style="display:block; width:100%;">
            </div>
            <div class="modal-actions">
                <button type="button" class="btn-modal-cancel" onclick="cancelCustCrop()">Back</button>
                <button type="button" class="btn-modal-save"   onclick="applyCustCrop()">Crop &amp; Use</button>
            </div>
        </div>

    </div>

    <!-- ============================================================ -->
    <!-- Wallet Refund / Deduct Modal                                 -->
    <!-- ============================================================ -->
    <div class="modal-overlay" id="walletModal">
        <div class="modal-card" style="max-width:420px;">

            <div class="modal-header">
                <h2><i class="fas fa-undo"></i> Refund / Adjustment</h2>
                <button class="modal-close" onclick="closeWalletModal()">✕</button>
            </div>

            <form action="ManageCustomers.php" method="POST">
                <input type="hidden" name="wallet_user_id" id="wallet-user-id">

                <div class="modal-grid" style="grid-template-columns:1fr;">

                    <div class="wallet-modal-info">
                        <span id="wallet-modal-name">—</span>
                        <div class="wallet-current-badge">
                            <i class="fas fa-wallet"></i>
                            Current: <strong id="wallet-modal-current">RM 0.00</strong>
                        </div>
                    </div>

                    <div class="modal-field">
                        <label>Action</label>
                        <div class="wallet-action-toggle">
                            <label class="wallet-radio">
                                <input type="radio" name="adjust_action" value="refund" checked>
                                <span><i class="fas fa-undo"></i> Refund</span>
                            </label>
                            <label class="wallet-radio">
                                <input type="radio" name="adjust_action" value="deduct">
                                <span><i class="fas fa-minus-circle"></i> Deduct</span>
                            </label>
                        </div>
                    </div>

                    <div class="modal-field">
                        <label>Amount (RM)</label>
                        <input type="number" name="adjust_amount" placeholder="0.00" step="0.01" min="0.01" required
                            style="width:100%; padding:10px 14px; border:1.5px solid var(--border); border-radius:10px; font-size:14px; font-family:'Outfit',sans-serif; outline:none; box-sizing:border-box;">
                    </div>

                    <div class="modal-field">
                        <label>Reason <span style="color:#ef4444;">*</span></label>
                        <input type="text" name="adjust_reason" placeholder="e.g. Booking cancellation refund" required
                            style="width:100%; padding:10px 14px; border:1.5px solid var(--border); border-radius:10px; font-size:14px; font-family:'Outfit',sans-serif; outline:none; box-sizing:border-box;">
                    </div>

                </div>

                <div class="modal-actions">
                    <button type="button" class="btn-modal-cancel" onclick="closeWalletModal()">Cancel</button>
                    <button type="submit" name="adjust_wallet" class="btn-modal-save">Confirm</button>
                </div>
            </form>

        </div>
    </div>

    <script src="../Dashboard/Dashboard.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.js"></script>

    <!-- Pass PHP chart data to JS -->
    <script>
        const chartDatasets = {
            users: {
                allLabels : <?php echo json_encode($chart_user_labels); ?>,
                allData   : <?php echo json_encode($chart_user_data); ?>,
                label  : 'New Customers',
                color  : '#f59e0b',
                fill   : 'rgba(245,158,11,0.08)',
                prefix : '',
                suffix : ' users'
            },
            revenue: {
                allLabels : <?php echo json_encode($chart_rev_labels); ?>,
                allData   : <?php echo json_encode($chart_rev_data); ?>,
                label  : 'Revenue (RM)',
                color  : '#6366f1',
                fill   : 'rgba(99,102,241,0.08)',
                prefix : 'RM ',
                suffix : ''
            }
        };
    </script>

    <script src="ManageCustomers.js"></script>

</body>
</html>