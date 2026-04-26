<?php
include("config.php");

if(!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Получаем текущую корзину
$cart_query = mysqli_query($con, "SELECT id, `data` FROM `order` WHERE user_id = '$user_id' AND `check` = 0 LIMIT 1");

if(mysqli_num_rows($cart_query) > 0) {
    $cart_data = mysqli_fetch_assoc($cart_query);
    $order_id = $cart_data['id'];
    $order_date = $cart_data['data'];
    
    // Получаем информацию о товарах в заказе
    $items_query = mysqli_query($con, "
        SELECT s.name, s.price 
        FROM cart ca
        JOIN subscribes s ON ca.subscribes_id = s.id
        WHERE ca.order_id = '$order_id'
    ");
    
    $total = 0;
    $items_list = "";
    while($item = mysqli_fetch_assoc($items_query)) {
        $total += $item['price'];
        $items_list .= "• " . $item['name'] . " - " . number_format($item['price'], 2) . " руб.\n";
    }
    

    mysqli_query($con, "UPDATE `order` SET `check` = 1 WHERE id = '$order_id'");
    
    $file_content = "Детали заказа\n";
    $file_content .= "================\n";
    $file_content .= "Номер заказа: #$order_id\n";
    $file_content .= "Дата оформления: " . date('d.m.Y H:i', strtotime($order_date)) . "\n";
    $file_content .= "Сумма заказа: " . number_format($total, 2) . " руб.\n\n";
    $file_content .= "Состав заказа:\n";
    $file_content .= $items_list;
    $file_content .= "\nСпасибо за покупку!";
    
    $_SESSION['message'] = "Заказ успешно оформлен! Номер вашего заказа: #$order_id";
    // header("Location: orders.php");
    header('Content-Type: text/plain');
    header('Content-Disposition: attachment; filename="order_'.$order_id.'.txt"');
    header('Content-Length: ' . strlen($file_content));
    echo $file_content;
    
    exit();
    
} else {
    $_SESSION['message'] = "У вас нет активных заказов!";
    header("Location: cart.php");
    exit();
}
?>