<?php
require_once 'config.php';

// جلب منتجات المكتبة مرتبة حسب الفئة
$sql = "SELECT lp.*, c.name as category_name 
        FROM library_products lp 
        LEFT JOIN categories c ON lp.category_id = c.id 
        ORDER BY c.name, lp.name";
$result = mysqli_query($conn, $sql);

// تجميع المنتجات حسب الفئة
$categories = array();
while ($row = mysqli_fetch_assoc($result)) {
    $category_name = $row['category_name'];
    if (!isset($categories[$category_name])) {
        $categories[$category_name] = array();
    }
    $categories[$category_name][] = $row;
}
?>

<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <title>أسعار مكتبة الكلية</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .fees-container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 1rem;
        }
        .category-section {
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
            overflow: hidden;
        }
        .category-header {
            background: #f8f9fa;
            padding: 1rem;
            border-bottom: 1px solid #dee2e6;
        }
        .category-header h2 {
            margin: 0;
            color: #333;
        }
        .products-table {
            width: 100%;
            border-collapse: collapse;
        }
        .products-table th,
        .products-table td {
            padding: 1rem;
            border: 1px solid #dee2e6;
        }
        .products-table th {
            background: #f8f9fa;
            font-weight: bold;
        }
        .products-table tr:nth-child(even) {
            background-color: #f8f9fa;
        }
        .price {
            font-weight: bold;
            color: #28a745;
        }
        .no-products {
            text-align: center;
            padding: 2rem;
            color: #666;
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="navbar-container">
            <h1>أسعار مكتبة الكلية</h1>
            <div class="nav-links">
                <a href="index.php" class="btn btn-secondary">العودة للرئيسية</a>
                <?php if(isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true): ?>
                    <?php if($_SESSION["role"] === "admin"): ?>
                        <a href="admin/dashboard.php" class="btn btn-primary">لوحة التحكم</a>
                    <?php endif; ?>
                    <a href="auth/logout.php" class="btn btn-danger">تسجيل الخروج</a>
                <?php else: ?>
                    <a href="auth/login.php" class="btn btn-primary">تسجيل الدخول</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <div class="fees-container">
        <?php if (empty($categories)): ?>
            <div class="no-products">
                <p>لا توجد منتجات مضافة حتى الآن.</p>
            </div>
        <?php else: ?>
            <?php foreach ($categories as $category_name => $products): ?>
                <div class="category-section">
                    <div class="category-header">
                        <h2><?php echo htmlspecialchars($category_name); ?></h2>
                    </div>
                    <table class="products-table">
                        <thead>
                            <tr>
                                <th>اسم المنتج</th>
                                <th>السعر</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($products as $product): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($product['name']); ?></td>
                                    <td class="price"><?php echo htmlspecialchars($product['price']); ?> ريال</td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</body>
</html>