<?php
error_reporting(E_ERROR|E_PARSE);
require_once __DIR__ . '/config.php';
if(session_status()===PHP_SESSION_NONE) session_start();

function loginUser(string $email, string $password): array {
    $db=getDB();
    $stmt=$db->prepare("SELECT * FROM users WHERE email=? AND is_active=1");
    $stmt->execute([$email]); $user=$stmt->fetch();
    if($user){
        $valid=password_verify($password,$user['password'])||$password===$user['password'];
        if($valid){
            if(!password_verify($password,$user['password'])){
                $db->prepare("UPDATE users SET password=? WHERE id=?")->execute([password_hash($password,PASSWORD_BCRYPT,['cost'=>12]),$user['id']]);
            }
            $_SESSION['user_id']=$user['id'];$_SESSION['user_name']=$user['name'];
            $_SESSION['user_role']=$user['role'];$_SESSION['user_type']='staff';
            $_SESSION['login_time']=time();
            return ['success'=>true,'role'=>$user['role'],'type'=>'staff'];
        }
    }
    $stmt=$db->prepare("SELECT * FROM clients WHERE email=? AND is_active=1");
    $stmt->execute([$email]); $client=$stmt->fetch();
    if($client){
        $valid=password_verify($password,$client['password'])||$password===$client['password'];
        if($valid){
            if(!password_verify($password,$client['password'])){
                $db->prepare("UPDATE clients SET password=? WHERE id=?")->execute([password_hash($password,PASSWORD_BCRYPT,['cost'=>12]),$client['id']]);
            }
            $_SESSION['user_id']=$client['id'];$_SESSION['user_name']=$client['contact_person'];
            $_SESSION['user_role']='client';$_SESSION['user_type']='client';
            $_SESSION['login_time']=time();
            return ['success'=>true,'role'=>'client','type'=>'client'];
        }
    }
    return ['success'=>false,'message'=>'Invalid email or password. Please try again.'];
}

function getBasePath(): string {
    $script=$_SERVER['SCRIPT_NAME']??'';
    foreach(['/admin/','/manager/','/client/','/public/','/includes/'] as $seg){
        if(strpos($script,$seg)!==false) return substr($script,0,strpos($script,$seg));
    }
    return rtrim(dirname($script),'/');
}

function logoutUser(): void {
    session_unset();session_destroy();
    $base=getBasePath();
    header('Location: '.$base.'/index.php');exit;
}

function isLoggedIn(): bool {
    if(!isset($_SESSION['user_id'])) return false;
    if(time()-($_SESSION['login_time']??0)>SESSION_TIMEOUT){logoutUser();}
    return true;
}

function requireLogin(): void {
    if(!isLoggedIn()){
        $base=getBasePath();
        header('Location: '.$base.'/index.php?msg=session_expired');exit;
    }
}

function requireRole(array $allowedRoles): void {
    requireLogin();
    if(!in_array($_SESSION['user_role'],$allowedRoles)){
        $base=getBasePath();
        $role=$_SESSION['user_role'];
        if($role==='client'){header('Location: '.$base.'/client/dashboard.php');exit;}
        if(in_array($role,['manager','employee'])){header('Location: '.$base.'/manager/dashboard.php');exit;}
        header('Location: '.$base.'/index.php');exit;
    }
}

function currentUser(): array {
    return ['id'=>$_SESSION['user_id']??null,'name'=>$_SESSION['user_name']??'Guest','role'=>$_SESSION['user_role']??null,'type'=>$_SESSION['user_type']??null];
}

function isAdmin(): bool   { return ($_SESSION['user_role']??'')==='admin'; }
function isManager(): bool { return ($_SESSION['user_role']??'')==='manager'; }
function isClient(): bool  { return ($_SESSION['user_role']??'')==='client'; }
function isStaff(): bool   { return in_array($_SESSION['user_role']??'',['admin','manager','employee']); }

function redirectByRole(string $role): void {
    $base=getBasePath();
    $routes=['admin'=>$base.'/admin/dashboard.php','manager'=>$base.'/manager/dashboard.php','employee'=>$base.'/manager/dashboard.php','client'=>$base.'/client/dashboard.php'];
    header('Location: '.($routes[$role]??$base.'/index.php'));exit;
}

function csrfToken(): string {
    if(empty($_SESSION['csrf_token'])) $_SESSION['csrf_token']=bin2hex(random_bytes(32));
    return $_SESSION['csrf_token'];
}

function verifyCsrf(): void {
    if(!isset($_POST['csrf_token'])||$_POST['csrf_token']!==($_SESSION['csrf_token']??'')) die('Invalid request. Please go back and try again.');
}

function hashPassword(string $password): string {
    return password_hash($password,PASSWORD_BCRYPT,['cost'=>12]);
}
