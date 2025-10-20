<?php
require __DIR__ . '/../config/bootstrap.php';
session_destroy();
header("Location: /harmony/");
exit;
