// Toggle collapsible filter panel
function toggleBookingFilter() {
    var panel = document.getElementById('bookingFilterPanel');
    panel.classList.toggle('open');
}

// Toggle details row open/close
function toggleDetails(id, row) {
    var detailsRow = document.getElementById('details-' + id);
    var isOpen     = detailsRow.classList.contains('open');

    document.querySelectorAll('.details-row.open').forEach(function(r) { r.classList.remove('open'); });
    document.querySelectorAll('.main-row.open').forEach(function(r)    { r.classList.remove('open'); });

    if (!isOpen) {
        detailsRow.classList.add('open');
        row.classList.add('open');
    }
}

// Event delegation — more reliable than onclick on <tr>
document.addEventListener('DOMContentLoaded', function() {
    var tbody = document.querySelector('.data-table tbody');
    if (!tbody) return;

    tbody.addEventListener('click', function(e) {
        // Ignore clicks on interactive elements
        if (e.target.closest('select, input, button, a, label, .bulk-col')) return;

        var row = e.target.closest('tr.main-row');
        if (!row) return;

        var id = parseInt(row.id.replace('booking-row-', ''));
        toggleDetails(id, row);
    });
});

// Open edit modal and populate fields with current booking data
function openEditModal(id, date, startTime, endTime, courtId, coachId, sessionType, notes) {
    document.getElementById('modal-booking-id').value   = id;
    document.getElementById('modal-booking-date').value = date;
    document.getElementById('edit-start-time').value = startTime + ':00';
    document.getElementById('edit-end-time').value   = endTime;
    document.getElementById('modal-session-type').value = sessionType;
    document.getElementById('modal-notes').value        = notes;

    setSearchSelectValue(document.getElementById('editCourtSearch'), courtId);
    setSearchSelectValue(document.getElementById('editCoachSearch'), coachId);

    document.getElementById('editModal').classList.add('active');
}

// Close edit modal
function closeEditModal() {
    document.getElementById('editModal').classList.remove('active');
}

// Close modal when clicking outside the card
document.getElementById('editModal').addEventListener('click', function(e) {
    if (e.target === this) closeEditModal();
});

// Open add booking modal
function openAddModal() {
    document.getElementById('addModal').classList.add('active');
    document.querySelectorAll('#addModal .search-select').forEach(resetSearchSelect);
    // Default Add modal coach to "No Coach" so the form can submit without a manual pick
    var addCoach = document.querySelector('#addModal [data-search="addCoach"]');
    if (addCoach) setSearchSelectValue(addCoach, 0);
}

// Search-select: filter list as user types, click item to pick.
// Applied to every element with class .search-select on the page (Player, Court, Coach in both add + edit modals).
function initSearchSelect(wrapper) {
    var input  = wrapper.querySelector('.search-select-input');
    var hidden = wrapper.querySelector('.search-select-value');
    var list   = wrapper.querySelector('.search-select-list');
    var empty  = wrapper.querySelector('.search-select-empty');
    if (!input || !hidden || !list) return;
    var items = list.querySelectorAll('.search-select-item');

    input.addEventListener('focus', function () { wrapper.classList.add('is-open'); });
    input.addEventListener('blur',  function () {
        // Delay so item mousedown can register before list collapses
        setTimeout(function () { wrapper.classList.remove('is-open'); }, 150);
    });

    input.addEventListener('input', function () {
        var q = this.value.trim().toLowerCase();
        var visibleCount = 0;
        items.forEach(function (el) {
            var name = el.getAttribute('data-name').toLowerCase();
            var match = name.indexOf(q) !== -1;
            el.style.display = match ? '' : 'none';
            if (match) visibleCount++;
        });
        if (empty) empty.style.display = visibleCount === 0 ? 'block' : 'none';
        wrapper.classList.add('is-open');
        hidden.value = '';
        items.forEach(function (el) { el.classList.remove('is-selected'); });
    });

    items.forEach(function (el) {
        el.addEventListener('mousedown', function (e) { e.preventDefault(); });
        el.addEventListener('click', function () {
            hidden.value = this.getAttribute('data-id');
            input.value  = this.getAttribute('data-name');
            items.forEach(function (other) { other.classList.remove('is-selected'); });
            this.classList.add('is-selected');
            wrapper.classList.remove('is-open');
        });
    });
}

function resetSearchSelect(wrapper) {
    var input  = wrapper.querySelector('.search-select-input');
    var hidden = wrapper.querySelector('.search-select-value');
    var empty  = wrapper.querySelector('.search-select-empty');
    if (input)  input.value  = '';
    if (hidden) hidden.value = '';
    if (empty)  empty.style.display = 'none';
    wrapper.querySelectorAll('.search-select-item').forEach(function (el) {
        el.classList.remove('is-selected');
        el.style.display = '';
    });
}

function setSearchSelectValue(wrapper, id) {
    if (!wrapper) return;
    var input  = wrapper.querySelector('.search-select-input');
    var hidden = wrapper.querySelector('.search-select-value');
    var empty  = wrapper.querySelector('.search-select-empty');
    var match  = wrapper.querySelector('.search-select-item[data-id="' + id + '"]');
    wrapper.querySelectorAll('.search-select-item').forEach(function (el) {
        el.classList.remove('is-selected');
        el.style.display = '';
    });
    if (empty) empty.style.display = 'none';
    if (match) {
        hidden.value = match.getAttribute('data-id');
        input.value  = match.getAttribute('data-name');
        match.classList.add('is-selected');
    } else {
        if (hidden) hidden.value = '';
        if (input)  input.value  = '';
    }
}

document.querySelectorAll('.search-select').forEach(initSearchSelect);

// Close add booking modal
function closeAddModal() {
    document.getElementById('addModal').classList.remove('active');
}

// Close add modal when clicking outside the card
document.getElementById('addModal').addEventListener('click', function(e) {
    if (e.target === this) closeAddModal();
});

// Scroll to top button
const scrollTopBtn = document.getElementById('scrollTopBtn');
window.addEventListener('scroll', () => {
    if (window.scrollY > 300) {
        scrollTopBtn.classList.add('show');
    } else {
        scrollTopBtn.classList.remove('show');
    }
});

let bulkActive = false;

function toggleBulkMode() {
    bulkActive = !bulkActive;
    document.body.classList.toggle('bulk-mode', bulkActive);
    document.getElementById('bulkToggleText').textContent = 'Select';
    document.getElementById('bulkToggleBtn').classList.toggle('active', bulkActive);
    document.getElementById('bulkActionBar').classList.toggle('show', bulkActive);

    // Deselect all when exiting
    if (!bulkActive) {
        document.querySelectorAll('.row-check').forEach(c => c.checked = false);
        updateBulkCount();
    }
}

function updateBulkCount() {
    const count = document.querySelectorAll('.row-check:checked').length;
    document.getElementById('bulkCount').textContent = count + ' selected';
}

function submitBulk(action) {
    const ids = [...document.querySelectorAll('.row-check:checked')].map(c => c.value);
    if (!ids.length) { Toast.show('Please select at least one booking.', 'pending'); return; }
    if (!confirm(`${action.charAt(0).toUpperCase() + action.slice(1)} ${ids.length} booking(s)?`)) return;
    document.getElementById('bulkAction').value = action;
    document.getElementById('bulkIds').value = ids.join(',');
    document.getElementById('bulkForm').submit();
}

function openProofModal(bookingId) {
    document.getElementById('proof-booking-id').value = bookingId;
    document.getElementById('proof-booking-label').textContent = 'Booking #' + bookingId;
    document.getElementById('proofPreviewWrap').style.display = 'none';
    document.getElementById('proofDropPrompt').style.display = 'block';
    document.getElementById('proofFileInput').value = '';
    const m = document.getElementById('proofUploadModal');
    m.classList.add('active');
    m.style.display = 'flex';
}

function closeProofModal() {
    const m = document.getElementById('proofUploadModal');
    m.classList.remove('active');
    m.style.display = 'none';
}

function previewProof(input) {
    if (!input.files || !input.files[0]) return;
    const reader = new FileReader();
    reader.onload = function(e) {
        document.getElementById('proofPreviewImg').src = e.target.result;
        document.getElementById('proofPreviewWrap').style.display = 'block';
        document.getElementById('proofDropPrompt').style.display = 'none';
    };
    reader.readAsDataURL(input.files[0]);
}

function openProofView(filename, bookingId, isCoach) {
    const base = '../../Pictures/Admin_Module/booking_proofs/';
    document.getElementById('proofViewImg').src = base + filename;
    document.getElementById('proofDownloadLink').href = base + filename;
    document.getElementById('proof-view-id').textContent = bookingId;

    const changeBtn = document.getElementById('proofChangeBtn');
    const deleteBtn = document.getElementById('proofDeleteBtn');
    changeBtn.style.display = isCoach ? 'inline-flex' : 'none';
    deleteBtn.style.display = isCoach ? 'inline-flex' : 'none';

    if (isCoach) {
        document.getElementById('deleteProofBookingId').value = bookingId;
    }

    const m = document.getElementById('proofViewModal');
    m.classList.add('active');
    m.style.display = 'flex';
}

function openChangeProof() {
    const bookingId = document.getElementById('deleteProofBookingId').value;
    closeProofView();
    openProofModal(bookingId);
}

function confirmDeleteProof() {
    if (!confirm('Delete this proof photo? The booking will revert to Confirmed.')) return;
    document.getElementById('deleteProofForm').submit();
}

function closeProofView() {
    const m = document.getElementById('proofViewModal');
    m.classList.remove('active');
    m.style.display = 'none';
}

// Prevent adding/editing bookings to a date/time that has already passed
(function () {
    function validateBookingForm(form, dateInput) {
        form.addEventListener('submit', function (e) {
            var startInput = form.querySelector('input[name="start_time"]');
            var endInput   = form.querySelector('input[name="end_time"]');

            if (!dateInput.value || !startInput.value || !endInput.value) return;

            var chosen = new Date(dateInput.value + 'T' + startInput.value);
            var now = new Date();

            if (chosen < now) {
                e.preventDefault();
                alert('That time slot is in the past. Please choose a future date/time.');
                return;
            }

            if (endInput.value <= startInput.value) {
                e.preventDefault();
                alert('End time must be after start time.');
            }
        });
    }

    var addDateInput = document.getElementById('add-booking-date');
    var addForm = document.querySelector('#addModal form');
    if (addDateInput && addForm) {
        addDateInput.min = new Date().toISOString().split('T')[0];
        validateBookingForm(addForm, addDateInput);
    }

    var editDateInput = document.getElementById('modal-booking-date');
    var editForm = document.querySelector('#editModal form');
    if (editDateInput && editForm) {
        editDateInput.min = new Date().toISOString().split('T')[0];
        validateBookingForm(editForm, editDateInput);
    }
})();

// Pagination: jump to page on Enter
document.querySelectorAll('.page-jump-input').forEach(function (input) {
    input.addEventListener('keydown', function (e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            input.closest('form').submit();
        }
    });
});

// ============================================================
// Time Slot Picker for Add/Edit Booking (reuses admin's own
// ajax_get_available_slots.php, mirrors the customer booking flow)
// ============================================================
(function () {
    function setupSlotPicker(config) {
        const dateInput    = document.getElementById(config.dateInputId);
        const courtWrapper = document.querySelector(config.courtWrapperSelector);
        const slotPicker    = document.getElementById(config.slotPickerId);
        const hoursPicker   = document.getElementById(config.hoursPickerId);
        const startHidden   = document.getElementById(config.startHiddenId);
        const endHidden     = document.getElementById(config.endHiddenId);
        const bookingIdInput = config.bookingIdInputId ? document.getElementById(config.bookingIdInputId) : null;

        if (!dateInput || !courtWrapper || !slotPicker || !hoursPicker || !startHidden || !endHidden) return;

        let availableSlots = [];
        let selectedStartHour = null;
        let selectedStartTime = null;

        function getCourtId() {
            const hidden = courtWrapper.querySelector('.search-select-value');
            return hidden ? parseInt(hidden.value) || 0 : 0;
        }

        function resetPickers(message) {
            slotPicker.innerHTML = '<div class="slot-picker-hint">' + message + '</div>';
            hoursPicker.innerHTML = '<div class="slot-picker-hint">Select a start time first</div>';
            startHidden.value = '';
            endHidden.value = '';
            selectedStartHour = null;
            selectedStartTime = null;
        }

        async function loadSlots() {
            const courtId = getCourtId();
            const date = dateInput.value;

            if (!courtId || !date) {
                resetPickers('Select a court and date first');
                return;
            }

            slotPicker.innerHTML = '<div class="slot-picker-hint">Loading...</div>';
            hoursPicker.innerHTML = '<div class="slot-picker-hint">Select a start time first</div>';
            startHidden.value = '';
            endHidden.value = '';
            selectedStartHour = null;
            selectedStartTime = null;

            let url = `ajax_get_available_slots.php?court_id=${courtId}&date=${date}`;
            if (bookingIdInput && bookingIdInput.value) {
                url += `&exclude_booking_id=${bookingIdInput.value}`;
            }

            try {
                const res = await fetch(url);
                const slots = await res.json();
                availableSlots = slots;

                if (!slots || slots.length === 0) {
                    slotPicker.innerHTML = '<div class="slot-picker-empty">No available slots for this date</div>';
                    return;
                }

                const now = new Date();
                const today = now.toISOString().slice(0, 10);
                const currentHour = now.getHours();

                let html = '';
                slots.forEach(function (slot) {
                    const hour = parseInt(slot.time.split(':')[0]);
                    const isPast = (date === today && hour < currentHour);
                    if (isPast) return; // don't even show past hours today
                    html += `<button type="button" class="slot-btn" data-time="${slot.time}" data-hour="${hour}">${slot.display}</button>`;
                });

                if (html === '') {
                    slotPicker.innerHTML = '<div class="slot-picker-empty">No available slots for this date</div>';
                    return;
                }

                slotPicker.innerHTML = html;

                slotPicker.querySelectorAll('.slot-btn').forEach(function (btn) {
                    btn.addEventListener('click', function () {
                        slotPicker.querySelectorAll('.slot-btn').forEach(function (b) { b.classList.remove('slot-selected'); });
                        this.classList.add('slot-selected');
                        selectedStartTime = this.dataset.time;
                        selectedStartHour = parseInt(this.dataset.hour);
                        startHidden.value = selectedStartTime;
                        endHidden.value = '';
                        generateHourButtons();
                    });
                });
            } catch (e) {
                slotPicker.innerHTML = '<div class="slot-picker-empty">Error loading slots</div>';
            }
        }

        function generateHourButtons() {
            let maxHours = 0;
            let checkHour = selectedStartHour;
            while (true) {
                const timeStr = (checkHour % 24).toString().padStart(2, '0') + ':00:00';
                const isAvail = availableSlots.some(function (s) { return s.time === timeStr; });
                if (!isAvail) break;
                maxHours++;
                checkHour++;
                if (checkHour >= 23) break; // cap at business close (10 PM start = max to 23:00)
            }

            if (maxHours === 0) {
                hoursPicker.innerHTML = '<div class="slot-picker-empty">No hours available</div>';
                return;
            }

            let html = '';
            for (let i = 1; i <= maxHours; i++) {
                html += `<button type="button" class="hour-btn" data-hours="${i}">${i} hour${i > 1 ? 's' : ''}</button>`;
            }
            hoursPicker.innerHTML = html;

            hoursPicker.querySelectorAll('.hour-btn').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    hoursPicker.querySelectorAll('.hour-btn').forEach(function (b) { b.classList.remove('hour-selected'); });
                    this.classList.add('hour-selected');
                    const hours = parseInt(this.dataset.hours);
                    const endHour = (selectedStartHour + hours) % 24;
                    endHidden.value = endHour.toString().padStart(2, '0') + ':00';
                });
            });
        }

        dateInput.addEventListener('change', loadSlots);

        // Court is a search-select: refresh slots when a court item is clicked
        courtWrapper.querySelectorAll('.search-select-item').forEach(function (item) {
            item.addEventListener('click', function () {
                setTimeout(loadSlots, 0); // let the existing click handler set the hidden value first
            });
        });
    }

    // Add Booking modal
    setupSlotPicker({
        dateInputId: 'add-booking-date',
        courtWrapperSelector: '#addModal [data-search="addCourt"]',
        slotPickerId: 'addSlotPicker',
        hoursPickerId: 'addHoursPicker',
        startHiddenId: 'add-start-time',
        endHiddenId: 'add-end-time'
    });

    // Edit Booking modal
    setupSlotPicker({
        dateInputId: 'modal-booking-date',
        courtWrapperSelector: '#editCourtSearch',
        slotPickerId: 'editSlotPicker',
        hoursPickerId: 'editHoursPicker',
        startHiddenId: 'edit-start-time',
        endHiddenId: 'edit-end-time',
        bookingIdInputId: 'modal-booking-id'
    });
})();