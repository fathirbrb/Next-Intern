<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/functions.php';
require_admin();

$pageTitle = 'Data Lowongan';
$pageSubtitle = 'Kelola lowongan magang dari perusahaan mitra';
$activeMenu = 'internships';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = $_POST['action'] ?? '';
  $data = [
    ($_POST['company_id'] ?? '') !== '' ? (int) $_POST['company_id'] : null,
    trim($_POST['position'] ?? ''),
    trim($_POST['description'] ?? ''),
    trim($_POST['location'] ?? ''),
    trim($_POST['duration'] ?? ''),
    $_POST['deadline'] ?? date('Y-m-d'),
    max(1, (int) ($_POST['slots'] ?? 1)),
    $_POST['posted_date'] ?: date('Y-m-d'),
    $_POST['status'] ?? 'active',
  ];

  if ($action === 'create') {
    $stmt = $pdo->prepare("INSERT INTO internships (company_id,position,description,location,duration,deadline,slots,posted_date,status) VALUES (?,?,?,?,?,?,?,?,?)");
    $stmt->execute($data);
    flash_set('success', 'Lowongan berhasil ditambahkan.');
    redirect('sysadmin/internships.php');
  }

  if ($action === 'update') {
    $data[] = (int) ($_POST['id'] ?? 0);
    $stmt = $pdo->prepare("UPDATE internships SET company_id=?, position=?, description=?, location=?, duration=?, deadline=?, slots=?, posted_date=?, status=? WHERE id=?");
    $stmt->execute($data);
    flash_set('success', 'Lowongan berhasil diperbarui.');
    redirect('sysadmin/internships.php');
  }

  if ($action === 'delete') {
    $pdo->prepare("DELETE FROM internships WHERE id=?")->execute([(int) ($_POST['id'] ?? 0)]);
    flash_set('success', 'Lowongan berhasil dihapus.');
    redirect('sysadmin/internships.php');
  }
}

require_once __DIR__ . '/../includes/layout_start.php';

$companies = $pdo->query("SELECT id, name FROM companies ORDER BY name")->fetchAll();
$q = trim($_GET['q'] ?? '');
$status = $_GET['status'] ?? 'all';
$where = [];
$params = [];
if ($q !== '') {
  $where[] = '(i.position LIKE ? OR c.name LIKE ? OR i.location LIKE ?)';
  array_push($params, "%$q%", "%$q%", "%$q%");
}
if ($status !== 'all') {
  $where[] = 'i.status=?';
  $params[] = $status;
}
$stmt = $pdo->prepare("SELECT i.*, c.name company_name FROM internships i LEFT JOIN companies c ON c.id=i.company_id" . ($where ? ' WHERE ' . implode(' AND ', $where) : '') . " ORDER BY i.posted_date DESC,i.id DESC");
$stmt->execute($params);
$rows = $stmt->fetchAll();
$stat = $pdo->query("SELECT COUNT(*) total, SUM(status='active') active, SUM(status='closed') closed, SUM(status='draft') draft FROM internships")->fetch();
$edit = null;
$editId = (int) ($_GET['edit'] ?? 0);
if ($editId > 0) {
  $stmt = $pdo->prepare("SELECT * FROM internships WHERE id=?");
  $stmt->execute([$editId]);
  $edit = $stmt->fetch() ?: null;
}
?>
<div class="grid grid-4">
  <div class="card stat">
    <div class="stat-number"><?= e($stat['total'] ?? 0) ?></div>
    <div class="stat-title">Total Lowongan</div>
  </div>
  <div class="card stat">
    <div class="stat-number text-green"><?= e($stat['active'] ?? 0) ?></div>
    <div class="stat-title">Aktif</div>
  </div>
  <div class="card stat">
    <div class="stat-number text-red"><?= e($stat['closed'] ?? 0) ?></div>
    <div class="stat-title">Ditutup</div>
  </div>
  <div class="card stat">
    <div class="stat-number text-yellow"><?= e($stat['draft'] ?? 0) ?></div>
    <div class="stat-title">Draft</div>
  </div>
</div>

<div class="card mt-18">
  <form class="toolbar" method="get">
    <div class="searchbox"><?= icon_svg('search') ?><input class="input" name="q" value="<?= e($q) ?>"
        placeholder="Cari posisi, perusahaan, atau lokasi..."></div>
    <select class="select" name="status">
      <option value="all">Semua Status</option>
      <option value="active" <?= $status === 'active' ? 'selected' : '' ?>>Aktif</option>
      <option value="closed" <?= $status === 'closed' ? 'selected' : '' ?>>Ditutup</option>
      <option value="draft" <?= $status === 'draft' ? 'selected' : '' ?>>Draft</option>
    </select>
    <button class="btn outline">Filter</button>
  </form>
</div>

<details class="card form-panel">
  <summary><span><?= icon_svg('plus') ?> Tambah Lowongan</span><span class="badge badge-dark">Form</span></summary>
  <form class="form-body" method="post">
    <input type="hidden" name="action" value="create">
    <div class="form-grid">
      <div class="form-field"><label>Perusahaan</label><select class="select" name="company_id">
          <option value="">Tanpa Perusahaan</option><?php foreach ($companies as $c): ?>
            <option value="<?= e($c['id']) ?>"><?= e($c['name']) ?></option><?php endforeach; ?>
        </select></div>
      <div class="form-field"><label>Posisi</label><input class="input" name="position" required></div>
      <div class="form-field"><label>Lokasi</label><input class="input" name="location" required></div>
      <div class="form-field"><label>Durasi</label><input class="input" name="duration" placeholder="6 bulan" required>
      </div>
      <div class="form-field"><label>Deadline</label><input class="input" type="date" name="deadline" required></div>
      <div class="form-field"><label>Tanggal Posting</label><input class="input" type="date" name="posted_date"
          value="<?= e(date('Y-m-d')) ?>"></div>
      <div class="form-field"><label>Kuota</label><input class="input" type="number" min="1" name="slots" value="1"
          required></div>
      <div class="form-field"><label>Status</label><select class="select" name="status">
          <option value="active">Aktif</option>
          <option value="closed">Ditutup</option>
          <option value="draft">Draft</option>
        </select></div>
      <div class="form-field full"><label>Deskripsi</label><textarea class="input" name="description"
          required></textarea></div>
    </div>
    <div class="form-actions"><button class="btn" type="submit">Simpan Lowongan</button></div>
  </form>
</details>

<?php if ($edit): ?>
  <details class="card form-panel" open>
    <summary><span><?= icon_svg('edit') ?> Edit Lowongan</span><span
        class="badge badge-blue"><?= e($edit['position']) ?></span></summary>
    <form class="form-body" method="post">
      <input type="hidden" name="action" value="update">
      <input type="hidden" name="id" value="<?= e($edit['id']) ?>">
      <div class="form-grid">
        <div class="form-field"><label>Perusahaan</label><select class="select" name="company_id">
            <option value="">Tanpa Perusahaan</option><?php foreach ($companies as $c): ?>
              <option value="<?= e($c['id']) ?>" <?= (int) $edit['company_id'] === (int) $c['id'] ? 'selected' : '' ?>>
                <?= e($c['name']) ?></option><?php endforeach; ?>
          </select></div>
        <div class="form-field"><label>Posisi</label><input class="input" name="position"
            value="<?= e($edit['position']) ?>" required></div>
        <div class="form-field"><label>Lokasi</label><input class="input" name="location"
            value="<?= e($edit['location']) ?>" required></div>
        <div class="form-field"><label>Durasi</label><input class="input" name="duration"
            value="<?= e($edit['duration']) ?>" required></div>
        <div class="form-field"><label>Deadline</label><input class="input" type="date" name="deadline"
            value="<?= e($edit['deadline']) ?>" required></div>
        <div class="form-field"><label>Tanggal Posting</label><input class="input" type="date" name="posted_date"
            value="<?= e($edit['posted_date']) ?>"></div>
        <div class="form-field"><label>Kuota</label><input class="input" type="number" min="1" name="slots"
            value="<?= e($edit['slots']) ?>" required></div>
        <div class="form-field"><label>Status</label><select class="select" name="status">
            <option value="active" <?= $edit['status'] === 'active' ? 'selected' : '' ?>>Aktif</option>
            <option value="closed" <?= $edit['status'] === 'closed' ? 'selected' : '' ?>>Ditutup</option>
            <option value="draft" <?= $edit['status'] === 'draft' ? 'selected' : '' ?>>Draft</option>
          </select></div>
        <div class="form-field full"><label>Deskripsi</label><textarea class="input" name="description"
            required><?= e($edit['description']) ?></textarea></div>
      </div>
      <div class="form-actions"><a class="btn outline" href="<?= e(url('sysadmin/internships.php')) ?>">Batal</a><button
          class="btn" type="submit">Simpan Perubahan</button></div>
    </form>
  </details>
<?php endif; ?>

<div class="card mt-18">
  <div class="table-wrap">
    <table class="table">
      <thead>
        <tr>
          <th>Lowongan</th>
          <th>Perusahaan</th>
          <th>Lokasi</th>
          <th>Durasi</th>
          <th>Kuota</th>
          <th>Deadline</th>
          <th>Status</th>
          <th style="text-align:right">Aksi</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($rows as $r): ?>
          <tr>
            <td>
              <div class="entity">
                <div class="entity-icon"><?= icon_svg('briefcase') ?></div>
                <div>
                  <div class="entity-title"><?= e($r['position']) ?></div>
                  <div class="entity-sub"><?= e(short_text($r['description'], 60)) ?></div>
                </div>
              </div>
            </td>
            <td><?= e($r['company_name'] ?? '-') ?></td>
            <td><?= e($r['location']) ?></td>
            <td><?= e($r['duration']) ?></td>
            <td><?= e($r['slots']) ?></td>
            <td><?= e(format_date_id($r['deadline'])) ?></td>
            <td><span class="badge <?= status_badge_class($r['status']) ?>"><?= e(status_label($r['status'])) ?></span>
            </td>
            <td>
              <div class="actions"><a class="btn outline sm"
                  href="<?= e(url('sysadmin/internships.php?edit=' . $r['id'])) ?>"><?= icon_svg('edit') ?></a>
                <form method="post"><input type="hidden" name="action" value="delete"><input type="hidden" name="id"
                    value="<?= e($r['id']) ?>"><button class="btn danger sm"
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