<?php
require_once '../config.php';

if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: ../auth/login.php");
    exit;
}

$reply_to = $_GET['reply_to'] ?? null;
$product_id = $_GET['product_id'] ?? null;

function getReceiver($conn, $user_id) {
    $sql = "SELECT username FROM users WHERE id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    return mysqli_fetch_assoc($result);
}

function getUsers($conn, $current_user_id) {
    $sql = "SELECT id, username FROM users WHERE id != ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $current_user_id);
    mysqli_stmt_execute($stmt);
    return mysqli_stmt_get_result($stmt);
}

function getUserProducts($conn, $user_id) {
    $sql = "SELECT id, title FROM products WHERE user_id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    return mysqli_stmt_get_result($stmt);
}

// إذا كانت رسالة رد، نجلب معلومات المستلم
$receiver = $reply_to ? getReceiver($conn, $reply_to) : null;

if($_SERVER["REQUEST_METHOD"] == "POST"){
    $receiver_id = $_POST['receiver_id'];
    $message = trim($_POST['message']);
    $product_id = $_POST['product_id'] ?: null;
    $sender_id = $_SESSION["id"];
    
    $sql = "INSERT INTO messages (sender_id, receiver_id, product_id, message) VALUES (?, ?, ?, ?)";
    
    if($stmt = mysqli_prepare($conn, $sql)){
        mysqli_stmt_bind_param($stmt, "iiis", $sender_id, $receiver_id, $product_id, $message);
        
        if(mysqli_stmt_execute($stmt)){
            header("location: inbox.php");
            exit();
        } else{
            echo "حدث خطأ ما. الرجاء المحاولة لاحقاً.";
        }
        mysqli_stmt_close($stmt);
    }
}

$users_result = !$reply_to ? getUsers($conn, $_SESSION["id"]) : null;
$products_result = !$product_id ? getUserProducts($conn, $_SESSION["id"]) : null;
?>

<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <title>إرسال رسالة</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="container">
        <div class="form-container">
            <h2>إرسال رسالة جديدة</h2>
            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST">
                <?php if($reply_to && isset($receiver)): ?>
                    <div class="form-group">
                        <label>إلى</label>
                        <input type="text" class="form-control" value="<?php echo htmlspecialchars($receiver['username']); ?>" readonly>
                        <input type="hidden" name="receiver_id" value="<?php echo $reply_to; ?>">
                    </div>
                <?php else: ?>
                    <div class="form-group">
                        <label>اختر المستلم</label>
                        <select name="receiver_id" class="form-control" required>
                            <?php
                            while($user = mysqli_fetch_assoc($users_result)):
                            ?>
                                <option value="<?php echo $user['id']; ?>">
                                    <?php echo htmlspecialchars($user['username']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                <?php endif; ?>

                <?php if($product_id): ?>
                    <input type="hidden" name="product_id" value="<?php echo $product_id; ?>">
                <?php else: ?>
                    <div class="form-group">
                        <label>اختر المنتج (اختياري)</label>
                        <select name="product_id" class="form-control">
                            <option value="">بدون منتج</option>
                            <?php
                            while($product = mysqli_fetch_assoc($products_result)):
                            ?>
                                <option value="<?php echo $product['id']; ?>">
                                    <?php echo htmlspecialchars($product['title']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                <?php endif; ?>

                <div class="form-group">
                    <label>الرسالة</label>
                    <textarea name="message" class="form-control" required rows="5"></textarea>
                </div>

                <div class="form-group">
                    <input type="submit" class="btn btn-primary" value="إرسال">
                    <a href="inbox.php" class="btn btn-secondary">إلغاء</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>