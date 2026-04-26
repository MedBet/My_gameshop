<?php

include("config.php");


if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Проверка is_admin 
$user_id = $_SESSION['user_id'];
$admin_check = mysqli_query($con, "SELECT is_admin FROM user WHERE id = '$user_id'");
if (!$admin_check) {
    die("Ошибка запроса: " . mysqli_error($con));
}

$admin_row = mysqli_fetch_assoc($admin_check);
if (!$admin_row || $admin_row['is_admin'] != 1) {  
    header("Location: index.php");
    exit();
}

// Получаем списки для выпадающих меню
$types = mysqli_query($con, "SELECT * FROM type");
if (!$types) die("Ошибка загрузки типов: " . mysqli_error($con));

$companies = mysqli_query($con, "SELECT * FROM company");
if (!$companies) die("Ошибка загрузки компаний: " . mysqli_error($con));

$photos = mysqli_query($con, "SELECT * FROM photo");
if (!$photos) die("Ошибка загрузки фото: " . mysqli_error($con));

// Функция для загрузки изображения
function uploadImage($file) {
    $target_dir = "images/";
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }
    
    // Генерируем уникальное имя файла
    $file_name = pathinfo($file['name'], PATHINFO_FILENAME);
    $file_ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $unique_name = uniqid();
    $target_file = $target_dir . $unique_name . '.' . $file_ext;
    // Проверяем тип файла
    $allowed_types = ['image/jpeg', 'image/png'];
    if (!in_array($file['type'], $allowed_types)) {
        return false;
    }
    
    // Проверяем размер файла (макс. 5MB)
    if ($file['size'] > 5000000) {
        return false;
    }
    
    // Загружаем 
    if (move_uploaded_file($file['tmp_name'], $target_file)) {
        return [
            'name' => $file['name'],
            'link' => $unique_name.'.jpg'
        ];
    }
    
    return false;
}

// Обработка удаления подписки
if ($_SERVER['REQUEST_METHOD'] == 'GET' && isset($_GET['delete_id'])) {
    $delete_id = mysqli_real_escape_string($con, $_GET['delete_id']);
    
    // Получаем информацию о связанном изображении
    $photo_query = "SELECT p.link FROM photo p
                   JOIN photo_catalog pc ON p.id = pc.photo_id
                   WHERE pc.subscribes_id = '$delete_id'";
    $photo_result = mysqli_query($con, $photo_query);
    $photo = mysqli_fetch_assoc($photo_result);
    
    // Удаляем подписку и связанные данные
    $delete_query = "DELETE FROM subscribes WHERE id = '$delete_id'";
    if (mysqli_query($con, $delete_query)) {
        // Удаляем файл изображения, если он существует
        if ($photo && file_exists("images/" . $photo['link'])) {
            unlink("images/" . $photo['link']);
        }
        
        $success_message = "Подписка успешно удалена!";
        
        // Обновляем список подписок
        $subscribes = mysqli_query($con, "SELECT s.id, t.name AS type_name, c.name AS company_name, 
                                        s.name, s.time, s.price 
                                        FROM subscribes s
                                        JOIN type t ON s.type_id = t.id
                                        JOIN company c ON s.company_id = c.id
                                        ORDER BY s.id DESC");
        if (!$subscribes) die("Ошибка загрузки подписок: " . mysqli_error($con));
    } else {
        $error_message = "Ошибка при удалении подписки: " . mysqli_error($con);
    }
}

// Обработка формы добавления подписки
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_subscribe'])) {
    $type_id = mysqli_real_escape_string($con, $_POST['type_id']);
    $company_id = mysqli_real_escape_string($con, $_POST['company_id']);
    $name = mysqli_real_escape_string($con, $_POST['name']);
    $time = mysqli_real_escape_string($con, $_POST['time']);
    $price = mysqli_real_escape_string($con, $_POST['price']);
    
    // Обработка загрузки изображения
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] == UPLOAD_ERR_OK) {
        $image_info = uploadImage($_FILES['photo']);
        
        if ($image_info) {
            // Вставляем информацию о фото в БД
            $photo_query = "INSERT INTO photo (name, link) VALUES (
                '" . mysqli_real_escape_string($con, $image_info['name']) . "',
                '" . mysqli_real_escape_string($con, $image_info['link']) . "'
            )";
            
            if (mysqli_query($con, $photo_query)) {
                $photo_id = mysqli_insert_id($con);
                
                // Добавляем подписку
                $query = "INSERT INTO subscribes (type_id, company_id, name, time, price) 
                         VALUES ('$type_id', '$company_id', '$name', '$time', '$price')";
                
                if (mysqli_query($con, $query)) {
                    $subscribe_id = mysqli_insert_id($con);
                    
                    // Связываем подписку с фото
                    $photo_catalog_query = "INSERT INTO photo_catalog (photo_id, subscribes_id) 
                                          VALUES ('$photo_id', '$subscribe_id')";
                    mysqli_query($con, $photo_catalog_query);
                    
                    $success_message = "Подписка успешно добавлена!";
                    
                    // Обновляем списки
                    $types = mysqli_query($con, "SELECT * FROM type");
                    $companies = mysqli_query($con, "SELECT * FROM company");
                    $photos = mysqli_query($con, "SELECT * FROM photo");
                    $subscribes = mysqli_query($con, "SELECT s.id, t.name AS type_name, c.name AS company_name, 
                                                    s.name, s.time, s.price 
                                                    FROM subscribes s
                                                    JOIN type t ON s.type_id = t.id
                                                    JOIN company c ON s.company_id = c.id
                                                    ORDER BY s.id DESC");
                } else {
                    $error_message = "Ошибка при добавлении подписки: " . mysqli_error($con);
                }
            } else {
                $error_message = "Ошибка при сохранении изображения: " . mysqli_error($con);
            }
        } else {
            $error_message = "Ошибка загрузки изображения. Проверьте формат и размер файла.";
        }
    } else {
        $error_message = "Не выбрано изображение для подписки";
    }
}

// Получаем список подписок (первоначальная загрузка)
$subscribes = mysqli_query($con, "SELECT s.id, t.name AS type_name, c.name AS company_name, 
                                s.name, s.time, s.price 
                                FROM subscribes s
                                JOIN type t ON s.type_id = t.id
                                JOIN company c ON s.company_id = c.id
                                ORDER BY s.id DESC");
if (!$subscribes) die("Ошибка загрузки подписок: " . mysqli_error($con));
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Администрирование подписок</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            max-width: 1000px;
            margin: 0 auto;
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            text-align: center;
        }
        .form-group {
            margin-bottom: 15px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        input, select {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }
        button {
            background-color: #4CAF50;
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
        }
        button:hover {
            background-color: #45a049;
        }
        .message {
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 4px;
        }
        .success {
            background-color: #dff0d8;
            color: #3c763d;
        }
        .error {
            background-color: #f2dede;
            color: #a94442;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
        }
        .delete-btn {
            background-color: #f44336;
            color: white;
            padding: 5px 10px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
        }
        .delete-btn:hover {
            background-color: #d32f2f;
        }
        .actions-cell {
            text-align: center;
        }
        #imagePreview {
            max-height: 200px;
            margin-top: 10px;
            border: 1px solid #ddd;
            padding: 5px;
            display: none;
        }
        input[type="file"] {
            padding: 5px;
            background: #f9f9f9;
            border: 1px dashed #ccc;
        }
        .existing-photos {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 10px;
        }
        .photo-thumb {
            border: 1px solid #ddd;
            padding: 5px;
            text-align: center;
        }
        .photo-thumb img {
            max-height: 60px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Администрирование подписок</h1>
        
        <?php if (isset($success_message)): ?>
            <div class="message success"><?php echo $success_message; ?></div>
        <?php endif; ?>
        
        <?php if (isset($error_message)): ?>
            <div class="message error"><?php echo $error_message; ?></div>
        <?php endif; ?>
        
        <h2>Добавить новую подписку</h2>
        <form method="POST" action="" enctype="multipart/form-data">
            <div class="form-group">
                <label for="type_id">Тип подписки:</label>
                <select id="type_id" name="type_id" required>
                    <option value="">-- Выберите тип --</option>
                    <?php 
                    mysqli_data_seek($types, 0);
                    while ($type = mysqli_fetch_assoc($types)): ?>
                        <option value="<?php echo $type['id']; ?>"><?php echo $type['name']; ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label for="company_id">Компания:</label>
                <select id="company_id" name="company_id" required>
                    <option value="">-- Выберите компанию --</option>
                    <?php 
                    mysqli_data_seek($companies, 0);
                    while ($company = mysqli_fetch_assoc($companies)): ?>
                        <option value="<?php echo $company['id']; ?>"><?php echo $company['name']; ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label for="name">Название подписки:</label>
                <input type="text" id="name" name="name" required>
            </div>
            
            <div class="form-group">
                <label for="time">Срок действия (месяцев):</label>
                <input type="number" id="time" name="time" required>
            </div>
            
            <div class="form-group">
                <label for="price">Цена (руб.):</label>
                <input type="number" id="price" name="price" step="0.01" required>
            </div>
            
            <div class="form-group">
                <label for="photo">Изображение подписки:</label>
                <input type="file" id="photo" name="photo" accept="image/jpeg,image/png" required>
                <small>Только JPG/PNG изображения (макс. 5MB)</small>
                <img id="imagePreview" src="#" alt="Предпросмотр">
            </div>
            
            <div class="form-group">
                <label>Последние загруженные изображения:</label>
                <div class="existing-photos">
                    <?php
                    $recent_photos = mysqli_query($con, "SELECT * FROM photo ORDER BY id DESC LIMIT 5");
                    while($photo = mysqli_fetch_assoc($recent_photos)) {
                        echo '<div class="photo-thumb">';
                        echo '<img src="images/'.$photo['name'].'" alt="'.$photo['name'].'">';
                        echo '<div>'.$photo['name'].'</div>';
                        echo '</div>';
                    }
                    ?>
                </div>
            </div>
            
            <button type="submit" name="add_subscribe">Добавить подписку</button>
        </form>
        
        <h2>Список подписок</h2>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Тип</th>
                    <th>Компания</th>
                    <th>Название</th>
                    <th>Срок (мес.)</th>
                    <th>Цена (руб.)</th>
                    <th class="actions-cell">Действия</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($subscribe = mysqli_fetch_assoc($subscribes)): ?>
                    <tr>
                        <td><?php echo $subscribe['id']; ?></td>
                        <td><?php echo $subscribe['type_name']; ?></td>
                        <td><?php echo $subscribe['company_name']; ?></td>
                        <td><?php echo $subscribe['name']; ?></td>
                        <td><?php echo $subscribe['time']; ?></td>
                        <td><?php echo number_format($subscribe['price'], 2); ?></td>
                        <td class="actions-cell">
                            <a href="?delete_id=<?php echo $subscribe['id']; ?>" 
                               class="delete-btn"
                               onclick="return confirm('Вы уверены, что хотите удалить эту подписку?')">
                                Удалить
                            </a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
    
    <script>
        // Предпросмотр изображения перед загрузкой
        document.getElementById('photo').addEventListener('change', function(e) {
            const preview = document.getElementById('imagePreview');
            const file = e.target.files[0];
            
            if (file) {
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    preview.style.display = 'block';
                }
                
                reader.readAsDataURL(file);
            }
        });

        // Добавляем подтверждение перед удалением
        document.querySelectorAll('.delete-btn').forEach(btn => {
            btn.addEventListener('click', function(e) {
                if (!confirm('Вы уверены, что хотите удалить эту подписку?')) {
                    e.preventDefault();
                }
            });
        });
    </script>
</body>
</html>