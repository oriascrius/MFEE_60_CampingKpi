<?php
$db_host = 'localhost';
$db_name = 'camp_explorer_db';
$db_user = 'root';
$db_pass = '';
$db_charset = 'utf8mb4';

try {
    // 建立 PDO 連線
    $dsn = "mysql:host={$db_host};dbname={$db_name};charset={$db_charset}";
    $pdo = new PDO($dsn, $db_user, $db_pass);
    
    // 設定錯誤模式為例外
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // 設定預設的提取模式為關聯陣列
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
    // 確保所有字串都使用 utf8mb4 編碼
    $pdo->query("SET NAMES utf8mb4");
    
} catch(PDOException $e) {
    // 資料庫連線失敗時的錯誤處理
    error_log("Database Connection Error: " . $e->getMessage());
    die("資料庫連線失敗，請聯繫系統管理員。");
}

// 設定時區為台北
date_default_timezone_set('Asia/Taipei');

// 初始化變數
$error_message = null;
$users = [];

// 初始化分頁變數
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 10; // 每頁顯示的會員數量
$offset = max(0, ($page - 1) * $perPage);

try {
    $allowed_fields = [
        'id',
        'email',
        'name',
        'phone',
        'birthday',
        'gender',
        'address',
        'avatar',
        'last_login',
        'status',
        'created_at',
        'updated_at'
    ];

    $sort_field = isset($_GET['sort']) ? $_GET['sort'] : 'created_at';
    $sort_order = isset($_GET['order']) ? strtoupper($_GET['order']) : 'ASC';

    // 修改 SQL 查詢，處理不同欄位的排序
    $orderBy = in_array($sort_field, $allowed_fields) ? "u.{$sort_field} {$sort_order}" : "u.created_at ASC";

    // 獲取會員列表，包含所有狀態的會員，並進行分頁
    $sql = "SELECT u.*
            FROM users u
            ORDER BY {$orderBy}
            LIMIT :perPage OFFSET :offset";

    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->bindValue(':perPage', $perPage, PDO::PARAM_INT);
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 獲取會員總數
    $totalStmt = $pdo->query("SELECT COUNT(*) FROM users");
    $totalUsers = $totalStmt->fetchColumn();
    $totalPages = ceil($totalUsers / $perPage);
} catch (Exception $e) {
    $error_message = $e->getMessage();
}
?>
<!-- 頁面主要內容 -->
<div class="container-fluid py-4">
    <?php if ($error_message): ?>
        <div class="alert alert-danger" role="alert">
            <?= htmlspecialchars($error_message) ?>
        </div>
    <?php endif; ?>

    <!-- 頁面標題和新增按鈕 -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="h4 mb-0">會員管理</h2>
        <button type="button" class="btn btn-success" data-action="add">
            <i class="bi bi-plus-lg"></i> 新增會員
        </button>
    </div>

    <!-- 會員列表 -->
    <div class="card">
        <div class="card-body" id="userTableContainer">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th>
                            <button class="btn btn-link text-dark p-0" data-sort="id">
                                編號 <i class="bi bi-arrow-down-up"></i>
                            </button>
                        </th>
                        <th>
                            <button class="btn btn-link text-dark p-0" data-sort="email">
                                Email <i class="bi bi-arrow-down-up"></i>
                            </button>
                        </th>
                        <th>
                            <button class="btn btn-link text-dark p-0" data-sort="name">
                                姓名 <i class="bi bi-arrow-down-up"></i>
                            </button>
                        </th>
                        <th>
                            <button class="btn btn-link text-dark p-0" data-sort="phone">
                                電話 <i class="bi bi-arrow-down-up"></i>
                            </button>
                        </th>
                        <th>
                            <button class="btn btn-link text-dark p-0" data-sort="birthday">
                                生日 <i class="bi bi-arrow-down-up"></i>
                            </button>
                        </th>
                        <th>
                            <button class="btn btn-link text-dark p-0" data-sort="gender">
                                性別 <i class="bi bi-arrow-down-up"></i>
                            </button>
                        </th>
                        <th>
                            <button class="btn btn-link text-dark p-0" data-sort="address">
                                地址 <i class="bi bi-arrow-down-up"></i>
                            </button>
                        </th>
                        <th>
                            <button class="btn btn-link text-dark p-0" data-sort="avatar">
                                頭像 <i class="bi bi-arrow-down-up"></i>
                            </button>
                        </th>
                        <th>
                            <button class="btn btn-link text-dark p-0" data-sort="last_login">
                                最後登入 <i class="bi bi-arrow-down-up"></i>
                            </button>
                        </th>
                        <th>
                            <button class="btn btn-link text-dark p-0" data-sort="status">
                                狀態 <i class="bi bi-arrow-down-up"></i>
                            </button>
                        </th>
                        <th>
                            <button class="btn btn-link text-dark p-0" data-sort="created_at">
                                建立時間 <i class="bi bi-arrow-down-up"></i>
                            </button>
                        </th>
                        <th>
                            <button class="btn btn-link text-dark p-0" data-sort="updated_at">
                                更新時間 <i class="bi bi-arrow-down-up"></i>
                            </button>
                        </th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($users as $user): ?>
    <tr>
        <td><?= htmlspecialchars($user['id']) ?></td>
        <td><?= htmlspecialchars($user['email']) ?></td>
        <td><?= htmlspecialchars($user['name']) ?></td>
        <td><?= htmlspecialchars($user['phone']) ?></td>
        <td><?= htmlspecialchars($user['birthday']) ?></td>
        <td><?= htmlspecialchars($user['gender']) ?></td>
        <td><?= htmlspecialchars($user['address']) ?></td>
        <td>
            <?php if (!empty($user['avatar'])): ?>
                <img src="/CampExplorer/uploads/avatars/<?= htmlspecialchars($user['avatar']) ?>"
                     alt="<?= htmlspecialchars($user['name']) ?>"
                     class="product-thumbnail"
                     onerror="this.onerror=null; this.src='/CampExplorer/assets/images/no-image.png';">
            <?php else: ?>
                <div class="no-image-placeholder">
                    無圖片
                </div>
            <?php endif; ?>
        </td>
        <td><?= htmlspecialchars($user['last_login']) ?></td>
        <td>
            <span class="badge <?= $user['status'] ? 'bg-success' : 'bg-danger' ?>">
                <?= $user['status'] ? '啟用' : '停用' ?>
            </span>
        </td>
        <td><?= date('Y-m-d', strtotime($user['created_at'])) ?></td>
        <td>
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-sm btn-outline-primary" data-action="edit" data-id="<?= $user['id'] ?>">
                    編輯
                </button>
                <button type="button" 
                        class="btn btn-sm <?= $user['status'] ? 'btn-outline-danger' : 'btn-outline-success' ?>"
                        data-action="toggle-status"
                        data-id="<?= $user['id'] ?>"
                        data-status="<?= $user['status'] ?>">
                    <?= $user['status'] ? '停用' : '啟用' ?>
                </button>
            </div>
        </td>
    </tr>
<?php endforeach; ?>
                    
                </tbody>
            </table>
        </div>
    </div>

    <!-- 分頁導航 -->
    <nav aria-label="Page navigation">
        <ul class="pagination justify-content-center">
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                    <a class="page-link" href="/CampExplorer/admin/pages/users/member/member_list.php?page=<?= $i ?>&sort=<?= $sort_field ?>&order=<?= $sort_order ?>"><?= $i ?></a>
                </li>
            <?php endfor; ?>
        </ul>
    </nav>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('[data-action="edit"]').forEach(button => {
        button.addEventListener('click', async function() {
            const userId = this.getAttribute('data-id');
            try {
                const response = await fetch(`/CampExplorer/admin/api/users/member/read.php?id=${userId}`);
                const result = await response.json();
                
                if (!result.success) {
                    throw new Error(result.message);
                }

                const user = result.data;
                const formResult = await Swal.fire({
                    title: '編輯會員',
                    html: `
                        <form id="editUserForm">
                            <div class="mb-3">
                                <label class="form-label">會員名稱</label>
                                <input type="text" class="form-control" name="name" value="${user.name}" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" class="form-control" name="email" value="${user.email}" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">電話</label>
                                <input type="text" class="form-control" name="phone" value="${user.phone}">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">地址</label>
                                <input type="text" class="form-control" name="address" value="${user.address}">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">生日</label>
                                <input type="date" class="form-control" name="birthday" value="${user.birthday}">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">性別</label>
                                <select class="form-control" name="gender" required>
                                    <option value="male" ${user.gender === 'male' ? 'selected' : ''}>男</option>
                                    <option value="female" ${user.gender === 'female' ? 'selected' : ''}>女</option>
                                    <option value="other" ${user.gender === 'other' ? 'selected' : ''}>其他</option>
                                </select>
                            </div>
                        </form>
                    `,
                    showCancelButton: true,
                    confirmButtonText: '保存',
                    preConfirm: () => {
                        const form = document.getElementById('editUserForm');
                        const formData = new FormData(form);
                        return fetch(`/CampExplorer/admin/api/users/member/update.php`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json'
                            },
                            body: JSON.stringify(Object.fromEntries(formData))
                        }).then(response => response.json());
                    }
                });

                if (formResult.isConfirmed) {
                    const updateResult = formResult.value;
                    if (updateResult.success) {
                        Swal.fire('成功', '會員資料已更新', 'success').then(() => {
                            location.reload();
                        });
                    } else {
                        throw new Error(updateResult.message);
                    }
                }
            } catch (error) {
                Swal.fire('錯誤', error.message, 'error');
            }
        });
    });

    document.querySelectorAll('[data-action="toggle-status"]').forEach(button => {
        button.addEventListener('click', async function() {
            const userId = this.getAttribute('data-id');
            try {
                const response = await fetch(`/CampExplorer/admin/api/users/member/read.php?id=${userId}`);
                const result = await response.json();
                
                if (!result.success) {
                    throw new Error(result.message);
                }

                const user = result.data;
                const formResult = await Swal.fire({
                    title: user.status ? '停用會員' : '啟用會員',
                    html: `
                        <p>會員名稱: ${user.name}</p>
                        <p>Email: ${user.email}</p>
                        <p>電話: ${user.phone}</p>
                        <p>地址: ${user.address}</p>
                        <p>生日: ${user.birthday}</p>
                        <p>性別: ${user.gender}</p>
                        <p>狀態: ${user.status ? '啟用' : '停用'}</p>
                        <p>確定要${user.status ? '停用' : '啟用'}此會員嗎？</p>
                    `,
                    showCancelButton: true,
                    confirmButtonText: user.status ? '確定停用' : '確定啟用',
                    cancelButtonText: '取消',
                    preConfirm: () => {
                        const newStatus = user.status == 1 ? 0 : 1;
                        return fetch(`/CampExplorer/admin/api/users/member/toggle-status.php`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json'
                            },
                            body: JSON.stringify({ id: userId, status: newStatus })
                        }).then(response => response.json());
                    }
                });

                if (formResult.isConfirmed) {
                    const updateResult = formResult.value;
                    if (updateResult.success) {
                        Swal.fire('成功', '會員狀態已更新', 'success').then(() => {
                            // 更新狀態欄
                            const statusBadge = button.closest('tr').querySelector('.badge');
                            statusBadge.classList.toggle('bg-success', newStatus === 1);
                            statusBadge.classList.toggle('bg-danger', newStatus === 0);
                            statusBadge.textContent = newStatus === 1 ? '啟用' : '停用';
                            button.classList.toggle('btn-outline-danger', newStatus === 1);
                            button.classList.toggle('btn-outline-success', newStatus === 0);
                            button.textContent = newStatus === 1 ? '停用' : '啟用';
                            button.setAttribute('data-status', newStatus);
                        });
                    } else {
                        throw new Error(updateResult.message);
                    }
                }
            } catch (error) {
                Swal.fire('錯誤', error.message, 'error');
            }
        });
    });

    document.querySelector('button[data-action="add"]').addEventListener('click', async function() {
        try {
            const formResult = await Swal.fire({
                title: '新增會員',
                html: `
                    <form id="addUserForm">
                        <div class="mb-3">
                            <label class="form-label">會員名稱</label>
                            <input type="text" class="form-control" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" name="email" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">電話</label>
                            <input type="text" class="form-control" name="phone">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">地址</label>
                            <input type="text" class="form-control" name="address">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">生日</label>
                            <input type="date" class="form-control" name="birthday">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">性別</label>
                            <select class="form-control" name="gender" required>
                                <option value="male">男</option>
                                <option value="female">女</option>
                                <option value="other">其他</option>
                            </select>
                        </div>
                    </form>
                `,
                showCancelButton: true,
                confirmButtonText: '新增',
                cancelButtonText: '取消',
                preConfirm: () => {
                    const form = document.getElementById('addUserForm');
                    const formData = new FormData(form);
                    return Object.fromEntries(formData);
                }
            });

            if (formResult.isConfirmed && formResult.value) {
                const response = await fetch('/CampExplorer/admin/api/users/member/create.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(formResult.value)
                });

                const result = await response.json();
                if (result.success) {
                    await Swal.fire('成功', '會員新增成功', 'success');
                    location.reload();
                } else {
                    throw new Error(result.message);
                }
            }
        } catch (error) {
            console.error('Error:', error);
            Swal.fire('錯誤', error.message, 'error');
        }
    });
});
</script>

<style>
    .product-thumbnail {
        width: 80px;
        height: 80px;
        object-fit: cover;
        border-radius: 4px;
    }

    .no-image-placeholder {
        width: 80px;
        height: 80px;
        display: flex;
        align-items: center;
        justify-content: center;
        background-color: #f8f9fa;
        border: 1px dashed #dee2e6;
        border-radius: 4px;
        color: #6c757d;
        font-size: 0.875rem;
    }

    .btn-link {
        text-decoration: none;
    }
    .btn-link:hover {
        text-decoration: underline;
    }
    thead button[data-sort] {
        font-weight: bold;
    }
    .pagination {
        margin-top: 20px;
    }
</style>