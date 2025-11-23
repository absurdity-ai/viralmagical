<?php
require_once __DIR__ . '/db_connect.php';

function logApiCall($endpoint, $prompt, $response, $model, $token_count = null) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("INSERT INTO api_logs (endpoint, prompt, response, model, token_count) VALUES (?, ?, ?, ?, ?)");
        
        // Ensure prompt and response are strings (handle arrays/JSON)
        $promptStr = is_string($prompt) ? $prompt : json_encode($prompt);
        $responseStr = is_string($response) ? $response : json_encode($response);
        
        $stmt->execute([$endpoint, $promptStr, $responseStr, $model, $token_count]);
    } catch (Exception $e) {
        // Silently fail logging to avoid breaking the main app flow
        error_log("Failed to log API call: " . $e->getMessage());
    }
}
?>
