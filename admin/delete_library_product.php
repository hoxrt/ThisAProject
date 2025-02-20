<?php
session_start();
require_once '../config.php';

if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== "admin"){
    header("location: ../auth/login.php");
    exit;
}

if(isset($_GET["id"]) && !empty($_GET["id"])){
    $product_id = $_GET["id"];
    
    $sql = "DELETE FROM library_products WHERE id = ?";
    
    if($stmt = mysqli_prepare($conn, $sql)){
        mysqli_stmt_bind_param($stmt, "i", $product_id);
        
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