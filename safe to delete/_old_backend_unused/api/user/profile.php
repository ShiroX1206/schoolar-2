<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/require_user.php';
$pdo=getDbConnection(); $userId=(int)$_SESSION['user_id']; $method=$_SERVER['REQUEST_METHOD'];
function profileRow(PDO $pdo,int $userId):?array {
 $stmt=$pdo->prepare('SELECT u.id,u.full_name,u.email,u.birth_date,u.municipality_code,u.barangay_code,u.school_id,u.course_id,u.year_level,u.gwa,u.annual_family_income,u.profile_photo,s.name AS school_name,c.name AS course_name,c.code AS course_code FROM users u LEFT JOIN schools s ON s.id=u.school_id LEFT JOIN courses c ON c.id=u.course_id WHERE u.id=?');
 $stmt->execute([$userId]); $row=$stmt->fetch(); if(!$row)return null;
 if($row['birth_date']){$b=new DateTime($row['birth_date']);$row['age']=$b->diff(new DateTime('today'))->y;}else{$row['age']=null;}
 $row['id']=(int)$row['id']; return $row;
}
if($method==='GET'){ $row=profileRow($pdo,$userId); if(!$row){http_response_code(404);echo json_encode(['error'=>'User not found.']);exit;} echo json_encode($row);exit; }
if($method==='PUT'){
 $d=json_decode(file_get_contents('php://input'),true)?:[]; $name=trim((string)($d['name']??'')); $municipality=trim((string)($d['municipality_code']??'')); $barangay=trim((string)($d['barangay_code']??'')); $school=trim((string)($d['school_id']??'')); $course=trim((string)($d['course_id']??'')); $year=trim((string)($d['year_level']??'')); $gwa=$d['gwa']??null; $income=$d['annual_family_income']??null;
 if($name===''){http_response_code(400);echo json_encode(['error'=>'Full name is required.']);exit;}
 if($gwa!==null&&$gwa!==''&&(!is_numeric($gwa)||(float)$gwa<1||(float)$gwa>5)){http_response_code(400);echo json_encode(['error'=>'GPA/GWA must be between 1.00 and 5.00.']);exit;}
 if($income!==null&&$income!==''&&(!is_numeric($income)||(float)$income<0)){http_response_code(400);echo json_encode(['error'=>'Annual family income cannot be negative.']);exit;}
 if($school!==''||$course!==''){
  $s=$pdo->prepare('SELECT id FROM schools WHERE id=?');$s->execute([$school]);if(!$s->fetch()){http_response_code(400);echo json_encode(['error'=>'Selected school does not exist.']);exit;}
  $s=$pdo->prepare('SELECT id FROM courses WHERE id=? AND school_id=?');$s->execute([$course,$school]);if(!$s->fetch()){http_response_code(400);echo json_encode(['error'=>'Selected course does not belong to the selected school.']);exit;}
 }
 $s=$pdo->prepare('UPDATE users SET full_name=?,municipality_code=?,barangay_code=?,school_id=?,course_id=?,year_level=?,gwa=?,annual_family_income=? WHERE id=?');
 $s->execute([$name,$municipality?:null,$barangay?:null,$school?:null,$course?:null,$year?:null,$gwa===''?null:$gwa,$income===''?null:$income,$userId]);
 echo json_encode(['success'=>true,'user'=>profileRow($pdo,$userId)]);exit;
}
http_response_code(405);echo json_encode(['error'=>'Method not allowed.']);
