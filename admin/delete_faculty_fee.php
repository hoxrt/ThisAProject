<?php
require_once '../config.php';

if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== "admin"){
    header("location: ../auth/login.php");
    exit;
}

if(!isset($_GET["id"]) || empty(trim($_GET["id"]))){
    header("location: manage_faculty_fees.php");
    exit();
}

$id = trim($_GET["id"]);

// جلب اسم الكلية قبل الحذف للتسجيل في السجلات
$sql = "SELECT faculty_name FROM college_library_fees WHERE id = ?";
if($stmt = mysqli_prepare($conn, $sql)){
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);
    mysqli_stmt_bind_result($stmt, $faculty_name);
    mysqli_stmt_fetch($stmt);
    mysqli_stmt_close($stmt);
}

// حذف السعر
$sql = "DELETE FROM college_library_fees WHERE id = ?";
if($stmt = mysqli_prepare($conn, $sql)){
    mysqli_stmt_bind_param($stmt, "i", $id);
    if(mysqli_stmt_execute($stmt)){
        log_activity($_SESSION['id'], 'delete_faculty_fee', "تم حذف سعر مكتبة الكلية: $faculty_name");
        header("location: manage_faculty_fees.php");
        exit();
    } else {
        echo "حدث خطأ ما. الرجاء المحاولة لاحقاً.";
    }
    mysqli_stmt_close($stmt);
}
?>