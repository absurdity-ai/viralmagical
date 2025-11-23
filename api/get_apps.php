<?php
header('Content-Type: application/json');
require_once '../db_connect.php';
require_once '../sponsor_config.php';

try {
    $user_id = $_GET['user_id'] ?? '';

    // Fetch recent apps ordered by newest first
    $stmt = $pdo->query("SELECT * FROM apps ORDER BY created_at DESC LIMIT 20");
    $apps = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Add sponsor_name field using sponsor_config mapping
    foreach ($apps as &$app) {
        $key = $app['sponsor'];
        $app['sponsor_name'] = $sponsor_prompts[$key]['name'] ?? $key;
        if ($user_id) {
            $app['is_mine'] = ($app['user_id'] === $user_id);
        }
    }
    unset($app);

    echo json_encode(['success' => true, 'apps' => $apps]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
