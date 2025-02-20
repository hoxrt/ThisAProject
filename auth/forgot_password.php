<?php
require_once '../config.php';

$email = $email_err = $success = "";

if($_SERVER["REQUEST_METHOD"] == "POST"){
    // التحقق من رمز CSRF
    if (!verify_csrf_token($_POST['csrf_token'])) {
        die("خطأ في التحقق من صحة الطلب");
    }

    if(empty(trim($_POST["email"]))){
        $email_err = "الرجاء إدخال البريد الإلكتروني";
    } else{
        $email = filter_var(trim($_POST["email"]), FILTER_SANITIZE_EMAIL);
        if(!filter_var($email, FILTER_VALIDATE_EMAIL)){
            $email_err = "صيغة البريد الإلكتروني غير صحيحة";
        }
    }
    
    if(empty($email_err)){
        $sql = "SELECT id FROM users WHERE email = ? AND email_verified = 1";
        
        if($stmt = mysqli_prepare($conn, $sql)){
            mysqli_stmt_bind_param($stmt, "s", $param_email);
            $param_email = $email;
            
            if(mysqli_stmt_execute($stmt)){
                mysqli_stmt_store_result($stmt);
                
                if(mysqli_stmt_num_rows($stmt) == 1){
                    $token = bin2hex(random_bytes(32));
                    $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
                    
                    $sql = "UPDATE users SET password_reset_token = ?, password_reset_expires = ? WHERE email = ?";
                    if($update_stmt = mysqli_prepare($conn, $sql)){
                        mysqli_stmt_bind_param($update_stmt, "sss", $token, $expires, $email);
                        
                        if(mysqli_stmt_execute($update_stmt)){
                            // في بيئة الإنتاج، أرسل رابط إعادة تعيين كلمة المرور عبر البريد
                            // send_reset_email($email, $token);
                            
                            $success = "تم إرسال رابط إعادة تعيين كلمة المرور إلى بريدك الإلكتروني";
                        }
                        mysqli_stmt_close($update_stmt);
                    }
                } else{
                    $email_err = "لم يتم العثور على حساب بهذا البريد الإلكتروني";
                }
            }
            mysqli_stmt_close($stmt);
        }
    }
}

$csrf_token = generate_csrf_token();
?>

<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <title>نسيت كلمة المرور</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="auth-container">
        <h2>نسيت كلمة المرور</h2>
        <?php 
        if(!empty($success)){
            echo '<div class="alert alert-success">' . htmlspecialchars($success) . '</div>';
        }
        ?>
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
            <div class="form-group">
                <label>البريد الإلكتروني</label>
                <input type="email" name="email" class="form-control <?php echo (!empty($email_err)) ? 'is-invalid' : ''; ?>" 
                       value="<?php echo htmlspecialchars($email); ?>">
                <span class="invalid-feedback"><?php echo $email_err; ?></span>
            </div>
            <div class="form-group">
                <input type="submit" class="btn btn-primary" value="إرسال رابط إعادة التعيين">
            </div>
            <p><a href="login.php">العودة إلى تسجيل الدخول</a></p>
        </form>
    </div>
</body>
</html>