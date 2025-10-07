<?php
require_once 'db.php';

$username = "teststudent";
$email = "test@example.com";
$hashed_password = "$2y$10$2C6JOCbNc8SuveBWzZZuqeFYq8LQCHljBev65I7WTTyVIdU3D4Rxu"; // Hashed 'password123'
$role = "student";
$active = 1; // Directly activate the user

$stmt = $conn->prepare("INSERT INTO users (username, email, password, role, active) VALUES (?, ?, ?, ?, ?)");
$stmt->bind_param("ssssi", $username, $email, $hashed_password, $role, $active);

if ($stmt->execute()) {
    echo "User 'teststudent' inserted and activated successfully.";
} else {
    echo "Error inserting user: " . $stmt->error;
}

$stmt->close();
$conn->close();
?>