<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/camping_system/camping_db.php';
session_start();

// 檢查是否登入
if (!isset($_SESSION['owner_id'])) {
    header("Location: ../index.php");
    exit;
}

$owner_id = $_SESSION['owner_id'];

// 獲取營地列表
try {
    // 查詢自己提交的審核申請
    $stmt = $db->prepare("
        SELECT ca.*, cr.status as review_status, cr.comment as admin_comment
        FROM camp_applications ca 
        LEFT JOIN campsite_reviews cr ON ca.application_id = cr.campsite_id
        WHERE ca.owner_id = ?
        ORDER BY ca.created_at DESC
    ");
    $stmt->execute([$owner_id]);
    $pending_camps = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 查詢已通過的營地
    $stmt = $db->prepare("
        SELECT cr.*, ca.name, ca.address 
        FROM campsite_reviews cr
        JOIN camp_applications ca ON cr.campsite_id = ca.campsite_id
        WHERE cr.status = 1 AND ca.owner_id = ?
        ORDER BY cr.created_at DESC
    ");
    $stmt->execute([$owner_id]);
    $approved_camps = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("查詢失��：" . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>營地管理 - 營主後台</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <style>
        .status-badge {
            font-size: 0.875rem;
            padding: 0.25rem 0.5rem;
        }
        .camp-actions .btn {
            padding: 0.25rem 0.5rem;
            font-size: 0.875rem;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <?php include $_SERVER['DOCUMENT_ROOT'] . '/camping_system/owner/includes/sidebar.php'; ?>

            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">營地管理</h1>
                    <a href="camp-add.php" class="btn btn-primary">
                        <i class="bi bi-plus-lg"></i> 申請新營地
                    </a>
                </div>

                <?php if (isset($_SESSION['success_message'])): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?= $_SESSION['success_message'] ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <?php unset($_SESSION['success_message']); ?>
                <?php endif; ?>

                <!-- 審核申請狀態 -->
                <?php if (!empty($pending_camps)): ?>
                <div class="card mb-4">
                    <div class="card-header">
                        <h3 class="h5 mb-0">申請進度</h3>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>營地名稱</th>
                                        <th>申請時間</th>
                                        <th>狀態</th>
                                        <th>審核意見</th>
                                        <th>操作</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($pending_camps as $camp): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($camp['name']) ?></td>
                                            <td><?= date('Y-m-d H:i', strtotime($camp['created_at'])) ?></td>
                                            <td>
                                                <?php if ($camp['status'] == 0): ?>
                                                    <span class="badge bg-warning">審核中</span>
                                                <?php else: ?>
                                                    <span class="badge bg-danger">需修改</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if (isset($camp['admin_comment']) && $camp['admin_comment']): ?>
                                                    <small class="text-muted"><?= nl2br(htmlspecialchars($camp['admin_comment'])) ?></small>
                                                <?php else: ?>
                                                    <small class="text-muted">等待審核中...</small>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($camp['status'] == 2): ?>
                                                    <a href="camp-edit.php?id=<?= $camp['review_id'] ?>" 
                                                       class="btn btn-sm btn-outline-primary">
                                                        <i class="bi bi-pencil"></i> 修改申請
                                                    </a>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- 已通過的營地列表 -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="h5 mb-0">我的營地</h3>
                    </div>
                    <div class="card-body">
                        <?php if (empty($approved_camps)): ?>
                            <div class="text-center text-muted py-3">
                                <p>目前還沒有已通過的營地</p>
                                <p>點擊上方「申請新營地」按鈕來新增營地</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>營地名稱</th>
                                            <th>地址</th>
                                            <th>通過時間</th>
                                            <th>操作</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($approved_camps as $camp): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($camp['name']) ?></td>
                                                <td><?= htmlspecialchars($camp['address']) ?></td>
                                                <td><?= date('Y-m-d H:i', strtotime($camp['created_at'])) ?></td>
                                                <td>
                                                    <a href="camp-detail.php?id=<?= $camp['campsite_id'] ?>" 
                                                       class="btn btn-sm btn-outline-info">
                                                        <i class="bi bi-eye"></i> 查看詳情
                                                    </a>
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
</body>
</html>
