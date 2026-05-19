<?php
require_once __DIR__ . '/../config/database.php';
$pageTitle = 'Data Lamaran';
$pageSubtitle = 'Pantau dan kelola seluruh lamaran magang';
$activeMenu = 'applications';
require_once __DIR__ . '/../includes/layout_start.php';
$q = trim($_GET['q'] ?? '');
$status = $_GET['status'] ?? 'all';
$where = [];
$params = [];
if ($q !== '') {
    $where[] = '(student_name LIKE ? OR position LIKE ? OR company_name LIKE ?)';
    $params[] = "%$q%";
    $params[] = "%$q%";
    $params[] = "%$q%";
}
if ($status !== 'all') {
    $where[] = 'status=?';
    $params[] = $status;
}
$stmt = $pdo->prepare("SELECT * FROM applications" . ($where ? ' WHERE ' . implode(' AND ', $where) : '') . " ORDER BY applied_date DESC,id DESC");
$stmt->execute($params);
$rows = $stmt->fetchAll();
$stat = $pdo->query("SELECT COUNT(*) total, SUM(status='pending') pending, SUM(status='accepted') accepted, SUM(status='rejected') rejected FROM applications")->fetch();
?>
<div class="grid grid-4">
    <div class="card stat">
        <div class="stat-number"><?= e($stat['total']) ?></div>
        <div class="stat-title">Total Lamaran</div>
    </div>
    <div class="card stat">
        <div class="stat-number text-yellow"><?= e($stat['pending']) ?></div>
        <div class="stat-title">Pending</div>
    </div>
    <div class="card stat">
        <div class="stat-number text-green"><?= e($stat['accepted']) ?></div>
        <div class="stat-title">Diterima</div>
    </div>
    <div class="card stat">
        <div class="stat-number text-red"><?= e($stat['rejected']) ?></div>
        <div class="stat-title">Ditolak</div>
    </div>
</div>
<div class="card" style="margin-top:18px">
    <form class="toolbar" method="get">
        <div class="searchbox"><?= icon_svg('search') ?><input class="input" name="q" value="<?= e($q) ?>"
                placeholder="Cari mahasiswa, posisi, atau perusahaan..."></div><select class="select" name="status">
            <option value="all">Semua Status</option>
            <?php foreach (['pending', 'university_review', 'company_review', 'accepted', 'rejected'] as $s): ?>
                <option value="<?= e($s) ?>" <?= $status === $s ? 'selected' : '' ?>><?= e(status_label($s)) ?></option>
            <?php endforeach; ?>
        </select><button class="btn outline">Filter</button>
    </form>
</div>
<div class="card" style="margin-top:18px">
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
                        <td><span
                                class="badge <?= status_badge_class($r['status']) ?>"><?= e(status_label($r['status'])) ?></span>
                        </td>
                        <td>
                            <div class="actions"><button class="btn outline sm"><?= icon_svg('eye') ?></button><button
                                    class="btn outline sm"><?= icon_svg('edit') ?></button></div>
                        </td>
                    </tr><?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/layout_end.php'; ?>