<?php
require_once '../config.php';

$token = $_GET['token'] ?? '';
$message = '';

if (!empty($token)) {
    $sql = "SELECT id FROM users WHERE verification_token = ? AND email_verified = 0";
    if ($stmt = mysqli_prepare($conn, $sql)) {
        mysqli_stmt_bind_param($stmt, "s", $token);
        
        if (mysqli_stmt_execute($stmt)) {
            mysqli_stmt_store_result($stmt);
            
            if (mysqli_stmt_num_rows($stmt) == 1) {
                // تحديث حالة التحقق من البريد الإلكتروني
                $update_sql = "UPDATE users SET email_verified = 1, verification_token = NULL WHERE verification_token = ?";
                if ($update_stmt = mysqli_prepare($conn, $update_sql)) {
                    mysqli_stmt_bind_param($update_stmt, "s", $token);
                    if (mysqli_stmt_execute($update_stmt)) {
                        $message = "تم تفعيل حسابك بنجاح! يمكنك الآن تسجيل الدخول.";
                    } else {
                        $message = "عذراً، حدث خطأ أثناء تفعيل حسابك.";
                    }
                }
            } else {
                $message = "رمز التحقق غير صالح أو تم التحقق من الحساب مسبقاً.";
            }
        } else {
            $message = "عذراً، حدث خطأ ما. الرجاء المحاولة لاحقاً.";
        }
        mysqli_stmt_close($stmt);
    }
} else {
    $message = "رمز التحقق مطلوب.";
}
?>

<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <title>تفعيل الحساب</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="auth-container">
        <h2>تفعيل الحساب</h2>
        <div class="message">
            <?php echo htmlspecialchars($message); ?>
        </div>
        <div class="links">
            <a href="login.php" class="btn btn-primary">تسجيل الدخول</a>
        </div>
    </div>
</body>
</html>