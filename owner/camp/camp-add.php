<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/CampExplorer/camping_db.php';
session_start();

// 檢查是否登入
if (!isset($_SESSION['owner_id'])) {
    header("Location: ../owner-login.php");
    exit;
}

$owner_id = $_SESSION['owner_id'];
$owner_name = $_SESSION['owner_name'];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 表單處理邏輯
    $name = trim($_POST['name']);
    $address = trim($_POST['address']);
    $description = trim($_POST['description']);
    $rules = trim($_POST['rules'] ?? '');
    $notice = trim($_POST['notice'] ?? '');

    // 驗證
    if (empty($name)) $errors[] = "請填寫營地名稱";
    if (empty($address)) $errors[] = "請填寫營地地址";
    if (empty($description)) $errors[] = "請填寫營地描述";

    if (empty($errors)) {
        try {
            $db->beginTransaction();

            // 先新增營地申請
            $stmt = $db->prepare("
                INSERT INTO camp_applications (
                    owner_id, owner_name, name, address, description, 
                    rules, notice, status
                ) VALUES (?, ?, ?, ?, ?, ?, ?, 0)
            ");

            $stmt->execute([
                $owner_id,
                $owner_name,
                $name,
                $address,
                $description,
                $rules,
                $notice
            ]);

            $application_id = $db->lastInsertId();

            // 處理圖片上傳
            if (!empty($_FILES['images']['name'][0])) {
                $upload_dir = $_SERVER['DOCUMENT_ROOT'] . '/camping_system/uploads/camps/';
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }

                foreach ($_FILES['images']['tmp_name'] as $key => $tmp_name) {
                    $file_name = time() . '_' . $_FILES['images']['name'][$key];
                    $file_path = $upload_dir . $file_name;

                    if (move_uploaded_file($tmp_name, $file_path)) {
                        $stmt = $db->prepare("
                            INSERT INTO camp_images (application_id, image_path) 
                            VALUES (?, ?)
                        ");
                        $stmt->execute([$application_id, 'uploads/camps/' . $file_name]);
                    }
                }
            }

            // 處理營位資訊
            if (isset($_POST['spots']) && is_array($_POST['spots'])) {
                foreach ($_POST['spots'] as $spot) {
                    if (!empty($spot['name']) && !empty($spot['capacity']) && !empty($spot['price'])) {
                        $stmt = $db->prepare("
                            INSERT INTO camp_spot_applications (
                                application_id, owner_name, name, capacity, price, description
                            ) VALUES (?, ?, ?, ?, ?, ?)
                        ");
                        $stmt->execute([
                            $application_id,
                            $owner_name,
                            trim($spot['name']),
                            intval($spot['capacity']),
                            floatval($spot['price']),
                            trim($spot['description'] ?? '')
                        ]);
                    }
                }
            }

            $db->commit();
            echo json_encode([
                'success' => true,
                'message' => '營地申請已提交，請等待審核'
            ]);
            exit;
        } catch (PDOException $e) {
            $db->rollBack();
            echo json_encode([
                'success' => false,
                'message' => '系統錯誤：' . $e->getMessage()
            ]);
            exit;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="zh-TW">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>申請新營地 - 營主後台</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.3/dist/sweetalert2.min.css" rel="stylesheet">
    <style>
        .required::after {
            content: ' *';
            color: red;
        }
    </style>
</head>

<body>
    <div class="container-fluid">
        <div class="row">
            <?php include '../includes/sidebar.php'; ?>

            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">申請新營地</h1>
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

                <form method="POST" enctype="multipart/form-data" class="needs-validation" novalidate>
                    <div class="mb-3">
                        <label class="form-label required">營地名稱</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label required">營地地址</label>
                        <input type="text" name="address" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label required">營地描述</label>
                        <textarea name="description" class="form-control" rows="5" required></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">營地規則</label>
                        <textarea name="rules" class="form-control" rows="5"></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">注意事項</label>
                        <textarea name="notice" class="form-control" rows="5"></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">上傳圖片</label>
                        <input type="file" name="images[]" class="form-control" multiple accept="image/*">
                        <small class="text-muted">可選擇多張圖片，支援 JPG、PNG 格式</small>
                    </div>

                    <!-- 營位資訊區塊 -->
                    <div class="card mb-3">
                        <div class="card-header">
                            <h5 class="mb-0">營位資訊</h5>
                        </div>
                        <div class="card-body">
                            <div id="spotContainer">
                                <div class="spot-item border p-3 mb-3">
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label required">營位名稱</label>
                                            <input type="text" name="spots[0][name]" class="form-control" required>
                                        </div>
                                        <div class="col-md-3 mb-3">
                                            <label class="form-label required">容納人數</label>
                                            <input type="number" name="spots[0][capacity]" class="form-control" required min="1">
                                        </div>
                                        <div class="col-md-3 mb-3">
                                            <label class="form-label required">價格</label>
                                            <input type="number" name="spots[0][price]" class="form-control" required min="0">
                                        </div>
                                        <div class="col-12">
                                            <label class="form-label">營位描述</label>
                                            <textarea name="spots[0][description]" class="form-control" rows="3"></textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <button type="button" class="btn btn-outline-primary" id="addSpot">
                                <i class="bi bi-plus-circle me-2"></i>新增營位
                            </button>
                        </div>
                    </div>

                    <div class="mb-3">
                        <button type="submit" class="btn btn-primary">提交申請</button>
                        <a href="camp-list.php" class="btn btn-secondary">返回列表</a>
                    </div>
                </form>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.1/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.3/dist/sweetalert2.all.min.js"></script>
    <script>
        // 表單驗證
        (function() {
            'use strict'
            var forms = document.querySelectorAll('.needs-validation')
            Array.prototype.slice.call(forms)
                .forEach(function(form) {
                    form.addEventListener('submit', function(event) {
                        if (!form.checkValidity()) {
                            event.preventDefault()
                            event.stopPropagation()
                        }
                        form.classList.add('was-validated')
                    }, false)
                })
        })()

        let spotCount = 1;
        document.getElementById('addSpot').addEventListener('click', function() {
            const template = document.querySelector('.spot-item').cloneNode(true);
            template.querySelectorAll('input, textarea').forEach(input => {
                input.name = input.name.replace('[0]', `[${spotCount}]`);
                input.value = '';
            });
            document.getElementById('spotContainer').appendChild(template);
            spotCount++;
        });

        document.querySelector('form').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            if (!this.checkValidity()) {
                e.stopPropagation();
                this.classList.add('was-validated');
                return;
            }
            
            try {
                const formData = new FormData(this);
                const response = await fetch(window.location.href, {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    await Swal.fire({
                        icon: 'success',
                        title: '成功',
                        text: result.message,
                        confirmButtonText: '確定'
                    });
                    
                    // 重置表單
                    this.classList.remove('was-validated');
                    this.reset();
                    
                    // 清空所有營位（只保留第一個）
                    const spotContainer = document.querySelector('#spotContainer');
                    const firstSpot = spotContainer.querySelector('.spot-item').cloneNode(true);
                    spotContainer.innerHTML = '';
                    spotContainer.appendChild(firstSpot);
                    
                    // 清空第一個營位的所有輸入
                    firstSpot.querySelectorAll('input, textarea').forEach(input => {
                        input.value = '';
                    });
                    
                    // 滾動到頁面頂部
                    window.scrollTo(0, 0);
                    
                } else {
                    await Swal.fire({
                        icon: 'error',
                        title: '錯誤',
                        text: result.message,
                        confirmButtonText: '確定'
                    });
                }
            } catch (error) {
                console.error('Error:', error);
                await Swal.fire({
                    icon: 'error',
                    title: '錯誤',
                    text: '系統發生錯誤，請稍後再試',
                    confirmButtonText: '確定'
                });
            }
        });
    </script>
</body>

</html>