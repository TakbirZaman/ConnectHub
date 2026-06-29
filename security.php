<?php
/**
 * security.php — CSRF tokens, brute-force protection, session security
 * Include once from config.php
 */

function csrf_token(): string {
    if (empty($_SESSION['_csrf_token'])) {
        $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['_csrf_token'];
}

function csrf_field(): string {
    return '<input type="hidden" name="_csrf_token" value="' . csrf_token() . '">';
}

function csrf_validate(): bool {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') return true;
    $token = $_POST['_csrf_token'] ?? '';
    if ($token === '' || empty($_SESSION['_csrf_token']) || !hash_equals($_SESSION['_csrf_token'], $token)) {
        $_SESSION['flash_bad'] = 'Session expired. Please try again.';
        return false;
    }
    return true;
}

function sec_session_regenerate(): void {
    session_regenerate_id(true);
}

function brute_force_allowed(string $key): bool {
    $skey = '_bf_' . md5($key);
    $data = $_SESSION[$skey] ?? null;
    if (!$data) return true;

    $attempts = (int)($data['c'] ?? 0);
    $first    = (int)($data['t'] ?? 0);

    if (time() - $first > 900) {
        unset($_SESSION[$skey]);
        return true;
    }

    if ($attempts >= 5) {
        $remaining = 900 - (time() - $first);
        $_SESSION['flash_bad'] = 'Too many attempts. Try again in ' . ceil($remaining / 60) . ' minutes.';
        return false;
    }

    return true;
}

function brute_force_increment(string $key): void {
    $skey = '_bf_' . md5($key);
    $data = $_SESSION[$skey] ?? ['c' => 0, 't' => time()];
    $data['c']++;
    $data['t'] = $data['t'] ?: time();
    $_SESSION[$skey] = $data;
}

function brute_force_reset(string $key): void {
    $skey = '_bf_' . md5($key);
    unset($_SESSION[$skey]);
}
