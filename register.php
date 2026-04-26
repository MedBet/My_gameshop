    <?php include("config.php") ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Регистрация</title>
        <link rel="stylesheet" href="css/style.css">
    </head>
    <body>
        <div class="shell">
            <div id="header">
                <?php include("header.php"); ?>
            </div>

            <div id="main">
                <div class="cl">&nbsp;</div>
                <div id="content">
                    <h2>Регистрация</h2>
                    <?php
                    if (isset($_POST['register'])) {
                        $login = mysqli_real_escape_string($con, $_POST['login']);
                        $password = mysqli_real_escape_string($con, $_POST['password']); // Храним как есть
                        $phone = mysqli_real_escape_string($con, $_POST['phone']);
                        $mail = mysqli_real_escape_string($con, $_POST['mail']);
                        $first_name = mysqli_real_escape_string($con, $_POST['first_name']);
                        $last_name = mysqli_real_escape_string($con, $_POST['last_name']);
                        
                        // Проверка существования пользователя
                        $check = mysqli_query($con, "SELECT * FROM user WHERE login='$login' OR mail='$mail'");
                        if (mysqli_num_rows($check)) {
                            echo "<p style='color:red'>Пользователь с таким логином или email уже существует!</p>";
                        } else {
                            // Вставка без хэширования
                            $sql = "INSERT INTO user (login, password, phone, mail, first_name, last_name, is_admin) 
                                    VALUES ('$login', '$password', '$phone', '$mail', '$first_name', '$last_name', 0)";
                            
                            if (mysqli_query($con, $sql)) {
                                echo "<p style='color:green'>Регистрация успешна! <a href='login.php'>Войдите</a></p>";
                            } else {
                                echo "<p style='color:red'>Ошибка: " . mysqli_error($con) . "</p>";
                            }
                        }
                    }
                    ?>
                    <form method="post">
                        <label>Логин:</label><br>
                        <input type="text" name="login" required><br><br>
                        
                        <label>Пароль:</label><br>
                        <input type="password" name="password" required><br><br>
                        
                        <label>Телефон:</label><br>
                        <input type="text" name="phone" required><br><br>
                        
                        <label>Email:</label><br>
                        <input type="email" name="mail" required><br><br>
                        
                        <label>Имя:</label><br>
                        <input type="text" name="first_name" required><br><br>
                        
                        <label>Фамилия:</label><br>
                        <input type="text" name="last_name" required><br><br>
                        
                        <input type="submit" name="register" value="Зарегистрироваться">
                    </form>
                    <p>Уже есть аккаунт? <a href="login.php">Войти</a></p>
                </div>
            </div>
        </div>
    </body>
    </html>