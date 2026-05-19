<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function app_base_url(): string
{
    $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
    $pos = strpos($script, '/sysadmin/');
    if ($pos !== false) {
        $base = substr($script, 0, $pos);
    } else {
        $base = rtrim(str_replace('\\', '/', dirname($script)), '/');
    }
    if ($base === '/' || $base === '.')
        $base = '';
    return $base;
}

function url(string $path = ''): string
{
    return app_base_url() . '/' . ltrim($path, '/');
}

function redirect(string $path): void
{
    header('Location: ' . url($path));
    exit;
}

function is_logged_in(): bool
{
    return isset($_SESSION['user']) && is_array($_SESSION['user']);
}

function current_user(): array
{
    return $_SESSION['user'] ?? [];
}

function require_admin(): void
{
    if (!is_logged_in()) {
        redirect('login.php');
    }
    if (($_SESSION['user']['role_key'] ?? '') !== 'admin_website') {
        redirect('login.php?error=forbidden');
    }
}

function flash_set(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function flash_get(): ?array
{
    if (!isset($_SESSION['flash']))
        return null;
    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);
    return $flash;
}

function role_label(string $role): string
{
    return [
        'mahasiswa' => 'Mahasiswa',
        'perusahaan' => 'Perusahaan',
        'admin_universitas' => 'Admin Universitas',
        'admin_website' => 'Admin Sistem',
    ][$role] ?? $role;
}

function status_badge_class(string $status): string
{
    $green = ['active', 'verified', 'accepted', 'issued', 'ongoing', 'completed', 'admin_approved', 'university_approved'];
    $yellow = ['pending', 'university_review', 'company_review', 'draft'];
    $red = ['rejected', 'inactive', 'closed', 'revoked', 'terminated'];

    if (in_array($status, $green, true)) {
        return 'badge-green';
    }
    if (in_array($status, $yellow, true)) {
        return 'badge-yellow';
    }
    if (in_array($status, $red, true)) {
        return 'badge-red';
    }
    return 'badge-blue';
}

function status_label(string $status): string
{
    return [
        'active' => 'Aktif',
        'inactive' => 'Nonaktif',
        'verified' => 'Terverifikasi',
        'pending' => 'Menunggu',
        'rejected' => 'Ditolak',
        'accepted' => 'Diterima',
        'issued' => 'Diterbitkan',
        'revoked' => 'Dicabut',
        'ongoing' => 'Berjalan',
        'completed' => 'Selesai',
        'terminated' => 'Dihentikan',
        'university_review' => 'Review Universitas',
        'university_approved' => 'Disetujui Universitas',
        'admin_approved' => 'Disetujui Admin',
        'company_review' => 'Review Perusahaan',
        'draft' => 'Draft',
        'closed' => 'Ditutup',
    ][$status] ?? ucfirst(str_replace('_', ' ', $status));
}

function format_date_id(?string $date): string
{
    if (!$date)
        return '-';
    $months = [1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
    $time = strtotime($date);
    if (!$time)
        return e($date);
    return date('d', $time) . ' ' . $months[(int) date('n', $time)] . ' ' . date('Y', $time);
}

function initials(string $name): string
{
    $parts = preg_split('/\s+/', trim($name));
    $letters = '';
    foreach ($parts as $p) {
        if ($p !== '') {
            $letters .= function_exists('mb_substr') ? mb_substr($p, 0, 1) : substr($p, 0, 1);
        }
        $len = function_exists('mb_strlen') ? mb_strlen($letters) : strlen($letters);
        if ($len >= 2)
            break;
    }
    return function_exists('mb_strtoupper') ? mb_strtoupper($letters ?: 'NI') : strtoupper($letters ?: 'NI');
}

function short_text(string $text, int $limit = 60): string
{
    if (function_exists('mb_strimwidth')) {
        return mb_strimwidth($text, 0, $limit, '...');
    }
    return strlen($text) > $limit ? substr($text, 0, max(0, $limit - 3)) . '...' : $text;
}
