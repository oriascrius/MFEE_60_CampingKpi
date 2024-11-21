<?php
require_once __DIR__ . '/../../../camping_db.php';
header('Content-Type: application/json');

try {
    updateCoupon();
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

function updateCoupon()
{
    global $db;
    $data = json_decode(file_get_contents('php://input'), true);

    if (empty($data['id'])) {
        throw new Exception('缺少必要參數');
    }

    if (!filter_var($data['id'], FILTER_VALIDATE_INT)) {
        throw new Exception('無效的優惠券ID');
    }

    $db->beginTransaction();
    try {
        // 檢查優惠券是否存在
        $check_exist = $db->prepare("SELECT * FROM coupons WHERE id = ?");
        $check_exist->execute([$data['id']]);
        $coupon = $check_exist->fetch(PDO::FETCH_ASSOC);
        
        if (!$coupon) {
            throw new Exception('找不到該優惠券');
        }

        // 準備更新欄位
        $updates = [];
        $params = [];

        // 檢查並設置各個欄位的更新
        if (isset($data['name'])) {
            if (mb_strlen($data['name']) > 50) {
                throw new Exception('優惠券名稱不能超過50個字符');
            }
            $updates[] = "name = ?";
            $params[] = trim($data['name']);
        }

        if (isset($data['code'])) {
            if (mb_strlen($data['code']) > 20) {
                throw new Exception('優惠券代碼不能超過20個字符');
            }
            // 檢查代碼是否重複
            $check_stmt = $db->prepare("SELECT id FROM coupons WHERE code = ? AND id != ?");
            $check_stmt->execute([trim($data['code']), $data['id']]);
            if ($check_stmt->fetch()) {
                throw new Exception('優惠券代碼已存在');
            }
            $updates[] = "code = ?";
            $params[] = trim($data['code']);
        }

        if (isset($data['discount_type'])) {
            if (!in_array($data['discount_type'], ['percentage', 'fixed'])) {
                throw new Exception('無效的折扣類型');
            }
            $updates[] = "discount_type = ?";
            $params[] = $data['discount_type'];
        }

        if (isset($data['discount_value'])) {
            if ($data['discount_type'] === 'percentage' && ($data['discount_value'] <= 0 || $data['discount_value'] > 100)) {
                throw new Exception('百分比折扣必須在1-100之間');
            }
            if ($data['discount_type'] === 'fixed' && $data['discount_value'] <= 0) {
                throw new Exception('固定金額折扣必須大於0');
            }
            $updates[] = "discount_value = ?";
            $params[] = $data['discount_value'];
        }

        if (isset($data['min_purchase'])) {
            $updates[] = "min_purchase = ?";
            $params[] = $data['min_purchase'];
        }

        if (isset($data['max_discount'])) {
            $updates[] = "max_discount = ?";
            $params[] = $data['max_discount'];
        }

        if (isset($data['start_date'])) {
            $updates[] = "start_date = ?";
            $params[] = $data['start_date'];
        }

        if (isset($data['end_date'])) {
            $updates[] = "end_date = ?";
            $params[] = $data['end_date'];
        }

        if (isset($data['status'])) {
            $updates[] = "status = ?";
            $params[] = intval($data['status']);
        }

        if (empty($updates)) {
            throw new Exception('沒有要更新的資料');
        }

        // 加入ID參數
        $params[] = $data['id'];

        // 執行更新
        $sql = "UPDATE coupons SET " . implode(", ", $updates) . " WHERE id = ?";
        $stmt = $db->prepare($sql);
        $result = $stmt->execute($params);

        if (!$result) {
            throw new Exception('更新失敗');
        }

        $db->commit();
        echo json_encode([
            'success' => true,
            'message' => '更新成功',
            'data' => array_merge($coupon, array_intersect_key($data, $coupon))
        ]);
    } catch (Exception $e) {
        $db->rollBack();
        error_log('Update coupon error: ' . $e->getMessage());
        throw $e;
    }
}