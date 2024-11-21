<?php
require_once __DIR__ . '/../../../camping_db.php';

// 獲取優惠券數據
try {
    $coupons_sql = "SELECT * FROM coupons ORDER BY created_at DESC";
    $coupons_stmt = $db->prepare($coupons_sql);
    $coupons_stmt->execute();
    $coupons = $coupons_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Database Error: " . $e->getMessage());
    $error_message = "資料載入失敗，請稍後再試";
}
?>

<!-- 主要內容 -->
<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
                    <div>
                        <h5 class="mb-0">優惠券管理</h5>
                        <small class="text-muted">管理系統優惠券</small>
                    </div>
                    <button type="button" class="btn btn-primary" onclick="handleAddCoupon()">
                        <i class="bi bi-plus-lg me-1"></i>新增優惠券
                    </button>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th>優惠券代碼</th>
                                    <th>名稱</th>
                                    <th>折扣類型</th>
                                    <th>折扣值</th>
                                    <th>最低消費</th>
                                    <th>使用期限</th>
                                    <th>狀態</th>
                                    <th>操作</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($coupons as $coupon): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($coupon['code']) ?></td>
                                        <td><?= htmlspecialchars($coupon['name']) ?></td>
                                        <td><?= $coupon['discount_type'] === 'percentage' ? '百分比' : '固定金額' ?></td>
                                        <td>
                                            <?= $coupon['discount_type'] === 'percentage' ? 
                                                htmlspecialchars($coupon['discount_value']) . '%' : 
                                                '$' . htmlspecialchars($coupon['discount_value']) 
                                            ?>
                                        </td>
                                        <td>$<?= htmlspecialchars($coupon['min_purchase']) ?></td>
                                        <td>
                                            <?= date('Y/m/d', strtotime($coupon['start_date'])) ?> - 
                                            <?= date('Y/m/d', strtotime($coupon['end_date'])) ?>
                                        </td>
                                        <td>
                                            <span class="badge <?= $coupon['status'] ? 'bg-success' : 'bg-secondary' ?>">
                                                <?= $coupon['status'] ? '啟用中' : '已停用' ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="btn-group gap-2">
                                                <button type="button" class="btn btn-sm btn-outline-primary"
                                                    onclick="handleEditCoupon(<?= $coupon['id'] ?>)">
                                                    編輯
                                                </button>
                                                <button type="button" 
                                                    class="btn btn-sm <?= $coupon['status'] ? 'btn-outline-danger' : 'btn-outline-success' ?>"
                                                    onclick="handleToggleStatus(<?= $coupon['id'] ?>, <?= $coupon['status'] ?>)">
                                                    <?= $coupon['status'] ? '停用' : '啟用' ?>
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
async function handleAddCoupon() {
    try {
        const result = await Swal.fire({
            title: '新增優惠券',
            html: `
                <form id="addCouponForm" class="text-start">
                    <div class="mb-3">
                        <label class="form-label required">優惠券代碼</label>
                        <div class="input-group">
                            <input type="text" class="form-control" name="code" required maxlength="20">
                            <button type="button" class="btn btn-outline-secondary" onclick="generateCouponCode()">
                                生成代碼
                            </button>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label required">優惠券名稱</label>
                        <input type="text" class="form-control" name="name" required maxlength="50">
                    </div>
                    <div class="mb-3">
                        <label class="form-label required">折扣類型</label>
                        <select class="form-select" name="discount_type" required>
                            <option value="percentage">百分比</option>
                            <option value="fixed">固定金額</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label required">折扣值</label>
                        <input type="number" class="form-control" name="discount_value" required min="0" step="0.01">
                    </div>
                    <div class="mb-3">
                        <label class="form-label required">最低消費金額</label>
                        <input type="number" class="form-control" name="min_purchase" required min="0" step="0.01">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">最高折抵金額</label>
                        <input type="number" class="form-control" name="max_discount" min="0" step="0.01">
                    </div>
                    <div class="mb-3">
                        <label class="form-label required">使用期限</label>
                        <div class="input-group">
                            <input type="datetime-local" class="form-control" name="start_date" required>
                            <span class="input-group-text">至</span>
                            <input type="datetime-local" class="form-control" name="end_date" required>
                        </div>
                    </div>
                </form>
            `,
            showCancelButton: true,
            confirmButtonText: '確定新增',
            cancelButtonText: '取消',
            didOpen: () => {
                // 設置預設的開始時間為現在
                const now = new Date();
                document.querySelector('input[name="start_date"]').value = 
                    now.toISOString().slice(0, 16);
                
                // 設置預設的結束時間為一個月後
                const oneMonthLater = new Date(now.setMonth(now.getMonth() + 1));
                document.querySelector('input[name="end_date"]').value = 
                    oneMonthLater.toISOString().slice(0, 16);
            },
            preConfirm: async () => {
                try {
                    const form = document.getElementById('addCouponForm');
                    const formData = new FormData(form);
                    const data = Object.fromEntries(formData.entries());
                    
                    const response = await axios.post('/CampExplorer/admin/api/coupons/create.php', data);
                    return response.data;
                } catch (error) {
                    Swal.showValidationMessage(error.response?.data?.message || '新增失敗');
                }
            }
        });

        if (result.value?.success) {
            await Swal.fire({
                icon: 'success',
                title: '新增成功',
                showConfirmButton: false,
                timer: 1500
            });
            location.reload();
        }
    } catch (error) {
        console.error('Add coupon error:', error);
        await Swal.fire({
            icon: 'error',
            title: '錯誤',
            text: error.response?.data?.message || '操作失敗'
        });
    }
}

// 生成優惠券代碼的函數
function generateCouponCode() {
    // 生成格式：CAMP + 8位隨機大寫字母和數字
    const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    let code = 'CAMP';
    for (let i = 0; i < 8; i++) {
        code += chars.charAt(Math.floor(Math.random() * chars.length));
    }
    
    // 設置到輸入框
    document.querySelector('input[name="code"]').value = code;
}

async function handleEditCoupon(id) {
    try {
        // 獲取優惠券資料
        const response = await axios.get(`/CampExplorer/admin/api/coupons/read.php?id=${id}`);
        const coupon = response.data.data;

        const result = await Swal.fire({
            title: '編輯優惠券',
            html: `
                <form id="editCouponForm" class="text-start">
                    <input type="hidden" name="id" value="${coupon.id}">
                    <div class="mb-3">
                        <label class="form-label required">優惠券代碼</label>
                        <input type="text" class="form-control" name="code" required maxlength="20" value="${coupon.code}">
                    </div>
                    <div class="mb-3">
                        <label class="form-label required">優惠券名稱</label>
                        <input type="text" class="form-control" name="name" required maxlength="50" value="${coupon.name}">
                    </div>
                    <div class="mb-3">
                        <label class="form-label required">折扣類型</label>
                        <select class="form-select" name="discount_type" required>
                            <option value="percentage" ${coupon.discount_type === 'percentage' ? 'selected' : ''}>百分比</option>
                            <option value="fixed" ${coupon.discount_type === 'fixed' ? 'selected' : ''}>固定金額</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label required">折扣值</label>
                        <input type="number" class="form-control" name="discount_value" required min="0" step="0.01" value="${coupon.discount_value}">
                    </div>
                    <div class="mb-3">
                        <label class="form-label required">最低消費金額</label>
                        <input type="number" class="form-control" name="min_purchase" required min="0" step="0.01" value="${coupon.min_purchase}">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">最高折抵金額</label>
                        <input type="number" class="form-control" name="max_discount" min="0" step="0.01" value="${coupon.max_discount || ''}">
                    </div>
                    <div class="mb-3">
                        <label class="form-label required">使用期限</label>
                        <div class="input-group">
                            <input type="datetime-local" class="form-control" name="start_date" required 
                                value="${coupon.start_date.replace(' ', 'T')}">
                            <span class="input-group-text">至</span>
                            <input type="datetime-local" class="form-control" name="end_date" required 
                                value="${coupon.end_date.replace(' ', 'T')}">
                        </div>
                    </div>
                </form>
            `,
            showCancelButton: true,
            confirmButtonText: '確定修改',
            cancelButtonText: '取消',
            preConfirm: async () => {
                try {
                    const form = document.getElementById('editCouponForm');
                    const formData = new FormData(form);
                    const data = Object.fromEntries(formData.entries());
                    
                    const response = await axios.post('/CampExplorer/admin/api/coupons/update.php', data);
                    return response.data;
                } catch (error) {
                    Swal.showValidationMessage(error.response?.data?.message || '更新失敗');
                }
            }
        });

        if (result.value?.success) {
            await Swal.fire({
                icon: 'success',
                title: '更新成功',
                showConfirmButton: false,
                timer: 1500
            });
            location.reload();
        }
    } catch (error) {
        console.error('Edit coupon error:', error);
        await Swal.fire({
            icon: 'error',
            title: '錯誤',
            text: error.response?.data?.message || '操作失敗'
        });
    }
}

async function handleToggleStatus(id, currentStatus) {
    try {
        const result = await Swal.fire({
            title: '確認操作',
            text: `確定要${currentStatus ? '停用' : '啟用'}此優惠券嗎？`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: '確定',
            cancelButtonText: '取消'
        });

        if (result.isConfirmed) {
            const response = await axios.post('/CampExplorer/admin/api/coupons/update.php', {
                id: id,
                status: currentStatus ? 0 : 1
            });

            if (response.data.success) {
                await Swal.fire({
                    icon: 'success',
                    title: `${currentStatus ? '停用' : '啟用'}成功`,
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
</script>