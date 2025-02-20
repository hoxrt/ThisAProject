<?php
require_once '../config.php';

// التحقق من تسجيل الدخول
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: ../auth/login.php");
    exit;
}

// جلب منتجات المستخدم
$user_id = $_SESSION["id"];
$sql = "SELECT p.*, c.name as category_name 
        FROM products p 
        LEFT JOIN categories c ON p.category_id = c.id 
        WHERE p.user_id = ? 
        ORDER BY p.created_at DESC";

if($stmt = mysqli_prepare($conn, $sql)){
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
}

// جلب الفئات للنموذج
$categories_sql = "SELECT * FROM categories";
$categories_result = mysqli_query($conn, $categories_sql);
?>

<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <title>لوحة التحكم</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <nav class="navbar">
        <div class="navbar-container">
            <h1>لوحة التحكم</h1>
            <div class="nav-links">
                <a href="../index.php" class="btn btn-secondary">الصفحة الرئيسية</a>
                <a href="../messages/inbox.php" class="btn btn-primary">الرسائل</a>
                <a href="../auth/logout.php" class="btn btn-danger">تسجيل الخروج</a>
            </div>
        </div>
    </nav>

    <div class="container">
        <?php 
        if(isset($_SESSION['error_message'])): 
            echo '<div class="alert alert-danger">' . htmlspecialchars($_SESSION['error_message']) . '</div>';
            unset($_SESSION['error_message']);
        endif; 
        
        if(isset($_SESSION['success_message'])): 
            echo '<div class="alert alert-success">' . htmlspecialchars($_SESSION['success_message']) . '</div>';
            unset($_SESSION['success_message']);
        endif; 
        ?>

        <div class="form-container">
            <h2>إضافة منتج جديد</h2>
            <form action="add_product.php" method="POST">
                <div class="form-group">
                    <label>عنوان المنتج</label>
                    <input type="text" name="title" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>الوصف</label>
                    <textarea name="description" class="form-control" required></textarea>
                </div>
                <div class="form-group">
                    <label>السعر</label>
                    <input type="number" step="0.01" name="price" class="form-control" required min="0">
                </div>
                <div class="form-group">
                    <label>الفئة</label>
                    <select name="category_id" class="form-control" required>
                        <?php while($category = mysqli_fetch_assoc($categories_result)): ?>
                            <option value="<?php echo $category['id']; ?>">
                                <?php echo htmlspecialchars($category['name']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>الحالة</label>
                    <select name="condition_status" class="form-control" required>
                        <option value="new">جديد</option>
                        <option value="used">مستعمل</option>
                    </select>
                </div>
                <div class="form-group">
                    <input type="submit" class="btn btn-primary" value="إضافة المنتج">
                </div>
            </form>
        </div>

        <div class="products-container">
            <h2>منتجاتي</h2>
            <div class="products-grid">
                <?php 
                if(mysqli_num_rows($result) > 0):
                    while($product = mysqli_fetch_assoc($result)):
                ?>
                    <div class="product-card">
                        <h3><?php echo htmlspecialchars($product['title']); ?></h3>
                        <p class="price">السعر: <?php echo htmlspecialchars($product['price']); ?> ريال</p>
                        <p class="category">الفئة: <?php echo htmlspecialchars($product['category_name']); ?></p>
                        <p class="status">الحالة: <?php echo $product['status'] === 'available' ? 'متاح' : 'تم البيع'; ?></p>
                        <div class="actions">
                            <a href="edit_product.php?id=<?php echo $product['id']; ?>" class="btn btn-primary">تعديل</a>
                            <a href="delete_product.php?id=<?php echo $product['id']; ?>" 
                               class="btn btn-danger" 
                               onclick="return confirm('هل أنت متأكد من حذف هذا المنتج؟')">حذف</a>
                        </div>
                    </div>
                <?php 
                    endwhile;
                else:
                ?>
                    <p class="no-products">لم تقم بإضافة أي منتجات بعد</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>