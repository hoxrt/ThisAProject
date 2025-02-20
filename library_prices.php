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
        .prices-header {
            text-align: center;
            padding: 2rem 0;
            background: linear-gradient(135deg, var(--primary-color), var(--info-color));
            color: white;
            margin-bottom: 2rem;
        }
        .prices-header h1 {
            font-size: 2.5rem;
            margin-bottom: 1rem;
        }
        .prices-header p {
            font-size: 1.1rem;
            opacity: 0.9;
        }
        .category-section {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
            overflow: hidden;
            transition: transform 0.3s ease;
        }
        .category-section:hover {
            transform: translateY(-5px);
        }
        .category-header {
            background: linear-gradient(to right, var(--primary-color), var(--info-color));
            padding: 1.5rem;
            color: white;
        }
        .category-header h2 {
            margin: 0;
            font-size: 1.5rem;
            font-weight: 500;
        }
        .products-table {
            width: 100%;
            border-collapse: collapse;
        }
        .products-table th,
        .products-table td {
            padding: 1.2rem;
            text-align: right;
            border: none;
            border-bottom: 1px solid var(--border-color);
        }
        .products-table th {
            background: rgba(0,0,0,0.02);
            font-weight: 600;
            color: var(--dark-color);
        }
        .products-table tr:last-child td {
            border-bottom: none;
        }
        .price {
            font-weight: bold;
            color: var(--success-color);
            font-size: 1.1rem;
        }
        .no-products {
            text-align: center;
            padding: 4rem 2rem;
            color: var(--secondary-color);
            background: white;
            border-radius: 12px;
            box-shadow: var(--shadow);
        }
        .no-products i {
            font-size: 3rem;
            margin-bottom: 1rem;
            display: block;
        }
        @media (max-width: 768px) {
            .prices-header h1 {
                font-size: 2rem;
            }
            .category-header h2 {
                font-size: 1.25rem;
            }
            .products-table th,
            .products-table td {
                padding: 1rem;
            }
        }
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <nav class="navbar">
        <div class="navbar-container">
            <h1><i class="fas fa-book"></i> أسعار مكتبة الكلية</h1>
            <div class="nav-links">
                <a href="index.php" class="btn btn-secondary"><i class="fas fa-home"></i> العودة للرئيسية</a>
                <?php if(isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true): ?>
                    <?php if($_SESSION["role"] === "admin"): ?>
                        <a href="admin/dashboard.php" class="btn btn-primary"><i class="fas fa-cog"></i> لوحة التحكم</a>
                    <?php endif; ?>
                    <a href="auth/logout.php" class="btn btn-danger"><i class="fas fa-sign-out-alt"></i> تسجيل الخروج</a>
                <?php else: ?>
                    <a href="auth/login.php" class="btn btn-primary"><i class="fas fa-sign-in-alt"></i> تسجيل الدخول</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <div class="prices-header">
        <h1><i class="fas fa-tag"></i> قائمة أسعار مكتبة الكلية</h1>
        <p>تصفح جميع المنتجات والمستلزمات الدراسية مع أسعارها</p>
    </div>

    <div class="container">
        <?php if (empty($categories)): ?>
            <div class="no-products">
                <i class="fas fa-box-open"></i>
                <p>لا توجد منتجات مضافة حتى الآن.</p>
            </div>
        <?php else: ?>
            <?php foreach ($categories as $category_name => $products): ?>
                <div class="category-section">
                    <div class="category-header">
                        <h2><i class="fas fa-folder-open"></i> <?php echo htmlspecialchars($category_name); ?></h2>
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
                                    <td><i class="fas fa-shopping-bag"></i> <?php echo htmlspecialchars($product['name']); ?></td>
                                    <td class="price"><?php echo htmlspecialchars($product['price']); ?> ريال</td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <script>
    // إضافة تأثيرات حركية عند التمرير
    document.addEventListener('DOMContentLoaded', function() {
        const sections = document.querySelectorAll('.category-section');
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateY(0)';
                }
            });
        }, { threshold: 0.1 });

        sections.forEach(section => {
            section.style.opacity = '0';
            section.style.transform = 'translateY(20px)';
            section.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
            observer.observe(section);
        });
    });
    </script>
</body>
</html>