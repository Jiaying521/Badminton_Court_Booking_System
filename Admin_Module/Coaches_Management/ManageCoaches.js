let cropperInstance = null;

function handleAvailChange(select, coachId) {
    const val = select.value;
    if (val === 'customize') {
        /* reset the dropdown back to its current saved value */
        const opts = select.querySelectorAll('option');
        opts.forEach(o => { if (o.value !== 'customize') o.selected = o.defaultSelected; });
        window.location.href = 'CoachSchedule.php';
        return;
    }
    window.location.href = 'ManageCoaches.php?avail_id=' + coachId + '&avail_status=' + encodeURIComponent(val);
}

// Add Coach modal
function openAddCoachModal() {
    var m = document.getElementById('coachAddModal');
    if (m) { m.classList.add('active'); m.style.display = 'flex'; }
}
function closeAddCoachModal() {
    var m = document.getElementById('coachAddModal');
    if (m) { m.classList.remove('active'); m.style.display = 'none'; }
}
document.addEventListener('DOMContentLoaded', function () {
    var modal = document.getElementById('coachAddModal');
    if (modal) modal.addEventListener('click', function (e) {
        if (e.target === this) closeAddCoachModal();
    });
});

// Open / hide the filter panel above the coach table.
function toggleFilter() {
    const panel = document.getElementById('filterPanel');
    panel.classList.toggle('open');
}

// Fill the edit modal with the clicked row's data and show it.
// All values come from the row attributes in the HTML.
function openCoachEditModal(id, name, specialty, phone, gender, age, price, img) {
    document.getElementById('coach-modal-id').value        = id;
    document.getElementById('coach-modal-name').value      = name;
    document.getElementById('coach-modal-specialty').value = specialty;
    document.getElementById('coach-modal-phone').value     = phone;
    document.getElementById('coach-modal-gender').value    = gender;
    document.getElementById('coach-modal-age').value       = age;
    document.getElementById('coach-modal-price').value     = price;
    document.getElementById('cropped-img-data').value      = '';

    const preview = document.getElementById('coach-modal-img-preview');
    preview.src   = img ? '../../Pictures/Admin_Module/coaches/' + img : '../../Pictures/Admin_Module/coaches/default.png';

    // Start on the edit panel (not the crop panel).
    document.getElementById('editPanel').style.display = 'block';
    document.getElementById('cropPanel').style.display = 'none';
    document.getElementById('coachEditModal').style.display = 'flex';
}

// Close the edit modal. Also destroy any running cropper to free memory.
function closeCoachEditModal() {
    if(cropperInstance){
        cropperInstance.destroy();
        cropperInstance = null;
    }
    document.getElementById('coachEditModal').style.display = 'none';
}

// Apply the user's crop: turn the cropped canvas into base64,
// drop it into the preview img and into the hidden form field
// so PHP can save it on form submit.
function applyCrop() {
    if(!cropperInstance) return;

    const canvas = cropperInstance.getCroppedCanvas({ width: 300, height: 300 });
    const dataUrl = canvas.toDataURL('image/png');

    document.getElementById('coach-modal-img-preview').src = dataUrl;
    document.getElementById('cropped-img-data').value      = dataUrl;

    cropperInstance.destroy();
    cropperInstance = null;

    // Switch back to the edit panel.
    document.getElementById('cropPanel').style.display = 'none';
    document.getElementById('editPanel').style.display = 'block';
}

// Cancel the crop and go back to the edit panel without saving.
function cancelCrop() {
    if(cropperInstance){
        cropperInstance.destroy();
        cropperInstance = null;
    }
    document.getElementById('coach-img-input').value = '';
    document.getElementById('cropPanel').style.display = 'none';
    document.getElementById('editPanel').style.display = 'block';
}

document.addEventListener('DOMContentLoaded', function() {

    // When the admin picks a new photo, read the file and start cropper.
    document.getElementById('coach-img-input').addEventListener('change', function() {
        const file = this.files[0];
        if(!file) return;

        const reader = new FileReader();
        reader.onload = function(e) {
            const cropImg = document.getElementById('crop-img');
            cropImg.src   = e.target.result;

            // Swap the edit panel out for the crop panel.
            document.getElementById('editPanel').style.display = 'none';
            document.getElementById('cropPanel').style.display = 'block';

            if(cropperInstance){
                cropperInstance.destroy();
            }

            cropperInstance = new Cropper(cropImg, {
                aspectRatio: 1,
                viewMode: 1,
                autoCropArea: 0.8
            });
        };
        reader.readAsDataURL(file);
    });

    // Click outside the modal card to close it.
    document.getElementById('coachEditModal').addEventListener('click', function(e) {
        if(e.target === this) closeCoachEditModal();
    });

});
