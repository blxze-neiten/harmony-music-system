<?php
require "../config/bootstrap.php";
session_destroy();
header("Location: login.php");
exit;
