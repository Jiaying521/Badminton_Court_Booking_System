// CoachProfile.js
// Handles the "change photo" flow on the coach's own profile page:
//   1. User picks an image from disk
//   2. Open the crop modal so they can resize/center the photo
//   3. On confirm, replace the visible avatar and stash the cropped data
//      inside a hidden input so PHP receives the new image on form submit.

let cropperInstance = null;

let formDirty = false;

(function () {
    const form = document.querySelector('form[method="POST"]');
    if (!form) return;

    form.addEventListener('input',  () => { formDirty = true; });
    form.addEventListener('change', () => { formDirty = true; });
    form.addEventListener('submit', () => { formDirty = false; });

    window.addEventListener('beforeunload', function (e) {
        if (!formDirty) return;
        e.preventDefault();
        e.returnValue = '';
    });

    document.addEventListener('click', function (e) {
        if (!formDirty) return;
        const link = e.target.closest('a[href]');
        if (!link) return;
        const href = link.getAttribute('href');
        if (!href || href.startsWith('#') || href.startsWith('javascript')) return;
        e.preventDefault();
        if (confirm('You have unsaved changes. Leave without saving?')) {
            formDirty = false;
            window.location.href = link.href;
        }
    });
})();

// Step 1 — when the user picks a file, read it and open the crop modal.
document.getElementById('photo-input').addEventListener('change', function () {
    const file = this.files[0];
    if (!file) return;

    const reader = new FileReader();
    reader.onload = function (e) {
        const cropImg = document.getElementById('crop-img');
        cropImg.src   = e.target.result;

        document.getElementById('cropModal').style.display = 'flex';

        // Throw away any previous cropper instance before starting a new one.
        if (cropperInstance) cropperInstance.destroy();
        cropperInstance = new Cropper(cropImg, {
            aspectRatio: 1,
            viewMode: 1,
            autoCropArea: 0.8
        });
    };
    reader.readAsDataURL(file);
});

// Step 2 — confirm crop: convert the cropped area to a base64 string
// and put it in the hidden input so the form submission carries it.
function applyCrop() {
    if (!cropperInstance) return;
    const canvas = cropperInstance.getCroppedCanvas({ width: 300, height: 300 });
    const dataUrl = canvas.toDataURL('image/png');

    document.getElementById('hero-avatar').src        = dataUrl;
    document.getElementById('cropped-img-data').value = dataUrl;
    formDirty = true;

    cropperInstance.destroy();
    cropperInstance = null;
    document.getElementById('cropModal').style.display = 'none';
}

// Cancel crop — clear everything and close the modal without saving.
function cancelCrop() {
    if (cropperInstance) { cropperInstance.destroy(); cropperInstance = null; }
    document.getElementById('photo-input').value     = '';
    document.getElementById('cropModal').style.display = 'none';
}

/* Click outside the crop card to close */
document.getElementById('cropModal').addEventListener('click', function (e) {
    if (e.target === this) cancelCrop();
});

/* ── Schedule Modal ─────────────────────────── */

let schedYear     = new Date().getFullYear();
let schedMonth    = new Date().getMonth() + 1;
let schedSelected = new Date().toISOString().slice(0, 10);
let schedData     = { availability: {}, bookings: {} };

const MONTH_NAMES = [
    'January','February','March','April','May','June',
    'July','August','September','October','November','December'
];

const STATUS_COLORS = {
    'On Leave'     : 'status-onleave',
    'Sick'         : 'status-sick',
    'Off Day'      : 'status-offday',
    'Custom Hours' : 'status-custom',
};

const STATUS_CHIP_STYLE = {
    'On Leave'     : 'background:#fef3c7;color:#92400e;',
    'Sick'         : 'background:#fee2e2;color:#991b1b;',
    'Off Day'      : 'background:#f1f5f9;color:#475569;',
    'Custom Hours' : 'background:#ede9fe;color:#5b21b6;',
};

function openScheduleModal() {
    document.getElementById('scheduleModal').style.display = 'flex';

    /* pre-fill leave from/to with today */
    const today = new Date().toISOString().slice(0, 10);
    document.getElementById('sched-leave-from').value = today;
    document.getElementById('sched-leave-to').value   = today;

    loadMonth();
    updateFormDateLabel();
    loadCurrentChips();
}

function closeScheduleModal() {
    document.getElementById('scheduleModal').style.display = 'none';
}

document.getElementById('scheduleModal').addEventListener('click', function (e) {
    if (e.target === this) closeScheduleModal();
});

function changeMonth(dir) {
    schedMonth += dir;
    if (schedMonth > 12) { schedMonth = 1;  schedYear++; }
    if (schedMonth < 1)  { schedMonth = 12; schedYear--; }
    loadMonth();
}

function loadMonth() {
    fetch(`${AJAX_URL}?action=get_month&coach_id=${COACH_ID}&year=${schedYear}&month=${schedMonth}`)
        .then(r => r.json())
        .then(data => {
            if (!data.success) return;
            schedData = data;
            renderCalendar();
        });
}

function renderCalendar() {
    document.getElementById('sched-month-label').textContent = `${MONTH_NAMES[schedMonth - 1]} ${schedYear}`;

    const firstDay    = new Date(schedYear, schedMonth - 1, 1).getDay();
    const daysInMonth = new Date(schedYear, schedMonth, 0).getDate();
    const todayStr    = new Date().toISOString().slice(0, 10);

    let html = '';

    for (let i = 0; i < firstDay; i++) {
        html += '<div class="sched-day-cell empty"></div>';
    }

    for (let d = 1; d <= daysInMonth; d++) {
        const dateStr   = `${schedYear}-${String(schedMonth).padStart(2,'0')}-${String(d).padStart(2,'0')}`;
        const avails    = schedData.availability[dateStr] || [];
        const bookCount = schedData.bookings[dateStr] || 0;

        let statusClass = '';
        const fullDay = avails.find(a => !a.start_time);
        if (fullDay) statusClass = STATUS_COLORS[fullDay.status] || '';

        let classes = 'sched-day-cell';
        if (dateStr === todayStr)      classes += ' today';
        if (dateStr === schedSelected) classes += ' selected';
        if (statusClass)               classes += ` ${statusClass}`;

        const dot = bookCount > 0 ? '<div class="sched-booking-dot"></div>' : '';

        const SHORT = { 'On Leave':'Leave', 'Sick':'Sick', 'Off Day':'Off', 'Custom Hours':'Custom' };
        const statusTag = fullDay
            ? `<div class="sched-day-tag">${SHORT[fullDay.status] || fullDay.status}</div>`
            : '';

        html += `<div class="${classes}" onclick="selectDay('${dateStr}')">${d}${statusTag}${dot}</div>`;
    }

    document.getElementById('sched-cal-days').innerHTML = html;
}

function selectDay(dateStr) {
    schedSelected = dateStr;
    renderCalendar();
    updateFormDateLabel();
    loadCurrentChips();
}

function updateFormDateLabel() {
    const d       = new Date(schedSelected + 'T00:00:00');
    const todayStr = new Date().toISOString().slice(0, 10);
    const label   = schedSelected === todayStr
        ? 'Today — ' + d.toLocaleDateString('en-MY', { weekday:'long', day:'numeric', month:'long' })
        : d.toLocaleDateString('en-MY', { weekday:'long', day:'numeric', month:'long', year:'numeric' });
    document.getElementById('sched-form-date-label').textContent = label;
}

function loadCurrentChips() {
    fetch(`${AJAX_URL}?action=get_day&coach_id=${COACH_ID}&date=${schedSelected}`)
        .then(r => r.json())
        .then(data => {
            if (!data.success) return;
            renderCurrentChips(data.availability);
            renderDayBookings(schedSelected, data.bookings);
        });
}

function renderCurrentChips(avails) {
    const wrap = document.getElementById('sched-current-chips');

    if (avails.length === 0) {
        wrap.innerHTML = '';
        return;
    }

    wrap.innerHTML = avails.map(a => {
        const label = a.start_time
            ? `${a.status} ${a.start_time.slice(0,5)}–${a.end_time.slice(0,5)}`
            : a.status;
        const style = STATUS_CHIP_STYLE[a.status] || '';
        return `<span class="sched-chip" style="${style}">
                    ${label}
                    <button class="sched-chip-del" onclick="deleteSchedule(${a.id})" title="Remove">
                        <i class="fas fa-xmark"></i>
                    </button>
                </span>`;
    }).join('');
}

function renderDayBookings(dateStr, bookings) {
    const wrap  = document.getElementById('sched-upcoming-list');
    const label = document.getElementById('sched-booking-section-label');

    const d       = new Date(dateStr + 'T00:00:00');
    const todayStr = new Date().toISOString().slice(0, 10);
    const dateLabel = dateStr === todayStr
        ? 'Today'
        : d.toLocaleDateString('en-MY', { day:'numeric', month:'short' });

    label.textContent = `Bookings — ${dateLabel}`;

    if (!bookings || bookings.length === 0) {
        wrap.innerHTML = `
            <div class="sched-upcoming-empty">
                <i class="fas fa-inbox"></i>
                No Booking Found
            </div>`;
        return;
    }

    wrap.innerHTML = bookings.map(b => {
        const linkDate = b.booking_date || dateStr;
        const start    = b.start_time ? b.start_time.slice(0,5) : '';
        const end      = b.end_time   ? b.end_time.slice(0,5)   : '';
        return `<a class="sched-upcoming-card" href="../Bookings_Management/ManageBookings.php?date=${linkDate}">
                    <div class="sched-upcoming-card-date">${start} – ${end}</div>
                    <div class="sched-upcoming-card-info">${b.court_name} · <i class="fas fa-user" style="font-size:10px;"></i> ${b.customer_name}</div>
                </a>`;
    }).join('');
}

/* ── Upcoming bookings ───────────────────────── */
function loadUpcomingBookings() {
    fetch(`${AJAX_URL}?action=get_upcoming_bookings&coach_id=${COACH_ID}`)
        .then(r => r.json())
        .then(data => {
            const wrap = document.getElementById('sched-upcoming-list');

            if (!data.success || data.bookings.length === 0) {
                wrap.innerHTML = `
                    <div class="sched-upcoming-empty">
                        <i class="fas fa-inbox"></i>
                        No Booking Found
                    </div>`;
                return;
            }

            wrap.innerHTML = data.bookings.map(b => {
                const d    = new Date(b.booking_date + 'T00:00:00');
                const date = d.toLocaleDateString('en-MY', { weekday:'short', day:'numeric', month:'short' });
                const time = `${b.start_time.slice(0,5)} – ${b.end_time.slice(0,5)}`;
                return `<a class="sched-upcoming-card" href="../Bookings_Management/ManageBookings.php?date=${b.booking_date}">
                            <div class="sched-upcoming-card-date">${date}</div>
                            <div class="sched-upcoming-card-info">${b.court_name} · ${time}</div>
                        </a>`;
            }).join('');
        });
}

/* ── Save block hours ────────────────────────── */
function saveWorkingHours() {
    const from = document.getElementById('sched-block-start').value;
    const to   = document.getElementById('sched-block-end').value;

    if (!from || !to) {
        alert('Please set both start and end time.');
        return;
    }

    if (from >= to) {
        alert('End time must be later than start time.');
        return;
    }

    if (from < BIZ_OPEN) {
        alert(`Start time cannot be earlier than business open time (${BIZ_OPEN}).`);
        return;
    }

    if (to > BIZ_CLOSE) {
        alert(`End time cannot be later than business close time (${BIZ_CLOSE}).`);
        return;
    }

    const body = new FormData();
    body.append('action',         'save_working_hours');
    body.append('available_from', from);
    body.append('available_to',   to);

    fetch(AJAX_URL, { method: 'POST', body })
        .then(r => r.json())
        .then(data => {
            if (!data.success) { alert(data.message || 'Save failed'); return; }

            const btn = document.querySelector('[onclick="saveWorkingHours()"]');
            if (btn) {
                const orig = btn.innerHTML;
                btn.innerHTML = '<i class="fas fa-check"></i> Saved!';
                setTimeout(() => { btn.innerHTML = orig; }, 1800);
            }
        });
}

/* ── Save leave range ────────────────────────── */
function saveLeave() {
    const from   = document.getElementById('sched-leave-from').value;
    const to     = document.getElementById('sched-leave-to').value;
    const type   = document.getElementById('sched-leave-type').value;
    const reason = document.getElementById('sched-leave-reason').value.trim();

    if (!from || !to) {
        alert('Please set the date range.');
        return;
    }

    const body = new FormData();
    body.append('action',   'save_range');
    body.append('coach_id', COACH_ID);
    body.append('from',     from);
    body.append('to',       to);
    body.append('status',   type);
    body.append('reason',   reason);

    fetch(AJAX_URL, { method: 'POST', body })
        .then(r => r.json())
        .then(data => {
            if (!data.success) { alert(data.message || 'Save failed'); return; }

            const warn = document.getElementById('sched-leave-conflict');
            if (data.conflict) {
                document.getElementById('sched-leave-conflict-msg').textContent =
                    `Note: ${data.conflict_count} booking(s) exist within this period.`;
                warn.style.display = 'flex';
            } else {
                warn.style.display = 'none';
            }

            document.getElementById('sched-leave-reason').value = '';

            loadMonth();
            loadCurrentChips();
        });
}

/* ── Delete chip ─────────────────────────────── */
function deleteSchedule(id) {
    if (!confirm('Remove this schedule entry?')) return;

    const body = new FormData();
    body.append('action', 'delete');
    body.append('id',     id);

    fetch(AJAX_URL, { method: 'POST', body })
        .then(r => r.json())
        .then(data => {
            if (!data.success) { alert(data.message || 'Delete failed'); return; }
            loadMonth();
            loadCurrentChips();
        });
}
