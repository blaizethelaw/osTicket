<?php
require_once(dirname(__FILE__) . '/../include/gemini.php');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$subject = $data['subject'] ?? '';
$body    = $data['body'] ?? '';
$prompt  = "You are a ticket triage assistant. Choose the best category and priority from the lists.\n".
           "Categories: Incident: Software > VPN, Software > Email, Hardware > Printer, Hardware > Laptop, Network > Connectivity; " .
           "Service Request: Software > Licensing, Hardware > New Employee, Access > Shared Drive, Access > New Account, Hardware > Peripheral Request.\n".
           "Priorities: Low, Medium, High, Urgent.\n".
           "Respond in JSON with keys category and priority.\n".
           "Subject: $subject\nBody: $body";

$response = GeminiClient::call($prompt);
$choice = $response['candidates'][0]['content']['parts'][0]['text'] ?? '';
$result = json_decode($choice, true);
if (!is_array($result))
    $result = array('category' => '', 'priority' => '');

header('Content-Type: application/json');
echo json_encode($result);
