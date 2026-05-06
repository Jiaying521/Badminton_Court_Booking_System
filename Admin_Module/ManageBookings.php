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

    // Filter values from GET
    $filter_status  = isset($_GET['status'])   ? $_GET['status']   : '';
    $filter_court   = isset($_GET['court'])    ? intval($_GET['court'])   : 0;
    $filter_coach   = isset($_GET['coach'])    ? intval($_GET['coach'])   : 0;
    $filter_date_from = isset($_GET['date_from']) ? mysqli_real_escape_string($conn, $_GET['date_from']) : '';
    $filter_date_to   = isset($_GET['date_to'])   ? mysqli_real_escape_string($conn, $_GET['date_to'])   : '';
    $filter_search    = isset($_GET['search'])    ? mysqli_real_escape_string($conn, $_GET['search'])    : '';

    // Check if any filter is active
    $has_filter = $filter_status || $filter_court || $filter_coach || $filter_date_from || $filter_date_to || $filter_search;

    // Build WHERE clause
    $where_parts = [];
    if($filter_status !== '')    $where_parts[] = "bookings.status = '$filter_status'";
    if($filter_court > 0)        $where_parts[] = "bookings.court_id = $filter_court";
    if($filter_coach > 0)        $where_parts[] = "bookings.coach_id = $filter_coach";
    if($filter_date_from !== '')  $where_parts[] = "bookings.booking_date >= '$filter_date_from'";
    if($filter_date_to !== '')    $where_parts[] = "bookings.booking_date <= '$filter_date_to'";
    if($filter_search !== '')     $where_parts[] = "(users.name LIKE '%$filter_search%' OR courts.court_name LIKE '%$filter_search%')";

    // Coach only sees bookings assigned to them
    if($role === 'Coach'){
        $coach_id_query = mysqli_query($conn, "SELECT id FROM coaches WHERE admin_id = " . (int)$_SESSION['id']);
        $coach_row      = mysqli_fetch_assoc($coach_id_query);
        $my_coach_id    = $coach_row ? (int)$coach_row['id'] : 0;
        $where_parts[]  = "bookings.coach_id = $my_coach_id";
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
            COALESCE(coaches.name, 'No Coach') AS coach_name

        FROM bookings

        JOIN users   ON bookings.user_id  = users.id
        JOIN courts  ON bookings.court_id = courts.id
        LEFT JOIN coaches ON bookings.coach_id = coaches.id

        $where_sql

        ORDER BY bookings.booking_date DESC
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
                    <p><?php echo ($role === 'Coach') ? 'View your assigned court sessions.' : 'Manage all court bookings, view details, and update booking statuses.'; ?></p>
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
                    <div class="filter-field">
                        <label>Date From</label>
                        <input type="date" name="date_from" value="<?php echo htmlspecialchars($filter_date_from); ?>">
                    </div>
                    <div class="filter-field">
                        <label>Date To</label>
                        <input type="date" name="date_to" value="<?php echo htmlspecialchars($filter_date_to); ?>">
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
                    <tr>
                        <th>ID</th>
                        <th>Player Name</th>
                        <th>Court Name</th>
                        <th>Booking Date</th>
                        <th>Start Time</th>
                        <th>End Time</th>
                        <th>Total Price</th>
                        <th>Status</th>
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
                            <!-- Status Dropdown — stopPropagation prevents row click when using dropdown -->
                            <?php if($role === 'Coach'): ?>
                                <span class="status-select <?php 
                                    if($row['status'] == 'Confirmed') echo 'status-active';
                                    elseif($row['status'] == 'Pending') echo 'status-inactive';
                                    elseif($row['status'] == 'Cancelled') echo 'status-suspended';
                                    else echo 'status-active';
                                ?>"><?php echo $row['status']; ?></span>
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
                                        <label>Coach</label>
                                        <span><?php echo htmlspecialchars($row['coach_name']); ?></span>
                                    </div>
                                    <div class="details-item">
                                        <label>Session Type</label>
                                        <span><?php echo htmlspecialchars($row['session_type'] ?: '—'); ?></span>
                                    </div>
                                    <div class="details-item">
                                        <label>Time Range</label>
                                        <span><?php echo date("h:i A", strtotime($row['start_time'])) . ' – ' . date("h:i A", strtotime($row['end_time'])); ?></span>
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
