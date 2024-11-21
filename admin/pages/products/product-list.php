<?php
require_once __DIR__ . '/../../../camping_db.php';

// 初始化變數
$error_message = null;
$products = [];

try {
    $allowed_fields = [
        'id',
        'name',
        'category_name',
        'subcategory_name',
        'price',
        'stock',
        'sort_order',
        'status',
        'created_at'
    ];

    $sort_field = isset($_GET['sort']) ? $_GET['sort'] : 'sort_order';
    $sort_order = isset($_GET['order']) ? strtoupper($_GET['order']) : 'ASC';

    // 修改 SQL 查詢，處理不同欄位的排序
    $orderBy = match ($sort_field) {
        'category_name' => "c.sort_order {$sort_order}, c.name",
        'subcategory_name' => "s.sort_order {$sort_order}, s.name",
        'name', 'price', 'stock', 'status', 'created_at' => "p.{$sort_field}",
        'sort_order' => "p.sort_order {$sort_order}, p.name",
        default => "p.sort_order"
    };

    // 獲取商品列表
    $sql = "SELECT p.*, 
            c.name as category_name, 
            s.name as subcategory_name,
            p.main_image
            FROM products p
            LEFT JOIN categories c ON p.category_id = c.id
            LEFT JOIN subcategories s ON p.subcategory_id = s.id
            WHERE c.status = 1 AND s.status = 1
            ORDER BY {$orderBy} {$sort_order}";

    $stmt = $db->query($sql);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Database Error: " . $e->getMessage());
    $error_message = "資料載入失敗，請稍後再試";
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
        <h2 class="h4 mb-0">商品管理</h2>
        <button type="button" class="btn btn-success" data-action="add">
            <i class="bi bi-plus-lg"></i> 新增商品
        </button>
    </div>

    <!-- 商品列表 -->
    <div class="card">
        <div class="card-body" id="productTableContainer">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th>
                            <button class="btn btn-link text-dark p-0" data-sort="id">
                                編號 <i class="bi bi-arrow-down-up"></i>
                            </button>
                        </th>
                        <th>商品圖片</th>
                        <th>
                            <button class="btn btn-link text-dark p-0" data-sort="name">
                                商品名稱 <i class="bi bi-arrow-down-up"></i>
                            </button>
                        </th>
                        <th>
                            <button class="btn btn-link text-dark p-0" data-sort="category_name">
                                主分類 <i class="bi bi-arrow-down-up"></i>
                            </button>
                        </th>
                        <th>
                            <button class="btn btn-link text-dark p-0" data-sort="subcategory_name">
                                子分類 <i class="bi bi-arrow-down-up"></i>
                            </button>
                        </th>
                        <th>
                            <button class="btn btn-link text-dark p-0" data-sort="price">
                                價格 <i class="bi bi-arrow-down-up"></i>
                            </button>
                        </th>
                        <th>
                            <button class="btn btn-link text-dark p-0" data-sort="stock">
                                庫存 <i class="bi bi-arrow-down-up"></i>
                            </button>
                        </th>
                        <th>
                            <button class="btn btn-link text-dark p-0" data-sort="sort_order">
                                排序 <i class="bi bi-arrow-down-up"></i>
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
                        <th>操作</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($products)): ?>
                        <tr>
                            <td colspan="11" class="text-center">目前沒有商品資料</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($products as $product): ?>
                            <tr>
                                <td><?= htmlspecialchars($product['id']) ?></td>
                                <td>
                                    <?php if (!empty($product['main_image'])): ?>
                                        <img src="/CampExplorer/uploads/products/main/<?= htmlspecialchars($product['main_image']) ?>"
                                             alt="<?= htmlspecialchars($product['name']) ?>"
                                             class="product-thumbnail"
                                             onerror="this.onerror=null; this.src='/CampExplorer/assets/images/no-image.png';">
                                    <?php else: ?>
                                        <div class="no-image-placeholder">
                                            無圖片
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars($product['name']) ?></td>
                                <td><?= htmlspecialchars($product['category_name']) ?></td>
                                <td><?= htmlspecialchars($product['subcategory_name']) ?></td>
                                <td>$<?= number_format($product['price'], 0) ?></td>
                                <td><?= htmlspecialchars($product['stock']) ?></td>
                                <td><?= htmlspecialchars($product['sort_order']) ?></td>
                                <td>
                                    <span class="badge <?= $product['status'] ? 'bg-success' : 'bg-danger' ?>">
                                        <?= $product['status'] ? '啟用' : '停用' ?>
                                    </span>
                                </td>
                                <td><?= date('Y-m-d', strtotime($product['created_at'])) ?></td>
                                <td>
                                    <div class="d-flex gap-2">
                                        <button type="button" class="btn btn-sm btn-outline-primary" data-action="edit" data-id="<?= $product['id'] ?>">
                                            編輯
                                        </button>
                                        <button type="button" 
                                                class="btn btn-sm <?= $product['status'] ? 'btn-outline-danger' : 'btn-outline-success' ?>"
                                                data-action="toggle-status"
                                                data-id="<?= $product['id'] ?>"
                                                data-status="<?= $product['status'] ?>">
                                            <?= $product['status'] ? '停用' : '啟用' ?>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php
function getFieldLabel($field)
{
    $labels = [
        'id' => '編號',
        'main_image' => '商品圖片',
        'name' => '商品名稱',
        'category_name' => '主分類',
        'subcategory_name' => '子分類',
        'price' => '格',
        'stock' => '庫存',
        'sort_order' => '排序',
        'status' => '狀態',
        'created_at' => '建立時間'
    ];
    return $labels[$field] ?? $field;
}
?>

<script>
    const ProductUI = {
        currentSort: {
            field: 'id',
            order: 'asc'
        },

        init() {
            this.initEventListeners();
            this.initSortButtons();
        },

        initEventListeners() {
            // 表格事件監聽
            const table = document.querySelector('table');
            if (table) {
                table.addEventListener('click', async (e) => {
                    const button = e.target.closest('button');
                    if (!button) return;

                    const action = button.dataset.action;
                    const id = button.dataset.id;
                    const status = button.dataset.status;

                    switch (action) {
                        case 'edit':
                            await this.handleEditProduct(id);
                            break;
                        case 'toggle-status':
                            await this.handleToggleStatus(id, parseInt(status));
                            break;
                    }
                });
            }

            // 新增按鈕事件監聽
            const addButton = document.querySelector('button[data-action="add"]');
            if (addButton) {
                addButton.addEventListener('click', () => this.handleAddProduct());
            }
        },

        initSortButtons() {
            const thead = document.querySelector('thead');
            if (!thead) return;

            thead.addEventListener('click', async (e) => {
                const sortBtn = e.target.closest('button[data-sort]');
                if (!sortBtn) return;

                const field = sortBtn.dataset.sort;
                this.currentSort.order = this.currentSort.field === field && this.currentSort.order === 'asc' ? 'desc' : 'asc';
                this.currentSort.field = field;

                // 更新 URL 參數
                const url = new URL(window.location.href);
                url.searchParams.set('sort', field);
                url.searchParams.set('order', this.currentSort.order);
                window.history.pushState({}, '', url);

                // 重新載入商品列表
                await this.loadProducts();
            });
        },

        async loadProducts() {
            try {
                const response = await fetch(`/CampExplorer/admin/api/products/read.php?sort=${this.currentSort.field}&order=${this.currentSort.order}`);
                const result = await response.json();

                if (!result.success) {
                    throw new Error(result.message);
                }

                // 更新表格內容
                const tbody = document.querySelector('tbody');
                tbody.innerHTML = this.renderProducts(result.data);

                // 更新排序圖示
                this.updateSortIcons();
            } catch (error) {
                console.error('Error:', error);
                Swal.fire('錯誤', error.message, 'error');
            }
        },

        updateSortIcons() {
            document.querySelectorAll('thead button[data-sort]').forEach(btn => {
                const field = btn.dataset.sort;
                const icon = btn.querySelector('i');
                if (field === this.currentSort.field) {
                    icon.className = `bi bi-arrow-${this.currentSort.order === 'asc' ? 'up' : 'down'}`;
                } else {
                    icon.className = 'bi bi-arrow-down-up';
                }
            });
        },

        renderProducts(products) {
            if (!products.length) {
                return '<tr><td colspan="11" class="text-center">目前沒有商品資料</td></tr>';
            }

            return products.map(product => `
                <tr>
                    <td>${product.id}</td>
                    <td>
                        ${product.main_image ? `
                            <img src="/CampExplorer/uploads/products/main/${product.main_image}"
                                 alt="${product.name}"
                                 class="product-thumbnail">
                        ` : '<div class="no-image-placeholder">無圖片</div>'}
                    </td>
                    <td>${product.name}</td>
                    <td>${product.category_name}</td>
                    <td>${product.subcategory_name}</td>
                    <td>${product.price}</td>
                    <td>${product.stock}</td>
                    <td>${product.sort_order}</td>
                    <td>
                        <span class="badge ${product.status ? 'bg-success' : 'bg-danger'}">
                            ${product.status ? '啟用' : '停用'}
                        </span>
                    </td>
                    <td>${new Date(product.created_at).toLocaleDateString()}</td>
                    <td>
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-sm btn-outline-primary" 
                                    data-action="edit" data-id="${product.id}">
                                編輯
                            </button>
                            <button type="button" 
                                    class="btn btn-sm ${product.status ? 'btn-outline-danger' : 'btn-outline-success'}"
                                    data-action="toggle-status"
                                    data-id="${product.id}"
                                    data-status="${product.status}">
                                ${product.status ? '停用' : '啟用'}
                            </button>
                        </div>
                    </td>
                </tr>
            `).join('');
        },

        setupImagePreviews(form) {
            // 主圖預覽
            const mainImageInput = form.querySelector('input[name="main_image"]');
            const mainImagePreview = document.getElementById('mainImagePreview');
            
            mainImageInput.addEventListener('change', function(e) {
                const file = this.files[0];
                if (file) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        mainImagePreview.innerHTML = `
                            <img src="${e.target.result}" class="img-thumbnail" 
                                 style="max-width: 200px; height: 150px; object-fit: cover;">
                        `;
                    }
                    reader.readAsDataURL(file);
                }
            });

            // 圖片集預覽
            const galleryInput = form.querySelector('input[name="gallery_images[]"]');
            const galleryPreview = document.getElementById('galleryPreview');
            
            galleryInput.addEventListener('change', function(e) {
                Array.from(this.files).forEach(file => {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        const div = document.createElement('div');
                        div.className = 'col-md-3 col-sm-4 col-6 mb-2';
                        div.innerHTML = `
                            <div class="position-relative">
                                <img src="${e.target.result}" class="img-thumbnail w-100" 
                                     style="height: 150px; object-fit: cover;">
                                <div class="position-absolute top-0 end-0 p-1">
                                    <button type="button" class="btn btn-danger btn-sm rounded-circle" 
                                            onclick="this.closest('.col-md-3').remove()">
                                        <i class="bi bi-x"></i>
                                    </button>
                                </div>
                            </div>
                        `;
                        galleryPreview.appendChild(div);
                    }
                    reader.readAsDataURL(file);
                });
            });
        },

        async handleEditProduct(id) {
            try {
                const response = await fetch(`/CampExplorer/admin/api/products/read.php?id=${id}`);
                const result = await response.json();
                
                if (!result.success) {
                    throw new Error(result.message);
                }

                const product = result.data;
                const formResult = await Swal.fire({
                    title: '編輯商品',
                    html: `
                        <form id="editProductForm">
                            <div class="mb-3">
                                <label class="form-label">商品名稱</label>
                                <input type="text" class="form-control" name="name" value="${product.name}" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">主分類</label>
                                <select class="form-control" name="category_id" required>
                                    <option value="">請選擇主分類</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">子分類</label>
                                <select class="form-control" name="subcategory_id" required>
                                    <option value="">請選擇子分類</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">價格</label>
                                <input type="number" class="form-control" name="price" value="${product.price}" min="0" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">庫存</label>
                                <input type="number" class="form-control" name="stock" value="${product.stock}" min="0" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">商品描述</label>
                                <textarea class="form-control" name="description" rows="3">${product.description || ''}</textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">商品主圖</label>
                                <input type="file" class="form-control" name="main_image" accept="image/*">
                                <div id="mainImagePreview" class="mt-2">
                                    ${product.main_image ? `
                                        <img src="/CampExplorer/uploads/products/main/${product.main_image}" 
                                             class="img-thumbnail" style="max-width: 200px; height: 150px; object-fit: cover;">
                                    ` : ''}
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">商品圖片集</label>
                                <input type="file" class="form-control" name="gallery_images[]" accept="image/*" multiple>
                                <small class="text-muted">可以一次選擇多張圖片</small>
                                <div id="galleryPreview" class="mt-2 row"></div>
                            </div>
                        </form>
                    `,
                    didOpen: async () => {
                        const form = document.getElementById('editProductForm');
                        this.setupImagePreviews(form);
                        await this.loadCategories(form, product);
                    },
                    preConfirm: async () => {
                        const form = document.getElementById('editProductForm');
                        const formData = new FormData(form);
                        
                        const productData = {
                            id: product.id,
                            name: formData.get('name'),
                            category_id: parseInt(formData.get('category_id')),
                            subcategory_id: parseInt(formData.get('subcategory_id')),
                            price: parseFloat(formData.get('price')),
                            stock: parseInt(formData.get('stock')),
                            description: formData.get('description') || '',
                            status: product.status
                        };

                        // 驗證和處理圖片上傳
                        return await this.handleProductSubmit(formData, productData);
                    },
                    showCancelButton: true,
                    confirmButtonText: '儲存',
                    cancelButtonText: '取消',
                    width: '80%'
                });

                if (formResult.isConfirmed && formResult.value) {
                    const response = await fetch('/CampExplorer/admin/api/products/update.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify(formResult.value)
                    });

                    const result = await response.json();
                    if (result.success) {
                        await Swal.fire('成功', '商品更新成功', 'success');
                        location.reload();
                    } else {
                        throw new Error(result.message);
                    }
                }
            } catch (error) {
                console.error('Error:', error);
                Swal.fire('錯誤', error.message, 'error');
            }
        },

        async loadCategories(form, product = null) {
            const categorySelect = form.querySelector('select[name="category_id"]');
            const subcategorySelect = form.querySelector('select[name="subcategory_id"]');
            
            try {
                const response = await fetch('/CampExplorer/admin/api/categories/read.php');
                const data = await response.json();
                if (data.success) {
                    categorySelect.innerHTML = '<option value="">請選擇主分類</option>' +
                        data.data.map(category =>
                            `<option value="${category.id}" ${product && category.id == product.category_id ? 'selected' : ''}>
                                ${category.name}
                            </option>`
                        ).join('');
                    
                    if (product?.category_id) {
                        await this.loadSubcategories(product.category_id, subcategorySelect, product.subcategory_id);
                    }
                }
            } catch (error) {
                console.error('載入分類失敗:', error);
            }

            categorySelect.addEventListener('change', async () => {
                const categoryId = categorySelect.value;
                await this.loadSubcategories(categoryId, subcategorySelect);
            });
        },

        async loadSubcategories(categoryId, subcategorySelect, selectedId = null) {
            subcategorySelect.innerHTML = '<option value="">請選擇子分類</option>';
            
            if (categoryId) {
                try {
                    const response = await fetch(`/CampExplorer/admin/api/categories/read.php?action=subcategory&category_id=${categoryId}`);
                    const data = await response.json();
                    if (data.success) {
                        subcategorySelect.innerHTML += data.data.map(subcategory =>
                            `<option value="${subcategory.id}" ${selectedId && subcategory.id == selectedId ? 'selected' : ''}>
                                ${subcategory.name}
                            </option>`
                        ).join('');
                    }
                } catch (error) {
                    console.error('載入子分類失敗:', error);
                }
            }
        },

        async handleProductSubmit(formData, productData) {
            // 驗證必填欄位
            if (!productData.name || !productData.category_id ||
                !productData.subcategory_id || !productData.price ||
                !productData.stock) {
                Swal.showValidationMessage('請填寫所有必填欄位');
                return false;
            }

            // 處理主圖上傳
            const mainImageFile = formData.get('main_image');
            if (mainImageFile?.size > 0) {
                try {
                    const mainImageResult = await uploadImage(mainImageFile, 'main');
                    if (mainImageResult.success) {
                        productData.main_image = mainImageResult.files[0];
                    }
                } catch (error) {
                    Swal.showValidationMessage('主圖上傳失敗：' + error.message);
                    return false;
                }
            }

            // 處理圖片集上傳
            const galleryFiles = formData.getAll('gallery_images[]');
            if (galleryFiles.some(file => file.size > 0)) {
                try {
                    const galleryResult = await uploadImage(galleryFiles, 'gallery');
                    if (galleryResult.success) {
                        productData.gallery_images = galleryResult.files;
                    }
                } catch (error) {
                    Swal.showValidationMessage('圖片集上傳失敗：' + error.message);
                    return false;
                }
            }

            return productData;
        },

        async handleAddProduct() {
            try {
                const formResult = await Swal.fire({
                    title: '新增商品',
                    html: `
                        <form id="addProductForm">
                            <div class="mb-3">
                                <label class="form-label">商品名稱</label>
                                <input type="text" class="form-control" name="name" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">主分類</label>
                                <select class="form-control" name="category_id" required>
                                    <option value="">請選擇主分類</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">子分類</label>
                                <select class="form-control" name="subcategory_id" required>
                                    <option value="">請選擇子分類</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">價格</label>
                                <input type="number" class="form-control" name="price" min="0" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">庫存</label>
                                <input type="number" class="form-control" name="stock" min="0" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">商品描述</label>
                                <textarea class="form-control" name="description" rows="3"></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">商品主圖</label>
                                <input type="file" class="form-control" name="main_image" accept="image/*">
                                <div id="mainImagePreview" class="mt-2"></div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">商品圖片集</label>
                                <input type="file" class="form-control" name="gallery_images[]" accept="image/*" multiple>
                                <small class="text-muted">可以一次選擇多張圖片</small>
                                <div id="galleryPreview" class="mt-2 row"></div>
                            </div>
                        </form>
                    `,
                    didOpen: async () => {
                        const form = document.getElementById('addProductForm');
                        this.setupImagePreviews(form);
                        await this.loadCategories(form);
                    },
                    preConfirm: async () => {
                        const form = document.getElementById('addProductForm');
                        const formData = new FormData(form);
                        
                        const productData = {
                            name: formData.get('name'),
                            category_id: parseInt(formData.get('category_id')),
                            subcategory_id: parseInt(formData.get('subcategory_id')),
                            price: parseFloat(formData.get('price')),
                            stock: parseInt(formData.get('stock')),
                            description: formData.get('description') || ''
                        };

                        return await this.handleProductSubmit(formData, productData);
                    },
                    showCancelButton: true,
                    confirmButtonText: '新增',
                    cancelButtonText: '取消',
                    width: '80%'
                });

                if (formResult.isConfirmed && formResult.value) {
                    const response = await fetch('/CampExplorer/admin/api/products/create.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify(formResult.value)
                    });

                    const result = await response.json();
                    if (result.success) {
                        await Swal.fire('成功', '商品新增成功', 'success');
                        location.reload();
                    } else {
                        throw new Error(result.message);
                    }
                }
            } catch (error) {
                console.error('Error:', error);
                Swal.fire('錯誤', error.message, 'error');
            }
        },

        async handleToggleStatus(id, currentStatus) {
            try {
                const result = await Swal.fire({
                    title: `確定要${currentStatus ? '停用' : '啟用'}此商品？`,
                    text: `商品將被${currentStatus ? '停用' : '啟用'}`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: `確定${currentStatus ? '停用' : '啟用'}`,
                    cancelButtonText: '取消',
                    confirmButtonColor: currentStatus ? '#dc3545' : '#198754'
                });

                if (result.isConfirmed) {
                    const response = await fetch('/CampExplorer/admin/api/products/delete.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            id: id,
                            status: currentStatus ? 0 : 1
                        })
                    });

                    const data = await response.json();
                    if (data.success) {
                        await Swal.fire({
                            title: '成功',
                            text: data.message,
                            icon: 'success',
                            confirmButtonColor: '#198754'
                        });
                        location.reload();
                    } else {
                        throw new Error(data.message);
                    }
                }
            } catch (error) {
                console.error('Error:', error);
                Swal.fire('錯誤', error.message, 'error');
            }
        }
    };

    // 初始化
    document.addEventListener('DOMContentLoaded', () => {
        ProductUI.init();
    });

    // 新增上傳圖片的輔助函數
    async function uploadImage(file, type = 'main') {
        const formData = new FormData();
        
        if (type === 'main') {
            formData.append('image', file);
        } else {
            // 處理多圖片上傳
            if (file instanceof FileList) {
                Array.from(file).forEach(f => {
                    formData.append('images[]', f);
                });
            } else if (Array.isArray(file)) {
                file.forEach(f => {
                    formData.append('images[]', f);
                });
            } else {
                formData.append('images[]', file);
            }
        }
        formData.append('type', type);

        const response = await fetch('/CampExplorer/admin/api/products/upload.php', {
            method: 'POST',
            body: formData
        });

        if (!response.ok) {
            throw new Error('圖片上傳失敗');
        }

        return await response.json();
    }
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
</style>