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
if (isset($_GET['bulk']))                                       { $toasts[] = ['text' => 'Selected products updated.',     'type' => 'success']; }

$conn = mysqli_connect("localhost", "root", "", "badminton_hub");

// ============================================================
// ★★★ 获取所有分类（优先从 category_config 读取） ★★★
// ============================================================
$all_categories = [];

// 检查 category_config 表是否存在
$tableCheck = mysqli_query($conn, "SHOW TABLES LIKE 'category_config'");
if (mysqli_num_rows($tableCheck) > 0) {
    // 从 category_config 获取分类（过滤空值）
    $cat_result = mysqli_query($conn, "SELECT category FROM category_config WHERE is_active = 1 AND category != '' AND category IS NOT NULL ORDER BY sort_order, category");
    while ($cat_row = mysqli_fetch_assoc($cat_result)) {
        $all_categories[] = $cat_row['category'];
    }
}

// 如果 category_config 没有数据，从 products 表获取
if (empty($all_categories)) {
    $cat_result = mysqli_query($conn, "SELECT DISTINCT category FROM products WHERE category != '' AND category IS NOT NULL ORDER BY category ASC");
    while ($cat_row = mysqli_fetch_assoc($cat_result)) {
        $all_categories[] = $cat_row['category'];
    }
}

// 如果还是没有，使用默认分类
if (empty($all_categories)) {
    $all_categories = ['racket', 'shuttlecock', 'grip', 'string', 'snack', 'drink'];
}

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

// Pagination
$per_page    = 15;
$page        = max(1, (int)($_GET['page'] ?? 1));

$count_res   = mysqli_query($conn, "SELECT COUNT(*) AS cnt FROM products $where_sql");
$total_rows  = (int)mysqli_fetch_assoc($count_res)['cnt'];
$total_pages = max(1, (int)ceil($total_rows / $per_page));
$page        = min($page, $total_pages);
$offset      = ($page - 1) * $per_page;

$result = mysqli_query($conn, "SELECT * FROM products $where_sql ORDER BY $sort_col $sort_dir LIMIT $per_page OFFSET $offset");

function addonPageQS($p, $sort, $dir, $filter_category, $filter_status, $filter_search) {
    $params = ['page' => $p, 'sort' => $sort, 'dir' => $dir];
    if ($filter_category !== '') $params['category'] = $filter_category;
    if ($filter_status   !== '') $params['status']   = $filter_status;
    if ($filter_search   !== '') $params['search']   = $filter_search;
    return http_build_query($params);
}

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
        /* ── Select toggle button ── */
        .btn-bulk-toggle {
            padding: 11px 22px;
            background: linear-gradient(135deg, #f59e0b, #d97706);
            color: #1e293b;
            border: none;
            border-radius: 50px;
            font-size: 14px;
            font-weight: 800;
            font-family: 'Outfit', sans-serif;
            cursor: pointer;
            transition: all 0.25s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 4px 12px rgba(245,158,11,0.3);
        }
        .btn-bulk-toggle:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(245,158,11,0.45);
        }
        .btn-bulk-toggle.active {
            background: #fff;
            color: #d97706;
            border: 2px solid #f59e0b;
            box-shadow: 0 0 0 3px rgba(245,158,11,0.15);
        }
        /* ── Checkbox column ── */
        .bulk-col { 
            display: none; 
            width: 40px; 
            padding: 12px 8px 12px 14px; 
        }

        .bulk-col input[type="checkbox"] { 
            width: 16px; 
            height: 16px; 
            cursor: pointer; 
            accent-color: #f59e0b; 
        }

        /* ── Bulk action bar ── */
        .bulk-action-bar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: #fff;
            border-left: 4px solid #f59e0b;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            padding: 0 20px;
            margin-bottom: 12px;
            max-height: 0;
            overflow: hidden;
            opacity: 0;
            transition: max-height 0.35s ease, opacity 0.35s ease, padding 0.35s ease;
        }
        .bulk-action-bar.show { 
            max-height: 60px; 
            padding: 12px 20px; 
            opacity: 1; 
        }

        #bulkCount { 
            font-size: 14px; 
            font-weight: 700; 
            color: #d97706; 
        }

        .bulk-action-btns { 
            display: flex; 
            gap: 8px; 
        }

        .bulk-btn {
            padding: 7px 18px;
            border: none;
            border-radius: 50px;
            font-size: 13px;
            font-weight: 700;
            font-family: 'Outfit', sans-serif;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.2s;
        }

        .bulk-activate { 
            background: #fff; 
            color: #16a34a; 
            border: 1.5px solid #16a34a; 
        }

        .bulk-activate:hover { 
            background: #16a34a; 
            color: #fff; 
        }

        .bulk-deactivate { 
            background: #fff; 
            color: #475569; 
            border: 1.5px solid #cbd5e1; 
        }

        .bulk-deactivate:hover { 
            background: #f1f5f9; 
            border-color: #94a3b8; 
        }

        .bulk-delete { 
            background: #fff; 
            color: #dc2626; 
            border: 1.5px solid #dc2626; 
        }

        .bulk-delete:hover { 
            background: #dc2626; 
            color: #fff; 
        }

        /* ── Inline status pill dropdown ── */
        .data-table select.status-select {
            border-radius: 50px !important;
            padding: 6px 14px;
            font-family: 'Outfit', sans-serif;
            font-size: 12px;
            font-weight: 700;
            border: none !important;
            outline: none;
            cursor: pointer;
            transition: all 0.2s;
            -webkit-appearance: none;
            -moz-appearance: none;
            appearance: none;
        }
        .data-table select.status-select.st-active { 
            background-color: #dcfce7; 
            color: #16a34a; 
        }

        .data-table select.status-select.st-inactive { 
            background-color: #fef3c7; 
            color: #d97706; 
        }

        .data-table select.status-select:hover { 
            filter: brightness(0.97); 
        }

        .cat-dropdown { 
            position: relative; 
        }

        .cat-dropdown-input {
            width: 100%;
            padding: 9px 13px;
            border: 1.5px solid var(--border);
            border-radius: 9px;
            font-size: 13px;
            font-family: 'Outfit', sans-serif;
            outline: none;
            background-color: #fff;
            color: #1f2937;
            box-sizing: border-box;
        }
        .cat-dropdown.open .cat-dropdown-input,
        .cat-dropdown-input:focus { 
            border-color: var(--primary); 
        }

        .cat-dropdown-panel {
            display: none;
            position: absolute;
            top: calc(100% + 6px);
            left: 0; right: 0;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
            z-index: 50;
            max-height: 240px;
            overflow-y: auto;
            padding: 6px;
        }
        .cat-dropdown.open .cat-dropdown-panel { 
            display: block; 
        }

        .cat-option {
            padding: 10px 14px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 600;
            color: #1f2937;
            cursor: pointer;
            text-transform: capitalize;
        }
        .cat-option:hover { 
            background: #fff7e6; 
        }

        .cat-option.selected {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: #fff;
        }
        .cat-option-new {
            color: #d97706;
            border-top: 1px solid var(--border);
            margin-top: 4px;
            padding-top: 10px;
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
                    <button type="button" class="btn-bulk-toggle" id="selectToggleBtn" onclick="toggleSelectMode()">
                        <i class="fas fa-check-square"></i> <span id="selToggleText">Select</span>
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
                            <?php foreach ($all_categories as $cat): ?>
                                <option value="<?php echo htmlspecialchars($cat); ?>" <?php echo ($filter_category === $cat) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars(ucfirst($cat)); ?>
                                </option>
                            <?php endforeach; ?>
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

            <!-- Bulk Action Bar -->
            <div class="bulk-action-bar" id="bulkBar">
                <span id="bulkCount">0 selected</span>
                <div class="bulk-action-btns">
                    <button type="button" class="bulk-btn bulk-activate" onclick="bulkSubmit('activate')"><i class="fas fa-check"></i> Set Active</button>
                    <button type="button" class="bulk-btn bulk-deactivate" onclick="bulkSubmit('deactivate')"><i class="fas fa-ban"></i> Set Inactive</button>
                    <button type="button" class="bulk-btn bulk-delete" onclick="bulkSubmit('delete')"><i class="fas fa-trash"></i> Delete</button>
                </div>
            </div>

            <!-- Products Table -->
            <form id="bulkForm" action="save_addon.php" method="POST">
            <input type="hidden" name="bulk_action" value="1">
            <input type="hidden" name="bulk_type" id="bulkType">
            <table class="data-table">
                <thead>
                    <tr>
                        <th class="bulk-col"><input type="checkbox" id="selectAll" onclick="toggleAll(this)"></th>
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

                    <tr class="main-row"
                        data-id="<?php echo $row['id']; ?>"
                        data-name="<?php echo htmlspecialchars($row['name'], ENT_QUOTES); ?>"
                        data-category="<?php echo htmlspecialchars($row['category'], ENT_QUOTES); ?>"
                        data-price="<?php echo $row['price']; ?>"
                        data-stock="<?php echo $row['stock']; ?>"
                        data-description="<?php echo htmlspecialchars($row['description'] ?? '', ENT_QUOTES); ?>"
                        data-image="<?php echo htmlspecialchars($row['image_url'] ?? '', ENT_QUOTES); ?>">
                        <td class="bulk-col" onclick="event.stopPropagation()">
                            <input type="checkbox" name="ids[]" value="<?php echo $row['id']; ?>" class="row-check" onchange="updateSelCount()">
                        </td>
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
                        <td onclick="event.stopPropagation()">
                            <select class="status-select <?php echo $row['is_active'] == 1 ? 'st-active' : 'st-inactive'; ?>"
                                    onchange="toggleStatus(<?php echo $row['id']; ?>, this.value)">
                                <option value="1" <?php echo $row['is_active'] == 1 ? 'selected' : ''; ?>>Active</option>
                                <option value="0" <?php echo $row['is_active'] == 0 ? 'selected' : ''; ?>>Inactive</option>
                            </select>
                        </td>
                    </tr>

                    <?php endwhile; ?>
                </tbody>
            </table>
            </form>

            <?php if ($total_pages > 1): ?>
            <div class="log-pagination">
                <?php if ($page > 1): ?>
                    <a href="?<?php echo addonPageQS($page - 1, $sort_col, strtolower($sort_dir), $filter_category, $filter_status, $filter_search); ?>" class="page-btn">
                        <i class="fas fa-chevron-left"></i>
                    </a>
                <?php else: ?>
                    <span class="page-btn disabled"><i class="fas fa-chevron-left"></i></span>
                <?php endif; ?>

                <form method="GET" class="page-jump-form">
                    <input type="number" name="page" class="page-jump-input"
                           value="<?php echo $page; ?>" min="1" max="<?php echo $total_pages; ?>">
                    <span class="page-jump-of">/ <?php echo $total_pages; ?></span>
                    <input type="hidden" name="sort" value="<?php echo $sort_col; ?>">
                    <input type="hidden" name="dir"  value="<?php echo strtolower($sort_dir); ?>">
                    <?php if ($filter_category !== '') echo '<input type="hidden" name="category" value="' . htmlspecialchars($filter_category) . '">'; ?>
                    <?php if ($filter_status   !== '') echo '<input type="hidden" name="status" value="'   . htmlspecialchars($filter_status)   . '">'; ?>
                    <?php if ($filter_search   !== '') echo '<input type="hidden" name="search" value="'   . htmlspecialchars($filter_search)   . '">'; ?>
                </form>

                <?php if ($page < $total_pages): ?>
                    <a href="?<?php echo addonPageQS($page + 1, $sort_col, strtolower($sort_dir), $filter_category, $filter_status, $filter_search); ?>" class="page-btn">
                        <i class="fas fa-chevron-right"></i>
                    </a>
                <?php else: ?>
                    <span class="page-btn disabled"><i class="fas fa-chevron-right"></i></span>
                <?php endif; ?>
            </div>
            <?php endif; ?>

        </div>
    </main>

    <!-- Edit Product Modal -->
    <div class="modal-overlay" id="addonModal">
        <div class="modal-card">

            <div class="modal-header">
                <h2><i class="fas fa-pen"></i> Edit Product</h2>
                <button class="modal-close" onclick="closeAddonModal()">✕</button>
            </div>

            <form action="save_addon.php" method="POST" enctype="multipart/form-data" onsubmit="return validateCategorySelected('edit')">
                <input type="hidden" name="product_id" id="modal-product-id">

                <div class="modal-grid">

                    <div class="modal-field">
                        <label>Product Name</label>
                        <input type="text" name="name" id="modal-name" required>
                    </div>

                    <div class="modal-field">
                        <label>Category</label>
                        <div class="cat-dropdown" id="edit-cat-dropdown">
                            <input type="text" class="cat-dropdown-input" id="edit-cat-input"
                                placeholder="Search or type new category" autocomplete="off"
                                oninput="filterCatDropdown('edit')"
                                onfocus="openCatDropdown('edit')">
                            <input type="hidden" name="category" id="edit-cat-value">
                            <div class="cat-dropdown-panel" id="edit-cat-panel">
                                <?php foreach ($all_categories as $cat): ?>
                                    <div class="cat-option" data-value="<?php echo htmlspecialchars($cat); ?>"
                                        onclick="selectCatOption('edit', '<?php echo htmlspecialchars(addslashes($cat)); ?>')">
                                        <?php echo htmlspecialchars($cat); ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
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

            <form action="save_addon.php" method="POST" enctype="multipart/form-data" onsubmit="return validateCategorySelected('add')">
                <div class="modal-grid">

                    <div class="modal-field">
                        <label>Product Name</label>
                        <input type="text" name="name" placeholder="e.g. Yonex Astrox 100ZZ" required>
                    </div>

                    <div class="modal-field">
                        <label>Category</label>
                        <div class="cat-dropdown" id="add-cat-dropdown">
                            <input type="text" class="cat-dropdown-input" id="add-cat-input"
                                placeholder="Search or type new category" autocomplete="off"
                                oninput="filterCatDropdown('add')"
                                onfocus="openCatDropdown('add')">
                            <input type="hidden" name="category" id="add-cat-value">
                            <div class="cat-dropdown-panel" id="add-cat-panel">
                                <?php foreach ($all_categories as $cat): ?>
                                    <div class="cat-option" data-value="<?php echo htmlspecialchars($cat); ?>"
                                        onclick="selectCatOption('add', '<?php echo htmlspecialchars(addslashes($cat)); ?>')">
                                        <?php echo htmlspecialchars($cat); ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
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

    <!-- Hidden form for single status toggle -->
    <form id="statusForm" action="save_addon.php" method="POST" style="display:none;">
        <input type="hidden" name="toggle_status" value="1">
        <input type="hidden" name="product_id" id="statusProductId">
        <input type="hidden" name="new_status" id="statusNewStatus">
    </form>

    <script>
        function toggleSelectMode() {
            const on = document.body.classList.toggle('select-mode');
            document.querySelectorAll('.bulk-col').forEach(el => el.style.display = on ? 'table-cell' : 'none');
            document.getElementById('bulkBar').classList.toggle('show', on);
            document.getElementById('selectToggleBtn').classList.toggle('active', on);
            document.getElementById('selToggleText').textContent = on ? 'Done' : 'Select';
            if (!on) {
                document.querySelectorAll('.row-check').forEach(c => c.checked = false);
                const sa = document.getElementById('selectAll');
                if (sa) sa.checked = false;
                updateSelCount();
            }
        }
        function toggleAll(master) {
            document.querySelectorAll('.row-check').forEach(c => c.checked = master.checked);
            updateSelCount();
        }
        function updateSelCount() {
            document.getElementById('bulkCount').textContent =
                document.querySelectorAll('.row-check:checked').length + ' selected';
        }
        function bulkSubmit(type) {
            const n = document.querySelectorAll('.row-check:checked').length;
            if (n === 0) { alert('Please select at least one product.'); return; }
            if (type === 'delete' && !confirm('Delete the selected products? Products with order history will be deactivated instead.')) return;
            document.getElementById('bulkType').value = type;
            document.getElementById('bulkForm').submit();
        }
        function toggleStatus(id, val) {
            document.getElementById('statusProductId').value = id;
            document.getElementById('statusNewStatus').value = val;
            document.getElementById('statusForm').submit();
        }
    </script>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.2/cropper.min.js"></script>
    <script src="ManageAddOns.js"></script>
    <script src="../Dashboard/Dashboard.js"></script>

    <?php include __DIR__ . '/../modal.php'; ?>
    <?php include __DIR__ . '/../scroll_top.php'; ?>
    <?php include __DIR__ . '/../toast/toast.php'; ?>

</body>
</html>