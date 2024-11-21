<?php
require_once '../../camping_db.php';
session_start();

// 檢查是否登入
if (!isset($_SESSION['owner_id'])) {
    header("Location: ../index.php");
    exit;
}

$owner_id = $_SESSION['owner_id'];
$camps = [];

// 獲取營地列表
try {
    // 查詢所有營地狀態
    $stmt = $db->prepare("
        SELECT 
            ca.application_id,
            ca.name,
            ca.status,
            ca.created_at,
            ca.updated_at,
            ca.description,
            ca.address,
            ca.rules,
            ca.notice,
            cr.comment as admin_comment,
            CASE ca.status
                WHEN 0 THEN '審核中'
                WHEN 1 THEN '已通過'
                WHEN 2 THEN '已退回'
                ELSE '未知'
            END as status_text
        FROM camp_applications ca
        LEFT JOIN campsite_reviews cr ON ca.application_id = cr.review_id
        WHERE ca.owner_id = ?
        ORDER BY ca.created_at DESC
    ");
    
    $stmt->execute([$owner_id]);
    $camps = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $error = "查詢敗：" . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>營地狀態管理 - 營主後台</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <style>
        .status-badge {
            font-size: 0.875rem;
            padding: 0.25rem 0.5rem;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <?php include '../includes/sidebar.php'; ?>

            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">營地狀態管理</h1>
                </div>

                <?php if (isset($error)): ?>
                    <div class="alert alert-danger"><?= $error ?></div>
                <?php endif; ?>

                <div class="card">
                    <div class="card-header">
                        <h3 class="h5 mb-0">營地狀態列表</h3>
                    </div>
                    <div class="card-body">
                        <?php if (empty($camps)): ?>
                            <div class="text-center text-muted py-3">
                                <p>目前還沒有營地資料</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>營地名稱</th>
                                            <th>審核狀態</th>
                                            <th>申請時間</th>
                                            <th>最後更新</th>
                                            <th>操作</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($camps as $camp): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($camp['name']) ?></td>
                                                <td>
                                                    <?php
                                                    $statusClass = match($camp['status']) {
                                                        0 => 'bg-warning',
                                                        1 => 'bg-success',
                                                        2 => 'bg-danger',
                                                        default => 'bg-secondary'
                                                    };
                                                    ?>
                                                    <span class="badge <?= $statusClass ?>"><?= $camp['status_text'] ?></span>
                                                </td>
                                                <td><?= date('Y/m/d H:i', strtotime($camp['created_at'])) ?></td>
                                                <td><?= $camp['updated_at'] ? date('Y/m/d H:i', strtotime($camp['updated_at'])) : '-' ?></td>
                                                <td>
                                                    <button type="button" 
                                                            class="btn btn-sm btn-outline-primary"
                                                            data-bs-toggle="modal"
                                                            data-bs-target="#campDetailModal"
                                                            data-camp-name="<?= htmlspecialchars($camp['name']) ?>"
                                                            data-camp-address="<?= htmlspecialchars($camp['address']) ?>"
                                                            data-camp-status="<?= $camp['status_text'] ?>"
                                                            data-camp-created="<?= date('Y/m/d H:i', strtotime($camp['created_at'])) ?>"
                                                            data-camp-updated="<?= $camp['updated_at'] ? date('Y/m/d H:i', strtotime($camp['updated_at'])) : '-' ?>"
                                                            data-camp-comment="<?= htmlspecialchars($camp['admin_comment'] ?? '') ?>"
                                                            data-camp-description="<?= htmlspecialchars($camp['description']) ?>"
                                                            data-camp-rules="<?= htmlspecialchars($camp['rules']) ?>"
                                                            data-camp-notice="<?= htmlspecialchars($camp['notice']) ?>">
                                                        <i class="bi bi-eye"></i> 查看詳情
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.1/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Modal -->
    <div class="modal fade" id="campDetailModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">營地詳細資訊</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="camp-info">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <p><span class="info-label">營地名稱：</span> <span id="modalCampName"></span></p>
                                <p><span class="info-label">營地地址：</span> <span id="modalCampAddress"></span></p>
                                <p><span class="info-label">申請時間：</span> <span id="modalCampCreated"></span></p>
                            </div>
                            <div class="col-md-6">
                                <p><span class="info-label">審核狀態：</span> <span id="modalCampStatus"></span></p>
                                <p><span class="info-label">最後更新：</span> <span id="modalCampUpdated"></span></p>
                                <p><span class="info-label">審核意見：</span> <span id="modalCampComment"></span></p>
                            </div>
                        </div>
                        <div class="mb-3">
                            <h6 class="info-label">營地介紹</h6>
                            <div id="modalCampDescription" class="p-3 bg-light rounded"></div>
                        </div>
                        <div class="mb-3">
                            <h6 class="info-label">營地規則</h6>
                            <div id="modalCampRules" class="p-3 bg-light rounded"></div>
                        </div>
                        <div class="mb-3">
                            <h6 class="info-label">注意事項</h6>
                            <div id="modalCampNotice" class="p-3 bg-light rounded"></div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">關閉</button>
                </div>
            </div>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const modal = document.getElementById('campDetailModal');
        modal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            
            // 更新 Modal 內容
            document.getElementById('modalCampName').textContent = button.getAttribute('data-camp-name');
            document.getElementById('modalCampAddress').textContent = button.getAttribute('data-camp-address');
            document.getElementById('modalCampStatus').textContent = button.getAttribute('data-camp-status');
            document.getElementById('modalCampCreated').textContent = button.getAttribute('data-camp-created');
            document.getElementById('modalCampUpdated').textContent = button.getAttribute('data-camp-updated');
            document.getElementById('modalCampComment').textContent = button.getAttribute('data-camp-comment') || '無審核意見';
            document.getElementById('modalCampDescription').innerHTML = button.getAttribute('data-camp-description') || '無介紹';
            document.getElementById('modalCampRules').innerHTML = button.getAttribute('data-camp-rules') || '無規則';
            document.getElementById('modalCampNotice').innerHTML = button.getAttribute('data-camp-notice') || '無注意事項';
        });
    });
    </script>
</body>
</html>