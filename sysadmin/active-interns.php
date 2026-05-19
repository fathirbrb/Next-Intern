<?php
require_once __DIR__ . '/../config/database.php';
$pageTitle = 'Data Magang Aktif';
$pageSubtitle = 'Monitoring mahasiswa yang sedang menjalankan magang';
$activeMenu = 'active-interns';
require_once __DIR__ . '/../includes/layout_start.php';
$q = trim($_GET['q'] ?? '');
$status = $_GET['status'] ?? 'all';
$where = [];
$params = [];
if ($q !== '') {
    $where[] = '(student_name LIKE ? OR company_name LIKE ? OR position LIKE ? OR university LIKE ?)';
    $params[] = "%$q%";
    $params[] = "%$q%";
    $params[] = "%$q%";
    $params[] = "%$q%";
}
if ($status !== 'all') {
    $where[] = 'status=?';
    $params[] = $status;
}
$stmt = $pdo->prepare("SELECT * FROM active_interns" . ($where ? ' WHERE ' . implode(' AND ', $where) : '') . " ORDER BY start_date DESC,id DESC");
$stmt->execute($params);
$rows = $stmt->fetchAll();
$stat = $pdo->query("SELECT COUNT(*) total, SUM(status='ongoing') ongoing, SUM(status='completed') completed, ROUND(AVG(progress),0) avg_progress FROM active_interns")->fetch();
?>
<div class="grid grid-4">
    <div class="card stat">
        <div class="stat-number"><?= e($stat['total']) ?></div>
        <div class="stat-title">Total Magang</div>
    </div>
    <div class="card stat">
        <div class="stat-number text-blue"><?= e($stat['ongoing']) ?></div>
        <div class="stat-title">Berjalan</div>
    </div>
    <div class="card stat">
        <div class="stat-number text-green"><?= e($stat['completed']) ?></div>
        <div class="stat-title">Selesai</div>
    </div>
    <div class="card stat">
        <div class="stat-number"><?= e($stat['avg_progress'] ?: 0) ?>%</div>
        <div class="stat-title">Rata-rata Progress</div>
    </div>
</div>
<div class="card" style="margin-top:18px">
    <form class="toolbar" method="get">
        <div class="searchbox"><?= icon_svg('search') ?><input class="input" name="q" value="<?= e($q) ?>"
                placeholder="Cari mahasiswa, kampus, perusahaan..."></div><select class="select" name="status">
            <option value="all">Semua Status</option>
            <option value="ongoing" <?= $status === 'ongoing' ? 'selected' : '' ?>>Berjalan</option>
            <option value="completed" <?= $status === 'completed' ? 'selected' : '' ?>>Selesai</option>
            <option value="terminated" <?= $status === 'terminated' ? 'selected' : '' ?>>Dihentikan</option>
        </select><button class="btn outline">Filter</button>
    </form>
</div>
<div class="cards-list" style="margin-top:18px"><?php foreach ($rows as $r): ?>
        <div class="card record-card">
            <div style="flex:1">
                <div class="entity">
                    <div class="entity-icon"><?= icon_svg('activity') ?></div>
                    <div>
                        <div class="entity-title"><?= e($r['student_name']) ?></div>
                        <div class="entity-sub"><?= e($r['university']) ?> • <?= e($r['position']) ?></div>
                    </div>
                </div>
                <div class="record-meta" style="margin-top:14px">
                    <span><?= e($r['company_name']) ?></span><span><?= e(format_date_id($r['start_date'])) ?> -
                        <?= e(format_date_id($r['end_date'])) ?></span><span
                        class="badge <?= status_badge_class($r['status']) ?>"><?= e(status_label($r['status'])) ?></span>
                </div>
                <div class="progress" style="margin-top:14px"><span style="width:<?= e($r['progress']) ?>%"></span></div>
                <div class="entity-sub" style="margin-top:6px">Progress <?= e($r['progress']) ?>%</div>
            </div>
            <div class="actions"><button class="btn outline sm"><?= icon_svg('eye') ?></button><button
                    class="btn outline sm"><?= icon_svg('edit') ?></button></div>
        </div><?php endforeach; ?>
</div>
<?php require_once __DIR__ . '/../includes/layout_end.php'; ?>