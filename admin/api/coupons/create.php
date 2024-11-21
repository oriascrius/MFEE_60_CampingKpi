<?php
require_once __DIR__ . '/../../../camping_db.php';
header('Content-Type: application/json');

try {
    createCoupon();
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

function createCoupon() {
    global $db;
    $data = json_decode(file_get_contents('php://input'), true);
    
    // 基本驗證
    if (!$data) {
        throw new Exception('無效的請求數據');
    }

    // 必填欄位驗證
    $required_fields = ['code', 'name', 'discount_type', 'discount_value', 'start_date', 'end_date'];
    foreach ($required_fields as $field) {
        if (empty($data[$field])) {
            throw new Exception($field . ' 為必填欄位');
        }
    }

    // 欄位格式驗證
    if (mb_strlen($data['code']) > 20) {
        throw new Exception('優惠券代碼不能超過20個字符');
    }

    if (mb_strlen($data['name']) > 50) {
        throw new Exception('優惠券名稱不能超過50個字符');
    }

    if (!in_array($data['discount_type'], ['percentage', 'fixed'])) {
        throw new Exception('無效的折扣類型');
    }

    // 折扣值驗證
    if ($data['discount_type'] === 'percentage' && ($data['discount_value'] <= 0 || $data['discount_value'] > 100)) {
        throw new Exception('百分比折扣必須在1-100之間');
    }

    if ($data['discount_type'] === 'fixed' && $data['discount_value'] <= 0) {
        throw new Exception('固定金額折扣必須大於0');
    }

    $db->beginTransaction();
    try {
        // 檢查代碼是否重複
        $check_stmt = $db->prepare("SELECT id FROM coupons WHERE code = ?");
        $check_stmt->execute([trim($data['code'])]);
        if ($check_stmt->fetch()) {
            throw new Exception('優惠券代碼已存在');
        }

        $stmt = $db->prepare("INSERT INTO coupons (code, name, discount_type, discount_value, 
            min_purchase, max_discount, start_date, end_date, status) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            
        $result = $stmt->execute([
            trim($data['code']),
            trim($data['name']),
            $data['discount_type'],
            $data['discount_value'],
            $data['min_purchase'] ?? 0,
            $data['max_discount'] ?? null,
            $data['start_date'],
            $data['end_date'],
            1
        ]);

        if (!$result) {
            throw new Exception('新增失敗');
        }

        $coupon_id = $db->lastInsertId();
        
        $db->commit();
        
        echo json_encode([
            'success' => true,
            'message' => '新增成功',
            'data' => [
                'id' => $coupon_id,
                'code' => trim($data['code']),
                'name' => trim($data['name']),
                'discount_type' => $data['discount_type'],
                'discount_value' => $data['discount_value'],
                'min_purchase' => $data['min_purchase'] ?? 0,
                'max_discount' => $data['max_discount'] ?? null,
                'start_date' => $data['start_date'],
                'end_date' => $data['end_date'],
                'status' => 1
            ]
        ]);
    } catch (Exception $e) {
        $db->rollBack();
        error_log('Coupon creation error: ' . $e->getMessage());
        throw $e;
    }
}