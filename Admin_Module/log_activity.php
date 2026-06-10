<?php
function logActivity($conn, $action, $module, $description,
                     $user_id = null, $username = null, $role = null) {
    if (!$conn) return;

    $user_id  = $user_id  ?? ($_SESSION['id']       ?? null);
    $username = $username ?? ($_SESSION['username']  ?? null);
    $role     = $role     ?? ($_SESSION['role']      ?? null);

    $ip = $_SERVER['HTTP_X_FORWARDED_FOR']
          ?? $_SERVER['REMOTE_ADDR']
          ?? null;

    $uid  = ($user_id !== null) ? (int)$user_id : 'NULL';
    $user = ($username !== null)
                ? "'" . mysqli_real_escape_string($conn, $username) . "'"
                : 'NULL';
    $rol  = ($role !== null)
                ? "'" . mysqli_real_escape_string($conn, $role) . "'"
                : 'NULL';
    $act  = mysqli_real_escape_string($conn, $action);
    $mod  = mysqli_real_escape_string($conn, $module);
    $desc = mysqli_real_escape_string($conn, $description);
    $ip_s = ($ip !== null)
                ? "'" . mysqli_real_escape_string($conn, $ip) . "'"
                : 'NULL';

    $now = date('Y-m-d H:i:s');

    mysqli_query($conn,
        "INSERT INTO activity_logs (user_id, username, role, action, module, description, ip_address, created_at)
         VALUES ($uid, $user, $rol, '$act', '$mod', '$desc', $ip_s, '$now')"
    );
}
