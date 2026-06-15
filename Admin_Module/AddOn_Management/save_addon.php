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

    $subfolder = isset($category_folders[$category]) ? $category_folders[$category] : '';
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

/* Add new product */
if (isset($_POST['add_product'])) {
    $name        = mysqli_real_escape_string($conn, trim($_POST['name']));
    $category    = mysqli_real_escape_string($conn, $_POST['category']);
    $price       = floatval($_POST['price']);
    $stock       = intval($_POST['stock']);
    $description = mysqli_real_escape_string($conn, trim($_POST['description']));
    $is_active   = ($stock > 0) ? 1 : 0;

    $image_url = '';
    if (!empty($_FILES['image']['name'])) {
        $result = uploadImage($_FILES['image'], $category, $upload_base, $category_folders);
        if ($result === false) {
            header("Location: ManageAddOns.php?error=image");
            exit();
        }
        $image_url = $result;
    }

    mysqli_query($conn, "
        INSERT INTO products (category, name, description, price, image_url, stock, is_active)
        VALUES ('$category', '$name', '$description', '$price', '$image_url', '$stock', '$is_active')
    ");

    logActivity($conn, 'Create', 'Add-On Management', "Added product: $name (category: $category, price: RM$price)");
    header("Location: ManageAddOns.php?success=added");
    exit();
}

/* Update existing product */
if (isset($_POST['update_product'])) {
    $id          = intval($_POST['product_id']);
    $name        = mysqli_real_escape_string($conn, trim($_POST['name']));
    $category    = mysqli_real_escape_string($conn, $_POST['category']);
    $price       = floatval($_POST['price']);
    $stock       = intval($_POST['stock']);
    $description = mysqli_real_escape_string($conn, trim($_POST['description']));
    $is_active   = ($stock > 0) ? 1 : 0;

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

    logActivity($conn, 'Update', 'Add-On Management', "Updated product: $name (ID $id)");
    header("Location: ManageAddOns.php?success=updated");
    exit();
}

/* Delete product */
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

/* Toggle a single product's active status */
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

/* Bulk actions: activate / deactivate / delete selected products */
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