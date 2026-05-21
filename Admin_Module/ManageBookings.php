<?php
    session_start();
    if(!isset($_SESSION['username'])){
        header("Location: LoginPage.php");
        exit();
    }

    // Check role - only Superadmin, Admin and Coach can access
    if(!in_array($_SESSION['role'], ['Superadmin', 'Admin', 'Coach'])){
        header("Location: LoginPage.php");
        exit();
    }

    // Prevent Browser Caching
    header("Cache-Control: no-cache, no-store, must-revalidate"); 
    header("Pragma: no-cache");
    header("Expires: 0");

    // Database Connection
    $conn = mysqli_connect("localhost", "root", "", "badminton_hub");

    $username     = $_SESSION['username'];
    $role         = $_SESSION['role'];
    $display_name = $username;

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

    // Filter values from GET
    $filter_status    = isset($_GET['status'])    ? $_GET['status']                                      : '';
    $filter_court     = isset($_GET['court'])     ? intval($_GET['court'])                               : 0;
    $filter_coach     = isset($_GET['coach'])     ? intval($_GET['coach'])                               : 0;
    $filter_booking_date = isset($_GET['booking_date']) ? mysqli_real_escape_string($conn, $_GET['booking_date']) : '';
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
    <link rel="stylesheet" href="ManageBookings.css">
    <link rel="stylesheet" href="SuperAdminDashboard.css">
    <link rel="stylesheet" href="AdminManagement.css">
</head>

<body>
    <!-- Nav Bar -->
    <?php include 'navbar.php';?>

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
                        <?php if($has_filter): ?>
                            <span class="filter-dot"></span>
                        <?php endif; ?>
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

            <!-- Update Success Message -->
            <?php if(isset($_GET['updated'])): ?>
                <div class="badge success" style="width:100%; padding:15px; margin-bottom:20px;">
                    Booking status updated successfully!
                </div>
            <?php endif; ?>

            <!-- Edit Success Message -->
            <?php if(isset($_GET['edited'])): ?>
                <div class="badge success" style="width:100%; padding:15px; margin-bottom:20px;">
                    Booking updated successfully!
                </div>
            <?php endif; ?>

            <!-- Add Success Message -->
            <?php if(isset($_GET['added'])): ?>
                <div class="badge success" style="width:100%; padding:15px; margin-bottom:20px;">
                    Booking added successfully!
                </div>
            <?php endif; ?>

            <!-- Add Conflict Error Message -->
            <?php if(isset($_GET['conflict'])): ?>
                <div class="badge pending" style="width:100%; padding:15px; margin-bottom:20px;">
                    This time slot is already booked. Please choose another time.
                </div>
            <?php endif; ?>

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
                        <th><?php echo bookingSortLink('ID',           'id',           $sort_col, $sort_dir, $next_dir, $extra); ?></th>
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
                    <tr class="main-row" onclick="toggleDetails(<?php echo $row['id']; ?>, this)">
                        <td><?php echo $row['id']; ?> <span class="expand-icon">▼</span></td>
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
                                    <!-- Mark as Completed (only for Confirmed) -->
                                    <a href="UpdateBookingsStatus.php?id=<?php echo $row['id']; ?>&status=Completed"
                                       class="btn-edit-booking"
                                       onclick="event.stopPropagation(); return confirm('Mark this session as Completed?');"
                                       style="background:#10b981; color:#fff; border:none; text-decoration:none; display:inline-block;">
                                        <i class="fas fa-check"></i> Mark as Completed
                                    </a>
                                    <!-- Decline a Confirmed booking -->
                                    <a href="UpdateBookingsStatus.php?id=<?php echo $row['id']; ?>&status=Cancelled"
                                       class="btn-edit-booking"
                                       onclick="event.stopPropagation(); return confirm('Are you sure you want to decline this session?');"
                                       style="background:#ef4444; color:#fff; border:none; text-decoration:none; display:inline-block; margin-top:8px;">
                                        <i class="fas fa-times"></i> Decline Session
                                    </a>
                                    <?php endif; ?>

                                <?php endif; ?>

                            </div>
                        </td>
                    </tr>

                    <?php endwhile; ?>

                </tbody>
            </table>
        </div>
    </main>

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
                        <select name="court_id" id="modal-court-id" required>
                            <?php foreach($courts_list as $court): ?>
                                <option value="<?php echo $court['id']; ?>">
                                    <?php echo htmlspecialchars($court['court_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="modal-field">
                        <label>Coach</label>
                        <select name="coach_id" id="modal-coach-id">
                            <option value="0">No Coach</option>
                            <?php foreach($coaches_list as $coach): ?>
                                <option value="<?php echo $coach['id']; ?>">
                                    <?php echo htmlspecialchars($coach['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
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
                        <select name="user_id" required>
                            <option value="">Select Player</option>
                            <?php foreach($users_list as $user): ?>
                                <option value="<?php echo $user['id']; ?>">
                                    <?php echo htmlspecialchars($user['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
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
                        <select name="court_id" required>
                            <option value="">Select Court</option>
                            <?php foreach($courts_list as $court): ?>
                                <option value="<?php echo $court['id']; ?>">
                                    <?php echo htmlspecialchars($court['court_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="modal-field">
                        <label>Coach</label>
                        <select name="coach_id">
                            <option value="0">No Coach</option>
                            <?php foreach($coaches_list as $coach): ?>
                                <option value="<?php echo $coach['id']; ?>">
                                    <?php echo htmlspecialchars($coach['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
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
    <script src="SuperAdminDashboard.js"></script>
</body>
</html>