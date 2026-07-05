<?php
function logActivity($conn, $action, $module, $description,
                     $user_id = null, $username = null, $role = null) {
    if (!$conn) return;

    // ？？的意思是先看第一个要求有没有，如果有就用第一个要求的值，如果没有就看第二个要求有没有，如果有就用第二个要求的值，如果都没有就用null
    $user_id  = $user_id  ?? ($_SESSION['id']       ?? null);
    $username = $username ?? ($_SESSION['username']  ?? null);
    $role     = $role     ?? ($_SESSION['role']      ?? null);

    $ip = $_SERVER['HTTP_X_FORWARDED_FOR'] // Original client IP (if using proxy 代理服务器)
          ?? $_SERVER['REMOTE_ADDR'] // Direct connect to server 的 IP address
          ?? null;

    // ? : (Ternary Operator)两个一起搭配用意思是: $uid  = (if的条件) ? （if里面的第一个选项） : （else第二个选项）;
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
