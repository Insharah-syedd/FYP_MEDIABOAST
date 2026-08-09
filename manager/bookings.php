<?php
error_reporting(E_ERROR|E_PARSE);
require_once '../includes/auth.php';
requireRole(['admin','manager','employee']);
if(isAdmin()){header('Location: ../admin/bookings.php');exit;}
// Redirect to admin page - manager can access these
header('Location: ../admin/bookings.php');exit;
