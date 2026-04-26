<?php 
include("config.php");
include("functions.php");

$is_admin = 0;
if(isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $admin_query = mysqli_query($con, "SELECT is_admin FROM user WHERE id = '$user_id'");
    if(mysqli_num_rows($admin_query) > 0) {
        $admin_data = mysqli_fetch_assoc($admin_query);
        $is_admin = $admin_data['is_admin'];
    }
}

// Получаем параметры фильтрации
$category = isset($_GET['category']) ? mysqli_real_escape_string($con, $_GET['category']) : '';
$search = isset($_GET['search']) ? mysqli_real_escape_string($con, $_GET['search']) : '';
$price_min = isset($_GET['price_min']) ? (float)$_GET['price_min'] : 0;
$price_max = isset($_GET['price_max']) ? (float)$_GET['price_max'] : 0;
$sort = isset($_GET['sort']) ? mysqli_real_escape_string($con, $_GET['sort']) : '';

$sql = "SELECT s.id, t.name AS type_name, c.name AS company_name, s.name, s.time, s.price, p.link 
        FROM subscribes s 
        JOIN photo_catalog pc ON pc.subscribes_id = s.id 
        JOIN photo p ON pc.photo_id = p.id 
        JOIN type t ON t.id = s.type_id 
        JOIN company c ON c.id = s.company_id
        WHERE 1=1";

// Добавляем условия фильтрации
if (!empty($category)) {
    $sql .= " AND t.name = '$category'";
}
if (!empty($search)) {
    $sql .= " AND (s.name LIKE '%$search%' OR c.name LIKE '%$search%')";
}
if ($price_min > 0) {
    $sql .= " AND s.price >= $price_min";
}
if ($price_max > 0 && $price_max >= $price_min) {
    $sql .= " AND s.price <= $price_max";
}

// Добавляем сортировку
switch ($sort) {
    case 'price_asc':
        $sql .= " ORDER BY s.price ASC";
        break;
    case 'price_desc':
        $sql .= " ORDER BY s.price DESC";
        break;
    case 'name_asc':
        $sql .= " ORDER BY s.name ASC";
        break;
    case 'name_desc':
        $sql .= " ORDER BY s.name DESC";
        break;
    default:
        $sql .= " ORDER BY s.id DESC";
}

$query = mysqli_query($con, $sql);

// Получаем данные корзины
$items = 0;
$total = 0;
if(isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $cart_query = mysqli_query($con, "
        SELECT COUNT(ca.id) as items, SUM(s.price) as total 
        FROM cart ca 
        JOIN subscribes s ON ca.subscribes_id = s.id 
        JOIN `order` o ON ca.order_id = o.id 
        WHERE o.user_id = '$user_id' AND o.`check` = 0
    ");
    
    if($cart_query && mysqli_num_rows($cart_query) > 0) {
        $cart_data = mysqli_fetch_assoc($cart_query);
        $items = $cart_data['items'] ?? 0;
        $total = $cart_data['total'] ?? 0;
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BearPass - Подписки и цифровые товары</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="shell">
        <!-- Header -->
        <div id="header">
            <div class="header-content">
                <div class="mobile-menu-toggle">☰</div>
                <div class="logo">
                    <img src="images/logo.png" alt="BearPass Logo" class="logo-img">
                    <span class="logo-text">BearPass</span>
                </div>
                <nav id="navigation">
                    <ul>
                        <li><a href="?" class="<?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>">Главная</a></li>
                        <?php if(isset($_SESSION['user_id'])): ?>
                            <li><a href="order_details.php">Мой аккаунт</a></li>
                            <li><a href="logout.php">Выйти</a></li>
                            <?php if($is_admin == 1): ?>
                                <li><a href="admin_subscribes.php">Админ-панель</a></li>
                            <?php endif; ?>
                        <?php else: ?>
                            <li><a href="login.php">Войти</a></li>
                            <li><a href="register.php">Регистрация</a></li>
                        <?php endif; ?>
                    </ul>
                </nav>
                <div id="cart">
                    <a href="cart.php" class="cart-link">Корзина</a>
                    <div class="cart-info">
                        <span>Товаров: <strong><?php echo $items; ?></strong></span>
                        <span>Сумма: <strong><?php echo number_format($total, 2); ?> руб.</strong></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="main-container">
            
            <div id="sidebar">
                <div class="search-box">
                    <h2>Поиск товаров</h2>
                    <form action="" method="get">
                        <input type="text" class="search-field" name="search" placeholder="Название игры или сервиса" value="<?php echo htmlspecialchars($search); ?>">
                        
                        <select class="search-field" name="category">
                            <option value="">Все категории</option>
                            <option value="Музыка" <?php echo $category == 'Музыка' ? 'selected' : ''; ?>>Музыка</option>
                            <option value="Игры" <?php echo $category == 'Игры' ? 'selected' : ''; ?>>Игры</option>
                            <option value="Спорт" <?php echo $category == 'Спорт' ? 'selected' : ''; ?>>Спорт</option>
                            <option value="Новости" <?php echo $category == 'Новости' ? 'selected' : ''; ?>>Новости</option>
                            <option value="Кино" <?php echo $category == 'Кино' ? 'selected' : ''; ?>>Кино</option>
                            <option value="Соц-сети" <?php echo $category == 'Соц-сети' ? 'selected' : ''; ?>>Соц-сети</option>
                        </select>
                        
                        <div class="price-filter">
                            <input type="number" name="price_min" placeholder="От" min="0" value="<?php echo $price_min > 0 ? $price_min : ''; ?>">
                            <input type="number" name="price_max" placeholder="До" min="0" value="<?php echo $price_max > 0 ? $price_max : ''; ?>">
                        </div>
                        
                        <button type="submit" class="buy-btn">Найти</button>
                        <a href="?" class="reset-btn">Сбросить фильтры</a>
                    </form>
                </div>
                
                <div class="categories-box">
                    <h2>Категории</h2>
                    <ul class="categories-list">
                        <li><a href="?<?php echo buildQueryString(['category' => 'Музыка']); ?>" class="<?php echo $category == 'Музыка' ? 'active' : ''; ?>">Музыка</a></li>
                        <li><a href="?<?php echo buildQueryString(['category' => 'Игры']); ?>" class="<?php echo $category == 'Игры' ? 'active' : ''; ?>">Игры</a></li>
                        <li><a href="?<?php echo buildQueryString(['category' => 'Спорт']); ?>" class="<?php echo $category == 'Спорт' ? 'active' : ''; ?>">Спорт</a></li>
                        <li><a href="?<?php echo buildQueryString(['category' => 'Новости']); ?>" class="<?php echo $category == 'Новости' ? 'active' : ''; ?>">Новости</a></li>
                        <li><a href="?<?php echo buildQueryString(['category' => 'Кино']); ?>" class="<?php echo $category == 'Кино' ? 'active' : ''; ?>">Кино</a></li>
                        <li><a href="?<?php echo buildQueryString(['category' => 'Соц-сети']); ?>" class="<?php echo $category == 'Соц-сети' ? 'active' : ''; ?>">Соц-сети</a></li>
                    </ul>
                </div>
            </div>

           
            <main id="main">
                <div id="content">
                    <div class="sorting-options">
                        <span>Сортировка:</span>
                        <a href="?<?php echo buildQueryString(['sort' => 'price_asc']); ?>" class="<?php echo $sort == 'price_asc' ? 'active' : ''; ?>">Цена ↑</a>
                        <a href="?<?php echo buildQueryString(['sort' => 'price_desc']); ?>" class="<?php echo $sort == 'price_desc' ? 'active' : ''; ?>">Цена ↓</a>
                        <a href="?<?php echo buildQueryString(['sort' => 'name_asc']); ?>" class="<?php echo $sort == 'name_asc' ? 'active' : ''; ?>">Название А-Я</a>
                        <a href="?<?php echo buildQueryString(['sort' => 'name_desc']); ?>" class="<?php echo $sort == 'name_desc' ? 'active' : ''; ?>">Название Я-А</a>
                    </div>
                    
                    <div class="products-grid">
                    <?php 
                    if(mysqli_num_rows($query) > 0) {
                        while($product = mysqli_fetch_array($query)): 
                            $discount = rand(5, 15);
                    ?>
                    <div class="product-card">
                        <a href="product.php?open_prod=<?php echo $product[0]; ?>">
                            <img src="images/<?php echo $product[6]; ?>" alt="<?php echo $product[3]; ?>">
                        </a>
                        <div class="product-header">
                            <div class="product-title"><?php echo $product[3]; ?></div>
                            <span class="discount-badge">-<?php echo $discount; ?>%</span>
                        </div>
                        <div class="product-content">
                            <div class="product-description">
                                <?php echo $product[1]; ?> - <?php echo $product[2]; ?>
                            </div>
                            <div class="product-price">
                                <span class="current-price"><?php echo $product[5]; ?></span> ₽
                                <span class="original-price"><?php echo round($product[5] * (1 + $discount/100)); ?> ₽</span>
                            </div>
                            <div class="product-sales"><?php echo rand(100, 5000); ?>+ продаж</div>
                            <?php if(isset($_SESSION['user_id'])): ?>
                                <a href="cart.php?buy=<?php echo $product[0]; ?>" class="buy-btn">Купить</a>
                            <?php else: ?>
                                <a href="login.php" class="buy-btn">Войти для покупки</a>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php 
                        endwhile;
                    } else {
                        echo '<div class="no-results">Товары не найдены. Попробуйте изменить параметры поиска.</div>';
                    }
                    ?>
                    </div>
                </div>
            </main>
        </div>

        <!-- Footer -->
        <footer id="footer">
            <div class="footer-content">
                <p>© <?php echo date('Y'); ?> BearPass. Все права защищены.</p>
                <div class="footer-links">
                    <a href="#">Пользовательское соглашение</a>
                    <a href="#">Политика конфиденциальности</a>
                    <a href="#">Возврат средств</a>
                    <a href="#">Техническая поддержка</a>
                </div>
            </div>
        </footer>
    </div>

    
</body>
</html>