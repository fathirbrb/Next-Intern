<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/functions.php';
require_admin();

$pageTitle = 'Data Magang Aktif';
$pageSubtitle = 'Monitoring mahasiswa yang sedang menjalankan magang';
$activeMenu = 'active-interns';
$statuses = ['ongoing', 'completed', 'terminated'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = $_POST['action'] ?? '';
  $progress = max(0, min(100, (int) ($_POST['progress'] ?? 0)));
  $data = [
    trim($_POST['student_name'] ?? ''),
    trim($_POST['university'] ?? ''),
    ($_POST['company_id'] ?? '') !== '' ? (int) $_POST['company_id'] : null,
    trim($_POST['company_name'] ?? ''),
    ($_POST['internship_id'] ?? '') !== '' ? (int) $_POST['internship_id'] : null,
    trim($_POST['position'] ?? ''),
    $_POST['start_date'] ?? date('Y-m-d'),
    $_POST['end_date'] ?? date('Y-m-d'),
    $_POST['status'] ?? 'ongoing',
    $progress,
  ];

  if ($action === 'create') {
    $stmt = $pdo->prepare("INSERT INTO active_interns (student_name,university,company_id,company_name,internship_id,position,start_date,end_date,status,progress) VALUES (?,?,?,?,?,?,?,?,?,?)");
    $stmt->execute($data);
    flash_set('success', 'Data magang aktif berhasil ditambahkan.');
    redirect('sysadmin/active-interns.php');
  }
  if ($action === 'update') {
    $data[] = (int) ($_POST['id'] ?? 0);
    $stmt = $pdo->prepare("UPDATE active_interns SET student_name=?, university=?, company_id=?, company_name=?, internship_id=?, position=?, start_date=?, end_date=?, status=?, progress=? WHERE id=?");
    $stmt->execute($data);
    flash_set('success', 'Data magang aktif berhasil diperbarui.');
    redirect('sysadmin/active-interns.php');
  }
  if ($action === 'delete') {
    $pdo->prepare("DELETE FROM active_interns WHERE id=?")->execute([(int) ($_POST['id'] ?? 0)]);
    flash_set('success', 'Data magang aktif berhasil dihapus.');
    redirect('sysadmin/active-interns.php');
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
  $where[] = '(student_name LIKE ? OR company_name LIKE ? OR position LIKE ? OR university LIKE ?)';
  array_push($params, "%$q%", "%$q%", "%$q%", "%$q%");
}
if ($status !== 'all') {
  $where[] = 'status=?';
  $params[] = $status;
}
$stmt = $pdo->prepare("SELECT * FROM active_interns" . ($where ? ' WHERE ' . implode(' AND ', $where) : '') . " ORDER BY start_date DESC,id DESC");
$stmt->execute($params);
$rows = $stmt->fetchAll();
$stat = $pdo->query("SELECT COUNT(*) total, SUM(status='ongoing') ongoing, SUM(status='completed') completed, ROUND(AVG(progress),0) avg_progress FROM active_interns")->fetch();
$edit = null;
$editId = (int) ($_GET['edit'] ?? 0);
if ($editId > 0) {
  $stmt = $pdo->prepare("SELECT * FROM active_interns WHERE id=?");
  $stmt->execute([$editId]);
  $edit = $stmt->fetch() ?: null;
}
?>
<div class="grid grid-4">
  <div class="card stat">
    <div class="stat-number"><?= e($stat['total'] ?? 0) ?></div>
    <div class="stat-title">Total Magang</div>
  </div>
  <div class="card stat">
    <div class="stat-number text-blue"><?= e($stat['ongoing'] ?? 0) ?></div>
    <div class="stat-title">Berjalan</div>
  </div>
  <div class="card stat">
    <div class="stat-number text-green"><?= e($stat['completed'] ?? 0) ?></div>
    <div class="stat-title">Selesai</div>
  </div>
  <div class="card stat">
    <div class="stat-number"><?= e($stat['avg_progress'] ?: 0) ?>%</div>
    <div class="stat-title">Rata-rata Progress</div>
  </div>
</div>
<div class="card mt-18">
  <form class="toolbar" method="get">
    <div class="searchbox"><?= icon_svg('search') ?><input class="input" name="q" value="<?= e($q) ?>"
        placeholder="Cari mahasiswa, kampus, perusahaan..."></div><select class="select" name="status">
      <option value="all">Semua Status</option><?php foreach ($statuses as $s): ?>
        <option value="<?= e($s) ?>" <?= $status === $s ? 'selected' : '' ?>><?= e(status_label($s)) ?></option>
      <?php endforeach; ?>
    </select><button class="btn outline">Filter</button>
  </form>
</div>

<details class="card form-panel">
  <summary><span><?= icon_svg('plus') ?> Tambah Magang Aktif</span><span class="badge badge-dark">Form</span></summary>
  <form class="form-body" method="post">
    <input type="hidden" name="action" value="create">
    <div class="form-grid">
      <div class="form-field"><label>Mahasiswa</label><input class="input" name="student_name" required></div>
      <div class="form-field"><label>Universitas</label><input class="input" name="university" required></div>
      <div class="form-field"><label>Perusahaan Terkait</label><select class="select" name="company_id">
          <option value="">Tidak ada</option><?php foreach ($companies as $c): ?>
            <option value="<?= e($c['id']) ?>"><?= e($c['name']) ?></option><?php endforeach; ?>
        </select></div>
      <div class="form-field"><label>Nama Perusahaan</label><input class="input" name="company_name" required></div>
      <div class="form-field"><label>Lowongan Terkait</label><select class="select" name="internship_id">
          <option value="">Tidak ada</option><?php foreach ($internships as $i): ?>
            <option value="<?= e($i['id']) ?>"><?= e($i['position']) ?></option><?php endforeach; ?>
        </select></div>
      <div class="form-field"><label>Posisi</label><input class="input" name="position" required></div>
      <div class="form-field"><label>Mulai</label><input class="input" type="date" name="start_date" required></div>
      <div class="form-field"><label>Selesai</label><input class="input" type="date" name="end_date" required></div>
      <div class="form-field"><label>Status</label><select class="select"
          name="status"><?php foreach ($statuses as $s): ?>
            <option value="<?= e($s) ?>"><?= e(status_label($s)) ?></option><?php endforeach; ?>
        </select></div>
      <div class="form-field"><label>Progress</label><input class="input" type="number" min="0" max="100"
          name="progress" value="0" required></div>
    </div>
    <div class="form-actions"><button class="btn" type="submit">Simpan Magang</button></div>
  </form>
</details>

<?php if ($edit): ?>
  <details class="card form-panel" open>
    <summary><span><?= icon_svg('edit') ?> Edit Magang Aktif</span><span
        class="badge badge-blue"><?= e($edit['student_name']) ?></span></summary>
    <form class="form-body" method="post">
      <input type="hidden" name="action" value="update"><input type="hidden" name="id" value="<?= e($edit['id']) ?>">
      <div class="form-grid">
        <div class="form-field"><label>Mahasiswa</label><input class="input" name="student_name"
            value="<?= e($edit['student_name']) ?>" required></div>
        <div class="form-field"><label>Universitas</label><input class="input" name="university"
            value="<?= e($edit['university']) ?>" required></div>
        <div class="form-field"><label>Perusahaan Terkait</label><select class="select" name="company_id">
            <option value="">Tidak ada</option><?php foreach ($companies as $c): ?>
              <option value="<?= e($c['id']) ?>" <?= (int) $edit['company_id'] === (int) $c['id'] ? 'selected' : '' ?>>
                <?= e($c['name']) ?></option><?php endforeach; ?>
          </select></div>
        <div class="form-field"><label>Nama Perusahaan</label><input class="input" name="company_name"
            value="<?= e($edit['company_name']) ?>" required></div>
        <div class="form-field"><label>Lowongan Terkait</label><select class="select" name="internship_id">
            <option value="">Tidak ada</option><?php foreach ($internships as $i): ?>
              <option value="<?= e($i['id']) ?>" <?= (int) $edit['internship_id'] === (int) $i['id'] ? 'selected' : '' ?>>
                <?= e($i['position']) ?></option><?php endforeach; ?>
          </select></div>
        <div class="form-field"><label>Posisi</label><input class="input" name="position"
            value="<?= e($edit['position']) ?>" required></div>
        <div class="form-field"><label>Mulai</label><input class="input" type="date" name="start_date"
            value="<?= e($edit['start_date']) ?>" required></div>
        <div class="form-field"><label>Selesai</label><input class="input" type="date" name="end_date"
            value="<?= e($edit['end_date']) ?>" required></div>
        <div class="form-field"><label>Status</label><select class="select"
            name="status"><?php foreach ($statuses as $s): ?>
              <option value="<?= e($s) ?>" <?= $edit['status'] === $s ? 'selected' : '' ?>><?= e(status_label($s)) ?></option>
            <?php endforeach; ?>
          </select></div>
        <div class="form-field"><label>Progress</label><input class="input" type="number" min="0" max="100"
            name="progress" value="<?= e($edit['progress']) ?>" required></div>
      </div>
      <div class="form-actions"><a class="btn outline"
          href="<?= e(url('sysadmin/active-interns.php')) ?>">Batal</a><button class="btn" type="submit">Simpan
          Perubahan</button></div>
    </form>
  </details>
<?php endif; ?>

<div class="cards-list mt-18"><?php foreach ($rows as $r): ?>
    <div class="card record-card">
      <div style="flex:1">
        <div class="entity">
          <div class="entity-icon"><?= icon_svg('activity') ?></div>
          <div>
            <div class="entity-title"><?= e($r['student_name']) ?></div>
            <div class="entity-sub"><?= e($r['university']) ?> - <?= e($r['position']) ?></div>
          </div>
        </div>
        <div class="record-meta mt-18">
          <span><?= e($r['company_name']) ?></span><span><?= e(format_date_id($r['start_date'])) ?> -
            <?= e(format_date_id($r['end_date'])) ?></span><span
            class="badge <?= status_badge_class($r['status']) ?>"><?= e(status_label($r['status'])) ?></span></div>
        <div class="progress mt-18"><span style="width:<?= e($r['progress']) ?>%"></span></div>
        <div class="entity-sub" style="margin-top:6px">Progress <?= e($r['progress']) ?>%</div>
      </div>
      <div class="actions"><a class="btn outline sm"
          href="<?= e(url('sysadmin/active-interns.php?edit=' . $r['id'])) ?>"><?= icon_svg('edit') ?></a>
        <form method="post"><input type="hidden" name="action" value="delete"><input type="hidden" name="id"
            value="<?= e($r['id']) ?>"><button class="btn danger sm" type="submit"><?= icon_svg('trash') ?></button>
        </form>
      </div>
    </div><?php endforeach; ?>
</div>
<?php require_once __DIR__ . '/../includes/layout_end.php'; ?>