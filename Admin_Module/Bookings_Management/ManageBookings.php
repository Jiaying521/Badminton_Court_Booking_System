<?php
    session_start();
    require_once __DIR__ . '/../toast/toast_init.php';
    if(!isset($_SESSION['username'])){
        header("Location: ../LoginPage.php");
        exit();
    }

    // Check role - only Superadmin, Admin and Coach can access
    if(!in_array($_SESSION['role'], ['Superadmin', 'Admin', 'Coach'])){
        header("Location: ../LoginPage.php");
        exit();
    }

    // Prevent Browser Caching
    header("Cache-Control: no-cache, no-store, must-revalidate"); 
    header("Pragma: no-cache");
    header("Expires: 0");

    // Toast notifications from URL params (redirects from AddBooking / edit / bulk actions)
    if (isset($_GET['declined'])) {
        if ($_GET['declined'] === 'late') {
            $ban = $_GET['ban'] ?? '';
            $ban_msg = ($ban === 'perm') ? 'Your account has been permanently suspended.' : "Your account has been suspended for {$ban} days.";
            $toasts[] = ['text' => 'Late cancellation. The customer was fully refunded with compensation. ' . $ban_msg, 'type' => 'error'];
        } elseif ($_GET['declined'] === 'ontime') {
            $toasts[] = ['text' => 'Session declined. The customer has been fully refunded. No penalty applied.', 'type' => 'success'];
        } else {
            $toasts[] = ['text' => 'Session declined. The customer has been refunded.', 'type' => 'success'];
        }
    }
    if (isset($_GET['updated']))     { $toasts[] = ['text' => 'Booking status updated successfully!', 'type' => 'success']; }
    if (isset($_GET['edited']))      { $toasts[] = ['text' => 'Booking updated successfully!',        'type' => 'success']; }
    if (isset($_GET['added']))       { $toasts[] = ['text' => 'Booking added successfully!',          'type' => 'success']; }
    if (isset($_GET['conflict']))    { $toasts[] = ['text' => 'This time slot is already booked. Please choose another time.', 'type' => 'pending']; }
    if (isset($_GET['locked']))      { $toasts[] = ['text' => 'This booking is already cancelled or completed and can no longer be edited.', 'type' => 'error']; }
    if (isset($_GET['invalid_price'])){ $toasts[] = ['text' => 'Price cannot be negative. Please enter a valid amount or leave it blank to auto-calculate.', 'type' => 'error']; }    
    if (isset($_GET['proof_error'])) { $toasts[] = ['text' => 'Photo upload failed. Please use JPG/PNG under 5MB.', 'type' => 'error']; }
    if (isset($_GET['invalid_date'])) {
        if ($_GET['invalid_date'] === 'past') {
            $toasts[] = ['text' => 'That time slot is in the past. Please choose a future date/time.', 'type' => 'error'];
        } elseif ($_GET['invalid_date'] === 'range') {
            $toasts[] = ['text' => 'End time must be after start time.', 'type' => 'error'];
        }
    }

    // Handle bulk action
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['ids'])) {
        $ids    = array_filter(array_map('intval', explode(',', $_POST['ids'])));
        $action = $_POST['action'];

        if (!empty($ids)) {
            $conn_bulk = mysqli_connect("localhost", "root", "", "badminton_hub");
            require_once __DIR__ . '/../log_activity.php';
            $ids_str   = implode(',', $ids);
            $role_bulk = $_SESSION['role'];
            $count     = count($ids);

            if ($role_bulk === 'Coach') {
                // Coach: can only mark complete or decline their own bookings
                $my_coach_q   = mysqli_query($conn_bulk, "SELECT id FROM coaches WHERE admin_id = " . (int)$_SESSION['id']);
                $my_coach_row = mysqli_fetch_assoc($my_coach_q);
                $my_cid       = $my_coach_row ? (int)$my_coach_row['id'] : 0;

                if ($action === 'confirm') {
                    mysqli_query($conn_bulk, "UPDATE bookings SET status='Completed' WHERE id IN ($ids_str) AND coach_id = $my_cid");
                    logActivity($conn_bulk, 'Status Change', 'Booking Management', "Bulk marked $count booking(s) as Completed: IDs $ids_str");
                } elseif ($action === 'cancel') {
                    mysqli_query($conn_bulk, "UPDATE bookings SET status='Cancelled' WHERE id IN ($ids_str) AND coach_id = $my_cid");
                    logActivity($conn_bulk, 'Status Change', 'Booking Management', "Bulk cancelled $count booking(s): IDs $ids_str");
                }

            } elseif (in_array($role_bulk, ['Superadmin', 'Admin'])) {
                if ($action === 'confirm') {
                    mysqli_query($conn_bulk, "UPDATE bookings SET status='Confirmed' WHERE id IN ($ids_str)");
                    logActivity($conn_bulk, 'Status Change', 'Booking Management', "Bulk confirmed $count booking(s): IDs $ids_str");
                } elseif ($action === 'cancel') {
                    mysqli_query($conn_bulk, "UPDATE bookings SET status='Cancelled' WHERE id IN ($ids_str)");
                    logActivity($conn_bulk, 'Status Change', 'Booking Management', "Bulk cancelled $count booking(s): IDs $ids_str");
                } elseif ($action === 'complete') {
                    mysqli_query($conn_bulk, "UPDATE bookings SET status='Completed' WHERE id IN ($ids_str)");
                    logActivity($conn_bulk, 'Status Change', 'Booking Management', "Bulk marked $count booking(s) as Completed: IDs $ids_str");
                } elseif ($action === 'delete') {
                    mysqli_query($conn_bulk, "DELETE FROM bookings WHERE id IN ($ids_str)");
                    logActivity($conn_bulk, 'Delete', 'Booking Management', "Bulk deleted $count booking(s): IDs $ids_str");
                }
            }

            mysqli_close($conn_bulk);
        }

        header("Location: ManageBookings.php?updated=1");
        exit();
    }

    // Database Connection
    $conn = mysqli_connect("localhost", "root", "", "badminton_hub");

    // Mark coach no-shows: pending coach bookings whose session time has already passed
    require_once __DIR__ . '/coach_no_show_check.php';
    handleCoachNoShows($conn);

    $username     = $_SESSION['username'];
    $role         = $_SESSION['role'];
    $display_name = $username;

    // This page sits at Admin_Module root, so navbar links don't need a prefix.
    $base_path = '../';

    // Fetch all courts for edit modal dropdown
    $courts_result = mysqli_query($conn, "SELECT id, court_name FROM courts WHERE is_active = 1 ORDER BY court_name ASC");
    $courts_list   = [];
    while($c = mysqli_fetch_assoc($courts_result)) $courts_list[] = $c;

    // Fetch all coaches for edit modal dropdown
    $coaches_result = mysqli_query($conn, "SELECT id, name FROM coaches WHERE is_active = 1 ORDER BY name ASC");
    $coaches_list   = [];
    while($c = mysqli_fetch_assoc($coaches_result)) $coaches_list[] = $c;

    // All coaches (incl. inactive/suspended) for the filter so past bookings stay filterable
    $all_coaches_result = mysqli_query($conn, "SELECT id, name FROM coaches ORDER BY name ASC");
    $all_coaches_list   = [];
    while($c = mysqli_fetch_assoc($all_coaches_result)) $all_coaches_list[] = $c;

    // Fetch all users for add booking modal dropdown
    $users_result = mysqli_query($conn, "SELECT id, name FROM users ORDER BY name ASC");
    $users_list   = [];
    while($u = mysqli_fetch_assoc($users_result)) $users_list[] = $u;

    // Get current coach ID if role is Coach
    $my_coach_id = 0;
    if($role === 'Coach'){
        $coach_id_query = mysqli_query($conn, "SELECT id FROM coaches WHERE admin_id = " . (int)$_SESSION['id']);
        $coach_row      = mysqli_fetch_assoc($coach_id_query);
        $my_coach_id    = $coach_row ? (int)$coach_row['id'] : 0;
    }

    // From dashboard calendar: highlight a specific row by booking ID
    $highlight_id = isset($_GET['highlight']) ? intval($_GET['highlight']) : 0;

    // Filter values from GET
    $filter_status    = isset($_GET['status'])    ? $_GET['status']                                      : '';
    $filter_court     = isset($_GET['court'])     ? intval($_GET['court'])                               : 0;
    // Coach filter: '' / '0' = all, 'none' = no coach assigned, a number = that coach
    $filter_coach        = isset($_GET['coach']) ? trim($_GET['coach']) : '';
    $coach_filter_active = ($filter_coach === 'none') || (intval($filter_coach) > 0);
    // ?date= comes from "View All Bookings" calendar link; ?booking_date= from the filter form
    $filter_booking_date = isset($_GET['booking_date']) ? mysqli_real_escape_string($conn, $_GET['booking_date'])
                         : (isset($_GET['date'])         ? mysqli_real_escape_string($conn, $_GET['date']) : '');
    $filter_search    = isset($_GET['search'])    ? mysqli_real_escape_string($conn, $_GET['search'])    : '';

    // Sort handling
    $allowed_sorts = ['id', 'name', 'court_name', 'created_at', 'booking_date', 'start_time', 'end_time', 'total_price', 'status'];
    $sort_col = isset($_GET['sort']) && in_array($_GET['sort'], $allowed_sorts) ? $_GET['sort'] : 'created_at';
    $sort_dir = isset($_GET['dir']) && $_GET['dir'] === 'asc' ? 'ASC' : 'DESC';
    $next_dir = ($sort_dir === 'ASC') ? 'desc' : 'asc';
    $order_col = match($sort_col) {
                    'name'       => 'users.name',
                    'court_name' => 'courts.court_name',
                    default      => "bookings.$sort_col"
                };

    function bookingSortLink($label, $col, $current_sort, $current_dir, $next_dir, $params_extra = []) {
        $is_active = ($current_sort === $col);
        $dir = $is_active ? $next_dir : 'desc';
        $arrow = '';
        if ($is_active) {
            $arrow = $current_dir === 'ASC'
                ? ' <i class="fas fa-arrow-up sort-arrow active-arrow"></i>'
                : ' <i class="fas fa-arrow-down sort-arrow active-arrow"></i>';
        } else {
            $arrow = ' <i class="fas fa-sort sort-arrow"></i>';
        }
        $params = array_merge($params_extra, ['sort' => $col, 'dir' => $dir]);
        $qs = http_build_query($params);
        return "<a href='ManageBookings.php?$qs' class='sort-link'>$label$arrow</a>";
    }
    
    // Check if any filter is active
    $has_filter = $filter_status || $filter_court || $coach_filter_active || $filter_booking_date || $filter_search;

    // Build WHERE clause
    $where_parts = [];
    if($filter_status !== '')   $where_parts[] = "bookings.status = '$filter_status'";
    if($filter_court > 0)       $where_parts[] = "bookings.court_id = $filter_court";
    if($filter_booking_date !== '') $where_parts[] = "bookings.booking_date = '$filter_booking_date'";
    if($filter_search !== '')    $where_parts[] = "(users.name LIKE '%$filter_search%' OR courts.court_name LIKE '%$filter_search%')";

    // Coach: force filter to own bookings only, ignore coach filter from GET
    if($role === 'Coach'){
        $where_parts[] = "bookings.coach_id = $my_coach_id";
    } else {
        // Admin/Superadmin: allow coach filter ('none' = bookings with no coach)
        if($filter_coach === 'none')      $where_parts[] = "bookings.coach_id IS NULL";
        elseif(intval($filter_coach) > 0) $where_parts[] = "bookings.coach_id = " . intval($filter_coach);
    }

    $where_sql = count($where_parts) > 0 ? "WHERE " . implode(" AND ", $where_parts) : "";

    // Pagination
    $per_page   = 15;
    $page       = max(1, (int)($_GET['page'] ?? 1));

    $count_res  = mysqli_query($conn, "
        SELECT COUNT(*) AS cnt
        FROM bookings
        JOIN users  ON bookings.user_id  = users.id
        JOIN courts ON bookings.court_id = courts.id
        $where_sql
    ");
    $total_rows  = (int)mysqli_fetch_assoc($count_res)['cnt'];
    $total_pages = max(1, (int)ceil($total_rows / $per_page));
    $page        = min($page, $total_pages);
    $offset      = ($page - 1) * $per_page;

    function bookingPageQS($p, $sort, $dir, $extra) {
        $params = array_merge($extra, ['page' => $p, 'sort' => $sort, 'dir' => $dir]);
        // Strip empty values so URL stays clean
        $params = array_filter($params, fn($v) => $v !== '' && $v !== 0 && $v !== '0');
        return http_build_query($params);
    }

    // Fetch booking data with player name, court name, coach name, session type and notes
    $result = mysqli_query($conn, "
        SELECT
            bookings.id,
            bookings.court_id,
            bookings.coach_id,
            users.name,
            courts.court_name,
            bookings.booking_date,
            bookings.created_at,
            bookings.start_time,
            bookings.end_time,
            bookings.status,
            bookings.total_price,
            bookings.coach_price_total,
            bookings.session_type,
            bookings.notes,
            bookings.cancellation_fee,
            bookings.completion_photo,
            COALESCE(coaches.name, 'No Coach') AS coach_name,
            payments.payment_method,
            payments.payment_status,
            payments.payment_date,
            payments.transaction_id

        FROM bookings

        JOIN users   ON bookings.user_id  = users.id
        JOIN courts  ON bookings.court_id = courts.id
        LEFT JOIN coaches  ON bookings.coach_id = coaches.id
        LEFT JOIN payments ON payments.booking_id = bookings.id

        $where_sql

        ORDER BY $order_col $sort_dir
        LIMIT $per_page OFFSET $offset
    ");

    // Load add-on line items per booking for the price breakdown modal
    $addons_by_booking = [];
    $addon_res = mysqli_query($conn, "
        SELECT ba.booking_id, ba.quantity, ba.price, p.name
        FROM booking_addons ba
        JOIN products p ON ba.product_id = p.id
    ");
    if ($addon_res) {
        while ($a = mysqli_fetch_assoc($addon_res)) {
            $addons_by_booking[$a['booking_id']][] = $a;
        }
    }

    // Collected during the row loop, then output as JSON for the breakdown modal
    $breakdowns = [];

?>
<!DOCTYPE html>
<html lang="en">
<head> 
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Smash Arena - Bookings Management</title>
    
    <!-- Font Awesome CDN -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <!-- Google Fonts CDN -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800;900&display=swap">

    <!-- Connect previous CSS -->
    <link rel="stylesheet" href="../Dashboard/Dashboard.css">
    <link rel="stylesheet" href="../Superadmin/AdminManagement.css">
    <link rel="stylesheet" href="ManageBookings.css">
</head>

<body>
    <!-- Nav Bar -->
    <?php include '../navbar.php';?>

    <!-- Main Content -->
    <main class="content">
        <div class="manage-container">
            
            <header class="management-header">
                <div>
                    <h1><?php echo ($role === 'Coach') ? 'My Bookings' : 'Bookings Management'; ?></h1>
                    <p><?php echo ($role === 'Coach') ? 'View and respond to your assigned court sessions.' : 'Manage all court bookings, view details, and update booking statuses.'; ?></p>
                </div>
                <div class="btn-add-group">
                    <button class="btn-filter-toggle" onclick="toggleBookingFilter()">
                        <i class="fas fa-filter"></i> Filter
                    </button>
                    <button class="btn-bulk-toggle" id="bulkToggleBtn" onclick="toggleBulkMode()">
                        <i class="fas fa-check-square"></i> <span id="bulkToggleText">Select</span>
                    </button>
                    <?php if($role !== 'Coach'): ?>
                    <a href="#" class="btn-add-account" onclick="openAddModal(); return false;" style="text-decoration:none;">
                        <i class="fas fa-plus"></i> Add Booking
                    </a>
                    <?php endif; ?>
                </div>
            </header>

            <!-- Collapsible Filter Panel -->
            <div class="filter-panel <?php echo $has_filter ? 'open' : ''; ?>" id="bookingFilterPanel">
                <form method="GET" class="filter-grid">
                    <div class="filter-field">
                        <label>Search</label>
                        <input type="text" name="search" placeholder="Player or court name..." value="<?php echo htmlspecialchars($filter_search); ?>">
                    </div>
                    <div class="filter-field">
                        <label>Status</label>
                        <select name="status">
                            <option value="">All Status</option>
                            <option value="Pending"   <?php echo ($filter_status === 'Pending')   ? 'selected' : ''; ?>>Pending</option>
                            <option value="Confirmed" <?php echo ($filter_status === 'Confirmed') ? 'selected' : ''; ?>>Confirmed</option>
                            <option value="Completed" <?php echo ($filter_status === 'Completed') ? 'selected' : ''; ?>>Completed</option>
                            <option value="Cancelled" <?php echo ($filter_status === 'Cancelled') ? 'selected' : ''; ?>>Cancelled</option>
                        </select>
                    </div>
                    <div class="filter-field">
                        <label>Court</label>
                        <div class="search-select filter-search">
                            <input type="text" class="search-select-input" placeholder="Search court..." autocomplete="off"
                                   value="<?php
                                       if ($filter_court > 0) {
                                           foreach ($courts_list as $c) { if ((int)$c['id'] === $filter_court) echo htmlspecialchars($c['court_name']); }
                                       }
                                   ?>">
                            <input type="hidden" name="court" class="search-select-value" value="<?php echo $filter_court ?: '0'; ?>">
                            <div class="search-select-list">
                                <div class="search-select-item" data-id="0" data-name="All Courts">All Courts</div>
                                <?php foreach($courts_list as $court): ?>
                                    <div class="search-select-item" data-id="<?php echo $court['id']; ?>" data-name="<?php echo htmlspecialchars($court['court_name']); ?>">
                                        <?php echo htmlspecialchars($court['court_name']); ?>
                                    </div>
                                <?php endforeach; ?>
                                <div class="search-select-empty" style="display:none;">No courts match.</div>
                            </div>
                        </div>
                    </div>
                    <?php if($role !== 'Coach'): ?>
                    <div class="filter-field">
                        <label>Coach</label>
                        <div class="search-select filter-search">
                            <input type="text" class="search-select-input" placeholder="Search coach..." autocomplete="off"
                                   value="<?php
                                       if ($filter_coach === 'none') echo 'No Coach';
                                       elseif (intval($filter_coach) > 0) {
                                           foreach ($all_coaches_list as $c) { if ((int)$c['id'] === intval($filter_coach)) echo htmlspecialchars($c['name']); }
                                       }
                                   ?>">
                            <input type="hidden" name="coach" class="search-select-value" value="<?php echo htmlspecialchars($filter_coach !== '' ? $filter_coach : '0'); ?>">
                            <div class="search-select-list">
                                <div class="search-select-item" data-id="none" data-name="No Coach">No Coach</div>
                                <div class="search-select-item" data-id="0" data-name="All Coaches">All Coaches</div>
                                <?php foreach($all_coaches_list as $coach): ?>
                                    <div class="search-select-item" data-id="<?php echo $coach['id']; ?>" data-name="<?php echo htmlspecialchars($coach['name']); ?>">
                                        <?php echo htmlspecialchars($coach['name']); ?>
                                    </div>
                                <?php endforeach; ?>
                                <div class="search-select-empty" style="display:none;">No coaches match.</div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                    <div class="filter-field">
                        <label>Booking Date</label>
                        <input type="date" name="booking_date" value="<?php echo htmlspecialchars($filter_booking_date); ?>">
                    </div>
                    <div class="filter-actions">
                        <button type="submit" class="btn-filter-apply"><i class="fas fa-search"></i> Apply</button>
                        <a href="ManageBookings.php" class="btn-filter-clear">Clear</a>
                    </div>
                </form>
            </div>

            <!-- Bulk Action Bar -->
            <div class="bulk-action-bar" id="bulkActionBar">
                <span id="bulkCount">0 selected</span>
                <div class="bulk-action-btns <?php echo $role === 'Coach' ? 'bulk-btns-coach' : ''; ?>">
                    <?php if($role === 'Coach'): ?>
                        <button class="bulk-btn bulk-confirm" onclick="submitBulk('confirm')"><i class="fas fa-check"></i> Mark as Complete</button>
                        <button class="bulk-btn bulk-delete"  onclick="submitBulk('cancel')"><i class="fas fa-times"></i> Decline</button>
                    <?php else: ?>
                        <button class="bulk-btn bulk-confirm"  onclick="submitBulk('confirm')"><i class="fas fa-check"></i> Confirm</button>
                        <button class="bulk-btn bulk-cancel"   onclick="submitBulk('cancel')"><i class="fas fa-times"></i> Cancel</button>
                        <button class="bulk-btn bulk-complete" onclick="submitBulk('complete')"><i class="fas fa-flag-checkered"></i> Complete</button>
                        <button class="bulk-btn bulk-delete"   onclick="submitBulk('delete')"><i class="fas fa-trash"></i> Delete</button>
                    <?php endif; ?>
                </div>
            </div>
            <form id="bulkForm" method="POST" action="ManageBookings.php" style="display:none;">
                <input type="hidden" name="action" id="bulkAction">
                <input type="hidden" name="ids"    id="bulkIds">
            </form>

            <table class="data-table">
                <thead>
                    <?php
                    $extra = [
                        'status'       => $filter_status,
                        'court'        => $filter_court,
                        'coach'        => $filter_coach,
                        'booking_date' => $filter_booking_date,
                        'search'       => $filter_search,
                    ];
                    ?>
                    <tr>
                        <th class="bulk-col"></th>
                        <th><?php echo bookingSortLink('Player Name',  'name',         $sort_col, $sort_dir, $next_dir, $extra); ?></th>
                        <th><?php echo bookingSortLink('Court Name',   'court_name',   $sort_col, $sort_dir, $next_dir, $extra); ?></th>
                        <th><?php echo bookingSortLink('Order Date',   'created_at',   $sort_col, $sort_dir, $next_dir, $extra); ?></th>
                        <th><?php echo bookingSortLink('Booking Date', 'booking_date', $sort_col, $sort_dir, $next_dir, $extra); ?></th>
                        <th><?php echo bookingSortLink('Start Time',   'start_time',   $sort_col, $sort_dir, $next_dir, $extra); ?></th>
                        <th><?php echo bookingSortLink('End Time',     'end_time',     $sort_col, $sort_dir, $next_dir, $extra); ?></th>
                        <th><?php echo ($role === 'Coach')
                                ? bookingSortLink('Your Fee', 'total_price', $sort_col, $sort_dir, $next_dir, $extra)
                                : bookingSortLink('Total Price', 'total_price', $sort_col, $sort_dir, $next_dir, $extra); ?></th>
                        <th><?php echo bookingSortLink('Status',       'status',       $sort_col, $sort_dir, $next_dir, $extra); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($row = mysqli_fetch_assoc($result)):
                        /* Coach declining < 24h before the session counts as a late cancellation (penalty applies) */
                        $session_ts     = strtotime($row['booking_date'] . ' ' . $row['start_time']);
                        $is_late_cancel = (($session_ts - time()) / 3600) < 24;

                        /* Build the price breakdown for this booking (court / coach / add-ons) */
                        $bk_addons   = $addons_by_booking[$row['id']] ?? [];
                        $addon_items = [];
                        $addon_sum   = 0;
                        foreach ($bk_addons as $a) {
                            $sub        = $a['quantity'] * $a['price'];
                            $addon_sum += $sub;
                            $addon_items[] = ['name' => $a['name'], 'qty' => (int)$a['quantity'], 'subtotal' => (float)$sub];
                        }
                        $coach_fee = (float)$row['coach_price_total'];
                        $breakdowns[$row['id']] = [
                            'court'            => (float)$row['total_price'] - $coach_fee - $addon_sum,
                            'coach'            => $coach_fee,
                            'addons'           => $addon_items,
                            'total'            => (float)$row['total_price'],
                            'status'           => $row['status'],
                            'cancellation_fee' => (float)($row['cancellation_fee'] ?? 0),
                        ];
                    ?>

                    <!-- Main row — click to expand details -->
                    <tr id="booking-row-<?php echo $row['id']; ?>"
                        class="main-row<?php echo ($highlight_id === (int)$row['id']) ? ' booking-highlight' : ''; ?>">
                        <td class="bulk-col" onclick="event.stopPropagation()">
                            <input type="checkbox" class="row-check" value="<?php echo $row['id']; ?>" onchange="updateBulkCount()">
                        </td>
                        <td><?php echo htmlspecialchars($row['name']); ?></td>
                        <td><?php echo htmlspecialchars($row['court_name']); ?></td>
                        <td><?php echo date("d-m-Y h:i A", strtotime($row['created_at'])); ?></td>
                        <td><?php echo date("d-m-Y", strtotime($row['booking_date'])); ?></td>
                        <td><?php echo date("h:i A", strtotime($row['start_time'])); ?></td>
                        <td><?php echo date("h:i A", strtotime($row['end_time'])); ?></td>
                        <td>RM <?php echo ($role === 'Coach')
                                ? number_format($row['coach_price_total'], 2)
                                : number_format($row['total_price'], 2); ?></td>
                        <td onclick="event.stopPropagation()">
                            <?php if($role === 'Coach'): ?>
                                <!-- Coach: only show dropdown if Pending, else show static badge -->
                                <?php if($row['status'] === 'Pending'): ?>
                                    <select class="status-select status-inactive" onchange="handleCoachStatusChange(this, <?php echo $row['id']; ?>, <?php echo $is_late_cancel ? 'true' : 'false'; ?>)">
                                        <option value="Pending"   selected>Pending</option>
                                        <option value="Confirmed">Accept</option>
                                        <option value="Cancelled">Decline</option>
                                    </select>
                                <?php else: ?>
                                    <span class="status-select <?php 
                                        if($row['status'] == 'Confirmed') echo 'status-active';
                                        elseif($row['status'] == 'Cancelled') echo 'status-suspended';
                                        else echo 'status-active'; // Completed
                                    ?>"><?php echo $row['status']; ?></span>
                                <?php endif; ?>
                            <?php else: ?>
                                <select class="status-select <?php 
                                    if($row['status'] == 'Confirmed') echo 'status-active';
                                    elseif($row['status'] == 'Pending') echo 'status-inactive';
                                    elseif($row['status'] == 'Cancelled') echo 'status-suspended';
                                    else echo 'status-active'; // Completed
                                ?>" onchange="location.href='UpdateBookingsStatus.php?id=<?php echo $row['id']; ?>&status=' + this.value">
                                    <option value="Pending"   <?php echo ($row['status'] == 'Pending'   ? 'selected' : ''); ?>>Pending</option>
                                    <option value="Confirmed" <?php echo ($row['status'] == 'Confirmed' ? 'selected' : ''); ?>>Confirmed</option>
                                    <option value="Completed" <?php echo ($row['status'] == 'Completed' ? 'selected' : ''); ?>>Completed</option>
                                    <option value="Cancelled" <?php echo ($row['status'] == 'Cancelled' ? 'selected' : ''); ?>>Cancelled</option>
                                </select>
                            <?php endif; ?>
                        </td>
                    </tr>

                    <!-- Details row — hidden by default, shown on click -->
                    <tr class="details-row" id="details-<?php echo $row['id']; ?>">
                        <td colspan="9">
                            <!-- Coloured left border based on booking status -->
                            <div class="details-inner status-<?php echo $row['status']; ?>">
                                <div class="details-grid">
                            <div class="details-item">
                                <label>Player Name</label>
                                <span><?php echo htmlspecialchars($row['name']); ?></span>
                            </div>
                            <div class="details-item">
                                <label>Court</label>
                                <span><?php echo htmlspecialchars($row['court_name']); ?></span>
                            </div>
                            <div class="details-item">
                                <label>Booking Date</label>
                                <span><?php echo date("d-m-Y", strtotime($row['booking_date'])); ?></span>
                            </div>
                            <div class="details-item">
                                <label>Time Range</label>
                                <span><?php echo date("h:i A", strtotime($row['start_time'])) . ' – ' . date("h:i A", strtotime($row['end_time'])); ?></span>
                            </div>
                            <div class="details-item">
                                <label>Coach</label>
                                <span><?php echo htmlspecialchars($row['coach_name']); ?></span>
                            </div>
                            <div class="details-item">
                                <label>Session Type</label>
                                <span><?php echo htmlspecialchars($row['session_type'] ?: '—'); ?></span>
                            </div>
                            <?php if ($role === 'Coach'): ?>
                            <div class="details-item">
                                <label>Your Coaching Fee</label>
                                <span>RM <?php echo number_format($row['coach_price_total'], 2); ?></span>
                            </div>
                            <?php else: ?>
                            <div class="details-item">
                                <label>Total Price</label>
                                <span>
                                    <button type="button" class="price-breakdown-btn"
                                            onclick="event.stopPropagation(); openBreakdown(<?php echo $row['id']; ?>)">
                                        RM <?php echo number_format($row['total_price'], 2); ?>
                                        <i class="fas fa-circle-info"></i>
                                    </button>
                                </span>
                            </div>
                            <?php endif; ?>
                            <div class="details-item">
                                <label>Payment Method</label>
                                <span><?php echo htmlspecialchars($row['payment_method'] ?: '—'); ?></span>
                            </div>
                            <div class="details-item">
                                <label>Payment Status</label>
                                <span><?php echo htmlspecialchars($row['payment_status'] ?: '—'); ?></span>
                            </div>
                            <div class="details-item">
                                <label>Payment Date</label>
                                <span><?php echo $row['payment_date'] ? date("d-m-Y h:i A", strtotime($row['payment_date'])) : '—'; ?></span>
                            </div>
                            <div class="details-item">
                                <label>Transaction ID</label>
                                <span><?php echo htmlspecialchars($row['transaction_id'] ?: '—'); ?></span>
                            </div>
                            <div class="details-item notes-item">
                                <label>Notes</label>
                                <span><?php echo htmlspecialchars($row['notes'] ?: '—'); ?></span>
                            </div>
                            <?php if($row['coach_id']): ?>
                            <div class="details-item">
                                <label>Proof Photo</label>
                                <?php if($row['status'] === 'Completed' && !empty($row['completion_photo'])): ?>
                                    <button type="button" class="btn-view-proof" onclick="event.stopPropagation(); openProofView('<?php echo htmlspecialchars($row['completion_photo']); ?>', <?php echo $row['id']; ?>, <?php echo $role === 'Coach' ? 'true' : 'false'; ?>)">
                                        <i class="fas fa-image"></i> Show Photo
                                    </button>
                                <?php elseif($row['status'] === 'Completed'): ?>
                                    <span class="proof-missing"><i class="fas fa-clock"></i> Waiting</span>
                                <?php else: ?>
                                    <span style="font-size:13px; color:#94a3b8;">—</span>
                                <?php endif; ?>
                            </div>
                            <?php endif; ?>
                        </div>

                                <!-- Edit button — stopPropagation so clicking it doesn't collapse the row -->
                                <?php if($role !== 'Coach'): ?>
                                <button class="btn-edit-booking" onclick="event.stopPropagation(); openEditModal(
                                    <?php echo $row['id']; ?>,
                                    '<?php echo $row['booking_date']; ?>',
                                    '<?php echo date("H:i", strtotime($row['start_time'])); ?>',
                                    '<?php echo date("H:i", strtotime($row['end_time'])); ?>',
                                    <?php echo (int)$row['court_id']; ?>,
                                    <?php echo $row['coach_id'] ? (int)$row['coach_id'] : 0; ?>,
                                    '<?php echo addslashes($row['session_type']); ?>',
                                    '<?php echo addslashes($row['notes']); ?>'
                                )">
                                    <i class="fas fa-pen"></i> Edit
                                </button>
                                <?php endif; ?>

                                <!-- Coach action buttons -->
                                <?php if($role === 'Coach'): ?>

                                    <?php if($row['status'] === 'Confirmed'): ?>
                                    <div style="display:flex; flex-direction:column; gap:8px; flex-shrink:0;">
                                        <button type="button"
                                           class="btn-coach-action btn-coach-complete"
                                           onclick="event.stopPropagation(); openProofModal(<?php echo $row['id']; ?>)">
                                            <i class="fas fa-camera"></i> Mark as Completed
                                        </button>
                                        <a href="coach_decline.php?id=<?php echo $row['id']; ?>"
                                           class="btn-coach-action btn-coach-decline"
                                           onclick="event.stopPropagation(); return confirmCoachDecline(<?php echo $is_late_cancel ? 'true' : 'false'; ?>);">
                                            <i class="fas fa-times"></i> Decline Session
                                        </a>
                                    </div>
                                    <?php endif; ?>

                                <?php endif; ?>

                            </div>
                        </td>
                    </tr>

                    <?php endwhile; ?>

                </tbody>
            </table>

            <?php if ($total_pages > 1): ?>
            <div class="log-pagination">
                <?php if ($page > 1): ?>
                    <a href="?<?php echo bookingPageQS($page - 1, $sort_col, strtolower($sort_dir), $extra); ?>" class="page-btn">
                        <i class="fas fa-chevron-left"></i>
                    </a>
                <?php else: ?>
                    <span class="page-btn disabled"><i class="fas fa-chevron-left"></i></span>
                <?php endif; ?>

                <form method="GET" class="page-jump-form">
                    <input type="number" name="page" class="page-jump-input"
                           value="<?php echo $page; ?>" min="1" max="<?php echo $total_pages; ?>">
                    <span class="page-jump-of">/ <?php echo $total_pages; ?></span>
                    <input type="hidden" name="sort" value="<?php echo $sort_col; ?>">
                    <input type="hidden" name="dir"  value="<?php echo strtolower($sort_dir); ?>">
                    <?php foreach ($extra as $k => $v): if ($v !== '' && $v !== 0 && $v !== '0'): ?>
                        <input type="hidden" name="<?php echo htmlspecialchars($k); ?>" value="<?php echo htmlspecialchars($v); ?>">
                    <?php endif; endforeach; ?>
                </form>

                <?php if ($page < $total_pages): ?>
                    <a href="?<?php echo bookingPageQS($page + 1, $sort_col, strtolower($sort_dir), $extra); ?>" class="page-btn">
                        <i class="fas fa-chevron-right"></i>
                    </a>
                <?php else: ?>
                    <span class="page-btn disabled"><i class="fas fa-chevron-right"></i></span>
                <?php endif; ?>
            </div>
            <?php endif; ?>

        </div>

        <button id="scrollTopBtn" onclick="window.scrollTo({top:0, behavior:'smooth'})">
            <i class="fas fa-chevron-up"></i>
        </button>
    </main>

    <!-- Upload Completion Proof Modal (Coach only) -->
    <div class="modal-overlay" id="proofUploadModal">
        <div class="modal-card" style="max-width:420px;">
            <div class="modal-header">
                <h2><i class="fas fa-camera"></i> Upload Completion Proof</h2>
                <button class="modal-close" onclick="closeProofModal()">✕</button>
            </div>
            <form action="UploadCompletionProof.php" method="POST" enctype="multipart/form-data" style="padding:0 18px 18px;">
                <input type="hidden" name="booking_id" id="proof-booking-id">
                <div class="modal-field" style="margin-bottom:14px;">
                    <label>Booking</label>
                    <div id="proof-booking-label" style="font-size:14px; font-weight:600; color:#1f2937; padding:8px 0;"></div>
                </div>
                <div class="modal-field proof-upload-area" id="proofDropArea">
                    <label>Proof Photo <span style="font-weight:400; text-transform:none; letter-spacing:0; color:#94a3b8;">(JPG / PNG / GIF, max 5MB)</span></label>
                    <div class="proof-drop-zone" onclick="document.getElementById('proofFileInput').click()">
                        <div id="proofPreviewWrap" style="display:none;">
                            <img id="proofPreviewImg" alt="preview" style="max-height:180px; max-width:100%; border-radius:8px; object-fit:contain;">
                        </div>
                        <div id="proofDropPrompt">
                            <i class="fas fa-cloud-upload-alt" style="font-size:32px; color:#cbd5e1; margin-bottom:8px;"></i>
                            <div style="font-size:13px; color:#64748b;">Click to choose photo</div>
                        </div>
                        <input type="file" id="proofFileInput" name="proof_photo" accept="image/*" required style="display:none;" onchange="previewProof(this)">
                    </div>
                </div>
                <div class="modal-actions" style="margin-top:16px;">
                    <button type="button" class="btn-modal-cancel" onclick="closeProofModal()">Cancel</button>
                    <button type="submit" class="btn-modal-save"><i class="fas fa-check"></i> Submit & Complete</button>
                </div>
            </form>
        </div>
    </div>

    <!-- View Proof Photo Modal -->
    <div class="modal-overlay" id="proofViewModal">
        <div class="modal-card" style="max-width:560px;">
            <div class="modal-header">
                <h2><i class="fas fa-image"></i> Completion Proof — Booking #<span id="proof-view-id"></span></h2>
                <button class="modal-close" onclick="closeProofView()">&#x2715;</button>
            </div>
            <div style="padding:0 18px 18px; text-align:center;">
                <img id="proofViewImg" src="" alt="Proof Photo" style="max-width:100%; max-height:420px; border-radius:10px; object-fit:contain; border:1px solid #e5e7eb;">
                <div style="margin-top:12px; display:flex; justify-content:center; gap:10px; flex-wrap:wrap;">
                    <a id="proofDownloadLink" href="" download target="_blank" class="btn-modal-save" style="display:inline-flex; align-items:center; gap:6px; text-decoration:none;">
                        <i class="fas fa-download"></i> Download
                    </a>
                    <button id="proofChangeBtn" type="button" class="btn-modal-save" style="display:none; background:#f59e0b;" onclick="openChangeProof()">
                        <i class="fas fa-camera"></i> Change Photo
                    </button>
                    <button id="proofDeleteBtn" type="button" class="btn-modal-cancel" style="display:none;" onclick="confirmDeleteProof()">
                        <i class="fas fa-trash"></i> Delete Photo
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Hidden form for delete proof -->
    <form id="deleteProofForm" action="DeleteCompletionProof.php" method="POST" style="display:none;">
        <input type="hidden" name="booking_id" id="deleteProofBookingId">
    </form>

    <!-- Edit Booking Modal -->
    <div class="modal-overlay" id="editModal">
        <div class="modal-card">

            <div class="modal-header">
                <h2><i class="fas fa-pen"></i> Edit Booking</h2>
                <button class="modal-close" onclick="closeEditModal()">✕</button>
            </div>

            <form action="UpdateBookingsStatus.php" method="POST">
                <input type="hidden" name="booking_id" id="modal-booking-id">

                <div class="modal-grid">

                    <div class="modal-field full-width">
                        <label>Booking Date</label>
                        <input type="date" name="booking_date" id="modal-booking-date" required>
                    </div>

                    <div class="modal-field full-width">
                        <label>Start Time</label>
                        <div class="slot-picker" id="editSlotPicker">
                            <div class="slot-picker-hint">Select a court and date first</div>
                        </div>
                        <input type="hidden" name="start_time" id="edit-start-time" required>
                    </div>

                    <div class="modal-field full-width">
                        <label>Court Hours</label>
                        <div class="hours-picker" id="editHoursPicker">
                            <div class="slot-picker-hint">Select a start time first</div>
                        </div>
                        <input type="hidden" name="end_time" id="edit-end-time" required>
                    </div>

                    <div class="modal-field">
                        <label>Court</label>
                        <div class="search-select" data-search="editCourt" id="editCourtSearch">
                            <input type="text" class="search-select-input" placeholder="Type to search a court..." autocomplete="off">
                            <input type="hidden" name="court_id" class="search-select-value" required>
                            <div class="search-select-list">
                                <?php foreach($courts_list as $court): ?>
                                    <div class="search-select-item" data-id="<?php echo $court['id']; ?>" data-name="<?php echo htmlspecialchars($court['court_name']); ?>">
                                        <?php echo htmlspecialchars($court['court_name']); ?>
                                    </div>
                                <?php endforeach; ?>
                                <div class="search-select-empty" style="display:none;">No courts match.</div>
                            </div>
                        </div>
                    </div>

                    <div class="modal-field">
                        <label>Coach</label>
                        <div class="search-select" data-search="editCoach" id="editCoachSearch">
                            <input type="text" class="search-select-input" placeholder="Type to search a coach..." autocomplete="off">
                            <input type="hidden" name="coach_id" class="search-select-value" value="0">
                            <div class="search-select-list">
                                <div class="search-select-item" data-id="0" data-name="No Coach">No Coach</div>
                                <?php foreach($coaches_list as $coach): ?>
                                    <div class="search-select-item" data-id="<?php echo $coach['id']; ?>" data-name="<?php echo htmlspecialchars($coach['name']); ?>">
                                        <?php echo htmlspecialchars($coach['name']); ?>
                                    </div>
                                <?php endforeach; ?>
                                <div class="search-select-empty" style="display:none;">No coaches match.</div>
                            </div>
                        </div>
                    </div>

                    <div class="modal-field full-width">
                        <label>Session Type</label>
                        <select name="session_type" id="modal-session-type">
                            <option value="">— None —</option>
                            <option value="Training">Training</option>
                            <option value="Casual">Casual</option>
                            <option value="Tournament">Tournament</option>
                        </select>
                    </div>

                    <div class="modal-field full-width">
                        <label>Total Price (RM) <span style="font-weight:400; color:#94a3b8;">— leave blank to auto-calculate</span></label>
                        <input type="number" step="0.01" min="0" name="total_price" id="modal-total-price" placeholder="Auto-calculated">
                    </div>

                    <div class="modal-field full-width">
                        <label>Notes</label>
                        <textarea name="notes" id="modal-notes" placeholder="Enter notes..."></textarea>
                    </div>

                </div>

                <div class="modal-actions">
                    <button type="button" class="btn-modal-cancel" onclick="closeEditModal()">Cancel</button>
                    <button type="submit" class="btn-modal-save">Save Changes</button>
                </div>

            </form>
        </div>
    </div>

    <!-- Add Booking Modal -->
    <div class="modal-overlay" id="addModal">
        <div class="modal-card">

            <div class="modal-header">
                <h2><i class="fas fa-plus"></i> Add Booking</h2>
                <button class="modal-close" onclick="closeAddModal()">✕</button>
            </div>

            <form action="AddBooking.php" method="POST">

                <div class="modal-grid">

                    <div class="modal-field full-width">
                        <label>Player</label>
                        <div class="search-select" data-search="addPlayer">
                            <input type="text" class="search-select-input" placeholder="Type to search a player..." autocomplete="off">
                            <input type="hidden" name="user_id" class="search-select-value" required>
                            <div class="search-select-list">
                                <?php foreach($users_list as $user): ?>
                                    <div class="search-select-item" data-id="<?php echo $user['id']; ?>" data-name="<?php echo htmlspecialchars($user['name']); ?>">
                                        <?php echo htmlspecialchars($user['name']); ?>
                                    </div>
                                <?php endforeach; ?>
                                <div class="search-select-empty" style="display:none;">No players match your search.</div>
                            </div>
                        </div>
                    </div>

                    <div class="modal-field full-width">
                        <label>Booking Date</label>
                        <input type="date" name="booking_date" id="add-booking-date" required>
                    </div>

                    <div class="modal-field full-width">
                        <label>Start Time</label>
                        <div class="slot-picker" id="addSlotPicker">
                            <div class="slot-picker-hint">Select a court and date first</div>
                        </div>
                        <input type="hidden" name="start_time" id="add-start-time" required>
                    </div>

                    <div class="modal-field full-width">
                        <label>Court Hours</label>
                        <div class="hours-picker" id="addHoursPicker">
                            <div class="slot-picker-hint">Select a start time first</div>
                        </div>
                        <input type="hidden" name="end_time" id="add-end-time" required>
                    </div>

                    <div class="modal-field">
                        <label>Court</label>
                        <div class="search-select" data-search="addCourt">
                            <input type="text" class="search-select-input" placeholder="Type to search a court..." autocomplete="off">
                            <input type="hidden" name="court_id" class="search-select-value" required>
                            <div class="search-select-list">
                                <?php foreach($courts_list as $court): ?>
                                    <div class="search-select-item" data-id="<?php echo $court['id']; ?>" data-name="<?php echo htmlspecialchars($court['court_name']); ?>">
                                        <?php echo htmlspecialchars($court['court_name']); ?>
                                    </div>
                                <?php endforeach; ?>
                                <div class="search-select-empty" style="display:none;">No courts match.</div>
                            </div>
                        </div>
                    </div>

                    <div class="modal-field">
                        <label>Coach</label>
                        <div class="search-select" data-search="addCoach">
                            <input type="text" class="search-select-input" placeholder="Type to search a coach..." autocomplete="off">
                            <input type="hidden" name="coach_id" class="search-select-value" value="0">
                            <div class="search-select-list">
                                <div class="search-select-item" data-id="0" data-name="No Coach">No Coach</div>
                                <?php foreach($coaches_list as $coach): ?>
                                    <div class="search-select-item" data-id="<?php echo $coach['id']; ?>" data-name="<?php echo htmlspecialchars($coach['name']); ?>">
                                        <?php echo htmlspecialchars($coach['name']); ?>
                                    </div>
                                <?php endforeach; ?>
                                <div class="search-select-empty" style="display:none;">No coaches match.</div>
                            </div>
                        </div>
                    </div>

                    <div class="modal-field full-width">
                        <label>Session Type</label>
                        <select name="session_type">
                            <option value="">— None —</option>
                            <option value="Training">Training</option>
                            <option value="Casual Play">Casual Play</option>
                            <option value="Tournament">Tournament</option>
                            <option value="Friendly Game">Friendly Game</option>
                        </select>
                    </div>

                    <div class="modal-field full-width">
                        <label>Notes</label>
                        <textarea name="notes" placeholder="Enter notes..."></textarea>
                    </div>

                </div>

                <div class="modal-actions">
                    <button type="button" class="btn-modal-cancel" onclick="closeAddModal()">Cancel</button>
                    <button type="submit" class="btn-modal-save">Add Booking</button>
                </div>

            </form>
        </div>
    </div>

    <!-- Price Breakdown Modal (Admin / Superadmin) -->
    <div class="modal-overlay" id="breakdownModal">
        <div class="modal-card" style="max-width:420px;">
            <div class="modal-header">
                <h2><i class="fas fa-receipt"></i> Price Breakdown — Booking #<span id="bd-id"></span></h2>
                <button class="modal-close" onclick="closeBreakdown()">✕</button>
            </div>
            <div id="bd-body" class="bd-body"></div>
        </div>
    </div>

    <script src="ManageBookings.js"></script>
    <script src="../Dashboard/Dashboard.js"></script>

    <script>
    /* Filter search-selects (Court / Coach): apply the filter as soon as an option is picked */
    document.querySelectorAll('.filter-panel .filter-search .search-select-item').forEach(function (item) {
        item.addEventListener('click', function () {
            var form = item.closest('form');
            if (form) form.submit();
        });
    });
    </script>

    <?php if ($role !== 'Coach'): ?>
    <script>
    /* Price breakdown modal — clicking a booking's total shows court / coach / add-on details */
    const bookingBreakdowns = <?php echo json_encode($breakdowns); ?>;

    function rmRow(label, amount, negative) {
        const sign = negative ? '−RM ' : 'RM ';
        return '<div class="bd-row"><span>' + label + '</span><span>' + sign + Number(amount).toFixed(2) + '</span></div>';
    }

    function openBreakdown(id) {
        const b = bookingBreakdowns[id];
        if (!b) return;

        document.getElementById('bd-id').textContent = id;

        let html = rmRow('Court Fee', b.court);
        if (b.coach > 0) html += rmRow('Coach Fee', b.coach);

        if (b.addons.length) {
            html += '<div class="bd-section">Add-ons</div>';
            b.addons.forEach(function (a) {
                html += rmRow(a.name + ' × ' + a.qty, a.subtotal);
            });
        }

        html += '<div class="bd-total">' + rmRow('Total', b.total) + '</div>';

        if (b.status === 'Cancelled' && b.cancellation_fee > 0) {
            html += rmRow('Cancellation Fee', b.cancellation_fee, true);
        }

        document.getElementById('bd-body').innerHTML = html;
        document.getElementById('breakdownModal').classList.add('active');
    }

    function closeBreakdown() {
        document.getElementById('breakdownModal').classList.remove('active');
    }
    </script>
    <?php endif; ?>

    <?php if ($role === 'Coach'): ?>
    <script>
    /* Confirmation text differs depending on how close the session is */
    function confirmCoachDecline(isLate) {
        if (isLate) {
            return confirm('This is a LATE cancellation (less than 24 hours before the session).\n\nThe customer will be fully refunded plus compensation, and your account will be suspended (escalating: 3 days, then 7 days, then permanent).\n\nDecline anyway?');
        }
        return confirm('Decline this session? The customer will receive a full refund. Since you are giving enough notice, there is no penalty.');
    }

    function handleCoachStatusChange(sel, bookingId, isLate) {
        const val = sel.value;
        if (val === 'Confirmed') {
            location.href = 'UpdateBookingsStatus.php?id=' + bookingId + '&status=Confirmed';
        } else if (val === 'Cancelled') {
            if (confirmCoachDecline(isLate)) {
                location.href = 'coach_decline.php?id=' + bookingId;
            } else {
                sel.value = 'Pending';
            }
        }
    }
    </script>
    <?php endif; ?>

    <?php if ($highlight_id): ?>
    <script>
        // Scroll to the highlighted booking row from the dashboard calendar
        document.addEventListener('DOMContentLoaded', function () {
            const row = document.getElementById('booking-row-<?php echo $highlight_id; ?>');
            if (row) {
                row.scrollIntoView({ behavior: 'smooth', block: 'center' });
                // Auto-expand the details panel for that row
                toggleDetails(<?php echo $highlight_id; ?>, row);
            }
        });
    </script>
    <?php endif; ?>

    <!-- Modal styling -->
    <?php include __DIR__ . '/../modal.php'; ?>

    <!-- Toast notifications -->
    <?php include __DIR__ . '/../toast/toast.php'; ?>
</body>
</html>