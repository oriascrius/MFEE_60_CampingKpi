<?php
require_once __DIR__ . '/../../../camping_db.php';
header('Content-Type: application/json');

try {
    deleteCoupon();
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

function deleteCoupon()
{
    global $db;
    try {
        $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
        if (!$id) {
            throw new Exception('無效的優惠券ID');
        }

        $db->beginTransaction();

        // 檢查優惠券是否存在且未被停用
        $check_stmt = $db->prepare("SELECT id FROM coupons WHERE id = ? AND status = 1");
        $check_stmt->execute([$id]);
        if (!$check_stmt->fetch()) {
            throw new Exception('找不到該優惠券或已被停用');
        }

        // 停用優惠券
        $stmt = $db->prepare("UPDATE coupons SET status = 0 WHERE id = ?");
        $result = $stmt->execute([$id]);

        if ($result === false) {
            throw new Exception('停用優惠券失敗');
        }

        $db->commit();
        echo json_encode([
            'success' => true,
            'message' => '已成功停用該優惠券'
        ]);
    } catch (Exception $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        error_log('Delete coupon error: ' . $e->getMessage());
        throw new Exception('停用失敗：' . $e->getMessage());
    }
}