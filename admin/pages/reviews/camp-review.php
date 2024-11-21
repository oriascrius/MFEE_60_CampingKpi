<?php
require_once __DIR__ . '/../../../camping_db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

try {
    global $db;
    $sql = "
        SELECT 
            ca.application_id,
            ca.owner_name,
            ca.name AS camp_name,
            ca.address,
            ca.created_at,
            ca.status,
            o.company_name,
            o.phone,
            COUNT(DISTINCT csa.spot_id) as spot_count,
            COUNT(DISTINCT ci.image_id) as image_count
        FROM camp_applications ca
        LEFT JOIN owners o ON ca.owner_id = o.id
        LEFT JOIN camp_spot_applications csa ON ca.application_id = csa.application_id
        LEFT JOIN camp_images ci ON ca.application_id = ci.application_id
        GROUP BY ca.application_id, ca.owner_name, ca.name, ca.address, ca.created_at, ca.status, o.company_name, o.phone
        ORDER BY ca.created_at DESC
    ";

    $stmt = $db->query($sql);
    $applications = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Database Error: " . $e->getMessage());
    $_SESSION['error_message'] = '資料載入失敗，請稍後再試';
    $applications = [];
}
?>

<!-- 只保留內容部分，移除所有 JavaScript -->
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">待審核營地列表</h1>
</div>

<?php if (isset($_SESSION['error_message'])): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?= htmlspecialchars($_SESSION['error_message']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        <?php unset($_SESSION['error_message']); ?>
    </div>
<?php endif; ?>

<div class="card shadow-sm">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th scope="col" class="text-nowrap">申請編號</th>
                        <th scope="col" style="min-width: 200px;">營地資訊</th>
                        <th scope="col" style="min-width: 180px;">營主資訊</th>
                        <th scope="col" class="text-center">申請內容</th>
                        <th scope="col" class="text-nowrap">申請時間</th>
                        <th scope="col" class="text-center">狀態</th>
                        <th scope="col" class="text-center">操作</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($applications)): ?>
                        <tr>
                            <td colspan="7" class="text-center py-4 text-muted">
                                <i class="bi bi-inbox fs-4 d-block mb-2"></i>
                                目前沒有營地申請
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($applications as $app): ?>
                            <tr>
                                <td class="text-nowrap">
                                    <span class="badge bg-secondary">
                                        #<?= htmlspecialchars($app['application_id']) ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="fw-bold mb-1"><?= htmlspecialchars($app['camp_name']) ?></div>
                                    <small class="text-muted">
                                        <i class="bi bi-geo-alt"></i>
                                        <?= htmlspecialchars($app['address']) ?>
                                    </small>
                                </td>
                                <td>
                                    <div class="mb-1"><?= htmlspecialchars($app['owner_name']) ?></div>
                                    <small class="text-muted d-block">
                                        <i class="bi bi-building"></i>
                                        <?= htmlspecialchars($app['company_name']) ?>
                                    </small>
                                    <small class="text-muted d-block">
                                        <i class="bi bi-telephone"></i>
                                        <?= htmlspecialchars($app['phone']) ?>
                                    </small>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-primary me-2">
                                        <i class="bi bi-house-door"></i>
                                        <?= $app['spot_count'] ?> 個營位
                                    </span>
                                    <span class="badge bg-info">
                                        <i class="bi bi-images"></i>
                                        <?= $app['image_count'] ?> 張圖片
                                    </span>
                                </td>
                                <td class="text-nowrap">
                                    <small>
                                        <i class="bi bi-clock"></i>
                                        <?= date('Y/m/d H:i', strtotime($app['created_at'])) ?>
                                    </small>
                                </td>
                                <td class="text-center">
                                    <?php
                                    $statusClass = match($app['status']) {
                                        0 => 'bg-warning',
                                        1 => 'bg-success',
                                        2 => 'bg-danger',
                                        default => 'bg-secondary'
                                    };
                                    $statusText = match($app['status']) {
                                        0 => '待審核',
                                        1 => '已通過',
                                        2 => '未通過',
                                        default => '未知'
                                    };
                                    ?>
                                    <span class="badge <?= $statusClass ?>">
                                        <?= $statusText ?>
                                    </span>
                                </td>
                                <td class="text-center">
                                    <button type="button" 
                                            class="btn btn-primary btn-sm" 
                                            onclick="showStatusModal(<?= $app['application_id'] ?>, <?= $app['status'] ?>)">
                                        <i class="bi bi-pencil-square"></i> 編輯
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- 新增狀態編輯的 Modal -->
<div class="modal fade" id="statusModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">編輯審核狀態</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="currentApplicationId">
                <div class="mb-3">
                    <label class="form-label">審核狀態</label>
                    <div class="d-flex gap-2">
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="status" id="status0" value="0">
                            <label class="form-check-label" for="status0">
                                待審核
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="status" id="status1" value="1">
                            <label class="form-check-label" for="status1">
                                通過
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="status" id="status2" value="2">
                            <label class="form-check-label" for="status2">
                                不通過
                            </label>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
                <button type="button" class="btn btn-primary" onclick="updateStatus()">確認更新</button>
            </div>
        </div>
    </div>
</div>

<script>
let statusModal;
let currentApplicationId;

document.addEventListener('DOMContentLoaded', function() {
    statusModal = new bootstrap.Modal(document.getElementById('statusModal'));
});

function showStatusModal(applicationId, currentStatus) {
    currentApplicationId = applicationId;
    // 設定當前狀態
    document.querySelector(`input[name="status"][value="${currentStatus}"]`).checked = true;
    statusModal.show();
}

async function updateStatus() {
    const status = document.querySelector('input[name="status"]:checked').value;
    
    try {
        const response = await fetch('/CampExplorer/admin/api/reviews/update_status.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                application_id: currentApplicationId,
                status: parseInt(status)
            })
        });

        const data = await response.json();

        if (data.success) {
            statusModal.hide();
            await Swal.fire({
                title: '更新成功',
                text: '營地申請狀態已更新',
                icon: 'success',
                timer: 1500,
                showConfirmButton: false
            });
            location.reload();
        } else {
            throw new Error(data.message || '更新失敗');
        }

    } catch (error) {
        console.error('Error:', error);
        Swal.fire({
            title: '更新失敗',
            text: error.message || '請稍後再試',
            icon: 'error'
        });
    }
}
</script>

<style>
.table > :not(caption) > * > * {
    padding: 1rem 0.75rem;
}

.badge {
    font-weight: 500;
    letter-spacing: 0.5px;
}

.table-responsive {
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
}

.card {
    border: none;
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
}

.btn-sm {
    padding: 0.25rem 0.75rem;
}

.table-light th {
    font-weight: 600;
    color: #495057;
    background-color: #f8f9fa;
}

.text-muted {
    color: #6c757d !important;
}

.bi {
    margin-right: 0.25rem;
}

@media (max-width: 768px) {
    .table-responsive {
        margin: 0 -1rem;
    }
    
    .card-body {
        padding: 1rem 0;
    }
}

.btn-group .btn {
    padding: 0.25rem 0.5rem;
    font-size: 0.875rem;
}

.btn-group .btn:not(:last-child) {
    margin-right: 2px;
}

.btn-group .bi {
    font-size: 0.875rem;
}

.btn-outline-warning:hover {
    color: #000;
    background-color: #ffc107;
}

.btn-outline-danger:hover {
    color: #fff;
    background-color: #dc3545;
}

.btn-outline-success:hover {
    color: #fff;
    background-color: #198754;
}

.modal-body .form-check {
    margin-right: 1rem;
}

.badge {
    font-size: 0.875rem;
    padding: 0.5em 0.75em;
}
</style>