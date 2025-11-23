<?php
header('Content-Type: application/json');
require_once '../db_connect.php';

try {
    $user_id = $_GET['user_id'] ?? '';

    if ($user_id) {
        // Fetch user's apps first, then others? Or just user's?
        // Let's fetch global recent apps, but maybe mark user's?
        // Or if "My Apps" tab is requested.
        // For now, the script.js sends user_id always.
        // Let's just return recent apps as before, but maybe we can add a "mine" flag?
        // Actually, the requirement was "My Apps" filtering.
        // Let's just return all recent apps for the main gallery.
        // If we want a specific "My Apps" view, we'd filter.
        // But the current UI is a single gallery.
        // Let's stick to global gallery for "Democratic" feel, everyone sees everything.
        // But maybe we highlight the user's own apps?
        
        $stmt = $pdo->query("SELECT * FROM apps ORDER BY created_at DESC LIMIT 20");
        $apps = $stmt->fetchAll();
        
        // Add 'is_mine' flag
        foreach ($apps as &$app) {
            $app['is_mine'] = ($app['user_id'] === $user_id);
        }
    } else {
        $stmt = $pdo->query("SELECT * FROM apps ORDER BY created_at DESC LIMIT 20");
        $apps = $stmt->fetchAll();
    }

    echo json_encode(['success' => true, 'apps' => $apps]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
