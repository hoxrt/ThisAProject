<?php
session_start();
require_once '../config.php';

if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: ../auth/login.php");
    exit;
}

try {
    if($_SERVER["REQUEST_METHOD"] == "POST"){
        // التحقق من البيانات
        if(empty(trim($_POST["title"])) || empty(trim($_POST["description"])) || 
           empty(trim($_POST["price"])) || empty(trim($_POST["category_id"])) || 
           empty(trim($_POST["condition_status"]))) {
            throw new Exception("جميع الحقول مطلوبة");
        }

        $title = sanitize_input($_POST["title"]);
        $description = sanitize_input($_POST["description"]);
        $price = filter_var(trim($_POST["price"]), FILTER_VALIDATE_FLOAT);
        $category_id = filter_var(trim($_POST["category_id"]), FILTER_VALIDATE_INT);
        $condition_status = trim($_POST["condition_status"]);
        $user_id = $_SESSION["id"];
        
        if($price === false || $price < 0) {
            throw new Exception("السعر غير صالح");
        }

        if($category_id === false) {
            throw new Exception("الفئة غير صالحة");
        }

        if(!in_array($condition_status, ['new', 'used'])) {
            throw new Exception("حالة المنتج غير صالحة");
        }

        $sql = "INSERT INTO products (title, description, price, category_id, user_id, condition_status) 
                VALUES (?, ?, ?, ?, ?, ?)";
        
        if($stmt = mysqli_prepare($conn, $sql)){
            mysqli_stmt_bind_param($stmt, "ssdiis", $title, $description, $price, $category_id, $user_id, $condition_status);
            
            if(!mysqli_stmt_execute($stmt)){
                throw new Exception("حدث خطأ في إضافة المنتج");
            }

            log_activity($user_id, 'add_product', "تمت إضافة منتج جديد: $title");
            header("location: dashboard.php");
            exit();
        }
        mysqli_stmt_close($stmt);
    }
} catch (Exception $e) {
    $_SESSION['error_message'] = $e->getMessage();
    header("location: dashboard.php");
    exit();
}

header("location: dashboard.php");
?>