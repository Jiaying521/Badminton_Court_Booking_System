// AdminManagement.js
// Small UI helpers used by AdminManagement.php.

// Open the "Create New Admin" form card so the inputs become visible.
function toggleForm(id) {
    document.getElementById('adminForm').classList.remove('active');
    document.getElementById(id).classList.add('active');
}

// Open / close the filter panel that sits above the staff table.
function toggleFilter() {
    const panel = document.getElementById('filterPanel');
    panel.classList.toggle('open');
}
