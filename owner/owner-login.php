<?php
session_start();
require_once __DIR__ . '/../camping_db.php';

// 使用 camping_db.php 中已建立的連接
$conn = $db;

// 如果已經登入，重定向到營主後台
if (isset($_SESSION['owner_id'])) {
    header("Location: dashboard.php");
    exit;
}

// AJAX 請求處理
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
    header('Content-Type: application/json');

    try {
        // 處理註冊請求
        if (isset($_POST['action']) && $_POST['action'] === 'register') {
            $email = trim($_POST['email'] ?? '');
            $password = trim($_POST['password'] ?? '');
            $confirm_password = trim($_POST['confirm_password'] ?? '');
            $name = trim($_POST['name'] ?? '');
            $company_name = trim($_POST['company_name'] ?? '');
            $phone = trim($_POST['phone'] ?? '');
            $address = trim($_POST['address'] ?? '');

            // 基本驗證
            if (empty($email) || empty($password) || empty($name) || empty($company_name)) {
                throw new Exception('填欄位不能為空');
            }

            try {
                // 開始交易
                $conn->beginTransaction();

                // 檢查信箱是否已存在
                $stmt = $conn->prepare("SELECT COUNT(*) FROM owners WHERE email = ?");
                $stmt->execute([$email]);
                if ($stmt->fetchColumn() > 0) {
                    throw new Exception('此信箱已被註冊');
                }

                // 新增營主資料
                $stmt = $conn->prepare("INSERT INTO owners (email, password, name, company_name, phone, address, status) VALUES (?, ?, ?, ?, ?, ?, 1)");

                // 記錄 SQL 執行前的資料
                error_log("Attempting to insert owner with email: " . $email);

                $result = $stmt->execute([
                    $email,
                    $password,
                    $name,
                    $company_name,
                    $phone,
                    $address
                ]);


                if (!$result) {
                    error_log("Insert failed. Error info: " . json_encode($stmt->errorInfo()));
                    throw new Exception('註冊失敗，請稍後再試');
                }

                // 提交交易
                $conn->commit();
                error_log("Transaction committed successfully for email: " . $email);

                // 返回成功響應
                echo json_encode([
                    'success' => true,
                    'message' => '註冊成功！'
                ]);
                return;
            } catch (Exception $e) {
                $conn->rollBack();
                error_log("Registration error: " . $e->getMessage());
                throw $e;
            }
        }

        // 處理登入請求
        if (isset($_POST['action']) && $_POST['action'] === 'login') {
            $email = $_POST['email'] ?? '';
            $password = $_POST['password'] ?? '';

            if (empty($email) || empty($password)) {
                throw new Exception('請輸入信箱和密碼');
            }

            // 檢查帳號狀態
            $stmt = $conn->prepare("SELECT * FROM owners WHERE email = ? AND status = 1");
            $stmt->execute([$email]);
            $owner = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$owner) {
                error_log("Login failed: No user found with email: " . $email);
                throw new Exception('信箱或密碼錯誤');
            }

            if ($password === $owner['password']) {
                // 設置 session
                $_SESSION['owner_id'] = $owner['id'];
                $_SESSION['owner_name'] = $owner['name'];
                $_SESSION['owner_email'] = $owner['email'];
                $_SESSION['owner_company'] = $owner['company_name'];

                echo json_encode([
                    'success' => true,
                    'message' => '登入成功！',
                    'redirect' => 'dashboard.php'
                ]);
            } else {
                throw new Exception('信箱或密碼錯誤');
            }
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// 在頁面頂部生成 CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>

<!DOCTYPE html>
<html lang="zh-TW">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>營主專區 - 露營趣</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body {
            min-height: 100vh;
            background: linear-gradient(135deg, #1a2a6c, #b21f1f, #fdbb2d);
            background-size: 400% 400%;
            animation: gradient 15s ease infinite;
            font-family: 'Segoe UI', 'Microsoft JhengHei', sans-serif;
        }

        @keyframes gradient {
            0% {
                background-position: 0% 50%;
            }

            50% {
                background-position: 100% 50%;
            }

            100% {
                background-position: 0% 50%;
            }
        }

        .auth-container {
            min-height: 100vh;
            display: flex;
            padding: 2rem;
        }

        .auth-content {
            width: 100%;
            max-width: 500px;
            margin: auto;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 2.5rem;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
        }

        .auth-header {
            text-align: center;
            margin-bottom: 2.5rem;
        }

        .auth-header h2 {
            color: #2c3e50;
            font-size: 2.2rem;
            font-weight: bold;
            margin-bottom: 0.5rem;
        }

        .auth-header p {
            color: #666;
            font-size: 1.1rem;
        }

        .auth-tabs {
            display: flex;
            justify-content: center;
            gap: 1.5rem;
            margin-bottom: 2.5rem;
        }

        .auth-tab {
            font-size: 1.1rem;
            padding: 0.8rem 2rem;
            border: none;
            background: rgba(255, 255, 255, 0.1);
            color: #666;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .auth-tab:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.15);
        }

        .auth-tab.active {
            background: linear-gradient(135deg, #3498db, #2980b9);
            color: white;
            font-weight: 500;
        }

        .form-floating {
            margin-bottom: 1.5rem;
        }

        .form-floating:last-of-type {
            margin-bottom: 2rem;
        }

        .form-floating>.form-control {
            border-radius: 10px;
            border: 1px solid #dee2e6;
            padding: 1rem 0.75rem;
        }

        .form-floating>.form-control:focus {
            border-color: #2c3e50;
            box-shadow: 0 0 0 0.25rem rgba(44, 62, 80, 0.25);
        }

        .btn-auth {
            width: 100%;
            padding: 1rem;
            font-size: 1.1rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
            background: linear-gradient(135deg, #3498db, #2980b9);
            border: none;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(52, 152, 219, 0.2);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .btn-auth:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(52, 152, 219, 0.3);
            background: linear-gradient(135deg, #2980b9, #3498db);
        }

        .btn-auth:active {
            transform: translateY(1px);
        }

        .btn-auth i {
            font-size: 1.2rem;
            margin-right: 8px;
            transition: transform 0.3s ease;
        }

        .btn-auth:hover i {
            transform: translateX(3px);
        }

        .form-text {
            color: #666;
            font-size: 0.9rem;
            margin-top: 0.25rem;
        }

        .back-to-home {
            position: fixed;
            top: 20px;
            left: 20px;
            color: white;
            text-decoration: none;
            padding: 10px 20px;
            border-radius: 25px;
            background: rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(5px);
            transition: all 0.3s ease;
        }

        .back-to-home:hover {
            background: rgba(255, 255, 255, 0.3);
            color: white;
        }

        @media (max-width: 576px) {
            .auth-container {
                padding: 1rem;
            }

            .auth-content {
                padding: 1.5rem;
            }

            .auth-header h2 {
                font-size: 1.8rem;
            }
        }

        /* 表單容器樣式 */
        .forms-container {
            position: relative;
            height: auto;
            overflow: hidden;
        }

        /* 表單樣式 */
        .auth-form {
            display: none;
            /* 預設隱藏所有表單 */
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .auth-form.active {
            display: block;
            opacity: 1;
        }

        /* 標籤切換樣式 */
        .auth-tab {
            position: relative;
            overflow: hidden;
            z-index: 1;
        }

        .auth-tab::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: #2c3e50;
            border-radius: 8px;
            transform: translateX(-100%);
            transition: transform 0.3s ease;
            z-index: -1;
        }

        .auth-tab:hover::before {
            transform: translateX(0);
        }

        .auth-tab.active::before {
            transform: translateX(0);
        }

        /* 輸入框動畫 */
        .form-floating input {
            transition: all 0.3s ease;
        }

        .form-floating input:focus {
            transform: translateY(-2px);
        }

        /* 按鈕動畫 */
        .btn-auth {
            position: relative;
            overflow: hidden;
            z-index: 1;
        }

        .btn-auth::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(45deg, #2c3e50, #3498db);
            opacity: 0;
            transition: opacity 0.3s ease;
            z-index: -1;
        }

        .btn-auth:hover::before {
            opacity: 1;
        }

        /* 表單和按鈕間距優化 */
        .form-floating {
            margin-bottom: 1.5rem;
        }

        .form-floating:last-of-type {
            margin-bottom: 2rem;
        }

        /* 登入表單特別處理 */
        #login-form .form-floating:last-of-type {
            margin-bottom: 2.5rem;
        }

        /* 按鈕樣式優化 */
        .btn-auth {
            width: 100%;
            padding: 1rem;
            border-radius: 10px;
            background: #2c3e50;
            border: none;
            font-weight: 500;
            font-size: 1.1rem;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        /* 表單分組樣式 */
        .mb-3 {
            margin-bottom: 1.5rem !important;
        }

        .mb-4 {
            margin-bottom: 2rem !important;
        }

        /* 加載動畫 */
        .spin {
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            from {
                transform: rotate(0deg);
            }

            to {
                transform: rotate(360deg);
            }
        }

        .auth-title {
            color: #fff;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .text-white-50 {
            font-size: 0.95rem;
            letter-spacing: 1px;
        }

        .welcome-message {
            margin-top: 1rem;
        }

        .welcome-message p {
            color: #1a2a6c;
            margin: 0;
            font-size: 1.1rem;
            line-height: 1.6;
            text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.2);
        }

        .welcome-message .sub-text {
            color: #1a2a6c;
            font-size: 0.95rem;
            margin-top: 8px;
            letter-spacing: 0.5px;
        }

        .auth-title {
            font-size: 2.2rem;
            font-weight: 700;
            color: #fff;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.2);
            margin-bottom: 1.2rem;
        }
    </style>
</head>

<body>
    <a href="../portal.php" class="back-to-home">
        <i class="bi bi-arrow-left me-2"></i>返回首頁
    </a>

    <div class="auth-container">
        <div class="auth-content">
            <div class="auth-header">
                <div class="text-center mb-4">
                    <h2 class="auth-title">營主專區</h2>
                    <div class="welcome-message">
                        <p>加入露營趣大家庭，一同打造最棒的露營體驗！</p>
                        <p class="sub-text">專業營地管理系統，助您輕鬆經營</p>
                    </div>
                </div>
                <div class="auth-tabs">
                    <button type="button" class="auth-tab active" data-form="login">
                        <i class="bi bi-box-arrow-in-right me-2"></i>登入
                    </button>
                    <button type="button" class="auth-tab" data-form="register">
                        <i class="bi bi-person-plus me-2"></i>註冊
                    </button>
                </div>
            </div>

            <div class="forms-container">
                <!-- 登入表單 -->
                <form method="POST" class="auth-form active" id="login-form">
                    <input type="hidden" name="action" value="login">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    <div class="form-floating">
                        <input type="email"
                            class="form-control"
                            id="login-email"
                            name="email"
                            autocomplete="email"
                            required>
                        <label for="login-email">
                            <i class="bi bi-envelope me-2"></i>電子信箱
                        </label>
                    </div>

                    <div class="form-floating mb-3">
                        <input type="password"
                            class="form-control"
                            id="login-password"
                            name="password"
                            placeholder="密碼"
                            autocomplete="current-password"
                            required>
                        <label for="login-password">
                            <i class="bi bi-lock me-2"></i>密碼
                        </label>
                    </div>

                    <button type="submit" class="btn btn-primary btn-auth">
                        <i class="bi bi-box-arrow-in-right me-2"></i>登入系統
                    </button>
                </form>

                <!-- 註冊表單 -->
                <form id="register-form" class="auth-form" method="POST">
                    <input type="hidden" name="action" value="register">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

                    <div class="form-floating mb-3">
                        <input type="email"
                            class="form-control"
                            id="register-email"
                            name="email"
                            autocomplete="email"
                            required>
                        <label for="register-email">
                            <i class="bi bi-envelope me-2"></i>電子郵件
                        </label>
                    </div>

                    <div class="form-floating mb-3">
                        <input type="text"
                            class="form-control"
                            id="register-name"
                            name="name"
                            autocomplete="name"
                            required>
                        <label for="register-name">
                            <i class="bi bi-person me-2"></i>營主姓
                        </label>
                    </div>

                    <div class="form-floating mb-3">
                        <input type="text"
                            class="form-control"
                            id="register-company"
                            name="company_name"
                            autocomplete="organization"
                            required>
                        <label for="register-company">
                            <i class="bi bi-building me-2"></i>公司名稱
                        </label>
                    </div>

                    <div class="form-floating mb-3">
                        <input type="password"
                            class="form-control"
                            id="register-password"
                            name="password"
                            placeholder="密碼"
                            autocomplete="new-password"
                            required>
                        <label for="register-password">
                            <i class="bi bi-lock me-2"></i>密碼
                        </label>
                    </div>

                    <div class="form-floating mb-3">
                        <input type="password"
                            class="form-control"
                            id="register-confirm-password"
                            name="confirm_password"
                            placeholder="確認密碼"
                            autocomplete="new-password"
                            required>
                        <label for="register-confirm-password">
                            <i class="bi bi-lock-fill me-2"></i>確認密碼
                        </label>
                    </div>

                    <div class="form-floating mb-3">
                        <input type="tel"
                            class="form-control"
                            id="register-phone"
                            name="phone"
                            autocomplete="tel">
                        <label for="register-phone">
                            <i class="bi bi-phone me-2"></i>聯絡電話
                        </label>
                    </div>

                    <div class="form-floating mb-3">
                        <input type="text"
                            class="form-control"
                            id="register-address"
                            name="address"
                            autocomplete="street-address">
                        <label for="register-address">
                            <i class="bi bi-geo-alt me-2"></i>地址
                        </label>
                    </div>

                    <button type="submit" class="btn btn-primary btn-auth">
                        <i class="bi bi-person-plus me-2"></i>註冊帳號
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const switchForm = (formType) => {
                // 隱藏所有表單
                document.querySelectorAll('.auth-form').forEach(form => {
                    form.style.display = 'none';
                    form.classList.remove('active');
                });

                // 顯目標表單
                const targetForm = document.getElementById(`${formType}-form`);
                targetForm.style.display = 'block';
                setTimeout(() => {
                    targetForm.classList.add('active');
                }, 10);

                // 更新標籤狀態
                document.querySelectorAll('.auth-tab').forEach(tab => {
                    tab.classList.toggle('active', tab.dataset.form === formType);
                });
            };

            // 綁定標籤點擊事件
            document.querySelectorAll('.auth-tab').forEach(tab => {
                tab.addEventListener('click', () => switchForm(tab.dataset.form));
            });

            // 表單提交處理
            ['login', 'register'].forEach(formType => {
                document.getElementById(`${formType}-form`).addEventListener('submit', async function(e) {
                    e.preventDefault();

                    // 添加提交動畫
                    const submitBtn = this.querySelector('.btn-auth');
                    submitBtn.disabled = true;
                    submitBtn.innerHTML = '<i class="bi bi-arrow-repeat spin me-2"></i>處理中...';

                    const formData = new FormData(this);

                    try {
                        const response = await fetch('owner-login.php', {
                            method: 'POST',
                            body: formData,
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest'
                            }
                        });

                        const data = await response.json();

                        if (data.success) {
                            await Swal.fire({
                                icon: 'success',
                                title: '成功',
                                text: data.message,
                                showConfirmButton: false,
                                timer: 1500
                            });

                            if (data.redirect) {
                                window.location.href = data.redirect;
                            } else {
                                window.location.reload();
                            }
                        } else {
                            await Swal.fire({
                                icon: 'error',
                                title: '錯誤',
                                text: data.message
                            });
                        }
                    } catch (error) {
                        console.error('Error:', error);
                        await Swal.fire({
                            icon: 'error',
                            title: '系統錯誤',
                            text: '請稍後再試'
                        });
                    } finally {
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = formType === 'login' ?
                            '<i class="bi bi-box-arrow-in-right me-2"></i>登入系統' :
                            '<i class="bi bi-person-plus me-2"></i>註冊帳號';
                    }
                });
            });

            // 密碼強度檢查
            function checkPasswordStrength(password) {
                let strength = 0;
                const feedback = [];

                if (password.length < 8) {
                    feedback.push('密碼長度至少需要8個字符');
                } else {
                    strength += 1;
                }

                if (password.match(/[A-Z]/)) strength += 1;
                if (password.match(/[a-z]/)) strength += 1;
                if (password.match(/[0-9]/)) strength += 1;

                if (strength < 4) {
                    feedback.push('碼必須包含大小寫字母和數字');
                }

                return {
                    valid: strength === 4,
                    feedback: feedback.join('、')
                };
            }

            // 即時密碼驗證
            const passwordInput = document.getElementById('register-password');
            const confirmInput = document.getElementById('register-confirm-password');

            passwordInput?.addEventListener('input', function() {
                const result = checkPasswordStrength(this.value);
                this.setCustomValidity(result.valid ? '' : result.feedback);
                this.reportValidity();
            });

            confirmInput?.addEventListener('input', function() {
                const password = passwordInput.value;
                if (this.value !== password) {
                    this.setCustomValidity('密碼不一致');
                } else {
                    this.setCustomValidity('');
                }
                this.reportValidity();
            });

            // Email 格式驗證
            const emailInput = document.getElementById('register-email');
            emailInput?.addEventListener('input', function() {
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (!emailRegex.test(this.value)) {
                    this.setCustomValidity('請輸入有效的電子郵件地址');
                } else {
                    this.setCustomValidity('');
                }
                this.reportValidity();
            });
        });
    </script>
</body>

</html>