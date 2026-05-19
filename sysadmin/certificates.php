<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/functions.php';
require_admin();

$pageTitle = 'Data Sertifikat';
$pageSubtitle = 'Kelola sertifikat hasil magang mahasiswa';
$activeMenu = 'certificates';
$statuses = ['issued', 'pending', 'revoked'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = $_POST['action'] ?? '';
  $data = [
    ($_POST['active_intern_id'] ?? '') !== '' ? (int) $_POST['active_intern_id'] : null,
    trim($_POST['student_name'] ?? ''),
    trim($_POST['university'] ?? ''),
    trim($_POST['company_name'] ?? ''),
    trim($_POST['position'] ?? ''),
    $_POST['start_date'] ?? date('Y-m-d'),
    $_POST['end_date'] ?? date('Y-m-d'),
    ($_POST['issued_date'] ?? '') ?: null,
    trim($_POST['certificate_number'] ?? ''),
    $_POST['status'] ?? 'pending',
    trim($_POST['file_path'] ?? ''),
  ];
  if ($action === 'create') {
    $stmt = $pdo->prepare("INSERT INTO certificates (active_intern_id,student_name,university,company_name,position,start_date,end_date,issued_date,certificate_number,status,file_path) VALUES (?,?,?,?,?,?,?,?,?,?,?)");
    $stmt->execute($data);
    flash_set('success', 'Sertifikat berhasil ditambahkan.');
    redirect('sysadmin/certificates.php');
  }
  if ($action === 'update') {
    $data[] = (int) ($_POST['id'] ?? 0);
    $stmt = $pdo->prepare("UPDATE certificates SET active_intern_id=?, student_name=?, university=?, company_name=?, position=?, start_date=?, end_date=?, issued_date=?, certificate_number=?, status=?, file_path=? WHERE id=?");
    $stmt->execute($data);
    flash_set('success', 'Sertifikat berhasil diperbarui.');
    redirect('sysadmin/certificates.php');
  }
  if ($action === 'delete') {
    $pdo->prepare("DELETE FROM certificates WHERE id=?")->execute([(int) ($_POST['id'] ?? 0)]);
    flash_set('success', 'Sertifikat berhasil dihapus.');
    redirect('sysadmin/certificates.php');
  }
}

require_once __DIR__ . '/../includes/layout_start.php';
$interns = $pdo->query("SELECT id, student_name, position FROM active_interns ORDER BY student_name")->fetchAll();
$q = trim($_GET['q'] ?? '');
$status = $_GET['status'] ?? 'all';
$where = [];
$params = [];
if ($q !== '') {
  $where[] = '(student_name LIKE ? OR company_name LIKE ? OR certificate_number LIKE ?)';
  array_push($params, "%$q%", "%$q%", "%$q%");
}
if ($status !== 'all') {
  $where[] = 'status=?';
  $params[] = $status;
}
$stmt = $pdo->prepare("SELECT * FROM certificates" . ($where ? ' WHERE ' . implode(' AND ', $where) : '') . " ORDER BY COALESCE(issued_date, created_at) DESC,id DESC");
$stmt->execute($params);
$rows = $stmt->fetchAll();
$stat = $pdo->query("SELECT COUNT(*) total, SUM(status='issued') issued, SUM(status='pending') pending, SUM(status='revoked') revoked FROM certificates")->fetch();
$edit = null;
$editId = (int) ($_GET['edit'] ?? 0);
if ($editId > 0) {
  $stmt = $pdo->prepare("SELECT * FROM certificates WHERE id=?");
  $stmt->execute([$editId]);
  $edit = $stmt->fetch() ?: null;
}
?>
<div class="grid grid-4">
  <div class="card stat">
    <div class="stat-number"><?= e($stat['total'] ?? 0) ?></div>
    <div class="stat-title">Total Sertifikat</div>
  </div>
  <div class="card stat">
    <div class="stat-number text-green"><?= e($stat['issued'] ?? 0) ?></div>
    <div class="stat-title">Diterbitkan</div>
  </div>
  <div class="card stat">
    <div class="stat-number text-yellow"><?= e($stat['pending'] ?? 0) ?></div>
    <div class="stat-title">Menunggu</div>
  </div>
  <div class="card stat">
    <div class="stat-number text-red"><?= e($stat['revoked'] ?? 0) ?></div>
    <div class="stat-title">Dicabut</div>
  </div>
</div>
<div class="card mt-18">
  <form class="toolbar" method="get">
    <div class="searchbox"><?= icon_svg('search') ?><input class="input" name="q" value="<?= e($q) ?>"
        placeholder="Cari mahasiswa, perusahaan, atau nomor sertifikat..."></div><select class="select" name="status">
      <option value="all">Semua Status</option><?php foreach ($statuses as $s): ?>
        <option value="<?= e($s) ?>" <?= $status === $s ? 'selected' : '' ?>><?= e(status_label($s)) ?></option>
      <?php endforeach; ?>
    </select><button class="btn outline">Filter</button>
  </form>
</div>

<details class="card form-panel">
  <summary><span><?= icon_svg('plus') ?> Tambah Sertifikat</span><span class="badge badge-dark">Form</span></summary>
  <form class="form-body" method="post">
    <input type="hidden" name="action" value="create">
    <div class="form-grid">
      <div class="form-field"><label>Magang Terkait</label><select class="select" name="active_intern_id">
          <option value="">Tidak ada</option><?php foreach ($interns as $i): ?>
            <option value="<?= e($i['id']) ?>"><?= e($i['student_name']) ?> - <?= e($i['position']) ?></option>
          <?php endforeach; ?>
        </select></div>
      <div class="form-field"><label>No. Sertifikat</label><input class="input" name="certificate_number" required>
      </div>
      <div class="form-field"><label>Mahasiswa</label><input class="input" name="student_name" required></div>
      <div class="form-field"><label>Universitas</label><input class="input" name="university" required></div>
      <div class="form-field"><label>Perusahaan</label><input class="input" name="company_name" required></div>
      <div class="form-field"><label>Posisi</label><input class="input" name="position" required></div>
      <div class="form-field"><label>Mulai</label><input class="input" type="date" name="start_date" required></div>
      <div class="form-field"><label>Selesai</label><input class="input" type="date" name="end_date" required></div>
      <div class="form-field"><label>Tanggal Terbit</label><input class="input" type="date" name="issued_date"></div>
      <div class="form-field"><label>Status</label><select class="select"
          name="status"><?php foreach ($statuses as $s): ?>
            <option value="<?= e($s) ?>"><?= e(status_label($s)) ?></option><?php endforeach; ?>
        </select></div>
      <div class="form-field full"><label>File Path</label><input class="input" name="file_path"
          placeholder="uploads/certificates/file.pdf"></div>
    </div>
    <div class="form-actions"><button class="btn" type="submit">Simpan Sertifikat</button></div>
  </form>
</details>

<?php if ($edit): ?>
  <details class="card form-panel" open>
    <summary><span><?= icon_svg('edit') ?> Edit Sertifikat</span><span
        class="badge badge-blue"><?= e($edit['certificate_number']) ?></span></summary>
    <form class="form-body" method="post">
      <input type="hidden" name="action" value="update"><input type="hidden" name="id" value="<?= e($edit['id']) ?>">
      <div class="form-grid">
        <div class="form-field"><label>Magang Terkait</label><select class="select" name="active_intern_id">
            <option value="">Tidak ada</option><?php foreach ($interns as $i): ?>
              <option value="<?= e($i['id']) ?>" <?= (int) $edit['active_intern_id'] === (int) $i['id'] ? 'selected' : '' ?>>
                <?= e($i['student_name']) ?> - <?= e($i['position']) ?></option><?php endforeach; ?>
          </select></div>
        <div class="form-field"><label>No. Sertifikat</label><input class="input" name="certificate_number"
            value="<?= e($edit['certificate_number']) ?>" required></div>
        <div class="form-field"><label>Mahasiswa</label><input class="input" name="student_name"
            value="<?= e($edit['student_name']) ?>" required></div>
        <div class="form-field"><label>Universitas</label><input class="input" name="university"
            value="<?= e($edit['university']) ?>" required></div>
        <div class="form-field"><label>Perusahaan</label><input class="input" name="company_name"
            value="<?= e($edit['company_name']) ?>" required></div>
        <div class="form-field"><label>Posisi</label><input class="input" name="position"
            value="<?= e($edit['position']) ?>" required></div>
        <div class="form-field"><label>Mulai</label><input class="input" type="date" name="start_date"
            value="<?= e($edit['start_date']) ?>" required></div>
        <div class="form-field"><label>Selesai</label><input class="input" type="date" name="end_date"
            value="<?= e($edit['end_date']) ?>" required></div>
        <div class="form-field"><label>Tanggal Terbit</label><input class="input" type="date" name="issued_date"
            value="<?= e($edit['issued_date']) ?>"></div>
        <div class="form-field"><label>Status</label><select class="select"
            name="status"><?php foreach ($statuses as $s): ?>
              <option value="<?= e($s) ?>" <?= $edit['status'] === $s ? 'selected' : '' ?>><?= e(status_label($s)) ?></option>
            <?php endforeach; ?>
          </select></div>
        <div class="form-field full"><label>File Path</label><input class="input" name="file_path"
            value="<?= e($edit['file_path']) ?>"></div>
      </div>
      <div class="form-actions"><a class="btn outline" href="<?= e(url('sysadmin/certificates.php')) ?>">Batal</a><button
          class="btn" type="submit">Simpan Perubahan</button></div>
    </form>
  </details>
<?php endif; ?>

<div class="cards-list mt-18"><?php foreach ($rows as $r): ?>
    <div class="card record-card">
      <div>
        <div class="entity">
          <div class="entity-icon"><?= icon_svg('award') ?></div>
          <div>
            <div class="entity-title"><?= e($r['student_name']) ?></div>
            <div class="entity-sub"><?= e($r['certificate_number']) ?> - <?= e($r['university']) ?></div>
          </div>
        </div>
        <div class="record-meta mt-18">
          <span><?= e($r['company_name']) ?></span><span><?= e($r['position']) ?></span><span><?= e(format_date_id($r['start_date'])) ?>
            - <?= e(format_date_id($r['end_date'])) ?></span><span
            class="badge <?= status_badge_class($r['status']) ?>"><?= e(status_label($r['status'])) ?></span></div>
      </div>
      <div class="actions"><a class="btn outline sm"
          href="<?= e(url('sysadmin/certificates.php?edit=' . $r['id'])) ?>"><?= icon_svg('edit') ?></a>
        <form method="post"><input type="hidden" name="action" value="delete"><input type="hidden" name="id"
            value="<?= e($r['id']) ?>"><button class="btn danger sm" type="submit"><?= icon_svg('trash') ?></button>
        </form>
      </div>
    </div><?php endforeach; ?>
</div>
<?php require_once __DIR__ . '/../includes/layout_end.php'; ?>