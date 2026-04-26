<?php
include("config.php");

if(!isset($_SESSION['user_id']) || !isset($_GET['cart_item_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$cart_item_id = (int)$_GET['cart_item_id'];

// Проверяем принадлежность элемента корзины пользователю
$verify_query = mysqli_query($con, "
    SELECT ca.id 
    FROM cart ca
    JOIN `order` o ON ca.order_id = o.id
    WHERE ca.id = '$cart_item_id' 
    AND o.user_id = '$user_id'
    AND o.`check` = 0
");

if(!$verify_query) die("Ошибка проверки: " . mysqli_error($con));

if(mysqli_num_rows($verify_query) > 0) {
    // Удаляем элемент из корзины
    if(!mysqli_query($con, "DELETE FROM cart WHERE id = '$cart_item_id'")) {
        die("Ошибка удаления: " . mysqli_error($con));
    }
    $_SESSION['message'] = "Товар удален из корзины";
}

header("Location: cart.php");
exit();
?>