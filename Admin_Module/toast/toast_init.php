<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }

if (!function_exists('toast_push')) {
    function toast_push($text, $type = 'success') {
        if (!isset($_SESSION['toasts']) || !is_array($_SESSION['toasts'])) {
            $_SESSION['toasts'] = [];
        }
        $_SESSION['toasts'][] = ['text' => $text, 'type' => $type];
    }
}

if (!isset($toasts) || !is_array($toasts)) { $toasts = []; }
