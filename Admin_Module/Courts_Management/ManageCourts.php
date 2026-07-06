<?php
    
    // Login Status Check
    session_start();
    require_once __DIR__ . '/../toast/toast_init.php';
    if(!isset($_SESSION['username'])){
        header("Location: ../LoginPage.php");
        exit();
    }

    // Check role - only Superadmin and Admin can access
    if(!in_array($_SESSION['role'], ['Superadmin', 'Admin'])){
        header("Location: ../LoginPage.php");
        exit();
    }

    // Prevent Browser Caching
    header("Cache-Control: no-cache, no-store, must-revalidate"); 
    header("Pragma: no-cache");
    header("Expires: 0");

    // Toast notifications from URL params (redirects from AddCourt / edit_court / delete)
    if (isset($_GET['success']))  { $toasts[] = ['text' => 'Court saved successfully!', 'type' => 'success']; }
    if (isset($_GET['deleted']))  { $toasts[] = ['text' => 'Court deleted (or deactivated) successfully.', 'type' => 'pending']; }
    if (isset($_GET['error']) && $_GET['error'] === 'duplicate') {
        $toasts[] = ['text' => 'Court name already exists!', 'type' => 'error'];
    }

    // Database Connection
    $conn = mysqli_connect("localhost", "root", "", "badminton_hub");
    require_once __DIR__ . '/../log_activity.php';

    // Take session user information
    $username     = $_SESSION['username'];
    $role         = $_SESSION['role'];
    $display_name = $username;

    // This page sits at Admin_Module root, so navbar links don't need a prefix.
    $base_path = '../';

    // Handle add from modal
    if(isset($_POST['save_court'])){
        $court_name     = mysqli_real_escape_string($conn, $_POST['court_name']);
        $court_type     = mysqli_real_escape_string($conn, $_POST['court_type']);
        $location       = mysqli_real_escape_string($conn, $_POST['location']);
        $facilities     = mysqli_real_escape_string($conn, $_POST['facilities']);
        $price_off_peak = mysqli_real_escape_string($conn, $_POST['price_off_peak']);
        $price_peak     = mysqli_real_escape_string($conn, $_POST['price_peak']);
        $is_active      = isset($_POST['is_active']) ? 1 : 0;

        $check = mysqli_query($conn, "SELECT id FROM courts WHERE court_name = '$court_name'");
        if(mysqli_num_rows($check) > 0){
            header("Location: ManageCourts.php?error=duplicate");
            exit();
        }

        mysqli_query($conn, "INSERT INTO courts (court_name, court_type, location, facilities, price_off_peak, price_peak, is_active)
                VALUES ('$court_name', '$court_type', '$location', '$facilities', '$price_off_peak', '$price_peak', '$is_active')");
        $new_court_id = mysqli_insert_id($conn);
        for ($day = 1; $day <= 7; $day++) {
            mysqli_query($conn, "INSERT INTO court_availability (court_id, day_of_week, start_time, end_time) VALUES ('$new_court_id', '$day', '08:00:00', '01:00:00')");
        }

        // Save photos picked in the Add Court modal, using the naming the customer pages read
        $base_name = strtolower(str_replace(' ', '_', $_POST['court_name']));
        $photo_dir = __DIR__ . '/../../Pictures/Admin_Module/courts/';
        foreach (['main', '1', '2', '3', '4', '5'] as $slot) {
            $field = 'photo_' . $slot;
            if (!isset($_FILES[$field]) || $_FILES[$field]['error'] !== UPLOAD_ERR_OK) continue;
            $info = getimagesize($_FILES[$field]['tmp_name']);
            if ($info === false || !in_array($info[2], [IMAGETYPE_JPEG, IMAGETYPE_PNG])) continue;
            $stem = ($slot === 'main') ? $base_name : $base_name . '_' . $slot;
            if (move_uploaded_file($_FILES[$field]['tmp_name'], $photo_dir . $stem . '.jpg') && $slot === 'main') {
                $safe_img = mysqli_real_escape_string($conn, $stem . '.jpg');
                mysqli_query($conn, "UPDATE courts SET court_image = '$safe_img' WHERE id = $new_court_id");
            }
        }

        logActivity($conn, 'Create', 'Court Management', "Added new court: $court_name");
        header("Location: ManageCourts.php?success=1");
        exit();
    }

    // Handle delete from modal
    if(isset($_POST['delete_court'])){
        $del_id        = intval($_POST['court_id_delete']);
        $del_row       = mysqli_fetch_assoc(mysqli_query($conn, "SELECT court_name FROM courts WHERE id = $del_id"));
        $del_label     = $del_row['court_name'] ?? "ID $del_id";
        $booking_check = mysqli_query($conn, "SELECT id FROM bookings WHERE court_id = $del_id LIMIT 1");
        if($booking_check && mysqli_num_rows($booking_check) > 0){
            mysqli_query($conn, "UPDATE courts SET is_active = 0 WHERE id = $del_id");
            logActivity($conn, 'Delete', 'Court Management', "Deactivated court (has bookings): $del_label");
        } else {
            mysqli_query($conn, "DELETE FROM courts WHERE id = $del_id");
            logActivity($conn, 'Delete', 'Court Management', "Deleted court: $del_label");
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
        logActivity($conn, 'Update', 'Court Management', "Updated court: $court_name (ID $court_id)");
        header("Location: ManageCourts.php?success=1");
        exit();
    }

    function courtSortLink($label, $col, $current_sort, $current_dir, $next_dir, $filter_type, $filter_status, $filter_search) {
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
    $params = http_build_query([
        'sort'   => $col,
        'dir'    => $dir,
        'type'   => $filter_type,
        'status' => $filter_status,
        'search' => $filter_search,
    ]);
        return "<a href='ManageCourts.php?$params' class='sort-link'>$label$arrow</a>";
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

    // Sort handling
    $allowed_sorts = ['id', 'court_name', 'court_type', 'is_active'];
    $sort_col = isset($_GET['sort']) && in_array($_GET['sort'], $allowed_sorts) ? $_GET['sort'] : 'id';
    $sort_dir = isset($_GET['dir']) && $_GET['dir'] === 'asc' ? 'ASC' : 'DESC';
    $next_dir = ($sort_dir === 'ASC') ? 'desc' : 'asc';

    // Pagination
    $per_page    = 15;
    $page        = max(1, (int)($_GET['page'] ?? 1));

    $count_res   = mysqli_query($conn, "SELECT COUNT(*) AS cnt FROM courts $where_sql");
    $total_rows  = (int)mysqli_fetch_assoc($count_res)['cnt'];
    $total_pages = max(1, (int)ceil($total_rows / $per_page));
    $page        = min($page, $total_pages);
    $offset      = ($page - 1) * $per_page;

    // Fetch court data from database
    $result = mysqli_query($conn, "SELECT * FROM courts $where_sql ORDER BY $sort_col $sort_dir LIMIT $per_page OFFSET $offset");

    function courtPageQS($p, $sort, $dir, $filter_type, $filter_status, $filter_search) {
        $params = ['page' => $p, 'sort' => $sort, 'dir' => $dir];
        if ($filter_type   !== '') $params['type']   = $filter_type;
        if ($filter_status !== '') $params['status'] = $filter_status;
        if ($filter_search !== '') $params['search'] = $filter_search;
        return http_build_query($params);
    }

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Smash Arena - Court Management</title>

    <!-- Font Awesome CDN -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <!-- Google Fonts CDN -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800;900&display=swap">

    <!-- Connect previous CSS -->
    <link rel="stylesheet" href="../Dashboard/Dashboard.css">
    <link rel="stylesheet" href="../Superadmin/AdminManagement.css">
    <link rel="stylesheet" href="ManageCourts.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.2/cropper.min.css">
    <style>
        .modal-photos {
            margin-top: 4px;
            padding-top: 14px;
            border-top: 1px solid var(--border);
        }
        .modal-photos .photos-title {
            font-size: 12.5px;
            font-weight: 700;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 10px;
        }
        .photos-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 10px;
        }
        .photo-slot {
            position: relative;
            border: 1.5px dashed var(--border);
            border-radius: 10px;
            aspect-ratio: 16 / 9;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 3px;
            cursor: pointer;
            color: var(--text-muted);
            font-size: 11px;
            font-weight: 600;
            background: #f8fafc;
            transition: border-color 0.25s, background 0.25s;
        }
        .photo-slot:hover {
            border-color: var(--primary);
            background: #fffbeb;
        }
        .photo-slot i { font-size: 15px; }
        .photo-slot img {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .photo-slot .slot-tag {
            position: absolute;
            top: 5px;
            left: 5px;
            background: rgba(17,24,39,0.75);
            color: #fff;
            font-size: 9px;
            font-weight: 700;
            padding: 1px 7px;
            border-radius: 20px;
            letter-spacing: 0.4px;
            z-index: 2;
        }
        .photo-slot .slot-tag.tag-main { background: var(--primary); }
        .photo-slot .slot-delete {
            position: absolute;
            top: 4px;
            right: 4px;
            width: 22px;
            height: 22px;
            border: none;
            border-radius: 50%;
            background: rgba(239,68,68,0.92);
            color: #fff;
            font-size: 10px;
            cursor: pointer;
            z-index: 2;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: transform 0.2s;
        }
        .photo-slot .slot-delete:hover { transform: scale(1.1); }
        .photo-slot.slot-more {
            border-style: solid;
            background: #fffbeb;
            border-color: var(--primary);
            color: var(--primary);
        }
        .photo-slot.slot-more:hover { background: #fef3c7; }
        .more-grid { grid-template-columns: repeat(2, 1fr); }

        .crop-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(17,24,39,0.6);
            z-index: 3000;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .crop-overlay.active { display: flex; }
        .crop-card {
            background: #fff;
            border-radius: 14px;
            width: 100%;
            max-width: 900px;
            overflow: hidden;
        }
        .crop-card .crop-head {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 14px 20px;
            border-bottom: 1px solid var(--border);
        }
        .crop-card .crop-head h3 {
            font-size: 15px;
            font-weight: 700;
            color: #1f2937;
        }
        .crop-card .crop-close {
            border: none;
            background: none;
            font-size: 20px;
            color: var(--text-muted);
            cursor: pointer;
        }
        .crop-body {
            padding: 16px 20px;
        }
        .crop-body img {
            display: block;
            width: 100%;
            height: 460px;
        }
        .crop-actions {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            padding: 14px 20px;
            border-top: 1px solid var(--border);
        }
        .crop-actions .btn-crop-cancel {
            border: 1.5px solid var(--border);
            background: #fff;
            color: #374151;
            padding: 8px 18px;
            border-radius: 9px;
            font-family: 'Outfit', sans-serif;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
        }
        .crop-actions .btn-crop-save {
            border: none;
            background: var(--primary);
            color: #fff;
            padding: 8px 18px;
            border-radius: 9px;
            font-family: 'Outfit', sans-serif;
            font-size: 13px;
            font-weight: 700;
            cursor: pointer;
        }
        .crop-actions .btn-crop-save:disabled { opacity: 0.6; cursor: wait; }
    </style>
</head>
<body>
    
    <!-- Nav Bar -->
    <?php include '../navbar.php'; ?>
    
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
                    </button>
                    <button type="button" class="btn-add-account" onclick="openAddCourtModal()">
                        <i class="fas fa-plus"></i> Add Court
                    </button>
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


            <!-- Court Table -->
            <table class="data-table">
                <thead>
                    <tr>
                        <th><?php echo courtSortLink('ID', 'id', $sort_col, $sort_dir, $next_dir, $filter_type, $filter_status, $filter_search); ?></th>
                        <th><?php echo courtSortLink('Court Info', 'court_name', $sort_col, $sort_dir, $next_dir, $filter_type, $filter_status, $filter_search); ?></th>
                        <th><?php echo courtSortLink('Type', 'court_type', $sort_col, $sort_dir, $next_dir, $filter_type, $filter_status, $filter_search); ?></th>
                        <th>Pricing</th>
                        <th><?php echo courtSortLink('Status', 'is_active', $sort_col, $sort_dir, $next_dir, $filter_type, $filter_status, $filter_search); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($row = mysqli_fetch_assoc($result)): ?>

                    <!-- Main row — click to open edit modal -->
                    <tr class="main-row" data-court-id="<?php echo $row['id']; ?>" onclick="openCourtModal(
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

            <?php if ($total_pages > 1): ?>
            <div class="log-pagination">
                <?php if ($page > 1): ?>
                    <a href="?<?php echo courtPageQS($page - 1, $sort_col, strtolower($sort_dir), $filter_type, $filter_status, $filter_search); ?>" class="page-btn">
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
                    <?php if ($filter_type   !== '') echo '<input type="hidden" name="type" value="'   . htmlspecialchars($filter_type)   . '">'; ?>
                    <?php if ($filter_status !== '') echo '<input type="hidden" name="status" value="' . htmlspecialchars($filter_status) . '">'; ?>
                    <?php if ($filter_search !== '') echo '<input type="hidden" name="search" value="' . htmlspecialchars($filter_search) . '">'; ?>
                </form>

                <?php if ($page < $total_pages): ?>
                    <a href="?<?php echo courtPageQS($page + 1, $sort_col, strtolower($sort_dir), $filter_type, $filter_status, $filter_search); ?>" class="page-btn">
                        <i class="fas fa-chevron-right"></i>
                    </a>
                <?php else: ?>
                    <span class="page-btn disabled"><i class="fas fa-chevron-right"></i></span>
                <?php endif; ?>
            </div>
            <?php endif; ?>

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

                    <div class="modal-field full-width modal-photos">
                        <div class="photos-title"><i class="fas fa-images" style="color:var(--primary); margin-right:5px;"></i>Court Photos</div>
                        <div class="photos-grid" id="courtPhotosGrid"></div>
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

    <!-- Add Court Modal -->
    <div class="modal-overlay" id="addCourtModal">
        <div class="modal-card">

            <div class="modal-header">
                <h2><i class="fas fa-plus"></i> Add New Court</h2>
                <button class="modal-close" type="button" onclick="closeAddCourtModal()">&times;</button>
            </div>

            <form action="ManageCourts.php" method="POST" enctype="multipart/form-data">
                <div class="modal-grid">

                    <div class="modal-field">
                        <label>Court Name</label>
                        <input type="text" name="court_name" placeholder="e.g. Court A" required>
                    </div>

                    <div class="modal-field">
                        <label>Court Type</label>
                        <select name="court_type" required>
                            <option value="" disabled selected>Select Type</option>
                            <option value="Standard">Standard</option>
                            <option value="Training">Training</option>
                        </select>
                    </div>

                    <div class="modal-field">
                        <label>Location</label>
                        <input type="text" name="location" placeholder="e.g. Main Hall 1">
                    </div>

                    <div class="modal-field">
                        <label>Facilities</label>
                        <input type="text" name="facilities" placeholder="e.g. Shower, Locker">
                    </div>

                    <div class="modal-field">
                        <label>Off-Peak Price (RM)</label>
                        <input type="number" name="price_off_peak" value="10.00" step="0.01" min="0" required>
                    </div>

                    <div class="modal-field">
                        <label>Peak Price (RM)</label>
                        <input type="number" name="price_peak" value="15.00" step="0.01" min="0" required>
                    </div>

                    <div class="modal-field full-width">
                        <label class="checkbox-label">
                            <input type="checkbox" name="is_active" checked> Active
                        </label>
                    </div>

                    <div class="modal-field full-width modal-photos">
                        <div class="photos-title"><i class="fas fa-images" style="color:var(--primary); margin-right:5px;"></i>Court Photos</div>
                        <div class="photos-grid" id="addPhotosGrid"></div>
                        <input type="file" name="photo_main" id="addPhotoFile-main" hidden>
                        <input type="file" name="photo_1" id="addPhotoFile-1" hidden>
                        <input type="file" name="photo_2" id="addPhotoFile-2" hidden>
                        <input type="file" name="photo_3" id="addPhotoFile-3" hidden>
                        <input type="file" name="photo_4" id="addPhotoFile-4" hidden>
                        <input type="file" name="photo_5" id="addPhotoFile-5" hidden>
                    </div>

                </div>

                <div class="modal-actions">
                    <div></div>
                    <div style="display:flex; gap:10px;">
                        <button type="button" class="btn-modal-cancel" onclick="closeAddCourtModal()">Cancel</button>
                        <button type="submit" name="save_court" class="btn-modal-save">
                            <i class="fas fa-save"></i> Save Court
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- More gallery photos modal -->
    <div class="crop-overlay" id="morePhotosModal">
        <div class="crop-card">
            <div class="crop-head">
                <h3><i class="fas fa-images" style="color:var(--primary); margin-right:6px;"></i>More Gallery Photos</h3>
                <button type="button" class="crop-close" onclick="closeMorePhotos()">&times;</button>
            </div>
            <div class="crop-body">
                <div class="photos-grid more-grid" id="morePhotosGrid"></div>
            </div>
            <div class="crop-actions">
                <button type="button" class="btn-crop-cancel" onclick="closeMorePhotos()">Close</button>
            </div>
        </div>
    </div>

    <!-- Court photo crop modal -->
    <div class="crop-overlay" id="cropOverlay">
        <div class="crop-card">
            <div class="crop-head">
                <h3><i class="fas fa-crop-alt" style="color:var(--primary); margin-right:6px;"></i>Crop Photo</h3>
                <button type="button" class="crop-close" onclick="closeCrop()">&times;</button>
            </div>
            <div class="crop-body">
                <img id="cropImage" src="" alt="Crop preview">
            </div>
            <div class="crop-actions">
                <button type="button" class="btn-crop-cancel" onclick="closeCrop()">Cancel</button>
                <button type="button" class="btn-crop-save" id="cropSaveBtn" onclick="saveCrop()">
                    <i class="fas fa-check"></i> Crop & Upload
                </button>
            </div>
        </div>
    </div>
    <input type="file" id="photoInput" accept="image/png, image/jpeg" style="display:none;">

    <script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.2/cropper.min.js"></script>
    <script src="ManageCourts.js"></script>
    <script src="../Dashboard/Dashboard.js"></script>

    <!-- Modal styling -->
    <?php include __DIR__ . '/../modal.php'; ?>

    <!-- Scroll-to-top -->
    <?php include __DIR__ . '/../scroll_top.php'; ?>

    <!-- Toast notifications -->
    <?php include __DIR__ . '/../toast/toast.php'; ?>
</body>
</html>
