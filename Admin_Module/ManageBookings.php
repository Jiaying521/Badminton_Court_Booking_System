<?php
    session_start();
    if(!isset($_SESSION['username'])){
        header("Location: LoginPage.php");
        exit();
    }

    //Check role only Superadmin and Admin can access
    if(!in_array($_SESSION['role'],['Superadmin','Admin'])){
        header("Location: LoginPage.php");
        exit();
    }

    //Prevent Browser Caching
    header("Cache-Control: no-cache, no-store, must-revalidate"); 
    header("Pragma: no-cache");
    header("Expires: 0");

    //Database Connection
    $conn = mysqli_connect("localhost", "root", "", "badminton_hub");

    $username = $_SESSION['username'];
    $role = $_SESSION['role'];
    $display_name = $username; //Show "Hello, xxx" in the header

    // Filter by status if selected
    $filter = isset($_GET['status']) ? $_GET['status'] : 'All';

    $where = "";
    if($filter !== 'All'){
        $safe_filter = mysqli_real_escape_string($conn, $filter);
        $where = "WHERE bookings.status = '$safe_filter'";
    }

    // Fetch all courts for edit modal dropdown
    $courts_result = mysqli_query($conn, "SELECT id, court_name FROM courts WHERE is_active = 1 ORDER BY court_name ASC");
    $courts_list = [];
    while($c = mysqli_fetch_assoc($courts_result)) $courts_list[] = $c;

    // Fetch all coaches for edit modal dropdown
    $coaches_result = mysqli_query($conn, "SELECT id, name FROM coaches WHERE is_active = 1 ORDER BY name ASC");
    $coaches_list = [];
    while($c = mysqli_fetch_assoc($coaches_result)) $coaches_list[] = $c;

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

    JOIN users ON bookings.user_id = users.id
    JOIN courts ON bookings.court_id = courts.id
    LEFT JOIN coaches ON bookings.coach_id = coaches.id

    $where

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
                    <h1>Bookings Management</h1>
                    <p> Manage all court bookings, view details, and update booking statuses.</p>
                </div>
            </header>

            <!-- Filter Bar -->
            <div class="filter-bar">
                <a href="ManageBookings.php" class="filter-btn <?php echo $filter === 'All' ? 'active' : ''; ?>">All</a>
                <a href="ManageBookings.php?status=Pending" class="filter-btn <?php echo $filter === 'Pending' ? 'active' : ''; ?>">Pending</a>
                <a href="ManageBookings.php?status=Confirmed" class="filter-btn <?php echo $filter === 'Confirmed' ? 'active' : ''; ?>">Confirmed</a>
                <a href="ManageBookings.php?status=Completed" class="filter-btn <?php echo $filter === 'Completed' ? 'active' : ''; ?>">Completed</a>
                <a href="ManageBookings.php?status=Cancelled" class="filter-btn <?php echo $filter === 'Cancelled' ? 'active' : ''; ?>">Cancelled</a>
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
                            <!-- Status Dropdown (mirrors AdminManagement.php style) -->
                            <!-- stopPropagation prevents row click when using dropdown -->
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

            <form action="UpdateBooking.php" method="POST">
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

    <script src="SuperAdminDashboard.js"></script>

    <script>
        // Toggle details row open/close when main row is clicked
        function toggleDetails(id, row) {
            var detailsRow = document.getElementById('details-' + id);
            var isOpen = detailsRow.classList.contains('open');

            // Close all open detail rows first
            document.querySelectorAll('.details-row.open').forEach(function(r) {
                r.classList.remove('open');
            });
            document.querySelectorAll('.main-row.open').forEach(function(r) {
                r.classList.remove('open');
            });

            // If it was not open, open it
            if (!isOpen) {
                detailsRow.classList.add('open');
                row.classList.add('open');
            }
        }

        // Open edit modal and populate fields with current booking data
        function openEditModal(id, date, startTime, endTime, courtId, coachId, sessionType, notes) {
            document.getElementById('modal-booking-id').value   = id;
            document.getElementById('modal-booking-date').value = date;
            document.getElementById('modal-start-time').value   = startTime;
            document.getElementById('modal-end-time').value     = endTime;
            document.getElementById('modal-court-id').value     = courtId;
            document.getElementById('modal-coach-id').value     = coachId;
            document.getElementById('modal-session-type').value = sessionType;
            document.getElementById('modal-notes').value        = notes;

            document.getElementById('editModal').classList.add('active');
        }

        // Close edit modal
        function closeEditModal() {
            document.getElementById('editModal').classList.remove('active');
        }

        // Close modal when clicking outside the card
        document.getElementById('editModal').addEventListener('click', function(e) {
            if (e.target === this) closeEditModal();
        });
    </script>
</body>
</html>