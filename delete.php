<?php
session_start();

// 1. Security Check: Only allow logged-in teachers to trigger this file
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'teacher') {
    header("Location: index.php");
    exit();
}

// 2. Check if a USN was actually sent to be deleted
if (isset($_POST['delete_student_usn'])) {
    
    $conn = new mysqli("localhost", "root", "", "student_db");
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    $target_usn = $_POST['delete_student_usn'];
    $teacher_subject = $_SESSION['teacher_subject'];
    $teacher_id = $_SESSION['teacher_username_id'];

    // 3. SAFE DELETE: Removes the student's slot ONLY from this specific teacher's subject tracking list
    $delete_stmt = $conn->prepare("DELETE FROM academic_records WHERE student_usn = ? AND subject_name = ? AND teacher_id = ?");
    $delete_stmt->bind_param("sss", $target_usn, $teacher_subject, $teacher_id);
    
    if ($delete_stmt->execute()) {
        // Success redirect
        echo "<script>alert('Student record successfully removed from your subject slot.'); window.location.href='teacher_dashboard.php';</script>";
    } else {
        // Error redirect
        echo "<script>alert('Failed to remove student record.'); window.location.href='teacher_dashboard.php';</script>";
    }

    $delete_stmt->close();
    $conn->close();
} else {
    // If someone tries to open delete.php directly in the browser, send them away
    header("Location: teacher_dashboard.php");
    exit();
}
?>