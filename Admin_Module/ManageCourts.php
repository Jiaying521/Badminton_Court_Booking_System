<?php
    
    //Login Status Check
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

    //Take session user information
    $username = $_SESSION['username'];
    $role = $_SESSION['role'];
    $display_name= $username; //Show "Hello, xxx" in the header

    //Fetch court data from database
    $result = mysqli_query($conn, "SELECT * FROM courts ORDER BY id DESC");

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
                <a href="AddCourt.php" class="btn-add-account" style="text-decoration:none;">
                    <i class="fas fa-plus"></i> Add Court
                </a>
            </div>
        </header>
        
        <!-- Add sucessfully message -->
        <?php if(isset($_GET['success'])): ?>
            <div class="badge success" style="width:100%; padding:15px; margin-bottom:20px;">
                Court saved successfully!
            </div>
        <?php endif; ?>

        <!-- Delete sucessfully message -->
        <?php if (isset($_GET['deleted'])): ?>
            <div class="badge pending" style="width:100%; padding:15px; margin-bottom:20px;">
                Court deleted successfully.
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
                        <th>Actions</th>
                    </tr>
                </thead>

                <tbody>
                
                    <?php
                        while($row = mysqli_fetch_assoc($result)):
                    ?>

                    <tr>
                        
                            <td>#<?php echo $row['id']; ?></td>

                            <td>
                                <strong><?php echo htmlspecialchars($row['court_name']); ?></strong>
                                <br>
                                <small style="color:#999;">
                                    <?php echo htmlspecialchars($row['location']); ?>
                                </small>
                            </td>

                            <td><?php echo htmlspecialchars($row['court_type']); ?></td>

                            <td>
                                Off-Peak: RM <?php echo number_format($row['price_off_peak'], 2); ?><br>
                                <small style="color:#999;">Peak: RM <?php echo number_format($row['price_peak'], 2); ?></small>
                            </td>

                            <td>
                                <?php
                                if ($row['is_active'] == 1): ?>
                                    <span class="badge success">Active</span>
                                <?php else: ?>
                                    <span class="badge pending">Inactive</span>
                                <?php endif; ?>
                            </td>

                            <td>
                                <a href="edit_court.php?id=<?php echo $row['id']; ?>">
                                    <i class="fas fa-edit" style="color:#f59e0b; font-size:18px; margin-right:10px;"></i>
                                </a>

                                <a href="delete_court.php?id=<?php echo $row['id']; ?>"
                                onclick="return confirm('Are you sure you want to delete this court?');">
                                    <i class="fas fa-trash-alt" style="color:#ef4444; font-size:18px;"></i>
                                </a>
                            </td>
                        </tr>

                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </main>

     <!-- JavaSciprt -->
    <script src="SuperAdminDashboard.js"></script>
</body>
</html>
