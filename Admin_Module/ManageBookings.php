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

    // Fetch booking data with player name and court name
    $result = mysqli_query($conn, "
    SELECT 
        bookings.id,
        users.name,
        courts.court_name,
        bookings.booking_date,
        bookings.start_time,
        bookings.end_time,
        bookings.status,
        bookings.total_price

    FROM bookings

    JOIN users ON bookings.user_id = users.id
    JOIN courts ON bookings.court_id = courts.id

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
    <link rel="stylesheet" href="SuperAdminDashboard.css">
    <link rel="stylesheet" href="AdminManagement.css">

    <style>
        .filter-bar   { 
            margin-bottom:15px; 
            display:flex; 
            gap:8px; 
            flex-wrap:wrap; 
        }

        .filter-btn   { 
            padding:6px 14px; 
            border-radius:5px; 
            text-decoration:none; 
            font-size:13px; 
            background:#e5e7eb; 
            color:#374151; 
        }

        .filter-btn.active { 
            background:#6366f1; 
            color:white; 
        }

    </style>
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
                    <tr>
                        <td><?php echo $row['id']; ?></td>
                        <td><?php echo htmlspecialchars($row['name']); ?></td>
                        <td><?php echo htmlspecialchars($row['court_name']); ?></td>
                        <td><?php echo date("d-m-Y", strtotime($row['booking_date'])); ?></td>
                        <td><?php echo date("h:i A", strtotime($row['start_time'])); ?></td>
                        <td><?php echo date("h:i A", strtotime($row['end_time'])); ?></td>
                        <td>RM <?php echo number_format($row['total_price'], 2); ?></td>
                        <td>
                            <!-- Status Dropdown (mirrors AdminManagement.php style) -->
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

                    <?php endwhile; ?>

                </tbody>
            </table>
        </div>
    </main>
</body>
</html>