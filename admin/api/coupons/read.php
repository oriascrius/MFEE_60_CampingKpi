<?php
require_once __DIR__ . '/../../../camping_db.php';
header('Content-Type: application/json');

try {
    getCoupon();
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

function getCoupon()
{
    global $db;
    $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
    $status = filter_input(INPUT_GET, 'status', FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => 1]]);
    
    if (!$id) {
        throw new Exception('無效的優惠券ID');
    }

    $sql = "SELECT * FROM coupons WHERE id = ?";
    if (isset($status)) {
        $sql .= " AND status = ?";
        $params = [$id, $status];
    } else {
        $params = [$id];
    }

    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $coupon = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$coupon) {
        throw new Exception('找不到該優惠券');
    }

    echo json_encode(['success' => true, 'data' => $coupon]);
}