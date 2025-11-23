<?php
header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'message' => 'Preview generator API is reachable',
    'post_data' => $_POST,
    'files_count' => count($_FILES)
]);
?>
