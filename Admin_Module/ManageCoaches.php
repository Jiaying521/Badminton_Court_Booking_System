<?php 
    //LOGIN Check
    session_start();
    if(!isset($_SESSION['username'])){
        header("Location: LoginPage.php");
        exit();
    }

    //Role check
    if(!in_array($_SESSION['role'], ['Superadmin', 'Admin'])){
        header("Location: LoginPage.php");
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

    //Handle edit coach
    if(isset($_POST['update_coach'])){
        $coach_id       = intval($_POST['coach_id']);
        $name           = mysqli_real_escape_string($conn, $_POST['coach_name']);
        $specialty      = mysqli_real_escape_string($conn, $_POST['specialty']);
        $phone          = mysqli_real_escape_string($conn, $_POST['phone']);
        $gender         = mysqli_real_escape_string($conn, $_POST['gender']);
        $age            = intval($_POST['age']);
        $price_per_hour = floatval($_POST['price_per_hour']);

        //Handle profile image upload
        $img_sql = "";
        if(isset($_FILES['profile_img']) && $_FILES['profile_img']['error'] === 0){
            $img_name    = time() . '_' . basename($_FILES['profile_img']['name']);
            $upload_path = 'Pictures/coaches/' . $img_name;

            if(move_uploaded_file($_FILES['profile_img']['tmp_name'], $upload_path)){
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

    //Get coach data from database
    $result = mysqli_query($conn, "
        SELECT coaches.id, coaches.name, coaches.specialty,
               coaches.phone, coaches.price_per_hour, coaches.is_active,
               coaches.availability_status, coaches.gender, coaches.age,
               coaches.profile_img, admins.email
        FROM coaches
        JOIN admins ON coaches.admin_id = admins.id
        ORDER BY coaches.id ASC
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

    <link rel="stylesheet" href="SuperAdminDashboard.css">
    <link rel="stylesheet" href="AdminManagement.css">
    <link rel="stylesheet" href="ManageCourts.css">
</head>

<body>

    <?php include 'navbar.php'; ?>

    <main class="content">
        <div class="manage-container">

            <header class="management-header">
                <div>
                    <h1>Coach Management</h1>
                    <p>View and manage coach profiles, specialty and pricing.</p>
                </div>
            </header>

            <?php if(isset($_GET['success'])): ?>
                <div class="badge success" style="width:100%; padding:15px; margin-bottom:20px;">Coach updated successfully!</div>
            <?php endif; ?>

            <?php if(isset($_GET['updated'])): ?>
                <div class="badge success" style="width:100%; padding:15px; margin-bottom:20px;">Status updated successfully!</div>
            <?php endif; ?>

            <table class="data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Coach Info</th>
                        <th>Specialty</th>
                        <th>Price/Hour</th>
                        <th>Status</th>
                        <th>Account</th>
                    </tr>
                </thead>

                <tbody>
                    <?php while($row = mysqli_fetch_assoc($result)): ?>
                    <tr class="main-row" onclick="openCoachEditModal(
                        <?php echo $row['id']; ?>,
                        '<?php echo addslashes($row['name']); ?>',
                        '<?php echo addslashes($row['specialty']); ?>',
                        '<?php echo addslashes($row['phone'] ?? ''); ?>',
                        '<?php echo addslashes($row['gender'] ?? ''); ?>',
                        '<?php echo $row['age'] ?? ''; ?>',
                        '<?php echo $row['price_per_hour']; ?>',
                        '<?php echo $row['profile_img'] ?? ''; ?>'
                    )" style="cursor:pointer;">
                        <td>#<?php echo $row['id']; ?></td>
                        <td>
                            <strong><?php echo htmlspecialchars($row['name']); ?></strong><br>
                            <small style="color:#999;"><?php echo htmlspecialchars($row['email']); ?></small>
                        </td>
                        <td><?php echo htmlspecialchars($row['specialty'] ?: '—'); ?></td>
                        <td>RM <?php echo number_format($row['price_per_hour'], 2); ?></td>

                        <!-- Availability status -->
                        <td onclick="event.stopPropagation()">
                            <?php
                                $avail = $row['availability_status'] ?? 'Available';
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
        <div class="modal-card">

            <div class="modal-header">
                <h2><i class="fas fa-pen"></i> Edit Coach Profile</h2>
                <button class="modal-close" onclick="closeCoachEditModal()">✕</button>
            </div>

            <form action="ManageCoaches.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="coach_id" id="coach-modal-id">

                <div class="modal-grid">

                    <!-- Profile Image -->
                    <div class="modal-field full-width" style="display:flex; flex-direction:column; align-items:center;">
                        <img id="coach-modal-img-preview"
                            src="Pictures/coaches/default.png"
                            style="width:80px; height:80px; border-radius:50%; object-fit:cover; margin-bottom:8px; border:3px solid #f59e0b;">
                        <label class="btn-create" style="cursor:pointer; padding:8px 16px; font-size:13px;">
                            <i class="fas fa-camera"></i> Change Photo
                            <input type="file" name="profile_img" id="coach-img-input" accept="image/*" style="display:none;">
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
                        <input type="text" name="specialty" id="coach-modal-specialty" required>
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
    </div>

    <script src="SuperAdminDashboard.js"></script>

    <script>
        function openCoachEditModal(id, name, specialty, phone, gender, age, price, img) {
            document.getElementById('coach-modal-id').value        = id;
            document.getElementById('coach-modal-name').value      = name;
            document.getElementById('coach-modal-specialty').value = specialty;
            document.getElementById('coach-modal-phone').value     = phone;
            document.getElementById('coach-modal-gender').value    = gender;
            document.getElementById('coach-modal-age').value       = age;
            document.getElementById('coach-modal-price').value     = price;

            //Show profile image, fall back to default if none
            const preview = document.getElementById('coach-modal-img-preview');
            preview.src   = img ? 'Pictures/coaches/' + img : 'Pictures/coaches/default.png';

            document.getElementById('coachEditModal').style.display = 'flex';
        }

        function closeCoachEditModal() {
            document.getElementById('coachEditModal').style.display = 'none';
        }

        document.addEventListener('DOMContentLoaded', function() {

            //Preview image before uploading
            document.getElementById('coach-img-input').addEventListener('change', function() {
                const file   = this.files[0];
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('coach-modal-img-preview').src = e.target.result;
                };
                reader.readAsDataURL(file);
            });

            //Close modal when clicking outside
            document.getElementById('coachEditModal').addEventListener('click', function(e) {
                if(e.target === this) closeCoachEditModal();
            });

        });
    </script>

</body>
</html>