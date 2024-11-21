<?php
require_once __DIR__ . '/../../../../camping_db.php';
header('Content-Type: application/json');

try {
    updateMember();
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

function updateMember()
{
    global $db;
    $data = json_decode(file_get_contents('php://input'), true);

    if (empty($data['id'])) {
        throw new Exception('缺少必要參數');
    }

    if (!filter_var($data['id'], FILTER_VALIDATE_INT)) {
        throw new Exception('無效的會員ID');
    }

    $db->beginTransaction();
    try {
        // 檢查會員是否存在
        $check_exist = $db->prepare("SELECT * FROM users WHERE id = ?");
        $check_exist->execute([$data['id']]);
        $member = $check_exist->fetch(PDO::FETCH_ASSOC);
        
        if (!$member) {
            throw new Exception('找不到該會員');
        }

        // 準備更新欄位
        $updates = [];
        $params = [];

        // 檢查並設置各個欄位的更新
        if (isset($data['name'])) {
            if (mb_strlen($data['name']) > 50) {
                throw new Exception('會員名稱不能超過50個字符');
            }
            $updates[] = "name = ?";
            $params[] = trim($data['name']);
        }

        if (isset($data['email'])) {
            if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                throw new Exception('無效的電子郵件格式');
            }
            // 檢查信箱是否重複
            $check_stmt = $db->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
            $check_stmt->execute([trim($data['email']), $data['id']]);
            if ($check_stmt->fetch()) {
                throw new Exception('此信箱已被使用');
            }
            $updates[] = "email = ?";
            $params[] = trim($data['email']);
        }

        if (isset($data['phone'])) {
            $updates[] = "phone = ?";
            $params[] = trim($data['phone']);
        }

        if (isset($data['birthday'])) {
            $updates[] = "birthday = ?";
            $params[] = $data['birthday'];
        }

        if (isset($data['gender'])) {
            if (!in_array($data['gender'], ['male', 'female', 'other'])) {
                throw new Exception('無效的性別選項');
            }
            $updates[] = "gender = ?";
            $params[] = $data['gender'];
        }

        if (isset($data['address'])) {
            $updates[] = "address = ?";
            $params[] = trim($data['address']);
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
        $sql = "UPDATE users SET " . implode(", ", $updates) . " WHERE id = ?";
        $stmt = $db->prepare($sql);
        $result = $stmt->execute($params);

        if (!$result) {
            throw new Exception('更新失敗');
        }

        $db->commit();
        echo json_encode([
            'success' => true,
            'message' => '更新成功',
            'data' => array_merge($member, array_intersect_key($data, $member))
        ]);
    } catch (Exception $e) {
        $db->rollBack();
        error_log('Update member error: ' . $e->getMessage());
        throw $e;
    }
}