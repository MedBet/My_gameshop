<?php
function buildQueryString($newParams = []) {
    $params = $_GET;
    foreach ($newParams as $key => $value) {
        if ($value === null) {
            unset($params[$key]);
        } else {
            $params[$key] = $value;
        }
    }
    return http_build_query($params);
}

function getCartInfo($con) {
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
    
    return ['items' => $items, 'total' => $total];
}
?>