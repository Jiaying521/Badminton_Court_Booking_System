// Toggle between Admin and Coach creation forms
function toggleForm(id) {
    document.getElementById('adminForm').classList.remove('active');
    document.getElementById('coachForm').classList.remove('active');
    document.getElementById(id).classList.add('active');
}

// Open the Edit Coach modal and fill in the existing data
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
    preview.src   = img ? 'Pictures/coaches/' + img : 'Pictures/coaches/default.png';

    document.getElementById('crop-area').style.display = 'none';
    document.getElementById('coachEditModal').style.display = 'flex';
}

// Close the Edit Coach modal
function closeCoachEditModal() {
    if (cropperInstance) {
        cropperInstance.destroy();
        cropperInstance = null;
    }
    document.getElementById('crop-area').style.display = 'none';
    document.getElementById('coachEditModal').style.display = 'none';
}

let cropperInstance = null;

function applyCrop() {
    if (!cropperInstance) return;

    const canvas = cropperInstance.getCroppedCanvas({ width: 300, height: 300 });
    const dataUrl = canvas.toDataURL('image/png');

    document.getElementById('coach-modal-img-preview').src = dataUrl;
    document.getElementById('cropped-img-data').value      = dataUrl;

    cropperInstance.destroy();
    cropperInstance = null;
    document.getElementById('crop-area').style.display = 'none';
}

function cancelCrop() {
    if (cropperInstance) {
        cropperInstance.destroy();
        cropperInstance = null;
    }
    document.getElementById('crop-area').style.display = 'none';
    document.getElementById('coach-img-input').value   = '';
}

document.addEventListener('DOMContentLoaded', function () {

    document.getElementById('coach-img-input').addEventListener('change', function () {
        const file = this.files[0];
        if (!file) return;

        const reader = new FileReader();
        reader.onload = function (e) {
            const cropImg = document.getElementById('crop-img');
            cropImg.src = e.target.result;

            document.getElementById('crop-area').style.display = 'block';

            if (cropperInstance) {
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

    document.getElementById('coachEditModal').addEventListener('click', function (e) {
        if (e.target === this) closeCoachEditModal();
    });

});