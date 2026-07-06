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

// Fill the edit modal with the clicked row's data and show it.
function openAdminEditModal(id, username, email, role, status) {
    document.getElementById('admin-modal-id').value       = id;
    document.getElementById('admin-modal-username').value  = username;
    document.getElementById('admin-modal-email').value     = email;

    const roleWrap   = document.getElementById('admin-modal-role-wrap');
    const statusWrap = document.getElementById('admin-modal-status-wrap');

    if (role === 'Superadmin') {
        // A Superadmin can only change his own username + email — hide role/status.
        roleWrap.style.display   = 'none';
        statusWrap.style.display = 'none';
    } else {
        roleWrap.style.display   = '';
        statusWrap.style.display = '';
        document.getElementById('admin-modal-role').value   = role;
        document.getElementById('admin-modal-status').value = status;
    }

    document.getElementById('adminEditModal').classList.add('active');
}

function closeAdminEditModal() {
    document.getElementById('adminEditModal').classList.remove('active');
}

// Click outside the modal card to close it.
document.addEventListener('DOMContentLoaded', function () {
    const modal = document.getElementById('adminEditModal');
    if (modal) modal.addEventListener('click', function (e) {
        if (e.target === this) closeAdminEditModal();
    });
});

document.querySelectorAll('.page-jump-input').forEach(function (input) {
    input.addEventListener('keydown', function (e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            input.closest('form').submit();
        }
    });
});
