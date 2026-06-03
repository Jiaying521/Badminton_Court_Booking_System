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
    document.getElementById('modal-court-id').value     = courtId;
    document.getElementById('modal-coach-id').value     = coachId;
    document.getElementById('modal-session-type').value = sessionType;
    document.getElementById('modal-notes').value        = notes;

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
}

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
    if (!ids.length) { alert('Please select at least one booking.'); return; }
    if (!confirm(`${action.charAt(0).toUpperCase() + action.slice(1)} ${ids.length} booking(s)?`)) return;
    document.getElementById('bulkAction').value = action;
    document.getElementById('bulkIds').value = ids.join(',');
    document.getElementById('bulkForm').submit();
}
