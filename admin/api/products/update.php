<?php
require_once __DIR__ . '/../../../camping_db.php';
header('Content-Type: application/json');

try {
    updateProduct();
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

function updateProduct()
{
    global $db;
    $data = json_decode(file_get_contents('php://input'), true);

    if (empty($data['id'])) {
        throw new Exception('缺少必要參數');
    }

    $db->beginTransaction();
    try {
        // 檢查商品是否存在
        $check_exist = $db->prepare("SELECT * FROM products WHERE id = ?");
        $check_exist->execute([$data['id']]);
        $product = $check_exist->fetch(PDO::FETCH_ASSOC);
        
        if (!$product) {
            throw new Exception('找不到該商品');
        }

        // 準備更新欄位
        $updates = [];
        $params = [];

        // 檢查並添加各個欄位的更新
        $fields = ['name', 'category_id', 'subcategory_id', 'price', 'stock', 'description', 'status', 'main_image'];
        foreach ($fields as $field) {
            if (isset($data[$field])) {
                $updates[] = "$field = ?";
                $params[] = $data[$field];
            }
        }

        if (empty($updates)) {
            throw new Exception('沒有要更新的資料');
        }

        // 加入ID參數
        $params[] = $data['id'];

        // 執行更新
        $sql = "UPDATE products SET " . implode(", ", $updates) . " WHERE id = ?";
        $stmt = $db->prepare($sql);
        $result = $stmt->execute($params);

        if (!$result) {
            throw new Exception('更新失敗');
        }

        // 處理圖片集
        if (!empty($data['gallery_images'])) {
            // 先刪除原有的圖片集記錄
            $delete_gallery = $db->prepare("DELETE FROM product_images WHERE product_id = ? AND is_main = 0");
            $delete_gallery->execute([$data['id']]);

            // 插入新的圖片集記錄
            $insert_gallery = $db->prepare("INSERT INTO product_images (product_id, image_path, is_main, sort_order) VALUES (?, ?, 0, ?)");
            foreach ($data['gallery_images'] as $index => $image) {
                $insert_gallery->execute([$data['id'], $image, $index + 1]);
            }
        }

        $db->commit();
        echo json_encode([
            'success' => true,
            'message' => '更新成功',
            'data' => array_merge($product, array_intersect_key($data, $product))
        ]);
    } catch (Exception $e) {
        $db->rollBack();
        throw $e;
    }
}