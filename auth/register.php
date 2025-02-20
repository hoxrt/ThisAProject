<?php
require_once '../config.php';

// التحقق من عدم تسجيل الدخول مسبقاً
if(isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true){
    header("location: ../index.php");
    exit;
}

// Remove redundant session_start() since it's already called in config.php

$username = $password = $confirm_password = $email = "";
$username_err = $password_err = $confirm_password_err = $email_err = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // التحقق من رمز CSRF
    if (!verify_csrf_token($_POST['csrf_token'])) {
        die("خطأ في التحقق من صحة الطلب");
    }

    // التحقق من حد محاولات التسجيل
    if (!check_rate_limit($_SERVER['REMOTE_ADDR'], 'register', 3, 3600)) {
        die("تم تجاوز الحد المسموح من محاولات التسجيل. الرجاء المحاولة بعد ساعة");
    }

    // التحقق من اسم المستخدم
    if (empty(trim($_POST["username"]))) {
        $username_err = "الرجاء إدخال اسم المستخدم";
    } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', trim($_POST["username"]))) {
        $username_err = "اسم المستخدم يجب أن يحتوي على حروف وأرقام وشرطة سفلية فقط";
    } else {
        $sql = "SELECT id FROM users WHERE username = ?";
        if ($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "s", $param_username);
            $param_username = trim($_POST["username"]);
            if (mysqli_stmt_execute($stmt)) {
                mysqli_stmt_store_result($stmt);
                if (mysqli_stmt_num_rows($stmt) == 1) {
                    $username_err = "اسم المستخدم مستخدم بالفعل";
                } else {
                    $username = sanitize_input($_POST["username"]);
                }
            }
            mysqli_stmt_close($stmt);
        }
    }

    // التحقق من البريد الإلكتروني
    if (empty(trim($_POST["email"]))) {
        $email_err = "الرجاء إدخال البريد الإلكتروني";
    } elseif (!filter_var($_POST["email"], FILTER_VALIDATE_EMAIL)) {
        $email_err = "صيغة البريد الإلكتروني غير صحيحة";
    } else {
        $sql = "SELECT id FROM users WHERE email = ?";
        if ($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "s", $param_email);
            $param_email = trim($_POST["email"]);
            if (mysqli_stmt_execute($stmt)) {
                mysqli_stmt_store_result($stmt);
                if (mysqli_stmt_num_rows($stmt) == 1) {
                    $email_err = "البريد الإلكتروني مستخدم بالفعل";
                } else {
                    $email = sanitize_input($_POST["email"]);
                }
            }
            mysqli_stmt_close($stmt);
        }
    }

    // التحقق من كلمة المرور
    if (empty(trim($_POST["password"]))) {
        $password_err = "الرجاء إدخال كلمة المرور";
    } elseif (!validate_password(trim($_POST["password"]))) {
        $password_err = "كلمة المرور يجب أن تحتوي على 8 أحرف على الأقل، وحرف كبير، وحرف صغير، ورقم";
    } else {
        $password = trim($_POST["password"]);
    }

    // التحقق من تأكيد كلمة المرور
    if (empty(trim($_POST["confirm_password"]))) {
        $confirm_password_err = "الرجاء تأكيد كلمة المرور";
    } else {
        $confirm_password = trim($_POST["confirm_password"]);
        if (empty($password_err) && ($password != $confirm_password)) {
            $confirm_password_err = "كلمتا المرور غير متطابقتين";
        }
    }

    // التحقق من عدم وجود أخطاء قبل الإدخال في قاعدة البيانات
    if (empty($username_err) && empty($password_err) && empty($confirm_password_err) && empty($email_err)) {
        $sql = "INSERT INTO users (username, password, email, verification_token, email_verified) VALUES (?, ?, ?, ?, ?)";
        if ($stmt = mysqli_prepare($conn, $sql)) {
            // تحضير المتغيرات
            $verification_token = bin2hex(random_bytes(32));
            $email_verified = 1;
            
            // تشفير كلمة المرور باستخدام PASSWORD_DEFAULT
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // طباعة قيم التشخيص - يجب إزالتها في الإنتاج
            error_log("Registration - Username: " . $username);
            error_log("Registration - Password hash: " . $hashed_password);
            
            mysqli_stmt_bind_param($stmt, "ssssi", 
                $username,
                $hashed_password,
                $email,
                $verification_token,
                $email_verified
            );
            
            if (mysqli_stmt_execute($stmt)) {
                $user_id = mysqli_insert_id($conn);
                log_activity($user_id, 'register', 'تم إنشاء حساب جديد');
                header("location: login.php");
                exit();
            } else {
                error_log("Registration error: " . mysqli_error($conn));
                echo "حدث خطأ ما. الرجاء المحاولة لاحقاً.";
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
    <title>تسجيل حساب جديد</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="auth-container">
        <h2>تسجيل حساب جديد</h2>
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" autocomplete="off">
            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
            <div class="form-group">
                <label>اسم المستخدم</label>
                <input type="text" name="username" class="form-control <?php echo (!empty($username_err)) ? 'is-invalid' : ''; ?>" 
                       value="<?php echo htmlspecialchars($username); ?>">
                <span class="invalid-feedback"><?php echo $username_err; ?></span>
            </div>
            <div class="form-group">
                <label>البريد الإلكتروني</label>
                <input type="email" name="email" class="form-control <?php echo (!empty($email_err)) ? 'is-invalid' : ''; ?>" 
                       value="<?php echo htmlspecialchars($email); ?>">
                <span class="invalid-feedback"><?php echo $email_err; ?></span>
            </div>
            <div class="form-group">
                <label>كلمة المرور</label>
                <input type="password" name="password" class="form-control <?php echo (!empty($password_err)) ? 'is-invalid' : ''; ?>">
                <span class="invalid-feedback"><?php echo $password_err; ?></span>
            </div>
            <div class="form-group">
                <label>تأكيد كلمة المرور</label>
                <input type="password" name="confirm_password" class="form-control <?php echo (!empty($confirm_password_err)) ? 'is-invalid' : ''; ?>">
                <span class="invalid-feedback"><?php echo $confirm_password_err; ?></span>
            </div>
            <div class="form-group">
                <input type="submit" class="btn btn-primary" value="تسجيل">
                <input type="reset" class="btn btn-secondary" value="إعادة تعيين">
            </div>
            <p>هل لديك حساب بالفعل؟ <a href="login.php">تسجيل الدخول</a></p>
        </form>
    </div>

    <script>
    // إضافة تحقق من قوة كلمة المرور في جانب العميل
    document.querySelector('input[name="password"]').addEventListener('input', function(e) {
        const password = e.target.value;
        const strength = {
            length: password.length >= 8,
            hasUpper: /[A-Z]/.test(password),
            hasLower: /[a-z]/.test(password),
            hasNumber: /[0-9]/.test(password)
        };
        
        let feedback = [];
        if (!strength.length) feedback.push("8 أحرف على الأقل");
        if (!strength.hasUpper) feedback.push("حرف كبير");
        if (!strength.hasLower) feedback.push("حرف صغير");
        if (!strength.hasNumber) feedback.push("رقم");
        
        const feedbackEl = this.nextElementSibling;
        if (feedback.length > 0) {
            feedbackEl.textContent = "كلمة المرور يجب أن تحتوي على: " + feedback.join("، ");
            feedbackEl.style.display = 'block';
        } else {
            feedbackEl.style.display = 'none';
        }
    });
    </script>
</body>
</html>