<?php
require_once "config.php";
// Versucht sich mit der Datenbank zu verbinden
try {
  $pdo = new PDO('mysql:host=' . $ini_array["mysql"]["host"] . ';dbname=' . $ini_array["mysql"]["database"] . ';charset=utf8', $ini_array["mysql"]["user"], $ini_array["mysql"]["passwd"]);
} catch(PDOException $e) {
  // Fehler mit der Datenbank
  $backtrace = debug_backtrace();
  error_log($backtrace[count($backtrace)-1]['file'] . ':' . $backtrace[count($backtrace)-1]['line'] . ': Database connection failed: ' . $e->getMessage());
  print('Database connection failed');
  exit;
}
?>
