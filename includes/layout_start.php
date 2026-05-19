<?php
require_once __DIR__ . '/../app/functions.php';
require_once __DIR__ . '/../app/icons.php';
require_admin();
$flash = flash_get();
$user = current_user();
$activeMenu = $activeMenu ?? 'dashboard';
$pageTitle = $pageTitle ?? 'Dashboard Admin Sistem';
$pageSubtitle = $pageSubtitle ?? 'Kelola sistem NextIntern';
$menuItems = [
  ['key' => 'dashboard', 'label' => 'Beranda', 'path' => 'sysadmin/dashboard.php', 'icon' => 'home'],
  ['key' => 'users', 'label' => 'Data Pengguna', 'path' => 'sysadmin/users.php', 'icon' => 'users'],
  ['key' => 'companies', 'label' => 'Data Perusahaan', 'path' => 'sysadmin/companies.php', 'icon' => 'building'],
  ['key' => 'internships', 'label' => 'Data Lowongan', 'path' => 'sysadmin/internships.php', 'icon' => 'briefcase'],
  ['key' => 'applications', 'label' => 'Data Lamaran', 'path' => 'sysadmin/applications.php', 'icon' => 'file'],
  ['key' => 'active-interns', 'label' => 'Data Magang Aktif', 'path' => 'sysadmin/active-interns.php', 'icon' => 'activity'],
  ['key' => 'certificates', 'label' => 'Data Sertifikat', 'path' => 'sysadmin/certificates.php', 'icon' => 'award'],
  ['key' => 'documents', 'label' => 'Data Dokumen', 'path' => 'sysadmin/documents.php', 'icon' => 'file'],
];
?>
<!doctype html>
<html lang="id">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($pageTitle) ?> - NextIntern</title>
  <link rel="stylesheet" href="<?= e(url('assets/css/style.css')) ?>">
</head>

<body>
  <div class="admin-app">
    <header class="topbar">
      <a href="<?= e(url('sysadmin/dashboard.php')) ?>" class="brand" aria-label="NextIntern Admin Sistem">
        <span class="brand-mark">NI</span>
        <span class="brand-text">
          <strong>NextIntern</strong>
          <small>Admin Sistem</small>
        </span>
      </a>
      <div class="topbar-right">
        <span class="notification-dot" title="Notifikasi"><?= icon_svg('bell') ?></span>
        <div class="profile-chip">
          <span class="avatar"><?= e(initials($user['name'] ?? 'Admin')) ?></span>
          <span>
            <b><?= e($user['name'] ?? 'System Admin') ?></b>
            <small><?= e($user['role_name'] ?? 'Admin Sistem') ?></small>
          </span>
        </div>
      </div>
    </header>

    <aside class="sidebar">
      <nav class="side-nav" aria-label="Menu Admin Sistem">
        <?php foreach ($menuItems as $item): ?>
          <a class="side-link <?= $activeMenu === $item['key'] ? 'active' : '' ?>" href="<?= e(url($item['path'])) ?>">
            <?= icon_svg($item['icon']) ?>
            <span><?= e($item['label']) ?></span>
          </a>
        <?php endforeach; ?>
        <div class="side-divider"></div>
        <a class="side-link danger" href="<?= e(url('logout.php')) ?>">
          <?= icon_svg('logout') ?>
          <span>Keluar</span>
        </a>
      </nav>
    </aside>

    <main class="main">
      <section class="page-head">
        <div class="breadcrumbs">
          <?= icon_svg('home', 'icon tiny') ?>
          <span>NextIntern</span>
          <span>/</span>
          <strong><?= e($pageTitle) ?></strong>
        </div>
        <h1><?= e($pageTitle) ?></h1>
        <p><?= e($pageSubtitle) ?></p>
      </section>

      <section class="content">
        <?php if ($flash): ?>
          <div class="alert <?= e($flash['type']) ?>"><?= e($flash['message']) ?></div>
        <?php endif; ?>