<?php
require_once '../config.php';
 
// حذف جميع متغيرات الجلسة
$_SESSION = array();
 
// تدمير الجلسة
session_destroy();
 
// إعادة التوجيه إلى صفحة تسجيل الدخول
header("location: login.php");
exit;
?>