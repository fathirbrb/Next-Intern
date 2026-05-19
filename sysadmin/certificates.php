<?php
require_once __DIR__ . '/../config/database.php';
$pageTitle = 'Data Sertifikat';
$pageSubtitle = 'Kelola sertifikat hasil magang mahasiswa';
$activeMenu = 'certificates';
require_once __DIR__ . '/../includes/layout_start.php';
$q = trim($_GET['q'] ?? '');
$status = $_GET['status'] ?? 'all';
$where = [];
$params = [];
if ($q !== '') {
    $where[] = '(student_name LIKE ? OR company_name LIKE ? OR certificate_number LIKE ?)';
    $params[] = "%$q%";
    $params[] = "%$q%";
    $params[] = "%$q%";
}
if ($status !== 'all') {
    $where[] = 'status=?';
    $params[] = $status;
}
$stmt = $pdo->prepare("SELECT * FROM certificates" . ($where ? ' WHERE ' . implode(' AND ', $where) : '') . " ORDER BY COALESCE(issued_date, created_at) DESC,id DESC");
$stmt->execute($params);
$rows = $stmt->fetchAll();
$stat = $pdo->query("SELECT COUNT(*) total, SUM(status='issued') issued, SUM(status='pending') pending, SUM(status='revoked') revoked FROM certificates")->fetch();
?>
<div class="grid grid-4">
    <div class="card stat">
        <div class="stat-number"><?= e($stat['total']) ?></div>
        <div class="stat-title">Total Sertifikat</div>
    </div>
    <div class="card stat">
        <div class="stat-number text-green"><?= e($stat['issued']) ?></div>
        <div class="stat-title">Diterbitkan</div>
    </div>
    <div class="card stat">
        <div class="stat-number text-yellow"><?= e($stat['pending']) ?></div>
        <div class="stat-title">Menunggu</div>
    </div>
    <div class="card stat">
        <div class="stat-number text-red"><?= e($stat['revoked']) ?></div>
        <div class="stat-title">Dicabut</div>
    </div>
</div>
<div class="card" style="margin-top:18px">
    <form class="toolbar" method="get">
        <div class="searchbox"><?= icon_svg('search') ?><input class="input" name="q" value="<?= e($q) ?>"
                placeholder="Cari mahasiswa, perusahaan, atau nomor sertifikat..."></div><select class="select"
            name="status">
            <option value="all">Semua Status</option>
            <option value="issued" <?= $status === 'issued' ? 'selected' : '' ?>>Diterbitkan</option>
            <option value="pending" <?= $status === 'pending' ? 'selected' : '' ?>>Menunggu</option>
            <option value="revoked" <?= $status === 'revoked' ? 'selected' : '' ?>>Dicabut</option>
        </select><button class="btn outline">Filter</button>
    </form>
</div>
<div class="cards-list" style="margin-top:18px"><?php foreach ($rows as $r): ?>
        <div class="card record-card">
            <div>
                <div class="entity">
                    <div class="entity-icon"><?= icon_svg('award') ?></div>
                    <div>
                        <div class="entity-title"><?= e($r['student_name']) ?></div>
                        <div class="entity-sub"><?= e($r['certificate_number']) ?> • <?= e($r['university']) ?></div>
                    </div>
                </div>
                <div class="record-meta" style="margin-top:14px">
                    <span><?= e($r['company_name']) ?></span><span><?= e($r['position']) ?></span><span><?= e(format_date_id($r['start_date'])) ?>
                        - <?= e(format_date_id($r['end_date'])) ?></span><span
                        class="badge <?= status_badge_class($r['status']) ?>"><?= e(status_label($r['status'])) ?></span>
                </div>
            </div>
            <div class="actions"><button class="btn outline sm"><?= icon_svg('eye') ?></button><button
                    class="btn outline sm"><?= icon_svg('download') ?></button></div>
        </div><?php endforeach; ?>
</div>
<?php require_once __DIR__ . '/../includes/layout_end.php'; ?>