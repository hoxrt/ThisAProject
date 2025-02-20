<?php
require_once '../config.php';

if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== "admin"){
    header("location: ../auth/login.php");
    exit;
}

// جلب منتجات المكتبة
$sql = "SELECT lp.*, c.name as category_name 
        FROM library_products lp 
        LEFT JOIN categories c ON lp.category_id = c.id 
        ORDER BY c.name, lp.name";
$result = mysqli_query($conn, $sql);

// جلب الفئات للنموذج
$categories_sql = "SELECT * FROM categories";
$categories_result = mysqli_query($conn, $categories_sql);
?>

<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <title>لوحة تحكم المشرف</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <nav class="navbar">
        <div class="navbar-container">
            <h1>لوحة تحكم المشرف</h1>
            <div class="nav-links">
                <a href="../index.php" class="btn btn-secondary">الصفحة الرئيسية</a>
                <a href="../faculty_fees.php" class="btn btn-info">عرض أسعار المكتبة</a>
                <a href="../auth/logout.php" class="btn btn-danger">تسجيل الخروج</a>
            </div>
        </div>
    </nav>

    <div class="container">
        <div class="section">
            <h2>إدارة المكتبة</h2>
            <div class="form-container">
                <h3>إضافة منتج جديد للمكتبة</h3>
                <form action="add_library_product.php" method="POST">
                    <div class="form-group">
                        <label>اسم المنتج</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>السعر</label>
                        <input type="number" step="0.01" name="price" class="form-control" required>
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
                        <input type="submit" class="btn btn-primary" value="إضافة المنتج">
                    </div>
                </form>
            </div>

            <div class="table-responsive">
                <h3>منتجات المكتبة</h3>
                <?php if(mysqli_num_rows($result) > 0): ?>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>اسم المنتج</th>
                                <th>الفئة</th>
                                <th>السعر</th>
                                <th>الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($product = mysqli_fetch_assoc($result)): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($product['name']); ?></td>
                                    <td><?php echo htmlspecialchars($product['category_name']); ?></td>
                                    <td><?php echo htmlspecialchars($product['price']); ?> ريال</td>
                                    <td>
                                        <a href="edit_library_product.php?id=<?php echo $product['id']; ?>" 
                                           class="btn btn-primary btn-sm">تعديل</a>
                                        <a href="delete_library_product.php?id=<?php echo $product['id']; ?>" 
                                           class="btn btn-danger btn-sm"
                                           onclick="return confirm('هل أنت متأكد من حذف هذا المنتج؟')">حذف</a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p class="alert alert-info">لا توجد منتجات مضافة حتى الآن.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <style>
    .section {
        background: #fff;
        padding: 1rem;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        margin-top: 2rem;
    }
    .table-responsive {
        margin-top: 2rem;
    }
    </style>
</body>
</html>