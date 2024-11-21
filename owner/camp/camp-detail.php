<?php
require_once '../../includes/db.php';
session_start();

// 檢查是否登入
if (!isset($_SESSION['owner_id'])) {
    header("Location: ../index.php");
    exit;
}

$owner_id = $_SESSION['owner_id'];
$campsite_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// 獲取營地資料
try {
    $stmt = $db->prepare("
        SELECT c.*, 
               COUNT(cs.spot_id) as spot_count,
               COUNT(CASE WHEN cs.status = 1 THEN 1 END) as active_spot_count
        FROM campsites c
        LEFT JOIN camping_spots cs ON c.campsite_id = cs.campsite_id
        WHERE c.campsite_id = ? AND c.owner_id = ?
        GROUP BY c.campsite_id
    ");
    $stmt->execute([$campsite_id, $owner_id]);
    $camp = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$camp) {
        $_SESSION['error_message'] = "找不到該營地資料";
        header("Location: camp-list.php");
        exit;
    }

    // 獲取營地的營位列表
    $stmt = $db->prepare("
        SELECT * FROM camping_spots 
        WHERE campsite_id = ? 
        ORDER BY created_at DESC
    ");
    $stmt->execute([$campsite_id]);
    $spots = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch(PDOException $e) {
    die("查詢失敗：" . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($camp['name']) ?> - 營地詳情</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <style>
        .camp-info {
            background-color: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
        }
        .info-label {
            font-weight: 600;
            color: #495057;
        }
        .spot-card {
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
            transition: all 0.3s ease;
        }
        .spot-card:hover {
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <?php include '../includes/sidebar.php'; ?>

            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2"><?= htmlspecialchars($camp['name']) ?></h1>
                    <div class="btn-toolbar">
                        <a href="../spot/spot-add.php?campsite_id=<?= $camp['campsite_id'] ?>" class="btn btn-primary me-2">
                            <i class="bi bi-plus-lg"></i> 新增營位
                        </a>
                        <a href="camp-list.php" class="btn btn-secondary">
                            <i class="bi bi-arrow-left"></i> 返回列表
                        </a>
                    </div>
                </div>

                <!-- 營地基本資訊 -->
                <div class="camp-info">
                    <div class="row">
                        <div class="col-md-6">
                            <p><span class="info-label">營地地址：</span> <?= htmlspecialchars($camp['address']) ?></p>
                            <p><span class="info-label">營位數量：</span> <?= $camp['spot_count'] ?> (啟用中: <?= $camp['active_spot_count'] ?>)</p>
                            <p><span class="info-label">建立時間：</span> <?= date('Y-m-d H:i', strtotime($camp['created_at'])) ?></p>
                        </div>
                        <div class="col-md-6">
                            <p><span class="info-label">狀態：</span> 
                                <span class="badge bg-success">已通過審核</span>
                            </p>
                        </div>
                    </div>
                </div>

                <!-- 營地詳細資訊 -->
                <div class="row mb-4">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title mb-3">營地介紹</h5>
                                <div class="camp-description mb-4">
                                    <?= nl2br(htmlspecialchars($camp['description'])) ?>
                                </div>

                                <h5 class="card-title mb-3">營地規則</h5>
                                <div class="camp-rules mb-4">
                                    <?= nl2br(htmlspecialchars($camp['rules'])) ?>
                                </div>

                                <h5 class="card-title mb-3">注意事項</h5>
                                <div class="camp-notice">
                                    <?= nl2br(htmlspecialchars($camp['notice'])) ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 營位列表 -->
                <h3 class="h4 mb-3">營位列表</h3>
                <div class="row">
                    <?php if (empty($spots)): ?>
                        <div class="col-12">
                            <div class="alert alert-info">
                                目前還沒有營位，點擊上方「新增營位」按鈕來新增。
                            </div>
                        </div>
                    <?php else: ?>
                        <?php foreach ($spots as $spot): ?>
                            <div class="col-md-6 col-lg-4">
                                <div class="spot-card">
                                    <h5><?= htmlspecialchars($spot['name']) ?></h5>
                                    <p class="mb-2">
                                        <span class="badge bg-<?= $spot['status'] ? 'success' : 'secondary' ?>">
                                            <?= $spot['status'] ? '啟用中' : '已停用' ?>
                                        </span>
                                    </p>
                                    <p class="mb-2">類���：<?= htmlspecialchars($spot['type']) ?></p>
                                    <p class="mb-2">價格：NT$ <?= number_format($spot['price']) ?></p>
                                    <p class="mb-2">容納人數：<?= $spot['capacity'] ?> 人</p>
                                    <div class="mt-2">
                                        <a href="../spot/spot-edit.php?id=<?= $spot['spot_id'] ?>" 
                                           class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-pencil"></i> 編輯
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
