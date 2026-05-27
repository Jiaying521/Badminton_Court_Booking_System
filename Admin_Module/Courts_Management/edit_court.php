<?php
session_start();
if (!isset($_SESSION['username']) || !in_array($_SESSION['role'], ['Superadmin', 'Admin'])) {
    header("Location: LoginPage.php");
    exit();
}

header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

$conn = mysqli_connect("localhost", "root", "", "badminton_hub");
$username = $_SESSION['username'];
$role = $_SESSION['role'];
$display_name = $username;
$error = "";

$court_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($court_id <= 0) {
    header("Location: ManageCourts.php");
    exit();
}

if (isset($_POST['update_court'])) {
    $court_name = mysqli_real_escape_string($conn, $_POST['court_name']);
    $court_type = mysqli_real_escape_string($conn, $_POST['court_type']);
    $location = mysqli_real_escape_string($conn, $_POST['location']);
    $facilities = mysqli_real_escape_string($conn, $_POST['facilities']);
    $price_off_peak = mysqli_real_escape_string($conn, $_POST['price_off_peak']);
    $price_peak = mysqli_real_escape_string($conn, $_POST['price_peak']);
    $is_active = isset($_POST['is_active']) ? 1 : 0;

    $check = mysqli_query($conn, "SELECT id FROM courts WHERE court_name = '$court_name' AND id != $court_id");
    if (mysqli_num_rows($check) > 0) {
        $error = "Court name already exists!";
    } else {
        $sql = "UPDATE courts
                SET court_name = '$court_name',
                    court_type = '$court_type',
                    location = '$location',
                    facilities = '$facilities',
                    price_off_peak = '$price_off_peak',
                    price_peak = '$price_peak',
                    is_active = '$is_active'
                WHERE id = $court_id";

        if (mysqli_query($conn, $sql)) {
            header("Location: ManageCourts.php?success=1");
            exit();
        }

        $error = "Database error: " . mysqli_error($conn);
    }
}

$result = mysqli_query($conn, "SELECT * FROM courts WHERE id = $court_id");
$court = mysqli_fetch_assoc($result);
if (!$court) {
    header("Location: ManageCourts.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Badminton Hub - Edit Court</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800;900&display=swap">
    <link rel="stylesheet" href="SuperAdminDashboard.css">
    <link rel="stylesheet" href="AdminManagement.css">
    <link rel="stylesheet" href="AddCourt.css">
</head>
<body>
    <?php include 'navbar.php'; ?>

    <main class="content">
        <div class="manage-container">
            <header class="management-header">
                <div>
                    <h1>Edit Court</h1>
                    <p>Update court details, availability status and pricing.</p>
                </div>
                <div class="btn-add-group">
                    <a href="ManageCourts.php" class="btn-add-account" style="text-decoration:none; background:#6b7280;">
                        <i class="fas fa-arrow-left"></i> Back
                    </a>
                </div>
            </header>

            <?php if ($error !== ""): ?>
                <div class="badge pending" style="width:100%; padding:15px; margin-bottom:20px;">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <div class="form-card active">
                <form method="POST" class="form-grid">
                    <div class="form-group">
                        <label>Court Name</label>
                        <input type="text" name="court_name" value="<?php echo htmlspecialchars($court['court_name']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label>Court Type</label>
                        <select name="court_type" required>
                            <option value="Standard" <?php echo ($court['court_type'] === 'Standard') ? 'selected' : ''; ?>>Standard</option>
                            <option value="Training" <?php echo ($court['court_type'] === 'Training') ? 'selected' : ''; ?>>Training</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Location</label>
                        <input type="text" name="location" value="<?php echo htmlspecialchars($court['location']); ?>">
                    </div>

                    <div class="form-group">
                        <label>Facilities</label>
                        <input type="text" name="facilities" value="<?php echo htmlspecialchars($court['facilities']); ?>">
                    </div>

                    <div class="form-group">
                        <label>Off-Peak Price (RM)</label>
                        <input type="number" name="price_off_peak" value="<?php echo htmlspecialchars($court['price_off_peak']); ?>" step="0.01" min="0" required>
                    </div>

                    <div class="form-group">
                        <label>Peak Price (RM)</label>
                        <input type="number" name="price_peak" value="<?php echo htmlspecialchars($court['price_peak']); ?>" step="0.01" min="0" required>
                    </div>

                    <div class="form-group">
                        <label>Status</label>
                        <input type="checkbox" name="is_active" <?php echo ((int)$court['is_active'] === 1) ? 'checked' : ''; ?>> Active
                    </div>

                    <div class="form-group" style="grid-column: 1 / -1;">
                        <button type="submit" name="update_court" class="btn-create">
                            <i class="fas fa-save"></i> Update Court
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </main>

    <script src="SuperAdminDashboard.js"></script>
</body>
</html>
