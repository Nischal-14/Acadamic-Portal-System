<?php
session_start();
$conn = new mysqli("localhost", "root", "", "student_db");

if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

$role = $_GET['role'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = strtoupper(trim($_POST['username']));
    $password = trim($_POST['password']);

    // ==========================================
    // 🎓 STUDENT AUTHENTICATION ROUTE
    // ==========================================
    if ($role === 'student') {
        $login_dept = $_POST['login_dept'] ?? '';
        $login_semester = isset($_POST['login_semester']) ? intval($_POST['login_semester']) : 1;

        // 1. Structural Branch Prefix Security Check
        if (!str_contains($username, $login_dept)) {
            echo "<script>alert('❌ Branch Mismatch! Your input USN does not match the designated branch selection ($login_dept).'); window.history.back();</script>";
            exit();
        }

        $stmt = $conn->prepare("SELECT id, roll_no, password, semester, approval_status FROM students WHERE roll_no = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();

            if ($password === $row['password']) {
                
                // 🛑 DECENTRALIZED RULE UPDATE: 
                // Global student registration pending check is removed here, because 
                // validation security is enforced on an isolated subject-by-subject level!

                // 🔒 CHECK: Two-Way Semester Verification Match
                if (intval($row['semester']) !== $login_semester) {
                    echo "<script>alert('❌ Login Blocked! The semester you selected does not match the authorized semester assigned during registration.'); window.history.back();</script>";
                    exit();
                }

                // Everything matches perfectly! Log them in safely
                $_SESSION['role'] = 'student';
                $_SESSION['student_id'] = $row['id'];
                $_SESSION['roll_no'] = $row['roll_no'];
                
                header("Location: student_profile.php");
                exit();
            } else {
                echo "<script>alert('❌ Incorrect password entry. Please verify numbers and re-submit.'); window.history.back();</script>";
                exit();
            }
        } else {
            echo "<script>alert('❌ Records Mismatch! This USN is not present in the database.'); window.history.back();</script>";
            exit();
        }
        $stmt->close();
    }

    // ==========================================
    // 👨‍🏫 TEACHER AUTHENTICATION ROUTE
    // ==========================================
    elseif ($role === 'teacher') {
        $stmt = $conn->prepare("SELECT teacher_id, password FROM teachers WHERE teacher_id = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();

            if ($password === $row['password']) {
                $_SESSION['role'] = 'teacher';
                $_SESSION['teacher_username_id'] = $row['teacher_id'];
                
                header("Location: teacher_dashboard.php");
                exit();
            } else {
                echo "<script>alert('❌ Incorrect administrator security key sequence.'); window.history.back();</script>";
                exit();
            }
        } else {
            echo "<script>alert('❌ Teacher ID profile matching parameter empty.'); window.history.back();</script>";
            exit();
        }
        $stmt->close();
    }
}
$conn->close();
?>