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
    if (isset($_GET['updated']))     { $toasts[] = ['text' => 'Booking status updated successfully!', 'type' => 'success']; }
    if (isset($_GET['edited']))      { $toasts[] = ['text' => 'Booking updated successfully!',        'type' => 'success']; }
    if (isset($_GET['added']))       { $toasts[] = ['text' => 'Booking added successfully!',          'type' => 'success']; }
    if (isset($_GET['conflict']))    { $toasts[] = ['text' => 'This time slot is already booked. Please choose another time.', 'type' => 'pending']; }
    if (isset($_GET['proof_error'])) { $toasts[] = ['text' => 'Photo upload failed. Please use JPG/PNG under 5MB.', 'type' => 'error']; }

    // Handle bulk action
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['ids'])) {
        $ids    = array_filter(array_map('intval', explode(',', $_POST['ids'])));
        $action = $_POST['action'];

        if (!empty($ids)) {
            $conn_bulk = mysqli_connect("localhost", "root", "", "badminton_hub");
            $ids_str   = implode(',', $ids);
            $role_bulk = $_SESSION['role'];

            if ($role_bulk === 'Coach') {
                // Coach: can only mark complete or decline their own bookings
                $my_coach_q   = mysqli_query($conn_bulk, "SELECT id FROM coaches WHERE admin_id = " . (int)$_SESSION['id']);
                $my_coach_row = mysqli_fetch_assoc($my_coach_q);
                $my_cid       = $my_coach_row ? (int)$my_coach_row['id'] : 0;

                if ($action === 'confirm') {
                    mysqli_query($conn_bulk, "UPDATE bookings SET status='Completed' WHERE id IN ($ids_str) AND coach_id = $my_cid");
                } elseif ($action === 'cancel') {
                    mysqli_query($conn_bulk, "UPDATE bookings SET status='Cancelled' WHERE id IN ($ids_str) AND coach_id = $my_cid");
                }

            } elseif (in_array($role_bulk, ['Superadmin', 'Admin'])) {
                if ($action === 'confirm') {
                    mysqli_query($conn_bulk, "UPDATE bookings SET status='Confirmed' WHERE id IN ($ids_str)");
                } elseif ($action === 'cancel') {
                    mysqli_query($conn_bulk, "UPDATE bookings SET status='Cancelled' WHERE id IN ($ids_str)");
                } elseif ($action === 'complete') {
                    mysqli_query($conn_bulk, "UPDATE bookings SET status='Completed' WHERE id IN ($ids_str)");
                } elseif ($action === 'delete') {
                    mysqli_query($conn_bulk, "DELETE FROM bookings WHERE id IN ($ids_str)");
                }
            }

            mysqli_close($conn_bulk);
        }

        header("Location: ManageBookings.php?updated=1");
        exit();
    }

    // Database Connection
    $conn = mysqli_connect("localhost", "root", "", "badminton_hub");

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
    $filter_coach     = isset($_GET['coach'])     ? intval($_GET['coach'])                               : 0;
    // ?date= comes from "View All Bookings" calendar link; ?booking_date= from the filter form
    $filter_booking_date = isset($_GET['booking_date']) ? mysqli_real_escape_string($conn, $_GET['booking_date'])
                         : (isset($_GET['date'])         ? mysqli_real_escape_string($conn, $_GET['date']) : '');
    $filter_search    = isset($_GET['search'])    ? mysqli_real_escape_string($conn, $_GET['search'])    : '';

    // Sort handling
    $allowed_sorts = ['id', 'name', 'court_name', 'booking_date', 'start_time', 'end_time', 'total_price', 'status'];
    $sort_col = isset($_GET['sort']) && in_array($_GET['sort'], $allowed_sorts) ? $_GET['sort'] : 'booking_date';
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
    $has_filter = $filter_status || $filter_court || $filter_coach || $filter_booking_date || $filter_search;

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
        // Admin/Superadmin: allow coach filter
        if($filter_coach > 0) $where_parts[] = "bookings.coach_id = $filter_coach";
    }

    $where_sql = count($where_parts) > 0 ? "WHERE " . implode(" AND ", $where_parts) : "";

    // Fetch booking data with player name, court name, coach name, session type and notes
    $result = mysqli_query($conn, "
        SELECT 
            bookings.id,
            bookings.court_id,
            bookings.coach_id,
            users.name,
            courts.court_name,
            bookings.booking_date,
            bookings.start_time,
            bookings.end_time,
            bookings.status,
            bookings.total_price,
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
    ");

?>
<!DOCTYPE html>
<html lang="en">
<head> 
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Badminton Hub - Bookings Management</title>
    
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
                        <select name="court">
                            <option value="0">All Courts</option>
                            <?php foreach($courts_list as $court): ?>
                                <option value="<?php echo $court['id']; ?>" <?php echo ($filter_court === $court['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($court['court_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php if($role !== 'Coach'): ?>
                    <div class="filter-field">
                        <label>Coach</label>
                        <select name="coach">
                            <option value="0">All Coaches</option>
                            <?php foreach($coaches_list as $coach): ?>
                                <option value="<?php echo $coach['id']; ?>" <?php echo ($filter_coach === $coach['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($coach['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
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
                        <th><?php echo bookingSortLink('Booking Date', 'booking_date', $sort_col, $sort_dir, $next_dir, $extra); ?></th>
                        <th><?php echo bookingSortLink('Start Time',   'start_time',   $sort_col, $sort_dir, $next_dir, $extra); ?></th>
                        <th><?php echo bookingSortLink('End Time',     'end_time',     $sort_col, $sort_dir, $next_dir, $extra); ?></th>
                        <th><?php echo bookingSortLink('Total Price',  'total_price',  $sort_col, $sort_dir, $next_dir, $extra); ?></th>
                        <th><?php echo bookingSortLink('Status',       'status',       $sort_col, $sort_dir, $next_dir, $extra); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($row = mysqli_fetch_assoc($result)): ?>

                    <!-- Main row — click to expand details -->
                    <tr id="booking-row-<?php echo $row['id']; ?>"
                        class="main-row<?php echo ($highlight_id === (int)$row['id']) ? ' booking-highlight' : ''; ?>">
                        <td class="bulk-col" onclick="event.stopPropagation()">
                            <input type="checkbox" class="row-check" value="<?php echo $row['id']; ?>" onchange="updateBulkCount()">
                        </td>
                        <td><?php echo htmlspecialchars($row['name']); ?></td>
                        <td><?php echo htmlspecialchars($row['court_name']); ?></td>
                        <td><?php echo date("d-m-Y", strtotime($row['booking_date'])); ?></td>
                        <td><?php echo date("h:i A", strtotime($row['start_time'])); ?></td>
                        <td><?php echo date("h:i A", strtotime($row['end_time'])); ?></td>
                        <td>RM <?php echo number_format($row['total_price'], 2); ?></td>
                        <td onclick="event.stopPropagation()">
                            <?php if($role === 'Coach'): ?>
                                <!-- Coach: only show dropdown if Pending, else show static badge -->
                                <?php if($row['status'] === 'Pending'): ?>
                                    <select class="status-select status-inactive" onchange="location.href='UpdateBookingsStatus.php?id=<?php echo $row['id']; ?>&status=' + this.value">
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
                        <td colspan="8">
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
                            <div class="details-item">
                                <label>Total Price</label>
                                <span>RM <?php echo number_format($row['total_price'], 2); ?></span>
                            </div>
                            <div class="details-item">
                                <label>Cancellation Fee</label>
                                <span>RM <?php echo number_format($row['cancellation_fee'] ?? 0, 2); ?></span>
                            </div>
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
                                        <a href="UpdateBookingsStatus.php?id=<?php echo $row['id']; ?>&status=Cancelled"
                                           class="btn-coach-action btn-coach-decline"
                                           onclick="event.stopPropagation(); return confirm('Decline this session?');">
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

                    <div class="modal-field">
                        <label>Start Time</label>
                        <input type="time" name="start_time" id="modal-start-time" required>
                    </div>

                    <div class="modal-field">
                        <label>End Time</label>
                        <input type="time" name="end_time" id="modal-end-time" required>
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
                        <input type="date" name="booking_date" required>
                    </div>

                    <div class="modal-field">
                        <label>Start Time</label>
                        <input type="time" name="start_time" required>
                    </div>

                    <div class="modal-field">
                        <label>End Time</label>
                        <input type="time" name="end_time" required>
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

    <script src="ManageBookings.js"></script>
    <script src="../Dashboard/Dashboard.js"></script>

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