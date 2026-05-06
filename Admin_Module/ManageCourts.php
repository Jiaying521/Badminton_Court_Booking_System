<?php
    
    // Login Status Check
    session_start();
    if(!isset($_SESSION['username'])){
        header("Location: LoginPage.php");
        exit();
    }

    // Check role - only Superadmin and Admin can access
    if(!in_array($_SESSION['role'], ['Superadmin', 'Admin'])){
        header("Location: LoginPage.php");
        exit();
    }

    // Prevent Browser Caching
    header("Cache-Control: no-cache, no-store, must-revalidate"); 
    header("Pragma: no-cache");
    header("Expires: 0");

    // Database Connection
    $conn = mysqli_connect("localhost", "root", "", "badminton_hub");

    // Take session user information
    $username     = $_SESSION['username'];
    $role         = $_SESSION['role'];
    $display_name = $username;

    // Handle delete from modal
    if(isset($_POST['delete_court'])){
        $del_id       = intval($_POST['court_id_delete']);
        $booking_check = mysqli_query($conn, "SELECT id FROM bookings WHERE court_id = $del_id LIMIT 1");
        if($booking_check && mysqli_num_rows($booking_check) > 0){
            mysqli_query($conn, "UPDATE courts SET is_active = 0 WHERE id = $del_id");
        } else {
            mysqli_query($conn, "DELETE FROM courts WHERE id = $del_id");
        }
        header("Location: ManageCourts.php?deleted=1");
        exit();
    }

    // Handle edit from modal
    if(isset($_POST['update_court'])){
        $court_id       = intval($_POST['court_id']);
        $court_name     = mysqli_real_escape_string($conn, $_POST['court_name']);
        $court_type     = mysqli_real_escape_string($conn, $_POST['court_type']);
        $location       = mysqli_real_escape_string($conn, $_POST['location']);
        $facilities     = mysqli_real_escape_string($conn, $_POST['facilities']);
        $price_off_peak = mysqli_real_escape_string($conn, $_POST['price_off_peak']);
        $price_peak     = mysqli_real_escape_string($conn, $_POST['price_peak']);
        $is_active      = isset($_POST['is_active']) ? 1 : 0;

        $check = mysqli_query($conn, "SELECT id FROM courts WHERE court_name = '$court_name' AND id != $court_id");
        if(mysqli_num_rows($check) > 0){
            header("Location: ManageCourts.php?error=duplicate");
            exit();
        }

        mysqli_query($conn, "
            UPDATE courts
            SET court_name     = '$court_name',
                court_type     = '$court_type',
                location       = '$location',
                facilities     = '$facilities',
                price_off_peak = '$price_off_peak',
                price_peak     = '$price_peak',
                is_active      = '$is_active'
            WHERE id = $court_id
        ");
        header("Location: ManageCourts.php?success=1");
        exit();
    }

    // Filter values from GET
    $filter_type   = isset($_GET['type'])   ? $_GET['type']   : '';
    $filter_status = isset($_GET['status']) ? $_GET['status'] : '';
    $filter_search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';

    // Build WHERE clause
    $where_parts = [];
    if($filter_type !== '')   $where_parts[] = "court_type = '$filter_type'";
    if($filter_status !== '') $where_parts[] = "is_active = " . ($filter_status === 'Active' ? 1 : 0);
    if($filter_search !== '') $where_parts[] = "(court_name LIKE '%$filter_search%' OR location LIKE '%$filter_search%' OR facilities LIKE '%$filter_search%')";

    $where_sql = count($where_parts) > 0 ? "WHERE " . implode(" AND ", $where_parts) : "";

    // Fetch court data from database
    $result = mysqli_query($conn, "SELECT * FROM courts $where_sql ORDER BY id DESC");

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Badminton Hub - Court Management</title>

    <!-- Font Awesome CDN -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <!-- Google Fonts CDN -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800;900&display=swap">

    <!-- Connect previous CSS -->
    <link rel="stylesheet" href="SuperAdminDashboard.css">
    <link rel="stylesheet" href="AdminManagement.css">
    <link rel="stylesheet" href="ManageCourts.css">
</head>
<body>
    
    <!-- Nav Bar -->
    <?php include 'navbar.php'; ?>
    
    <!-- Main Content -->
    <main class="content">
        <div class="manage-container">

            <!-- Title and Add Court Button -->
            <header class="management-header">
                <div>
                    <h1>Court Management</h1>
                    <p>Manage badminton courts, availability and pricing.</p>
                </div>
                <div class="btn-add-group">
                    <button class="btn-filter-toggle" onclick="toggleFilter()">
                        <i class="fas fa-filter"></i> Filter
                        <?php if($filter_type || $filter_status || $filter_search): ?>
                            <span class="filter-dot"></span>
                        <?php endif; ?>
                    </button>
                    <a href="AddCourt.php" class="btn-add-account" style="text-decoration:none;">
                        <i class="fas fa-plus"></i> Add Court
                    </a>
                </div>
            </header>

            <!-- Collapsible Filter Panel -->
            <div class="filter-panel <?php echo ($filter_type || $filter_status || $filter_search) ? 'open' : ''; ?>" id="filterPanel">
                <form method="GET" class="filter-grid">
                    <div class="filter-field">
                        <label>Search</label>
                        <input type="text" name="search" placeholder="Court name, location, facilities..." value="<?php echo htmlspecialchars($filter_search); ?>">
                    </div>
                    <div class="filter-field">
                        <label>Type</label>
                        <select name="type">
                            <option value="">All Types</option>
                            <option value="Standard" <?php echo ($filter_type === 'Standard') ? 'selected' : ''; ?>>Standard</option>
                            <option value="Training" <?php echo ($filter_type === 'Training') ? 'selected' : ''; ?>>Training</option>
                        </select>
                    </div>
                    <div class="filter-field">
                        <label>Status</label>
                        <select name="status">
                            <option value="">All Status</option>
                            <option value="Active"   <?php echo ($filter_status === 'Active')   ? 'selected' : ''; ?>>Active</option>
                            <option value="Inactive" <?php echo ($filter_status === 'Inactive') ? 'selected' : ''; ?>>Inactive</option>
                        </select>
                    </div>
                    <div class="filter-actions">
                        <button type="submit" class="btn-filter-apply"><i class="fas fa-search"></i> Apply</button>
                        <a href="ManageCourts.php" class="btn-filter-clear">Clear</a>
                    </div>
                </form>
            </div>

            <!-- Success / Error Messages -->
            <?php if(isset($_GET['success'])): ?>
                <div class="badge success" style="width:100%; padding:15px; margin-bottom:20px;">
                    Court saved successfully!
                </div>
            <?php endif; ?>

            <?php if(isset($_GET['deleted'])): ?>
                <div class="badge pending" style="width:100%; padding:15px; margin-bottom:20px;">
                    Court deleted (or deactivated) successfully.
                </div>
            <?php endif; ?>

            <?php if(isset($_GET['error']) && $_GET['error'] === 'duplicate'): ?>
                <div class="badge pending" style="width:100%; padding:15px; margin-bottom:20px;">
                    Error: Court name already exists!
                </div>
            <?php endif; ?>

            <!-- Court Table -->
            <table class="data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Court Info</th>
                        <th>Type</th>
                        <th>Pricing</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($row = mysqli_fetch_assoc($result)): ?>

                    <!-- Main row — click to open edit modal -->
                    <tr class="main-row" onclick="openCourtModal(
                        <?php echo $row['id']; ?>,
                        '<?php echo addslashes($row['court_name']); ?>',
                        '<?php echo addslashes($row['court_type']); ?>',
                        '<?php echo addslashes($row['location']); ?>',
                        '<?php echo addslashes($row['facilities']); ?>',
                        '<?php echo $row['price_off_peak']; ?>',
                        '<?php echo $row['price_peak']; ?>',
                        <?php echo (int)$row['is_active']; ?>
                    )">
                        <td>#<?php echo $row['id']; ?></td>
                        <td>
                            <strong><?php echo htmlspecialchars($row['court_name']); ?></strong>
                            <br>
                            <small style="color:#999;"><?php echo htmlspecialchars($row['location']); ?></small>
                        </td>
                        <td><?php echo htmlspecialchars($row['court_type']); ?></td>
                        <td>
                            Off-Peak: RM <?php echo number_format($row['price_off_peak'], 2); ?><br>
                            <small style="color:#999;">Peak: RM <?php echo number_format($row['price_peak'], 2); ?></small>
                        </td>
                        <td>
                            <?php if($row['is_active'] == 1): ?>
                                <span class="badge success">Active</span>
                            <?php else: ?>
                                <span class="badge pending">Inactive</span>
                            <?php endif; ?>
                        </td>
                    </tr>

                    <?php endwhile; ?>
                </tbody>
            </table>

        </div>
    </main>

    <!-- Edit Court Modal -->
    <div class="modal-overlay" id="courtModal">
        <div class="modal-card">

            <div class="modal-header">
                <h2><i class="fas fa-pen"></i> Edit Court</h2>
                <button class="modal-close" onclick="closeCourtModal()">✕</button>
            </div>

            <!-- Edit Form -->
            <form action="ManageCourts.php" method="POST">
                <input type="hidden" name="court_id" id="modal-court-id">

                <div class="modal-grid">

                    <div class="modal-field">
                        <label>Court Name</label>
                        <input type="text" name="court_name" id="modal-court-name" required>
                    </div>

                    <div class="modal-field">
                        <label>Court Type</label>
                        <select name="court_type" id="modal-court-type" required>
                            <option value="Standard">Standard</option>
                            <option value="Training">Training</option>
                        </select>
                    </div>

                    <div class="modal-field">
                        <label>Location</label>
                        <input type="text" name="location" id="modal-location">
                    </div>

                    <div class="modal-field">
                        <label>Facilities</label>
                        <input type="text" name="facilities" id="modal-facilities">
                    </div>

                    <div class="modal-field">
                        <label>Off-Peak Price (RM)</label>
                        <input type="number" name="price_off_peak" id="modal-price-off-peak" step="0.01" min="0" required>
                    </div>

                    <div class="modal-field">
                        <label>Peak Price (RM)</label>
                        <input type="number" name="price_peak" id="modal-price-peak" step="0.01" min="0" required>
                    </div>

                    <div class="modal-field full-width">
                        <label class="checkbox-label">
                            <input type="checkbox" name="is_active" id="modal-is-active"> Active
                        </label>
                    </div>

                </div>

                <div class="modal-actions">
                    <!-- Delete button on the left -->
                    <form action="ManageCourts.php" method="POST" style="margin:0;" onsubmit="return confirm('Delete this court permanently?')">
                        <input type="hidden" name="court_id_delete" id="modal-delete-id">
                        <button type="submit" name="delete_court" class="btn-modal-delete">
                            <i class="fas fa-trash-alt"></i> Delete
                        </button>
                    </form>
                    <div style="display:flex; gap:10px;">
                        <button type="button" class="btn-modal-cancel" onclick="closeCourtModal()">Cancel</button>
                        <button type="submit" name="update_court" class="btn-modal-save">Save Changes</button>
                    </div>
                </div>

            </form>
        </div>
    </div>

    <script src="ManageCourts.js"></script>
    <script src="SuperAdminDashboard.js"></script>
</body>
</html>
