<?php
require_once __DIR__ . '/../config/database.php';
$pageTitle = 'Dashboard Admin Sistem';
$pageSubtitle = 'Kelola sistem dari sisi teknis dan operasional';
$activeMenu = 'dashboard';
require_once __DIR__ . '/../includes/layout_start.php';

$stats = $pdo->query("SELECT
  (SELECT COUNT(*) FROM users WHERE status = 'active') AS total_pengguna,
  (SELECT COUNT(*) FROM companies) AS total_perusahaan,
  (SELECT COUNT(*) FROM internships) AS total_lowongan,
  (SELECT COUNT(*) FROM applications) AS total_lamaran,
  (SELECT COUNT(*) FROM active_interns WHERE status = 'ongoing') AS magang_aktif,
  (SELECT COUNT(*) FROM certificates) AS total_sertifikat,
  (SELECT COUNT(*) FROM documents) AS total_dokumen")->fetch();
$recent = $pdo->query("SELECT student_name, company_name, position, status, applied_date FROM applications ORDER BY applied_date DESC, id DESC LIMIT 5")->fetchAll();
?>
<div class="grid grid-4">
  <div class="card stat">
    <div class="stat-row">
      <div>
        <div class="stat-title">Total Pengguna</div>
        <div class="stat-number"><?= e($stats['total_pengguna']) ?></div><small>Akun aktif</small>
      </div>
      <div class="stat-icon"><?= icon_svg('users') ?></div>
    </div>
  </div>
  <div class="card stat">
    <div class="stat-row">
      <div>
        <div class="stat-title">Status Sistem</div>
        <div class="stat-number text-green">Online</div><small>100% uptime</small>
      </div>
      <div class="stat-icon"><?= icon_svg('shield') ?></div>
    </div>
  </div>
  <div class="card stat">
    <div class="stat-row">
      <div>
        <div class="stat-title">Database</div>
        <div class="stat-number">Normal</div><small>Semua berjalan baik</small>
      </div>
      <div class="stat-icon"><?= icon_svg('database') ?></div>
    </div>
  </div>
  <div class="card stat">
    <div class="stat-row">
      <div>
        <div class="stat-title">Keamanan</div>
        <div class="stat-number text-green">Aman</div><small>Tidak ada ancaman</small>
      </div>
      <div class="stat-icon"><?= icon_svg('shield') ?></div>
    </div>
  </div>
</div>

<div class="grid grid-4" style="margin-top:18px">
  <div class="card stat">
    <div class="stat-title">Perusahaan</div>
    <div class="stat-number"><?= e($stats['total_perusahaan']) ?></div><small>Mitra terdaftar</small>
  </div>
  <div class="card stat">
    <div class="stat-title">Lowongan</div>
    <div class="stat-number"><?= e($stats['total_lowongan']) ?></div><small>Data lowongan</small>
  </div>
  <div class="card stat">
    <div class="stat-title">Lamaran</div>
    <div class="stat-number"><?= e($stats['total_lamaran']) ?></div><small>Pengajuan magang</small>
  </div>
  <div class="card stat">
    <div class="stat-title">Magang Aktif</div>
    <div class="stat-number"><?= e($stats['magang_aktif']) ?></div><small>Sedang berjalan</small>
  </div>
</div>

<div class="grid grid-2" style="margin-top:22px">
  <a class="card quick-card" href="<?= e(url('sysadmin/users.php')) ?>">
    <div class="stat-icon"><?= icon_svg('users') ?></div>
    <h3>Kelola Pengguna</h3>
    <p>Kelola akun pengguna, hak akses, dan role.</p>
  </a>
  <a class="card quick-card" href="<?= e(url('sysadmin/companies.php')) ?>">
    <div class="stat-icon"><?= icon_svg('database') ?></div>
    <h3>Data Perusahaan</h3>
    <p>Kelola perusahaan mitra dan status kemitraan.</p>
  </a>
</div>

<div class="card" style="margin-top:22px">
  <div class="card-head">
    <h3>Lamaran Terbaru</h3>
    <p>Aktivitas pendaftaran magang terbaru di sistem</p>
  </div>
  <div class="table-wrap">
    <table class="table">
      <thead>
        <tr>
          <th>Mahasiswa</th>
          <th>Posisi</th>
          <th>Perusahaan</th>
          <th>Status</th>
          <th>Tanggal</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($recent as $row): ?>
          <tr>
            <td><?= e($row['student_name']) ?></td>
            <td><?= e($row['position']) ?></td>
            <td><?= e($row['company_name']) ?></td>
            <td><span
                class="badge <?= status_badge_class($row['status']) ?>"><?= e(status_label($row['status'])) ?></span></td>
            <td><?= e(format_date_id($row['applied_date'])) ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php require_once __DIR__ . '/../includes/layout_end.php'; ?>