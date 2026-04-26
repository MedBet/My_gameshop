<div id="header">
    
    <div class="header-content">
        <div class="logo">
            <img src="images/logo.png" alt="BearPass Logo" class="logo-img">
            BearPass
        </div>
        <div id="cart">
            <a href="cart.php" class="cart-link">Корзина</a>
            <div class="cl">&nbsp;</div>
            <?php
            $is_admin = 0;
            if(isset($_SESSION['user_id'])) {
                $user_id = $_SESSION['user_id'];
                $admin_query = mysqli_query($con, "SELECT is_admin FROM user WHERE id = '$user_id'");
                if(mysqli_num_rows($admin_query) > 0) {
                $admin_data = mysqli_fetch_assoc($admin_query);
                $is_admin = $admin_data['is_admin'];
                }

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
                } else {
                    $items = 0;
                    $total = 0;
                }
                
                echo "<span>Товаров: <strong>$items</strong></span>";
                echo "&nbsp;&nbsp;";
                echo "<span>Сумма: <strong>" . number_format($total, 2) . " руб.</strong></span>";
            } else {
                echo "<span>Товаров: <strong>0</strong></span>";
                echo "&nbsp;&nbsp;";
                echo "<span>Сумма: <strong>0.00 руб.</strong></span>";
            }
            ?>
        </div>
        <nav id="navigation">
            <ul>
                <li><a href="index.php" class="active">Главная</a></li>
                <!-- <li><a href="#">Поддержка</a></li> -->
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
                <!-- <li><a href="#">О магазине</a></li>
                <li><a href="#">Контакты</a></li> -->
            </ul>
        </nav>
    </div>
</div>