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

// ── Crop before upload (shared by the add and edit modals) ──────────────────
var productCropper = null;
var cropSourceInput = null;
var cropPreviewId = null;
var cropHintId = null;

function openProductCrop(input, previewId, hintId) {
    if (!input.files || !input.files[0]) return;
    cropSourceInput = input;
    cropPreviewId = previewId;
    cropHintId = hintId;

    var reader = new FileReader();
    reader.onload = function(e) {
        var img = document.getElementById('cropImage');
        img.src = e.target.result;
        document.getElementById('cropOverlay').classList.add('active');
        if (productCropper) productCropper.destroy();
        productCropper = new Cropper(img, {
            aspectRatio: 1,
            viewMode: 1,
            autoCropArea: 1,
            background: false,
            dragMode: 'none',
            movable: false,
            zoomable: false,
            zoomOnWheel: false,
            toggleDragModeOnDblclick: false
        });
    };
    reader.readAsDataURL(input.files[0]);
}

function closeCrop() {
    document.getElementById('cropOverlay').classList.remove('active');
    if (productCropper) { productCropper.destroy(); productCropper = null; }
    if (cropSourceInput) cropSourceInput.value = '';
}

function applyProductCrop() {
    if (!productCropper) return;
    productCropper.getCroppedCanvas({ width: 800, height: 800 }).toBlob(function(blob) {
        // Put the cropped file back into the form's file input so the normal POST uploads it
        var file = new File([blob], 'product.jpg', { type: 'image/jpeg' });
        var dt = new DataTransfer();
        dt.items.add(file);
        cropSourceInput.files = dt.files;

        var preview = document.getElementById(cropPreviewId);
        var hint    = document.getElementById(cropHintId);
        preview.src           = URL.createObjectURL(blob);
        preview.style.display = 'block';
        hint.textContent      = 'Click to change image';

        document.getElementById('cropOverlay').classList.remove('active');
        productCropper.destroy();
        productCropper = null;
        cropSourceInput = null;
    }, 'image/jpeg', 0.9);
}

// Crop image before upload (edit modal)
function previewEditImage(input) {
    openProductCrop(input, 'modal-image-preview', 'modal-image-hint');
}

// Crop image before upload (add modal)
function previewAddImage(input) {
    openProductCrop(input, 'add-image-preview', 'add-image-hint');
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