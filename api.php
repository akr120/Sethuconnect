<?php
// api.php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/db.php';
session_start();

$pdo = getPDO();
$action = $_GET['action'] ?? $_POST['action'] ?? '';

function json($v){ echo json_encode($v); exit; }

switch($action){

  case 'init':
    // sanity check
    json(['ok'=>true, 'session'=>isset($_SESSION['user'])?$_SESSION['user']:null]);
    break;

  case 'register':
    $data = json_decode(file_get_contents('php://input'), true);
    $name = trim($data['name'] ?? '');
    $email = trim($data['email'] ?? '');
    $password = $data['password'] ?? '';
    $phone = $data['phone'] ?? '';
    $aadhaar = $data['aadhaar'] ?? null;
    $language = $data['language'] ?? 'en';
    if (!$name || !$email || !$password) json(['error'=>'missing fields']);
    $hash = password_hash($password, PASSWORD_DEFAULT);
    try {
      $stmt = $pdo->prepare("INSERT INTO users (name,email,password_hash,phone,aadhaar,language) VALUES (?,?,?,?,?,?)");
      $stmt->execute([$name,$email,$hash,$phone,$aadhaar,$language]);
      $userId = $pdo->lastInsertId();
      // create worker row
      $pdo->prepare("INSERT INTO workers (user_id,current_state,current_city,skills) VALUES (?,?,?,?)")
          ->execute([$userId,'','', '']);
      $_SESSION['user'] = ['id'=>$userId,'name'=>$name,'email'=>$email,'language'=>$language];
      json(['ok'=>true,'user'=>$_SESSION['user']]);
    } catch (Exception $e) {
      json(['error'=>'registration_failed','message'=>$e->getMessage()]);
    }
    break;

  case 'login':
    $data = json_decode(file_get_contents('php://input'), true);
    $email = $data['email'] ?? '';
    $password = $data['password'] ?? '';
    if (!$email || !$password) json(['error'=>'missing']);
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    if (!$user || !password_verify($password, $user['password_hash'])) json(['error'=>'invalid_credentials']);
    $_SESSION['user'] = ['id'=>$user['id'],'name'=>$user['name'],'email'=>$user['email'],'language'=>$user['language']];
    json(['ok'=>true,'user'=>$_SESSION['user']]);
    break;

  case 'logout':
    session_destroy();
    json(['ok'=>true]);
    break;

  case 'profile_update':
    if (!isset($_SESSION['user'])) json(['error'=>'unauth']);
    $data = json_decode(file_get_contents('php://input'), true);
    $state = $data['current_state'] ?? '';
    $city = $data['current_city'] ?? '';
    $skills = $data['skills'] ?? '';
    $eshram = !empty($data['registered_on_eshram']) ? 1 : 0;
    $uid = $_SESSION['user']['id'];
    $stmt = $pdo->prepare("UPDATE workers SET current_state=?,current_city=?,skills=?,registered_on_eshram=? WHERE user_id=?");
    $stmt->execute([$state,$city,$skills,$eshram,$uid]);
    json(['ok'=>true]);
    break;

  case 'create_job':
    // public for demo; in prod restrict to employer/admin
    $data = json_decode(file_get_contents('php://input'), true);
    $title = $data['title'] ?? '';
    if (!$title) json(['error'=>'missing_title']);
    $stmt = $pdo->prepare("INSERT INTO jobs (title,description,employer,state,city,min_wage) VALUES (?,?,?,?,?,?)");
    $stmt->execute([
      $title,
      $data['description'] ?? '',
      $data['employer'] ?? '',
      $data['state'] ?? '',
      $data['city'] ?? '',
      intval($data['min_wage'] ?? 0)
    ]);
    json(['ok'=>true,'id'=>$pdo->lastInsertId()]);
    break;

  case 'list_jobs':
    $q = $_GET['q'] ?? null;
    $state = $_GET['state'] ?? null;
    $city = $_GET['city'] ?? null;
    $sql = "SELECT * FROM jobs WHERE 1=1";
    $params = [];
    if ($state) { $sql .= " AND state = ?"; $params[] = $state; }
    if ($city) { $sql .= " AND city = ?"; $params[] = $city; }
    if ($q) { $sql .= " AND (title LIKE ? OR description LIKE ?)"; $params[] = "%$q%"; $params[] = "%$q%"; }
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    json($rows);
    break;

  case 'verify_aadhaar':
    if (!isset($_SESSION['user'])) json(['error'=>'unauth']);
    $data = json_decode(file_get_contents('php://input'), true);
    $aad = $data['aadhaar'] ?? '';
    if (!$aad || strlen($aad) != 12) json(['verified'=>false,'message'=>'Invalid format']);
    $last = intval(substr($aad, -1));
    $verified = ($last % 2 === 0);
    json(['verified'=>$verified,'message'=>$verified ? 'Mock-verified' : 'Mock-not-verified']);
    break;

  case 'ration_check':
    $aad = $_GET['aadhaar'] ?? '';
    if (!$aad) json(['error'=>'missing']);
    $last = intval(substr($aad, -1));
    $portable = ($last % 5 === 0);
    json(['aadhaar'=>$aad,'portable'=>$portable, 'message'=> $portable ? 'Ration portable (mock)' : 'No portability record (mock)']);
    break;

  case 'admin_users':
    // simple listing for admin/debug
    $stmt = $pdo->query("SELECT id,name,email,phone,aadhaar,language,created_at FROM users");
    json($stmt->fetchAll(PDO::FETCH_ASSOC));
    break;

  default:
    json(['error'=>'unknown_action','action'=>$action]);
}
