<?php
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

// get specialisations for dropdown
$spec_stmt = $pdo->query("SELECT DISTINCT specialisation FROM admins WHERE is_doctor = 1 AND specialisation IS NOT NULL AND specialisation != '' ORDER BY specialisation");
$specialisations = $spec_stmt->fetchAll(PDO::FETCH_COLUMN);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <title>Book Appointment | CareConnect</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,300;14..32,400;14..32,500;14..32,600;14..32,700&display=swap" rel="stylesheet">
    <!-- Flatpickr -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(145deg, #f6fafd 0%, #eef2f8 100%);
            color: #1a2c3e;
            min-height: 100vh;
            padding: 2rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .container {
            max-width: 700px;
            width: 100%;
            margin: 0 auto;
            background: white;
            border-radius: 32px;
            padding: 2rem;
            box-shadow: 0 12px 28px rgba(0, 0, 0, 0.08);
            transition: all 0.2s;
        }
        h1 {
            font-size: 1.8rem;
            font-weight: 700;
            color: #0099ff;
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .subtitle {
            color: #5b6e8c;
            margin-bottom: 2rem;
            font-size: 0.9rem;
            border-left: 3px solid #0099ff;
            padding-left: 0.8rem;
        }
        .step-indicator {
            display: flex;
            justify-content: space-between;
            margin-bottom: 2rem;
            background: #f8fafc;
            padding: 0.8rem 1.5rem;
            border-radius: 60px;
            font-size: 0.8rem;
            font-weight: 500;
            color: #94a3b8;
        }
        .step-indicator .step-active {
            color: #0099ff;
            font-weight: 700;
        }
        label {
            font-weight: 600;
            display: block;
            margin-top: 1.2rem;
            color: #1e2a3e;
            font-size: 0.9rem;
        }
        .required {
            color: #e74c3c;
            margin-left: 0.2rem;
        }
        select, input, textarea {
            width: 100%;
            padding: 0.8rem 1rem;
            margin-top: 0.4rem;
            border: 1.5px solid #e2e8f0;
            border-radius: 60px;
            font-size: 1rem;
            font-family: 'Inter', sans-serif;
            transition: all 0.2s;
            background-color: #f8fafc;
        }
        select:focus, input:focus, textarea:focus {
            outline: none;
            border-color: #0099ff;
            box-shadow: 0 0 0 3px rgba(0, 153, 255, 0.2);
            background-color: white;
        }
        textarea {
            border-radius: 24px;
            resize: vertical;
        }
        button, .btn-submit {
            background: linear-gradient(105deg, #0099ff, #0077cc);
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
            box-shadow: 0 4px 10px rgba(0, 153, 255, 0.3);
        }
        button:hover:not(:disabled), .btn-submit:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(0, 153, 255, 0.4);
        }
        button:disabled, .btn-submit:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }
        .back-link {
            display: inline-block;
            margin-top: 1.5rem;
            color: #0099ff;
            text-decoration: none;
            text-align: center;
            width: 100%;
            font-weight: 500;
        }
        .back-link:hover {
            text-decoration: underline;
        }
        .error-message, .holiday-message {
            background: #fee2e2;
            border-left: 5px solid #e74c3c;
            color: #c0392b;
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
            background: #eef2ff;
            color: #1e2a3e;
            border: 1px solid #cbd5e1;
            padding: 0.5rem 1rem;
            border-radius: 40px;
            cursor: pointer;
            font-size: 0.9rem;
            transition: 0.2s;
        }
        .slot-btn.selected {
            background: #0099ff;
            color: white;
            border-color: #0099ff;
        }
        .slot-btn:hover:not(.selected) {
            background: #e2e8f0;
        }
        .info-text {
            font-size: 0.85rem;
            color: #5b6e8c;
            margin-top: 0.5rem;
        }
        /* Flatpickr 自定义禁用日期样式 */
        .flatpickr-day.disabled,
        .flatpickr-day.disabled:hover {
            background: #ffe6e6 !important;
            color: #e74c3c !important;
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
    <h1>📅 Book an Appointment</h1>
    <div class="subtitle">Fill in the details to schedule your visit</div>

    <div class="step-indicator">
        <span id="step1" class="step-active">1. Specialisation</span>
        <span>→</span>
        <span id="step2">2. Doctor</span>
        <span>→</span>
        <span id="step3">3. Date & Time</span>
        <span>→</span>
        <span id="step4">4. Confirm</span>
    </div>

    <div id="holidayWarning" class="holiday-message" style="display: none;"></div>
    <?php if (isset($_GET['error'])): ?>
        <div class="error-message">⚠️ <?php echo htmlspecialchars($_GET['error']); ?></div>
    <?php endif; ?>

    <form id="appointmentForm" action="process_appointment.php" method="POST">
        <label>Specialisation <span class="required">*</span></label>
        <select id="specialisation" required>
            <option value="">-- Select specialisation --</option>
            <?php foreach ($specialisations as $spec): ?>
                <option value="<?= htmlspecialchars($spec) ?>"><?= htmlspecialchars($spec) ?></option>
            <?php endforeach; ?>
        </select>

        <label>Doctor <span class="required">*</span></label>
        <select id="doctor_id" name="doctor_id" required disabled>
            <option value="">-- First select a specialisation --</option>
        </select>

        <label>Appointment Date <span class="required">*</span></label>
        <input type="text" id="appointment_date" name="appointment_date" placeholder="Select a date" readonly disabled>

        <div id="timeSlotContainer" style="display: none;">
            <label>Available Time Slots <span class="required">*</span></label>
            <div id="slotList" class="slot-container"></div>
            <input type="hidden" id="selected_time" name="appointment_time" required>
        </div>

        <label>Service Type</label>
        <select name="appointment_type">
            <option value="Consultation">General Consultation</option>
            <option value="Follow-up">Follow-up</option>
            <option value="Vaccination">Vaccination</option>
            <option value="Health Screening">Health Screening</option>
        </select>

        <label>Additional Notes (optional)</label>
        <textarea name="notes" rows="3" placeholder="Any symptoms or requests..."></textarea>

        <button type="submit" id="submitBtn" disabled>Confirm Booking →</button>
    </form>
    <a href="dashboard.php" class="back-link">← Back to Dashboard</a>
</div>

<script>
    // DOM elements
    const specialisationSelect = document.getElementById('specialisation');
    const doctorSelect = document.getElementById('doctor_id');
    const dateInput = document.getElementById('appointment_date');
    const timeSlotContainer = document.getElementById('timeSlotContainer');
    const slotList = document.getElementById('slotList');
    const selectedTimeInput = document.getElementById('selected_time');
    const submitBtn = document.getElementById('submitBtn');
    const holidayWarning = document.getElementById('holidayWarning');

    // step indicators
    const step1 = document.getElementById('step1');
    const step2 = document.getElementById('step2');
    const step3 = document.getElementById('step3');
    const step4 = document.getElementById('step4');

    let flatpickrInstance = null;

    // renew step highlight
    function updateStepHighlight() {
        const specVal = specialisationSelect.value;
        const docVal = doctorSelect.value;
        const dateVal = dateInput.value;
        const timeVal = selectedTimeInput.value;
        step1.classList.toggle('step-active', !specVal);
        step2.classList.toggle('step-active', specVal && !docVal);
        step3.classList.toggle('step-active', docVal && !dateVal);
        step4.classList.toggle('step-active', dateVal && timeVal);
    }

    // initialize flatpickr with disabled dates
    function initDatePicker(disabledDates) {
        flatpickrInstance = flatpickr(dateInput, {
            dateFormat: "Y-m-d",
            minDate: "today",
            maxDate: new Date().fp_incr(730), // 2年
            disable: disabledDates,
            onChange: function(selectedDates, dateStr, instance) {
                if (dateStr && doctorSelect.value) {
                    checkHolidayAndLoadSlots(dateStr);
                }
                updateStepHighlight();
            },
        });
    }

    // fetch holidays and initialize date picker
    fetch('ajax_get_holidays.php')
        .then(response => response.json())
        .then(holidays => {
            initDatePicker(holidays);
        })
        .catch(err => {
            console.error('Failed to load holidays:', err);
            initDatePicker([]);
        });

    // specialisation change
    specialisationSelect.addEventListener('change', function() {
        const specialisation = this.value;
        if (!specialisation) {
            resetDoctorAndDate();
            updateStepHighlight();
            return;
        }
        doctorSelect.innerHTML = '<option value="">Loading...</option>';
        doctorSelect.disabled = true;
        resetDateAndSlots();

        fetch(`ajax_get_doctors_by_specialisation.php?specialisation=${encodeURIComponent(specialisation)}`)
            .then(response => response.json())
            .then(data => {
                if (data.length === 0) {
                    doctorSelect.innerHTML = '<option value="">No doctors found</option>';
                    doctorSelect.disabled = true;
                } else {
                    let options = '<option value="">-- Select a doctor --</option>';
                    data.forEach(doc => {
                        options += `<option value="${doc.id}">${escapeHtml(doc.username)} (${escapeHtml(doc.specialisation)})</option>`;
                    });
                    doctorSelect.innerHTML = options;
                    doctorSelect.disabled = false;
                }
                updateStepHighlight();
            })
            .catch(error => {
                console.error(error);
                doctorSelect.innerHTML = '<option value="">Error loading doctors</option>';
            });
    });

    // doctor change
    doctorSelect.addEventListener('change', function() {
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

    function resetDoctorAndDate() {
        doctorSelect.innerHTML = '<option value="">-- First select a specialisation --</option>';
        doctorSelect.disabled = true;
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
        fetch(`ajax_check_holiday.php?date=${date}`)
            .then(response => response.json())
            .then(data => {
                if (data.isHoliday) {
                    holidayWarning.innerText = '⚠️ This date is a public holiday. Clinic is closed. Please choose another date.';
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
        const doctorId = doctorSelect.value;
        if (!doctorId || !date) return;

        slotList.innerHTML = '<div class="info-text">Loading available slots...</div>';
        timeSlotContainer.style.display = 'block';
        selectedTimeInput.value = '';
        submitBtn.disabled = true;

        fetch(`ajax_get_available_slots.php?doctor_id=${doctorId}&date=${date}`)
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

    function formatTime(timeStr) {
        return timeStr.substring(0, 5);
    }

    function escapeHtml(str) {
        return str.replace(/[&<>]/g, function(m) {
            if (m === '&') return '&amp;';
            if (m === '<') return '&lt;';
            if (m === '>') return '&gt;';
            return m;
        });
    }

    // initial step highlight
    updateStepHighlight();
</script>
</body>
</html>