<?php
session_start();

if (!isset($_SESSION['username'])) {
    header("Location: ../LoginPage.php");
    exit();
}

if (!in_array($_SESSION['role'], ['Superadmin', 'Admin'])) {
    header("Location: ../LoginPage.php");
    exit();
}

header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

$conn         = mysqli_connect("localhost", "root", "", "badminton_hub");
$username     = $_SESSION['username'];
$role         = $_SESSION['role'];
$display_name = $username;
$base_path    = '../';

/* all coaches for dropdown + today sidebar */
$coaches_res = mysqli_query($conn, "
    SELECT id, name, profile_img, availability_status
    FROM coaches
    WHERE is_active = 1
    ORDER BY name
");

$coaches     = [];
$today_str   = date('Y-m-d');

while ($row = mysqli_fetch_assoc($coaches_res)) {
    $coaches[] = $row;
}

/* today's unavailable coaches */
$today_res = mysqli_query($conn, "
    SELECT ca.coach_id, ca.status, ca.start_time, ca.end_time, ca.reason,
           c.name AS coach_name, c.profile_img
    FROM coach_availability ca
    JOIN coaches c ON ca.coach_id = c.id
    WHERE ca.date = '$today_str'
    ORDER BY c.name, ca.start_time
");

$today_unavail = [];
while ($row = mysqli_fetch_assoc($today_res)) {
    $today_unavail[$row['coach_id']][] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Smash Arena - Coach Schedule</title>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800;900&display=swap">

    <link rel="stylesheet" href="../Dashboard/Dashboard.css">
    <link rel="stylesheet" href="../Superadmin/AdminManagement.css">
    <link rel="stylesheet" href="ManageCoaches.css">
    <link rel="stylesheet" href="CoachSchedule.css">
</head>
<body>

    <?php include '../navbar.php'; ?>

    <main class="content">
        <div class="manage-container">

            <header class="management-header">
                <div>
                    <h1>Coach Schedule</h1>
                    <p>Track coach availability and manage their schedule.</p>
                </div>
                <div class="btn-add-group">
                    <a href="ManageCoaches.php" class="cs-btn-back" style="text-decoration:none;">
                        <i class="fas fa-chevron-left"></i> Back
                    </a>
                    <button class="btn-add-account" type="button" onclick="openHistoryModal()">
                        <i class="fas fa-clock-rotate-left"></i> History Log
                    </button>
                </div>
            </header>

            <!-- Today's Status Banner -->
            <?php if (!empty($today_unavail)): ?>
            <div class="cs-today-banner">
                <div class="cs-today-title">
                    <i class="fas fa-calendar-day"></i>
                    Today — <?php echo date('l, d M Y'); ?>
                </div>
                <div class="cs-today-chips">
                    <?php foreach ($today_unavail as $coach_id => $records):
                        $first  = $records[0];
                        $status = $first['status'];
                        $img    = $first['profile_img']
                            ? '../../Pictures/Admin_Module/coaches/' . htmlspecialchars($first['profile_img'])
                            : '../../Pictures/Admin_Module/coaches/default.png';
                        $chip_class = match($status) {
                            'On Leave'     => 'chip-onleave',
                            'Sick'         => 'chip-sick',
                            'Off Day'      => 'chip-offday',
                            'Custom Hours' => 'chip-custom',
                            default        => 'chip-offday',
                        };
                    ?>
                    <div class="cs-today-chip <?php echo $chip_class; ?>">
                        <img src="<?php echo $img; ?>"
                             onerror="this.src='../../Pictures/Admin_Module/coaches/default.png'"
                             alt="">
                        <span><?php echo htmlspecialchars($first['coach_name']); ?></span>
                        <small><?php echo $status; ?></small>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php else: ?>
            <div class="cs-today-banner cs-today-all-ok">
                <i class="fas fa-circle-check"></i>
                All coaches are available today — <?php echo date('l, d M Y'); ?>
            </div>
            <?php endif; ?>

            <!-- Calendar Section -->
            <div class="cs-calendar-wrap">

                <!-- Month nav + filter -->
                <div class="cs-cal-header">
                    <div class="cs-cal-nav">
                        <button type="button" onclick="changeMonth(-1)"><i class="fas fa-chevron-left"></i></button>
                        <span id="cs-month-label"></span>
                        <button type="button" onclick="changeMonth(1)"><i class="fas fa-chevron-right"></i></button>
                    </div>
                    <div class="cs-cal-filter">
                        <label><i class="fas fa-user"></i> Filter Coach</label>
                        <select id="cs-filter-coach" onchange="loadMonth()">
                            <option value="0">All Coaches</option>
                            <?php foreach ($coaches as $c): ?>
                            <option value="<?php echo $c['id']; ?>"><?php echo htmlspecialchars($c['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <!-- Day-of-week headers -->
                <div class="cs-dow-row">
                    <?php foreach (['Sun','Mon','Tue','Wed','Thu','Fri','Sat'] as $dow): ?>
                        <div class="cs-dow"><?php echo $dow; ?></div>
                    <?php endforeach; ?>
                </div>

                <!-- Calendar grid (rendered by JS) -->
                <div class="cs-cal-grid" id="cs-cal-grid"></div>

            </div>

        </div>
    </main>

    <!-- Day Detail Modal -->
    <div class="modal-overlay" id="dayDetailModal">
        <div class="dd-modal-card">

            <!-- Modal Header -->
            <div class="dd-header">
                <div class="dd-header-left">
                    <div class="dd-header-icon"><i class="fas fa-calendar-day"></i></div>
                    <div>
                        <div class="dd-header-title" id="dd-date-label"></div>
                        <div class="dd-header-sub" id="dd-header-sub"></div>
                    </div>
                </div>
                <button class="modal-close" type="button" onclick="closeDayDetail()">&times;</button>
            </div>

            <!-- View Panel -->
            <div id="dd-view-panel">

                <div class="dd-panel-body">

                    <!-- Unavailabilities list -->
                    <div class="dd-section-label">
                        <i class="fas fa-user-slash"></i> Unavailable Coaches
                    </div>
                    <div id="dd-avail-list"></div>

                    <!-- Add new button -->
                    <button type="button" class="dd-btn-add-new" onclick="showAddPanel()">
                        <i class="fas fa-plus"></i> Set Unavailability
                    </button>

                </div>

            </div>

            <!-- Edit Panel (shown when Edit is clicked) -->
            <div id="dd-edit-panel" style="display:none;">

                <div class="dd-edit-banner">
                    <span id="dd-edit-banner-label"></span>
                    <button type="button" class="dd-btn-back" onclick="showViewPanel()">
                        <i class="fas fa-chevron-left"></i> Back
                    </button>
                </div>

                <div class="dd-panel-body">

                    <div class="dd-form-grid">

                        <div class="modal-field full-width">
                            <label>Status</label>
                            <select id="dd-edit-status" onchange="toggleDdCustomHours()">
                                <option value="On Leave">On Leave</option>
                                <option value="Sick">Sick</option>
                                <option value="Off Day">Off Day</option>
                                <option value="Custom Hours">Custom Hours (partial)</option>
                            </select>
                        </div>

                        <div id="dd-edit-custom-hours" style="display:none;" class="full-width">
                            <div class="dd-form-grid">
                                <div class="modal-field">
                                    <label>Unavailable From</label>
                                    <input type="time" id="dd-edit-start">
                                </div>
                                <div class="modal-field">
                                    <label>Until</label>
                                    <input type="time" id="dd-edit-end">
                                </div>
                            </div>
                        </div>

                        <div class="modal-field full-width">
                            <label>Reason <span class="dd-optional-tag">Optional</span></label>
                            <input type="text" id="dd-edit-reason" placeholder="e.g. Medical appointment">
                        </div>

                    </div>

                    <div id="dd-edit-conflict-warn" class="dd-conflict-warn" style="display:none;">
                        <i class="fas fa-triangle-exclamation"></i>
                        <span id="dd-edit-conflict-msg"></span>
                    </div>

                    <div class="dd-form-actions">
                        <button type="button" class="btn-modal-cancel" onclick="showViewPanel()">Cancel</button>
                        <button type="button" class="btn-modal-save" onclick="saveEdit()">
                            <i class="fas fa-floppy-disk"></i> Save Changes
                        </button>
                    </div>

                </div>

            </div>

            <!-- Add Panel (shown when + Set Unavailability is clicked) -->
            <div id="dd-add-panel" style="display:none;">

                <div class="dd-edit-banner">
                    <span>Set Unavailability</span>
                    <button type="button" class="dd-btn-back" onclick="showViewPanel()">
                        <i class="fas fa-chevron-left"></i> Back
                    </button>
                </div>

                <div class="dd-panel-body">

                    <div class="dd-form-grid">

                        <div class="modal-field full-width">
                            <label>Coach</label>
                            <div class="cs-search-wrap">
                                <input type="text" id="dd-coach-search" placeholder="Search coach name..." autocomplete="off" oninput="filterCoachSearch()">
                                <div class="cs-search-list" id="dd-coach-list">
                                    <?php foreach ($coaches as $c): ?>
                                    <div class="cs-search-item" data-id="<?php echo $c['id']; ?>" onclick="selectCoach(this)">
                                        <?php echo htmlspecialchars($c['name']); ?>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                                <input type="hidden" id="dd-coach-id">
                            </div>
                        </div>

                        <div class="modal-field full-width">
                            <label>Status</label>
                            <select id="dd-add-status" onchange="toggleAddCustomHours()">
                                <option value="On Leave">On Leave</option>
                                <option value="Sick">Sick</option>
                                <option value="Off Day">Off Day</option>
                                <option value="Custom Hours">Custom Hours (partial)</option>
                            </select>
                        </div>

                        <div id="dd-add-custom-hours" style="display:none;" class="full-width">
                            <div class="dd-form-grid">
                                <div class="modal-field">
                                    <label>Unavailable From</label>
                                    <input type="time" id="dd-add-start">
                                </div>
                                <div class="modal-field">
                                    <label>Until</label>
                                    <input type="time" id="dd-add-end">
                                </div>
                            </div>
                        </div>

                        <div class="modal-field full-width">
                            <label>Reason <span class="dd-optional-tag">Optional</span></label>
                            <input type="text" id="dd-add-reason" placeholder="e.g. Annual leave">
                        </div>

                    </div>

                    <div id="dd-add-conflict-warn" class="dd-conflict-warn" style="display:none;">
                        <i class="fas fa-triangle-exclamation"></i>
                        <span id="dd-add-conflict-msg"></span>
                    </div>

                    <div class="dd-form-actions">
                        <button type="button" class="btn-modal-cancel" onclick="showViewPanel()">Cancel</button>
                        <button type="button" class="btn-modal-save" onclick="saveDayDetail()">
                            <i class="fas fa-floppy-disk"></i> Save
                        </button>
                    </div>

                </div>

            </div>

        </div>
    </div>

    <!-- History Log Modal -->
    <div class="modal-overlay" id="historyModal">
        <div class="cs-history-modal-card">
            <div class="cs-history-modal-header">
                <h2><i class="fas fa-clock-rotate-left"></i> History Log</h2>
                <button class="modal-close" type="button" onclick="closeHistoryModal()">&times;</button>
            </div>

            <div class="cs-history-filter">
                <div class="modal-field">
                    <label>Coach</label>
                    <select id="hist-coach-filter">
                        <option value="0">All Coaches</option>
                        <?php foreach ($coaches as $c): ?>
                        <option value="<?php echo $c['id']; ?>"><?php echo htmlspecialchars($c['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="modal-field">
                    <label>From</label>
                    <input type="date" id="hist-from" value="<?php echo date('Y-m-01'); ?>">
                </div>
                <div class="modal-field">
                    <label>To</label>
                    <input type="date" id="hist-to" value="<?php echo date('Y-m-d'); ?>">
                </div>
                <button type="button" class="btn-filter-apply" onclick="loadHistory()">
                    <i class="fas fa-search"></i> Search
                </button>
            </div>

            <div style="padding:16px 20px 20px;overflow-y:auto;flex:1;">
                <div id="hist-list"></div>
            </div>
        </div>
    </div>

    <script>
        const AJAX_URL = 'coach_schedule_ajax.php';
    </script>

    <script src="../Dashboard/Dashboard.js"></script>
    <script src="CoachSchedule.js"></script>

    <?php include __DIR__ . '/../modal.php'; ?>
    <?php include __DIR__ . '/../scroll_top.php'; ?>
    <?php include __DIR__ . '/../toast/toast.php'; ?>

</body>
</html>
