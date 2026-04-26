<?php
include("config.php");

if(!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Получаем информацию о пользователе
$user_query = mysqli_query($con, "SELECT first_name FROM user WHERE id = '$user_id'");
$user = mysqli_fetch_assoc($user_query);

// Получаем список заказов
$orders = mysqli_query($con, "
    SELECT o.id, o.data, o.check,
       COUNT(ca.id) as items_count,
       IFNULL(SUM(s.price), 0) as total
    FROM `order` o
    LEFT JOIN cart ca ON o.id = ca.order_id
    LEFT JOIN subscribes s ON ca.subscribes_id = s.id
    WHERE o.user_id = '$user_id' AND o.`check` = 1
    GROUP BY o.id
    ORDER BY o.data DESC
");


?>
<!DOCTYPE html>
<html>
<head>
    <title>Мои заказы - Магазин подписок</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .user-greeting {
            background: #f5f5f5;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .user-greeting h2 {
            margin: 0;
            color: #333;
        }
        
        .back-to-shop {
            background: #5ba32b;
            color: white;
            padding: 10px 15px;
            border-radius: 4px;
            text-decoration: none;
            font-weight: 600;
            transition: background-color 0.3s;
        }
        
        .back-to-shop:hover {
            background-color: #4a8c24;
        }
        
        .cart-summary {
            background: #f9f9f9;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 16px;
        }
        
        .orders-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .orders-table th, .orders-table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #e0e0e0;
        }
        
        .orders-table th {
            background: #171a21;
            color: white;
            font-weight: 500;
        }
        
        .orders-table tr:hover {
            background-color: #f9f9f9;
        }
        
        .orders-table td {
            color: #555;
        }
        
        .view-link {
            color: #5ba32b;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s;
        }
        
        .view-link:hover {
            color: #4a8c24;
            text-decoration: underline;
        }
        
        .no-orders {
            background: #f9f9f9;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
            margin-top: 20px;
        }
        
        .no-orders p {
            margin-bottom: 15px;
            color: #555;
        }
        
        .shop-link {
            color: #5ba32b;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s;
        }
        
        .shop-link:hover {
            color: #4a8c24;
            text-decoration: underline;
        }
        
        .message {
            background: #dff0d8;
            color: #3c763d;
            padding: 15px;
            margin-bottom: 20px;
            border: 1px solid #d6e9c6;
            border-radius: 4px;
        }
    </style>
</head>
<body>
    <div class="shell">
        <?php include("header.php"); ?>
        
        <div id="main">
            <div class="cl">&nbsp;</div>
            <div id="content">
                <!-- Приветствие пользователя -->
                <div class="user-greeting">
                    <h2>Здравствуйте, <?= htmlspecialchars($user['first_name']) ?>!</h2>
                    <a href="index.php" class="back-to-shop">Вернуться в магазин</a>
                </div>
                
              
                
                <!-- Сообщения -->
                <?php if(isset($_SESSION['message'])): ?>
                    <div class="message"><?= $_SESSION['message'] ?></div>
                    <?php unset($_SESSION['message']); ?>
                <?php endif; ?>
                
                <!-- Список заказов -->
                <h2>Мои заказы</h2>
                
                <?php if(mysqli_num_rows($orders) > 0): ?>
                    <table class="orders-table">
                        <thead>
                            <tr>
                                <th>№ заказа</th>
                                <th>Дата</th>
                                <th>Товаров</th>
                                <th>Сумма</th>
                                <th>Статус</th>
                                <th>Детали</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($order = mysqli_fetch_assoc($orders)): ?>
                                <tr>
                                    <td>#<?= $order['id'] ?></td>
                                    <td><?= date('d.m.Y', strtotime($order['data'])) ?></td>
                                    <td><?= $order['items_count'] ?></td>
                                    <td><?= number_format($order['total'], 2) ?> руб.</td>
                                    <td><?= $order['check'] ? 'Завершен' : 'В корзине' ?></td>
                                    <td><a href="order_details.php?order_id=<?= $order['id'] ?>" class="view-link">Просмотр</a></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="no-orders">
                        <p>У вас пока нет завершенных заказов.</p>
                        <p><a href="index.php" class="shop-link">Перейти к покупкам</a></p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>