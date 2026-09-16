<?php
$conn = new mysqli("localhost", "root", "");
if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}
$conn->query("CREATE DATABASE IF NOT EXISTS hr_portal CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
$conn->select_db("hr_portal");
?>