<?php
require_once __DIR__ . '/../../../camping_db.php';
header('Content-Type: application/json');

try {
    if (!isset($_FILES['images']) && !isset($_FILES['image'])) {
        throw new Exception('未收到圖片檔案');
    }

    $type = $_POST['type'] ?? 'main';
    $uploaded_files = [];
    
    // 支援的圖片格式和副檔名設定
    $allowed_types = [
        'image/jpeg', 'image/jpg', 'image/png', 'image/gif',
        'image/webp', 'image/avif', 'image/bmp', 'image/tiff'
    ];
    
    $allowed_extensions = [
        'jpg', 'jpeg', 'png', 'gif', 'webp', 'avif', 'bmp', 'tiff'
    ];

    // 設定上傳目錄
    $base_dir = $_SERVER['DOCUMENT_ROOT'] . '/CampExplorer/uploads/products/';
    $upload_dir = $type === 'main' ? $base_dir . 'main/' : $base_dir . 'gallery/';
    
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    // 處理多圖片上傳
    if (isset($_FILES['images'])) {
        foreach ($_FILES['images']['tmp_name'] as $key => $tmp_name) {
            $file_name = $_FILES['images']['name'][$key];
            $file_size = $_FILES['images']['size'][$key];
            $file_type = $_FILES['images']['type'][$key];
            $file_extension = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
            
            // 驗證檔案格式
            if (!in_array($file_type, $allowed_types) || !in_array($file_extension, $allowed_extensions)) {
                continue;
            }
            
            // 驗證檔案大小 (5MB)
            if ($file_size > 5242880) {
                continue;
            }

            $original_name = pathinfo($file_name, PATHINFO_FILENAME);
            $filename = $original_name . '.' . $file_extension;
            $filepath = $upload_dir . $filename;

            // 如果檔案已存在，則在檔名後加上數字
            $counter = 1;
            while (file_exists($filepath)) {
                $filename = $original_name . '_' . $counter . '.' . $file_extension;
                $filepath = $upload_dir . $filename;
                $counter++;
            }

            if (move_uploaded_file($tmp_name, $filepath)) {
                chmod($filepath, 0644);
                $uploaded_files[] = $filename;
            }
        }
    }

    // 處理單一圖片上傳（主圖）
    if (isset($_FILES['image'])) {
        $file = $_FILES['image'];
        $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        if (!in_array($file['type'], $allowed_types) || !in_array($file_extension, $allowed_extensions)) {
            throw new Exception('不支援的檔案格式');
        }

        if ($file['size'] > 5242880) {
            throw new Exception('檔案大小不能超過 5MB');
        }

        // 只保留原始檔名和副檔名
        $original_name = pathinfo($file['name'], PATHINFO_FILENAME);
        $filename = $original_name . '.' . $file_extension;
        $filepath = $upload_dir . $filename;

        // 如果檔案已存在，則在檔名後加上數字
        $counter = 1;
        while (file_exists($filepath)) {
            $filename = $original_name . '_' . $counter . '.' . $file_extension;
            $filepath = $upload_dir . $filename;
            $counter++;
        }

        if (move_uploaded_file($file['tmp_name'], $filepath)) {
            chmod($filepath, 0644);
            $uploaded_files[] = $filename;
        }
    }

    echo json_encode([
        'success' => true,
        'message' => '上傳成功',
        'files' => $uploaded_files
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}