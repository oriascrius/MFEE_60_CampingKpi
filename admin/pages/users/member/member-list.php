<?php
require_once __DIR__ . '/../../../../camping_db.php';

// 獲取會員數據
try {
    $members_sql = "SELECT * FROM users ORDER BY created_at DESC";
    $members_stmt = $db->prepare($members_sql);
    $members_stmt->execute();
    $members = $members_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Database Error: " . $e->getMessage());
    $error_message = "資料載入失敗，請稍後再試";
}
?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
                    <div>
                        <h5 class="mb-0">會員管理</h5>
                        <small class="text-muted">管理系統會員</small>
                    </div>
                    <button type="button" class="btn btn-primary" onclick="handleAddMember()">
                        <i class="bi bi-plus-lg me-1"></i>新增會員
                    </button>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th>編號</th>
                                    <th>姓名</th>
                                    <th>信箱</th>
                                    <th>電話</th>
                                    <th>性別</th>
                                    <th>生日</th>
                                    <th>狀態</th>
                                    <th>註冊時間</th>
                                    <th>操作</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($members as $member): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($member['id']) ?></td>
                                        <td><?= htmlspecialchars($member['name']) ?></td>
                                        <td><?= htmlspecialchars($member['email']) ?></td>
                                        <td><?= htmlspecialchars($member['phone']) ?></td>
                                        <td><?= $member['gender'] === 'male' ? '男' : ($member['gender'] === 'female' ? '女' : '其他') ?></td>
                                        <td><?= $member['birthday'] ? date('Y/m/d', strtotime($member['birthday'])) : '-' ?></td>
                                        <td>
                                            <span class="badge <?= $member['status'] ? 'bg-success' : 'bg-secondary' ?>">
                                                <?= $member['status'] ? '啟用中' : '已停用' ?>
                                            </span>
                                        </td>
                                        <td><?= date('Y/m/d H:i', strtotime($member['created_at'])) ?></td>
                                        <td>
                                            <div class="btn-group gap-2">
                                                <button type="button" class="btn btn-sm btn-outline-primary"
                                                    onclick="handleEditMember(<?= $member['id'] ?>)">
                                                    編輯
                                                </button>
                                                <button type="button" 
                                                    class="btn btn-sm <?= $member['status'] ? 'btn-outline-danger' : 'btn-outline-success' ?>"
                                                    onclick="handleToggleStatus(<?= $member['id'] ?>, <?= $member['status'] ?>)">
                                                    <?= $member['status'] ? '停用' : '啟用' ?>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
async function handleViewMember(id) {
    try {
        const response = await axios.get(`/CampExplorer/admin/api/users/member/read.php?id=${id}`);
        if (response.data.success) {
            const member = response.data.data;
            await Swal.fire({
                title: '會員資料',
                html: `
                    <div class="text-start">
                        <p><strong>姓名：</strong>${member.name}</p>
                        <p><strong>信箱：</strong>${member.email}</p>
                        <p><strong>電話：</strong>${member.phone || '-'}</p>
                        <p><strong>性別：</strong>${member.gender === 'male' ? '男' : (member.gender === 'female' ? '女' : '其他')}</p>
                        <p><strong>生日：</strong>${member.birthday ? new Date(member.birthday).toLocaleDateString() : '-'}</p>
                        <p><strong>地址：</strong>${member.address || '-'}</p>
                        <p><strong>註冊時間：</strong>${new Date(member.created_at).toLocaleString()}</p>
                        <p><strong>最後登入：</strong>${member.last_login ? new Date(member.last_login).toLocaleString() : '-'}</p>
                    </div>
                `,
                confirmButtonText: '關閉'
            });
        }
    } catch (error) {
        console.error('View member error:', error);
        await Swal.fire({
            icon: 'error',
            title: '錯誤',
            text: error.response?.data?.message || '載入會員資料失敗'
        });
    }
}

async function handleToggleStatus(id, currentStatus) {
    try {
        const result = await Swal.fire({
            title: `確定要${currentStatus ? '停用' : '啟用'}此會員？`,
            text: currentStatus ? '停用後該會員將無法登入系統' : '啟用後該會員可以重新登入系統',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: currentStatus ? '確定停用' : '確定啟用',
            cancelButtonText: '取消'
        });

        if (result.isConfirmed) {
            const response = await axios.post('/CampExplorer/admin/api/users/member/update.php', {
                id: id,
                status: currentStatus ? 0 : 1
            });

            if (response.data.success) {
                await Swal.fire({
                    icon: 'success',
                    title: '成功',
                    text: response.data.message,
                    showConfirmButton: false,
                    timer: 1500
                });
                location.reload();
            }
        }
    } catch (error) {
        console.error('Toggle status error:', error);
        await Swal.fire({
            icon: 'error',
            title: '錯誤',
            text: error.response?.data?.message || '操作失敗'
        });
    }
}

async function handleAddMember() {
    try {
        const { value: formValues } = await Swal.fire({
            title: '新增會員',
            html: `
                <form id="addMemberForm" class="text-start needs-validation" novalidate>
                    <div class="mb-3">
                        <label class="form-label required">姓名</label>
                        <input type="text" class="form-control" name="name" required maxlength="50">
                        <div class="invalid-feedback">請輸入姓名（最多50個字）</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label required">信箱</label>
                        <input type="email" class="form-control" name="email" required 
                            pattern="[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,}$">
                        <div class="invalid-feedback">請輸入有效的電子郵件地址</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label required">密碼</label>
                        <input type="password" class="form-control" name="password" required 
                            minlength="6" maxlength="20">
                        <div class="invalid-feedback">密碼長度必須在6-20個字元之間</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">電話</label>
                        <input type="tel" class="form-control" name="phone" 
                            pattern="[0-9]{10}">
                        <div class="invalid-feedback">請輸入有���的電話號碼（10位數字）</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">性別</label>
                        <select class="form-select" name="gender">
                            <option value="male">男</option>
                            <option value="female">女</option>
                            <option value="other">其他</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">生日</label>
                        <input type="date" class="form-control" name="birthday" 
                            max="${new Date().toISOString().split('T')[0]}">
                        <div class="invalid-feedback">生日不能大於今天</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">地址</label>
                        <input type="text" class="form-control" name="address" maxlength="100">
                        <div class="invalid-feedback">地址最多100個字</div>
                    </div>
                </form>
            `,
            didOpen: () => {
                const form = document.getElementById('addMemberForm');
                const inputs = form.querySelectorAll('input');
                
                // 即時驗證
                inputs.forEach(input => {
                    input.addEventListener('input', function() {
                        if (this.checkValidity()) {
                            this.classList.remove('is-invalid');
                            this.classList.add('is-valid');
                        } else {
                            this.classList.remove('is-valid');
                            this.classList.add('is-invalid');
                        }
                    });
                });

                // 檢查信箱是否重複
                const emailInput = form.querySelector('input[name="email"]');
                emailInput.addEventListener('blur', async function() {
                    if (this.checkValidity()) {
                        try {
                            // 使用 create.php 來檢查信箱
                            const response = await axios.post('/CampExplorer/admin/api/users/member/create.php', {
                                email: this.value,
                                checkOnly: true  // 只檢查信箱
                            });
                            this.classList.add('is-valid');
                        } catch (error) {
                            if (error.response?.data?.message.includes('信箱已被使用')) {
                                this.classList.add('is-invalid');
                                this.nextElementSibling.textContent = '此信箱已被使用';
                            }
                        }
                    }
                });
            },
            showCancelButton: true,
            confirmButtonText: '確定新增',
            cancelButtonText: '取消',
            preConfirm: () => {
                const form = document.getElementById('addMemberForm');
                if (!form.checkValidity()) {
                    form.classList.add('was-validated');
                    return false;
                }
                
                const formData = new FormData(form);
                const data = {};
                formData.forEach((value, key) => {
                    if (value) data[key] = value;
                });
                return data;
            }
        });

        if (formValues) {
            const response = await axios.post('/CampExplorer/admin/api/users/member/create.php', formValues);
            if (response.data.success) {
                await Swal.fire({
                    icon: 'success',
                    title: '成功',
                    text: '新增會員成功',
                    showConfirmButton: false,
                    timer: 1500
                });
                location.reload();
            }
        }
    } catch (error) {
        console.error('Add member error:', error);
        Swal.showValidationMessage(error.response?.data?.message || '新增會員失敗');
    }
}

async function handleEditMember(id) {
    try {
        const response = await axios.get(`/CampExplorer/admin/api/users/member/read.php?id=${id}`);
        if (response.data.success) {
            const member = response.data.data;
            const result = await Swal.fire({
                title: '編輯會員資料',
                html: `
                    <form id="editMemberForm" class="text-start">
                        <div class="mb-3">
                            <label class="form-label required">姓名</label>
                            <input type="text" class="form-control" name="name" value="${member.name}" required maxlength="50">
                        </div>
                        <div class="mb-3">
                            <label class="form-label required">信箱</label>
                            <input type="email" class="form-control" name="email" value="${member.email}" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">電話</label>
                            <input type="tel" class="form-control" name="phone" value="${member.phone || ''}">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">性別</label>
                            <select class="form-select" name="gender">
                                <option value="male" ${member.gender === 'male' ? 'selected' : ''}>男</option>
                                <option value="female" ${member.gender === 'female' ? 'selected' : ''}>女</option>
                                <option value="other" ${member.gender === 'other' ? 'selected' : ''}>其他</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">生日</label>
                            <input type="date" class="form-control" name="birthday" value="${member.birthday || ''}">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">地址</label>
                            <input type="text" class="form-control" name="address" value="${member.address || ''}">
                        </div>
                    </form>
                `,
                showCancelButton: true,
                confirmButtonText: '確定修改',
                cancelButtonText: '取消',
                preConfirm: () => {
                    const form = document.getElementById('editMemberForm');
                    const formData = new FormData(form);
                    const data = { id };
                    formData.forEach((value, key) => {
                        if (value) data[key] = value;
                    });
                    return data;
                }
            });

            if (result.isConfirmed) {
                const updateResponse = await axios.post('/CampExplorer/admin/api/users/member/update.php', result.value);
                if (updateResponse.data.success) {
                    await Swal.fire({
                        icon: 'success',
                        title: '成功',
                        text: '更新會員資料成功',
                        showConfirmButton: false,
                        timer: 1500
                    });
                    location.reload();
                }
            }
        }
    } catch (error) {
        console.error('Edit member error:', error);
        await Swal.fire({
            icon: 'error',
            title: '錯誤',
            text: error.response?.data?.message || '編輯會員失敗'
        });
    }
}
</script>