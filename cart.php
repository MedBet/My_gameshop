<?php
include("config.php");

if(!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$order_id = getOrCreateCart($con, $user_id);

// Обработка добавления товара
if(isset($_GET['buy'])) {
    $subscribes_id = (int)$_GET['buy'];
    
    // Проверяем, есть ли уже такой товар в корзине
    $exists_query = mysqli_query($con, "SELECT id FROM cart WHERE order_id = '$order_id' AND subscribes_id = '$subscribes_id'");
    
    if(!$exists_query) die("Ошибка запроса: " . mysqli_error($con));
    
    if(mysqli_num_rows($exists_query) == 0) {
        // Добавляем в корзину
        if(!mysqli_query($con, "INSERT INTO cart (order_id, subscribes_id) VALUES ('$order_id', '$subscribes_id')")) {
            die("Ошибка добавления в корзину: " . mysqli_error($con));
        }
        $_SESSION['message'] = "Товар добавлен в корзину!";
    } else {
        $_SESSION['message'] = "Этот товар уже в вашей корзине!";
    }
    
    header("Location: cart.php");
    exit();
}

// Получаем товары в корзине
$cart_query = mysqli_query($con, "
    SELECT 
        ca.id as cart_item_id,
        s.id as subscribe_id,
        s.name AS subscribe_name,
        s.time, 
        s.price,
        c.name AS company_name,
        t.name AS type_name
    FROM cart ca
    JOIN subscribes s ON ca.subscribes_id = s.id
    JOIN company c ON s.company_id = c.id
    JOIN type t ON s.type_id = t.id
    WHERE ca.order_id = '$order_id'
");

if(!$cart_query) die("Ошибка запроса корзины: " . mysqli_error($con));

// Вычисляем общую сумму
$total = 0;
$cart_items = [];
while($item = mysqli_fetch_assoc($cart_query)) {
    $cart_items[] = $item;
    $total += $item['price'];
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Корзина - Магазин подписок</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .cart-container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 20px;
        }
        .message {
            background: #dff0d8;
            color: #3c763d;
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        .cart-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .cart-table th {
            background: #343a40;
            color: white;
            padding: 12px;
            text-align: left;
        }
        .cart-table td {
            padding: 12px;
            border-bottom: 1px solid #dee2e6;
        }
        .cart-table tr:nth-child(even) {
            background: #f8f9fa;
        }
        .total-row {
            font-weight: bold;
            background: #e9ecef;
        }
        .checkout-btn {
            text-align: right;
            margin-top: 20px;
        }
        .btn {
            display: inline-block;
            padding: 10px 20px;
            background: #28a745;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            border: none;
            cursor: pointer;
        }
        .btn:hover {
            background: #218838;
        }
        .btn-danger {
            background: #dc3545;
        }
        .btn-danger:hover {
            background: #c82333;
        }
        .empty-cart {
            text-align: center;
            padding: 40px;
            background: #f8f9fa;
            border-radius: 5px;
        }
    </style>
</head>
<body>
    <div class="shell">
        <?php include("header.php"); ?>
        
        <div class="cart-container">
            <h1>Ваша корзина</h1>
            
            <?php if(isset($_SESSION['message'])): ?>
                <div class="message"><?= $_SESSION['message'] ?></div>
                <?php unset($_SESSION['message']); ?>
            <?php endif; ?>
            
            <?php if(!empty($cart_items)): ?>
                <table class="cart-table">
                    <thead>
                        <tr>
                            <th>Название</th>
                            <th>Тип</th>
                            <th>Компания</th>
                            <th>Срок</th>
                            <th>Цена</th>
                            <th>Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($cart_items as $item): ?>
                            <tr>
                                <td><?= htmlspecialchars($item['subscribe_name']) ?></td>
                                <td><?= htmlspecialchars($item['type_name']) ?></td>
                                <td><?= htmlspecialchars($item['company_name']) ?></td>
                                <td><?= $item['time'] ?> дней</td>
                                <td><?= number_format($item['price'], 2) ?> руб.</td>
                                <td>
                                    <a href="remove_from_cart.php?cart_item_id=<?= $item['cart_item_id'] ?>" class="btn btn-danger">Удалить</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <tr class="total-row">
                            <td colspan="4">Итого:</td>
                            <td><?= number_format($total, 2) ?> руб.</td>
                            <td></td>
                        </tr>
                    </tbody>
                </table>
                
                <div class="checkout-btn">
                    <a href="checkout.php" class="btn">Оформить заказ</a>
                </div>
            <?php else: ?>
                <div class="empty-cart">
                    <h3>Ваша корзина пуста</h3>
                    <p>Вернитесь в <a href="index.php">каталог</a>, чтобы добавить товары</p>
                </div>
            <?php endif; ?>
        </div>
        
        
    </div>
</body>
</html>