<?php
session_start();
$conn = new mysqli("localhost", "root", "", "student_db");

if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

if (isset($_POST['register_teacher'])) {
    $custom_id = strtoupper(trim($_POST['teacher_id'])); 
    $name = trim($_POST['name']);
    $dept = $_POST['dept'];               
    $subject = trim($_POST['subject']);   
    $pass = $_POST['password'];
    $semester = intval($_POST['semester']); 
    
    if (!str_contains($custom_id, $dept)) {
        echo "<script>alert('[ERROR] Department Mismatch! Your Teacher ID must include your selected department code ($dept).'); window.history.back();</script>";
        exit();
    }
    
    $photo_path = NULL;
    if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] === UPLOAD_ERR_OK) {
        $file_tmp = $_FILES['profile_photo']['tmp_name'];
        $file_name = $_FILES['profile_photo']['name'];
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        $new_file_name = $custom_id . "_" . time() . "." . $file_ext;
        $destination = "uploads/" . $new_file_name;
        
        if (move_uploaded_file($file_tmp, $destination)) {
            $photo_path = $destination;
        }
    } else {
        echo "<script>alert('[ERROR] A profile photo is compulsory for registration.'); window.history.back();</script>";
        exit();
    }

    if (!ctype_digit($pass)) {
        echo "<script>alert('[ERROR] Password must contain numbers only!'); window.history.back();</script>";
        exit();
    } else {
        $stmt = $conn->prepare("INSERT INTO teachers (teacher_id, name, dept, subject, password, profile_photo, semester) VALUES (?, ?, ?, ?, ?, ?, ?)");
        if ($stmt === false) {
            die("<pre>[DATABASE ERROR] SQL Preparation Failed!\nDetails: " . $conn->error . "</pre>");
        }
        
        $stmt->bind_param("ssssssi", $custom_id, $name, $dept, $subject, $pass, $photo_path, $semester);
        
        if ($stmt->execute()) {
            echo "<script>alert('Registration Successful! Your Admin ID is: $custom_id'); window.location.href='index.php';</script>";
            exit();
        } else {
            echo "<script>alert('Registration Rejected! This Admin ID is already taken.'); window.history.back();</script>";
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Faculty Registration — Academic Portal System">
    <title>Faculty Registration — Academic Portal</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="style.css">
</head>
<body class="register-page">

    <div class="register-card" id="teacher-register-card">
        <div class="register-card-header">
            <div class="icon-ring teacher" aria-hidden="true">👨‍🏫</div>
            <h2 class="gradient-text">Faculty Registration</h2>
            <p>Set up your instructor account and department mapping.</p>
        </div>

        <form action="teacher_register.php" method="POST" enctype="multipart/form-data" novalidate>
            <div class="form-group">
                <label for="tea_id">Teacher ID / Username</label>
                <input type="text" name="teacher_id" id="tea_id" required
                       placeholder="e.g., TEA_CS01" autocomplete="username">
            </div>

            <div class="form-group">
                <label for="tea_name">Full Name</label>
                <input type="text" name="name" id="tea_name" required
                       placeholder="e.g., Dr. Amit Kumar" autocomplete="name">
            </div>

            <div class="form-group">
                <label for="tea_photo">Profile Photo (Required)</label>
                <input type="file" name="profile_photo" id="tea_photo" accept="image/*" required
                       aria-describedby="photo-hint">
                <small id="photo-hint" style="color:var(--text-muted);font-size:0.78rem;margin-top:4px;display:block;">
                    JPG, PNG or WEBP — clearly shows your face
                </small>
            </div>

            <div class="form-group">
                <label for="tea_subject">Subject Name</label>
                <input type="text" name="subject" id="tea_subject" required
                       placeholder="e.g., Database Management">
            </div>

            <div class="form-group">
                <label for="tea_dept">Assigned Department</label>
                <select name="dept" id="tea_dept" required aria-required="true">
                    <option value="CS">Computer Science (CSE)</option>
                    <option value="EC">Electronics (ECE)</option>
                    <option value="IS">Information Science (ISE)</option>
                    <option value="ME">Mechanical (ME)</option>
                </select>
            </div>

            <div class="form-group">
                <label for="tea_sem">Target Semester</label>
                <select name="semester" id="tea_sem" required aria-required="true">
                    <?php for($i=1; $i<=8; $i++) { echo "<option value='$i'>Semester $i</option>"; } ?>
                </select>
            </div>

            <div class="form-group">
                <label for="tea_pass">Password (Numbers Only)</label>
                <input type="text" name="password" id="tea_pass" required
                       pattern="[0-9]+" inputmode="numeric" placeholder="e.g., 789123">
            </div>

            <button type="submit" name="register_teacher" class="btn-submit" aria-label="Register faculty account">
                Register Account →
            </button>
        </form>

        <a href="index.php" class="login-link" style="display:block;text-align:center;margin-top:16px;">← Back to Login Gateway</a>
    </div>

    <script src="portal.js"></script>
</body>
</html>