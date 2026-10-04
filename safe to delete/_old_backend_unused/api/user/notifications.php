<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/require_user.php';
$pdo=getDbConnection();$userId=(int)$_SESSION['user_id'];$method=$_SERVER['REQUEST_METHOD'];
if($method==='GET'){$s=$pdo->prepare('SELECT id,scholarship_id,type,title,message,is_read,created_at FROM notifications WHERE user_id=? ORDER BY created_at DESC');$s->execute([$userId]);echo json_encode($s->fetchAll());exit;}
if($method==='PUT'){$d=json_decode(file_get_contents('php://input'),true)?:[];$id=(int)($d['id']??0);$s=$pdo->prepare('UPDATE notifications SET is_read=1 WHERE user_id=? AND id=?');$s->execute([$userId,$id]);echo json_encode(['success'=>true]);exit;}
http_response_code(405);echo json_encode(['error'=>'Method not allowed.']);
