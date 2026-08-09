<?php
// ============================================================
//  MediaBoost — Database Configuration
// ============================================================

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'mediaboost');
define('DB_CHARSET', 'utf8mb4');

// App Settings
define('APP_NAME', 'MediaBoost');
define('APP_VERSION', '1.0.0');
define('SESSION_TIMEOUT', 3600);

// Dynamic APP_URL - works on any server automatically
if(!defined('APP_URL')) {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS']!=='off') ? 'https' : 'http';
    $host     = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $script   = $_SERVER['SCRIPT_NAME'] ?? '';
    // Find the base path (e.g. /mediaboost)
    $base = '';
    if(strpos($script, '/admin/') !== false)      $base = substr($script, 0, strpos($script, '/admin/'));
    elseif(strpos($script, '/manager/') !== false) $base = substr($script, 0, strpos($script, '/manager/'));
    elseif(strpos($script, '/client/') !== false)  $base = substr($script, 0, strpos($script, '/client/'));
    elseif(strpos($script, '/public/') !== false)  $base = substr($script, 0, strpos($script, '/public/'));
    elseif(strpos($script, '/includes/') !== false)$base = substr($script, 0, strpos($script, '/includes/'));
    else {
        $base = rtrim(dirname($script), '/');
    }
    define('APP_URL', $protocol.'://'.$host.$base);
}

// Email Settings
define('MAIL_HOST', 'smtp.gmail.com');
define('MAIL_PORT', 587);
define('MAIL_USER', 'your_email@gmail.com');
define('MAIL_PASS', 'your_app_password');
define('MAIL_FROM_NAME', 'All Media Marketing');

// File Upload
define('UPLOAD_PATH', __DIR__ . '/../uploads/');
define('MAX_FILE_SIZE', 5 * 1024 * 1024);

// ============================================================
//  Database Connection (PDO)
// ============================================================
function getDB(): PDO {
    static $pdo = null;
    if($pdo === null) {
        try {
            $dsn = "mysql:host=".DB_HOST.";dbname=".DB_NAME.";charset=".DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch(PDOException $e) {
            die('<h2 style="font-family:sans-serif;padding:40px;color:red">Database Error: '.$e->getMessage().'<br><br>Please make sure MySQL is running and database "mediaboost" exists.</h2>');
        }
    }
    return $pdo;
}
?>
