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

    photoMode = 'edit';
    loadCourtPhotos(id);

    document.getElementById('courtModal').classList.add('active');
}

// Close edit modal
function closeCourtModal() {
    document.getElementById('courtModal').classList.remove('active');
}

// Add Court modal
function openAddCourtModal() {
    var m = document.getElementById('addCourtModal');
    if (m) {
        photoMode = 'add';
        resetAddPhotos();
        m.classList.add('active');
        m.style.display = 'flex';
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

    var photoInput = document.getElementById('photoInput');
    if (photoInput) photoInput.addEventListener('change', function () {
        if (!this.files || !this.files[0]) return;
        var reader = new FileReader();
        reader.onload = function (e) {
            var img = document.getElementById('cropImage');
            img.src = e.target.result;
            document.getElementById('cropOverlay').classList.add('active');
            if (photoCropper) photoCropper.destroy();
            photoCropper = new Cropper(img, {
                aspectRatio: 16 / 9,
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
        reader.readAsDataURL(this.files[0]);
    });
});

// ── Court photos (edit modal uploads instantly, add modal holds them until Save) ──
var photoCropper = null;
var photoSlot = null;
var photoCourtId = null;
var photoMode = 'edit';          // 'edit' = upload straight to server, 'add' = keep in the form
var photoSlotsData = {};         // edit mode: server photos per slot
var addPhotoPreviews = {};       // add mode: local preview URLs per slot

function currentPhoto(slot) {
    return photoMode === 'add' ? addPhotoPreviews[slot] : photoSlotsData[slot];
}

function makePhotoSlot(slot) {
    var photo = currentPhoto(slot);
    var div = document.createElement('div');
    div.className = 'photo-slot';
    div.onclick = function () { pickPhoto(slot); };
    div.innerHTML =
        '<span class="slot-tag ' + (slot === 'main' ? 'tag-main' : '') + '">' +
            (slot === 'main' ? 'MAIN' : 'GALLERY ' + slot) + '</span>' +
        (photo
            ? '<img src="' + photo + '" alt="Court photo">' +
              '<button type="button" class="slot-delete" title="Remove photo" ' +
              'onclick="event.stopPropagation(); deleteCourtPhoto(\'' + slot + '\')"><i class="fas fa-trash-alt"></i></button>'
            : '<i class="fas fa-cloud-upload-alt"></i><span>Upload</span>');
    return div;
}

function renderPhotoGrids() {
    var grid = document.getElementById(photoMode === 'add' ? 'addPhotosGrid' : 'courtPhotosGrid');
    if (!grid) return;
    grid.innerHTML = '';
    grid.appendChild(makePhotoSlot('main'));
    grid.appendChild(makePhotoSlot('1'));

    // "More" tile opens a second modal holding gallery slots 2-5
    var moreCount = ['2', '3', '4', '5'].filter(function (s) { return currentPhoto(s); }).length;
    var more = document.createElement('div');
    more.className = 'photo-slot slot-more';
    more.onclick = openMorePhotos;
    more.innerHTML = '<i class="fas fa-plus"></i><span>More Photos' + (moreCount > 0 ? ' (' + moreCount + ')' : '') + '</span>';
    grid.appendChild(more);

    var moreGrid = document.getElementById('morePhotosGrid');
    if (moreGrid) {
        moreGrid.innerHTML = '';
        ['2', '3', '4', '5'].forEach(function (slot) {
            moreGrid.appendChild(makePhotoSlot(slot));
        });
    }
}

function resetAddPhotos() {
    addPhotoPreviews = {};
    ['main', '1', '2', '3', '4', '5'].forEach(function (slot) {
        var input = document.getElementById('addPhotoFile-' + slot);
        if (input) input.value = '';
    });
    renderPhotoGrids();
}

function loadCourtPhotos(courtId) {
    photoCourtId = courtId;
    var grid = document.getElementById('courtPhotosGrid');
    if (!grid) return;
    grid.innerHTML = '<span style="color:#94a3b8; font-size:12px;">Loading photos...</span>';

    fetch('upload_court_image.php?action=list&court_id=' + courtId)
        .then(function (res) { return res.json(); })
        .then(function (data) {
            if (!data.success) {
                grid.innerHTML = '<span style="color:#ef4444; font-size:12px;">' + (data.message || 'Failed to load photos') + '</span>';
                return;
            }
            photoSlotsData = data.slots;
            renderPhotoGrids();
        })
        .catch(function () {
            grid.innerHTML = '<span style="color:#ef4444; font-size:12px;">Failed to load photos</span>';
        });
}

function openMorePhotos() {
    document.getElementById('morePhotosModal').classList.add('active');
}

function closeMorePhotos() {
    document.getElementById('morePhotosModal').classList.remove('active');
}

function pickPhoto(slot) {
    photoSlot = slot;
    var input = document.getElementById('photoInput');
    input.value = '';
    input.click();
}

function closeCrop() {
    document.getElementById('cropOverlay').classList.remove('active');
    if (photoCropper) { photoCropper.destroy(); photoCropper = null; }
}

function saveCrop() {
    if (!photoCropper) return;
    var btn = document.getElementById('cropSaveBtn');
    btn.disabled = true;
    photoCropper.getCroppedCanvas({ width: 1280, height: 720 }).toBlob(function (blob) {

        // Add mode: stash the cropped file into the form's hidden input, upload happens on Save Court
        if (photoMode === 'add') {
            var file = new File([blob], 'court_' + photoSlot + '.jpg', { type: 'image/jpeg' });
            var dt = new DataTransfer();
            dt.items.add(file);
            document.getElementById('addPhotoFile-' + photoSlot).files = dt.files;
            addPhotoPreviews[photoSlot] = URL.createObjectURL(blob);
            btn.disabled = false;
            closeCrop();
            renderPhotoGrids();
            return;
        }

        // Edit mode: upload straight to the server
        var formData = new FormData();
        formData.append('court_id', photoCourtId);
        formData.append('slot', photoSlot);
        formData.append('action', 'upload');
        formData.append('image', blob, 'court.jpg');
        fetch('upload_court_image.php', { method: 'POST', body: formData })
            .then(function (res) { return res.json(); })
            .then(function (data) {
                btn.disabled = false;
                if (data.success) {
                    closeCrop();
                    loadCourtPhotos(photoCourtId);
                } else {
                    alert(data.message || 'Upload failed');
                }
            })
            .catch(function () {
                btn.disabled = false;
                alert('Upload failed');
            });
    }, 'image/jpeg', 0.9);
}

function deleteCourtPhoto(slot) {
    if (!confirm('Remove this photo?')) return;

    // Add mode: just clear the pending file from the form
    if (photoMode === 'add') {
        delete addPhotoPreviews[slot];
        var input = document.getElementById('addPhotoFile-' + slot);
        if (input) input.value = '';
        renderPhotoGrids();
        return;
    }

    var formData = new FormData();
    formData.append('court_id', photoCourtId);
    formData.append('slot', slot);
    formData.append('action', 'delete');
    fetch('upload_court_image.php', { method: 'POST', body: formData })
        .then(function (res) { return res.json(); })
        .then(function (data) {
            if (data.success) loadCourtPhotos(photoCourtId);
            else alert(data.message || 'Failed to remove photo');
        });
}
