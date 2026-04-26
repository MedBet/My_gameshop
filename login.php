<?php
include("config.php");


function verifyPassword($inputPassword, $storedPassword) {
    // Если пароль начинается с $2y$ - это хэш, используем password_verify
    if (strpos($storedPassword, '$2y$') === 0) {
        return password_verify($inputPassword, $storedPassword);
    }
    // Иначе простое сравнение строк
    return $inputPassword === $storedPassword;
}

if (isset($_POST['res'])) {
    $input = trim($_POST['login']);
    $password = trim($_POST['password']);
    
    // Экранируем специальные символы
    $safeInput = mysqli_real_escape_string($con, $input);
    
    // Ищем пользователя по логину или email
    $sql = "SELECT * FROM user WHERE login='$safeInput' OR mail='$safeInput' LIMIT 1";
    $result = mysqli_query($con, $sql);
    
    if (!$result) {
        die("<p class='error'>Ошибка базы данных: " . mysqli_error($con) . "</p>");
    }
    
    if (mysqli_num_rows($result) == 1) {
        $user = mysqli_fetch_assoc($result);
        
        // Проверяем пароль
        if (verifyPassword($password, $user['password'])) {
            
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['login'] = $user['login'];
            $_SESSION['is_admin'] = $user['is_admin'];
            
            // Если пароль не хэширован - хэшируем его и обновляем в БД
            if (strpos($user['password'], '$2y$') !== 0) {
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                mysqli_query($con, "UPDATE user SET password='$hashedPassword' WHERE id=".$user['id']);
            }
            
            header("Location: index.php");
            exit();
        } else {
            echo "<div class='error'>";
            echo "<p>Неверный пароль!</p>";
            echo "<p>Попробуйте ввести: <strong>" . htmlspecialchars($password) . "</strong></p>";
            echo "<p>Пароль в базе: <strong>" . htmlspecialchars($user['password']) . "</strong></p>";
            echo "</div>";
        }
    } else {
        echo "<p class='error'>Пользователь с логином/email '".htmlspecialchars($input)."' не найден!</p>";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Вход</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .error { 
            color: red;
            background: #ffeeee;
            padding: 10px;
            border: 1px solid #ffcccc;
            margin: 10px 0;
        }
        .debug-info {
            background: #f0f0f0;
            padding: 10px;
            margin: 20px 0;
            border: 1px dashed #ccc;
        }
    </style>
</head>
<body>
    <div class="shell">
        <div id="header">
            <?php include("header.php"); ?>
        </div>

        <div id="main">
            <div class="cl">&nbsp;</div>
            <div id="content">
                <h2>Вход в систему</h2>
                
                <form method="post" autocomplete="off">
                    <label>Логин или Email:</label><br>
                    <input type="text" name="login" required autofocus><br><br>
                    
                    <label>Пароль:</label><br>
                    <input type="password" name="password" required><br><br>
                    
                    <input type="submit" name="res" value="Войти">
                </form>
                
                <p>Нет аккаунта? <a href="register.php">Зарегистрируйтесь</a></p>
                
                <?php if(isset($_GET['debug'])): ?>
                    <div class="debug-info">
                        <h3>Отладочная информация (все пользователи):</h3>
                        <?php
                        $debugQuery = mysqli_query($con, "SELECT id, login, mail, 
                            LEFT(password, 15) as password_part, 
                            LENGTH(password) as pass_length 
                            FROM user");
                        
                        echo "<table>";
                        echo "<tr><th>ID</th><th>Логин</th><th>Email</th><th>Пароль (начало)</th><th>Длина</th></tr>";
                        
                        while ($row = mysqli_fetch_assoc($debugQuery)) {
                            echo "<tr>";
                            echo "<td>".$row['id']."</td>";
                            echo "<td>".htmlspecialchars($row['login'])."</td>";
                            echo "<td>".htmlspecialchars($row['mail'])."</td>";
                            echo "<td>".htmlspecialchars($row['password_part'])."</td>";
                            echo "<td>".$row['pass_length']."</td>";
                            echo "</tr>";
                        }
                        echo "</table>";
                        ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>