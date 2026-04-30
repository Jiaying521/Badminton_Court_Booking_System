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
    $username = $_SESSION['username'];
    $role = $_SESSION['role'];
    $display_name = $username;

    $error = "";

    // Handle form submission
    if(isset($_POST['save_court'])){

        // Get form values
        $court_name = mysqli_real_escape_string($conn, $_POST['court_name']); // Escape user input to prevent SQL Injection
        $court_type = mysqli_real_escape_string($conn, $_POST['court_type']);
        $location   = mysqli_real_escape_string($conn, $_POST['location']);
        $facilities = mysqli_real_escape_string($conn, $_POST['facilities']);
        $price_off_peak = mysqli_real_escape_string($conn, $_POST['price_off_peak']);
        $price_peak = mysqli_real_escape_string($conn, $_POST['price_peak']);
        $is_active  = isset($_POST['is_active']) ? 1 : 0;

        // Check if court name already exists
        $check = mysqli_query($conn, "SELECT id FROM courts WHERE court_name = '$court_name'");
        if(mysqli_num_rows($check) > 0){
            $error = "Court name already exists!";
        } else {
            // Insert into database
            $sql = "INSERT INTO courts (court_name, court_type, location, facilities, price_off_peak, price_peak, is_active)
                    VALUES ('$court_name', '$court_type', '$location', '$facilities', '$price_off_peak', '$price_peak', '$is_active')";

            if(mysqli_query($conn, $sql)){
                $new_court_id = mysqli_insert_id($conn);
                for ($day = 1; $day <= 7; $day++) {
                    mysqli_query($conn, "INSERT INTO court_availability (court_id, day_of_week, start_time, end_time) VALUES ('$new_court_id', '$day', '08:00:00', '01:00:00')");
                }
                // Success - go back to ManageCourts with success message
                header("Location: ManageCourts.php?success=1");
                exit();
            } else {
                $error = "Database error: " . mysqli_error($conn);
            }
        }
    }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Badminton Hub - Add Court</title>

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
                    <h1>Add New Court</h1>
                    <p>Fill in the details to add a new badminton court.</p>
                </div>
                <div class="btn-add-group">
                    <a href="ManageCourts.php" class="btn-add-account" style="text-decoration:none; background:#6b7280;">
                        <i class="fas fa-arrow-left"></i> Back
                    </a>
                </div>
            </header>

            <?php if($error !== ""): ?>
                <div class="badge pending" style="width:100%; padding:15px; margin-bottom:20px;">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <div class="form-card active">
                <form method="POST" class="form-grid">

                    <div class="form-group">
                        <label>Court Name</label>
                        <input type="text" name="court_name" placeholder="e.g. Court A" required>
                    </div>

                    <div class="form-group">
                        <label>Court Type</label>
                        <select name="court_type" required>
                            <option value="" disabled selected>Select Type</option>
                            <option value="Standard">Standard</option>
                            <option value="Training">Training</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Location</label>
                        <input type="text" name="location" placeholder="e.g. Main Hall 1">
                    </div>

                    <div class="form-group">
                        <label>Facilities</label>
                        <input type="text" name="facilities" placeholder="e.g. Shower, Locker">
                    </div>

                    <div class="form-group">
                        <label>Off-Peak Price (RM)</label>
                        <input type="number" name="price_off_peak" placeholder="e.g. 10.00" value="10.00" step="0.01" min="0" required>
                    </div>

                    <div class="form-group">
                        <label>Peak Price (RM)</label>
                        <input type="number" name="price_peak" placeholder="e.g. 15.00" value="15.00" step="0.01" min="0" required>
                    </div>

                    <div class="form-group">
                        <label>Status</label>
                        <input type="checkbox" name="is_active" checked> Active
                    </div>

                    <div class="form-group" style="grid-column: 1 / -1;">
                        <button type="submit" name="save_court" class="btn-create">
                            <i class="fas fa-save"></i> Save Court
                        </button>
                    </div>

                </form>
            </div>

        </div>
    </main>

    <script src="SuperAdminDashboard.js"></script>
</body>
</html>
