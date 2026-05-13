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

    // Show current profile image, fall back to default if none
    const preview = document.getElementById('coach-modal-img-preview');
    preview.src   = img ? 'Pictures/coaches/' + img : 'Pictures/coaches/default.png';

    document.getElementById('coachEditModal').style.display = 'flex';
}

// Close the Edit Coach modal
function closeCoachEditModal() {
    document.getElementById('coachEditModal').style.display = 'none';
}

// Wait for the page to fully load before attaching event listeners
document.addEventListener('DOMContentLoaded', function () {

    // Preview the selected image before uploading
    document.getElementById('coach-img-input').addEventListener('change', function () {
        const file   = this.files[0];
        const reader = new FileReader();

        // Once the file is read, set it as the preview image source
        reader.onload = function (e) {
            document.getElementById('coach-modal-img-preview').src = e.target.result;
        };

        reader.readAsDataURL(file);
    });

    // Close the modal when clicking outside the modal card
    document.getElementById('coachEditModal').addEventListener('click', function (e) {
        if (e.target === this) closeCoachEditModal();
    });

});