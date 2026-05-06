// Toggle collapsible filter panel
function toggleBookingFilter() {
    var panel = document.getElementById('bookingFilterPanel');
    panel.classList.toggle('open');
}

// Toggle details row open/close when main row is clicked
function toggleDetails(id, row) {
    var detailsRow = document.getElementById('details-' + id);
    var isOpen     = detailsRow.classList.contains('open');

    // Close all open detail rows first
    document.querySelectorAll('.details-row.open').forEach(function(r) {
        r.classList.remove('open');
    });
    document.querySelectorAll('.main-row.open').forEach(function(r) {
        r.classList.remove('open');
    });

    // If it was not open, open it
    if (!isOpen) {
        detailsRow.classList.add('open');
        row.classList.add('open');
    }
}

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
