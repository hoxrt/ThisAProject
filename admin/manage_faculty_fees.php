<?php
require_once '../config.php';

if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== "admin"){
    header("location: ../auth/login.php");
    exit;
}

// جلب جميع رسوم مكتبة الكلية
$sql = "SELECT * FROM college_library_fees ORDER BY faculty_name, academic_year DESC";
$result = mysqli_query($conn, $sql);
?>

<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <title>إدارة أسعار مكتبة الكلية</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="wrapper">
        <h2>إدارة أسعار مكتبة الكلية</h2>
        <a href="add_faculty_fee.php" class="btn btn-success mb-3">إضافة سعر جديد</a>
        <a href="dashboard.php" class="btn btn-secondary mb-3">العودة إلى لوحة التحكم</a>
        
        <?php if(mysqli_num_rows($result) > 0): ?>
            <table class="table">
                <thead>
                    <tr>
                        <th>الكلية</th>
                        <th>المبلغ</th>
                        <th>السنة الدراسية</th>
                        <th>الوصف</th>
                        <th>تاريخ الإضافة</th>
                        <th>آخر تحديث</th>
                        <th>الإجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($row = mysqli_fetch_assoc($result)): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['faculty_name']); ?></td>
                            <td><?php echo htmlspecialchars($row['fee_amount']); ?> ريال</td>
                            <td><?php echo htmlspecialchars($row['academic_year']); ?></td>
                            <td><?php echo htmlspecialchars($row['description']); ?></td>
                            <td><?php echo htmlspecialchars($row['created_at']); ?></td>
                            <td><?php echo htmlspecialchars($row['updated_at']); ?></td>
                            <td>
                                <a href="edit_faculty_fee.php?id=<?php echo $row['id']; ?>" class="btn btn-primary btn-sm">تعديل</a>
                                <a href="delete_faculty_fee.php?id=<?php echo $row['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('هل أنت متأكد من حذف هذه الرسوم؟')">حذف</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p class="alert alert-info">لا توجد رسوم كليات مضافة حتى الآن.</p>
        <?php endif; ?>
    </div>
</body>
</html>