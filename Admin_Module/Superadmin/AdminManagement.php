<?php
// Admin Management page — ONLY accessible to Superadmin.
// Lives at Admin_Module/Superadmin/AdminManagement.php
// Lets the Superadmin create / suspend / delete other admin accounts
// and emails the temporary password to the new admin.

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// PHPMailer lives in Admin_Module/Email_System (one folder up from here).
require '../Email_System/phpmailer/src/Exception.php';
require '../Email_System/phpmailer/src/PHPMailer.php';
require '../Email_System/phpmailer/src/SMTP.php';

session_start();
require_once __DIR__ . '/../toast/toast_init.php';

// Security check - If no session, redirect immediately
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'Superadmin') {
    header("Location: ../LoginPage.php");
    exit();
}

$username     = $_SESSION['username'];
$role         = $_SESSION['role'];
$display_name = $username;

// This page lives inside Admin_Module/Superadmin/, so navbar links need to step up one level.
$base_path = '../';

// Prevent browser caching
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

// Database connection
$conn = mysqli_connect("localhost", "root", "", "badminton_hub");
require_once __DIR__ . '/../log_activity.php';

// PHPMailer function
function sendTemporaryPassword($to_email, $username, $temp_pass) {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'smasharenabadminton@gmail.com';
        $mail->Password   = 'hgrk ocze fowx rbrd';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        $mail->setFrom('smasharenabadminton@gmail.com', 'Smash Arena');
        $mail->addAddress($to_email);

        $mail->isHTML(true);
        $mail->CharSet = 'UTF-8';
        $mail->Subject = 'Smash Arena Credentials';
        $mail->Body    = "
        <div style='font-family: Arial, sans-serif; background-color: #f4f4f4; padding: 30px;'>
            <div style='max-width: 520px; margin: 0 auto; background: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 20px rgba(0,0,0,0.08);'>
                <div style='background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%); padding: 32px; text-align: center;'>
                    <p style='margin:0; font-size:32px;'>&#127992;</p>
                    <h1 style='margin: 10px 0 4px; color: #f59e0b; font-size: 22px;'>Smash Arena</h1>
                    <p style='margin:0; color: rgba(255,255,255,0.6); font-size: 13px;'>Admin Portal</p>
                </div>
                <div style='padding: 36px 32px;'>
                    <h2 style='margin: 0 0 8px; color: #0f172a; font-size: 20px;'>Welcome, $username!</h2>
                    <p style='color: #64748b; font-size: 15px; line-height: 1.6; margin: 0 0 24px;'>Your admin account has been created on Smash Arena. Use the temporary credentials below to log in for the first time.</p>
                    <div style='background: #fffbeb; border: 1.5px solid #fde68a; border-radius: 12px; padding: 20px 24px; margin-bottom: 24px;'>
                        <p style='margin: 0 0 8px; font-size: 12px; font-weight: 700; color: #92400e; text-transform: uppercase; letter-spacing: 0.5px;'>Your Temporary Password</p>
                        <p style='margin: 0; font-size: 24px; font-weight: 800; color: #0f172a; letter-spacing: 3px; font-family: monospace;'>$temp_pass</p>
                    </div>
                    <div style='background: #fef2f2; border-left: 4px solid #ef4444; border-radius: 8px; padding: 14px 18px; margin-bottom: 28px;'>
                        <p style='margin: 0; font-size: 13px; color: #991b1b;'>&#9888;&#65039; You will be required to change this password on your first login. Please keep these credentials private.</p>
                    </div>
                    <a href='http://localhost/fyp/Admin_Module/LoginPage.php' style='display: block; text-align: center; padding: 14px; background: linear-gradient(135deg, #f59e0b, #d97706); color: #0f172a; text-decoration: none; border-radius: 50px; font-weight: 800; font-size: 15px;'>Login to Admin Portal</a>
                </div>
                <div style='background: #f8fafc; padding: 20px 32px; text-align: center; border-top: 1px solid #e2e8f0;'>
                    <p style='margin: 0; font-size: 12px; color: #94a3b8;'>This email was sent by Smash Arena Admin System. If you did not expect this, please contact your system administrator.</p>
                </div>
            </div>
        </div>";

        $mail->send();
        return true;
    } catch (Exception $e) {
        return false;
    }
}

// Handle account creation
if (isset($_POST['add_account'])) {
    $user        = mysqli_real_escape_string($conn, $_POST['username']);
    $email       = mysqli_real_escape_string($conn, $_POST['email']);

    $temp_pass   = bin2hex(random_bytes(4));
    $hashed_pass = password_hash($temp_pass, PASSWORD_DEFAULT);

    $check = mysqli_query($conn, "SELECT username, email FROM admins WHERE username = '$user' OR email = '$email'");
    if (mysqli_num_rows($check) > 0) {
        $found = mysqli_fetch_assoc($check);
        if ($found['username'] === $user) {
            $toasts[] = ['text' => 'Username already exists!', 'type' => 'error'];
        } else {
            $toasts[] = ['text' => 'Email address already exists!', 'type' => 'error'];
        }
    } else {
        $sql = "INSERT INTO admins (username, email, password, role, status, is_coach, coach_price_per_hour)
                VALUES ('$user', '$email', '$hashed_pass', 'Admin', 'Inactive', 0, NULL)";

        if (mysqli_query($conn, $sql)) {
            logActivity($conn, 'Create', 'Admin Management', "Created Admin account: $user ($email)");
            $mail_sent = sendTemporaryPassword($email, $user, $temp_pass);
            if ($mail_sent) {
                $toasts[] = ['text' => 'Account created & email sent.', 'type' => 'success'];
            } else {
                $toasts[] = ['text' => 'Account created (email skipped).', 'type' => 'success'];
            }
        } else {
            $toasts[] = ['text' => 'Database error: ' . mysqli_error($conn), 'type' => 'error'];
        }
    }
}

// Handle edit admin (username + email)
if (isset($_POST['update_admin'])) {
    $aid       = intval($_POST['admin_id']);
    $new_user  = mysqli_real_escape_string($conn, $_POST['username']);
    $new_email = mysqli_real_escape_string($conn, $_POST['email']);

    // Load the target account to enforce permission rules.
    $target = mysqli_fetch_assoc(mysqli_query($conn, "SELECT username, role FROM admins WHERE id = $aid"));

    if (!$target) {
        $toasts[] = ['text' => 'Account not found!', 'type' => 'error'];
    } elseif ($target['role'] === 'Superadmin' && $target['username'] !== $username) {
        // A Superadmin account can only be edited by that Superadmin himself.
        $toasts[] = ['text' => 'You can only edit your own Superadmin account!', 'type' => 'error'];
    } else {
        // Make sure the new username / email isn't used by a different account.
        $dupe = mysqli_query($conn, "SELECT username, email FROM admins WHERE (username = '$new_user' OR email = '$new_email') AND id != $aid");
        if (mysqli_num_rows($dupe) > 0) {
            $found = mysqli_fetch_assoc($dupe);
            if ($found['username'] === $new_user) {
                $toasts[] = ['text' => 'Username already exists!', 'type' => 'error'];
            } else {
                $toasts[] = ['text' => 'Email address already exists!', 'type' => 'error'];
            }
        } else {
            if ($target['role'] === 'Superadmin') {
                // A Superadmin can only update his own username + email (role/status stay fixed).
                mysqli_query($conn, "UPDATE admins SET username = '$new_user', email = '$new_email' WHERE id = $aid");
            } else {
                // Admin accounts: role and status are editable too.
                $new_role   = in_array($_POST['role'],   ['Admin', 'Superadmin'])           ? $_POST['role']   : 'Admin';
                $new_status = in_array($_POST['status'], ['Active', 'Inactive', 'Suspended']) ? $_POST['status'] : 'Inactive';
                mysqli_query($conn, "UPDATE admins SET username = '$new_user', email = '$new_email', role = '$new_role', status = '$new_status' WHERE id = $aid");
            }

            // If a coach account shares this admin, keep its display name in sync.
            mysqli_query($conn, "UPDATE coaches SET name = '$new_user' WHERE admin_id = $aid");

            // If the Superadmin edited his own account, refresh the session name.
            if ($target['username'] === $username) {
                $_SESSION['username'] = $new_user;
                $username = $new_user;
                $display_name = $new_user;
            }

            logActivity($conn, 'Update', 'Admin Management', "Updated account: $new_user ($new_email)");
            $toasts[] = ['text' => 'Account updated successfully!', 'type' => 'success'];
        }
    }
}

// Handle toggle status
if (isset($_GET['update_id']) && isset($_GET['new_status'])) {
    $uid    = intval($_GET['update_id']);
    $status = mysqli_real_escape_string($conn, $_GET['new_status']);
    $target = mysqli_fetch_assoc(mysqli_query($conn, "SELECT username FROM admins WHERE id = $uid"));
    mysqli_query($conn, "UPDATE admins SET status = '$status' WHERE id = $uid");
    logActivity($conn, 'Status Change', 'Admin Management',
                "Set admin '" . ($target['username'] ?? "ID $uid") . "' status to $status");
    header("Location: AdminManagement.php");
    exit();
}

// Handle delete
if (isset($_GET['delete_id'])) {
    $did    = intval($_GET['delete_id']);
    $target = mysqli_fetch_assoc(mysqli_query($conn, "SELECT username FROM admins WHERE id = $did"));
    mysqli_query($conn, "DELETE FROM admins WHERE id = $did AND role != 'Superadmin'");
    logActivity($conn, 'Delete', 'Admin Management',
                "Deleted Admin account: '" . ($target['username'] ?? "ID $did") . "'");
    header("Location: AdminManagement.php");
    exit();
}

// Filter values from GET
$filter_role   = isset($_GET['filter']) ? $_GET['filter'] : 'All';
$filter_status = isset($_GET['status']) ? $_GET['status'] : '';
$filter_search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';

$has_filter = ($filter_role !== 'All' || $filter_status !== '' || $filter_search !== '');

function sortLink($label, $col, $current_sort, $current_dir, $next_dir, $filter_role, $filter_status, $filter_search) {
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
        'filter' => $filter_role,
        'status' => $filter_status,
        'search' => $filter_search,
    ]);
    return "<a href='AdminManagement.php?$params' class='sort-link'>$label$arrow</a>";
}

// Build query
$where_parts = ["role != 'Coach'"];
if ($filter_role === 'Superadmin') $where_parts[] = "role = 'Superadmin'";
elseif ($filter_role === 'Admin')  $where_parts[] = "role = 'Admin'";
if ($filter_status !== '')         $where_parts[] = "status = '$filter_status'";
if ($filter_search !== '')         $where_parts[] = "(username LIKE '%$filter_search%' OR email LIKE '%$filter_search%')";

// Sort handling
$allowed_sorts = ['id', 'username', 'role', 'created_at', 'status'];
$sort_col = isset($_GET['sort']) && in_array($_GET['sort'], $allowed_sorts) ? $_GET['sort'] : 'id';
$sort_dir = isset($_GET['dir']) && $_GET['dir'] === 'asc' ? 'ASC' : 'DESC';
$next_dir = ($sort_dir === 'ASC') ? 'desc' : 'asc';

$query = "SELECT * FROM admins WHERE " . implode(" AND ", $where_parts) . " ORDER BY $sort_col $sort_dir";

$result = mysqli_query($conn, $query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Smash Arena - Admin Management</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800;900&display=swap">
    <link rel="stylesheet" href="../Dashboard/Dashboard.css">
    <link rel="stylesheet" href="AdminManagement.css">
    <link rel="stylesheet" href="../Courts_Management/ManageCourts.css">
</head>
<body>

    <?php include '../navbar.php'; ?>

    <main class="content">
        <div class="manage-container">

            <header class="management-header">
                <div>
                    <h1>Admin Management</h1>
                    <p>Manage admin accounts and access permissions.</p>
                </div>
                <div class="btn-add-group">
                    <button class="btn-filter-toggle" onclick="toggleFilter()">
                        <i class="fas fa-filter"></i> Filter
                        <?php if($has_filter): ?>
                            <span class="filter-dot"></span>
                        <?php endif; ?>
                    </button>
                    <button class="btn-add-account" onclick="toggleForm('adminForm')">
                        <i class="fas fa-plus"></i> Add Admin
                    </button>
                </div>
            </header>

            <!-- Add Admin Form -->
            <div id="adminForm" class="form-card">
                <h3>Create New Admin</h3>
                <form method="POST" class="form-grid">
                    <input type="hidden" name="role" value="Admin">
                    <input type="text" name="username" placeholder="Username" required>
                    <input type="email" name="email" placeholder="Email" required>
                    <button type="submit" name="add_account" class="btn-create">Create Account</button>
                </form>
            </div>

            <!-- Collapsible Filter Panel -->
            <div class="filter-panel <?php echo $has_filter ? 'open' : ''; ?>" id="filterPanel">
                <form method="GET" class="filter-grid">
                    <div class="filter-field">
                        <label>Search</label>
                        <input type="text" name="search" placeholder="Username or email..." value="<?php echo htmlspecialchars($filter_search); ?>">
                    </div>
                    <div class="filter-field">
                        <label>Role</label>
                        <select name="filter">
                            <option value="All"        <?php echo ($filter_role === 'All'        ? 'selected' : ''); ?>>All Staff</option>
                            <option value="Superadmin" <?php echo ($filter_role === 'Superadmin' ? 'selected' : ''); ?>>Superadmin</option>
                            <option value="Admin"      <?php echo ($filter_role === 'Admin'      ? 'selected' : ''); ?>>Admin</option>
                        </select>
                    </div>
                    <div class="filter-field">
                        <label>Status</label>
                        <select name="status">
                            <option value=""          <?php echo ($filter_status === ''          ? 'selected' : ''); ?>>All Status</option>
                            <option value="Active"    <?php echo ($filter_status === 'Active'    ? 'selected' : ''); ?>>Active</option>
                            <option value="Inactive"  <?php echo ($filter_status === 'Inactive'  ? 'selected' : ''); ?>>Inactive</option>
                            <option value="Suspended" <?php echo ($filter_status === 'Suspended' ? 'selected' : ''); ?>>Suspended</option>
                        </select>
                    </div>
                    <div class="filter-actions">
                        <button type="submit" class="btn-filter-apply"><i class="fas fa-search"></i> Apply</button>
                        <a href="AdminManagement.php" class="btn-filter-clear">Clear</a>
                    </div>
                </form>
            </div>

            <table class="data-table">
                <thead>
                    <tr>
                        <th><?php echo sortLink('ID', 'id', $sort_col, $sort_dir, $next_dir, $filter_role, $filter_status, $filter_search); ?></th>
                        <th><?php echo sortLink('Staff Info', 'username', $sort_col, $sort_dir, $next_dir, $filter_role, $filter_status, $filter_search); ?></th>
                        <th style="text-align:center;"><?php echo sortLink('Role', 'role', $sort_col, $sort_dir, $next_dir, $filter_role, $filter_status, $filter_search); ?></th>
                        <th style="text-align:center;"><?php echo sortLink('Created', 'created_at', $sort_col, $sort_dir, $next_dir, $filter_role, $filter_status, $filter_search); ?></th>
                        <th style="text-align:center;"><?php echo sortLink('Account Status', 'status', $sort_col, $sort_dir, $next_dir, $filter_role, $filter_status, $filter_search); ?></th>
                        <th style="text-align:center;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($row = mysqli_fetch_assoc($result)):
                        // Admins are always editable; a Superadmin row is only editable by himself.
                        $can_edit = ($row['role'] !== 'Superadmin') || ($row['username'] === $username);
                    ?>
                    <tr <?php if ($can_edit): ?>onclick="openAdminEditModal(<?php echo $row['id']; ?>, '<?php echo addslashes($row['username']); ?>', '<?php echo addslashes($row['email']); ?>', '<?php echo $row['role']; ?>', '<?php echo $row['status']; ?>')" style="cursor:pointer;"<?php endif; ?>>
                        <td>#<?php echo $row['id']; ?></td>
                        <td>
                            <strong><?php echo htmlspecialchars($row['username']); ?></strong><br>
                            <small style="color:#999;"><?php echo htmlspecialchars($row['email']); ?></small>
                        </td>

                        <td style="text-align:center;">
                            <span class="role-label"><?php echo $row['role']; ?></span>
                        </td>

                        <td style="text-align:center; font-size:12px; color:var(--text-muted);">
                            <?php echo date('d M Y', strtotime($row['created_at'])); ?>
                        </td>

                        <td style="text-align:center;">
                            <?php if ($row['role'] !== 'Superadmin'): ?>
                            <span class="status-pill <?php
                                if($row['status'] == 'Active')       echo 'status-active';
                                elseif($row['status'] == 'Inactive') echo 'status-inactive';
                                else                                  echo 'status-suspended';
                            ?>"><?php echo $row['status']; ?></span>
                            <?php else: ?>
                                <span class="status-master">MASTER</span>
                            <?php endif; ?>
                        </td>

                        <td style="text-align:center;" onclick="event.stopPropagation()">
                            <?php if ($row['role'] !== 'Superadmin'): ?>
                            <a href="?delete_id=<?php echo $row['id']; ?>"
                                onclick="return confirm('Delete permanently?')">
                                <i class="fas fa-trash-alt delete-icon"></i>
                            </a>
                            <?php else: ?>
                                <i class="fas fa-lock" style="color:#ccc;"></i>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>

        </div>
    </main>

    <!-- Edit Admin Modal -->
    <div class="modal-overlay" id="adminEditModal">
        <div class="modal-card">
            <div class="modal-header">
                <h2><i class="fas fa-pen"></i> Edit Account</h2>
                <button class="modal-close" type="button" onclick="closeAdminEditModal()">&times;</button>
            </div>

            <form method="POST">
                <input type="hidden" name="admin_id" id="admin-modal-id">

                <div class="modal-grid">
                    <div class="modal-field full-width">
                        <label>Username</label>
                        <input type="text" name="username" id="admin-modal-username" required>
                    </div>

                    <div class="modal-field full-width">
                        <label>Email</label>
                        <input type="email" name="email" id="admin-modal-email" required>
                    </div>

                    <!-- Role + Status only apply to Admin accounts; hidden for a Superadmin's own row -->
                    <div class="modal-field" id="admin-modal-role-wrap">
                        <label>Role</label>
                        <select name="role" id="admin-modal-role">
                            <option value="Admin">Admin</option>
                            <option value="Superadmin">Superadmin</option>
                        </select>
                    </div>

                    <div class="modal-field" id="admin-modal-status-wrap">
                        <label>Status</label>
                        <select name="status" id="admin-modal-status">
                            <option value="Active">Active</option>
                            <option value="Inactive">Inactive</option>
                            <option value="Suspended">Suspended</option>
                        </select>
                    </div>
                </div>

                <div class="modal-actions">
                    <button type="button" class="btn-modal-cancel" onclick="closeAdminEditModal()">Cancel</button>
                    <button type="submit" name="update_admin" class="btn-modal-save">Save Changes</button>
                </div>
            </form>
        </div>
    </div>

    <script src="../Dashboard/Dashboard.js"></script>
    <script src="AdminManagement.js"></script>

    <!-- Modal styling -->
    <?php include __DIR__ . '/../modal.php'; ?>

    <!-- Scroll-to-top -->
    <?php include __DIR__ . '/../scroll_top.php'; ?>

    <!-- Toast notifications -->
    <?php include __DIR__ . '/../toast/toast.php'; ?>
</body>
</html>
