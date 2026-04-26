<?php
include("config.php");


// Проверка параметра order_id
if(!isset($_GET['order_id']) || !is_numeric($_GET['order_id'])) {
    header("Location: orders.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$order_id = (int)$_GET['order_id'];

// Проверяем принадлежность заказа пользователю
if(!validateCartOwnership($con, $order_id, $user_id)) {
    header("Location: orders.php");
    exit();
}

// Получаем информацию о заказе
$order_query = mysqli_query($con, "SELECT `data`, `check` FROM `order` WHERE id = '$order_id'");
if(!$order_query) die("Ошибка запроса заказа: " . mysqli_error($con));

$order_data = mysqli_fetch_assoc($order_query);

// Получаем товары в заказе
$items_query = mysqli_query($con, "
    SELECT 
        s.id, 
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

if(!$items_query) die("Ошибка запроса товаров: " . mysqli_error($con));

// Подсчет суммы и количества товаров
$sum_query = mysqli_query($con, "
    SELECT IFNULL(SUM(s.price), 0) as total
    FROM cart ca
    JOIN subscribes s ON ca.subscribes_id = s.id
    WHERE ca.order_id = '$order_id'
");

if(!$sum_query) die("Ошибка подсчета суммы: " . mysqli_error($con));

$sum_data = mysqli_fetch_assoc($sum_query);
$total = (float)$sum_data['total'];
$items_data = [];
while($item = mysqli_fetch_assoc($items_query)) {
    $items_data[] = $item;
    
}
$items_count = count($items_data);
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Детали заказа #<?= $order_id ?></title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .order-container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 20px;
        }
        .order-header {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .order-items {
            width: 100%;
            border-collapse: collapse;
        }
        .order-items th {
            background: #343a40;
            color: white;
            padding: 12px;
            text-align: left;
        }
        .order-items td {
            padding: 12px;
            border-bottom: 1px solid #dee2e6;
        }
        .order-items tr:nth-child(even) {
            background: #f8f9fa;
        }
        .order-total {
            font-weight: bold;
            background: #e9ecef;
        }
        .empty-order {
            text-align: center;
            padding: 40px;
            background: #f8f9fa;
            border-radius: 5px;
        }
        .back-link {
            display: inline-block;
            margin-top: 20px;
            color: #007bff;
            text-decoration: none;
        }
        .back-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="shell">
        <?php include("header.php"); ?>
        
        <div class="order-container">
            <div class="order-header">
                <h1>Заказ #<?= $order_id ?></h1>
                <p><strong>Дата:</strong> <?= date('d.m.Y H:i', strtotime($order_data['data'])) ?></p>
                <p><strong>Статус:</strong> <?= $order_data['check'] ? 'Завершен' : 'В обработке' ?></p>
                <p><strong>Товаров:</strong> <?= $items_count ?></p>
                <p><strong>Общая сумма:</strong> <?= $total = (float)$sum_data['total']; ?> руб.</p>
            </div>
            
            <?php if($items_count > 0): ?>
                <table class="order-items">
                    <thead>
                        <tr>
                            <th>Название</th>
                            <th>Тип</th>
                            <th>Компания</th>
                            <th>Срок</th>
                            <th>Цена</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($items_data as $item): ?>
                            <tr>
                                <td><?= htmlspecialchars($item['subscribe_name']) ?></td>
                                <td><?= htmlspecialchars($item['type_name']) ?></td>
                                <td><?= htmlspecialchars($item['company_name']) ?></td>
                                <td><?= $item['time'] ?> месяца</td>
                                <td><?= number_format($item['price'], 2) ?> руб.</td>
                            </tr>
                        <?php endforeach; ?>
                        <tr class="order-total">
                            <td colspan="4" class="text-right">Итого:</td>
                            <td><?= number_format($total, 2) ?> руб.</td>
                        </tr>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="empty-order">
                    <h3>В этом заказе нет товаров</h3>
                    <p>Похоже, этот заказ пуст или товары были удалены.</p>
                </div>
            <?php endif; ?>
            
            <a href="orders.php" class="back-link">← Вернуться к списку заказов</a>
        </div>
        
        
    </div>
</body>
</html>