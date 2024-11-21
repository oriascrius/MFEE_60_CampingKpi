<?php
session_start();
require_once __DIR__ . '/../camping_db.php';

// 檢查是否登入
if (!isset($_SESSION['owner_id'])) {
    header("Location: auth.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>營主後台 - 露營趣</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- 引入側邊欄 -->
            <?php include __DIR__ . '/includes/sidebar.php'; ?>

            <!-- 主要內容區 -->
            <div class="col-md-9 col-lg-10 ms-auto content">
                <h2 class="mb-4">歡迎回來，<?= htmlspecialchars($_SESSION['owner_name']) ?></h2>
                
                <!-- 統計卡片 -->
                <div class="row">
                    <!-- 營地申請狀態 -->
                    <div class="col-md-6 mb-4">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title"><i class="bi bi-file-earmark-text me-2"></i>營地申請狀態</h5>
                                <div class="mt-3">
                                    <p>目前狀態：<span class="badge bg-warning">審核中</span></p>
                                    <p>提交時間：2024-03-20 14:30</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 營地資訊 -->
                    <div class="col-md-6 mb-4">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title"><i class="bi bi-geo-alt me-2"></i>營地資訊</h5>
                                <div class="mt-3">
                                    <p>營位數量：<strong>5</strong></p>
                                    <p>上架狀態：<span class="badge bg-success">營業中</span></p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 訂單概況 -->
                    <div class="col-md-12 mb-4">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title"><i class="bi bi-receipt me-2"></i>訂單概況</h5>
                                <div class="row mt-3">
                                    <div class="col-md-4">
                                        <p>今日訂單：<strong>3</strong></p>
                                    </div>
                                    <div class="col-md-4">
                                        <p>本月訂單：<strong>25</strong></p>
                                    </div>
                                    <div class="col-md-4">
                                        <p>待處理訂單：<strong>2</strong></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include __DIR__ . '/../admin/components/scripts.php'; ?>
</body>
</html>