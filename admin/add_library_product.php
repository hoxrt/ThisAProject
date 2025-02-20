<?php
// Remove session_start() since it's already in config.php
require_once '../config.php';

if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== "admin"){
    header("location: ../auth/login.php");
    exit;
}

if($_SERVER["REQUEST_METHOD"] == "POST"){
    $name = trim($_POST["name"]);
    $price = trim($_POST["price"]);
    $category_id = trim($_POST["category_id"]);
    
    $sql = "INSERT INTO library_products (name, price, category_id) VALUES (?, ?, ?)";
    
    if($stmt = mysqli_prepare($conn, $sql)){
        mysqli_stmt_bind_param($stmt, "sdi", $name, $price, $category_id);
        
        if(mysqli_stmt_execute($stmt)){
            mysqli_stmt_close($stmt);
            header("location: dashboard.php");
            exit();
        } else{
            echo "حدث خطأ ما. الرجاء المحاولة لاحقاً.";
        }
        mysqli_stmt_close($stmt);
    }
}
// Remove redundant header redirect here
?>