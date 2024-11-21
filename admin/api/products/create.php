<?php
require_once __DIR__ . '/../../../camping_db.php';
header('Content-Type: application/json');

try {
    createProduct();
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

function createProduct() {
    global $db;
    $data = json_decode(file_get_contents('php://input'), true);
    
    // 基本驗證
    if (!$data) {
        throw new Exception('無效的請求數據');
    }

    // 必填欄位驗證
    $required_fields = ['name', 'category_id', 'subcategory_id', 'price', 'stock'];
    foreach ($required_fields as $field) {
        if (!isset($data[$field])) {
            throw new Exception($field . ' 為必填欄位');
        }
    }

    // 欄位格式驗證
    if (mb_strlen($data['name']) > 100) {
        throw new Exception('商品名稱不能超過100個字符');
    }

    if ($data['price'] <= 0) {
        throw new Exception('商品價格必須大於0');
    }

    if ($data['stock'] < 0) {
        throw new Exception('商品庫存不能小於0');
    }

    $db->beginTransaction();
    try {
        // 檢查分類是否存在
        $check_category = $db->prepare("SELECT id FROM categories WHERE id = ?");
        $check_category->execute([$data['category_id']]);
        if (!$check_category->fetch()) {
            throw new Exception('無效的主分類');
        }

        // 檢查子分類是否存在且屬於該主分類
        $check_subcategory = $db->prepare("SELECT id FROM subcategories WHERE id = ? AND category_id = ?");
        $check_subcategory->execute([$data['subcategory_id'], $data['category_id']]);
        if (!$check_subcategory->fetch()) {
            throw new Exception('無效的子分類');
        }

        // 獲取最大排序值
        $max_sort = $db->query("SELECT COALESCE(MAX(sort_order), 0) FROM products")->fetchColumn();
        $next_sort = $max_sort + 1;

        // 插入商品基本資料
        $sql = "INSERT INTO products (name, category_id, subcategory_id, description, price, 
                stock, sort_order, status) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $db->prepare($sql);
        $result = $stmt->execute([
            trim($data['name']),
            $data['category_id'],
            $data['subcategory_id'],
            $data['description'] ?? '',
            $data['price'],
            $data['stock'],
            $next_sort,
            $data['status'] ?? 1
        ]);

        if (!$result) {
            throw new Exception('商品新增失敗');
        }

        $product_id = $db->lastInsertId();
        
        // 處理主圖
        if (!empty($data['main_image'])) {
            $image_path = basename($data['main_image']); // 只保留檔名部分
            
            // 更新 products 表的 main_image 欄位
            $update_sql = "UPDATE products SET main_image = ? WHERE id = ?";
            $update_stmt = $db->prepare($update_sql);
            if (!$update_stmt->execute([$image_path, $product_id])) {
                throw new Exception('主圖更新失敗');
            }
        }
        
        // 處理圖片集
        if (!empty($data['gallery_images']) && is_array($data['gallery_images'])) {
            $sql = "INSERT INTO product_images (product_id, image_path, is_main, sort_order, status) 
                    VALUES (?, ?, 0, ?, 1)";
            $stmt = $db->prepare($sql);
            
            foreach ($data['gallery_images'] as $index => $image_path) {
                if (!$stmt->execute([$product_id, basename($image_path), $index + 1])) {
                    throw new Exception('圖片集儲存失敗');
                }
            }
        }
        
        $db->commit();
        
        echo json_encode([
            'success' => true,
            'message' => '新增成功',
            'data' => array_merge($data, [
                'id' => $product_id,
                'sort_order' => $next_sort,
                'created_at' => date('Y-m-d H:i:s')
            ])
        ]);
    } catch (Exception $e) {
        $db->rollBack();
        error_log('Create product error: ' . $e->getMessage());
        throw $e;
    }
}