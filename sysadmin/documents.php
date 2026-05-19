<?php
require_once __DIR__ . '/../config/database.php';
$pageTitle = 'Data Dokumen';
$pageSubtitle = 'Kelola CV, surat, dan dokumen lainnya';
$activeMenu = 'documents';
require_once __DIR__ . '/../includes/layout_start.php';
$q = trim($_GET['q'] ?? '');
$type = $_GET['type'] ?? 'all';
$status = $_GET['status'] ?? 'all';
$where = [];
$params = [];
if ($q !== '') {
    $where[] = '(file_name LIKE ? OR student_name LIKE ? OR company_name LIKE ?)';
    $params[] = "%$q%";
    $params[] = "%$q%";
    $params[] = "%$q%";
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
?>
<div class="grid grid-4">
    <div class="card stat">
        <div class="stat-number"><?= e($stat['total']) ?></div>
        <div class="stat-title">Total Dokumen</div>
    </div>
    <div class="card stat">
        <div class="stat-number text-green"><?= e($stat['verified']) ?></div>
        <div class="stat-title">Terverifikasi</div>
    </div>
    <div class="card stat">
        <div class="stat-number text-yellow"><?= e($stat['pending']) ?></div>
        <div class="stat-title">Menunggu</div>
    </div>
    <div class="card stat">
        <div class="stat-number text-red"><?= e($stat['rejected']) ?></div>
        <div class="stat-title">Ditolak</div>
    </div>
</div>
<div class="card" style="margin-top:18px">
    <form class="toolbar" method="get">
        <div class="searchbox"><?= icon_svg('search') ?><input class="input" name="q" value="<?= e($q) ?>"
                placeholder="Cari nama file atau mahasiswa..."></div><select class="select" name="type">
            <option value="all">Semua Tipe</option><?php foreach ($types as $t): ?>
                <option value="<?= e($t['document_type']) ?>" <?= $type === $t['document_type'] ? 'selected' : '' ?>>
                    <?= e($t['document_type']) ?></option><?php endforeach; ?>
        </select><select class="select" name="status">
            <option value="all">Semua Status</option>
            <option value="verified" <?= $status === 'verified' ? 'selected' : '' ?>>Terverifikasi</option>
            <option value="pending" <?= $status === 'pending' ? 'selected' : '' ?>>Menunggu</option>
            <option value="rejected" <?= $status === 'rejected' ? 'selected' : '' ?>>Ditolak</option>
        </select><button class="btn outline">Filter</button>
    </form>
</div>
<div class="card" style="margin-top:18px">
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
                        <td><span
                                class="badge <?= status_badge_class($r['status']) ?>"><?= e(status_label($r['status'])) ?></span>
                        </td>
                        <td>
                            <div class="actions"><button class="btn outline sm"><?= icon_svg('eye') ?></button><button
                                    class="btn outline sm"><?= icon_svg('download') ?></button></div>
                        </td>
                    </tr><?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/layout_end.php'; ?>