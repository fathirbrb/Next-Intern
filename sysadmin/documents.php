<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/functions.php';
require_admin();

$pageTitle = 'Data Dokumen';
$pageSubtitle = 'Kelola CV, surat, dan dokumen lainnya';
$activeMenu = 'documents';
$statuses = ['verified', 'pending', 'rejected'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = $_POST['action'] ?? '';
  $data = [
    trim($_POST['file_name'] ?? ''),
    trim($_POST['document_type'] ?? ''),
    trim($_POST['student_name'] ?? ''),
    trim($_POST['company_name'] ?? ''),
    $_POST['upload_date'] ?: date('Y-m-d'),
    trim($_POST['file_size'] ?? ''),
    $_POST['status'] ?? 'pending',
    trim($_POST['file_path'] ?? ''),
  ];
  if ($action === 'create') {
    $stmt = $pdo->prepare("INSERT INTO documents (file_name,document_type,student_name,company_name,upload_date,file_size,status,file_path) VALUES (?,?,?,?,?,?,?,?)");
    $stmt->execute($data);
    flash_set('success', 'Dokumen berhasil ditambahkan.');
    redirect('sysadmin/documents.php');
  }
  if ($action === 'update') {
    $data[] = (int) ($_POST['id'] ?? 0);
    $stmt = $pdo->prepare("UPDATE documents SET file_name=?, document_type=?, student_name=?, company_name=?, upload_date=?, file_size=?, status=?, file_path=? WHERE id=?");
    $stmt->execute($data);
    flash_set('success', 'Dokumen berhasil diperbarui.');
    redirect('sysadmin/documents.php');
  }
  if ($action === 'delete') {
    $pdo->prepare("DELETE FROM documents WHERE id=?")->execute([(int) ($_POST['id'] ?? 0)]);
    flash_set('success', 'Dokumen berhasil dihapus.');
    redirect('sysadmin/documents.php');
  }
}

require_once __DIR__ . '/../includes/layout_start.php';
$q = trim($_GET['q'] ?? '');
$type = $_GET['type'] ?? 'all';
$status = $_GET['status'] ?? 'all';
$where = [];
$params = [];
if ($q !== '') {
  $where[] = '(file_name LIKE ? OR student_name LIKE ? OR company_name LIKE ?)';
  array_push($params, "%$q%", "%$q%", "%$q%");
}
if ($type !== 'all') {
  $where[] = 'document_type=?';
  $params[] = $type;
}
if ($status !== 'all') {
  $where[] = 'status=?';
  $params[] = $status;
}
$stmt = $pdo->prepare("SELECT * FROM documents" . ($where ? ' WHERE ' . implode(' AND ', $where) : '') . " ORDER BY upload_date DESC,id DESC");
$stmt->execute($params);
$rows = $stmt->fetchAll();
$types = $pdo->query("SELECT DISTINCT document_type FROM documents ORDER BY document_type")->fetchAll();
$stat = $pdo->query("SELECT COUNT(*) total, SUM(status='verified') verified, SUM(status='pending') pending, SUM(status='rejected') rejected FROM documents")->fetch();
$edit = null;
$editId = (int) ($_GET['edit'] ?? 0);
if ($editId > 0) {
  $stmt = $pdo->prepare("SELECT * FROM documents WHERE id=?");
  $stmt->execute([$editId]);
  $edit = $stmt->fetch() ?: null;
}
?>
<div class="grid grid-4">
  <div class="card stat">
    <div class="stat-number"><?= e($stat['total'] ?? 0) ?></div>
    <div class="stat-title">Total Dokumen</div>
  </div>
  <div class="card stat">
    <div class="stat-number text-green"><?= e($stat['verified'] ?? 0) ?></div>
    <div class="stat-title">Terverifikasi</div>
  </div>
  <div class="card stat">
    <div class="stat-number text-yellow"><?= e($stat['pending'] ?? 0) ?></div>
    <div class="stat-title">Menunggu</div>
  </div>
  <div class="card stat">
    <div class="stat-number text-red"><?= e($stat['rejected'] ?? 0) ?></div>
    <div class="stat-title">Ditolak</div>
  </div>
</div>
<div class="card mt-18">
  <form class="toolbar" method="get">
    <div class="searchbox"><?= icon_svg('search') ?><input class="input" name="q" value="<?= e($q) ?>"
        placeholder="Cari nama file atau mahasiswa..."></div><select class="select" name="type">
      <option value="all">Semua Tipe</option><?php foreach ($types as $t): ?>
        <option value="<?= e($t['document_type']) ?>" <?= $type === $t['document_type'] ? 'selected' : '' ?>>
          <?= e($t['document_type']) ?></option><?php endforeach; ?>
    </select><select class="select" name="status">
      <option value="all">Semua Status</option><?php foreach ($statuses as $s): ?>
        <option value="<?= e($s) ?>" <?= $status === $s ? 'selected' : '' ?>><?= e(status_label($s)) ?></option>
      <?php endforeach; ?>
    </select><button class="btn outline">Filter</button>
  </form>
</div>

<details class="card form-panel">
  <summary><span><?= icon_svg('plus') ?> Tambah Dokumen</span><span class="badge badge-dark">Form</span></summary>
  <form class="form-body" method="post">
    <input type="hidden" name="action" value="create">
    <div class="form-grid">
      <div class="form-field"><label>Nama File</label><input class="input" name="file_name" required></div>
      <div class="form-field"><label>Tipe Dokumen</label><input class="input" name="document_type" placeholder="CV"
          required></div>
      <div class="form-field"><label>Mahasiswa</label><input class="input" name="student_name" required></div>
      <div class="form-field"><label>Perusahaan</label><input class="input" name="company_name" required></div>
      <div class="form-field"><label>Tanggal Upload</label><input class="input" type="date" name="upload_date"
          value="<?= e(date('Y-m-d')) ?>" required></div>
      <div class="form-field"><label>Ukuran File</label><input class="input" name="file_size" placeholder="245 KB"
          required></div>
      <div class="form-field"><label>Status</label><select class="select"
          name="status"><?php foreach ($statuses as $s): ?>
            <option value="<?= e($s) ?>"><?= e(status_label($s)) ?></option><?php endforeach; ?>
        </select></div>
      <div class="form-field"><label>File Path</label><input class="input" name="file_path"
          placeholder="uploads/documents/file.pdf"></div>
    </div>
    <div class="form-actions"><button class="btn" type="submit">Simpan Dokumen</button></div>
  </form>
</details>

<?php if ($edit): ?>
  <details class="card form-panel" open>
    <summary><span><?= icon_svg('edit') ?> Edit Dokumen</span><span
        class="badge badge-blue"><?= e($edit['file_name']) ?></span></summary>
    <form class="form-body" method="post">
      <input type="hidden" name="action" value="update"><input type="hidden" name="id" value="<?= e($edit['id']) ?>">
      <div class="form-grid">
        <div class="form-field"><label>Nama File</label><input class="input" name="file_name"
            value="<?= e($edit['file_name']) ?>" required></div>
        <div class="form-field"><label>Tipe Dokumen</label><input class="input" name="document_type"
            value="<?= e($edit['document_type']) ?>" required></div>
        <div class="form-field"><label>Mahasiswa</label><input class="input" name="student_name"
            value="<?= e($edit['student_name']) ?>" required></div>
        <div class="form-field"><label>Perusahaan</label><input class="input" name="company_name"
            value="<?= e($edit['company_name']) ?>" required></div>
        <div class="form-field"><label>Tanggal Upload</label><input class="input" type="date" name="upload_date"
            value="<?= e($edit['upload_date']) ?>" required></div>
        <div class="form-field"><label>Ukuran File</label><input class="input" name="file_size"
            value="<?= e($edit['file_size']) ?>" required></div>
        <div class="form-field"><label>Status</label><select class="select"
            name="status"><?php foreach ($statuses as $s): ?>
              <option value="<?= e($s) ?>" <?= $edit['status'] === $s ? 'selected' : '' ?>><?= e(status_label($s)) ?></option>
            <?php endforeach; ?>
          </select></div>
        <div class="form-field"><label>File Path</label><input class="input" name="file_path"
            value="<?= e($edit['file_path']) ?>"></div>
      </div>
      <div class="form-actions"><a class="btn outline" href="<?= e(url('sysadmin/documents.php')) ?>">Batal</a><button
          class="btn" type="submit">Simpan Perubahan</button></div>
    </form>
  </details>
<?php endif; ?>

<div class="card mt-18">
  <div class="table-wrap">
    <table class="table">
      <thead>
        <tr>
          <th>File</th>
          <th>Tipe</th>
          <th>Mahasiswa</th>
          <th>Perusahaan</th>
          <th>Ukuran</th>
          <th>Status</th>
          <th style="text-align:right">Aksi</th>
        </tr>
      </thead>
      <tbody><?php foreach ($rows as $r): ?>
          <tr>
            <td>
              <div class="entity">
                <div class="entity-icon"><?= icon_svg('file') ?></div>
                <div>
                  <div class="entity-title"><?= e($r['file_name']) ?></div>
                  <div class="entity-sub"><?= e(format_date_id($r['upload_date'])) ?></div>
                </div>
              </div>
            </td>
            <td><span class="badge badge-blue"><?= e($r['document_type']) ?></span></td>
            <td><?= e($r['student_name']) ?></td>
            <td><?= e($r['company_name']) ?></td>
            <td><?= e($r['file_size']) ?></td>
            <td><span class="badge <?= status_badge_class($r['status']) ?>"><?= e(status_label($r['status'])) ?></span>
            </td>
            <td>
              <div class="actions"><a class="btn outline sm"
                  href="<?= e(url('sysadmin/documents.php?edit=' . $r['id'])) ?>"><?= icon_svg('edit') ?></a>
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