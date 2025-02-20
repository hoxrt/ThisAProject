<?php
session_start();
require_once '../config.php';

if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: ../auth/login.php");
    exit;
}

if(isset($_GET["id"]) && !empty($_GET["id"])){
    $product_id = $_GET["id"];
    $user_id = $_SESSION["id"];
    
    $sql = "DELETE FROM products WHERE id = ? AND user_id = ?";
    
    if($stmt = mysqli_prepare($conn, $sql)){
        mysqli_stmt_bind_param($stmt, "ii", $product_id, $user_id);
        
        if(mysqli_stmt_execute($stmt)){
            header("location: dashboard.php");
            exit();
        } else{
            echo "حدث خطأ ما. الرجاء المحاولة لاحقاً.";
        }
        mysqli_stmt_close($stmt);
    }
}

header("location: dashboard.php");
?>