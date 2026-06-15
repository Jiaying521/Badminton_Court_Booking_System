<?php

session_start();
require_once __DIR__ . '/../toast/toast_init.php';

if (!isset($_SESSION['username'])) {
    header("Location: ../LoginPage.php");
    exit();
}

if (!in_array($_SESSION['role'], ['Superadmin', 'Admin'])) {
    header("Location: ../LoginPage.php");
    exit();
}

header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

if (isset($_GET['success']) && $_GET['success'] === 'added')   { $toasts[] = ['text' => 'Product added successfully!',   'type' => 'success']; }
if (isset($_GET['success']) && $_GET['success'] === 'updated') { $toasts[] = ['text' => 'Product updated successfully!', 'type' => 'success']; }
if (isset($_GET['deleted']))                                    { $toasts[] = ['text' => 'Product removed successfully.', 'type' => 'pending']; }
if (isset($_GET['error']) && $_GET['error'] === 'image')        { $toasts[] = ['text' => 'Invalid image file type.',      'type' => 'error'];   }

$conn = mysqli_connect("localhost", "root", "", "badminton_hub");

$username     = $_SESSION['username'];
$role         = $_SESSION['role'];
$display_name = $username;

$base_path = '../';

function addonSortLink($label, $col, $current_sort, $current_dir, $next_dir, $filter_category, $filter_status, $filter_search) {
    $is_active = ($current_sort === $col);
    $dir       = $is_active ? $next_dir : 'desc';
    $arrow     = '';

    if ($is_active) {
        $arrow = $current_dir === 'ASC'
            ? ' <i class="fas fa-arrow-up sort-arrow active-arrow"></i>'
            : ' <i class="fas fa-arrow-down sort-arrow active-arrow"></i>';
    } else {
        $arrow = ' <i class="fas fa-sort sort-arrow"></i>';
    }

    $params = http_build_query([
        'sort'     => $col,
        'dir'      => $dir,
        'category' => $filter_category,
        'status'   => $filter_status,
        'search'   => $filter_search,
    ]);

    return "<a href='ManageAddOns.php?$params' class='sort-link'>$label$arrow</a>";
}

$filter_category = isset($_GET['category']) ? $_GET['category'] : '';
$filter_status   = isset($_GET['status'])   ? $_GET['status']   : '';
$filter_search   = isset($_GET['search'])   ? mysqli_real_escape_string($conn, $_GET['search']) : '';

$where_parts = [];
if ($filter_category !== '') $where_parts[] = "category = '$filter_category'";
if ($filter_status   !== '') $where_parts[] = "is_active = " . ($filter_status === 'Active' ? 1 : 0);
if ($filter_search   !== '') $where_parts[] = "(name LIKE '%$filter_search%' OR description LIKE '%$filter_search%')";

$where_sql = count($where_parts) > 0 ? "WHERE " . implode(" AND ", $where_parts) : "";

$allowed_sorts = ['id', 'name', 'category', 'price', 'stock', 'is_active'];
$sort_col = isset($_GET['sort']) && in_array($_GET['sort'], $allowed_sorts) ? $_GET['sort'] : 'id';
$sort_dir = isset($_GET['dir']) && $_GET['dir'] === 'asc' ? 'ASC' : 'DESC';
$next_dir = ($sort_dir === 'ASC') ? 'desc' : 'asc';

$result = mysqli_query($conn, "SELECT * FROM products $where_sql ORDER BY $sort_col $sort_dir");

$category_icons = [
    'racket'      => 'fa-table-tennis',
    'shuttlecock' => 'fa-circle',
    'grip'        => 'fa-hand-peace',
    'string'      => 'fa-wave-square',
    'snack'       => 'fa-cookie-bite',
    'drink'       => 'fa-glass-water',
];

function getProductImagePath($image_url) {
    if (empty($image_url)) return '';
    return '../../Pictures/Admin_Module/products/' . $image_url;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Smash Arena - Add-On Management</title>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800;900&display=swap">

    <link rel="stylesheet" href="../Dashboard/Dashboard.css">
    <link rel="stylesheet" href="../Superadmin/AdminManagement.css">
    <link rel="stylesheet" href="ManageAddOns.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.2/cropper.min.css">
    <style>
        .crop-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(17,24,39,0.6);
            z-index: 3000;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .crop-overlay.active { display: flex; }
        .crop-card {
            background: #fff;
            border-radius: 14px;
            width: 100%;
            max-width: 760px;
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
        }
        .crop-body img {
            display: block;
            width: 100%;
            height: 440px;
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
    </style>
</head>
<body>

    <?php include '../navbar.php'; ?>

    <main class="content">
        <div class="manage-container">

            <header class="management-header">
                <div>
                    <h1>Add-On Management</h1>
                    <p>Manage add-on products, pricing and availability for customers.</p>
                </div>
                <div class="btn-add-group">
                    <button class="btn-filter-toggle" onclick="toggleFilter()">
                        <i class="fas fa-filter"></i> Filter
                    </button>
                    <button type="button" class="btn-add-account" onclick="openAddAddonModal()">
                        <i class="fas fa-plus"></i> Add Product
                    </button>
                </div>
            </header>

            <!-- Collapsible Filter Panel -->
            <div class="filter-panel <?php echo ($filter_category || $filter_status || $filter_search) ? 'open' : ''; ?>" id="filterPanel">
                <form method="GET" class="filter-grid">
                    <div class="filter-field">
                        <label>Search</label>
                        <input type="text" name="search" placeholder="Product name, description..." value="<?php echo htmlspecialchars($filter_search); ?>">
                    </div>
                    <div class="filter-field">
                        <label>Category</label>
                        <select name="category">
                            <option value="">All Categories</option>
                            <option value="racket"      <?php echo ($filter_category === 'racket')      ? 'selected' : ''; ?>>Racket</option>
                            <option value="shuttlecock" <?php echo ($filter_category === 'shuttlecock') ? 'selected' : ''; ?>>Shuttlecock</option>
                            <option value="string"      <?php echo ($filter_category === 'string')      ? 'selected' : ''; ?>>String</option>
                            <option value="grip"        <?php echo ($filter_category === 'grip')        ? 'selected' : ''; ?>>Grip</option>
                            <option value="snack"       <?php echo ($filter_category === 'snack')       ? 'selected' : ''; ?>>Snack</option>
                            <option value="drink"       <?php echo ($filter_category === 'drink')       ? 'selected' : ''; ?>>Drink</option>
                        </select>
                    </div>
                    <div class="filter-field">
                        <label>Status</label>
                        <select name="status">
                            <option value="">All Status</option>
                            <option value="Active"   <?php echo ($filter_status === 'Active')   ? 'selected' : ''; ?>>Active</option>
                            <option value="Inactive" <?php echo ($filter_status === 'Inactive') ? 'selected' : ''; ?>>Inactive</option>
                        </select>
                    </div>
                    <div class="filter-actions">
                        <button type="submit" class="btn-filter-apply"><i class="fas fa-search"></i> Apply</button>
                        <a href="ManageAddOns.php" class="btn-filter-clear">Clear</a>
                    </div>
                </form>
            </div>

            <!-- Products Table -->
            <table class="data-table">
                <thead>
                    <tr>
                        <th><?php echo addonSortLink('ID', 'id', $sort_col, $sort_dir, $next_dir, $filter_category, $filter_status, $filter_search); ?></th>
                        <th><?php echo addonSortLink('Product', 'name', $sort_col, $sort_dir, $next_dir, $filter_category, $filter_status, $filter_search); ?></th>
                        <th><?php echo addonSortLink('Category', 'category', $sort_col, $sort_dir, $next_dir, $filter_category, $filter_status, $filter_search); ?></th>
                        <th><?php echo addonSortLink('Price (RM)', 'price', $sort_col, $sort_dir, $next_dir, $filter_category, $filter_status, $filter_search); ?></th>
                        <th><?php echo addonSortLink('Stock', 'stock', $sort_col, $sort_dir, $next_dir, $filter_category, $filter_status, $filter_search); ?></th>
                        <th><?php echo addonSortLink('Status', 'is_active', $sort_col, $sort_dir, $next_dir, $filter_category, $filter_status, $filter_search); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = mysqli_fetch_assoc($result)): ?>

                    <tr class="main-row" onclick="openAddonModal(
                        <?php echo $row['id']; ?>,
                        '<?php echo addslashes($row['name']); ?>',
                        '<?php echo addslashes($row['category']); ?>',
                        '<?php echo $row['price']; ?>',
                        '<?php echo $row['stock']; ?>',
                        '<?php echo addslashes($row['description'] ?? ''); ?>',
                        '<?php echo addslashes($row['image_url'] ?? ''); ?>'
                    )">
                        <td>#<?php echo $row['id']; ?></td>
                        <td>
                            <div class="product-cell">
                                <?php
                                $img_path = getProductImagePath($row['image_url'] ?? '');
                                $icon     = $category_icons[$row['category']] ?? 'fa-box';
                                if ($img_path): ?>
                                    <img src="<?php echo htmlspecialchars($img_path); ?>" class="product-thumb" alt="<?php echo htmlspecialchars($row['name']); ?>" onerror="this.style.display='none';this.nextElementSibling.style.display='flex';">
                                    <div class="product-thumb-placeholder" style="display:none;"><i class="fas <?php echo $icon; ?>"></i></div>
                                <?php else: ?>
                                    <div class="product-thumb-placeholder"><i class="fas <?php echo $icon; ?>"></i></div>
                                <?php endif; ?>
                                <div>
                                    <strong><?php echo htmlspecialchars($row['name']); ?></strong>
                                    <?php if (!empty($row['description'])): ?>
                                        <br><small style="color:#999;"><?php echo htmlspecialchars(mb_strimwidth($row['description'], 0, 45, '...')); ?></small>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </td>
                        <td style="text-transform: capitalize;"><?php echo htmlspecialchars($row['category']); ?></td>
                        <td>RM <?php echo number_format($row['price'], 2); ?></td>
                        <td><?php echo $row['stock']; ?></td>
                        <td>
                            <?php if ($row['is_active'] == 1): ?>
                                <span class="badge success">Active</span>
                            <?php else: ?>
                                <span class="badge pending">Inactive</span>
                            <?php endif; ?>
                        </td>
                    </tr>

                    <?php endwhile; ?>
                </tbody>
            </table>

        </div>
    </main>

    <!-- Edit Product Modal -->
    <div class="modal-overlay" id="addonModal">
        <div class="modal-card">

            <div class="modal-header">
                <h2><i class="fas fa-pen"></i> Edit Product</h2>
                <button class="modal-close" onclick="closeAddonModal()">✕</button>
            </div>

            <form action="save_addon.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="product_id" id="modal-product-id">

                <div class="modal-grid">

                    <div class="modal-field">
                        <label>Product Name</label>
                        <input type="text" name="name" id="modal-name" required>
                    </div>

                    <div class="modal-field">
                        <label>Category</label>
                        <select name="category" id="modal-category" required>
                            <option value="racket">Racket</option>
                            <option value="shuttlecock">Shuttlecock</option>
                            <option value="string">String</option>
                            <option value="grip">Grip</option>
                            <option value="snack">Snack</option>
                            <option value="drink">Drink</option>
                        </select>
                    </div>

                    <div class="modal-field">
                        <label>Price (RM)</label>
                        <input type="number" name="price" id="modal-price" step="0.01" min="0" required>
                    </div>

                    <div class="modal-field">
                        <label>Stock</label>
                        <input type="number" name="stock" id="modal-stock" min="0" required>
                    </div>

                    <div class="modal-field full-width">
                        <label>Description</label>
                        <textarea name="description" id="modal-description"></textarea>
                    </div>

                    <div class="modal-field full-width">
                        <label>Product Image</label>
                        <div class="image-upload-area">
                            <input type="file" name="image" accept="image/*" onchange="previewEditImage(this)">
                            <img id="modal-image-preview" class="image-upload-preview" src="" alt="" style="display:none;">
                            <div class="image-upload-hint" id="modal-image-hint">Click to upload image</div>
                        </div>
                    </div>

                </div>

                <div class="modal-actions">
                    <form action="save_addon.php" method="POST" style="margin:0;" onsubmit="return confirm('Delete this product? If it has booking history it will be deactivated instead.')">
                        <input type="hidden" name="product_id_delete" id="modal-delete-id">
                        <button type="submit" name="delete_product" class="btn-modal-delete">
                            <i class="fas fa-trash-alt"></i> Delete
                        </button>
                    </form>
                    <div style="display:flex; gap:10px;">
                        <button type="button" class="btn-modal-cancel" onclick="closeAddonModal()">Cancel</button>
                        <button type="submit" name="update_product" class="btn-modal-save">Save Changes</button>
                    </div>
                </div>

            </form>
        </div>
    </div>

    <!-- Add Product Modal -->
    <div class="modal-overlay" id="addAddonModal">
        <div class="modal-card">

            <div class="modal-header">
                <h2><i class="fas fa-plus"></i> Add New Product</h2>
                <button class="modal-close" type="button" onclick="closeAddAddonModal()">✕</button>
            </div>

            <form action="save_addon.php" method="POST" enctype="multipart/form-data">
                <div class="modal-grid">

                    <div class="modal-field">
                        <label>Product Name</label>
                        <input type="text" name="name" placeholder="e.g. Yonex Astrox 100ZZ" required>
                    </div>

                    <div class="modal-field">
                        <label>Category</label>
                        <select name="category" required>
                            <option value="" disabled selected>Select Category</option>
                            <option value="racket">Racket</option>
                            <option value="shuttlecock">Shuttlecock</option>
                            <option value="string">String</option>
                            <option value="grip">Grip</option>
                            <option value="snack">Snack</option>
                            <option value="drink">Drink</option>
                        </select>
                    </div>

                    <div class="modal-field">
                        <label>Price (RM)</label>
                        <input type="number" name="price" step="0.01" min="0" placeholder="0.00" required>
                    </div>

                    <div class="modal-field">
                        <label>Stock</label>
                        <input type="number" name="stock" min="0" value="0" required>
                    </div>

                    <div class="modal-field full-width">
                        <label>Description</label>
                        <textarea name="description" placeholder="Short product description..."></textarea>
                    </div>

                    <div class="modal-field full-width">
                        <label>Product Image</label>
                        <div class="image-upload-area">
                            <input type="file" name="image" accept="image/*" onchange="previewAddImage(this)">
                            <img id="add-image-preview" class="image-upload-preview" src="" alt="" style="display:none;">
                            <div class="image-upload-hint" id="add-image-hint">Click to upload image</div>
                        </div>
                    </div>

                </div>

                <div class="modal-actions">
                    <div></div>
                    <div style="display:flex; gap:10px;">
                        <button type="button" class="btn-modal-cancel" onclick="closeAddAddonModal()">Cancel</button>
                        <button type="submit" name="add_product" class="btn-modal-save">
                            <i class="fas fa-save"></i> Save Product
                        </button>
                    </div>
                </div>
            </form>

        </div>
    </div>

    <!-- Product image crop modal -->
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
                <button type="button" class="btn-crop-save" onclick="applyProductCrop()">
                    <i class="fas fa-check"></i> Crop & Use
                </button>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.2/cropper.min.js"></script>
    <script src="ManageAddOns.js"></script>
    <script src="../Dashboard/Dashboard.js"></script>

    <?php include __DIR__ . '/../modal.php'; ?>
    <?php include __DIR__ . '/../scroll_top.php'; ?>
    <?php include __DIR__ . '/../toast/toast.php'; ?>

</body>
</html>