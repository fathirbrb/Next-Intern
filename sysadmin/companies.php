<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/functions.php';
require_admin();
$pageTitle = 'Data Perusahaan';
$pageSubtitle = 'Kelola informasi perusahaan mitra';
$activeMenu = 'companies';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = $_POST['action'] ?? '';
  if ($action === 'create') {
    $stmt = $pdo->prepare("INSERT INTO companies(name,email,industry,location,description,is_verified,partnership_since,partnership_status) VALUES(?,?,?,?,?,?,?,?)");
    $stmt->execute([
      trim($_POST['name'] ?? ''),
      trim($_POST['email'] ?? ''),
      trim($_POST['industry'] ?? ''),
      trim($_POST['location'] ?? ''),
      trim($_POST['description'] ?? ''),
      isset($_POST['is_verified']) ? 1 : 0,
      ($_POST['partnership_since'] ?? '') ?: null,
      $_POST['partnership_status'] ?? 'active'
    ]);
    flash_set('success', 'Perusahaan berhasil ditambahkan.');
    redirect('sysadmin/companies.php');
  }
  if ($action === 'delete') {
    $pdo->prepare("DELETE FROM companies WHERE id=?")->execute([(int) ($_POST['id'] ?? 0)]);
    flash_set('success', 'Perusahaan berhasil dihapus.');
    redirect('sysadmin/companies.php');
  }
}

require_once __DIR__ . '/../includes/layout_start.php';
$q = trim($_GET['q'] ?? '');
$filter = $_GET['filter'] ?? 'all';
$where = [];
$params = [];
if ($q !== '') {
  $where[] = "(name LIKE ? OR industry LIKE ? OR email LIKE ?)";
  $params[] = "%$q%";
  $params[] = "%$q%";
  $params[] = "%$q%";
}
if ($filter === 'verified')
  $where[] = "is_verified=1";
if ($filter === 'active')
  $where[] = "partnership_status='active'";
$stmt = $pdo->prepare("SELECT * FROM companies" . ($where ? ' WHERE ' . implode(' AND ', $where) : '') . " ORDER BY created_at DESC,id DESC");
$stmt->execute($params);
$companies = $stmt->fetchAll();
$stat = $pdo->query("SELECT COUNT(*) total, SUM(is_verified=1) verified, SUM(partnership_status='active') active, SUM(is_verified=0) pending FROM companies")->fetch();
?>
<div class="grid grid-4">
  <div class="card stat">
    <div class="stat-number"><?= e($stat['total'] ?? 0) ?></div>
    <div class="stat-title">Total Perusahaan</div>
  </div>
  <div class="card stat">
    <div class="stat-number text-green"><?= e($stat['verified'] ?? 0) ?></div>
    <div class="stat-title">Terverifikasi</div>
  </div>
  <div class="card stat">
    <div class="stat-number text-blue"><?= e($stat['active'] ?? 0) ?></div>
    <div class="stat-title">Aktif</div>
  </div>
  <div class="card stat">
    <div class="stat-number text-yellow"><?= e($stat['pending'] ?? 0) ?></div>
    <div class="stat-title">Pending</div>
  </div>
</div>

<div class="card mt-18">
  <form class="toolbar" method="get">
    <div class="searchbox"><?= icon_svg('search') ?><input class="input" name="q" value="<?= e($q) ?>"
        placeholder="Cari nama perusahaan atau industri..."></div>
    <select class="select" name="filter">
      <option value="all">Semua Status</option>
      <option value="verified" <?= $filter === 'verified' ? 'selected' : '' ?>>Terverifikasi</option>
      <option value="active" <?= $filter === 'active' ? 'selected' : '' ?>>Aktif</option>
    </select>
    <button class="btn outline" type="submit">Filter</button>
  </form>
</div>

<details class="card form-panel">
  <summary><span><?= icon_svg('plus') ?> Tambah Perusahaan</span><span class="badge badge-dark">Form</span></summary>
  <form class="form-body" method="post">
    <input type="hidden" name="action" value="create">
    <div class="form-grid">
      <div class="form-field"><label>Nama</label><input class="input" name="name" required></div>
      <div class="form-field"><label>Email</label><input class="input" type="email" name="email" required></div>
      <div class="form-field"><label>Industri</label><input class="input" name="industry" required></div>
      <div class="form-field"><label>Lokasi</label><input class="input" name="location" required></div>
      <div class="form-field"><label>Sejak</label><input class="input" type="date" name="partnership_since"></div>
      <div class="form-field"><label>Status Kemitraan</label><select class="select" name="partnership_status">
          <option value="active">Aktif</option>
          <option value="inactive">Tidak Aktif</option>
        </select></div>
      <div class="form-field full"><label>Deskripsi</label><textarea class="input" name="description"></textarea></div>
      <div class="form-field full"><label class="check-label"><input type="checkbox" name="is_verified" checked>
          Terverifikasi</label></div>
    </div>
    <div class="form-actions"><button class="btn" type="submit">Simpan Perusahaan</button></div>
  </form>
</details>

<div class="card mt-18">
  <div class="table-wrap">
    <table class="table">
      <thead>
        <tr>
          <th>Perusahaan</th>
          <th>Industri</th>
          <th>Lokasi</th>
          <th>Status</th>
          <th>Kemitraan</th>
          <th style="text-align:right">Aksi</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($companies as $c): ?>
          <tr>
            <td>
              <div class="entity">
                <div class="entity-icon"><?= icon_svg('building') ?></div>
                <div>
                  <div class="entity-title"><?= e($c['name']) ?></div>
                  <div class="entity-sub"><?= e($c['email']) ?></div>
                </div>
              </div>
            </td>
            <td><?= e($c['industry']) ?></td>
            <td><?= e($c['location']) ?></td>
            <td><span
                class="badge <?= $c['is_verified'] ? 'badge-green' : 'badge-yellow' ?>"><?= $c['is_verified'] ? 'Terverifikasi' : 'Pending' ?></span>
            </td>
            <td><span class="badge badge-blue"><?= e(status_label($c['partnership_status'])) ?></span></td>
            <td>
              <div class="actions"><button class="btn outline sm" type="button"><?= icon_svg('edit') ?></button>
                <form method="post"><input type="hidden" name="action" value="delete"><input type="hidden" name="id"
                    value="<?= e($c['id']) ?>"><button class="btn danger sm"
                    type="submit"><?= icon_svg('trash') ?></button></form>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php require_once __DIR__ . '/../includes/layout_end.php'; ?>