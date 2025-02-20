<?php
require_once '../config.php';

if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== "admin"){
    header("location: ../auth/login.php");
    exit;
}

// التحقق من وجود معرف الرسوم
if(!isset($_GET["id"]) || empty(trim($_GET["id"]))){
    header("location: manage_faculty_fees.php");
    exit();
}

$id = trim($_GET["id"]);
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
    
    // التحقق من عدم وجود أخطاء قبل التحديث
    if(empty($faculty_name_err) && empty($fee_amount_err) && empty($academic_year_err)){
        $sql = "UPDATE college_library_fees SET faculty_name = ?, fee_amount = ?, academic_year = ?, description = ? WHERE id = ?";
        
        if($stmt = mysqli_prepare($conn, $sql)){
            mysqli_stmt_bind_param($stmt, "sdssi", $faculty_name, $fee_amount, $academic_year, $description, $id);
            
            if(mysqli_stmt_execute($stmt)){
                log_activity($_SESSION['id'], 'edit_faculty_fee', "تم تحديث سعر مكتبة الكلية: $faculty_name");
                header("location: manage_faculty_fees.php");
                exit();
            } else {
                echo "حدث خطأ ما. الرجاء المحاولة لاحقاً.";
            }
            mysqli_stmt_close($stmt);
        }
    }
} else {
    // جلب بيانات الرسوم الحالية
    $sql = "SELECT * FROM college_library_fees WHERE id = ?";
    if($stmt = mysqli_prepare($conn, $sql)){
        mysqli_stmt_bind_param($stmt, "i", $id);
        if(mysqli_stmt_execute($stmt)){
            $result = mysqli_stmt_get_result($stmt);
            if(mysqli_num_rows($result) == 1){
                $row = mysqli_fetch_array($result);
                $faculty_name = $row["faculty_name"];
                $fee_amount = $row["fee_amount"];
                $academic_year = $row["academic_year"];
                $description = $row["description"];
            } else {
                header("location: manage_faculty_fees.php");
                exit();
            }
        } else {
            echo "حدث خطأ ما. الرجاء المحاولة لاحقاً.";
        }
        mysqli_stmt_close($stmt);
    }
}
?>

<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <title>تعديل سعر مكتبة الكلية</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="wrapper">
        <h2>تعديل سعر مكتبة الكلية</h2>
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]) . '?id=' . $id; ?>" method="post">
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
                <input type="text" name="academic_year" class="form-control <?php echo (!empty($academic_year_err)) ? 'is-invalid' : ''; ?>" value="<?php echo htmlspecialchars($academic_year); ?>">
                <span class="invalid-feedback"><?php echo $academic_year_err; ?></span>
            </div>
            <div class="form-group">
                <label>الوصف</label>
                <textarea name="description" class="form-control"><?php echo htmlspecialchars($description); ?></textarea>
            </div>
            <div class="form-group">
                <input type="submit" class="btn btn-primary" value="تحديث">
                <a href="manage_faculty_fees.php" class="btn btn-secondary">إلغاء</a>
            </div>
        </form>
    </div>
</body>
</html>