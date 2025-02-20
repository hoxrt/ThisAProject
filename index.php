<?php
require_once 'config.php';

// معالجة البحث والتصفية
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$category = isset($_GET['category']) ? (int)$_GET['category'] : 0;
$condition = isset($_GET['condition']) ? $_GET['condition'] : '';
$max_price = isset($_GET['max_price']) ? (float)$_GET['max_price'] : 0;

// بناء استعلام البحث
$sql = "SELECT p.*, c.name as category_name, u.username as seller_name 
        FROM products p 
        LEFT JOIN categories c ON p.category_id = c.id 
        LEFT JOIN users u ON p.user_id = u.id 
        WHERE p.status = 'available'";

$params = [];
$types = "";

if (!empty($search)) {
    $sql .= " AND (p.title LIKE ? OR p.description LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= "ss";
}

if ($category > 0) {
    $sql .= " AND p.category_id = ?";
    $params[] = $category;
    $types .= "i";
}

if (!empty($condition)) {
    $sql .= " AND p.condition_status = ?";
    $params[] = $condition;
    $types .= "s";
}

if ($max_price > 0) {
    $sql .= " AND p.price <= ?";
    $params[] = $max_price;
    $types .= "d";
}

$sql .= " ORDER BY p.created_at DESC";

// تنفيذ الاستعلام
$stmt = mysqli_prepare($conn, $sql);
if (!empty($params)) {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

// جلب الفئات للتصفية
$categories_sql = "SELECT * FROM categories";
$categories_result = mysqli_query($conn, $categories_sql);
?>

<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <title>سوق القرطاسية الجامعي</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <nav class="navbar">
        <div class="navbar-container">
            <h1>سوق القرطاسية الجامعي</h1>
            <div class="nav-links">
                <a href="faculty_fees.php" class="btn btn-info">رسوم الكليات</a>
                <?php if(isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true): ?>
                    <span>مرحباً <?php echo htmlspecialchars($_SESSION["username"]); ?></span>
                    <?php if($_SESSION["role"] === "admin"): ?>
                        <a href="admin/dashboard.php" class="btn btn-primary">لوحة التحكم</a>
                    <?php else: ?>
                        <a href="user/dashboard.php" class="btn btn-primary">لوحة التحكم</a>
                    <?php endif; ?>
                    <a href="auth/logout.php" class="btn btn-secondary">تسجيل الخروج</a>
                <?php else: ?>
                    <a href="auth/login.php" class="btn btn-primary">تسجيل الدخول</a>
                    <a href="auth/register.php" class="btn btn-secondary">تسجيل حساب جديد</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <div class="container">
        <div class="search-filters">
            <form method="GET" class="advanced-search">
                <div class="form-group">
                    <input type="text" name="search" class="form-control" 
                           placeholder="ابحث عن منتج..." 
                           value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <div class="form-group">
                    <select name="category" class="form-control">
                        <option value="">جميع الفئات</option>
                        <?php mysqli_data_seek($categories_result, 0); 
                        while($cat = mysqli_fetch_assoc($categories_result)): ?>
                            <option value="<?php echo $cat['id']; ?>" 
                                <?php echo $category == $cat['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($cat['name']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="form-group">
                    <select name="condition" class="form-control">
                        <option value="">جميع الحالات</option>
                        <option value="new" <?php echo $condition === 'new' ? 'selected' : ''; ?>>جديد</option>
                        <option value="used" <?php echo $condition === 'used' ? 'selected' : ''; ?>>مستعمل</option>
                    </select>
                </div>
                <div class="form-group">
                    <input type="number" name="max_price" class="form-control" 
                           placeholder="السعر الأقصى" 
                           value="<?php echo $max_price ?: ''; ?>">
                </div>
                <button type="submit" class="btn btn-primary">بحث</button>
                <a href="index.php" class="btn btn-secondary">إعادة تعيين</a>
            </form>
        </div>

        <div class="products-grid" id="products-container">
            <?php 
            if(mysqli_num_rows($result) > 0):
                while($product = mysqli_fetch_assoc($result)):
            ?>
                <div class="product-card" data-category="<?php echo $product['category_id']; ?>">
                    <h3><?php echo htmlspecialchars($product['title']); ?></h3>
                    <p class="price">السعر: <?php echo htmlspecialchars($product['price']); ?> ريال</p>
                    <p class="category">الفئة: <?php echo htmlspecialchars($product['category_name']); ?></p>
                    <p class="seller">البائع: <?php echo htmlspecialchars($product['seller_name']); ?></p>
                    <p class="condition">الحالة: <?php echo $product['condition_status'] === 'new' ? 'جديد' : 'مستعمل'; ?></p>
                    <?php if(isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true): ?>
                        <a href="product/view.php?id=<?php echo $product['id']; ?>" class="btn btn-primary">عرض التفاصيل</a>
                    <?php endif; ?>
                </div>
            <?php 
                endwhile;
            else:
            ?>
                <p class="no-products">لا توجد منتجات تطابق معايير البحث</p>
            <?php endif; ?>
        </div>
    </div>

    <script>
    // حذف وظائف filterProducts و searchProducts لأننا نستخدم الآن البحث من جانب الخادم
    </script>
</body>
</html>