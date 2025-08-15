<?php
require_once __DIR__ . '/../includes/bootstrap.php';
verify_csrf();
header('Content-Type: application/json');
if(!isset($_SESSION['user_id'])) { http_response_code(401); echo json_encode(['ok'=>false,'error'=>'auth']); exit; }
$userId = (int)$_SESSION['user_id'];
$codes = generate_recovery_codes($userId);
// Return plain codes once
echo json_encode(['ok'=>true,'codes'=>$codes]);
