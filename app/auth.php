<?php
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/../config/database.php';

function attempt_login(string $email, string $password): bool
{
    global $pdo;
    $stmt = $pdo->prepare("SELECT u.*, r.role_key, r.role_name FROM users u JOIN roles r ON r.id = u.role_id WHERE u.email = ? LIMIT 1");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user)
        return false;
    if ($user['status'] !== 'active')
        return false;
    if (!password_verify($password, $user['password_hash']))
        return false;
    if ($user['role_key'] !== 'admin_website')
        return false;

    $_SESSION['user'] = [
        'id' => (int) $user['id'],
        'name' => $user['name'],
        'email' => $user['email'],
        'role_key' => $user['role_key'],
        'role_name' => $user['role_name'],
    ];
    return true;
}
