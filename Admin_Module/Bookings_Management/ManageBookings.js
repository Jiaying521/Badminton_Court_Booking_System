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
    document.getElementById('modal-start-time').value   = startTime;
    document.getElementById('modal-end-time').value     = endTime;
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
