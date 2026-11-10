<?php
/**
 * Halaman Data Kendaraan (CRUD)
 * Smart Weighing Bridge
 */

declare(strict_types=1);

session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

require_once __DIR__ . '/../config/database.php';

use Config\Database;

$db = new Database();
$pdo = $db->getConnection();

$message = '';
$messageType = '';

// Generate CSRF token hanya pada permintaan awal (GET), jangan timpa saat POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $csrfToken = bin2hex(random_bytes(32));
    $_SESSION['csrf_token'] = $csrfToken;
} else {
    $csrfToken = $_SESSION['csrf_token'] ?? '';
}

// Ambil semua kendaraan
$vehicles = [];
try {
    $stmt = $pdo->query("
        SELECT id, vehicle_id, uid, plate, vehicle_type, owner_name, active, created_at
        FROM vehicles
        ORDER BY created_at DESC
    ");
    $vehicles = $stmt->fetchAll();
} catch (Throwable $e) {
    error_log("Vehicle list error: " . $e->getMessage());
    $message = 'Gagal memuat data kendaraan.';
    $messageType = 'danger';
}

// Proses form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postToken = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $postToken)) {
        $message = 'Token keamanan tidak valid.';
        $messageType = 'danger';
    } else {
        $action = $_POST['action'] ?? '';

        try {
            if ($action === 'create') {
                $vehicleId   = trim($_POST['vehicle_id'] ?? '');
                $uid         = strtoupper(trim($_POST['uid'] ?? ''));
                $plate       = strtoupper(trim($_POST['plate'] ?? ''));
                $vehicleType = trim($_POST['vehicle_type'] ?? '');
                $ownerName   = trim($_POST['owner_name'] ?? '');
                $active      = isset($_POST['active']) ? 1 : 0;

                if ($vehicleId === '' || $uid === '' || $plate === '' || $vehicleType === '' || $ownerName === '') {
                    $message = 'Semua field harus diisi.';
                    $messageType = 'warning';
                } else {
                    $chk = $pdo->prepare("SELECT id FROM vehicles WHERE uid = :uid LIMIT 1");
                    $chk->execute([':uid' => $uid]);
                    if ($chk->fetch()) {
                        $message = 'UID RFID sudah terdaftar pada kendaraan lain.';
                        $messageType = 'warning';
                    } else {
                        $stmt = $pdo->prepare("
                            INSERT INTO vehicles (vehicle_id, uid, plate, vehicle_type, owner_name, active)
                            VALUES (:vehicle_id, :uid, :plate, :vehicle_type, :owner_name, :active)
                        ");
                        $stmt->execute([
                            ':vehicle_id'   => $vehicleId,
                            ':uid'          => $uid,
                            ':plate'        => $plate,
                            ':vehicle_type' => $vehicleType,
                            ':owner_name'   => $ownerName,
                            ':active'       => $active,
                        ]);
                        $message = 'Kendaraan berhasil ditambahkan.';
                        $messageType = 'success';
                    }
                }
            } elseif ($action === 'update') {
                $id          = (int)($_POST['id'] ?? 0);
                $vehicleId   = trim($_POST['vehicle_id'] ?? '');
                $uid         = strtoupper(trim($_POST['uid'] ?? ''));
                $plate       = strtoupper(trim($_POST['plate'] ?? ''));
                $vehicleType = trim($_POST['vehicle_type'] ?? '');
                $ownerName   = trim($_POST['owner_name'] ?? '');
                $active      = isset($_POST['active']) ? 1 : 0;

                if ($id <= 0 || $vehicleId === '' || $uid === '' || $plate === '' || $vehicleType === '' || $ownerName === '') {
                    $message = 'Semua field harus diisi.';
                    $messageType = 'warning';
                } else {
                    $chk = $pdo->prepare("SELECT id FROM vehicles WHERE uid = :uid AND id != :id LIMIT 1");
                    $chk->execute([':uid' => $uid, ':id' => $id]);
                    if ($chk->fetch()) {
                        $message = 'UID RFID sudah terdaftar pada kendaraan lain.';
                        $messageType = 'warning';
                    } else {
                        $stmt = $pdo->prepare("
                            UPDATE vehicles
                            SET vehicle_id = :vehicle_id, uid = :uid, plate = :plate,
                                vehicle_type = :vehicle_type, owner_name = :owner_name, active = :active
                            WHERE id = :id
                        ");
                        $stmt->execute([
                            ':vehicle_id'   => $vehicleId,
                            ':uid'          => $uid,
                            ':plate'        => $plate,
                            ':vehicle_type' => $vehicleType,
                            ':owner_name'   => $ownerName,
                            ':active'       => $active,
                            ':id'           => $id,
                        ]);
                        $message = 'Data kendaraan berhasil diperbarui.';
                        $messageType = 'success';
                    }
                }
            } elseif ($action === 'delete') {
                $id = (int)($_POST['id'] ?? 0);
                if ($id <= 0) {
                    $message = 'ID kendaraan tidak valid.';
                    $messageType = 'danger';
                } else {
                    $stmt = $pdo->prepare("DELETE FROM vehicles WHERE id = :id");
                    $stmt->execute([':id' => $id]);
                    $message = 'Kendaraan berhasil dihapus.';
                    $messageType = 'success';
                }
            } elseif ($action === 'toggle') {
                $id = (int)($_POST['id'] ?? 0);
                $active = (int)($_POST['active'] ?? 0);
                $newActive = $active === 1 ? 0 : 1;
                $stmt = $pdo->prepare("UPDATE vehicles SET active = :active WHERE id = :id");
                $stmt->execute([':active' => $newActive, ':id' => $id]);
                $message = 'Status kendaraan berhasil diubah.';
                $messageType = 'success';
            }
        } catch (Throwable $e) {
            error_log("Vehicle CRUD error: " . $e->getMessage());
            $message = 'Terjadi kesalahan pada server.';
            $messageType = 'danger';
        }

        try {
            $stmt = $pdo->query("SELECT id, vehicle_id, uid, plate, vehicle_type, owner_name, active, created_at FROM vehicles ORDER BY created_at DESC");
            $vehicles = $stmt->fetchAll();
        } catch (Throwable $e) {
            $vehicles = [];
        }
    }
}

// Ambil data kendaraan untuk edit (jika ada parameter edit)
$editVehicle = null;
if (isset($_GET['edit'])) {
    $editId = (int)$_GET['edit'];
    try {
        $stmt = $pdo->prepare("SELECT * FROM vehicles WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $editId]);
        $editVehicle = $stmt->fetch();
    } catch (Throwable $e) {
        error_log("Vehicle edit fetch error: " . $e->getMessage());
    }
}

$pageTitle = 'Data Kendaraan';
$activePage = 'vehicles';

include __DIR__ . '/../layouts/header.php';
?>

            <?php if ($message): ?>
            <div style="padding:0 32px;margin-bottom:16px;">
                <div class="alert" style="<?php echo $messageType === 'success' ? 'background:#f0fdf4;border:1px solid #bbf7d0;color:#166534;' : ($messageType === 'warning' ? 'background:#fffbeb;border:1px solid #fcd34d;color:#92400e;' : 'background:#fef2f2;border:1px solid #fecaca;color:#991b1b;'); ?>">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Form Tambah / Edit -->
            <div style="padding:0 32px 24px;">
                <div class="card" style="display:none;" id="formContainer">
                    <h3 style="font-size:16px;font-weight:700;margin-bottom:16px;color:#0f172a;">
                        <i class="fas fa-<?php echo $editVehicle ? 'edit' : 'plus'; ?> me-2"></i><?php echo $editVehicle ? 'Edit Data Kendaraan' : 'Tambah Kendaraan Baru'; ?>
                    </h3>
                    <form method="POST" id="vehicleForm">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                        <input type="hidden" name="action" value="<?php echo $editVehicle ? 'update' : 'create'; ?>">
                        <?php if ($editVehicle): ?><input type="hidden" name="id" value="<?php echo (int)$editVehicle['id']; ?>"><?php endif; ?>

                        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:16px;">
                            <div class="form-group">
                                <label>ID Kendaraan <span style="color:#ef4444;">*</span></label>
                                <input type="text" name="vehicle_id" required maxlength="20"
                                    value="<?php echo $editVehicle ? htmlspecialchars($editVehicle['vehicle_id']) : ''; ?>"
                                    placeholder="Contoh: V001">
                            </div>
                            <div class="form-group">
                                <label>UID RFID <span style="color:#ef4444;">*</span></label>
                                <input type="text" name="uid" required maxlength="50"
                                    value="<?php echo $editVehicle ? htmlspecialchars($editVehicle['uid']) : ''; ?>"
                                    placeholder="Contoh: A1B2C3D4">
                                <small style="color:#64748b;">UID harus unik dan tidak boleh sama dengan kendaraan lain.</small>
                            </div>
                            <div class="form-group">
                                <label>Nomor Polisi <span style="color:#ef4444;">*</span></label>
                                <input type="text" name="plate" required maxlength="20"
                                    value="<?php echo $editVehicle ? htmlspecialchars($editVehicle['plate']) : ''; ?>"
                                    placeholder="Contoh: AB 1234 CD">
                            </div>
                            <div class="form-group">
                                <label>Jenis Kendaraan <span style="color:#ef4444;">*</span></label>
                                <select name="vehicle_type" required>
                                    <option value="">Pilih jenis</option>
                                    <option value="Truck" <?php echo ($editVehicle && $editVehicle['vehicle_type']==='Truck')?'selected':''; ?>>Truck</option>
                                    <option value="Bus" <?php echo ($editVehicle && $editVehicle['vehicle_type']==='Bus')?'selected':''; ?>>Bus</option>
                                    <option value="Trailer" <?php echo ($editVehicle && $editVehicle['vehicle_type']==='Trailer')?'selected':''; ?>>Trailer</option>
                                    <option value="Pickup" <?php echo ($editVehicle && $editVehicle['vehicle_type']==='Pickup')?'selected':''; ?>>Pickup</option>
                                    <option value="Lainnya" <?php echo ($editVehicle && $editVehicle['vehicle_type']==='Lainnya')?'selected':''; ?>>Lainnya</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Nama Pemilik <span style="color:#ef4444;">*</span></label>
                                <input type="text" name="owner_name" required maxlength="100"
                                    value="<?php echo $editVehicle ? htmlspecialchars($editVehicle['owner_name']) : ''; ?>">
                            </div>
                            <div class="form-group" style="display:flex;align-items:center;gap:10px;">
                                <label style="margin:0;">Aktif</label>
                                <input type="checkbox" name="active" value="1" <?php echo ($editVehicle ? ($editVehicle['active'] ? 'checked' : '') : 'checked'); ?>>
                            </div>
                        </div>
                        <div class="form-group" style="margin-top:20px;display:flex;gap:10px;">
                            <button type="submit" class="btn-save">
                                <i class="fas fa-save me-2"></i><?php echo $editVehicle ? 'Perbarui' : 'Simpan'; ?>
                            </button>
                            <button type="button" class="btn-cancel" onclick="closeFormModal()">Batal</button>
                            <a href="vehicle-data.php" class="btn-reset">Atur Ulang Form</a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Tabel Kendaraan -->
            <div style="padding:0 32px 40px;">
                <div class="table-controls" style="margin-bottom:16px;">
                    <input type="text" id="searchVehicle" placeholder="Cari nomor polisi, UID, atau pemilik..." style="min-width:280px;">
                </div>
                <div class="table-wrapper">
                    <table class="data-table" id="tableVehicles">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>ID Kendaraan</th>
                                <th>UID RFID</th>
                                <th>Nomor Polisi</th>
                                <th>Jenis Kendaraan</th>
                                <th>Nama Pemilik</th>
                                <th>Status</th>
                                <th>Dibuat</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="tbodyVehicles">
                            <?php $no = 1; foreach ($vehicles as $v): ?>
                            <tr>
                                <td><?php echo $no++; ?></td>
                                <td><?php echo htmlspecialchars($v['vehicle_id']); ?></td>
                                <td><code style="font-size:12px;"><?php echo htmlspecialchars($v['uid']); ?></code></td>
                                <td><?php echo htmlspecialchars($v['plate']); ?></td>
                                <td><?php echo htmlspecialchars($v['vehicle_type']); ?></td>
                                <td><?php echo htmlspecialchars($v['owner_name']); ?></td>
                                <td>
                                    <span class="badge <?php echo $v['active'] ? 'badge-success' : 'badge-secondary'; ?>" style="font-size:11px;padding:4px 10px;">
                                        <?php echo $v['active'] ? 'Aktif' : 'Nonaktif'; ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($v['created_at']); ?></td>
                                <td>
                                    <button class="btn-icon btn-edit" onclick="editVehicle(<?php echo htmlspecialchars(json_encode($v)); ?>)" title="Edit">
                                        <i class="fas fa-pen"></i>
                                    </button>
                                    <button class="btn-icon btn-toggle" onclick="toggleVehicle(<?php echo (int)$v['id']; ?>, <?php echo (int)$v['active']; ?>)" title="<?php echo $v['active'] ? 'Nonaktifkan' : 'Aktifkan'; ?>">
                                        <i class="fas fa-<?php echo $v['active'] ? 'ban' : 'check'; ?>"></i>
                                    </button>
                                    <button class="btn-icon btn-delete" onclick="deleteVehicle(<?php echo (int)$v['id']; ?>, '<?php echo htmlspecialchars($v['plate']); ?>')" title="Hapus">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($vehicles)): ?>
                            <tr><td colspan="9" class="text-center">Belum ada data kendaraan.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

<style>
.card{background:#fff;border:1px solid #e2e8f0;border-radius:14px;padding:24px;}
.form-group{margin-bottom:16px;}
.form-group label{display:block;margin-bottom:6px;font-weight:600;font-size:13px;color:#334155;}
.form-group input,.form-group select{width:100%;padding:10px 14px;border:1px solid #cbd5e1;border-radius:8px;font-size:13px;color:#0f172a;background:#fff;}
.form-group small{display:block;margin-top:4px;font-size:12px;color:#64748b;}
.btn-save{padding:10px 20px;background:#3b82f6;color:#fff;border-radius:8px;font-weight:600;font-size:13px;display:inline-flex;align-items:center;gap:6px;transition:background .2s;}
.btn-save:hover{background:#2563eb;}
.btn-cancel{padding:10px 20px;background:#f1f5f9;color:#475569;border-radius:8px;font-weight:600;font-size:13px;display:inline-flex;align-items:center;gap:6px;transition:background .2s;border:1px solid #e2e8f0;}
.btn-cancel:hover{background:#e2e8f0;}
.btn-reset{padding:10px 20px;background:#f1f5f9;color:#475569;border-radius:8px;font-weight:600;font-size:13px;border:1px solid #e2e8f0;transition:background .2s;}
.btn-reset:hover{background:#e2e8f0;}
.btn-icon{width:32px;height:32px;display:inline-flex;align-items:center;justify-content:center;border-radius:6px;color:#64748b;background:#f1f5f9;font-size:14px;margin:0 2px;transition:all .2s;}
.btn-icon.btn-edit:hover{background:#dbeafe;color:#2563eb;}
.btn-icon.btn-toggle:hover{background:#dcfce7;color:#166534;}
.btn-icon.btn-delete:hover{background:#fee2e2;color:#991b1b;}
.me-2{margin-right:6px;}
</style>

<script>
    function openFormModal(isEdit = false) {
        const container = document.getElementById('formContainer');
        const btn = document.querySelector('button[onclick^="openFormModal"]');
        if (container.style.display === 'none' || container.style.display === '') {
            container.style.display = 'block';
            if (btn) btn.innerHTML = '<i class="fas fa-times me-2"></i>Tutup Form';
            window.scrollTo({top: container.offsetTop - 100, behavior: 'smooth'});
        } else {
            closeFormModal();
        }
    }
    function closeFormModal() {
        document.getElementById('formContainer').style.display = 'none';
        const btn = document.querySelector('button[onclick^="openFormModal"]');
        if (btn) btn.innerHTML = '<i class="fas fa-plus me-2"></i>Tambah Kendaraan';
    }

    function editVehicle(v) {
        const form = document.getElementById('vehicleForm');
        form.querySelector('input[name="action"]').value = 'update';
        form.querySelector('input[name="id"]').value = v.id;
        form.querySelector('input[name="vehicle_id"]').value = v.vehicle_id;
        form.querySelector('input[name="uid"]').value = v.uid;
        form.querySelector('input[name="plate"]').value = v.plate;
        form.querySelector('select[name="vehicle_type"]').value = v.vehicle_type;
        form.querySelector('input[name="owner_name"]').value = v.owner_name;
        form.querySelector('input[name="active"]').checked = v.active == 1;

        const container = document.getElementById('formContainer');
        container.querySelector('h3').innerHTML = '<i class="fas fa-edit me-2"></i>Edit Data Kendaraan';
        container.style.display = 'block';
        const btn = document.querySelector('button[onclick^="openFormModal"]');
        if (btn) btn.innerHTML = '<i class="fas fa-times me-2"></i>Tutup Form';
        window.scrollTo({top: container.offsetTop - 100, behavior: 'smooth'});
    }

    function toggleVehicle(id, active) {
        if (!confirm('Apakah Anda yakin ingin mengubah status kendaraan ini?')) return;
        const formData = new FormData();
        formData.append('csrf_token', '<?php echo $csrfToken; ?>');
        formData.append('action', 'toggle');
        formData.append('id', id);
        formData.append('active', active);
        fetch('vehicle-data.php', { method: 'POST', body: formData })
            .then(r => r.text())
            .then(() => location.reload())
            .catch(() => alert('Gagal mengubah status.'));
    }

    function deleteVehicle(id, plate) {
        if (!confirm(`Yakin ingin menghapus kendaraan "${plate}"? Data penimbangan terkait akan di-set NULL.`)) return;
        const formData = new FormData();
        formData.append('csrf_token', '<?php echo $csrfToken; ?>');
        formData.append('action', 'delete');
        formData.append('id', id);
        fetch('vehicle-data.php', { method: 'POST', body: formData })
            .then(r => r.text())
            .then(() => location.reload())
            .catch(() => alert('Gagal menghapus.'));
    }

    document.getElementById('searchVehicle').addEventListener('input', function() {
        const q = this.value.toLowerCase();
        document.querySelectorAll('#tbodyVehicles tr').forEach(tr => {
            const text = tr.textContent.toLowerCase();
            tr.style.display = text.includes(q) ? '' : 'none';
        });
    });
</script>

</div> <!-- .main-body -->
        </main>

<?php include __DIR__ . '/../layouts/footer.php';