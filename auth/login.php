<?php
require_once '../config.php';

if(isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true){
    header("location: ../index.php");
    exit;
}

$username = $password = "";
$username_err = $password_err = $login_err = "";

if($_SERVER["REQUEST_METHOD"] == "POST"){
    if (!verify_csrf_token($_POST['csrf_token'])) {
        die("خطأ في التحقق من صحة الطلب");
    }

    if (!check_rate_limit($_SERVER['REMOTE_ADDR'], 'login', 5, 300)) {
        $login_err = "تم تجاوز الحد المسموح من محاولات تسجيل الدخول. الرجاء المحاولة بعد 5 دقائق";
    } else {
        $username = trim($_POST["username"]);
        $password = trim($_POST["password"]);
        
        if(empty($username)){
            $username_err = "الرجاء إدخال اسم المستخدم";
        }
        
        if(empty($password)){
            $password_err = "الرجاء إدخال كلمة المرور";
        }
        
        if(empty($username_err) && empty($password_err)){
            $sql = "SELECT id, username, password, role, email_verified FROM users WHERE username = ?";
            
            if($stmt = mysqli_prepare($conn, $sql)){
                mysqli_stmt_bind_param($stmt, "s", $username);
                
                if(mysqli_stmt_execute($stmt)){
                    mysqli_stmt_store_result($stmt);
                    
                    if(mysqli_stmt_num_rows($stmt) == 1){
                        mysqli_stmt_bind_result($stmt, $id, $db_username, $hashed_password, $role, $email_verified);
                        if(mysqli_stmt_fetch($stmt)){
                            // For debugging - remove in production
                            error_log("Login attempt for user: " . $username);
                            error_log("Password verification result: " . (password_verify($password, $hashed_password) ? "true" : "false"));
                            error_log("Email verified status: " . $email_verified);
                            
                            if(password_verify($password, $hashed_password)){
                                session_regenerate_id(true);
                                
                                $_SESSION["loggedin"] = true;
                                $_SESSION["id"] = $id;
                                $_SESSION["username"] = $db_username;
                                $_SESSION["role"] = $role;
                                $_SESSION['last_activity'] = time();
                                
                                log_activity($id, 'login', 'تسجيل دخول ناجح');
                                
                                if($role === "admin"){
                                    header("location: ../admin/dashboard.php");
                                    exit();
                                } else {
                                    header("location: ../index.php");
                                    exit();
                                }
                            } else {
                                error_log("Password mismatch for user: " . $username);
                                $login_err = "اسم المستخدم أو كلمة المرور غير صحيحة";
                                log_activity(0, 'failed_login', "محاولة تسجيل دخول فاشلة لاسم المستخدم: $username");
                            }
                        }
                    } else {
                        error_log("User not found: " . $username);
                        $login_err = "اسم المستخدم أو كلمة المرور غير صحيحة";
                        log_activity(0, 'failed_login', "محاولة تسجيل دخول فاشلة لاسم المستخدم: $username");
                    }
                } else {
                    error_log("SQL execution error: " . mysqli_error($conn));
                    $login_err = "حدث خطأ في تنفيذ الاستعلام";
                    log_activity(0, 'error', "خطأ في تنفيذ استعلام تسجيل الدخول");
                }
                mysqli_stmt_close($stmt);
            }
        }
    }
}

$csrf_token = generate_csrf_token();
?>

<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <title>تسجيل الدخول</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="auth-container">
        <h2>تسجيل الدخول</h2>
        <?php 
        if(!empty($login_err)){
            echo '<div class="alert alert-danger">' . htmlspecialchars($login_err) . '</div>';
        }        
        ?>
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
            <div class="form-group">
                <label>اسم المستخدم</label>
                <input type="text" name="username" class="form-control <?php echo (!empty($username_err)) ? 'is-invalid' : ''; ?>" 
                       value="<?php echo htmlspecialchars($username); ?>" autocomplete="username">
                <span class="invalid-feedback"><?php echo $username_err; ?></span>
            </div>    
            <div class="form-group">
                <label>كلمة المرور</label>
                <input type="password" name="password" class="form-control <?php echo (!empty($password_err)) ? 'is-invalid' : ''; ?>"
                       autocomplete="current-password">
                <span class="invalid-feedback"><?php echo $password_err; ?></span>
            </div>
            <div class="form-group">
                <input type="submit" class="btn btn-primary" value="تسجيل الدخول">
            </div>
            <p>ليس لديك حساب؟ <a href="register.php">سجل الآن</a></p>
            <p><a href="forgot_password.php">نسيت كلمة المرور؟</a></p>
        </form>
    </div>
</body>
</html>