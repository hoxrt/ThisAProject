<?php
require_once '../config.php';

if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== "admin"){
    header("location: ../auth/login.php");
    exit;
}

$faculty_name = $fee_amount = $academic_year = $description = "";
$faculty_name_err = $fee_amount_err = $academic_year_err = "";

if($_SERVER["REQUEST_METHOD"] == "POST"){
    // التحقق من صحة البيانات
    if(empty(trim($_POST["faculty_name"]))){
        $faculty_name_err = "الرجاء إدخال اسم الكلية";
    } else {
        $faculty_name = trim($_POST["faculty_name"]);
    }
    
    if(empty(trim($_POST["fee_amount"]))){
        $fee_amount_err = "الرجاء إدخال المبلغ";
    } elseif(!is_numeric(trim($_POST["fee_amount"])) || trim($_POST["fee_amount"]) <= 0){
        $fee_amount_err = "الرجاء إدخال مبلغ صحيح";
    } else {
        $fee_amount = trim($_POST["fee_amount"]);
    }
    
    if(empty(trim($_POST["academic_year"]))){
        $academic_year_err = "الرجاء إدخال السنة الدراسية";
    } else {
        $academic_year = trim($_POST["academic_year"]);
    }
    
    $description = trim($_POST["description"]);
    
    // التحقق من عدم وجود أخطاء قبل الإدخال في قاعدة البيانات
    if(empty($faculty_name_err) && empty($fee_amount_err) && empty($academic_year_err)){
        $sql = "INSERT INTO college_library_fees (faculty_name, fee_amount, academic_year, description) VALUES (?, ?, ?, ?)";
        
        if($stmt = mysqli_prepare($conn, $sql)){
            mysqli_stmt_bind_param($stmt, "sdss", $faculty_name, $fee_amount, $academic_year, $description);
            
            if(mysqli_stmt_execute($stmt)){
                log_activity($_SESSION['id'], 'add_faculty_fee', "تمت إضافة رسوم كلية جديدة: $faculty_name");
                header("location: manage_faculty_fees.php");
                exit();
            } else {
                echo "حدث خطأ ما. الرجاء المحاولة لاحقاً.";
            }
            mysqli_stmt_close($stmt);
        }
    }
}
?>

<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <title>إضافة سعر مكتبة الكلية</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="wrapper">
        <h2>إضافة سعر جديد لمكتبة الكلية</h2>
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
            <div class="form-group">
                <label>اسم الكلية</label>
                <input type="text" name="faculty_name" class="form-control <?php echo (!empty($faculty_name_err)) ? 'is-invalid' : ''; ?>" value="<?php echo htmlspecialchars($faculty_name); ?>">
                <span class="invalid-feedback"><?php echo $faculty_name_err; ?></span>
            </div>
            <div class="form-group">
                <label>المبلغ</label>
                <input type="number" step="0.01" name="fee_amount" class="form-control <?php echo (!empty($fee_amount_err)) ? 'is-invalid' : ''; ?>" value="<?php echo htmlspecialchars($fee_amount); ?>">
                <span class="invalid-feedback"><?php echo $fee_amount_err; ?></span>
            </div>
            <div class="form-group">
                <label>السنة الدراسية</label>
                <input type="text" name="academic_year" class="form-control <?php echo (!empty($academic_year_err)) ? 'is-invalid' : ''; ?>" value="<?php echo htmlspecialchars($academic_year); ?>" placeholder="مثال: 2023-2024">
                <span class="invalid-feedback"><?php echo $academic_year_err; ?></span>
            </div>
            <div class="form-group">
                <label>الوصف</label>
                <textarea name="description" class="form-control"><?php echo htmlspecialchars($description); ?></textarea>
            </div>
            <div class="form-group">
                <input type="submit" class="btn btn-primary" value="إضافة">
                <a href="manage_faculty_fees.php" class="btn btn-secondary">إلغاء</a>
            </div>
        </form>
    </div>
</body>
</html>