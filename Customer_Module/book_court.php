<?php
require_once __DIR__ . '/../config.php';
if(!isLoggedIn()) redirect('homepage.php');
$court_id = $_GET['court_id'] ?? 0;
if(!$court_id) redirect('dashboard.php');

$stmt = $pdo->prepare("SELECT * FROM courts WHERE id = ?");
$stmt->execute([$court_id]);
$court = $stmt->fetch();
if(!$court) redirect('dashboard.php');

// 如果是 Training Court，获取教练列表
$coaches = [];
if($court['court_type'] == 'Training') {
    $coachStmt = $pdo->query("SELECT * FROM coaches WHERE is_active = 1 ORDER BY price_per_hour");
    $coaches = $coachStmt->fetchAll();
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Book <?=htmlspecialchars($court['court_name'])?> | Smash Arena</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <style>
        * { box-sizing: border-box; }
        body { font-family:'Inter',sans-serif; background:#f5f9f0; padding:2rem; margin:0; }
        .container { max-width:800px; margin:0 auto; background:white; border-radius:32px; padding:2rem; box-shadow:0 12px 28px rgba(0,0,0,0.08); }
        h2 { color:#2b7e3a; margin-bottom:0.5rem; font-size:1.8rem; }
        .court-info { background:#eaf5e6; padding:1rem; border-radius:16px; margin-bottom:1.5rem; }
        .court-info div { margin-bottom:0.3rem; }
        .price-info { display:flex; gap:1rem; margin-top:0.5rem; flex-wrap:wrap; }
        .price-offpeak { color:#2b7e3a; background:#e0f0dc; padding:0.2rem 0.6rem; border-radius:20px; font-size:0.85rem; }
        .price-peak { color:#e67e22; background:#fff0e0; padding:0.2rem 0.6rem; border-radius:20px; font-size:0.85rem; }
        .training-badge { background:#2b7e3a; color:white; padding:0.2rem 0.8rem; border-radius:20px; font-size:0.7rem; display:inline-block; margin-left:0.5rem; }
        label { font-weight:600; display:block; margin-top:1.2rem; color:#1e2a2e; margin-bottom:0.3rem; }
        select, input, textarea { width:100%; padding:0.8rem; border:1.5px solid #dde4dc; border-radius:16px; background:#fefdf8; font-family:'Inter',sans-serif; font-size:1rem; }
        select:focus, input:focus, textarea:focus { outline:none; border-color:#2b7e3a; }
        .row-2cols { display:flex; gap:1rem; flex-wrap:wrap; }
        .row-2cols > div { flex:1; min-width:200px; }
        
        .slot-container { display:flex; flex-wrap:wrap; gap:0.6rem; margin-top:0.5rem; max-height:250px; overflow-y:auto; padding:0.5rem; border:1px solid #e0e0e0; border-radius:16px; background:#fafafa; }
        .slot-btn { background:#eaf5e6; border:1px solid #c2d5bb; padding:0.6rem 1rem; border-radius:40px; cursor:pointer; font-size:0.9rem; transition:0.2s; display:flex; flex-direction:column; align-items:center; min-width:85px; }
        .slot-btn:hover:not(.disabled) { background:#c2d5bb; transform:scale(1.02); }
        .slot-btn.selected { background:#2b7e3a; color:white; border-color:#2b7e3a; }
        .slot-btn.disabled { opacity:0.5; cursor:not-allowed; background:#e0e0e0; text-decoration:line-through; pointer-events:none; }
        .slot-time { font-weight:600; font-size:1rem; }
        .slot-price { font-size:0.7rem; opacity:0.9; }
        
        .hours-selector { display:flex; flex-wrap:wrap; gap:0.5rem; margin-top:0.5rem; }
        .hour-btn { background:#eaf5e6; border:1px solid #c2d5bb; padding:0.6rem 1rem; border-radius:40px; cursor:pointer; font-size:0.9rem; transition:0.2s; min-width:70px; text-align:center; }
        .hour-btn:hover { background:#c2d5bb; }
        .hour-btn.selected { background:#2b7e3a; color:white; border-color:#2b7e3a; }
        
        .price-breakdown { background:#f8f9fa; border-radius:16px; padding:1rem; margin-top:1.5rem; border-left:4px solid #2b7e3a; max-height:400px; overflow-y:auto; }
        .price-breakdown h4 { margin-bottom:0.8rem; color:#2b7e3a; margin-top:0; }
        .breakdown-item { display:flex; justify-content:space-between; padding:0.5rem 0; border-bottom:1px solid #e0e0e0; font-size:0.9rem; }
        .breakdown-total { display:flex; justify-content:space-between; padding:0.8rem 0 0 0; margin-top:0.5rem; font-weight:700; font-size:1.1rem; border-top:2px solid #2b7e3a; color:#2b7e3a; }
        
        button[type="submit"] { background:#2b7e3a; color:white; border:none; padding:0.9rem; border-radius:60px; width:100%; font-weight:700; font-size:1rem; margin-top:1.5rem; cursor:pointer; transition:0.2s; }
        button[type="submit"]:hover { background:#1f5a2a; transform:translateY(-2px); }
        button[type="submit"]:disabled { opacity:0.6; cursor:not-allowed; transform:none; }
        .back-link { display:inline-block; margin-top:1rem; color:#2b7e3a; text-decoration:none; }
        .back-link:hover { text-decoration:underline; }
        .help-text { font-size:0.75rem; color:#888; margin-top:0.2rem; }
        .loading { text-align:center; padding:1rem; color:#5a6e5c; }
        .error-msg { color:#e67e22; padding:0.5rem; text-align:center; background:#fff0e0; border-radius:12px; }
        .info-text { font-size:0.8rem; color:#2b7e3a; margin-top:0.5rem; padding:0.5rem; background:#e0f0dc; border-radius:12px; text-align:center; }
        .warning-text { font-size:0.8rem; color:#e67e22; margin-top:0.5rem; padding:0.5rem; background:#fff0e0; border-radius:12px; text-align:center; }
        
        .coach-container { display:flex; flex-direction:column; gap:0.8rem; margin-top:0.5rem; }
        .coach-option { display:flex; align-items:center; justify-content:space-between; padding:1rem; border:2px solid #e0e0e0; border-radius:16px; cursor:pointer; transition:0.2s; background:white; }
        .coach-option:hover { border-color:#2b7e3a; background:#eaf5e6; }
        .coach-option.selected { border-color:#2b7e3a; background:#e0f0dc; }
        .coach-info { flex:1; }
        .coach-name { font-weight:700; font-size:1.1rem; color:#1e2a2e; }
        .coach-specialty { font-size:0.8rem; color:#666; margin-top:0.2rem; }
        .coach-price { text-align:right; min-width:100px; }
        .coach-price .price { font-weight:700; color:#e67e22; font-size:1.2rem; }
        .coach-price .unit { font-size:0.7rem; color:#888; }
        .coach-badge { background:#2b7e3a; color:white; padding:0.2rem 0.5rem; border-radius:20px; font-size:0.7rem; display:inline-block; margin-top:0.3rem; }
        
        .coach-hours-selector { display:flex; flex-wrap:wrap; gap:0.5rem; margin-top:0.5rem; }
        .coach-hour-btn { background:#fff0e0; border:1px solid #e67e22; padding:0.5rem 1rem; border-radius:40px; cursor:pointer; font-size:0.85rem; transition:0.2s; min-width:60px; text-align:center; }
        .coach-hour-btn:hover { background:#e67e22; color:white; }
        .coach-hour-btn.selected { background:#e67e22; color:white; border-color:#e67e22; }
        .coach-section { background:#fff8f0; padding:1rem; border-radius:16px; margin-top:0.5rem; }
    </style>
</head>
<body>
<div class="container">
    <h2>🏸 Book <?=htmlspecialchars($court['court_name'])?>
        <?php if($court['court_type'] == 'Training'): ?>
            <span class="training-badge">🎯 Training Court</span>
        <?php endif; ?>
    </h2>
    <div class="court-info">
        <div><strong>📍 Location:</strong> <?=htmlspecialchars($court['location'] ?? 'Main Hall')?></div>
        <div><strong>🛠️ Facilities:</strong> <?=htmlspecialchars($court['facilities'] ?? 'Standard')?></div>
        <?php if($court['court_type'] == 'Training'): ?>
            <div><strong>🎓 Training Features:</strong> Professional coaching available</div>
        <?php endif; ?>
        <div class="price-info">
            <span class="price-offpeak">🕗 8am - 2pm: RM <?=$court['price_off_peak']?>/hour</span>
            <span class="price-peak">🕒 3pm - 1am: RM <?=$court['price_peak']?>/hour</span>
        </div>
    </div>

    <form id="bookingForm" action="process_booking.php" method="POST">
        <input type="hidden" name="court_id" value="<?=$court_id?>">
        <input type="hidden" id="selected_price" name="price" value="">
        <input type="hidden" id="total_hours" name="total_hours" value="">
        <input type="hidden" id="coach_price_total" name="coach_price_total" value="0">
        <input type="hidden" id="coach_id" name="coach_id" value="0">
        <input type="hidden" id="coach_hours" name="coach_hours" value="0">
        
        <label>📅 Select Date</label>
        <input type="text" id="datepicker" name="booking_date" placeholder="Click to select a date" required readonly>

        <div class="row-2cols">
            <div>
                <label>⏰ Start Time</label>
                <div id="timeSlotContainer" style="display:none;">
                    <div id="slotList" class="slot-container"></div>
                    <input type="hidden" id="selected_time" name="start_time" required>
                </div>
                <div id="noSlotsMessage" style="display:none;" class="error-msg">No available slots for this date</div>
            </div>
            <div>
                <label>⌛ Court Hours</label>
                <div id="hoursContainer" style="display:none;">
                    <div id="hoursList" class="hours-selector"></div>
                    <div class="help-text">How many hours you want to book the court</div>
                </div>
                <div id="maxHoursInfo" class="info-text" style="display:none;"></div>
            </div>
        </div>

        <!-- 只有 Training Court 才显示教练选项 -->
        <?php if($court['court_type'] == 'Training' && !empty($coaches)): ?>
        <div id="coachSection" style="display:none;">
            <label>🎓 Select Coach (Optional)</label>
            <div id="coachContainer" class="coach-container">
                <div class="coach-option" data-coach-id="0" data-coach-price="0" data-coach-name="No coach">
                    <div class="coach-info">
                        <div class="coach-name">📝 No coach (self-training)</div>
                        <div class="coach-specialty">Practice on your own</div>
                    </div>
                    <div class="coach-price">
                        <div class="price">FREE</div>
                    </div>
                </div>
                <?php foreach($coaches as $coach): ?>
                <div class="coach-option" data-coach-id="<?=$coach['id']?>" data-coach-price="<?=$coach['price_per_hour']?>" data-coach-name="<?=htmlspecialchars($coach['name'])?>">
                    <div class="coach-info">
                        <div class="coach-name"><?=htmlspecialchars($coach['name'])?></div>
                        <div class="coach-specialty"><?=htmlspecialchars($coach['specialty'])?></div>
                        <?php if($coach['id'] == 1): ?>
                            <span class="coach-badge">🏆 Popular</span>
                        <?php elseif($coach['id'] == 2): ?>
                            <span class="coach-badge">🎯 Best for Beginners</span>
                        <?php elseif($coach['id'] == 3): ?>
                            <span class="coach-badge">⭐ Advanced</span>
                        <?php endif; ?>
                    </div>
                    <div class="coach-price">
                        <div class="price">RM <?=$coach['price_per_hour']?></div>
                        <div class="unit">/ hour</div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            
            <!-- 教练小时数选择（只有当选择了教练才显示） -->
            <div id="coachHoursSection" style="display:none; margin-top:1rem;">
                <label>⏱️ Coach Hours</label>
                <div id="coachHoursList" class="coach-hours-selector"></div>
                <div class="help-text">How many hours you want the coach (max same as court hours)</div>
            </div>
        </div>
        <?php endif; ?>
        
        <label>📝 Special Requests (optional)</label>
        <textarea name="notes" rows="2" placeholder="e.g., need racket rental..."></textarea>
        
        <button type="submit" id="submitBtn" disabled>Proceed to Payment →</button>
    </form>
    <a href="dashboard.php" class="back-link">← Back to Courts</a>
</div>

<script>
    const courtId = <?=$court_id?>;
    const courtType = '<?=$court['court_type']?>';
    const offPeakPrice = <?=$court['price_off_peak']?>;
    const peakPrice = <?=$court['price_peak']?>;
    
    const dateInput = document.getElementById('datepicker');
    const timeSlotContainer = document.getElementById('timeSlotContainer');
    const slotList = document.getElementById('slotList');
    const noSlotsMessage = document.getElementById('noSlotsMessage');
    const selectedTimeInput = document.getElementById('selected_time');
    const hoursContainer = document.getElementById('hoursContainer');
    const hoursList = document.getElementById('hoursList');
    const maxHoursInfo = document.getElementById('maxHoursInfo');
    const selectedPriceInput = document.getElementById('selected_price');
    const totalHoursInput = document.getElementById('total_hours');
    const submitBtn = document.getElementById('submitBtn');
    const priceBreakdown = document.getElementById('priceBreakdown');
    const breakdownList = document.getElementById('breakdownList');
    const breakdownTotal = document.getElementById('breakdownTotal');
    const coachPriceTotalInput = document.getElementById('coach_price_total');
    const coachIdInput = document.getElementById('coach_id');
    const coachHoursInput = document.getElementById('coach_hours');
    const coachSection = document.getElementById('coachSection');
    const coachHoursSection = document.getElementById('coachHoursSection');
    const coachHoursList = document.getElementById('coachHoursList');

    let availableSlots = [];
    let selectedStartTime = null;
    let selectedStartHour = null;
    let selectedCourtHours = null;
    let maxAvailableHours = 0;
    let selectedCoachPrice = 0;
    let selectedCoachId = 0;
    let selectedCoachName = '';
    let selectedCoachHours = 0;

    // 辅助函数：安全设置innerHTML
    function safeSetInnerHTML(element, html) {
        if (element) element.innerHTML = html;
    }

    // 教练选择（仅 Training Court）
    if(document.getElementById('coachContainer')) {
        document.querySelectorAll('.coach-option').forEach(opt => {
            opt.addEventListener('click', function() {
                document.querySelectorAll('.coach-option').forEach(o => o.classList.remove('selected'));
                this.classList.add('selected');
                selectedCoachPrice = parseInt(this.getAttribute('data-coach-price'));
                selectedCoachId = parseInt(this.getAttribute('data-coach-id'));
                selectedCoachName = this.getAttribute('data-coach-name');
                if(coachIdInput) coachIdInput.value = selectedCoachId;
                
                if(selectedCoachId > 0 && selectedCourtHours) {
                    if(coachHoursSection) coachHoursSection.style.display = 'block';
                    generateCoachHourButtons();
                } else if(selectedCoachId === 0) {
                    if(coachHoursSection) coachHoursSection.style.display = 'none';
                    selectedCoachHours = 0;
                    if(coachHoursInput) coachHoursInput.value = 0;
                    if(selectedCourtHours) calculatePrice();
                } else {
                    if(coachHoursSection) coachHoursSection.style.display = 'none';
                }
                
                if(selectedCourtHours) calculatePrice();
            });
        });
        // 默认选择 No coach
        const defaultCoach = document.querySelector('.coach-option[data-coach-id="0"]');
        if(defaultCoach) {
            defaultCoach.classList.add('selected');
            selectedCoachPrice = 0;
            selectedCoachId = 0;
            if(coachIdInput) coachIdInput.value = 0;
        }
    }

    // 初始化日期选择器
    flatpickr(dateInput, {
        dateFormat: "Y-m-d",
        minDate: "today",
        maxDate: new Date().fp_incr(30),
        onChange: function(selectedDates, dateStr) {
            if(dateStr && dateStr !== '') {
                loadSlots(dateStr);
                resetSelection();
            }
        }
    });

    async function loadSlots(date) {
        if(timeSlotContainer) timeSlotContainer.style.display = 'block';
        if(slotList) slotList.innerHTML = '<div class="loading">⏳ Loading available slots...</div>';
        if(noSlotsMessage) noSlotsMessage.style.display = 'none';
        if(hoursContainer) hoursContainer.style.display = 'none';
        if(maxHoursInfo) maxHoursInfo.style.display = 'none';
        if(coachSection) coachSection.style.display = 'none';
        resetSelection();
        
        try {
            const res = await fetch(`ajax_get_available_slots.php?court_id=${courtId}&date=${date}`);
            const slots = await res.json();
            availableSlots = slots;
            
            if(!slots || slots.length === 0) {
                if(slotList) slotList.innerHTML = '';
                if(noSlotsMessage) noSlotsMessage.style.display = 'block';
                return;
            }
            
            const now = new Date();
            const today = new Date().toISOString().slice(0,10);
            const currentHour = now.getHours();
            const currentMinute = now.getMinutes();
            
            let html = '';
            let pastCount = 0;
            
            slots.forEach(slot => {
                let hour = parseInt(slot.time.split(':')[0]);
                let price = (hour >= 8 && hour < 14) ? offPeakPrice : peakPrice;
                
                let isPastTime = false;
                if (date === today) {
                    if (hour < currentHour) {
                        isPastTime = true;
                        pastCount++;
                    } else if (hour === currentHour && currentMinute > 0) {
                        isPastTime = true;
                        pastCount++;
                    }
                }
                
                let disabledClass = isPastTime ? 'disabled' : '';
                let disabledAttr = isPastTime ? 'disabled' : '';
                
                html += `
                    <button type="button" class="slot-btn ${disabledClass}" data-time="${slot.time}" data-hour="${hour}" data-price="${price}" ${disabledAttr}>
                        <span class="slot-time">${slot.display}</span>
                        <span class="slot-price">RM ${price}</span>
                    </button>
                `;
            });
            if(slotList) slotList.innerHTML = html;
            
            if (pastCount > 0 && slotList && slotList.parentNode) {
                const pastInfo = document.createElement('div');
                pastInfo.className = 'warning-text';
                pastInfo.innerHTML = `⏰ ${pastCount} past time slot${pastCount > 1 ? 's have' : ' has'} been disabled (cannot book past time)`;
                slotList.parentNode.appendChild(pastInfo);
                setTimeout(() => pastInfo.remove(), 5000);
            }
            
            document.querySelectorAll('.slot-btn:not(.disabled)').forEach(btn => {
                btn.addEventListener('click', function() {
                    document.querySelectorAll('.slot-btn').forEach(b => b.classList.remove('selected'));
                    this.classList.add('selected');
                    selectedStartTime = this.getAttribute('data-time');
                    selectedStartHour = parseInt(this.getAttribute('data-hour'));
                    if(selectedTimeInput) selectedTimeInput.value = selectedStartTime;
                    
                    calculateMaxHours(selectedStartHour);
                    generateHourButtons();
                    if(hoursContainer) hoursContainer.style.display = 'block';
                    if(maxHoursInfo) maxHoursInfo.style.display = 'block';
                    if(courtType === 'Training' && coachSection) coachSection.style.display = 'block';
                    selectedCourtHours = null;
                    selectedCoachHours = 0;
                    if(coachHoursInput) coachHoursInput.value = 0;
                    if(coachHoursSection) coachHoursSection.style.display = 'none';
                    if(priceBreakdown) priceBreakdown.style.display = 'none';
                    if(submitBtn) submitBtn.disabled = true;
                });
            });
            
            if (document.querySelectorAll('.slot-btn:not(.disabled)').length === 0 && slotList) {
                slotList.innerHTML = '<div class="error-msg">⏰ No available slots for the remaining time today. Please choose another date.</div>';
            }
            
        } catch(e) {
            console.error("Error loading slots:", e);
            if(slotList) slotList.innerHTML = '<div class="error-msg">⚠️ Error loading slots. Please refresh.</div>';
        }
    }

    function calculateMaxHours(startHour) {
        let maxHours = 0;
        let checkHour = startHour;
        
        while(true) {
            let nextHour = checkHour + 1;
            let timeStr = (checkHour % 24).toString().padStart(2, '0') + ':00:00';
            let isAvailable = availableSlots.some(slot => slot.time === timeStr);
            
            if(!isAvailable) break;
            
            maxHours++;
            checkHour++;
            if(checkHour >= 25) break;
        }
        
        maxAvailableHours = maxHours;
        if(maxHoursInfo) {
            maxHoursInfo.innerHTML = `📢 Maximum available: ${maxAvailableHours} hour${maxAvailableHours > 1 ? 's' : ''} from your selected start time`;
            if (maxAvailableHours === 0) {
                maxHoursInfo.style.background = '#fff0e0';
                maxHoursInfo.style.color = '#e67e22';
            } else {
                maxHoursInfo.style.background = '#e0f0dc';
                maxHoursInfo.style.color = '#2b7e3a';
            }
        }
    }

    function generateHourButtons() {
        if(!hoursList) return;
        
        if(maxAvailableHours === 0) {
            hoursList.innerHTML = '<div class="error-msg">No more hours available after this time</div>';
            return;
        }
        
        let html = '';
        for(let i = 1; i <= maxAvailableHours; i++) {
            html += `<button type="button" class="hour-btn" data-hours="${i}">${i} hour${i > 1 ? 's' : ''}</button>`;
        }
        hoursList.innerHTML = html;
        
        document.querySelectorAll('.hour-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                document.querySelectorAll('.hour-btn').forEach(b => b.classList.remove('selected'));
                this.classList.add('selected');
                selectedCourtHours = parseInt(this.getAttribute('data-hours'));
                if(totalHoursInput) totalHoursInput.value = selectedCourtHours;
                
                if(selectedCoachId > 0) {
                    generateCoachHourButtons();
                    if(coachHoursSection) coachHoursSection.style.display = 'block';
                }
                calculatePrice();
            });
        });
    }

    function generateCoachHourButtons() {
        if(!coachHoursList) return;
        
        let maxCoachHours = selectedCourtHours || maxAvailableHours;
        if(maxCoachHours === 0) maxCoachHours = 1;
        
        let html = '';
        for(let i = 1; i <= maxCoachHours; i++) {
            let isSelected = (selectedCoachHours === i) ? 'selected' : '';
            html += `<button type="button" class="coach-hour-btn ${isSelected}" data-hours="${i}">${i} hour${i > 1 ? 's' : ''}</button>`;
        }
        coachHoursList.innerHTML = html;
        
        if(selectedCoachHours === 0 && maxCoachHours > 0) {
            selectedCoachHours = maxCoachHours;
            if(coachHoursInput) coachHoursInput.value = selectedCoachHours;
            const defaultBtn = document.querySelector('.coach-hour-btn[data-hours="' + selectedCoachHours + '"]');
            if(defaultBtn) defaultBtn.classList.add('selected');
        }
        
        document.querySelectorAll('.coach-hour-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                document.querySelectorAll('.coach-hour-btn').forEach(b => b.classList.remove('selected'));
                this.classList.add('selected');
                selectedCoachHours = parseInt(this.getAttribute('data-hours'));
                if(coachHoursInput) coachHoursInput.value = selectedCoachHours;
                calculatePrice();
            });
        });
    }

    function calculatePrice() {
        if(!selectedStartHour || !selectedCourtHours) return;
        
        let breakdownHtml = '';
        let totalCourtPrice = 0;
        let currentHour = selectedStartHour;
        
        for(let i = 0; i < selectedCourtHours; i++) {
            let slotPrice = 0;
            let priceType = '';
            let displayHour = currentHour % 24;
            
            if(displayHour >= 8 && displayHour < 14) {
                slotPrice = offPeakPrice;
                priceType = 'Off-Peak (8am-2pm)';
            } else {
                slotPrice = peakPrice;
                priceType = 'Peak (3pm-1am)';
            }
            
            let startTime12 = formatHour(displayHour);
            let endTime12 = formatHour(displayHour + 1);
            let timeRange = `${startTime12} - ${endTime12}`;
            
            breakdownHtml += `
                <div class="breakdown-item">
                    <span>🏸 ${timeRange} (${priceType})</span>
                    <span>RM ${slotPrice}</span>
                </div>
            `;
            totalCourtPrice += slotPrice;
            currentHour++;
        }
        
        let totalCoachPrice = 0;
        if(selectedCoachId > 0 && selectedCoachHours > 0) {
            totalCoachPrice = selectedCoachPrice * selectedCoachHours;
            breakdownHtml += `
                <div class="breakdown-item" style="background:#fff0e0;">
                    <span>🎓 Coach: ${selectedCoachName} (${selectedCoachHours} hour${selectedCoachHours > 1 ? 's' : ''})</span>
                    <span>RM ${totalCoachPrice}</span>
                </div>
            `;
        }
        
        let totalPrice = totalCourtPrice + totalCoachPrice;
        
        safeSetInnerHTML(breakdownList, breakdownHtml);
        safeSetInnerHTML(breakdownTotal, `<span>Total (Court: ${selectedCourtHours}h${selectedCoachHours > 0 ? ' + Coach: ' + selectedCoachHours + 'h' : ''})</span><span>RM ${totalPrice}</span>`);
        
        if(priceBreakdown) priceBreakdown.style.display = 'block';
        if(selectedPriceInput) selectedPriceInput.value = totalPrice;
        if(coachPriceTotalInput) coachPriceTotalInput.value = totalCoachPrice;
        if(submitBtn) submitBtn.disabled = false;
    }

    function formatHour(hour) {
        let displayHour = hour % 24;
        if(displayHour >= 12) {
            let h = displayHour === 12 ? 12 : displayHour - 12;
            return h + ':00 PM';
        } else {
            let h = displayHour === 0 ? 12 : displayHour;
            return h + ':00 AM';
        }
    }

    function resetSelection() {
        selectedStartTime = null;
        selectedStartHour = null;
        selectedCourtHours = null;
        selectedCoachHours = 0;
        if(selectedTimeInput) selectedTimeInput.value = "";
        if(selectedPriceInput) selectedPriceInput.value = "";
        if(totalHoursInput) totalHoursInput.value = "";
        if(coachHoursInput) coachHoursInput.value = "0";
        if(hoursContainer) hoursContainer.style.display = 'none';
        if(maxHoursInfo) maxHoursInfo.style.display = 'none';
        if(coachSection) coachSection.style.display = 'none';
        if(coachHoursSection) coachHoursSection.style.display = 'none';
        if(priceBreakdown) priceBreakdown.style.display = 'none';
        if(submitBtn) submitBtn.disabled = true;
    }
</script>
</body>
</html>