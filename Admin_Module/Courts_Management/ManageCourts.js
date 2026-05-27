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

    document.getElementById('courtModal').classList.add('active');
}

// Close edit modal
function closeCourtModal() {
    document.getElementById('courtModal').classList.remove('active');
}

// Close modal when clicking outside the card
document.getElementById('courtModal').addEventListener('click', function(e) {
    if (e.target === this) closeCourtModal();
});
