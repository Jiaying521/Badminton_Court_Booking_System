// CoachProfile.js
// Handles the "change photo" flow on the coach's own profile page:
//   1. User picks an image from disk
//   2. Open the crop modal so they can resize/center the photo
//   3. On confirm, replace the visible avatar and stash the cropped data
//      inside a hidden input so PHP receives the new image on form submit.

let cropperInstance = null;

// Step 1 — when the user picks a file, read it and open the crop modal.
document.getElementById('photo-input').addEventListener('change', function () {
    const file = this.files[0];
    if (!file) return;

    const reader = new FileReader();
    reader.onload = function (e) {
        const cropImg = document.getElementById('crop-img');
        cropImg.src   = e.target.result;

        document.getElementById('cropModal').style.display = 'flex';

        // Throw away any previous cropper instance before starting a new one.
        if (cropperInstance) cropperInstance.destroy();
        cropperInstance = new Cropper(cropImg, {
            aspectRatio: 1,
            viewMode: 1,
            autoCropArea: 0.8
        });
    };
    reader.readAsDataURL(file);
});

// Step 2 — confirm crop: convert the cropped area to a base64 string
// and put it in the hidden input so the form submission carries it.
function applyCrop() {
    if (!cropperInstance) return;
    const canvas = cropperInstance.getCroppedCanvas({ width: 300, height: 300 });
    const dataUrl = canvas.toDataURL('image/png');

    document.getElementById('hero-avatar').src      = dataUrl;
    document.getElementById('cropped-img-data').value = dataUrl;

    cropperInstance.destroy();
    cropperInstance = null;
    document.getElementById('cropModal').style.display = 'none';
}

// Cancel crop — clear everything and close the modal without saving.
function cancelCrop() {
    if (cropperInstance) { cropperInstance.destroy(); cropperInstance = null; }
    document.getElementById('photo-input').value     = '';
    document.getElementById('cropModal').style.display = 'none';
}

// Click outside the crop card to close, same as clicking Cancel.
document.getElementById('cropModal').addEventListener('click', function (e) {
    if (e.target === this) cancelCrop();
});
