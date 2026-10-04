<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/require_user.php';
$pdo=getDbConnection();$userId=(int)$_SESSION['user_id'];$method=$_SERVER['REQUEST_METHOD'];$action=$_GET['action']??'';
function scholarshipExists(PDO $pdo,int $id):bool{$s=$pdo->prepare('SELECT id FROM scholarships WHERE id=?');$s->execute([$id]);return(bool)$s->fetch();}
if($action==='saved'){
 if($method==='GET'){
  $s=$pdo->prepare('SELECT s.*, ss.saved_at FROM saved_scholarships ss JOIN scholarships s ON s.id=ss.scholarship_id WHERE ss.user_id=? ORDER BY ss.saved_at DESC');$s->execute([$userId]);echo json_encode($s->fetchAll());exit;
 }
 if($method==='POST'){
  $d=json_decode(file_get_contents('php://input'),true)?:[];$id=(int)($d['scholarship_id']??0);if(!$id||!scholarshipExists($pdo,$id)){http_response_code(400);echo json_encode(['error'=>'Invalid scholarship.']);exit;}
  $s=$pdo->prepare('INSERT IGNORE INTO saved_scholarships(user_id,scholarship_id) VALUES(?,?)');$s->execute([$userId,$id]);echo json_encode(['success'=>true,'saved'=>true]);exit;
 }
 if($method==='DELETE'){$id=(int)($_GET['scholarship_id']??0);$s=$pdo->prepare('DELETE FROM saved_scholarships WHERE user_id=? AND scholarship_id=?');$s->execute([$userId,$id]);echo json_encode(['success'=>true,'saved'=>false]);exit;}
}
if($action==='status'&&$method==='GET'){$id=(int)($_GET['scholarship_id']??0);$s=$pdo->prepare('SELECT 1 FROM saved_scholarships WHERE user_id=? AND scholarship_id=?');$s->execute([$userId,$id]);echo json_encode(['saved'=>(bool)$s->fetch()]);exit;}
if($action==='viewed'&&$method==='GET'){$s=$pdo->prepare('SELECT s.*,MAX(v.viewed_at) AS viewed_at FROM viewed_scholarships v JOIN scholarships s ON s.id=v.scholarship_id WHERE v.user_id=? GROUP BY s.id ORDER BY viewed_at DESC');$s->execute([$userId]);echo json_encode($s->fetchAll());exit;}
if($action==='view'&&$method==='POST'){$d=json_decode(file_get_contents('php://input'),true)?:[];$id=(int)($d['scholarship_id']??0);if($id&&scholarshipExists($pdo,$id)){$s=$pdo->prepare('INSERT INTO viewed_scholarships(user_id,scholarship_id) VALUES(?,?)');$s->execute([$userId,$id]);}echo json_encode(['success'=>true]);exit;}
http_response_code(400);echo json_encode(['error'=>'Invalid interaction request.']);
