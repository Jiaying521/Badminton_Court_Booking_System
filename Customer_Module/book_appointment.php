<?php
require_once 'config.php';
if(!isLoggedIn()) redirect('homepage.php');
$court_id = $_GET['court_id'] ?? 0;
if(!$court_id) redirect('dashboard.php');
$court = $pdo->prepare("SELECT * FROM courts WHERE id=?")->execute([$court_id])->fetch();
if(!$court) redirect('dashboard.php');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Book <?=htmlspecialchars($court['court_name'])?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <style>
        body { font-family:'Inter',sans-serif; background:#f5f9f0; padding:2rem; }
        .container { max-width:600px; margin:0 auto; background:white; border-radius:32px; padding:2rem; box-shadow:0 12px 28px rgba(0,0,0,0.08); }
        h2 { color:#2b7e3a; margin-bottom:1rem; }
        label { font-weight:600; display:block; margin-top:1.2rem; }
        select, input, textarea { width:100%; padding:0.8rem; margin-top:0.4rem; border:1.5px solid #dde4dc; border-radius:60px; background:#fefdf8; }
        .slot-container { display:flex; flex-wrap:wrap; gap:0.6rem; margin-top:1rem; }
        .slot-btn { background:#eaf5e6; border:1px solid #c2d5bb; padding:0.5rem 1rem; border-radius:40px; cursor:pointer; }
        .slot-btn.selected { background:#2b7e3a; color:white; border-color:#2b7e3a; }
        button[type="submit"] { background:#2b7e3a; color:white; border:none; padding:0.9rem; border-radius:60px; width:100%; font-weight:700; margin-top:2rem; cursor:pointer; }
        .back-link { display:inline-block; margin-top:1rem; color:#2b7e3a; text-decoration:none; }
    </style>
</head>
<body>
<div class="container">
    <h2>Book <?=htmlspecialchars($court['court_name'])?> – $<?=$court['price_per_hour']?>/hour</h2>
    <form id="bookingForm" action="process_booking.php" method="POST">
        <input type="hidden" name="court_id" value="<?=$court_id?>">
        <label>Select Date</label>
        <input type="text" id="datepicker" name="booking_date" required readonly>

        <div id="timeSlotContainer" style="display:none;">
            <label>Available Time Slots</label>
            <div id="slotList" class="slot-container"></div>
            <input type="hidden" id="selected_time" name="start_time" required>
        </div>

        <label>Session Type</label>
        <select name="session_type">
            <option>Casual Play</option><option>Training</option><option>Tournament</option><option>Friendly Game</option>
        </select>
        <label>Special Requests (optional)</label>
        <textarea name="notes" rows="2" placeholder="e.g., need racket rental"></textarea>
        <button type="submit" id="submitBtn" disabled>Proceed to Payment</button>
    </form>
    <a href="dashboard.php" class="back-link">← Back to Courts</a>
</div>
<script>
    const courtId = <?=$court_id?>;
    const dateInput = document.getElementById('datepicker');
    const timeSlotContainer = document.getElementById('timeSlotContainer');
    const slotList = document.getElementById('slotList');
    const selectedTimeInput = document.getElementById('selected_time');
    const submitBtn = document.getElementById('submitBtn');

    flatpickr(dateInput, {
        dateFormat: "Y-m-d",
        minDate: "today",
        maxDate: new Date().fp_incr(30),
        disable: async function(date) {
            let res = await fetch(`ajax_check_closed_day.php?date=${date.toISOString().slice(0,10)}`);
            let data = await res.json();
            return data.isClosed;
        },
        onChange: function(selectedDates, dateStr) {
            if(dateStr) loadSlots(dateStr);
            else { timeSlotContainer.style.display = 'none'; submitBtn.disabled = true; }
        }
    });

    async function loadSlots(date) {
        timeSlotContainer.style.display = 'block';
        slotList.innerHTML = '<div>Loading slots...</div>';
        selectedTimeInput.value = '';
        submitBtn.disabled = true;
        try {
            const res = await fetch(`ajax_get_available_slots.php?court_id=${courtId}&date=${date}`);
            const slots = await res.json();
            if(slots.length === 0) {
                slotList.innerHTML = '<div>No available slots for this date. Choose another day.</div>';
                return;
            }
            let html = '';
            slots.forEach(slot => {
                html += `<button type="button" class="slot-btn" data-time="${slot}">${slot}</button>`;
            });
            slotList.innerHTML = html;
            document.querySelectorAll('.slot-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    document.querySelectorAll('.slot-btn').forEach(b => b.classList.remove('selected'));
                    this.classList.add('selected');
                    selectedTimeInput.value = this.getAttribute('data-time');
                    submitBtn.disabled = false;
                });
            });
        } catch(e) {
            slotList.innerHTML = '<div>Error loading slots. Please refresh.</div>';
        }
    }
</script>
</body>
</html>