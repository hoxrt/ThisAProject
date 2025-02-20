<?php
session_start();
require_once '../config.php';

if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: ../auth/login.php");
    exit;
}

$user_id = $_SESSION["id"];
$product_id = $_GET['id'] ?? 0;

try {
    // جلب بيانات المنتج مع التحقق من الملكية
    $sql = "SELECT * FROM products WHERE id = ? AND user_id = ?";
    if($stmt = mysqli_prepare($conn, $sql)){
        mysqli_stmt_bind_param($stmt, "ii", $product_id, $user_id);
        if(!mysqli_stmt_execute($stmt)){
            throw new Exception("حدث خطأ في جلب بيانات المنتج");
        }
        
        $result = mysqli_stmt_get_result($stmt);
        if(mysqli_num_rows($result) != 1){
            throw new Exception("المنتج غير موجود");
        }
        
        $product = mysqli_fetch_assoc($result);
    }

    if($_SERVER["REQUEST_METHOD"] == "POST"){
        // التحقق من البيانات
        if(empty(trim($_POST["title"])) || empty(trim($_POST["description"])) || 
           empty(trim($_POST["price"])) || empty(trim($_POST["category_id"])) || 
           empty(trim($_POST["condition_status"])) || empty(trim($_POST["status"]))) {
            throw new Exception("جميع الحقول مطلوبة");
        }

        $title = sanitize_input($_POST["title"]);
        $description = sanitize_input($_POST["description"]);
        $price = filter_var(trim($_POST["price"]), FILTER_VALIDATE_FLOAT);
        $category_id = filter_var(trim($_POST["category_id"]), FILTER_VALIDATE_INT);
        $condition_status = trim($_POST["condition_status"]);
        $status = trim($_POST["status"]);
        
        if($price === false || $price < 0) {
            throw new Exception("السعر غير صالح");
        }

        if($category_id === false) {
            throw new Exception("الفئة غير صالحة");
        }

        if(!in_array($condition_status, ['new', 'used'])) {
            throw new Exception("حالة المنتج غير صالحة");
        }

        if(!in_array($status, ['available', 'sold'])) {
            throw new Exception("حالة البيع غير صالحة");
        }

        $sql = "UPDATE products 
                SET title=?, description=?, price=?, category_id=?, condition_status=?, status=? 
                WHERE id=? AND user_id=?";
        
        if($stmt = mysqli_prepare($conn, $sql)){
            mysqli_stmt_bind_param($stmt, "sssissii", 
                $title, $description, $price, $category_id, $condition_status, $status, $product_id, $user_id);
            
            if(!mysqli_stmt_execute($stmt)){
                throw new Exception("حدث خطأ في تحديث المنتج");
            }

            log_activity($user_id, 'edit_product', "تم تعديل المنتج: $title");
            $_SESSION['success_message'] = "تم تحديث المنتج بنجاح";
            header("location: dashboard.php");
            exit();
        }
    }

    // جلب الفئات للنموذج
    $categories_sql = "SELECT * FROM categories";
    $categories_result = mysqli_query($conn, $categories_sql);

} catch (Exception $e) {
    $_SESSION['error_message'] = $e->getMessage();
    header("location: dashboard.php");
    exit();
}
?>

<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <title>تعديل المنتج</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <nav class="navbar">
        <div class="navbar-container">
            <h1>تعديل المنتج</h1>
            <div class="nav-links">
                <a href="dashboard.php" class="btn btn-secondary">العودة للوحة التحكم</a>
            </div>
        </div>
    </nav>

    <div class="container">
        <div class="form-container">
            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]) . '?id=' . $product_id; ?>" method="POST">
                <div class="form-group">
                    <label>عنوان المنتج</label>
                    <input type="text" name="title" class="form-control" 
                           value="<?php echo htmlspecialchars($product['title']); ?>" required>
                </div>
                <div class="form-group">
                    <label>الوصف</label>
                    <textarea name="description" class="form-control" required><?php echo htmlspecialchars($product['description']); ?></textarea>
                </div>
                <div class="form-group">
                    <label>السعر</label>
                    <input type="number" step="0.01" name="price" class="form-control" 
                           value="<?php echo htmlspecialchars($product['price']); ?>" required min="0">
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
                    <label>الحالة</label>
                    <select name="condition_status" class="form-control" required>
                        <option value="new" <?php echo ($product['condition_status'] == 'new') ? 'selected' : ''; ?>>جديد</option>
                        <option value="used" <?php echo ($product['condition_status'] == 'used') ? 'selected' : ''; ?>>مستعمل</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>حالة البيع</label>
                    <select name="status" class="form-control" required>
                        <option value="available" <?php echo ($product['status'] == 'available') ? 'selected' : ''; ?>>متاح</option>
                        <option value="sold" <?php echo ($product['status'] == 'sold') ? 'selected' : ''; ?>>تم البيع</option>
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