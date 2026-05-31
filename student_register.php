<?php
session_start();
$conn = new mysqli("localhost", "root", "", "student_db");

if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

if (isset($_POST['register_student'])) {
    $usn = strtoupper(trim($_POST['username']));
    $name = trim($_POST['name']);
    $password = trim($_POST['password']);
    // 🆕 CAPTURE SELECTED SEMESTER FROM THE DROPDOWN
    $semester = intval($_POST['semester']); 
    
    // Check if the student USN already exists to prevent duplicate profiles
    $check = $conn->prepare("SELECT id FROM students WHERE roll_no = ?");
    $check->bind_param("s", $usn);
    $check->execute();
    if ($check->get_result()->num_rows > 0) {
        echo "<script>alert('❌ Error: This USN is already registered!'); window.history.back();</script>";
        exit();
    }
    $check->close();

    // 🛡️ DYNAMIC RULE: Insert the actual selected semester dynamically with a 'Pending' account status
    $stmt = $conn->prepare("INSERT INTO students (roll_no, name, password, semester, approval_status) VALUES (?, ?, ?, ?, 'Pending')");
    $stmt->bind_param("sssi", $usn, $name, $password, $semester);
    
    if ($stmt->execute()) {
        echo "<script>alert('🎉 Registration Successful for Semester $semester! Your profile is now visible to your semester faculty queue.'); window.location.href='index.php';</script>";
        exit();
    } else {
        echo "<script>alert('❌ Database Error. Registration failed.'); window.history.back();</script>";
    }
    $stmt->close();
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Student Registration — Academic Portal System">
    <title>Student Registration — Academic Portal</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="style.css">
</head>
<body class="register-page">

    <div class="register-card" id="student-register-card">
        <div class="register-card-header">
            <div class="icon-ring student" aria-hidden="true">🎓</div>
            <h2 class="gradient-text">Student Sign Up</h2>
            <p>Create your credentials and select your semester so faculty can activate your courses.</p>
        </div>

        <form action="student_register.php" method="POST" novalidate>
            <div class="form-group">
                <label for="reg_usn">Student USN / Roll Number</label>
                <input type="text" name="username" id="reg_usn" required
                       placeholder="e.g., 4AI24CS001" autocomplete="username">
            </div>

            <div class="form-group">
                <label for="reg_name">Full Name</label>
                <input type="text" name="name" id="reg_name" required
                       placeholder="e.g., Abhishek Kumar" autocomplete="name">
            </div>

            <div class="form-group">
                <label for="reg_semester">Current Semester</label>
                <select name="semester" id="reg_semester" required aria-required="true">
                    <option value="">— Choose Your Semester —</option>
                    <?php for($i=1; $i<=8; $i++) { echo "<option value='$i'>Semester $i</option>"; } ?>
                </select>
            </div>

            <div class="form-group">
                <label for="reg_pass">Password (Numbers Only)</label>
                <input type="text" name="password" id="reg_pass" required
                       pattern="[0-9]+" inputmode="numeric" placeholder="e.g., 12345">
            </div>

            <button type="submit" name="register_student" class="btn-submit" aria-label="Submit student registration">
                Submit Registration →
            </button>
        </form>

        <a href="index.php" class="login-link" style="display:block;text-align:center;margin-top:16px;">← Back to Login Gateway</a>
    </div>

    <script src="portal.js"></script>
</body>
</html>