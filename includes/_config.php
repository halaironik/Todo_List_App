<?php
    
    $host = "localhost";
    $username = "root";
    $password = null;
    $dbName = "todoapp_db";
 
    $conn = new mysqli($host, $username, $password, $dbName);
    if($conn->connect_error) {
        die("Error connecting to database" . $conn->connect_error);
    }
    
    
?>