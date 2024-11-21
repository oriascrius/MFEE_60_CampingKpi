<?php
require_once __DIR__ . '/../../../../camping_db.php';
header('Content-Type: application/json');

try {
    deleteMember();
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

function deleteMember()
{
    global $db;
    try {
        $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
        if (!$id) {
            throw new Exception('無效的會員ID');
        }

        $db->beginTransaction();

        // 檢查會員是否存在且未被停用
        $check_stmt = $db->prepare("SELECT id FROM users WHERE id = ? AND status = 1");
        $check_stmt->execute([$id]);
        if (!$check_stmt->fetch()) {
            throw new Exception('找不到該會員或已被停用');
        }

        // 停用會員
        $stmt = $db->prepare("UPDATE users SET status = 0 WHERE id = ?");
        $result = $stmt->execute([$id]);

        if ($result === false) {
            throw new Exception('停用會員失敗');
        }

        $db->commit();
        echo json_encode([
            'success' => true,
            'message' => '已成功停用該會員'
        ]);
    } catch (Exception $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        error_log('Delete member error: ' . $e->getMessage());
        throw new Exception('停用失敗：' . $e->getMessage());
    }
}