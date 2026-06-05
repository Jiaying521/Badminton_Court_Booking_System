// Toggle collapsible filter panel
function toggleFilter() {
    var panel = document.getElementById('filterPanel');
    panel.classList.toggle('open');
}

// Open edit modal and populate fields
function openAddonModal(id, name, category, price, stock, description, imageUrl) {
    document.getElementById('modal-product-id').value    = id;
    document.getElementById('modal-delete-id').value     = id;
    document.getElementById('modal-name').value          = name;
    document.getElementById('modal-category').value      = category;
    document.getElementById('modal-price').value         = price;
    document.getElementById('modal-stock').value         = stock;
    document.getElementById('modal-description').value   = description;

    var preview = document.getElementById('modal-image-preview');
    var hint    = document.getElementById('modal-image-hint');

    if (imageUrl) {
        preview.src           = '../../Pictures/Admin_Module/products/' + imageUrl;
        preview.style.display = 'block';
        hint.textContent      = 'Click to change image';
    } else {
        preview.style.display = 'none';
        hint.textContent      = 'Click to upload image';
    }

    document.getElementById('addonModal').classList.add('active');
}

// Close edit modal
function closeAddonModal() {
    document.getElementById('addonModal').classList.remove('active');
}

// Open add modal
function openAddAddonModal() {
    document.getElementById('addAddonModal').classList.add('active');
}

// Close add modal
function closeAddAddonModal() {
    document.getElementById('addAddonModal').classList.remove('active');
    document.getElementById('add-image-preview').style.display = 'none';
    document.getElementById('add-image-hint').textContent = 'Click to upload image';
}

// Preview image before upload (edit modal)
function previewEditImage(input) {
    var preview = document.getElementById('modal-image-preview');
    var hint    = document.getElementById('modal-image-hint');

    if (input.files && input.files[0]) {
        var reader = new FileReader();
        reader.onload = function(e) {
            preview.src           = e.target.result;
            preview.style.display = 'block';
            hint.textContent      = 'Click to change image';
        };
        reader.readAsDataURL(input.files[0]);
    }
}

// Preview image before upload (add modal)
function previewAddImage(input) {
    var preview = document.getElementById('add-image-preview');
    var hint    = document.getElementById('add-image-hint');

    if (input.files && input.files[0]) {
        var reader = new FileReader();
        reader.onload = function(e) {
            preview.src           = e.target.result;
            preview.style.display = 'block';
            hint.textContent      = 'Click to change image';
        };
        reader.readAsDataURL(input.files[0]);
    }
}

// Close modals when clicking outside
document.addEventListener('DOMContentLoaded', function() {
    var edit = document.getElementById('addonModal');
    if (edit) edit.addEventListener('click', function(e) {
        if (e.target === this) closeAddonModal();
    });

    var add = document.getElementById('addAddonModal');
    if (add) add.addEventListener('click', function(e) {
        if (e.target === this) closeAddAddonModal();
    });
});