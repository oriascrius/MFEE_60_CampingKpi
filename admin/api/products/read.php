<?php
require_once __DIR__ . '/../../../camping_db.php';
header('Content-Type: application/json');

try {
    getProduct();
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

function getProduct()
{
    global $db;
    $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
    $status = filter_input(INPUT_GET, 'status', FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => 1]]);
    
    if (!$id) {
        // 獲取所有商品列表
        $sql = "SELECT p.*, 
                c.name as category_name, 
                s.name as subcategory_name,
                pi.image_path as main_image
                FROM products p
                LEFT JOIN categories c ON p.category_id = c.id
                LEFT JOIN subcategories s ON p.subcategory_id = s.id
                LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_main = 1";
        
        if (isset($status)) {
            $sql .= " WHERE p.status = ?";
            $params = [$status];
        } else {
            $params = [];
        }
        
        $sql .= " ORDER BY p.created_at DESC";
        
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['success' => true, 'data' => $products]);
        return;
    }

    // 獲取單個商品詳情
    $sql = "SELECT p.*, c.name as category_name, s.name as subcategory_name 
            FROM products p
            LEFT JOIN categories c ON p.category_id = c.id
            LEFT JOIN subcategories s ON p.subcategory_id = s.id
            WHERE p.id = ?";
            
    if (isset($status)) {
        $sql .= " AND p.status = ?";
        $params = [$id, $status];
    } else {
        $params = [$id];
    }

    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$product) {
        throw new Exception('找不到該商品');
    }

    // 獲取商品圖片
    $img_stmt = $db->prepare("SELECT * FROM product_images WHERE product_id = ? ORDER BY sort_order");
    $img_stmt->execute([$id]);
    $product['images'] = $img_stmt->fetchAll(PDO::FETCH_ASSOC);

    // 獲取商品規格
    $spec_stmt = $db->prepare("SELECT * FROM product_specs WHERE product_id = ? ORDER BY sort_order");
    $spec_stmt->execute([$id]);
    $product['specs'] = $spec_stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'data' => $product]);
}