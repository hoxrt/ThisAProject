<?php
// Check if session is already started
if (session_status() === PHP_SESSION_NONE) {
    // Configure session settings before starting the session
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_secure', 0);
    
    session_start();
}

// Configure error reporting
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Set timezone
date_default_timezone_set('Asia/Riyadh');

// Database connection settings
define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'root');
define('DB_PASSWORD', '');
define('DB_NAME', 'Examstu');

try {
    // محاولة الاتصال بقاعدة البيانات
    $conn = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD);
    
    if (!$conn) {
        throw new Exception("فشل الاتصال بقاعدة البيانات: " . mysqli_connect_error());
    }

    // تعيين charset للاتصال
    mysqli_set_charset($conn, "utf8mb4");

    // إنشاء قاعدة البيانات إذا لم تكن موجودة
    $sql = "CREATE DATABASE IF NOT EXISTS " . DB_NAME . " CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
    if (!mysqli_query($conn, $sql)) {
        throw new Exception("خطأ في إنشاء قاعدة البيانات: " . mysqli_error($conn));
    }

    // اختيار قاعدة البيانات
    if (!mysqli_select_db($conn, DB_NAME)) {
        throw new Exception("خطأ في اختيار قاعدة البيانات: " . mysqli_error($conn));
    }

    // إنشاء جدول college_library_fees إذا لم يكن موجوداً
    $sql = "CREATE TABLE IF NOT EXISTS college_library_fees (
        id INT PRIMARY KEY AUTO_INCREMENT,
        faculty_name VARCHAR(100) NOT NULL,
        fee_amount DECIMAL(10,2) NOT NULL,
        academic_year VARCHAR(20) NOT NULL,
        description TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    
    if (!mysqli_query($conn, $sql)) {
        throw new Exception("خطأ في إنشاء جدول college_library_fees: " . mysqli_error($conn));
    }

    // التحقق من وجود الجداول
    $result = mysqli_query($conn, "SHOW TABLES");
    $tables_exist = mysqli_num_rows($result) > 0;

    // إنشاء الجداول فقط إذا لم تكن موجودة
    if (!$tables_exist) {
        $tables = [
            "CREATE TABLE IF NOT EXISTS categories (
                id INT PRIMARY KEY AUTO_INCREMENT,
                name VARCHAR(50) NOT NULL,
                description TEXT
            )",
            
            "CREATE TABLE IF NOT EXISTS users (
                id INT PRIMARY KEY AUTO_INCREMENT,
                username VARCHAR(50) UNIQUE NOT NULL,
                password VARCHAR(255) NOT NULL,
                email VARCHAR(100) UNIQUE NOT NULL,
                role ENUM('student', 'admin') NOT NULL DEFAULT 'student',
                email_verified TINYINT(1) DEFAULT 0,
                verification_token VARCHAR(64),
                password_reset_token VARCHAR(64),
                password_reset_expires DATETIME,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )",
            
            "CREATE TABLE IF NOT EXISTS products (
                id INT PRIMARY KEY AUTO_INCREMENT,
                title VARCHAR(100) NOT NULL,
                description TEXT,
                price DECIMAL(10,2) NOT NULL,
                category_id INT,
                user_id INT,
                condition_status ENUM('new', 'used') NOT NULL,
                status ENUM('available', 'sold') DEFAULT 'available',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            )",
            
            "CREATE TABLE IF NOT EXISTS library_products (
                id INT PRIMARY KEY AUTO_INCREMENT,
                name VARCHAR(100) NOT NULL,
                price DECIMAL(10,2) NOT NULL,
                category_id INT,
                FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
            )",
            
            "CREATE TABLE IF NOT EXISTS messages (
                id INT PRIMARY KEY AUTO_INCREMENT,
                sender_id INT,
                receiver_id INT,
                product_id INT,
                message TEXT NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
                FOREIGN KEY (receiver_id) REFERENCES users(id) ON DELETE CASCADE,
                FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL
            )",
            
            "CREATE TABLE IF NOT EXISTS comments (
                id INT PRIMARY KEY AUTO_INCREMENT,
                product_id INT,
                user_id INT,
                comment TEXT NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            )",

            "CREATE TABLE IF NOT EXISTS rate_limits (
                id INT PRIMARY KEY AUTO_INCREMENT,
                ip VARCHAR(45) NOT NULL,
                action VARCHAR(50) NOT NULL,
                timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_ip_action (ip, action)
            )",

            "CREATE TABLE IF NOT EXISTS activity_logs (
                id INT PRIMARY KEY AUTO_INCREMENT,
                user_id INT,
                action VARCHAR(100) NOT NULL,
                details TEXT,
                ip_address VARCHAR(45) NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
            )"
        ];

        foreach ($tables as $sql) {
            if (!mysqli_query($conn, $sql)) {
                throw new Exception("خطأ في إنشاء الجداول: " . mysqli_error($conn));
            }
        }

        // إدخال البيانات الافتراضية فقط إذا تم إنشاء الجداول للتو
        $categories = [
            "('مذكرات دراسية', 'مذكرات ومحاضرات للمواد الدراسية')",
            "('أقلام', 'أقلام حبر وأقلام رصاص')",
            "('دفاتر', 'دفاتر للكتابة والرسم')",
            "('كتب', 'كتب دراسية ومراجع')",
            "('أدوات هندسية', 'مساطر وأدوات هندسية')",
            "('لوازم مكتبية', 'لوازم مكتبية متنوعة')"
        ];

        foreach ($categories as $category) {
            $sql = "INSERT INTO categories (name, description) VALUES " . $category;
            mysqli_query($conn, $sql);
        }

        // إضافة حساب المسؤول الافتراضي فقط إذا لم يكن موجوداً
        $check_admin = mysqli_query($conn, "SELECT id FROM users WHERE username = 'admin'");
        if (mysqli_num_rows($check_admin) == 0) {
            $admin_sql = "INSERT INTO users (username, password, email, role, email_verified) 
                        VALUES ('admin', ?, 'admin@example.com', 'admin', 1)";
            $admin_stmt = mysqli_prepare($conn, $admin_sql);
            $admin_password = password_hash('Admin123', PASSWORD_DEFAULT);
            mysqli_stmt_bind_param($admin_stmt, "s", $admin_password);
            mysqli_stmt_execute($admin_stmt);
        }
    }

} catch (Exception $e) {
    error_log($e->getMessage());
    die("حدث خطأ في إعداد قاعدة البيانات: " . $e->getMessage());
}

// تضمين ملف الأمان
require_once __DIR__ . '/includes/security.php';

// دوال المساعدة
function handle_error($message, $error_details = '') {
    error_log($error_details);
    die($message);
}

function verify_session() {
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 1800)) {
        session_unset();
        session_destroy();
        header("location: /auth/login.php");
        exit();
    }
    $_SESSION['last_activity'] = time();
}

// تحديث نشاط الجلسة
if (isset($_SESSION["loggedin"])) {
    verify_session();
}
?>