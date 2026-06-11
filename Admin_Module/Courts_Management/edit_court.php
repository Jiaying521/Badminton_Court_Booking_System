<?php
session_start();
require_once __DIR__ . '/../toast/toast_init.php';
if (!isset($_SESSION['username']) || !in_array($_SESSION['role'], ['Superadmin', 'Admin'])) {
    header("Location: ../LoginPage.php");
    exit();
}

header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

$conn = mysqli_connect("localhost", "root", "", "badminton_hub");
require_once __DIR__ . '/../log_activity.php';
$username = $_SESSION['username'];
$role = $_SESSION['role'];
$display_name = $username;

// This page sits at Admin_Module root, so navbar links don't need a prefix.
$base_path = '../';

$error = "";

$court_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($court_id <= 0) {
    header("Location: ManageCourts.php");
    exit();
}

if (isset($_POST['update_court'])) {
    $court_name = mysqli_real_escape_string($conn, $_POST['court_name']);
    $court_type = mysqli_real_escape_string($conn, $_POST['court_type']);
    $location = mysqli_real_escape_string($conn, $_POST['location']);
    $facilities = mysqli_real_escape_string($conn, $_POST['facilities']);
    $price_off_peak = mysqli_real_escape_string($conn, $_POST['price_off_peak']);
    $price_peak = mysqli_real_escape_string($conn, $_POST['price_peak']);
    $is_active = isset($_POST['is_active']) ? 1 : 0;

    $check = mysqli_query($conn, "SELECT id FROM courts WHERE court_name = '$court_name' AND id != $court_id");
    if (mysqli_num_rows($check) > 0) {
        $error = "Court name already exists!";
    } else {
        $sql = "UPDATE courts
                SET court_name = '$court_name',
                    court_type = '$court_type',
                    location = '$location',
                    facilities = '$facilities',
                    price_off_peak = '$price_off_peak',
                    price_peak = '$price_peak',
                    is_active = '$is_active'
                WHERE id = $court_id";

        if (mysqli_query($conn, $sql)) {
            logActivity($conn, 'Update', 'Court Management', "Updated court: $court_name (ID $court_id)");
            header("Location: ManageCourts.php?success=1");
            exit();
        }

        $error = "Database error: " . mysqli_error($conn);
    }
}

$result = mysqli_query($conn, "SELECT * FROM courts WHERE id = $court_id");
$court = mysqli_fetch_assoc($result);
if (!$court) {
    header("Location: ManageCourts.php");
    exit();
}

// Find the existing photo file for a slot ('main' or 1-5), same naming the customer pages read
function findCourtPhoto($court_name, $slot) {
    $base = strtolower(str_replace(' ', '_', $court_name));
    $stem = ($slot === 'main') ? $base : $base . '_' . $slot;
    foreach (['jpg', 'jpeg', 'png'] as $ext) {
        if (file_exists(__DIR__ . '/../../Pictures/Admin_Module/courts/' . $stem . '.' . $ext)) {
            return '../../Pictures/Admin_Module/courts/' . $stem . '.' . $ext . '?v=' . filemtime(__DIR__ . '/../../Pictures/Admin_Module/courts/' . $stem . '.' . $ext);
        }
    }
    return null;
}

$photo_slots = ['main' => findCourtPhoto($court['court_name'], 'main')];
for ($i = 1; $i <= 5; $i++) {
    $photo_slots[(string)$i] = findCourtPhoto($court['court_name'], (string)$i);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Badminton Hub - Edit Court</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800;900&display=swap">
    <link rel="stylesheet" href="../Dashboard/Dashboard.css">
    <link rel="stylesheet" href="../Superadmin/AdminManagement.css">
    <link rel="stylesheet" href="AddCourt.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.2/cropper.min.css">
    <style>
        .photos-card {
            background: #fff;
            border: 1px solid var(--border);
            border-radius: 14px;
            padding: 22px;
            margin-top: 18px;
        }
        .photos-card h2 {
            font-size: 16px;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 4px;
        }
        .photos-card .photos-hint {
            font-size: 12.5px;
            color: var(--text-muted);
            margin-bottom: 16px;
        }
        .photos-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
            gap: 14px;
        }
        .photo-slot {
            position: relative;
            border: 1.5px dashed var(--border);
            border-radius: 12px;
            aspect-ratio: 16 / 9;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 6px;
            cursor: pointer;
            color: var(--text-muted);
            font-size: 12.5px;
            font-weight: 600;
            background: #f8fafc;
            transition: border-color 0.25s, background 0.25s;
        }
        .photo-slot:hover {
            border-color: var(--primary);
            background: #fffbeb;
        }
        .photo-slot i { font-size: 20px; }
        .photo-slot img {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .photo-slot .slot-tag {
            position: absolute;
            top: 7px;
            left: 7px;
            background: rgba(17,24,39,0.75);
            color: #fff;
            font-size: 10.5px;
            font-weight: 700;
            padding: 2px 9px;
            border-radius: 20px;
            letter-spacing: 0.4px;
            z-index: 2;
        }
        .photo-slot .slot-tag.tag-main { background: var(--primary); }
        .photo-slot .slot-delete {
            position: absolute;
            top: 6px;
            right: 6px;
            width: 26px;
            height: 26px;
            border: none;
            border-radius: 50%;
            background: rgba(239,68,68,0.92);
            color: #fff;
            font-size: 12px;
            cursor: pointer;
            z-index: 2;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: transform 0.2s;
        }
        .photo-slot .slot-delete:hover { transform: scale(1.1); }

        .crop-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(17,24,39,0.6);
            z-index: 2000;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .crop-overlay.active { display: flex; }
        .crop-card {
            background: #fff;
            border-radius: 14px;
            width: 100%;
            max-width: 620px;
            overflow: hidden;
        }
        .crop-card .crop-head {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 14px 20px;
            border-bottom: 1px solid var(--border);
        }
        .crop-card .crop-head h3 {
            font-size: 15px;
            font-weight: 700;
            color: #1f2937;
        }
        .crop-card .crop-close {
            border: none;
            background: none;
            font-size: 20px;
            color: var(--text-muted);
            cursor: pointer;
        }
        .crop-body {
            padding: 16px 20px;
            max-height: 60vh;
        }
        .crop-body img {
            display: block;
            max-width: 100%;
            max-height: 50vh;
        }
        .crop-actions {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            padding: 14px 20px;
            border-top: 1px solid var(--border);
        }
        .crop-actions .btn-crop-cancel {
            border: 1.5px solid var(--border);
            background: #fff;
            color: #374151;
            padding: 8px 18px;
            border-radius: 9px;
            font-family: 'Outfit', sans-serif;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
        }
        .crop-actions .btn-crop-save {
            border: none;
            background: var(--primary);
            color: #fff;
            padding: 8px 18px;
            border-radius: 9px;
            font-family: 'Outfit', sans-serif;
            font-size: 13px;
            font-weight: 700;
            cursor: pointer;
        }
        .crop-actions .btn-crop-save:disabled { opacity: 0.6; cursor: wait; }
    </style>
</head>
<body>
    <?php include '../navbar.php'; ?>

    <main class="content">
        <div class="manage-container">
            <header class="management-header">
                <div>
                    <h1>Edit Court</h1>
                    <p>Update court details, availability status and pricing.</p>
                </div>
                <div class="btn-add-group">
                    <a href="ManageCourts.php" class="btn-add-account" style="text-decoration:none; background:#6b7280;">
                        <i class="fas fa-arrow-left"></i> Back
                    </a>
                </div>
            </header>

<?php if ($error !== ""): $toasts[] = ['text' => $error, 'type' => 'error']; endif; ?>

            <div class="form-card active">
                <form method="POST" class="form-grid">
                    <div class="form-group">
                        <label>Court Name</label>
                        <input type="text" name="court_name" value="<?php echo htmlspecialchars($court['court_name']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label>Court Type</label>
                        <select name="court_type" required>
                            <option value="Standard" <?php echo ($court['court_type'] === 'Standard') ? 'selected' : ''; ?>>Standard</option>
                            <option value="Training" <?php echo ($court['court_type'] === 'Training') ? 'selected' : ''; ?>>Training</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Location</label>
                        <input type="text" name="location" value="<?php echo htmlspecialchars($court['location']); ?>">
                    </div>

                    <div class="form-group">
                        <label>Facilities</label>
                        <input type="text" name="facilities" value="<?php echo htmlspecialchars($court['facilities']); ?>">
                    </div>

                    <div class="form-group">
                        <label>Off-Peak Price (RM)</label>
                        <input type="number" name="price_off_peak" value="<?php echo htmlspecialchars($court['price_off_peak']); ?>" step="0.01" min="0" required>
                    </div>

                    <div class="form-group">
                        <label>Peak Price (RM)</label>
                        <input type="number" name="price_peak" value="<?php echo htmlspecialchars($court['price_peak']); ?>" step="0.01" min="0" required>
                    </div>

                    <div class="form-group">
                        <label>Status</label>
                        <input type="checkbox" name="is_active" <?php echo ((int)$court['is_active'] === 1) ? 'checked' : ''; ?>> Active
                    </div>

                    <div class="form-group" style="grid-column: 1 / -1;">
                        <button type="submit" name="update_court" class="btn-create">
                            <i class="fas fa-save"></i> Update Court
                        </button>
                    </div>
                </form>
            </div>

            <!-- Court Photos -->
            <div class="photos-card">
                <h2><i class="fas fa-images" style="color:var(--primary); margin-right:6px;"></i>Court Photos</h2>
                <p class="photos-hint">Main photo appears on the customer dashboard card. Gallery photos (up to 5) appear on the customer booking page. Click a slot to upload — photos are cropped to 16:9.</p>

                <div class="photos-grid">
                    <?php foreach ($photo_slots as $slot => $photo): ?>
                        <div class="photo-slot" onclick="pickPhoto('<?php echo $slot; ?>')">
                            <span class="slot-tag <?php echo $slot === 'main' ? 'tag-main' : ''; ?>">
                                <?php echo $slot === 'main' ? 'MAIN' : 'GALLERY ' . $slot; ?>
                            </span>
                            <?php if ($photo): ?>
                                <img src="<?php echo htmlspecialchars($photo); ?>" alt="Court photo">
                                <button type="button" class="slot-delete" title="Remove photo"
                                    onclick="event.stopPropagation(); deletePhoto('<?php echo $slot; ?>')">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                            <?php else: ?>
                                <i class="fas fa-cloud-upload-alt"></i>
                                <span>Upload Photo</span>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>

                <input type="file" id="photoInput" accept="image/png, image/jpeg" style="display:none;">
            </div>
        </div>
    </main>

    <!-- Crop modal -->
    <div class="crop-overlay" id="cropOverlay">
        <div class="crop-card">
            <div class="crop-head">
                <h3><i class="fas fa-crop-alt" style="color:var(--primary); margin-right:6px;"></i>Crop Photo</h3>
                <button type="button" class="crop-close" onclick="closeCrop()">&times;</button>
            </div>
            <div class="crop-body">
                <img id="cropImage" src="" alt="Crop preview">
            </div>
            <div class="crop-actions">
                <button type="button" class="btn-crop-cancel" onclick="closeCrop()">Cancel</button>
                <button type="button" class="btn-crop-save" id="cropSaveBtn" onclick="saveCrop()">
                    <i class="fas fa-check"></i> Crop & Upload
                </button>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.2/cropper.min.js"></script>
    <script>
    const courtId = <?php echo $court_id; ?>;
    let cropper = null;
    let currentSlot = null;

    function pickPhoto(slot) {
        currentSlot = slot;
        const input = document.getElementById('photoInput');
        input.value = '';
        input.click();
    }

    document.getElementById('photoInput').addEventListener('change', function() {
        if (!this.files || !this.files[0]) return;
        const reader = new FileReader();
        reader.onload = (e) => {
            const img = document.getElementById('cropImage');
            img.src = e.target.result;
            document.getElementById('cropOverlay').classList.add('active');
            if (cropper) cropper.destroy();
            cropper = new Cropper(img, {
                aspectRatio: 16 / 9,
                viewMode: 1,
                autoCropArea: 1,
                background: false
            });
        };
        reader.readAsDataURL(this.files[0]);
    });

    function closeCrop() {
        document.getElementById('cropOverlay').classList.remove('active');
        if (cropper) { cropper.destroy(); cropper = null; }
    }

    function saveCrop() {
        if (!cropper) return;
        const btn = document.getElementById('cropSaveBtn');
        btn.disabled = true;
        cropper.getCroppedCanvas({ width: 1280, height: 720 }).toBlob(async (blob) => {
            const formData = new FormData();
            formData.append('court_id', courtId);
            formData.append('slot', currentSlot);
            formData.append('action', 'upload');
            formData.append('image', blob, 'court.jpg');
            try {
                const res = await fetch('upload_court_image.php', { method: 'POST', body: formData });
                const data = await res.json();
                if (data.success) {
                    location.reload();
                } else {
                    alert(data.message || 'Upload failed');
                    btn.disabled = false;
                }
            } catch (e) {
                alert('Upload failed');
                btn.disabled = false;
            }
        }, 'image/jpeg', 0.9);
    }

    async function deletePhoto(slot) {
        if (!confirm('Remove this photo?')) return;
        const formData = new FormData();
        formData.append('court_id', courtId);
        formData.append('slot', slot);
        formData.append('action', 'delete');
        const res = await fetch('upload_court_image.php', { method: 'POST', body: formData });
        const data = await res.json();
        if (data.success) location.reload();
        else alert(data.message || 'Failed to remove photo');
    }
    </script>

    <script src="../Dashboard/Dashboard.js"></script>

    <!-- Scroll-to-top -->
    <?php include __DIR__ . '/../scroll_top.php'; ?>

    <!-- Toast notifications -->
    <?php include __DIR__ . '/../toast/toast.php'; ?>
</body>
</html>
