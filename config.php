<?php
// config.php

// Set timezone to Philippines
date_default_timezone_set('Asia/Manila');

// Load environment variables from .env
function loadEnv() {
    require __DIR__ . '/vendor/autoload.php';
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
    $dotenv->load();
}

// Establish the database connection
function dbConnect() {
    $db_host = $_ENV['DB_HOST'];
    $db_user = $_ENV['DB_USER'];
    $db_pass = $_ENV['DB_PASS'];
    $db_name = $_ENV['DB_NAME'];

    // Create a new database connection
    $conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

    // Check the connection
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    return $conn;
}
?>