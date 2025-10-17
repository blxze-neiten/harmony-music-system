<?php
session_start();

$host="localhost"; $db="harmony"; $user="root"; $pass="";
$pdo=new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4",$user,$pass);
$pdo->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);

function current_user(){
    return $_SESSION['user'] ?? null;
}

function require_login(){
    if(!current_user()){
        header("Location: ../auth/login.php");
        exit;
    }
}

function require_roles($roles){
    $u=current_user();
    if(!$u || !in_array($u['role'],$roles)){
        echo "<div style='padding:20px;color:red;font-weight:bold;'>Access denied.</div>";
        exit;
    }
}
