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

    use PHPMailer\PHPMailer\PHPMailer;
    use PHPMailer\PHPMailer\Exception;

    require 'Email_System/phpmailer/src/Exception.php';
    require 'Email_System/phpmailer/src/PHPMailer.php';
    require 'Email_System/phpmailer/src/SMTP.php';

    //Database connection
    $conn = mysqli_connect("localhost", "root", "", "badminton_hub");

    $username     = $_SESSION['username'];
    $role         = $_SESSION['role'];
    $display_name = $username;

    // This page sits at Admin_Module root, so navbar links don't need a prefix.
    $base_path = '../';

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

            $mail->setFrom('smasharenabadminton@gmail.com', 'Badminton Hub');
            $mail->addAddress($to_email);

            $mail->isHTML(true);
            $mail->CharSet = 'UTF-8';
            $mail->Subject = 'Badminton Hub Credentials';
            $mail->Body    = "
            <div style='font-family: Arial, sans-serif; background-color: #f4f4f4; padding: 30px;'>
                <div style='max-width: 520px; margin: 0 auto; background: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 20px rgba(0,0,0,0.08);'>
                    <div style='background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%); padding: 32px; text-align: center;'>
                        <p style='margin:0; font-size:32px;'>&#127992;</p>
                        <h1 style='margin: 10px 0 4px; color: #f59e0b; font-size: 22px;'>Badminton Hub</h1>
                        <p style='margin:0; color: rgba(255,255,255,0.6); font-size: 13px;'>Admin Portal</p>
                    </div>
                    <div style='padding: 36px 32px;'>
                        <h2 style='margin: 0 0 8px; color: #0f172a; font-size: 20px;'>Welcome, $username!</h2>
                        <p style='color: #64748b; font-size: 15px; line-height: 1.6; margin: 0 0 24px;'>Your coach account has been created on Badminton Hub. Use the temporary credentials below to log in for the first time.</p>
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
                        <p style='margin: 0; font-size: 12px; color: #94a3b8;'>This email was sent by Badminton Hub Admin System. If you did not expect this, please contact your system administrator.</p>
                    </div>
                </div>
            </div>";

            $mail->send();
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    $message = "";

    //Handle create coach account
    if(isset($_POST['add_account'])){
        $user        = mysqli_real_escape_string($conn, $_POST['username']);
        $email       = mysqli_real_escape_string($conn, $_POST['email']);
        $spec        = mysqli_real_escape_string($conn, $_POST['spec']);
        $gender_add  = mysqli_real_escape_string($conn, $_POST['gender_add']);
        $age_add     = ($_POST['age_add'] !== '') ? intval($_POST['age_add']) : 'NULL';

        $coach_price_raw = $_POST['coach_price_per_hour'];
        $coach_price     = ($coach_price_raw !== '' && (float)$coach_price_raw > 0) ? (float)$coach_price_raw : NULL;
        $coach_price_sql = ($coach_price === NULL) ? "NULL" : $coach_price;

        $temp_pass   = bin2hex(random_bytes(4));
        $hashed_pass = password_hash($temp_pass, PASSWORD_DEFAULT);

        $check = mysqli_query($conn, "SELECT username, email FROM admins WHERE username = '$user' OR email = '$email'");
        if(mysqli_num_rows($check) > 0){
            $found = mysqli_fetch_assoc($check);
            if($found['username'] === $user){
                $message = "<div class='badge pending' style='width:100%; padding:15px; margin-bottom:20px;'>Error: Username already exists!</div>";
            } else {
                $message = "<div class='badge pending' style='width:100%; padding:15px; margin-bottom:20px;'>Error: Email address already exists!</div>";
            }
        } else {
            $sql = "INSERT INTO admins (username, email, password, role, status, specialisation, is_coach, coach_price_per_hour)
                    VALUES ('$user', '$email', '$hashed_pass', 'Coach', 'Inactive', '$spec', 1, $coach_price_sql)";

            if(mysqli_query($conn, $sql)){
                $admin_id    = mysqli_insert_id($conn);
                $age_sql_val = ($age_add === 'NULL') ? 'NULL' : $age_add;

                mysqli_query($conn, "INSERT INTO coaches (admin_id, name, specialty, price_per_hour, gender, age, is_active)
                                     VALUES ('$admin_id', '$user', '$spec', $coach_price_sql, '$gender_add', $age_sql_val, 0)");

                $mail_sent = sendTemporaryPassword($email, $user, $temp_pass);
                if($mail_sent){
                    $message = "<div class='badge success' style='width:100%; padding:15px; margin-bottom:20px;'>Success: Coach Account Created & Email Sent.</div>";
                } else {
                    $message = "<div class='badge success' style='width:100%; padding:15px; margin-bottom:20px;'>Success: Coach Account Created (Email skipped).</div>";
                }
            } else {
                $message = "<div class='badge pending' style='width:100%; padding:15px; margin-bottom:20px;'>Database Error: " . mysqli_error($conn) . "</div>";
            }
        }
    }

    //Handle edit coach
    if(isset($_POST['update_coach'])){
        $coach_id       = intval($_POST['coach_id']);
        $name           = mysqli_real_escape_string($conn, $_POST['coach_name']);
        $specialty      = mysqli_real_escape_string($conn, $_POST['specialty']);
        $phone          = mysqli_real_escape_string($conn, $_POST['phone']);
        $gender         = mysqli_real_escape_string($conn, $_POST['gender']);
        $age = ($_POST['age'] !== '' && $_POST['age'] > 0) ? intval($_POST['age']) : 'NULL';
        $price_per_hour = floatval($_POST['price_per_hour']);

        //Handle cropped image (base64)
        $img_sql = "";
        if(!empty($_POST['cropped_img_data'])){
            $img_data    = $_POST['cropped_img_data'];
            $img_data    = str_replace('data:image/png;base64,', '', $img_data);
            $img_data    = str_replace(' ', '+', $img_data);
            $img_decoded = base64_decode($img_data);
            $img_name    = time() . '_coach.png';
            $upload_path = '../../Pictures/Admin_Module/coaches/' . $img_name;

            if(file_put_contents($upload_path, $img_decoded)){
                $img_sql = ", profile_img = '$img_name'";
            }
        }

        mysqli_query($conn, "
            UPDATE coaches SET
                name           = '$name',
                specialty      = '$specialty',
                phone          = '$phone',
                gender         = '$gender',
                age            = $age,
                price_per_hour = $price_per_hour
                $img_sql
            WHERE id = $coach_id
        ");

        mysqli_query($conn, "
            UPDATE admins SET
                coach_price_per_hour = $price_per_hour,
                specialisation       = '$specialty'
            WHERE id = (SELECT admin_id FROM coaches WHERE id = $coach_id)
        ");

        header("Location: ManageCoaches.php?success=1");
        exit();
    }

    //Handle toggle account status
    if(isset($_GET['toggle_id']) && isset($_GET['status'])){
        $toggle_id    = intval($_GET['toggle_id']);
        $new_status   = intval($_GET['status']);
        $admin_status = ($new_status == 1) ? 'Active' : 'Inactive';

        mysqli_query($conn, "UPDATE coaches SET is_active = $new_status WHERE id = $toggle_id");
        mysqli_query($conn, "UPDATE admins SET status = '$admin_status' WHERE id = (SELECT admin_id FROM coaches WHERE id = $toggle_id)");

        header("Location: ManageCoaches.php?updated=1");
        exit();
    }

    //Handle availability status change
    if(isset($_GET['avail_id']) && isset($_GET['avail_status'])){
        $avail_id     = intval($_GET['avail_id']);
        $avail_status = mysqli_real_escape_string($conn, $_GET['avail_status']);

        mysqli_query($conn, "UPDATE coaches SET availability_status = '$avail_status' WHERE id = $avail_id");

        header("Location: ManageCoaches.php?updated=1");
        exit();
    }

    function coachSortLink($label, $col, $current_sort, $current_dir, $next_dir, $filter_gender, $filter_specialty, $filter_search, $filter_age_min, $filter_age_max) {
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
        'sort'      => $col,
        'dir'       => $dir,
        'gender'    => $filter_gender,
        'specialty' => $filter_specialty,
        'search'    => $filter_search,
        'age_min'   => $filter_age_min,
        'age_max'   => $filter_age_max,
    ]);
        return "<a href='ManageCoaches.php?$params' class='sort-link'>$label$arrow</a>";
    }

    // Filter values from GET
    $filter_gender    = isset($_GET['gender'])    ? $_GET['gender']    : '';
    $filter_specialty = isset($_GET['specialty']) ? $_GET['specialty'] : '';
    $filter_search    = isset($_GET['search'])    ? mysqli_real_escape_string($conn, $_GET['search']) : '';
    $filter_age_min   = isset($_GET['age_min'])   ? intval($_GET['age_min'])   : '';
    $filter_age_max   = isset($_GET['age_max'])   ? intval($_GET['age_max'])   : '';

    $has_filter = ($filter_gender || $filter_specialty || $filter_search || $filter_age_min || $filter_age_max);

    // Sort handling
    $allowed_sorts = ['id', 'name', 'specialty', 'price_per_hour', 'availability_status', 'is_active'];
    $sort_col = isset($_GET['sort']) && in_array($_GET['sort'], $allowed_sorts) ? $_GET['sort'] : 'id';
    $sort_dir = isset($_GET['dir']) && $_GET['dir'] === 'asc' ? 'ASC' : 'DESC';
    $next_dir = ($sort_dir === 'ASC') ? 'desc' : 'asc';

    //Get coach data from database
    $result = mysqli_query($conn, "
        SELECT coaches.id, coaches.name, coaches.specialty,
            coaches.phone, coaches.price_per_hour, coaches.is_active,
            coaches.availability_status, coaches.gender, coaches.age,
            coaches.profile_img, admins.email
        FROM coaches
        JOIN admins ON coaches.admin_id = admins.id
        ORDER BY coaches.$sort_col $sort_dir
    ");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Badminton Hub - Coach Management</title>
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800;900&display=swap">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.css">

    <link rel="stylesheet" href="../Dashboard/Dashboard.css">
    <link rel="stylesheet" href="../Superadmin/AdminManagement.css">
    <link rel="stylesheet" href="ManageCoaches.css">
</head>

<body>

    <?php include '../navbar.php'; ?>

    <main class="content">
        <div class="manage-container">

            <header class="management-header">
                <div>
                    <h1>Coach Management</h1>
                    <p>View and manage coach profiles, specialty and pricing.</p>
                </div>
                <div class="btn-add-group">
                    <button class="btn-filter-toggle" onclick="toggleFilter()">
                        <i class="fas fa-filter"></i> Filter
                    </button>
                    <button class="btn-add-account" onclick="toggleCoachForm()">
                        <i class="fas fa-plus"></i> Add Coach
                    </button>
                </div>
            </header>

            <?php if($message !== "") echo $message; ?>

            <?php if(isset($_GET['success'])): ?>
                <div class="badge success" style="width:100%; padding:15px; margin-bottom:20px;">Coach updated successfully!</div>
            <?php endif; ?>

            <?php if(isset($_GET['updated'])): ?>
                <div class="badge success" style="width:100%; padding:15px; margin-bottom:20px;">Status updated successfully!</div>
            <?php endif; ?>

            <!-- Add Coach Form -->
            <div id="coachForm" class="form-card">
                <h3>Create New Coach</h3>
                <form method="POST" class="form-grid">
                    <input type="text" name="username" placeholder="Coach Name" required>
                    <input type="email" name="email" placeholder="Email" required>
                    <select name="spec" required>
                        <option value="" disabled selected>Select Coaching Specialty</option>
                        <option value="Singles Coaching">Singles Coaching</option>
                        <option value="Doubles Coaching">Doubles Coaching</option>
                        <option value="Fitness & Conditioning">Fitness &amp; Conditioning</option>
                        <option value="Junior Development">Junior Development</option>
                        <option value="Tournament Preparation">Tournament Preparation</option>
                    </select>
                    <input type="number" name="coach_price_per_hour" placeholder="Price Per Hour (RM)" step="0.01" min="0" required>
                    <select name="gender_add">
                        <option value="">-- Gender (Optional) --</option>
                        <option value="Male">Male</option>
                        <option value="Female">Female</option>
                    </select>
                    <input type="number" name="age_add" placeholder="Age (Optional)" min="18" max="80">
                    <button type="submit" name="add_account" class="btn-create">Create Account</button>
                </form>
            </div>

            <!-- Collapsible Filter Panel -->
            <div class="filter-panel <?php echo $has_filter ? 'open' : ''; ?>" id="filterPanel">
                <form method="GET" class="filter-grid">
                    <div class="filter-field">
                        <label>Search</label>
                        <input type="text" name="search" placeholder="Coach name..." value="<?php echo htmlspecialchars($filter_search); ?>">
                    </div>
                    <div class="filter-field">
                        <label>Gender</label>
                        <select name="gender">
                            <option value="">All Genders</option>
                            <option value="Male"   <?php echo ($filter_gender === 'Male'   ? 'selected' : ''); ?>>Male</option>
                            <option value="Female" <?php echo ($filter_gender === 'Female' ? 'selected' : ''); ?>>Female</option>
                        </select>
                    </div>
                    <div class="filter-field">
                        <label>Specialty</label>
                        <select name="specialty">
                            <option value="">All Specialties</option>
                            <option value="Singles Coaching"       <?php echo ($filter_specialty === 'Singles Coaching'       ? 'selected' : ''); ?>>Singles Coaching</option>
                            <option value="Doubles Coaching"       <?php echo ($filter_specialty === 'Doubles Coaching'       ? 'selected' : ''); ?>>Doubles Coaching</option>
                            <option value="Fitness & Conditioning" <?php echo ($filter_specialty === 'Fitness & Conditioning' ? 'selected' : ''); ?>>Fitness &amp; Conditioning</option>
                            <option value="Junior Development"     <?php echo ($filter_specialty === 'Junior Development'     ? 'selected' : ''); ?>>Junior Development</option>
                            <option value="Tournament Preparation" <?php echo ($filter_specialty === 'Tournament Preparation' ? 'selected' : ''); ?>>Tournament Preparation</option>
                        </select>
                    </div>
                    <div class="filter-field">
                        <label>Age Min</label>
                        <input type="number" name="age_min" placeholder="Min" min="18" max="80" value="<?php echo $filter_age_min ?: ''; ?>">
                    </div>
                    <div class="filter-field">
                        <label>Age Max</label>
                        <input type="number" name="age_max" placeholder="Max" min="18" max="80" value="<?php echo $filter_age_max ?: ''; ?>">
                    </div>
                    <div class="filter-actions">
                        <button type="submit" class="btn-filter-apply"><i class="fas fa-search"></i> Apply</button>
                        <a href="ManageCoaches.php" class="btn-filter-clear">Clear</a>
                    </div>
                </form>
            </div>

            <table class="data-table">
                <thead>
                    <tr>
                        <th><?php echo coachSortLink('ID', 'id', $sort_col, $sort_dir, $next_dir, $filter_gender, $filter_specialty, $filter_search, $filter_age_min, $filter_age_max); ?></th>
                        <th><?php echo coachSortLink('Coach Info', 'name', $sort_col, $sort_dir, $next_dir, $filter_gender, $filter_specialty, $filter_search, $filter_age_min, $filter_age_max); ?></th>
                        <th><?php echo coachSortLink('Specialty', 'specialty', $sort_col, $sort_dir, $next_dir, $filter_gender, $filter_specialty, $filter_search, $filter_age_min, $filter_age_max); ?></th>
                        <th><?php echo coachSortLink('Price/Hour', 'price_per_hour', $sort_col, $sort_dir, $next_dir, $filter_gender, $filter_specialty, $filter_search, $filter_age_min, $filter_age_max); ?></th>
                        <th><?php echo coachSortLink('Status', 'availability_status', $sort_col, $sort_dir, $next_dir, $filter_gender, $filter_specialty, $filter_search, $filter_age_min, $filter_age_max); ?></th>
                        <th><?php echo coachSortLink('Account', 'is_active', $sort_col, $sort_dir, $next_dir, $filter_gender, $filter_specialty, $filter_search, $filter_age_min, $filter_age_max); ?></th>
                    </tr>
                </thead>

                <tbody>
                    <?php while($row = mysqli_fetch_assoc($result)):
                        $avail      = $row['availability_status'] ?? 'Available';
                        $name_lower = strtolower($row['name']);
                        $gender_val = $row['gender'] ?? '';
                        $spec_val   = $row['specialty'] ?? '';
                        $age_val    = $row['age'] ?? '';

                        // Apply PHP-side filters
                        if($filter_search    && stripos($row['name'], $filter_search) === false) continue;
                        if($filter_gender    && $gender_val !== $filter_gender) continue;
                        if($filter_specialty && $spec_val   !== $filter_specialty) continue;
                        if($filter_age_min   && $age_val    < $filter_age_min) continue;
                        if($filter_age_max   && $age_val    > $filter_age_max) continue;
                    ?>
                    <tr class="main-row"
                        onclick="openCoachEditModal(
                            <?php echo $row['id']; ?>,
                            '<?php echo addslashes($row['name']); ?>',
                            '<?php echo addslashes($spec_val); ?>',
                            '<?php echo addslashes($row['phone'] ?? ''); ?>',
                            '<?php echo addslashes($gender_val); ?>',
                            '<?php echo $age_val; ?>',
                            '<?php echo $row['price_per_hour']; ?>',
                            '<?php echo $row['profile_img'] ?? ''; ?>'
                        )" style="cursor:pointer;">
                        <td>#<?php echo $row['id']; ?></td>
                        <td>
                            <strong><?php echo htmlspecialchars($row['name']); ?></strong><br>
                            <small style="color:#999;"><?php echo htmlspecialchars($row['email']); ?></small>
                        </td>
                        <td><?php echo htmlspecialchars($spec_val ?: '—'); ?></td>
                        <td>RM <?php echo number_format($row['price_per_hour'], 2); ?></td>

                        <!-- Availability status -->
                        <td onclick="event.stopPropagation()">
                            <?php
                                $avail_class = match($avail) {
                                    'Available' => 'status-active',
                                    'On Leave'  => 'status-inactive',
                                    'Sick'      => 'status-suspended',
                                    'Off Day'   => 'status-inactive',
                                    default     => 'status-inactive'
                                };
                            ?>
                            <select class="status-select <?php echo $avail_class; ?>"
                                onclick="event.stopPropagation()"
                                onchange="location.href='ManageCoaches.php?avail_id=<?php echo $row['id']; ?>&avail_status=' + this.value">
                                <option value="Available" <?php echo $avail === 'Available' ? 'selected' : ''; ?>>Available</option>
                                <option value="On Leave"  <?php echo $avail === 'On Leave'  ? 'selected' : ''; ?>>On Leave</option>
                                <option value="Sick"      <?php echo $avail === 'Sick'      ? 'selected' : ''; ?>>Sick</option>
                                <option value="Off Day"   <?php echo $avail === 'Off Day'   ? 'selected' : ''; ?>>Off Day</option>
                            </select>
                        </td>

                        <!-- Account status -->
                        <td onclick="event.stopPropagation()">
                            <select class="status-select <?php echo $row['is_active'] == 1 ? 'status-active' : 'status-inactive'; ?>"
                                onclick="event.stopPropagation()"
                                onchange="location.href='ManageCoaches.php?toggle_id=<?php echo $row['id']; ?>&status=' + (this.value === 'Active' ? 1 : 0)">
                                <option value="Active"   <?php echo $row['is_active'] == 1 ? 'selected' : ''; ?>>Active</option>
                                <option value="Inactive" <?php echo $row['is_active'] == 0 ? 'selected' : ''; ?>>Inactive</option>
                            </select>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>

        </div>
    </main>

    <!-- Edit Coach Modal -->
    <div class="modal-overlay" id="coachEditModal">

        <!-- Edit Panel -->
        <div class="modal-card" id="editPanel">

            <div class="modal-header">
                <h2><i class="fas fa-pen"></i> Edit Coach Profile</h2>
                <button class="modal-close" onclick="closeCoachEditModal()">✕</button>
            </div>

            <form action="ManageCoaches.php" method="POST">
                <input type="hidden" name="coach_id" id="coach-modal-id">
                <input type="hidden" name="cropped_img_data" id="cropped-img-data">

                <div class="modal-grid">

                    <!-- Profile Image -->
                    <div class="modal-field full-width" style="display:flex; flex-direction:column; align-items:center;">
                        <img id="coach-modal-img-preview"
                            src="../../Pictures/Admin_Module/coaches/default.png"
                            style="width:80px; height:80px; border-radius:50%; object-fit:cover; margin-bottom:8px; border:3px solid #f59e0b;">
                        <label class="btn-create" style="cursor:pointer; padding:8px 16px; font-size:13px;">
                            <i class="fas fa-camera"></i> Change Photo
                            <input type="file" id="coach-img-input" accept="image/*" style="display:none;">
                        </label>
                    </div>

                    <!-- Name -->
                    <div class="modal-field full-width">
                        <label>Name</label>
                        <input type="text" name="coach_name" id="coach-modal-name" required>
                    </div>

                    <!-- Specialty -->
                    <div class="modal-field full-width">
                        <label>Specialty</label>
                        <select name="specialty" id="coach-modal-specialty">
                            <option value="">-- Select --</option>
                            <option value="Singles Coaching">Singles Coaching</option>
                            <option value="Doubles Coaching">Doubles Coaching</option>
                            <option value="Fitness & Conditioning">Fitness &amp; Conditioning</option>
                            <option value="Junior Development">Junior Development</option>
                            <option value="Tournament Preparation">Tournament Preparation</option>
                        </select>
                    </div>

                    <!-- Phone -->
                    <div class="modal-field">
                        <label>Phone</label>
                        <input type="text" name="phone" id="coach-modal-phone">
                    </div>

                    <!-- Price -->
                    <div class="modal-field">
                        <label>Price Per Hour (RM)</label>
                        <input type="number" name="price_per_hour" id="coach-modal-price" step="0.01" min="0" required>
                    </div>

                    <!-- Gender -->
                    <div class="modal-field">
                        <label>Gender</label>
                        <select name="gender" id="coach-modal-gender">
                            <option value="">-- Select --</option>
                            <option value="Male">Male</option>
                            <option value="Female">Female</option>
                        </select>
                    </div>

                    <!-- Age -->
                    <div class="modal-field">
                        <label>Age</label>
                        <input type="number" name="age" id="coach-modal-age" min="18" max="80">
                    </div>

                </div>

                <div class="modal-actions">
                    <button type="button" class="btn-modal-cancel" onclick="closeCoachEditModal()">Cancel</button>
                    <button type="submit" name="update_coach" class="btn-modal-save">Save Changes</button>
                </div>
            </form>

        </div>

        <!-- Crop Panel (replaces edit panel when cropping) -->
        <div class="modal-card" id="cropPanel" style="display:none;">

            <div class="modal-header">
                <h2><i class="fas fa-crop-alt"></i> Crop Photo</h2>
            </div>

            <div id="crop-area">
                <img id="crop-img" style="display:block; width:100%;">
            </div>

            <div class="modal-actions">
                <button type="button" class="btn-modal-cancel" onclick="cancelCrop()">Back</button>
                <button type="button" class="btn-modal-save" onclick="applyCrop()">Crop & Use</button>
            </div>

        </div>

    </div>

    <!-- Shared dashboard JS (mobile menu, etc.) -->
    <script src="../Dashboard/Dashboard.js"></script>
    <!-- Cropper library, loaded BEFORE ManageCoaches.js so it can use Cropper -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.js"></script>
    <!-- All page-specific UI logic lives in ManageCoaches.js -->
    <script src="ManageCoaches.js"></script>

</body>
</html>