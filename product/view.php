<?php
session_start();
require_once '../config.php';

if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: ../auth/login.php");
    exit;
}

$product_id = $_GET['id'] ?? 0;

// جلب تفاصيل المنتج
$sql = "SELECT p.*, c.name as category_name, u.username as seller_name 
        FROM products p 
        LEFT JOIN categories c ON p.category_id = c.id 
        LEFT JOIN users u ON p.user_id = u.id 
        WHERE p.id = ?";

if($stmt = mysqli_prepare($conn, $sql)){
    mysqli_stmt_bind_param($stmt, "i", $product_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $product = mysqli_fetch_assoc($result);
    
    if(!$product){
        header("location: ../index.php");
        exit();
    }
}

// جلب سعر المنتج في المكتبة للمقارنة
$library_sql = "SELECT * FROM library_products WHERE category_id = ? AND name LIKE ?";
if($stmt = mysqli_prepare($conn, $library_sql)){
    $search_name = "%" . $product['title'] . "%";
    mysqli_stmt_bind_param($stmt, "is", $product['category_id'], $search_name);
    mysqli_stmt_execute($stmt);
    $library_result = mysqli_stmt_get_result($stmt);
    $library_product = mysqli_fetch_assoc($library_result);
}

// إضافة تعليق جديد
if($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['comment'])){
    $comment = trim($_POST['comment']);
    $user_id = $_SESSION['id'];
    
    $sql = "INSERT INTO comments (product_id, user_id, comment) VALUES (?, ?, ?)";
    if($stmt = mysqli_prepare($conn, $sql)){
        mysqli_stmt_bind_param($stmt, "iis", $product_id, $user_id, $comment);
        mysqli_stmt_execute($stmt);
    }
}

// جلب التعليقات
$comments_sql = "SELECT c.*, u.username 
                FROM comments c 
                JOIN users u ON c.user_id = u.id 
                WHERE c.product_id = ? 
                ORDER BY c.created_at DESC";
$stmt = mysqli_prepare($conn, $comments_sql);
mysqli_stmt_bind_param($stmt, "i", $product_id);
mysqli_stmt_execute($stmt);
$comments_result = mysqli_stmt_get_result($stmt);
?>

<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($product['title']); ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <nav class="navbar">
        <div class="navbar-container">
            <a href="../index.php" class="btn btn-secondary">عودة للرئيسية</a>
        </div>
    </nav>

    <div class="container">
        <div class="product-details">
            <h1><?php echo htmlspecialchars($product['title']); ?></h1>
            <div class="product-info">
                <p class="description"><?php echo nl2br(htmlspecialchars($product['description'])); ?></p>
                <p class="price">السعر: <?php echo htmlspecialchars($product['price']); ?> ريال</p>
                <p class="category">الفئة: <?php echo htmlspecialchars($product['category_name']); ?></p>
                <p class="seller">البائع: <?php echo htmlspecialchars($product['seller_name']); ?></p>
                <p class="condition">الحالة: <?php echo $product['condition_status'] === 'new' ? 'جديد' : 'مستعمل'; ?></p>
                <p class="status">حالة البيع: <?php echo $product['status'] === 'available' ? 'متاح' : 'تم البيع'; ?></p>
                
                <?php if($library_product): ?>
                    <div class="price-comparison">
                        <h3>سعر المنتج في مكتبة الكلية</h3>
                        <p>السعر: <?php echo htmlspecialchars($library_product['price']); ?> ريال</p>
                        <p class="savings">التوفير: <?php echo $library_product['price'] - $product['price']; ?> ريال</p>
                    </div>
                <?php endif; ?>

                <?php if($product['status'] === 'available' && $product['user_id'] !== $_SESSION['id']): ?>
                    <a href="../messages/send.php?reply_to=<?php echo $product['user_id']; ?>&product_id=<?php echo $product['id']; ?>" 
                       class="btn btn-primary">تواصل مع البائع</a>
                <?php endif; ?>
            </div>
        </div>

        <div class="comments-section">
            <h2>التعليقات</h2>
            
            <form action="" method="POST" class="comment-form">
                <div class="form-group">
                    <textarea name="comment" class="form-control" required placeholder="أضف تعليقك هنا"></textarea>
                </div>
                <div class="form-group">
                    <input type="submit" class="btn btn-primary" value="إضافة تعليق">
                </div>
            </form>

            <div class="comments-list">
                <?php while($comment = mysqli_fetch_assoc($comments_result)): ?>
                    <div class="comment">
                        <div class="comment-header">
                            <span class="username"><?php echo htmlspecialchars($comment['username']); ?></span>
                            <span class="date"><?php echo htmlspecialchars($comment['created_at']); ?></span>
                        </div>
                        <div class="comment-content">
                            <?php echo nl2br(htmlspecialchars($comment['comment'])); ?>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        </div>
    </div>
</body>
</html>