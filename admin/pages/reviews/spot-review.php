<?php
require_once __DIR__ . '/../../../camping_db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>

<div class="container-fluid">
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">營位審核管理</h6>
        </div>
        
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>申請編號</th>
                            <th>營地名稱</th>
                            <th>營主名稱</th>
                            <th>營位名稱</th>
                            <th>容納人數</th>
                            <th>價格</th>
                            <th>申請時間</th>
                            <th>狀態</th>
                            <th>操作</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php
                    try {
                        global $db;
                        $sql = "SELECT 
                                    csa.spot_id,
                                    csa.application_id,
                                    csa.name AS spot_name,
                                    csa.capacity,
                                    csa.price,
                                    csa.description,
                                    csa.status,
                                    csa.created_at,
                                    ca.name AS camp_name,
                                    ca.owner_name,
                                    ca.description AS camp_description
                                FROM camp_spot_applications csa
                                JOIN camp_applications ca 
                                    ON csa.application_id = ca.application_id
                                ORDER BY csa.created_at DESC";

                        $stmt = $db->prepare($sql);
                        $stmt->execute();
                        $spots = $stmt->fetchAll(PDO::FETCH_ASSOC);

                        if (empty($spots)) {
                            echo '<tr><td colspan="9" class="text-center">目前沒有待審核的營位申請</td></tr>';
                        } else {
                            foreach ($spots as $spot) {
                                $statusBadge = match($spot['status']) {
                                    0 => '<span class="badge bg-warning">待審核</span>',
                                    1 => '<span class="badge bg-success">已通過</span>',
                                    2 => '<span class="badge bg-danger">已退回</span>',
                                    default => '<span class="badge bg-secondary">未知</span>'
                                };
                                ?>
                                <tr>
                                    <td><?= htmlspecialchars($spot['application_id']) ?></td>
                                    <td><?= htmlspecialchars($spot['camp_name']) ?></td>
                                    <td><?= htmlspecialchars($spot['owner_name']) ?></td>
                                    <td><?= htmlspecialchars($spot['spot_name']) ?></td>
                                    <td><?= htmlspecialchars($spot['capacity']) ?></td>
                                    <td>NT$ <?= number_format($spot['price']) ?></td>
                                    <td><?= date('Y-m-d H:i', strtotime($spot['created_at'])) ?></td>
                                    <td><?= $statusBadge ?></td>
                                    <td>
                                        <button class="btn btn-sm btn-primary" 
                                                onclick="viewSpotDetails(<?= $spot['spot_id'] ?>)">
                                            編輯
                                        </button>
                                    </td>
                                </tr>
                                <?php
                            }
                        }
                    } catch (PDOException $e) {
                        echo '<tr><td colspan="9" class="text-danger">資料載入失敗：' . $e->getMessage() . '</td></tr>';
                    }
                    ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- 修改 Modal 結構 -->
<div class="modal fade" id="statusModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">編輯審核狀態</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="currentSpotId">
                <div class="mb-3">
                    <label class="form-label">審核狀態</label>
                    <div class="d-flex gap-2">
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="status" id="status0" value="0">
                            <label class="form-check-label" for="status0">待審核</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="status" id="status1" value="1">
                            <label class="form-check-label" for="status1">通過</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="status" id="status2" value="2">
                            <label class="form-check-label" for="status2">不通過</label>
                        </div>
                    </div>
                </div>
                <div class="mb-3">
                    <label for="rejectReason" class="form-label">審核意見</label>
                    <textarea class="form-control" id="rejectReason" rows="3"></textarea>
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
let currentSpotId;

document.addEventListener('DOMContentLoaded', function() {
    statusModal = new bootstrap.Modal(document.getElementById('statusModal'));
});

function viewSpotDetails(spotId) {
    currentSpotId = spotId;
    // 獲取當前狀態並設置
    document.querySelector('input[name="status"][value="0"]').checked = true;
    // 清空審核意見
    document.getElementById('rejectReason').value = '';
    statusModal.show();
}

async function updateStatus() {
    const status = document.querySelector('input[name="status"]:checked').value;
    const rejectReason = document.getElementById('rejectReason').value;
    
    // 如果是退回狀態但沒有填寫原因
    if (status === '2' && !rejectReason.trim()) {
        await Swal.fire({
            title: '請填寫審核意見',
            text: '退回申請時需要填寫審核意見',
            icon: 'warning'
        });
        return;
    }
    
    try {
        const response = await fetch('/CampExplorer/admin/api/reviews/update_spot_status.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                spot_id: currentSpotId,
                status: parseInt(status),
                reject_reason: rejectReason
            })
        });

        const data = await response.json();

        if (data.success) {
            statusModal.hide();
            await Swal.fire({
                title: '更新成功',
                text: '營位申請狀態已更新',
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
    font-size: 0.875rem;
    padding: 0.5em 0.75em;
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

#rejectReason {
    resize: vertical;
    min-height: 100px;
}

@media (max-width: 768px) {
    .table-responsive {
        margin: 0 -1rem;
    }
    
    .card-body {
        padding: 1rem 0;
    }
}
</style>