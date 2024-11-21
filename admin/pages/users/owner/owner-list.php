<?php
require_once __DIR__ . '/../../../../camping_db.php';

try {
    $allowed_fields = [
        'id', 'name', 'company_name', 'email', 
        'phone', 'address', 'status', 'created_at'
    ];

    $sort_field = isset($_GET['sort']) ? $_GET['sort'] : 'created_at';
    $sort_order = isset($_GET['order']) ? strtoupper($_GET['order']) : 'DESC';

    // 防止 SQL 注入
    if (!in_array($sort_field, $allowed_fields)) {
        $sort_field = 'created_at';
    }
    if (!in_array($sort_order, ['ASC', 'DESC'])) {
        $sort_order = 'DESC';
    }

    $sql = "SELECT * FROM owners ORDER BY {$sort_field} {$sort_order}";
    $stmt = $db->query($sql);
    $owners = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 如果是 AJAX 請求，只返回表格內容
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
        ?>
        <table class="table table-hover">
            <thead>
                <tr>
                    <?php foreach ($allowed_fields as $field): ?>
                        <th class="sortable" data-field="<?= $field ?>" 
                            data-order="<?= $sort_field === $field ? $sort_order : '' ?>">
                            <?= getFieldLabel($field) ?>
                            <i class="bi bi-arrow-<?= $sort_field === $field ? 
                                ($sort_order === 'ASC' ? 'up' : 'down') : 
                                'down-up' ?> sort-icon"></i>
                        </th>
                    <?php endforeach; ?>
                    <th>操作</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($owners)): ?>
                    <tr><td colspan="9" class="text-center">目前沒有營主資料</td></tr>
                <?php else: ?>
                    <?php foreach ($owners as $owner): ?>
                        <tr>
                            <td><?= htmlspecialchars($owner['id']) ?></td>
                            <td><?= htmlspecialchars($owner['name']) ?></td>
                            <td><?= htmlspecialchars($owner['company_name']) ?></td>
                            <td><?= htmlspecialchars($owner['email']) ?></td>
                            <td><?= htmlspecialchars($owner['phone'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($owner['address'] ?? '-') ?></td>
                            <td>
                                <span class="badge bg-<?= $owner['status'] ? 'success' : 'danger' ?>">
                                    <?= $owner['status'] ? '啟用' : '停用' ?>
                                </span>
                            </td>
                            <td><?= htmlspecialchars($owner['created_at']) ?></td>
                            <td>
                                <button class="btn btn-sm btn-primary me-2" onclick="handleEditOwner(<?= $owner['id'] ?>)">
                                    編輯
                                </button>
                                <button class="btn btn-sm <?= $owner['status'] ? 'btn-danger' : 'btn-success' ?>" 
                                        onclick="handleToggleOwnerStatus(<?= $owner['id'] ?>, <?= $owner['status'] ?>)">
                                    <?= $owner['status'] ? '停用' : '啟用' ?>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
        <?php
        exit;
    }
} catch (PDOException $e) {
    error_log("Database Error: " . $e->getMessage());
    $error_message = "資料載入失敗，請稍後再試";
}
?>

<div class="container-fluid py-4">
    <?php if (isset($error_message)): ?>
        <div class="alert alert-danger" role="alert">
            <?= htmlspecialchars($error_message) ?>
        </div>
    <?php endif; ?>
    
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
                    <div>
                        <h5 class="mb-0">營主管理</h5>
                        <small class="text-muted">管理系統營主</small>
                    </div>
                    <button type="button" class="btn btn-primary" onclick="handleAddOwner()">
                        <i class="bi bi-plus-lg me-1"></i>新增營主
                    </button>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <?php foreach ($allowed_fields as $field): ?>
                                        <th class="sortable" data-field="<?= $field ?>" 
                                            data-order="<?= $sort_field === $field ? $sort_order : '' ?>">
                                            <?= getFieldLabel($field) ?>
                                            <i class="bi bi-arrow-<?= $sort_field === $field ? 
                                                ($sort_order === 'ASC' ? 'up' : 'down') : 
                                                'down-up' ?> sort-icon"></i>
                                        </th>
                                    <?php endforeach; ?>
                                    <th>操作</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php if (empty($owners)): ?>
                                <tr>
                                    <td colspan="9" class="text-center">目前沒有營主資料</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($owners as $owner): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($owner['id']) ?></td>
                                        <td><?= htmlspecialchars($owner['name']) ?></td>
                                        <td><?= htmlspecialchars($owner['company_name']) ?></td>
                                        <td><?= htmlspecialchars($owner['email']) ?></td>
                                        <td><?= htmlspecialchars($owner['phone'] ?? '-') ?></td>
                                        <td><?= htmlspecialchars($owner['address'] ?? '-') ?></td>
                                        <td>
                                            <span class="badge bg-<?= $owner['status'] ? 'success' : 'danger' ?>">
                                                <?= $owner['status'] ? '啟用' : '停用' ?>
                                            </span>
                                        </td>
                                        <td><?= htmlspecialchars($owner['created_at']) ?></td>
                                        <td class="btn-group gap-2">
                                            <button class="btn btn-sm btn-outline-primary" 
                                                    onclick="handleEditOwner(<?= $owner['id'] ?>)">
                                                編輯
                                            </button>
                                            <button class="btn btn-sm  <?= $owner['status'] ? 'btn-outline-danger' : 'btn-outline-success' ?>" 
                                                    onclick="handleToggleOwnerStatus(<?= $owner['id'] ?>, <?= $owner['status'] ?>)">
                                                <?= $owner['status'] ? '停用' : '啟用' ?>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- 營主表單 Modal -->
<div class="modal fade" id="ownerModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTitle">新增營主</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="ownerForm" novalidate>
                    <input type="hidden" id="ownerId">
                    <div class="mb-3">
                        <label class="form-label">電子郵件</label>
                        <input type="email" class="form-control" id="email" required>
                        <div class="invalid-feedback" id="emailError"></div>
                    </div>
                    <div class="mb-3 password-field">
                        <label class="form-label">密碼</label>
                        <input type="password" class="form-control" id="password" required>
                        <div class="invalid-feedback" id="passwordError"></div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">營主姓名</label>
                        <input type="text" class="form-control" id="name" required>
                        <div class="invalid-feedback" id="nameError"></div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">公司名稱</label>
                        <input type="text" class="form-control" id="company_name" required>
                        <div class="invalid-feedback" id="companyError"></div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">聯絡電話</label>
                        <input type="tel" class="form-control" id="phone">
                        <div class="invalid-feedback" id="phoneError"></div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">地址</label>
                        <input type="text" class="form-control" id="address">
                        <div class="invalid-feedback" id="addressError"></div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">狀態</label>
                        <select class="form-select" id="status">
                            <option value="1">啟用</option>
                            <option value="0">停</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
                <button type="button" class="btn btn-primary" onclick="handleSubmit()">確定</button>
            </div>
        </div>
    </div>
</div>

<style>
.sortable {
    cursor: pointer;
    user-select: none;
}
.sortable:hover {
    background-color: rgba(0,0,0,0.05);
}
.sort-icon {
    margin-left: 5px;
    transition: transform 0.2s;
}
.sortable[data-order="ASC"] .sort-icon {
    color: #0d6efd;
}
.sortable[data-order="DESC"] .sort-icon {
    color: #0d6efd;
}
</style>

<script>
const ownerModal = new bootstrap.Modal(document.getElementById('ownerModal'));
let isEditing = false;

// 即時驗證功能
function setupValidation() {
    const form = document.getElementById('ownerForm');
    const emailInput = document.getElementById('email');
    const passwordInput = document.getElementById('password');
    const nameInput = document.getElementById('name');
    const companyInput = document.getElementById('company_name');

    // 電子郵件驗證
    emailInput.addEventListener('input', async function() {
        const email = this.value.trim();
        const emailError = document.getElementById('emailError');
        
        if (!email) {
            showError(this, emailError, '請輸入電子郵件');
            return;
        }
        
        if (!isValidEmail(email)) {
            showError(this, emailError, '無效的電子郵件格式');
            return;
        }

        try {
            const response = await fetch(`/CampExplorer/admin/api/users/owner/check-email.php?email=${email}`);
            const result = await response.json();
            
            if (!result.success) {
                showError(this, emailError, '此信箱���被使用');
                return;
            }
        } catch (error) {
            console.error('Email check error:', error);
        }

        showSuccess(this, emailError);
    });

    // 密碼驗證
    passwordInput.addEventListener('input', function() {
        const password = this.value;
        const passwordError = document.getElementById('passwordError');
        
        if (!password && !isEditing) {
            showError(this, passwordError, '請輸入密碼');
            return;
        }
        
        showSuccess(this, passwordError);
    });

    // 營主姓名驗證
    nameInput.addEventListener('input', function() {
        const name = this.value.trim();
        const nameError = document.getElementById('nameError');
        
        if (!name) {
            showError(this, nameError, '請輸入營主姓名');
            return;
        }
        
        if (name.length > 50) {
            showError(this, nameError, '營主姓名不能超過50個字符');
            return;
        }
        
        showSuccess(this, nameError);
    });

    // 公司名稱驗證
    companyInput.addEventListener('input', function() {
        const company = this.value.trim();
        const companyError = document.getElementById('companyError');
        
        if (!company) {
            showError(this, companyError, '請輸入公司名稱');
            return;
        }
        
        if (company.length > 100) {
            showError(this, companyError, '公司名稱不能超過100個字符');
            return;
        }
        
        showSuccess(this, companyError);
    });
}

function showError(input, errorElement, message) {
    input.classList.add('is-invalid');
    input.classList.remove('is-valid');
    errorElement.textContent = message;
}

function showSuccess(input, errorElement) {
    input.classList.remove('is-invalid');
    input.classList.add('is-valid');
    errorElement.textContent = '';
}

function isValidEmail(email) {
    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
}

// 在模態框打開時初始化驗證
document.getElementById('ownerModal').addEventListener('shown.bs.modal', setupValidation);

// 修改原有的提交處理函數
async function handleSubmit() {
    const form = document.getElementById('ownerForm');
    const inputs = form.querySelectorAll('input[required]');
    let hasError = false;

    inputs.forEach(input => {
        if (!input.value.trim()) {
            const errorElement = document.getElementById(`${input.id}Error`);
            showError(input, errorElement, `請輸入${input.previousElementSibling.textContent.trim()}`);
            hasError = true;
        }
    });

    if (hasError) {
        return;
    }

    try {
        const formData = {
            email: document.getElementById('email').value.trim(),
            name: document.getElementById('name').value.trim(),
            company_name: document.getElementById('company_name').value.trim(),
            phone: document.getElementById('phone').value.trim(),
            address: document.getElementById('address').value.trim(),
            status: document.getElementById('status').value
        };

        if (!isEditing) {
            formData.password = document.getElementById('password').value;
        } else {
            formData.id = document.getElementById('ownerId').value;
        }

        const response = await fetch(`/CampExplorer/admin/api/users/owner/${isEditing ? 'update' : 'create'}.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(formData)
        });

        const result = await response.json();
        
        if (result.success) {
            await Swal.fire({
                title: '成功',
                text: result.message,
                icon: 'success'
            });
            ownerModal.hide();
            location.reload();
        } else {
            throw new Error(result.message);
        }
    } catch (error) {
        await Swal.fire({
            title: '錯誤',
            text: error.message,
            icon: 'error'
        });
    }
}

async function handleToggleOwnerStatus(id, currentStatus) {
    const action = currentStatus ? '停用' : '啟用';
    try {
        const result = await Swal.fire({
            title: `確定要${action}此營主嗎？`,
            text: `營主將被${action}`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: `確定${action}`,
            cancelButtonText: '取消'
        });

        if (result.isConfirmed) {
            const response = await axios.get(`/CampExplorer/admin/api/users/owner/delete.php?id=${id}`);
            const data = response.data;

            if (data.success) {
                await Swal.fire({
                    title: '成功',
                    text: data.message,
                    icon: 'success'
                });
                
                // 重新載入表格內容
                const url = new URL(window.location.href);
                const response = await axios.get(url.toString(), {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                document.querySelector('.table-responsive').innerHTML = response.data;
                TableSort.init();
            } else {
                throw new Error(data.message);
            }
        }
    } catch (error) {
        await Swal.fire({
            title: '錯誤',
            text: `${action}失敗：${error.message}`,
            icon: 'error'
        });
    }
}

async function handleAddOwner() {
    isEditing = false;
    document.getElementById('modalTitle').textContent = '新增營主';
    document.getElementById('ownerForm').reset();
    document.querySelector('.password-field').style.display = 'block';
    ownerModal.show();
}

async function handleEditOwner(id) {
    try {
        const response = await fetch(`/CampExplorer/admin/api/users/owner/read.php?id=${id}`);
        const result = await response.json();
        
        if (!result.success) {
            throw new Error(result.message);
        }

        isEditing = true;
        const owner = result.data;
        
        document.getElementById('modalTitle').textContent = '編輯營主';
        document.getElementById('ownerId').value = owner.id;
        document.getElementById('email').value = owner.email;
        document.getElementById('name').value = owner.name;
        document.getElementById('company_name').value = owner.company_name;
        document.getElementById('phone').value = owner.phone || '';
        document.getElementById('address').value = owner.address || '';
        document.getElementById('status').value = owner.status;
        
        document.querySelector('.password-field').style.display = 'none';
        ownerModal.show();
    } catch (error) {
        await Swal.fire({
            title: '錯誤',
            text: error.message,
            icon: 'error'
        });
    }
}

const TableSort = {
    init() {
        const headers = document.querySelectorAll('th.sortable');
        headers.forEach(header => {
            header.addEventListener('click', this.handleSort.bind(this));
        });
    },

    async handleSort(event) {
        const header = event.currentTarget;
        const field = header.dataset.field;
        const currentOrder = header.dataset.order || '';
        const newOrder = currentOrder === 'ASC' ? 'DESC' : 'ASC';
        
        // 更新所有表頭的排序狀態
        document.querySelectorAll('th.sortable').forEach(h => {
            h.dataset.order = '';
            const icon = h.querySelector('.sort-icon');
            icon.className = 'bi bi-arrow-down-up sort-icon';
        });
        
        // 設置當前表頭的排序狀態
        header.dataset.order = newOrder;
        const icon = header.querySelector('.sort-icon');
        icon.className = `bi bi-arrow-${newOrder === 'ASC' ? 'up' : 'down'} sort-icon`;
        
        try {
            const url = new URL(window.location.href);
            url.searchParams.set('sort', field);
            url.searchParams.set('order', newOrder);
            
            const response = await axios.get(url.toString(), {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            
            document.querySelector('.table-responsive').innerHTML = response.data;
            history.pushState({}, '', url.toString());
            this.init();
        } catch (error) {
            console.error('Sort error:', error);
            Swal.fire({
                icon: 'error',
                title: '錯誤',
                text: '排序失敗，請稍後再試'
            });
        }
    }
};

document.addEventListener('DOMContentLoaded', () => {
    TableSort.init();
});
</script>

<?php
function getFieldLabel($field) {
    $labels = [
        'id' => '編號',
        'name' => '姓名',
        'company_name' => '公司名稱',
        'email' => '信箱',
        'phone' => '電話',
        'address' => '地址',
        'status' => '狀態',
        'created_at' => '註冊時間'
    ];
    return $labels[$field] ?? $field;
}
?>