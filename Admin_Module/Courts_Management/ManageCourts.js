// Toggle collapsible filter panel
function toggleFilter() {
    var panel = document.getElementById('filterPanel');
    panel.classList.toggle('open');
}

// Open edit modal and populate fields with court data
function openCourtModal(id, name, type, location, facilities, priceOffPeak, pricePeak, isActive) {
    document.getElementById('modal-court-id').value        = id;
    document.getElementById('modal-delete-id').value      = id;
    document.getElementById('modal-court-name').value     = name;
    document.getElementById('modal-court-type').value     = type;
    document.getElementById('modal-location').value       = location;
    document.getElementById('modal-facilities').value     = facilities;
    document.getElementById('modal-price-off-peak').value = priceOffPeak;
    document.getElementById('modal-price-peak').value     = pricePeak;
    document.getElementById('modal-is-active').checked   = isActive === 1;

    if (typeof loadCourtPhotos === 'function') loadCourtPhotos(id);

    document.getElementById('courtModal').classList.add('active');
}

// Close edit modal
function closeCourtModal() {
    document.getElementById('courtModal').classList.remove('active');
}

// Add Court modal (falls back to AddCourt.php if the modal div is not on this page)
function openAddCourtModal() {
    var m = document.getElementById('addCourtModal');
    if (m) {
        m.classList.add('active');
        m.style.display = 'flex';
    } else {
        window.location.href = 'AddCourt.php';
    }
}
function closeAddCourtModal() {
    var m = document.getElementById('addCourtModal');
    if (m) { m.classList.remove('active'); m.style.display = 'none'; }
}

// Close modals when clicking outside the card
document.addEventListener('DOMContentLoaded', function () {
    var edit = document.getElementById('courtModal');
    if (edit) edit.addEventListener('click', function (e) {
        if (e.target === this) closeCourtModal();
    });
    var add = document.getElementById('addCourtModal');
    if (add) add.addEventListener('click', function (e) {
        if (e.target === this) closeAddCourtModal();
    });
});
