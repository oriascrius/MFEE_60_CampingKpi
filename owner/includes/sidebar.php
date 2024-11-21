<style>
    .sidebar {
        background: linear-gradient(180deg, #1a2236 0%, #2c3e50 100%);
        color: #ecf0f1;
        min-height: 100vh;
        padding: 1rem 0;
    }
    
    .nav-link {
        color: #a8b2c1 !important;
        padding: 0.8rem 1rem;
        transition: all 0.3s ease;
    }
    
    .nav-link:hover {
        color: #fff !important;
        background: rgba(255, 255, 255, 0.1);
    }
    
    .nav-link.active {
        color: #fff !important;
        background: rgba(52, 152, 219, 0.2);
    }
    
    .sub-menu {
        display: none;
        padding-left: 2rem;
        background: rgba(0, 0, 0, 0.1);
        border-left: 2px solid rgba(52, 152, 219, 0.5);
    }
    
    .sub-menu.show {
        display: block;
    }
    
    .menu-toggle {
        display: flex;
        justify-content: space-between;
        align-items: center;
        cursor: pointer;
    }
    
    .toggle-icon {
        transition: transform 0.3s;
    }
    
    .menu-toggle.active .toggle-icon {
        transform: rotate(180deg);
    }
</style>

<div class="col-md-3 col-lg-2 px-0 position-fixed sidebar">
    <div class="text-center p-3 mb-3">
        <h4>營主後台管理系統</h4>
    </div>
    <nav class="nav flex-column">
        <!-- 營地申請 -->
        <a href="/CampExplorer/owner/camp/camp-add.php" class="nav-link">
            <i class="bi bi-file-earmark-text me-2"></i>營地申請
        </a>

        <!-- 營地狀態 -->
        <a href="/CampExplorer/owner/campStatus/camp-status.php" class="nav-link">
            <i class="bi bi-clipboard-check me-2"></i>營地狀態
        </a>

        <!-- 營位管理 -->
        <a href="/CampExplorer/owner/spot/spot-list.php" class="nav-link">
            <i class="bi bi-geo-alt me-2"></i>營位管理
        </a>

        <!-- 訂單管理 -->
        <a href="/CampExplorer/owner/order/order-list.php" class="nav-link">
            <i class="bi bi-receipt me-2"></i>訂單管理
        </a>

        <!-- 登出按鈕 -->
        <a href="/CampExplorer/owner/logout.php" class="nav-link text-danger mt-3">
            <i class="bi bi-box-arrow-right me-2"></i>登出系統
        </a>
    </nav>
</div>

<script>
function toggleMenu(menuId, toggleElement) {
    const subMenu = document.getElementById(menuId);
    const isVisible = subMenu.classList.contains('show');

    // 隱藏所有子選單
    document.querySelectorAll('.sub-menu').forEach(menu => {
        menu.classList.remove('show');
        menu.previousElementSibling.classList.remove('active');
    });

    // 切換當前選單的顯示狀態
    if (!isVisible) {
        subMenu.classList.add('show');
        toggleElement.classList.add('active');
    }
}
</script>