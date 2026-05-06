<?php
require_once __DIR__ . '/../config.php';
if(!isLoggedIn()) redirect('homepage.php');

$user_id = $_SESSION['user_id'];

// 获取用户所有预订记录
$stmt = $pdo->prepare("
    SELECT b.*, c.court_name, c.court_type, c.location,
           co.name as coach_name
    FROM bookings b 
    JOIN courts c ON b.court_id = c.id 
    LEFT JOIN coaches co ON b.coach_id = co.id
    WHERE b.user_id = ? 
    ORDER BY b.booking_date DESC, b.start_time DESC
");
$stmt->execute([$user_id]);
$bookings = $stmt->fetchAll();

// 获取用户信息
$userStmt = $pdo->prepare("SELECT name FROM users WHERE id = ?");
$userStmt->execute([$user_id]);
$user = $userStmt->fetch();
?>
<!DOCTYPE html>
<html>
<head>
    <title>My Bookings | Smash Arena</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family:'Inter',sans-serif; background:linear-gradient(145deg,#f5f9f0 0%,#e8efe2 100%); color:#1e2a2e; padding:2rem; }
        .container { max-width:1300px; margin:0 auto; }
        
        /* Navbar */
        .navbar { display:flex; justify-content:space-between; align-items:center; margin-bottom:2rem; flex-wrap:wrap; gap:1rem; padding-bottom:1rem; border-bottom:1px solid rgba(43,126,58,0.15); }
        .logo img { height: 45px; width: auto; transition:transform 0.3s; }
        .logo img:hover { transform:scale(1.02); }
        .nav-links a { margin-left:1.5rem; color:#2c4a2e; text-decoration:none; font-weight:500; transition:0.2s; }
        .nav-links a:hover { color:#2b7e3a; }
        .nav-links a.active { color:#2b7e3a; font-weight:600; }
        .user-greeting { color:#2b7e3a; margin-left:1rem; font-weight:500; }
        
        /* Page Header */
        .page-header { display:flex; justify-content:space-between; align-items:center; margin-bottom:2rem; flex-wrap:wrap; gap:1rem; }
        .page-header h1 { color:#1e3a2a; font-size:1.8rem; }
        .page-header h1 i { color:#2b7e3a; margin-right:0.5rem; }
        .btn-book { background:#2b7e3a; color:white; border:none; padding:0.6rem 1.2rem; border-radius:50px; cursor:pointer; text-decoration:none; display:inline-flex; align-items:center; gap:0.5rem; font-weight:600; transition:0.2s; }
        .btn-book:hover { background:#1f5a2a; transform:translateY(-2px); }
        
        /* Stats Cards */
        .stats-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(200px,1fr)); gap:1rem; margin-bottom:2rem; }
        .stat-card { background:white; border-radius:24px; padding:1.2rem; text-align:center; box-shadow:0 4px 15px rgba(0,0,0,0.03); border:1px solid rgba(43,126,58,0.08); transition:0.2s; }
        .stat-card:hover { transform:translateY(-3px); box-shadow:0 8px 25px rgba(43,126,58,0.1); }
        .stat-number { font-size:2rem; font-weight:800; color:#2b7e3a; }
        .stat-label { color:#5a6e5c; font-size:0.85rem; margin-top:0.3rem; }
        
        /* Filter Tabs */
        .filter-tabs { display:flex; gap:0.5rem; margin-bottom:1.5rem; flex-wrap:wrap; }
        .filter-btn { background:#e8f0e5; border:none; padding:0.5rem 1.2rem; border-radius:50px; cursor:pointer; font-weight:500; transition:0.2s; }
        .filter-btn:hover { background:#d0e0c8; }
        .filter-btn.active { background:#2b7e3a; color:white; }
        
        /* Bookings Table */
        .bookings-table { background:white; border-radius:28px; overflow:hidden; box-shadow:0 8px 25px rgba(0,0,0,0.05); }
        table { width:100%; border-collapse:collapse; }
        th { background:#2b7e3a; color:white; padding:1rem; text-align:left; font-weight:600; }
        td { padding:1rem; border-bottom:1px solid #e0e0e0; vertical-align:middle; }
        tr:hover { background:#f9f9f9; }
        
        /* Status Badges */
        .status { display:inline-block; padding:0.25rem 0.8rem; border-radius:50px; font-size:0.75rem; font-weight:600; }
        .status-Confirmed { background:#d4edda; color:#155724; }
        .status-Pending { background:#fff3cd; color:#856404; }
        .status-Cancelled { background:#f8d7da; color:#721c24; }
        .status-Completed { background:#cce5ff; color:#004085; }
        
        /* Court Badge */
        .court-badge { display:inline-block; background:#eaf5e6; color:#2b7e3a; padding:0.2rem 0.5rem; border-radius:20px; font-size:0.7rem; margin-top:0.3rem; }
        
        /* Action Buttons */
        .action-btns { display:flex; gap:0.5rem; }
        .btn-view { background:#e8f0e5; color:#2c4a2e; border:none; padding:0.3rem 0.9rem; border-radius:50px; cursor:pointer; font-size:0.75rem; text-decoration:none; display:inline-flex; align-items:center; gap:0.3rem; transition:0.2s; }
        .btn-view:hover { background:#2b7e3a; color:white; }
        .btn-cancel { background:#fee2e2; color:#e67e22; border:none; padding:0.3rem 0.9rem; border-radius:50px; cursor:pointer; font-size:0.75rem; display:inline-flex; align-items:center; gap:0.3rem; transition:0.2s; }
        .btn-cancel:hover { background:#e67e22; color:white; }
        
        /* Empty State */
        .empty-state { text-align:center; padding:4rem; background:white; border-radius:28px; }
        .empty-state i { font-size:4rem; color:#cbd5c0; margin-bottom:1rem; }
        .empty-state h3 { color:#5a6e5c; margin-bottom:0.5rem; }
        .empty-state p { color:#888; margin-bottom:1.5rem; }
        
        /* Receipt Modal */
        .modal { display:none; position:fixed; z-index:1000; left:0; top:0; width:100%; height:100%; background-color:rgba(0,0,0,0.5); backdrop-filter:blur(4px); }
        .modal-content { background:white; margin:5% auto; padding:0; width:90%; max-width:500px; border-radius:28px; overflow:hidden; animation:slideUp 0.3s ease; }
        @keyframes slideUp { from { opacity:0; transform:translateY(30px); } to { opacity:1; transform:translateY(0); } }
        .modal-header { background:#2b7e3a; color:white; padding:1rem 1.5rem; display:flex; justify-content:space-between; align-items:center; }
        .modal-header h3 { margin:0; }
        .modal-close { background:none; border:none; color:white; font-size:1.5rem; cursor:pointer; }
        .modal-body { padding:1.5rem; max-height:60vh; overflow-y:auto; }
        .receipt-row { display:flex; justify-content:space-between; padding:0.5rem 0; border-bottom:1px solid #eee; }
        .receipt-total { display:flex; justify-content:space-between; padding:0.8rem 0 0; margin-top:0.5rem; border-top:2px solid #2b7e3a; font-weight:700; font-size:1.1rem; color:#2b7e3a; }
        .print-btn { background:#2b7e3a; color:white; border:none; padding:0.6rem; border-radius:50px; width:100%; margin-top:1rem; cursor:pointer; font-weight:600; transition:0.2s; }
        .print-btn:hover { background:#1f5a2a; transform:translateY(-2px); }
        
        @media (max-width:768px) { body { padding:1rem; } th, td { padding:0.5rem; font-size:0.8rem; } .action-btns { flex-direction:column; } .stats-grid { grid-template-columns:repeat(2,1fr); } }
    </style>
</head>
<body>
<div class="container">
    <!-- Navbar -->
    <div class="navbar">
        <div class="logo">
            <img src="../Admin_Module/Pictures/logo.png" alt="Smash Arena" style="height: 45px; width: auto;" onerror="this.style.display='none'; this.nextSibling.style.display='block';">
            <span style="display:none; font-size:1.5rem; font-weight:800; background:linear-gradient(135deg,#2b7e3a,#1b5e2a); -webkit-background-clip:text; background-clip:text; color:transparent;">Smash Arena</span>
        </div>
        <div class="nav-links">
            <a href="dashboard.php"><i class="fas fa-home"></i> Courts</a>
            <a href="my_bookings.php" class="active"><i class="fas fa-bookmark"></i> My Bookings</a>
            <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
            <span class="user-greeting">🏸 <?php echo htmlspecialchars($user['name'] ?? 'Player'); ?></span>
        </div>
    </div>
    
    <!-- Page Header -->
    <div class="page-header">
        <h1><i class="fas fa-bookmark"></i> My Bookings</h1>
        <a href="dashboard.php" class="btn-book"><i class="fas fa-plus"></i> Book New Court</a>
    </div>
    
    <!-- Stats Cards -->
    <?php 
    $total_spent = 0;
    $completed_count = 0;
    $upcoming_count = 0;
    $today = date('Y-m-d');
    foreach($bookings as $b) {
        $total_spent += $b['total_price'];
        if($b['status'] == 'Completed') $completed_count++;
        if($b['booking_date'] >= $today && $b['status'] == 'Confirmed') $upcoming_count++;
    }
    ?>
    <div class="stats-grid">
        <div class="stat-card"><div class="stat-number"><?php echo count($bookings); ?></div><div class="stat-label">Total Bookings</div></div>
        <div class="stat-card"><div class="stat-number"><?php echo $upcoming_count; ?></div><div class="stat-label">Upcoming</div></div>
        <div class="stat-card"><div class="stat-number"><?php echo $completed_count; ?></div><div class="stat-label">Completed</div></div>
        <div class="stat-card"><div class="stat-number">RM <?php echo number_format($total_spent, 2); ?></div><div class="stat-label">Total Spent</div></div>
    </div>
    
    <!-- Filter Tabs -->
    <div class="filter-tabs">
        <button class="filter-btn active" data-filter="all">All</button>
        <button class="filter-btn" data-filter="Confirmed">Confirmed</button>
        <button class="filter-btn" data-filter="Pending">Pending</button>
        <button class="filter-btn" data-filter="Completed">Completed</button>
        <button class="filter-btn" data-filter="Cancelled">Cancelled</button>
    </div>
    
    <!-- Bookings Table -->
    <?php if(count($bookings) > 0): ?>
    <div class="bookings-table">
        <table id="bookingsTable">
            <thead>
                <tr><th>Court</th><th>Date & Time</th><th>Duration</th><th>Coach</th><th>Total</th><th>Status</th><th>Action</th></tr>
            </thead>
            <tbody>
                <?php foreach($bookings as $b): 
                    $booking_date = date('M j, Y', strtotime($b['booking_date']));
                    $start_time = date('h:i A', strtotime($b['start_time']));
                    $end_time = date('h:i A', strtotime($b['end_time']));
                ?>
                <tr data-status="<?php echo $b['status']; ?>">
                    <td><strong><?php echo htmlspecialchars($b['court_name']); ?></strong><div class="court-badge"><?php echo htmlspecialchars($b['court_type']); ?></div></td>
                    <td><?php echo $booking_date; ?><br><small><?php echo $start_time; ?> - <?php echo $end_time; ?></small></td>
                    <td><?php echo $b['total_hours']; ?> hour<?php echo $b['total_hours'] > 1 ? 's' : ''; ?></td>
                    <td><?php if($b['coach_id'] && $b['coach_id'] > 0): ?><i class="fas fa-chalkboard-user"></i> <?php echo htmlspecialchars($b['coach_name']); ?><br><small><?php echo $b['coach_hours']; ?> hour(s)</small><?php else: ?>-<?php endif; ?></td>
                    <td>RM <?php echo number_format($b['total_price'], 2); ?></td>
                    <td><span class="status status-<?php echo $b['status']; ?>"><?php echo $b['status']; ?></span></td>
                    <td class="action-btns">
                        <button class="btn-view" onclick="viewReceipt(<?php echo $b['id']; ?>)"><i class="fas fa-receipt"></i> Receipt</button>
                        <?php if($b['status'] == 'Pending' || $b['status'] == 'Confirmed'): ?>
                        <button class="btn-cancel" onclick="cancelBooking(<?php echo $b['id']; ?>)"><i class="fas fa-times"></i> Cancel</button>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php else: ?>
    <div class="empty-state">
        <i class="fas fa-calendar-alt"></i>
        <h3>No Bookings Yet</h3>
        <p>You haven't made any court bookings.</p>
        <a href="dashboard.php" class="btn-book"><i class="fas fa-plus"></i> Book Your First Court</a>
    </div>
    <?php endif; ?>
</div>

<!-- Receipt Modal -->
<div id="receiptModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3><i class="fas fa-receipt"></i> Booking Receipt</h3>
            <button class="modal-close" onclick="closeModal()">&times;</button>
        </div>
        <div class="modal-body" id="receiptBody"></div>
    </div>
</div>

<script>
    // Filter functionality
    const filterBtns = document.querySelectorAll('.filter-btn');
    const tableRows = document.querySelectorAll('#bookingsTable tbody tr');
    
    filterBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            filterBtns.forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            const filter = this.getAttribute('data-filter');
            
            tableRows.forEach(row => {
                if(filter === 'all' || row.getAttribute('data-status') === filter) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
    });
    
    // View Receipt
    async function viewReceipt(bookingId) {
        try {
            const response = await fetch(`get_booking_details.php?id=${bookingId}`);
            const data = await response.json();
            
            if(data.success) {
                const b = data.booking;
                const modalBody = document.getElementById('receiptBody');
                modalBody.innerHTML = `
                    <div style="text-align:center; margin-bottom:1rem;">
                        <img src="../Admin_Module/Pictures/logo.png" alt="Smash Arena" style="height: 40px; margin-bottom:0.5rem;" onerror="this.style.display='none'">
                        <h2 style="color:#2b7e3a;">Smash Arena</h2>
                        <p style="color:#888;">Official Booking Receipt</p>
                    </div>
                    <div class="receipt-row"><span>Receipt No.</span><span>#${String(b.id).padStart(6,'0')}</span></div>
                    <div class="receipt-row"><span>Court</span><span>${b.court_name} (${b.court_type})</span></div>
                    <div class="receipt-row"><span>Date</span><span>${b.booking_date}</span></div>
                    <div class="receipt-row"><span>Time</span><span>${b.start_time} - ${b.end_time}</span></div>
                    <div class="receipt-row"><span>Duration</span><span>${b.total_hours} hour(s)</span></div>
                    ${b.coach_name ? `<div class="receipt-row"><span>Coach</span><span>${b.coach_name} (${b.coach_hours} hour(s))</span></div>` : ''}
                    <div class="receipt-row"><span>Booking Status</span><span class="status status-${b.status}">${b.status}</span></div>
                    <div class="receipt-total"><span>Total Paid</span><span>RM ${parseFloat(b.total_price).toFixed(2)}</span></div>
                    <div style="text-align:center; margin-top:1rem; font-size:0.75rem; color:#888;">
                        <p>Thank you for booking with Smash Arena!</p>
                    </div>
                    <button class="print-btn" onclick="window.print()"><i class="fas fa-print"></i> Print Receipt</button>
                `;
                document.getElementById('receiptModal').style.display = 'block';
            } else {
                alert('Failed to load receipt details');
            }
        } catch(e) {
            console.error(e);
            alert('Error loading receipt');
        }
    }
    
    function closeModal() {
        document.getElementById('receiptModal').style.display = 'none';
    }
    
    window.onclick = function(event) {
        const modal = document.getElementById('receiptModal');
        if(event.target === modal) closeModal();
    }
    
    // Cancel Booking
    async function cancelBooking(bookingId) {
        if(confirm('Are you sure you want to cancel this booking? Cancellation fees may apply.')) {
            try {
                const response = await fetch(`cancel_booking.php`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ booking_id: bookingId })
                });
                const data = await response.json();
                if(data.success) {
                    alert('Booking cancelled successfully');
                    location.reload();
                } else {
                    alert(data.message || 'Failed to cancel booking');
                }
            } catch(e) {
                console.error(e);
                alert('Error cancelling booking');
            }
        }
    }
</script>
</body>
</html>