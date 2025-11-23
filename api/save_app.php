<?php
header('Content-Type: application/json');

// Allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit;
}

// Get JSON input
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data) {
    echo json_encode(['success' => false, 'error' => 'Invalid JSON data']);
    exit;
}

// Basic validation
if (empty($data['id']) || empty($data['name']) || empty($data['inputs'])) {
    echo json_encode(['success' => false, 'error' => 'Missing required fields']);
    exit;
}

// Path to custom apps file
$customAppsFile = __DIR__ . '/../custom_apps.json';

// Load existing apps
$apps = [];
if (file_exists($customAppsFile)) {
    $apps = json_decode(file_get_contents($customAppsFile), true);
    if (!$apps) $apps = [];
}

// Check for duplicate ID
if (isset($apps[$data['id']])) {
    // Append random suffix to make unique
    $data['id'] = $data['id'] . '_' . substr(md5(time()), 0, 4);
}

// Add new app
$apps[$data['id']] = $data;

// Save back to file
if (file_put_contents($customAppsFile, json_encode($apps, JSON_PRETTY_PRINT))) {
    echo json_encode(['success' => true, 'app_id' => $data['id']]);
} else {
    echo json_encode(['success' => false, 'error' => 'Failed to save app data']);
}
?>
