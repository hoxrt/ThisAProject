<?php
require_once '../config.php';

if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== "admin"){
    header("location: ../auth/login.php");
    exit;
}

$product_id = $_GET['id'] ?? 0;

if($_SERVER["REQUEST_METHOD"] == "GET"){
    // جلب بيانات المنتج
    $sql = "SELECT * FROM library_products WHERE id = ?";
    if($stmt = mysqli_prepare($conn, $sql)){
        mysqli_stmt_bind_param($stmt, "i", $product_id);
        if(mysqli_stmt_execute($stmt)){
            $result = mysqli_stmt_get_result($stmt);
            if(mysqli_num_rows($result) == 1){
                $product = mysqli_fetch_assoc($result);
            } else {
                header("location: dashboard.php");
                exit();
            }
        }
        mysqli_stmt_close($stmt);
    }
}

if($_SERVER["REQUEST_METHOD"] == "POST"){
    $name = trim($_POST["name"]);
    $price = trim($_POST["price"]);
    $category_id = trim($_POST["category_id"]);
    
    $sql = "UPDATE library_products SET name=?, price=?, category_id=? WHERE id=?";
    
    if($stmt = mysqli_prepare($conn, $sql)){
        mysqli_stmt_bind_param($stmt, "sdii", $name, $price, $category_id, $product_id);
        
        if(mysqli_stmt_execute($stmt)){
            header("location: dashboard.php");
            exit();
        } else{
            echo "حدث خطأ ما. الرجاء المحاولة لاحقاً.";
        }
        mysqli_stmt_close($stmt);
    }
}

// جلب الفئات للنموذج
$categories_sql = "SELECT * FROM categories";
$categories_result = mysqli_query($conn, $categories_sql);
?>

<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <title>تعديل منتج المكتبة</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="container">
        <div class="form-container">
            <h2>تعديل منتج المكتبة</h2>
            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]) . '?id=' . $product_id; ?>" method="POST">
                <div class="form-group">
                    <label>اسم المنتج</label>
                    <input type="text" name="name" class="form-control" 
                           value="<?php echo htmlspecialchars($product['name']); ?>" required>
                </div>
                <div class="form-group">
                    <label>السعر</label>
                    <input type="number" step="0.01" name="price" class="form-control" 
                           value="<?php echo htmlspecialchars($product['price']); ?>" required>
                </div>
                <div class="form-group">
                    <label>الفئة</label>
                    <select name="category_id" class="form-control" required>
                        <?php while($category = mysqli_fetch_assoc($categories_result)): ?>
                            <option value="<?php echo $category['id']; ?>" 
                                <?php echo ($category['id'] == $product['category_id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($category['name']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="form-group">
                    <input type="submit" class="btn btn-primary" value="حفظ التعديلات">
                    <a href="dashboard.php" class="btn btn-secondary">إلغاء</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>