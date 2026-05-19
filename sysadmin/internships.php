<?php
require_once __DIR__ . '/../config/database.php';
$pageTitle = 'Data Lowongan';
$pageSubtitle = 'Kelola lowongan magang dari perusahaan mitra';
$activeMenu = 'internships';
require_once __DIR__ . '/../includes/layout_start.php';
$q = trim($_GET['q'] ?? '');
$status = $_GET['status'] ?? 'all';
$where = [];
$params = [];
if ($q !== '') {
    $where[] = '(i.position LIKE ? OR c.name LIKE ? OR i.location LIKE ?)';
    $params[] = "%$q%";
    $params[] = "%$q%";
    $params[] = "%$q%";
}
if ($status !== 'all') {
    $where[] = 'i.status=?';
    $params[] = $status;
}
$stmt = $pdo->prepare("SELECT i.*, c.name company_name FROM internships i LEFT JOIN companies c ON c.id=i.company_id" . ($where ? ' WHERE ' . implode(' AND ', $where) : '') . " ORDER BY i.posted_date DESC,i.id DESC");
$stmt->execute($params);
$rows = $stmt->fetchAll();
$stat = $pdo->query("SELECT COUNT(*) total, SUM(status='active') active, SUM(status='closed') closed, SUM(status='draft') draft FROM internships")->fetch();
?>
<div class="grid grid-4">
    <div class="card stat">
        <div class="stat-number"><?= e($stat['total']) ?></div>
        <div class="stat-title">Total Lowongan</div>
    </div>
    <div class="card stat">
        <div class="stat-number text-green"><?= e($stat['active']) ?></div>
        <div class="stat-title">Aktif</div>
    </div>
    <div class="card stat">
        <div class="stat-number text-red"><?= e($stat['closed']) ?></div>
        <div class="stat-title">Ditutup</div>
    </div>
    <div class="card stat">
        <div class="stat-number text-yellow"><?= e($stat['draft']) ?></div>
        <div class="stat-title">Draft</div>
    </div>
</div>
<div class="card" style="margin-top:18px">
    <form class="toolbar" method="get">
        <div class="searchbox"><?= icon_svg('search') ?><input class="input" name="q" value="<?= e($q) ?>"
                placeholder="Cari posisi, perusahaan, atau lokasi..."></div><select class="select" name="status">
            <option value="all">Semua Status</option>
            <option value="active" <?= $status === 'active' ? 'selected' : '' ?>>Aktif</option>
            <option value="closed" <?= $status === 'closed' ? 'selected' : '' ?>>Ditutup</option>
            <option value="draft" <?= $status === 'draft' ? 'selected' : '' ?>>Draft</option>
        </select><button class="btn outline">Filter</button><button class="btn"
            type="button"><?= icon_svg('plus') ?>Tambah Lowongan</button>
    </form>
</div>
<div class="card" style="margin-top:18px">
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
            <tbody><?php foreach ($rows as $r): ?>
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
                        <td><span
                                class="badge <?= status_badge_class($r['status']) ?>"><?= e(status_label($r['status'])) ?></span>
                        </td>
                        <td>
                            <div class="actions"><button class="btn outline sm"><?= icon_svg('edit') ?></button><button
                                    class="btn danger sm"><?= icon_svg('trash') ?></button></div>
                        </td>
                    </tr><?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/layout_end.php'; ?>