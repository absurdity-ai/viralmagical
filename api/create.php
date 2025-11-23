<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

header('Content-Type: application/json');

// Catch fatal errors
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        error_log("Fatal error in create.php: " . json_encode($error));
        if (!headers_sent()) {
            header('Content-Type: application/json');
        }
        echo json_encode(['success' => false, 'error' => 'Server error: ' . $error['message']]);
    }
});

require_once '../db_connect.php';
require_once '../config.php';
require_once 's3_upload.php';
require_once '../app_config.php';
require_once '../sponsor_config.php';

// Generate UUID v4
function generateUUID() {
    return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand(0, 0xffff), mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0x0fff) | 0x4000,
        mt_rand(0, 0x3fff) | 0x8000,
        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
    );
}

// Get input (JSON or POST)
$contentType = $_SERVER["CONTENT_TYPE"] ?? '';
if (strpos($contentType, 'application/json') !== false) {
    $input = json_decode(file_get_contents('php://input'), true);
    $prompt = $input['prompt'] ?? '';
    $sponsor_key = $input['sponsor'] ?? 'la_croix';
    $app_id = $input['app_id'] ?? null;
    $remix_image_url = $input['remix_image_url'] ?? '';
} else {
    $prompt = $_POST['prompt'] ?? '';
    $sponsor_key = $_POST['sponsor'] ?? 'la_croix';
    $app_id = $_POST['app_id'] ?? null;
    $remix_image_url = $_POST['remix_image_url'] ?? '';
}

// DEBUG LOGGING
file_put_contents('../debug_upload.log', date('Y-m-d H:i:s') . " - POST: " . print_r($_POST, true) . "\nFILES: " . print_r($_FILES, true) . "\n", FILE_APPEND);

// Handle Multiple Image Uploads & Named Inputs
$image_urls_input = []; // For legacy indexed inputs
$named_input_values = []; // For new named inputs (role => value)
$flux_images = []; // Final list for Flux

// Sponsor is always Image 1
$sponsor_data = $sponsors[$sponsor_key] ?? $sponsors['la_croix'];
$sponsor_image = $sponsor_data['image_ref'] ?? $sponsor_data['image'];
$flux_images[] = $sponsor_image;
$current_image_index = 2; // Start at 2

// Helper to process a file upload
function processUpload($file, $target_dir) {
    if ($file['error'] === UPLOAD_ERR_OK) {
        $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $uuid = generateUUID();
        $new_filename = $uuid . '.' . $file_extension;
        $target_file = $target_dir . $new_filename;
        
        if (move_uploaded_file($file['tmp_name'], $target_file)) {
            $s3_url = uploadToS3($target_file, "uploads/" . $new_filename, $file['type']);
            if ($s3_url) {
                unlink($target_file);
                return $s3_url;
            }
        }
    }
    return null;
}

$target_dir = "../uploads/";
if (!file_exists($target_dir)) {
    mkdir($target_dir, 0755, true);
}

// 1. Handle Legacy Indexed Images (images[])
if (!empty($_FILES['images']['name'][0])) {
    $file_count = count($_FILES['images']['name']);
    for ($i = 0; $i < $file_count; $i++) {
        $file = [
            'name' => $_FILES['images']['name'][$i],
            'type' => $_FILES['images']['type'][$i],
            'tmp_name' => $_FILES['images']['tmp_name'][$i],
            'error' => $_FILES['images']['error'][$i],
            'size' => $_FILES['images']['size'][$i]
        ];
        $url = processUpload($file, $target_dir);
        if ($url) {
            $image_urls_input[] = $url;
            $flux_images[] = $url; // Add to flux list
            $current_image_index++;
        }
    }
} elseif (!empty($remix_image_url)) {
    $image_urls_input[] = $remix_image_url;
    $flux_images[] = $remix_image_url;
    $current_image_index++;
}

// 2. Handle Named Inputs (from generate.php)
// We need to check $_FILES for named keys that are NOT 'images'
foreach ($_FILES as $key => $file) {
    if ($key !== 'images') {
        $url = processUpload($file, $target_dir);
        if ($url) {
            $named_input_values[$key] = $url;
            // We don't add to flux_images yet, we do it based on app config order
        }
    }
}

// Handle Named Text Inputs
foreach ($_POST as $key => $value) {
    if (!in_array($key, ['prompt', 'sponsor', 'app_id', 'remix_image_url'])) {
        $named_input_values[$key] = $value;
    }
}

// --- LOGIC START ---

$sponsor_name = $sponsor_data['name'];
$final_prompt_text = "";
$sponsor_mode = 'ambient_prop';
$sponsor_prompt_text = "";

if ($app_id && isset($image_apps[$app_id])) {
    // --- APP MODE ---
    $app = $image_apps[$app_id];
    
    // Determine Sponsor Mode
    $allowed_modes = $app['allowed_sponsor_modes'] ?? ['ambient_prop', 'panel_cameo'];
    $sponsor_modes = $sponsor_data['modes'];
    $valid_modes = array_intersect($allowed_modes, $sponsor_modes);
    $sponsor_mode = !empty($valid_modes) ? array_values($valid_modes)[0] : 'ambient_prop';
    
    // Get Sponsor Prompt
    $raw_sponsor_prompt = $sponsor_data['mode_prompts'][$sponsor_mode] ?? "";
    $sponsor_prompt_text = str_replace('image S', 'image 1', $raw_sponsor_prompt);
    
    // Build Main Prompt
    // If app has 'prompts' array (multi-panel), join them.
    // If 'prompt_template' string, use that.
    $template = "";
    if (isset($app['prompts']) && is_array($app['prompts'])) {
        $template = implode(" ", $app['prompts']);
    } else {
        $template = $app['prompt_template'] ?? ($app['prompts'][0] ?? "");
    }
    
    // Map Inputs to Flux Images and Prompt
    $role_to_image_index = [];
    
    // If we have legacy images[], map them to slots 1, 2, 3...
    if (!empty($image_urls_input)) {
        foreach ($app['inputs'] as $input) {
            $slot = $input['slot']; // 1-based
            if (isset($image_urls_input[$slot - 1])) {
                $role_to_image_index[$input['role']] = $slot + 1; // +1 for sponsor
            }
        }
    }
    
    // Process App Inputs (Named)
    foreach ($app['inputs'] as $input) {
        $role = $input['role'];
        $label = $input['label'];
        $type = $input['type'] ?? 'image'; 
        
        // PHP converts spaces and dots to underscores in POST/FILES keys
        $lookup_key = str_replace([' ', '.'], '_', $role);

        // Check if we have a named value for this role (using normalized key)
        if (isset($named_input_values[$lookup_key])) {
            $val = $named_input_values[$lookup_key];
            
            if ($type === 'image' || isset($_FILES[$lookup_key])) { 
                 // Add to Flux Images list
                 $flux_images[] = $val;
                 $role_to_image_index[$role] = count($flux_images); 
            } else {
                // Text input
            }
        }
    }
    
    // Replace placeholders in template
    foreach ($app['inputs'] as $input) {
        $role = $input['role'];
        $label = $input['label'];
        $type = $input['type'] ?? 'image';
        
        $lookup_key = str_replace([' ', '.'], '_', $role);
        
        if (isset($role_to_image_index[$role])) {
            // It's an image
            $idx = $role_to_image_index[$role];
            $replacement = "the {$label} from image $idx";
            $template = str_replace("[{$role}]", $replacement, $template);
        } elseif (isset($named_input_values[$lookup_key]) && $type !== 'image') {
            // It's a text value
            $template = str_replace("[{$role}]", $named_input_values[$lookup_key], $template);
        } else {
            // Missing input
            $template = str_replace("[{$role}]", "a {$label}", $template);
        }
    }
    
    // CLEANUP
    $template = preg_replace('/\[.*?\]/', '', $template);
    
    // Append User's "Vibe" / Extra Details (global prompt)
    $extra_prompt = $named_input_values['extra_prompt'] ?? $prompt;
    if (!empty($extra_prompt)) {
        $template .= " " . $extra_prompt;
    }
    
    $final_prompt_text = trim($template);
    
} else {
    // --- LEGACY / FREEFORM MODE ---
    $final_prompt_text = $prompt;
    
    // Default Sponsor Logic
    $sponsor_mode = 'ambient_prop';
    $raw_sponsor_prompt = $sponsor_data['mode_prompts'][$sponsor_mode] ?? "";
    $sponsor_prompt_text = str_replace('image S', 'image 1', $raw_sponsor_prompt);
    
    if (empty($final_prompt_text) && empty($image_urls_input)) {
         echo json_encode(['success' => false, 'error' => 'Prompt or Image is required']);
         exit;
    }
}

// 3. Build FLUX JSON
// We use a simplified structure or the one FLUX expects.
// The previous code used a complex JSON structure for "prompt".
// Let's stick to that structure but inject our new text.

$prompt_structure = [
    "scene" => $final_prompt_text,
    "objects" => [
        [
            "description" => $sponsor_prompt_text,
            "source_image" => "image 1", // Sponsor is always image 1
            "role" => "product placement",
            "placement" => "context-aware"
        ]
    ],
    // Add other fields if needed, or keep it simple
    "style" => "photorealistic cinematic",
    "resolution" => "high"
];

$full_prompt_json = json_encode($prompt_structure);

// Call FAL.AI API
$fal_key = getenv('FAL_KEY');
$api_url = "https://queue.fal.run/fal-ai/alpha-image-232/edit-image"; // Always use edit mode as we have sponsor image

$data = [
    "prompt" => $full_prompt_json,
    "image_urls" => $flux_images,
    "image_size" => "landscape_4_3",
    "num_inference_steps" => 28,
    "guidance_scale" => 3.5,
    "strength" => 0.85 
];

$headers = [
    "Authorization: Key $fal_key",
    "Content-Type: application/json"
];

require_once '../logger.php';

// Submit to Queue
$ch = curl_init($api_url);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

// Log the call
logApiCall('create_image', $full_prompt_json, $response, 'flux-pro');

if ($http_code !== 200 && $http_code !== 201 && $http_code !== 202) {
     echo json_encode(['success' => false, 'error' => 'Failed to submit to queue: ' . $response]);
     exit;
}

$queue_result = json_decode($response, true);
$request_id = $queue_result['request_id'] ?? null;

if (!$request_id) {
    echo json_encode(['success' => false, 'error' => 'No request ID returned']);
    exit;
}

// Poll for result
$attempts = 0;
$max_attempts = 120;
$image_url = '';

while ($attempts < $max_attempts) {
    sleep(1);
    $status_url_check = $queue_result['status_url'] ?? "https://queue.fal.run/fal-ai/alpha-image-232/edit-image/requests/$request_id/status";

    $ch = curl_init($status_url_check);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $status_response = curl_exec($ch);
    curl_close($ch);
    
    $status_data = json_decode($status_response, true);
    $status = $status_data['status'] ?? 'UNKNOWN';
    
    if ($status === 'COMPLETED') {
        if (isset($status_data['images'])) {
            $image_url = $status_data['images'][0]['url'];
        } elseif (isset($status_data['response_url'])) {
             $ch2 = curl_init($status_data['response_url']);
             curl_setopt($ch2, CURLOPT_HTTPHEADER, $headers);
             curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
             $final_response = curl_exec($ch2);
             curl_close($ch2);
             $final_data = json_decode($final_response, true);
             $image_url = $final_data['images'][0]['url'] ?? '';
        }
        break;
    } else if ($status === 'FAILED') {
         echo json_encode(['success' => false, 'error' => 'Generation failed']);
         exit;
    }
    $attempts++;
}

if (empty($image_url)) {
    echo json_encode(['success' => false, 'error' => 'Timeout or empty result']);
    exit;
}

// Upload generated image to S3
$temp_fal_image = '../uploads/fal_' . generateUUID() . '.png';
$image_content = file_get_contents($image_url);
if ($image_content !== false) {
    file_put_contents($temp_fal_image, $image_content);
    $s3_fal_url = uploadToS3($temp_fal_image, "generated/" . basename($temp_fal_image), 'image/png');
    if ($s3_fal_url) {
        $image_url = $s3_fal_url;
        unlink($temp_fal_image);
    }
}

// Generate Short Code
function generateShortCode($length = 6) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, strlen($characters) - 1)];
    }
    return $randomString;
}

$short_code = generateShortCode();
$user_id = $_COOKIE['vm_user_id'] ?? 'anonymous';
$app_uuid = generateUUID();

// Store in DB
try {
    $stmt = $pdo->prepare("INSERT INTO apps (uuid, prompt, image_url, sponsor, short_code, user_id, full_prompt, input_images) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$app_uuid, $final_prompt_text, $image_url, $sponsor_name, $short_code, $user_id, $full_prompt_json, json_encode($image_urls_input)]);
    
    echo json_encode([
        'success' => true,
        'app' => [
            'uuid' => $app_uuid,
            'prompt' => $final_prompt_text,
            'image_url' => $image_url,
            'sponsor' => $sponsor_name,
            'short_code' => $short_code,
            'share_url' => "https://" . $_SERVER['HTTP_HOST'] . "/app/" . $short_code
        ]
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'DB Error: ' . $e->getMessage()]);
}
?>
