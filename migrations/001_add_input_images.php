<?php
require_once __DIR__ . '/../db_connect.php';

try {
    echo "Adding input_images column to apps table...\n";
    $pdo->exec("ALTER TABLE apps ADD COLUMN input_images TEXT DEFAULT NULL");
    echo "Success!\n";
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'duplicate column name') !== false) {
        echo "Column already exists.\n";
    } else {
        echo "Error: " . $e->getMessage() . "\n";
    }
}
