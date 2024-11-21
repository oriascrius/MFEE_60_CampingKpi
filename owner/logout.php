<?php
session_start();

// 檢查是否有營主登入狀態
$isOwnerLoggedIn = isset($_SESSION['owner_id']);

// 清除所有 session 資料
session_destroy();

// 設定正確的路徑
$ownerAuthPath = "owner-login.php";  // 更新營主登入頁面路徑
$portalPath = "../portal.php";      // 入口頁面
?>

<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>登出系統</title>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            background: linear-gradient(135deg, #1a2a6c, #b21f1f, #fdbb2d);
            font-family: 'Microsoft JhengHei', sans-serif;
        }

        /* 表單元素間距優化 */
        .form-floating {
            margin-bottom: 1.5rem;  /* 增加表單元素間的垂直間距 */
        }

        /* 按鈕樣式優化 */
        .btn-auth {
            width: 100%;
            padding: 1rem;         /* 增加按鈕高度 */
            border-radius: 10px;
            background: #2c3e50;
            border: none;
            font-weight: 500;
            font-size: 1.1rem;
            margin-top: 2rem;      /* 增加與上方表單的間距 */
            transition: all 0.3s ease;
            display: flex;         /* 使用 flex 布局 */
            align-items: center;   /* 垂直居中 */
            justify-content: center; /* 水平居中 */
            gap: 0.5rem;          /* 圖示和文字的間距 */
        }

        /* 表單分組間距 */
        .form-group {
            margin-bottom: 2rem;  /* 增加表單分組間的間距 */
        }

        /* 最後一個表單元素的間距調整 */
        .form-floating:last-of-type {
            margin-bottom: 2rem;  /* 與按鈕的間距 */
        }

        /* 標籤間距優化 */
        .auth-tabs {
            margin-bottom: 2.5rem;  /* 增加與表單的間距 */
            gap: 0.5rem;           /* 標籤之間的間距 */
        }

        .auth-tab {
            padding: 1rem;         /* 增加標籤內部間距 */
        }
    </style>
</head>
<body>
    <script>
        Swal.fire({
            title: '登出成功',
            text: '感謝您的使用！',
            icon: 'success',
            showConfirmButton: false,
            timer: 1500
        }).then(() => {
            <?php if ($isOwnerLoggedIn): ?>
                Swal.fire({
                    title: '您要？',
                    icon: 'question',
                    showDenyButton: true,
                    confirmButtonText: '重新登入',
                    denyButtonText: '回到入口頁',
                    confirmButtonColor: '#28a745',
                    denyButtonColor: '#6c757d'
                }).then((result) => {
                    if (result.isConfirmed) {
                        // 重新登入
                        window.location.href = '<?php echo $ownerAuthPath; ?>';
                    } else {
                        // 回到入口頁
                        window.location.href = '<?php echo $portalPath; ?>';
                    }
                });
            <?php else: ?>
                // 直接回到入口頁
                window.location.href = '<?php echo $portalPath; ?>';
            <?php endif; ?>
        });
    </script>
</body>
</html>
