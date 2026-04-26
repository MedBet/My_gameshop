<?php
$host = "localhost";
$user = "root";
$pass = "vertrigo";
$db = "market_subs";

$con = mysqli_connect($host, $user, $pass, $db);

if (!$con) {
    die("Ошибка подключения: " . mysqli_error($con));
}

mysqli_set_charset($con, "utf8mb4");
session_start();


function getOrCreateCart($con, $user_id) {
    // Удаляем старые неоформленные корзины (старше 1 дня)
    $old_date = date('Y-m-d H:i:s', strtotime('-1 day'));
    mysqli_query($con, "DELETE FROM `order` WHERE user_id = '$user_id' AND `check` = 0 AND `data` < '$old_date'");
    
    
    $cart_query = mysqli_query($con, "SELECT id FROM `order` WHERE user_id = '$user_id' AND `check` = 0 ORDER BY id DESC LIMIT 1");
    
    if($cart_query && mysqli_num_rows($cart_query) > 0) {
        $cart_data = mysqli_fetch_assoc($cart_query);
        return $cart_data['id'];
    } else {
        // Создаем новую корзину
        $current_date = date('Y-m-d H:i:s');
        if(mysqli_query($con, "INSERT INTO `order` (`data`, `check`, `user_id`) VALUES ('$current_date', 0, '$user_id')")) {
            return mysqli_insert_id($con);
        } else {
            die("Ошибка создания корзины: " . mysqli_error($con));
        }
    }
}

// Функция для проверки принадлежности корзины пользователю
function validateCartOwnership($con, $order_id, $user_id) {
    $query = mysqli_query($con, "SELECT id FROM `order` WHERE id = '$order_id' AND user_id = '$user_id'");
    if(!$query) die("Ошибка запроса: " . mysqli_error($con));
    return (mysqli_num_rows($query) > 0);
}
?>