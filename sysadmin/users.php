<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/functions.php';
require_admin();
$pageTitle = 'Data Pengguna';
$pageSubtitle = 'Kelola akun pengguna dan hak akses sistem';
$activeMenu = 'users';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = $_POST['action'] ?? '';
  if ($action === 'create') {
    $stmt = $pdo->prepare("INSERT INTO users (name,email,password_hash,role_id,status) VALUES (?,?,?,?,?)");
    $stmt->execute([
      trim($_POST['name'] ?? ''),
      trim($_POST['email'] ?? ''),
      password_hash(($_POST['password'] ?? '') !== '' ? $_POST['password'] : 'password', PASSWORD_DEFAULT),
      (int) ($_POST['role_id'] ?? 1),
      $_POST['status'] ?? 'active'
    ]);
    flash_set('success', 'Pengguna baru berhasil ditambahkan.');
    redirect('sysadmin/users.php');
  }
  if ($action === 'update') {
    $id = (int) ($_POST['id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $roleId = (int) ($_POST['role_id'] ?? 1);
    $status = $_POST['status'] ?? 'active';

    if ($password !== '') {
      $stmt = $pdo->prepare("UPDATE users SET name=?, email=?, password_hash=?, role_id=?, status=? WHERE id=?");
      $stmt->execute([$name, $email, password_hash($password, PASSWORD_DEFAULT), $roleId, $status, $id]);
    } else {
      $stmt = $pdo->prepare("UPDATE users SET name=?, email=?, role_id=?, status=? WHERE id=?");
      $stmt->execute([$name, $email, $roleId, $status, $id]);
    }

    if ($id === (int) ($_SESSION['user']['id'] ?? 0)) {
      $stmt = $pdo->prepare("SELECT u.id, u.name, u.email, r.role_key, r.role_name FROM users u JOIN roles r ON r.id = u.role_id WHERE u.id = ?");
      $stmt->execute([$id]);
      $current = $stmt->fetch();
      if ($current) {
        $_SESSION['user'] = [
          'id' => (int) $current['id'],
          'name' => $current['name'],
          'email' => $current['email'],
          'role_key' => $current['role_key'],
          'role_name' => $current['role_name'],
        ];
      }
    }

    flash_set('success', 'Data pengguna berhasil diperbarui.');
    redirect('sysadmin/users.php');
  }
  if ($action === 'delete') {
    $stmt = $pdo->prepare("DELETE FROM users WHERE id=? AND id<>?");
    $stmt->execute([(int) ($_POST['id'] ?? 0), (int) ($_SESSION['user']['id'] ?? 0)]);
    flash_set('success', 'Data pengguna berhasil dihapus.');
    redirect('sysadmin/users.php');
  }
}

require_once __DIR__ . '/../includes/layout_start.php';
$q = trim($_GET['q'] ?? '');
$role = $_GET['role'] ?? 'all';
$status = $_GET['status'] ?? 'all';
$where = [];
$params = [];
if ($q !== '') {
  $where[] = "(u.name LIKE ? OR u.email LIKE ?)";
  $params[] = "%$q%";
  $params[] = "%$q%";
}
if ($role !== 'all') {
  $where[] = "r.role_key=?";
  $params[] = $role;
}
if ($status !== 'all') {
  $where[] = "u.status=?";
  $params[] = $status;
}
$sql = "SELECT u.*, r.role_key, r.role_name FROM users u JOIN roles r ON r.id=u.role_id" . ($where ? " WHERE " . implode(' AND ', $where) : '') . " ORDER BY u.created_at DESC, u.id DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$users = $stmt->fetchAll();
$roles = $pdo->query("SELECT * FROM roles ORDER BY id")->fetchAll();
$stat = $pdo->query("SELECT COUNT(*) total, SUM(role_id=1) mahasiswa, SUM(role_id=2) perusahaan, SUM(role_id IN(3,4)) admin FROM users")->fetch();
$editUser = null;
$editId = (int) ($_GET['edit'] ?? 0);
if ($editId > 0) {
  $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
  $stmt->execute([$editId]);
  $editUser = $stmt->fetch() ?: null;
}
?>
<div class="grid grid-4">
  <div class="card stat">
    <div class="stat-title">Total Pengguna</div>
    <div class="stat-number"><?= e($stat['total'] ?? 0) ?></div>
  </div>
  <div class="card stat">
    <div class="stat-title">Mahasiswa</div>
    <div class="stat-number"><?= e($stat['mahasiswa'] ?? 0) ?></div>
  </div>
  <div class="card stat">
    <div class="stat-title">Perusahaan</div>
    <div class="stat-number"><?= e($stat['perusahaan'] ?? 0) ?></div>
  </div>
  <div class="card stat">
    <div class="stat-title">Admin</div>
    <div class="stat-number"><?= e($stat['admin'] ?? 0) ?></div>
  </div>
</div>

<div class="card mt-18">
  <form class="toolbar" method="get">
    <div class="searchbox"><?= icon_svg('search') ?><input class="input" name="q" value="<?= e($q) ?>"
        placeholder="Cari nama atau email pengguna..."></div>
    <select class="select" name="role">
      <option value="all">Semua Role</option>
      <?php foreach ($roles as $r): ?>
        <option value="<?= e($r['role_key']) ?>" <?= $role === $r['role_key'] ? 'selected' : '' ?>><?= e($r['role_name']) ?>
        </option>
      <?php endforeach; ?>
    </select>
    <select class="select" name="status">
      <option value="all">Semua Status</option>
      <option value="active" <?= $status === 'active' ? 'selected' : '' ?>>Aktif</option>
      <option value="inactive" <?= $status === 'inactive' ? 'selected' : '' ?>>Nonaktif</option>
    </select>
    <button class="btn outline" type="submit">Filter</button>
  </form>
</div>

<details class="card form-panel">
  <summary><span><?= icon_svg('plus') ?> Tambah Pengguna</span><span class="badge badge-dark">Form</span></summary>
  <form class="form-body" method="post">
    <input type="hidden" name="action" value="create">
    <div class="form-grid">
      <div class="form-field"><label>Nama</label><input class="input" name="name" required></div>
      <div class="form-field"><label>Email</label><input class="input" type="email" name="email" required></div>
      <div class="form-field"><label>Password</label><input class="input" name="password" value="password"></div>
      <div class="form-field"><label>Role</label><select class="select" name="role_id"><?php foreach ($roles as $r): ?>
            <option value="<?= e($r['id']) ?>"><?= e($r['role_name']) ?></option><?php endforeach; ?>
        </select></div>
      <div class="form-field"><label>Status</label><select class="select" name="status">
          <option value="active">Aktif</option>
          <option value="inactive">Nonaktif</option>
        </select></div>
    </div>
    <div class="form-actions"><button class="btn" type="submit">Simpan Pengguna</button></div>
  </form>
</details>

<?php if ($editUser): ?>
  <details class="card form-panel" open>
    <summary><span><?= icon_svg('edit') ?> Edit Pengguna</span><span
        class="badge badge-blue"><?= e($editUser['email']) ?></span></summary>
    <form class="form-body" method="post">
      <input type="hidden" name="action" value="update">
      <input type="hidden" name="id" value="<?= e($editUser['id']) ?>">
      <div class="form-grid">
        <div class="form-field"><label>Nama</label><input class="input" name="name" value="<?= e($editUser['name']) ?>"
            required></div>
        <div class="form-field"><label>Email</label><input class="input" type="email" name="email"
            value="<?= e($editUser['email']) ?>" required></div>
        <div class="form-field"><label>Password Baru</label><input class="input" name="password"
            placeholder="Kosongkan jika tidak diganti"></div>
        <div class="form-field"><label>Role</label><select class="select" name="role_id"><?php foreach ($roles as $r): ?>
              <option value="<?= e($r['id']) ?>" <?= (int) $editUser['role_id'] === (int) $r['id'] ? 'selected' : '' ?>>
                <?= e($r['role_name']) ?></option><?php endforeach; ?>
          </select></div>
        <div class="form-field"><label>Status</label><select class="select" name="status">
            <option value="active" <?= $editUser['status'] === 'active' ? 'selected' : '' ?>>Aktif</option>
            <option value="inactive" <?= $editUser['status'] === 'inactive' ? 'selected' : '' ?>>Nonaktif</option>
          </select></div>
      </div>
      <div class="form-actions">
        <a class="btn outline" href="<?= e(url('sysadmin/users.php')) ?>">Batal</a>
        <button class="btn" type="submit">Simpan Perubahan</button>
      </div>
    </form>
  </details>
<?php endif; ?>

<div class="card mt-18">
  <div class="table-wrap">
    <table class="table">
      <thead>
        <tr>
          <th>Pengguna</th>
          <th>Role</th>
          <th>Status</th>
          <th>Terdaftar</th>
          <th style="text-align:right">Aksi</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($users as $u): ?>
          <tr>
            <td>
              <div class="entity">
                <div class="entity-icon"><?= icon_svg($u['role_key'] === 'perusahaan' ? 'building' : 'user') ?></div>
                <div>
                  <div class="entity-title"><?= e($u['name']) ?></div>
                  <div class="entity-sub"><?= e($u['email']) ?></div>
                </div>
              </div>
            </td>
            <td><span class="badge badge-dark"><?= e($u['role_name']) ?></span></td>
            <td><span class="badge <?= status_badge_class($u['status']) ?>"><?= e(status_label($u['status'])) ?></span>
            </td>
            <td><?= e(format_date_id($u['created_at'])) ?></td>
            <td>
              <div class="actions"><a class="btn outline sm"
                  href="<?= e(url('sysadmin/users.php?edit=' . $u['id'])) ?>"><?= icon_svg('edit') ?></a>
                <form method="post"><input type="hidden" name="action" value="delete"><input type="hidden" name="id"
                    value="<?= e($u['id']) ?>"><button class="btn danger sm"
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