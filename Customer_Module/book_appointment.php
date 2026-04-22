<?php
// book_appointment.php - Badminton Court Booking
require_once __DIR__ . '/../config.php';
if (!isset($_SESSION['user_id'])) {
    header('Location: homepage.php');
    exit;
}

// get user info
$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT name FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// get court types (previously specialisations)
$type_stmt = $pdo->query("SELECT DISTINCT court_type FROM courts WHERE is_active = 1 AND court_type IS NOT NULL AND court_type != '' ORDER BY court_type");
$court_types = $type_stmt->fetchAll(PDO::FETCH_COLUMN);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <title>Book Court | BadmintonHub</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,300;14..32,400;14..32,500;14..32,600;14..32,700&display=swap" rel="stylesheet">
    <!-- Flatpickr -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(145deg, #f8faf0 0%, #eef2e6 100%);
            color: #1e2a2e;
            min-height: 100vh;
            padding: 2rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .container {
            max-width: 700px;
            width: 100%;
            background: white;
            border-radius: 32px;
            padding: 2rem;
            box-shadow: 0 12px 28px rgba(0, 0, 0, 0.08);
            transition: all 0.2s;
        }
        h1 {
            font-size: 1.8rem;
            font-weight: 700;
            color: #2b7e3a;
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .subtitle {
            color: #5a6e5c;
            margin-bottom: 2rem;
            font-size: 0.9rem;
            border-left: 3px solid #2b7e3a;
            padding-left: 0.8rem;
        }
        .step-indicator {
            display: flex;
            justify-content: space-between;
            margin-bottom: 2rem;
            background: #f2f7ec;
            padding: 0.8rem 1.5rem;
            border-radius: 60px;
            font-size: 0.8rem;
            font-weight: 500;
            color: #8da08f;
        }
        .step-indicator .step-active { color: #2b7e3a; font-weight: 700; }
        label { font-weight: 600; display: block; margin-top: 1.2rem; color: #1e2a2e; font-size: 0.9rem; }
        .required { color: #e67e22; margin-left: 0.2rem; }
        select, input, textarea {
            width: 100%;
            padding: 0.8rem 1rem;
            margin-top: 0.4rem;
            border: 1.5px solid #dde4dc;
            border-radius: 60px;
            font-size: 1rem;
            font-family: 'Inter', sans-serif;
            transition: all 0.2s;
            background-color: #fefdf8;
        }
        select:focus, input:focus, textarea:focus {
            outline: none;
            border-color: #2b7e3a;
            box-shadow: 0 0 0 3px rgba(43, 126, 58, 0.2);
            background-color: white;
        }
        textarea { border-radius: 24px; resize: vertical; }
        button, .btn-submit {
            background: linear-gradient(105deg, #2b7e3a, #1f5a2a);
            color: white;
            border: none;
            padding: 0.9rem;
            border-radius: 60px;
            width: 100%;
            font-weight: 700;
            font-size: 1rem;
            cursor: pointer;
            margin-top: 1.8rem;
            transition: 0.2s;
            box-shadow: 0 4px 10px rgba(43, 126, 58, 0.3);
        }
        button:hover:not(:disabled), .btn-submit:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(43, 126, 58, 0.4);
        }
        button:disabled, .btn-submit:disabled { opacity: 0.6; cursor: not-allowed; transform: none; }
        .back-link {
            display: inline-block;
            margin-top: 1.5rem;
            color: #2b7e3a;
            text-decoration: none;
            text-align: center;
            width: 100%;
            font-weight: 500;
        }
        .back-link:hover { text-decoration: underline; }
        .error-message, .holiday-message {
            background: #fee2dd;
            border-left: 5px solid #e67e22;
            color: #b45f1b;
            padding: 0.8rem;
            border-radius: 20px;
            margin-bottom: 1rem;
            font-size: 0.85rem;
        }
        .slot-container {
            margin-top: 1rem;
            display: flex;
            flex-wrap: wrap;
            gap: 0.6rem;
        }
        .slot-btn {
            background: #eaf5e6;
            color: #1e2a2e;
            border: 1px solid #c2d5bb;
            padding: 0.5rem 1rem;
            border-radius: 40px;
            cursor: pointer;
            font-size: 0.9rem;
            transition: 0.2s;
        }
        .slot-btn.selected {
            background: #2b7e3a;
            color: white;
            border-color: #2b7e3a;
        }
        .slot-btn:hover:not(.selected) { background: #d0e6c8; }
        .info-text { font-size: 0.85rem; color: #5a6e5c; margin-top: 0.5rem; }
        .flatpickr-day.disabled, .flatpickr-day.disabled:hover {
            background: #ffe2cc !important;
            color: #e67e22 !important;
            text-decoration: line-through;
            cursor: not-allowed;
            opacity: 0.8;
        }
        @media (max-width: 640px) {
            body { padding: 1rem; }
            .container { padding: 1.5rem; }
            .step-indicator { font-size: 0.7rem; padding: 0.5rem 1rem; }
        }
    </style>
</head>
<body>
<div class="container">
    <h1>🏸 Book a Badminton Court</h1>
    <div class="subtitle">Reserve your court for a smashing game</div>

    <div class="step-indicator">
        <span id="step1" class="step-active">1. Court Type</span>
        <span>→</span>
        <span id="step2">2. Select Court</span>
        <span>→</span>
        <span id="step3">3. Date & Time</span>
        <span>→</span>
        <span id="step4">4. Confirm</span>
    </div>

    <div id="holidayWarning" class="holiday-message" style="display: none;"></div>
    <?php if (isset($_GET['error'])): ?>
        <div class="error-message">⚠️ <?php echo htmlspecialchars($_GET['error']); ?></div>
    <?php endif; ?>

    <form id="bookingForm" action="process_booking.php" method="POST">
        <label>Court Type <span class="required">*</span></label>
        <select id="court_type" required>
            <option value="">-- Select court type --</option>
            <?php foreach ($court_types as $type): ?>
                <option value="<?= htmlspecialchars($type) ?>"><?= htmlspecialchars($type) ?></option>
            <?php endforeach; ?>
        </select>

        <label>Choose Court <span class="required">*</span></label>
        <select id="court_id" name="court_id" required disabled>
            <option value="">-- First select a court type --</option>
        </select>

        <label>Booking Date <span class="required">*</span></label>
        <input type="text" id="booking_date" name="booking_date" placeholder="Select a date" readonly disabled>

        <div id="timeSlotContainer" style="display: none;">
            <label>Available Time Slots <span class="required">*</span></label>
            <div id="slotList" class="slot-container"></div>
            <input type="hidden" id="selected_time" name="booking_time" required>
        </div>

        <label>Session Type</label>
        <select name="session_type">
            <option value="Casual Play">Casual Play</option>
            <option value="Training">Training / Coaching</option>
            <option value="Tournament">Tournament / Match</option>
            <option value="Friendly Game">Friendly Game</option>
        </select>

        <label>Special Requests (optional)</label>
        <textarea name="notes" rows="3" placeholder="e.g., need equipment rental, coaching request..."></textarea>

        <button type="submit" id="submitBtn" disabled>Confirm Booking →</button>
    </form>
    <a href="dashboard.php" class="back-link">← Back to Dashboard</a>
</div>

<script>
    const courtTypeSelect = document.getElementById('court_type');
    const courtSelect = document.getElementById('court_id');
    const dateInput = document.getElementById('booking_date');
    const timeSlotContainer = document.getElementById('timeSlotContainer');
    const slotList = document.getElementById('slotList');
    const selectedTimeInput = document.getElementById('selected_time');
    const submitBtn = document.getElementById('submitBtn');
    const holidayWarning = document.getElementById('holidayWarning');
    const step1 = document.getElementById('step1');
    const step2 = document.getElementById('step2');
    const step3 = document.getElementById('step3');
    const step4 = document.getElementById('step4');

    let flatpickrInstance = null;

    function updateStepHighlight() {
        const typeVal = courtTypeSelect.value;
        const courtVal = courtSelect.value;
        const dateVal = dateInput.value;
        const timeVal = selectedTimeInput.value;
        step1.classList.toggle('step-active', !typeVal);
        step2.classList.toggle('step-active', typeVal && !courtVal);
        step3.classList.toggle('step-active', courtVal && !dateVal);
        step4.classList.toggle('step-active', dateVal && timeVal);
    }

    function initDatePicker(disabledDates) {
        flatpickrInstance = flatpickr(dateInput, {
            dateFormat: "Y-m-d",
            minDate: "today",
            maxDate: new Date().fp_incr(30), // 30 days ahead
            disable: disabledDates,
            onChange: function(selectedDates, dateStr) {
                if (dateStr && courtSelect.value) {
                    checkHolidayAndLoadSlots(dateStr);
                }
                updateStepHighlight();
            },
        });
    }

    fetch('ajax_get_closed_days.php')
        .then(response => response.json())
        .then(closedDays => {
            initDatePicker(closedDays);
        })
        .catch(err => {
            console.error('Failed to load closed days:', err);
            initDatePicker([]);
        });

    courtTypeSelect.addEventListener('change', function() {
        const courtType = this.value;
        if (!courtType) {
            resetCourtAndDate();
            updateStepHighlight();
            return;
        }
        courtSelect.innerHTML = '<option value="">Loading courts...</option>';
        courtSelect.disabled = true;
        resetDateAndSlots();

        fetch(`ajax_get_courts_by_type.php?court_type=${encodeURIComponent(courtType)}`)
            .then(response => response.json())
            .then(data => {
                if (data.length === 0) {
                    courtSelect.innerHTML = '<option value="">No courts available</option>';
                    courtSelect.disabled = true;
                } else {
                    let options = '<option value="">-- Select a court --</option>';
                    data.forEach(court => {
                        options += `<option value="${court.id}">${escapeHtml(court.court_name)} (${escapeHtml(court.court_type)}) - $${court.price_per_hour}/hr</option>`;
                    });
                    courtSelect.innerHTML = options;
                    courtSelect.disabled = false;
                }
                updateStepHighlight();
            })
            .catch(error => {
                console.error(error);
                courtSelect.innerHTML = '<option value="">Error loading courts</option>';
            });
    });

    courtSelect.addEventListener('change', function() {
        if (this.value) {
            if (flatpickrInstance) flatpickrInstance.clear();
            dateInput.disabled = false;
            dateInput.value = '';
            timeSlotContainer.style.display = 'none';
            submitBtn.disabled = true;
            holidayWarning.style.display = 'none';
        } else {
            resetDateAndSlots();
        }
        updateStepHighlight();
    });

    function resetCourtAndDate() {
        courtSelect.innerHTML = '<option value="">-- First select a court type --</option>';
        courtSelect.disabled = true;
        resetDateAndSlots();
        updateStepHighlight();
    }

    function resetDateAndSlots() {
        if (flatpickrInstance) flatpickrInstance.clear();
        dateInput.disabled = true;
        dateInput.value = '';
        timeSlotContainer.style.display = 'none';
        submitBtn.disabled = true;
        holidayWarning.style.display = 'none';
        updateStepHighlight();
    }

    function checkHolidayAndLoadSlots(date) {
        fetch(`ajax_check_closed_day.php?date=${date}`)
            .then(response => response.json())
            .then(data => {
                if (data.isClosed) {
                    holidayWarning.innerText = '⚠️ The sports hall is closed on this date (maintenance / holiday). Please choose another day.';
                    holidayWarning.style.display = 'block';
                    timeSlotContainer.style.display = 'none';
                    submitBtn.disabled = true;
                } else {
                    holidayWarning.style.display = 'none';
                    loadTimeSlots(date);
                }
                updateStepHighlight();
            })
            .catch(() => loadTimeSlots(date));
    }

    function loadTimeSlots(date) {
        const courtId = courtSelect.value;
        if (!courtId || !date) return;

        slotList.innerHTML = '<div class="info-text">Loading available time slots...</div>';
        timeSlotContainer.style.display = 'block';
        selectedTimeInput.value = '';
        submitBtn.disabled = true;

        fetch(`ajax_get_available_slots.php?court_id=${courtId}&date=${date}`)
            .then(response => response.json())
            .then(slots => {
                if (slots.length === 0) {
                    slotList.innerHTML = '<div class="info-text">No available slots for this date. Please choose another date.</div>';
                    submitBtn.disabled = true;
                } else {
                    let buttonsHtml = '';
                    slots.forEach(slot => {
                        buttonsHtml += `<button type="button" class="slot-btn" data-time="${slot}">${formatTime(slot)}</button>`;
                    });
                    slotList.innerHTML = buttonsHtml;
                    document.querySelectorAll('.slot-btn').forEach(btn => {
                        btn.addEventListener('click', function() {
                            document.querySelectorAll('.slot-btn').forEach(b => b.classList.remove('selected'));
                            this.classList.add('selected');
                            selectedTimeInput.value = this.getAttribute('data-time');
                            submitBtn.disabled = false;
                            updateStepHighlight();
                        });
                    });
                }
                updateStepHighlight();
            })
            .catch(error => {
                console.error(error);
                slotList.innerHTML = '<div class="info-text">Error loading slots. Please try again.</div>';
                submitBtn.disabled = true;
            });
    }

    function formatTime(timeStr) { return timeStr.substring(0, 5); }
    function escapeHtml(str) { return str.replace(/[&<>]/g, function(m) { if (m === '&') return '&amp;'; if (m === '<') return '&lt;'; if (m === '>') return '&gt;'; return m; }); }

    updateStepHighlight();
</script>
</body>
</html>