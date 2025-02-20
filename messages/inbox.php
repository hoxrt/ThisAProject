<?php
require_once '../config.php';

if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: ../auth/login.php");
    exit;
}

$user_id = $_SESSION["id"];

function getMessages($conn, $sql, $user_id) {
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    return $result;
}

// جلب الرسائل المستلمة
$inbox_sql = "SELECT m.*, u.username as sender_name, p.title as product_title 
              FROM messages m 
              JOIN users u ON m.sender_id = u.id 
              LEFT JOIN products p ON m.product_id = p.id 
              WHERE m.receiver_id = ? 
              ORDER BY m.created_at DESC";

// جلب الرسائل المرسلة
$sent_sql = "SELECT m.*, u.username as receiver_name, p.title as product_title 
             FROM messages m 
             JOIN users u ON m.receiver_id = u.id 
             LEFT JOIN products p ON m.product_id = p.id 
             WHERE m.sender_id = ? 
             ORDER BY m.created_at DESC";

$inbox_result = getMessages($conn, $inbox_sql, $user_id);
$sent_result = getMessages($conn, $sent_sql, $user_id);
?>

<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <title>صندوق الرسائل</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <nav class="navbar">
        <div class="navbar-container">
            <h1>صندوق الرسائل</h1>
            <div class="nav-links">
                <a href="../index.php" class="btn btn-secondary">الصفحة الرئيسية</a>
                <a href="../user/dashboard.php" class="btn btn-primary">لوحة التحكم</a>
            </div>
        </div>
    </nav>

    <div class="container">
        <div class="messages-container">
            <h2>الرسائل الواردة</h2>
            <div class="messages-list">
                <?php if(mysqli_num_rows($inbox_result) > 0): ?>
                    <?php while($message = mysqli_fetch_assoc($inbox_result)): ?>
                        <div class="message">
                            <div class="message-header">
                                <span class="sender">من: <?php echo htmlspecialchars($message['sender_name']); ?></span>
                                <span class="date"><?php echo htmlspecialchars($message['created_at']); ?></span>
                            </div>
                            <?php if($message['product_title']): ?>
                                <div class="product-info">
                                    بخصوص: <?php echo htmlspecialchars($message['product_title']); ?>
                                </div>
                            <?php endif; ?>
                            <div class="message-content">
                                <?php echo htmlspecialchars($message['message']); ?>
                            </div>
                            <div class="message-actions">
                                <a href="send.php?reply_to=<?php echo $message['sender_id']; ?>&product_id=<?php echo $message['product_id']; ?>" 
                                   class="btn btn-primary">رد</a>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p class="no-messages">لا توجد رسائل واردة</p>
                <?php endif; ?>
            </div>

            <h2>الرسائل المرسلة</h2>
            <div class="messages-list">
                <?php if(mysqli_num_rows($sent_result) > 0): ?>
                    <?php while($message = mysqli_fetch_assoc($sent_result)): ?>
                        <div class="message">
                            <div class="message-header">
                                <span class="receiver">إلى: <?php echo htmlspecialchars($message['receiver_name']); ?></span>
                                <span class="date"><?php echo htmlspecialchars($message['created_at']); ?></span>
                            </div>
                            <?php if($message['product_title']): ?>
                                <div class="product-info">
                                    بخصوص: <?php echo htmlspecialchars($message['product_title']); ?>
                                </div>
                            <?php endif; ?>
                            <div class="message-content">
                                <?php echo htmlspecialchars($message['message']); ?>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p class="no-messages">لا توجد رسائل مرسلة</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>