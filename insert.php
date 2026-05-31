<?php
// 1. Establish connection to local XAMPP MySQL Server
// Parameters: server, username (default 'root'), password (default empty), database_name
$conn = new mysqli("localhost", "root", "", "student_db");

// Check connection stability
if ($conn->connect_error) {
    die("Database Connection failed: " . $conn->connect_error);
}

// 2. Extract values submitted from the form
$name = $_POST['name'];
$rollNo = $_POST['rollNo'];
$course = $_POST['course'];
$email = $_POST['email'];

// 3. Check for Duplicate Roll Number (SQL Integrity Check)
$checkDuplicate = $conn->prepare("SELECT roll_no FROM students WHERE roll_no = ?");
$checkDuplicate->bind_param("s", $rollNo);
$checkDuplicate->execute();
$result = $checkDuplicate->get_result();

if ($result->num_rows > 0) {
    echo "<script>
            alert('[SQL ERROR] Duplicate Entry! Roll Number already exists.');
            window.location.href='index.php';
          </script>";
} else {
    // 4. Prepare and execute the SQL statement to Insert Data securely
    $stmt = $conn->prepare("INSERT INTO students (name, roll_no, course, email) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $name, $rollNo, $course, $email);

    if ($stmt->execute()) {
        // Record created successfully, go right back to the application dashboard
        header("Location: index.php");
    } else {
        echo "Error saving data: " . $conn->error;
    }
    $stmt->close();
}

$checkDuplicate->close();
$conn->close();
?>