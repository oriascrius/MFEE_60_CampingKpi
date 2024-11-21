<?php
require_once __DIR__ . '/../../../camping_db.php';
header('Content-Type: application/json');

try {
    toggleProductStatus();
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

function toggleProductStatus()
{
    global $db;
    try {
        // 獲取 POST 數據
        $json = file_get_contents('php://input');
        $data = json_decode($json, true);

        if (!isset($data['id']) || !isset($data['status'])) {
            throw new Exception('缺少必要參數');
        }

        $id = filter_var($data['id'], FILTER_VALIDATE_INT);
        $status = filter_var($data['status'], FILTER_VALIDATE_INT);

        if ($id === false || $status === null) {
            throw new Exception('無效的參數');
        }

        $db->beginTransaction();

        // 檢查商品是否存在
        $check_stmt = $db->prepare("SELECT id FROM products WHERE id = ?");
        $check_stmt->execute([$id]);
        if (!$check_stmt->fetch()) {
            throw new Exception('找不到該商品');
        }

        // 更新商品狀態
        $stmt = $db->prepare("UPDATE products SET status = ? WHERE id = ?");
        $result = $stmt->execute([$status, $id]);

        if ($result === false) {
            throw new Exception('更新商品狀態失敗');
        }

        $db->commit();
        echo json_encode([
            'success' => true,
            'message' => $status ? '商品已成功啟用' : '商品已成功停用'
        ]);
    } catch (Exception $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        error_log('Toggle product status error: ' . $e->getMessage());
        throw new Exception('更新狀態失敗：' . $e->getMessage());
    }
}