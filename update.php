<?php
$conn = new mysqli("localhost", "root", "", "student_db");

if ($conn->connect_error) {
    die("Database Connection failed: " . $conn->connect_error);
}

// Capture values from the updated form submission
$id = $_POST['id'];
$name = $_POST['name'];
$rollNo = $_POST['rollNo'];
$course = $_POST['course'];
$email = $_POST['email'];

// Run the SQL UPDATE query
$stmt = $conn->prepare("UPDATE students SET name=?, roll_no=?, course=?, email=? WHERE id=?");
$stmt->bind_param("ssssi", $name, $rollNo, $course, $email, $id);

if ($stmt->execute()) {
    header("Location: index.php"); // Redirect back to clean main view
} else {
    echo "Error updating record: " . $conn->error;
}

$stmt->close();
$conn->close();
?>