<?php include("config.php"); ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="Content-type" content="text/html; charset=utf-8" />
    <title>Product Details</title>
    <link rel="stylesheet" href="css/style.css" type="text/css" media="all" />
   
    <script src="js/jquery-1.4.1.min.js" type="text/javascript"></script>    
    <script src="js/jquery.jcarousel.pack.js" type="text/javascript"></script>    
    <script src="js/jquery-func.js" type="text/javascript"></script>    
    
    
    <style type="text/css">
        
        .product-container {
            width: 90%;
            max-width: 960px;
            margin-left: 120px;
            padding: 10px;
        }
        
        .product-details {
            display: flex;
            background: #fff;
            border: 1px solid #dedede;
            padding: 20px;
            margin-top: 20px;
            
        }
        
        .product-image {
            flex: 1;
            margin-top: 50px;
            margin-bottom: 40px;
            padding: 1px;
            text-align: center;
        }
        
        .product-image img {
            max-width: 100%;
            height: auto;
        }
        
        .product-info {
            flex: 1;
            padding: 10px 20px;
            
        }
        
        .product-description {
            margin-top: 20px;
            line-height: 1.6;
        }
        
        .buy-button {
    display: block;
    width: 100%;
    height: 100px;
    padding: 8px;
    background-color: #5ba32b;
    color: white;
    border: none;
    border-radius: 4px;
    font-weight: 600;
    cursor: pointer;
    transition: background-color 0.3s;
    text-align: center;
    text-decoration: none;
    font-size: 26px;
    margin-top: auto;
    
}

.buy-button:hover {
    background-color: #004a73;
}
        
        .price {
            font-size: 56px;
            color:rgb(0, 0, 0);
            margin: 15px 0;
            font-weight: bold;
        }
    </style>
</head>
<body>
    
  
<div class="shell">


    <div id="header">
    <?php include("header.php"); ?>

    </div>
 
    
    <!-- Main -->
    <div id="main">
        <div class="cl">&nbsp;</div>
        
        <!-- Content -->
        <div id="content">
            <div class="product-container">
                <?php
                if(isset($_GET['open_prod'])) {
                    $id = $_GET['open_prod'];
                    $sql = "call market_subs.get_product_by_id($id);";
                    $query = mysqli_query($con, $sql);
                    $product = mysqli_fetch_array($query);
                    
                    if($product) {
                        echo '
                        <div class="product-details">
                            <div class="product-image">
                                <img src="images/'.$product[6].'" alt="'.$product[2].'"/>
                            </div>
                            <div class="product-info">
                                <h2>'.$product[3].'</h2>
                                <h3>'.$product[1].'</h3>
                                <div class="product-description">
                                    <p>'.$product[2].'</p>
                                    <p><strong>На сколько месяцев:</strong> '.$product[4].'</p>
                                    <div class="price">'.$product[5].'</div>';
                                    
                                    if(isset($_SESSION['user_id'])) {
                                        echo '<form method="get" action="cart.php">
                                            <button name="buy" type="submit" value="'.$product[0].'" class="buy-button">Купить</button>
                                        </form>';
                                    } else {
                                        echo '<a href="login.php" class="buy-button">Войдите чтобы купить</a>';
                                    }
                                    
                                echo '
                                </div>
                            </div>
                        </div>';
                    } else {
                        echo '<p style="text-align: center; padding: 20px;">Product not found.</p>';
                    }
                } else {
                    echo '<p style="text-align: center; padding: 20px;">No product selected.</p>';
                }
                ?>
            </div>
        </div>
      
        
        <div class="cl">&nbsp;</div>
    </div>
   
    
    
    
</div>    

    
</body>
</html>