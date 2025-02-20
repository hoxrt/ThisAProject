<?php
// وظائف التحقق من الصلاحيات والأمان

function sanitize_input($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

function is_valid_price($price) {
    return is_numeric($price) && $price >= 0;
}

function validate_image($file) {
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
    $max_size = 5 * 1024 * 1024; // 5MB
    
    if (!isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
        return false;
    }
    
    if (!in_array($file['type'], $allowed_types)) {
        return false;
    }
    
    if ($file['size'] > $max_size) {
        return false;
    }
    
    return true;
}

function generate_csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf_token($token) {
    if (!isset($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

function check_rate_limit($ip, $action, $limit = 5, $time_window = 300) {
    global $conn;
    
    $sql = "DELETE FROM rate_limits WHERE timestamp < (NOW() - INTERVAL ? SECOND)";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $time_window);
    mysqli_stmt_execute($stmt);
    
    $sql = "SELECT COUNT(*) as attempts FROM rate_limits WHERE ip = ? AND action = ? AND timestamp > (NOW() - INTERVAL ? SECOND)";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "ssi", $ip, $action, $time_window);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    
    if ($row['attempts'] >= $limit) {
        return false;
    }
    
    $sql = "INSERT INTO rate_limits (ip, action, timestamp) VALUES (?, ?, NOW())";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "ss", $ip, $action);
    mysqli_stmt_execute($stmt);
    
    return true;
}

function log_activity($user_id, $action, $details = '') {
    global $conn;
    
    $sql = "INSERT INTO activity_logs (user_id, action, details, ip_address) VALUES (?, ?, ?, ?)";
    $stmt = mysqli_prepare($conn, $sql);
    $ip = $_SERVER['REMOTE_ADDR'];
    mysqli_stmt_bind_param($stmt, "isss", $user_id, $action, $details, $ip);
    mysqli_stmt_execute($stmt);
}

function validate_password($password) {
    // التحقق من قوة كلمة المرور
    if (strlen($password) < 8) {
        return false;
    }
    
    if (!preg_match("/[A-Z]/", $password)) {
        return false;
    }
    
    if (!preg_match("/[a-z]/", $password)) {
        return false;
    }
    
    if (!preg_match("/[0-9]/", $password)) {
        return false;
    }
    
    return true;
}

// إنشاء جدول rate_limits إذا لم يكن موجوداً
$sql = "CREATE TABLE IF NOT EXISTS rate_limits (
    id INT PRIMARY KEY AUTO_INCREMENT,
    ip VARCHAR(45) NOT NULL,
    action VARCHAR(50) NOT NULL,
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_ip_action (ip, action)
)";
mysqli_query($conn, $sql);

// إنشاء جدول activity_logs إذا لم يكن موجوداً
$sql = "CREATE TABLE IF NOT EXISTS activity_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    action VARCHAR(100) NOT NULL,
    details TEXT,
    ip_address VARCHAR(45) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
)";
mysqli_query($conn, $sql);