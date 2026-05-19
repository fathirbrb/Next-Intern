<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/functions.php';
require_admin();

$pageTitle = 'Data Lamaran';
$pageSubtitle = 'Pantau dan kelola seluruh lamaran magang';
$activeMenu = 'applications';
$statuses = ['pending', 'university_review', 'university_approved', 'admin_approved', 'company_review', 'accepted', 'rejected'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = $_POST['action'] ?? '';
  $baseData = [
    trim($_POST['student_name'] ?? ''),
    ($_POST['internship_id'] ?? '') !== '' ? (int) $_POST['internship_id'] : null,
    trim($_POST['position'] ?? ''),
    ($_POST['company_id'] ?? '') !== '' ? (int) $_POST['company_id'] : null,
    trim($_POST['company_name'] ?? ''),
    $_POST['applied_date'] ?: date('Y-m-d'),
  ];

  if ($action === 'create') {
    $stmt = $pdo->prepare("INSERT INTO applications (student_name,internship_id,position,company_id,company_name,status,applied_date) VALUES (?,?,?,?,?,?,?)");
    $stmt->execute([
      $baseData[0],
      $baseData[1],
      $baseData[2],
      $baseData[3],
      $baseData[4],
      'pending',
      $baseData[5],
    ]);
    flash_set('success', 'Lamaran berhasil ditambahkan.');
    redirect('sysadmin/applications.php');
  }
  if ($action === 'update') {
    $baseData[] = (int) ($_POST['id'] ?? 0);
    $stmt = $pdo->prepare("UPDATE applications SET student_name=?, internship_id=?, position=?, company_id=?, company_name=?, applied_date=? WHERE id=?");
    $stmt->execute($baseData);
    flash_set('success', 'Lamaran berhasil diperbarui.');
    redirect('sysadmin/applications.php');
  }
  if ($action === 'delete') {
    $pdo->prepare("DELETE FROM applications WHERE id=?")->execute([(int) ($_POST['id'] ?? 0)]);
    flash_set('success', 'Lamaran berhasil dihapus.');
    redirect('sysadmin/applications.php');
  }
}

require_once __DIR__ . '/../includes/layout_start.php';
$companies = $pdo->query("SELECT id, name FROM companies ORDER BY name")->fetchAll();
$internships = $pdo->query("SELECT id, position FROM internships ORDER BY position")->fetchAll();
$q = trim($_GET['q'] ?? '');
$status = $_GET['status'] ?? 'all';
$where = [];
$params = [];
if ($q !== '') {
  $where[] = '(student_name LIKE ? OR position LIKE ? OR company_name LIKE ?)';
  array_push($params, "%$q%", "%$q%", "%$q%");
}
if ($status !== 'all') {
  $where[] = 'status=?';
  $params[] = $status;
}
$stmt = $pdo->prepare("SELECT * FROM applications" . ($where ? ' WHERE ' . implode(' AND ', $where) : '') . " ORDER BY applied_date DESC,id DESC");
$stmt->execute($params);
$rows = $stmt->fetchAll();
$stat = $pdo->query("SELECT COUNT(*) total, SUM(status='pending') pending, SUM(status='accepted') accepted, SUM(status='rejected') rejected FROM applications")->fetch();
$edit = null;
$editId = (int) ($_GET['edit'] ?? 0);
if ($editId > 0) {
  $stmt = $pdo->prepare("SELECT * FROM applications WHERE id=?");
  $stmt->execute([$editId]);
  $edit = $stmt->fetch() ?: null;
}
?>
<div class="grid grid-4">
  <div class="card stat">
    <div class="stat-number"><?= e($stat['total'] ?? 0) ?></div>
    <div class="stat-title">Total Lamaran</div>
  </div>
  <div class="card stat">
    <div class="stat-number text-yellow"><?= e($stat['pending'] ?? 0) ?></div>
    <div class="stat-title">Pending</div>
  </div>
  <div class="card stat">
    <div class="stat-number text-green"><?= e($stat['accepted'] ?? 0) ?></div>
    <div class="stat-title">Diterima</div>
  </div>
  <div class="card stat">
    <div class="stat-number text-red"><?= e($stat['rejected'] ?? 0) ?></div>
    <div class="stat-title">Ditolak</div>
  </div>
</div>
<div class="card mt-18">
  <form class="toolbar" method="get">
    <div class="searchbox"><?= icon_svg('search') ?><input class="input" name="q" value="<?= e($q) ?>"
        placeholder="Cari mahasiswa, posisi, atau perusahaan..."></div><select class="select" name="status">
      <option value="all">Semua Status</option><?php foreach ($statuses as $s): ?>
        <option value="<?= e($s) ?>" <?= $status === $s ? 'selected' : '' ?>><?= e(status_label($s)) ?></option>
      <?php endforeach; ?>
    </select><button class="btn outline">Filter</button>
  </form>
</div>

<details class="card form-panel">
  <summary><span><?= icon_svg('plus') ?> Tambah Lamaran</span><span class="badge badge-dark">Form</span></summary>
  <form class="form-body" method="post">
    <input type="hidden" name="action" value="create">
    <div class="form-grid">
      <div class="form-field"><label>Mahasiswa</label><input class="input" name="student_name" required></div>
      <div class="form-field"><label>Lowongan Terkait</label><select class="select" name="internship_id">
          <option value="">Tidak ada</option><?php foreach ($internships as $i): ?>
            <option value="<?= e($i['id']) ?>"><?= e($i['position']) ?></option><?php endforeach; ?>
        </select></div>
      <div class="form-field"><label>Posisi</label><input class="input" name="position" required></div>
      <div class="form-field"><label>Perusahaan Terkait</label><select class="select" name="company_id">
          <option value="">Tidak ada</option><?php foreach ($companies as $c): ?>
            <option value="<?= e($c['id']) ?>"><?= e($c['name']) ?></option><?php endforeach; ?>
        </select></div>
      <div class="form-field"><label>Nama Perusahaan</label><input class="input" name="company_name" required></div>
      <div class="form-field"><label>Tanggal Lamar</label><input class="input" type="date" name="applied_date"
          value="<?= e(date('Y-m-d')) ?>" required></div>
    </div>
    <div class="form-actions"><button class="btn" type="submit">Simpan Lamaran</button></div>
  </form>
</details>

<?php if ($edit): ?>
  <details class="card form-panel" open>
    <summary><span><?= icon_svg('edit') ?> Edit Lamaran</span><span class="badge badge-blue">#<?= e($edit['id']) ?></span>
    </summary>
    <form class="form-body" method="post">
      <input type="hidden" name="action" value="update"><input type="hidden" name="id" value="<?= e($edit['id']) ?>">
      <div class="form-grid">
        <div class="form-field"><label>Mahasiswa</label><input class="input" name="student_name"
            value="<?= e($edit['student_name']) ?>" required></div>
        <div class="form-field"><label>Lowongan Terkait</label><select class="select" name="internship_id">
            <option value="">Tidak ada</option><?php foreach ($internships as $i): ?>
              <option value="<?= e($i['id']) ?>" <?= (int) $edit['internship_id'] === (int) $i['id'] ? 'selected' : '' ?>>
                <?= e($i['position']) ?></option><?php endforeach; ?>
          </select></div>
        <div class="form-field"><label>Posisi</label><input class="input" name="position"
            value="<?= e($edit['position']) ?>" required></div>
        <div class="form-field"><label>Perusahaan Terkait</label><select class="select" name="company_id">
            <option value="">Tidak ada</option><?php foreach ($companies as $c): ?>
              <option value="<?= e($c['id']) ?>" <?= (int) $edit['company_id'] === (int) $c['id'] ? 'selected' : '' ?>>
                <?= e($c['name']) ?></option><?php endforeach; ?>
          </select></div>
        <div class="form-field"><label>Nama Perusahaan</label><input class="input" name="company_name"
            value="<?= e($edit['company_name']) ?>" required></div>
        <div class="form-field"><label>Tanggal Lamar</label><input class="input" type="date" name="applied_date"
            value="<?= e($edit['applied_date']) ?>" required></div>
      </div>
      <div class="form-actions"><a class="btn outline" href="<?= e(url('sysadmin/applications.php')) ?>">Batal</a><button
          class="btn" type="submit">Simpan Perubahan</button></div>
    </form>
  </details>
<?php endif; ?>

<div class="card mt-18">
  <div class="table-wrap">
    <table class="table">
      <thead>
        <tr>
          <th>Mahasiswa</th>
          <th>Posisi</th>
          <th>Perusahaan</th>
          <th>Tanggal Lamar</th>
          <th>Status</th>
          <th style="text-align:right">Aksi</th>
        </tr>
      </thead>
      <tbody><?php foreach ($rows as $r): ?>
          <tr>
            <td>
              <div class="entity">
                <div class="entity-icon"><?= icon_svg('user') ?></div>
                <div>
                  <div class="entity-title"><?= e($r['student_name']) ?></div>
                  <div class="entity-sub">ID Lamaran #<?= e($r['id']) ?></div>
                </div>
              </div>
            </td>
            <td><?= e($r['position']) ?></td>
            <td><?= e($r['company_name']) ?></td>
            <td><?= e(format_date_id($r['applied_date'])) ?></td>
            <td><span class="badge <?= status_badge_class($r['status']) ?>"><?= e(status_label($r['status'])) ?></span>
            </td>
            <td>
              <div class="actions"><a class="btn outline sm"
                  href="<?= e(url('sysadmin/applications.php?edit=' . $r['id'])) ?>"><?= icon_svg('edit') ?></a>
                <form method="post"><input type="hidden" name="action" value="delete"><input type="hidden" name="id"
                    value="<?= e($r['id']) ?>"><button class="btn danger sm"
                    type="submit"><?= icon_svg('trash') ?></button></form>
              </div>
            </td>
          </tr><?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php require_once __DIR__ . '/../includes/layout_end.php'; ?>