<?php

session_start();

if (!isset($_SESSION['username']) || !in_array($_SESSION['role'], ['Superadmin', 'Admin'])) {
    header("Location: ../LoginPage.php");
    exit();
}

$conn = mysqli_connect("localhost", "root", "", "badminton_hub");
require_once __DIR__ . '/../log_activity.php';

$upload_base = __DIR__ . '/../../Pictures/Admin_Module/products/';

/* Category subfolder mapping */
$category_folders = [
    'racket'      => 'rackets/',
    'string'      => 'strings/',
    'shuttlecock' => 'shuttlecocks/',
    'grip'        => 'grips/',
    'snack'       => 'snacks/',
    'drink'       => 'drinks/',
];

function uploadImage($file, $category, $upload_base, $category_folders) {
    if (empty($file['name'])) return null;

    if (isset($category_folders[$category])) {
        $subfolder = $category_folders[$category];
    } else {
        $safe_cat  = preg_replace('/[^a-z0-9]/', '', strtolower($category));
        $subfolder = $safe_cat !== '' ? $safe_cat . 's/' : '';
    }
    $dir       = $upload_base . $subfolder;

    if (!is_dir($dir)) mkdir($dir, 0755, true);

    $ext      = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed  = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

    if (!in_array($ext, $allowed)) return false;

    $filename = time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', basename($file['name']));
    $dest     = $dir . $filename;

    if (move_uploaded_file($file['tmp_name'], $dest)) {
        return $subfolder . $filename;
    }

    return false;
}

// ============================================================
// ★★★ 自动创建分类配置函数（确保能工作） ★★★
// ============================================================
function autoCreateCategoryConfig($conn, $category) {
    // 如果分类为空，直接返回
    if (empty($category) || trim($category) === '') {
        return;
    }
    
    $category_escaped = mysqli_real_escape_string($conn, trim($category));
    $category_clean = str_replace(['_', '-'], ' ', $category_escaped);
    $label = '📦 ' . ucfirst($category_clean);
    
    // 1. 确保 category_config 表存在
    mysqli_query($conn, "
        CREATE TABLE IF NOT EXISTS `category_config` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `category` varchar(50) NOT NULL,
            `icon` varchar(50) DEFAULT 'fa-cube',
            `label` varchar(100) DEFAULT NULL,
            `max_qty` int(11) DEFAULT 10,
            `default_image` varchar(255) DEFAULT NULL,
            `sort_order` int(11) DEFAULT 0,
            `is_active` tinyint(1) DEFAULT 1,
            `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
            `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
            PRIMARY KEY (`id`),
            UNIQUE KEY `unique_category` (`category`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
    ");
    
    // 2. 检查分类是否已有配置
    $check = mysqli_query($conn, "SELECT COUNT(*) FROM category_config WHERE category = '$category_escaped'");
    $row = mysqli_fetch_row($check);
    if ($row[0] > 0) {
        return; // 已有配置，跳过
    }
    
    // 3. 智能匹配图标和标签
    $lowerCat = strtolower($category_escaped);
    
    // 图标映射
    $iconMap = [
        'racket' => 'fa-table-tennis',
        'shuttlecock' => 'fa-shuttlecock',
        'grip' => 'fa-hand-peace',
        'string' => 'fa-thread',
        'snack' => 'fa-cookie-bite',
        'drink' => 'fa-tint',
        'shoe' => 'fa-shoe-prints',
        'shoes' => 'fa-shoe-prints',
        'footwear' => 'fa-shoe-prints',
        'cloth' => 'fa-tshirt',
        'clothes' => 'fa-tshirt',
        'apparel' => 'fa-tshirt',
        'jersey' => 'fa-tshirt',
        'bag' => 'fa-bag-shopping',
        'bags' => 'fa-bag-shopping',
        'accessory' => 'fa-headphones',
        'accessories' => 'fa-headphones',
        'ball' => 'fa-futbol',
        'book' => 'fa-book',
        'equipment' => 'fa-tools',
        'gear' => 'fa-tools',
    ];
    
    // 标签映射（带 emoji）
    $labelMap = [
        'racket' => '🏸 Badminton Rackets',
        'shuttlecock' => '🏸 Shuttlecocks',
        'grip' => '🎾 Grips / Overgrips',
        'string' => '🧵 Badminton Strings',
        'snack' => '🍪 Snacks',
        'drink' => '🥤 Drinks',
        'shoe' => '👟 Badminton Shoes',
        'shoes' => '👟 Badminton Shoes',
        'footwear' => '👟 Footwear',
        'cloth' => '👕 Apparel',
        'clothes' => '👕 Apparel',
        'apparel' => '👕 Apparel',
        'jersey' => '👕 Jerseys',
        'bag' => '🎒 Bags',
        'bags' => '🎒 Bags',
        'accessory' => '🧤 Accessories',
        'accessories' => '🧤 Accessories',
        'ball' => '⚽ Balls',
        'book' => '📚 Books',
        'equipment' => '🔧 Equipment',
        'gear' => '🔧 Gear',
    ];
    
    $icon = 'fa-cube';
    $label_final = '📦 ' . ucfirst($category_clean);
    
    foreach ($iconMap as $key => $value) {
        if (strpos($lowerCat, $key) !== false) {
            $icon = $value;
            break;
        }
    }
    
    foreach ($labelMap as $key => $value) {
        if (strpos($lowerCat, $key) !== false) {
            $label_final = $value;
            break;
        }
    }
    
    // 4. 获取最大排序
    $sortResult = mysqli_query($conn, "SELECT MAX(sort_order) as max_order FROM category_config");
    $sortRow = mysqli_fetch_assoc($sortResult);
    $sortOrder = ($sortRow['max_order'] ?? 0) + 1;
    
    // 5. 插入配置
    $icon_escaped = mysqli_real_escape_string($conn, $icon);
    $label_escaped = mysqli_real_escape_string($conn, $label_final);
    
    $insert = mysqli_query($conn, "
        INSERT INTO category_config (category, icon, label, max_qty, sort_order, is_active) 
        VALUES ('$category_escaped', '$icon_escaped', '$label_escaped', 10, $sortOrder, 1)
    ");
    
    // 6. 记录日志
    if ($insert) {
        logActivity($conn, 'Create', 'Category Config', "Auto-created category config: $category (icon: $icon, label: $label_final)");
    }
    
    return $insert;
}

// ============================================================
// ADD NEW PRODUCT
// ============================================================
if (isset($_POST['add_product'])) {
    $name        = mysqli_real_escape_string($conn, trim($_POST['name']));
    $category    = mysqli_real_escape_string($conn, trim($_POST['category']));
    $price       = floatval($_POST['price']);
    $stock       = intval($_POST['stock']);
    $description = mysqli_real_escape_string($conn, trim($_POST['description']));
    $is_active   = ($stock > 0) ? 1 : 0;

    // 如果分类为空，使用默认值
    if (empty($category)) {
        $category = 'uncategorized';
    }

    $image_url = '';
    if (!empty($_FILES['image']['name'])) {
        $result = uploadImage($_FILES['image'], $category, $upload_base, $category_folders);
        if ($result === false) {
            header("Location: ManageAddOns.php?error=image");
            exit();
        }
        $image_url = $result;
    }

    // 插入产品
    $insert_product = mysqli_query($conn, "
        INSERT INTO products (category, name, description, price, image_url, stock, is_active)
        VALUES ('$category', '$name', '$description', '$price', '$image_url', '$stock', '$is_active')
    ");

    if (!$insert_product) {
        // 插入失败，记录错误
        error_log("Product insert failed: " . mysqli_error($conn));
        header("Location: ManageAddOns.php?error=db");
        exit();
    }

    // ============================================================
    // ★★★ 自动创建分类配置（核心功能） ★★★
    // ============================================================
    autoCreateCategoryConfig($conn, $category);

    logActivity($conn, 'Create', 'Add-On Management', "Added product: $name (category: $category, price: RM$price)");
    header("Location: ManageAddOns.php?success=added");
    exit();
}

// ============================================================
// UPDATE PRODUCT
// ============================================================
if (isset($_POST['update_product'])) {
    $id          = intval($_POST['product_id']);
    $name        = mysqli_real_escape_string($conn, trim($_POST['name']));
    $category    = mysqli_real_escape_string($conn, trim($_POST['category']));
    $price       = floatval($_POST['price']);
    $stock       = intval($_POST['stock']);
    $description = mysqli_real_escape_string($conn, trim($_POST['description']));
    $is_active   = ($stock > 0) ? 1 : 0;

    // 如果分类为空，使用默认值
    if (empty($category)) {
        $category = 'uncategorized';
    }

    $image_sql = '';
    if (!empty($_FILES['image']['name'])) {
        $result = uploadImage($_FILES['image'], $category, $upload_base, $category_folders);
        if ($result === false) {
            header("Location: ManageAddOns.php?error=image");
            exit();
        }
        $safe_url  = mysqli_real_escape_string($conn, $result);
        $image_sql = ", image_url = '$safe_url'";
    }

    mysqli_query($conn, "
        UPDATE products
        SET name        = '$name',
            category    = '$category',
            price       = '$price',
            stock       = '$stock',
            description = '$description',
            is_active   = '$is_active'
            $image_sql
        WHERE id = $id
    ");

    // ============================================================
    // ★★★ 自动创建分类配置（如果分类改变了） ★★★
    // ============================================================
    autoCreateCategoryConfig($conn, $category);

    logActivity($conn, 'Update', 'Add-On Management', "Updated product: $name (ID $id)");
    header("Location: ManageAddOns.php?success=updated");
    exit();
}

// ============================================================
// DELETE PRODUCT
// ============================================================
if (isset($_POST['delete_product'])) {
    $id = intval($_POST['product_id_delete']);

    $del_row = mysqli_fetch_assoc(mysqli_query($conn, "SELECT name FROM products WHERE id = $id"));
    $del_name = $del_row['name'] ?? "ID $id";

    $check = mysqli_query($conn, "SELECT id FROM booking_addons WHERE product_id = $id LIMIT 1");
    if ($check && mysqli_num_rows($check) > 0) {
        mysqli_query($conn, "UPDATE products SET is_active = 0 WHERE id = $id");
        logActivity($conn, 'Delete', 'Add-On Management', "Deactivated product (has orders): $del_name");
    } else {
        mysqli_query($conn, "DELETE FROM products WHERE id = $id");
        logActivity($conn, 'Delete', 'Add-On Management', "Deleted product: $del_name");
    }

    header("Location: ManageAddOns.php?deleted=1");
    exit();
}

// ============================================================
// TOGGLE STATUS
// ============================================================
if (isset($_POST['toggle_status'])) {
    $id  = intval($_POST['product_id']);
    $new = intval($_POST['new_status']) === 1 ? 1 : 0;

    mysqli_query($conn, "UPDATE products SET is_active = $new WHERE id = $id");

    $tg_row  = mysqli_fetch_assoc(mysqli_query($conn, "SELECT name FROM products WHERE id = $id"));
    $tg_name = $tg_row['name'] ?? "ID $id";
    $label   = $new ? 'Active' : 'Inactive';
    logActivity($conn, 'Update', 'Add-On Management', "Set product '$tg_name' to $label");

    header("Location: ManageAddOns.php?success=updated");
    exit();
}

// ============================================================
// BULK ACTIONS
// ============================================================
if (isset($_POST['bulk_action'])) {
    $ids  = isset($_POST['ids']) ? array_map('intval', (array)$_POST['ids']) : [];
    $type = $_POST['bulk_type'] ?? '';

    if (!empty($ids)) {
        $in = implode(',', $ids);

        if ($type === 'activate') {
            mysqli_query($conn, "UPDATE products SET is_active = 1 WHERE id IN ($in)");
            logActivity($conn, 'Update', 'Add-On Management', "Bulk set " . count($ids) . " product(s) to Active");
        } elseif ($type === 'deactivate') {
            mysqli_query($conn, "UPDATE products SET is_active = 0 WHERE id IN ($in)");
            logActivity($conn, 'Update', 'Add-On Management', "Bulk set " . count($ids) . " product(s) to Inactive");
        } elseif ($type === 'delete') {
            foreach ($ids as $pid) {
                $check = mysqli_query($conn, "SELECT id FROM booking_addons WHERE product_id = $pid LIMIT 1");
                if ($check && mysqli_num_rows($check) > 0) {
                    mysqli_query($conn, "UPDATE products SET is_active = 0 WHERE id = $pid");
                } else {
                    mysqli_query($conn, "DELETE FROM products WHERE id = $pid");
                }
            }
            logActivity($conn, 'Delete', 'Add-On Management', "Bulk removed " . count($ids) . " product(s)");
        }
    }

    header("Location: ManageAddOns.php?bulk=done");
    exit();
}

header("Location: ManageAddOns.php");
exit();
?>