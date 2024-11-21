<?php
require_once '../../includes/db.php';
session_start();

// 檢查是否登入
if (!isset($_SESSION['owner_id'])) {
    header("Location: ../index.php");
    exit;
}

$owner_id = $_SESSION['owner_id'];
$review_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// 獲取營地審核資料
try {
    $stmt = $db->prepare("
        SELECT * FROM campsite_reviews 
        WHERE review_id = ? AND owner_id = ?
    ");
    $stmt->execute([$review_id, $owner_id]);
    $camp = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$camp) {
        $_SESSION['error_message'] = "找不到該營地資料";
        header("Location: camp-list.php");
        exit;
    }
} catch(PDOException $e) {
    die("查詢失敗：" . $e->getMessage());
}

// 處理表單提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $address = trim($_POST['address']);
    $description = trim($_POST['description']);
    $rules = trim($_POST['rules']);
    $notice = trim($_POST['notice']);

    // 基本驗證
    $errors = [];
    if (empty($name)) $errors[] = "請填寫營地名稱";
    if (empty($address)) $errors[] = "請填寫營地地址";

    // 如果沒有錯誤，更新資料
    if (empty($errors)) {
        try {
            $sql = "UPDATE campsite_reviews SET 
                    name = ?, 
                    address = ?, 
                    description = ?, 
                    rules = ?, 
                    notice = ?,
                    status = 0,
                    admin_comment = NULL,
                    updated_at = NOW()
                    WHERE review_id = ? AND owner_id = ?";
            
            $stmt = $db->prepare($sql);
            $stmt->execute([
                $name, $address, $description, 
                $rules, $notice, $review_id, $owner_id
            ]);

            $_SESSION['success_message'] = "營地資料已更新，重新送審中";
            header("Location: camp-list.php");
            exit;
        } catch(PDOException $e) {
            $errors[] = "系統錯誤：" . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>編輯營地 - 營主後台</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.css" rel="stylesheet">
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <?php include '../includes/sidebar.php'; ?>

            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">編輯營地</h1>
                </div>

                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            <?php foreach ($errors as $error): ?>
                                <li><?= htmlspecialchars($error) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <div class="card">
                    <div class="card-body">
                        <form method="POST" action="">
                            <div class="mb-3">
                                <label for="name" class="form-label">營地名稱 <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="name" name="name" 
                                       value="<?= htmlspecialchars($camp['name']) ?>" required>
                            </div>

                            <div class="mb-3">
                                <label for="address" class="form-label">營地地址 <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="address" name="address" 
                                       value="<?= htmlspecialchars($camp['address']) ?>" required>
                            </div>

                            <div class="mb-3">
                                <label for="description" class="form-label">營地介紹</label>
                                <textarea class="form-control summernote" id="description" name="description"><?= htmlspecialchars($camp['description']) ?></textarea>
                            </div>

                            <div class="mb-3">
                                <label for="rules" class="form-label">營地規則</label>
                                <textarea class="form-control summernote" id="rules" name="rules"><?= htmlspecialchars($camp['rules']) ?></textarea>
                            </div>

                            <div class="mb-3">
                                <label for="notice" class="form-label">注意事項</label>
                                <textarea class="form-control summernote" id="notice" name="notice"><?= htmlspecialchars($camp['notice']) ?></textarea>
                            </div>

                            <?php if ($camp['admin_comment']): ?>
                                <div class="mb-3">
                                    <label class="form-label">審核意見</label>
                                    <div class="alert alert-info">
                                        <?= nl2br(htmlspecialchars($camp['admin_comment'])) ?>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                <a href="camp-list.php" class="btn btn-secondary">取消</a>
                                <button type="submit" class="btn btn-primary">更新並重新送審</button>
                            </div>
                        </form>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.1/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.js"></script>
    <script>
        $(document).ready(function() {
            $('.summernote').summernote({
                height: 200,
                toolbar: [
                    ['style', ['bold', 'italic', 'underline', 'clear']],
                    ['font', ['strikethrough']],
                    ['para', ['ul', 'ol']],
                    ['insert', ['link']],
                    ['view', ['fullscreen', 'codeview']]
                ],
                lang: 'zh-TW'
            });
        });
    </script>
</body>
</html>
