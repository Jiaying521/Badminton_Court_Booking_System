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
        $specialty      = mysqli_real_escape_string($conn, $_POST['specialty']);
        $phone          = mysqli_real_escape_string($conn, $_POST['phone']);
        $price_per_hour = floatval($_POST['price_per_hour']);

        mysqli_query($conn, "
            UPDATE coaches SET
                specialty      = '$specialty',
                phone          = '$phone',
                price_per_hour = $price_per_hour
            WHERE id = $coach_id
        ");

        header("Location: ManageCoaches.php?success=1");
        exit();
    }

    //Handle toggle status
    if(isset($_GET['toggle_id']) && isset($_GET['status'])){
        $toggle_id    = intval($_GET['toggle_id']);
        $new_status   = intval($_GET['status']);
        $admin_status = ($new_status == 1) ? 'Active' : 'Inactive';

        mysqli_query($conn, "UPDATE coaches SET is_active = $new_status WHERE id = $toggle_id");
        mysqli_query($conn, "UPDATE admins SET status = '$admin_status' WHERE id = (SELECT admin_id FROM coaches WHERE id = $toggle_id)");

        header("Location: ManageCoaches.php?updated=1");
        exit();
    }

    //Get coach data from database
    $result = mysqli_query($conn, "
        SELECT coaches.id, coaches.name, coaches.specialty,
               coaches.phone, coaches.price_per_hour, coaches.is_active,
               admins.email
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
                        <th>Phone</th>
                        <th>Price/Hour</th>
                        <th>Status</th>
                    </tr>
                </thead>

                <tbody>
                    <?php while($row = mysqli_fetch_assoc($result)): ?>
                    <tr class="main-row" onclick="openEditModal(
                        <?php echo $row['id']; ?>,
                        '<?php echo addslashes($row['specialty']); ?>',
                        '<?php echo addslashes($row['phone']); ?>',
                        '<?php echo $row['price_per_hour']; ?>'
                    )">
                        <td>#<?php echo $row['id']; ?></td>
                        <td>
                            <strong><?php echo htmlspecialchars($row['name']); ?></strong><br>
                            <small style="color:#999;"><?php echo htmlspecialchars($row['email']); ?></small>
                        </td>
                        <td><?php echo htmlspecialchars($row['specialty'] ?: '—'); ?></td>
                        <td><?php echo htmlspecialchars($row['phone'] ?: '—'); ?></td>
                        <td>RM <?php echo number_format($row['price_per_hour'], 2); ?></td>
                        <td onclick="event.stopPropagation()">
                            <select class="status-select <?php echo $row['is_active'] == 1 ? 'status-active' : 'status-inactive'; ?>"
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
    <div class="modal-overlay" id="editModal">
        <div class="modal-card">

            <div class="modal-header">
                <h2><i class="fas fa-pen"></i> Edit Coach</h2>
                <button class="modal-close" onclick="closeEditModal()">✕</button>
            </div>

            <form action="ManageCoaches.php" method="POST">
                <input type="hidden" name="coach_id" id="modal-coach-id">

                <div class="modal-grid">

                    <div class="modal-field full-width">
                        <label>Specialty</label>
                        <input type="text" name="specialty" id="modal-specialty" required>
                    </div>

                    <div class="modal-field">
                        <label>Phone</label>
                        <input type="text" name="phone" id="modal-phone">
                    </div>

                    <div class="modal-field">
                        <label>Price Per Hour (RM)</label>
                        <input type="number" name="price_per_hour" id="modal-price" step="0.01" min="0" required>
                    </div>

                </div>

                <div class="modal-actions">
                    <button type="button" class="btn-modal-cancel" onclick="closeEditModal()">Cancel</button>
                    <button type="submit" name="update_coach" class="btn-modal-save">Save Changes</button>
                </div>

            </form>
        </div>
    </div>

    <script src="SuperAdminDashboard.js"></script>

    <script>
        function openEditModal(id, specialty, phone, price) {
            document.getElementById('modal-coach-id').value  = id;
            document.getElementById('modal-specialty').value = specialty;
            document.getElementById('modal-phone').value     = phone;
            document.getElementById('modal-price').value     = price;
            document.getElementById('editModal').style.display = 'flex';
        }

        function closeEditModal() {
            document.getElementById('editModal').style.display = 'none';
        }

        document.getElementById('editModal').addEventListener('click', function(e) {
            if(e.target === this) closeEditModal();
        });
    </script>

</body>
</html>