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
    $sponsor = $input['sponsor'] ?? 'cola';
    $remix_image_url = $input['remix_image_url'] ?? '';
} else {
    $prompt = $_POST['prompt'] ?? '';
    $sponsor = $_POST['sponsor'] ?? 'cola';
    $remix_image_url = $_POST['remix_image_url'] ?? '';
}

if (empty($prompt) && empty($_FILES['images']) && empty($remix_image_url)) {
    echo json_encode(['success' => false, 'error' => 'Prompt or Image is required']);
    exit;
}

// Handle Multiple Image Uploads
$image_urls_input = [];

if (!empty($_FILES['images']['name'][0])) {
    $target_dir = "../uploads/";
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0755, true);
    }
    
    $file_count = count($_FILES['images']['name']);
    
    for ($i = 0; $i < $file_count; $i++) {
        if ($_FILES['images']['error'][$i] === UPLOAD_ERR_OK) {
            $file_extension = strtolower(pathinfo($_FILES["images"]["name"][$i], PATHINFO_EXTENSION));
            $uuid = generateUUID();
            $new_filename = $uuid . '.' . $file_extension;
            $target_file = $target_dir . $new_filename;
            
            if (move_uploaded_file($_FILES["images"]["tmp_name"][$i], $target_file)) {
                $s3_url = uploadToS3($target_file, "uploads/" . $new_filename, $_FILES["images"]["type"][$i]);
                if ($s3_url) {
                    $image_urls_input[] = $s3_url;
                    unlink($target_file); // Remove local file after upload
                } else {
                    error_log("Failed to upload input image #$i to S3: " . $_FILES["images"]["name"][$i]);
                    echo json_encode(['success' => false, 'error' => 'Failed to upload image to S3: ' . $_FILES["images"]["name"][$i]]);
                    exit;
                }
            } else {
                echo json_encode(['success' => false, 'error' => 'Failed to upload image: ' . $_FILES["images"]["name"][$i]]);
                exit;
            }
        }
    }
} elseif (!empty($remix_image_url)) {
    $image_urls_input[] = $remix_image_url;
}


// Sponsor prompt injection
$sponsor_prompts = [
    'cola' => 'holding a can of Generic Cola, red and white branding',
    'sneakers' => 'wearing Fast Sneakers, dynamic sporty footwear',
    'burger' => 'eating a Mega Burger, delicious fast food'
];
$sponsor_text = $sponsor_prompts[$sponsor] ?? '';
$full_prompt = "$prompt, $sponsor_text, high quality, photorealistic, cinematic lighting";

// Call FAL.AI API
$fal_key = getenv('FAL_KEY');

// Determine Model and Data
if (!empty($image_urls_input)) {
    // Image Edit Mode
    $api_url = "https://queue.fal.run/fal-ai/alpha-image-232/edit-image";
    $data = [
        "prompt" => $full_prompt,
        "image_urls" => $image_urls_input,
        "image_size" => "landscape_4_3",
        "num_inference_steps" => 28,
        "guidance_scale" => 3.5,
        "strength" => 0.85 // Adjust strength as needed for edit
    ];
} else {
    // Text-to-Image Mode
    $api_url = "https://queue.fal.run/fal-ai/beta-image-232";
    $data = [
        "prompt" => $full_prompt,
        "image_size" => "landscape_4_3",
        "num_inference_steps" => 28,
        "guidance_scale" => 3.5
    ];
}


$headers = [
    "Authorization: Key $fal_key",
    "Content-Type: application/json"
];

// 1. Submit to Queue
$ch = curl_init($api_url);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

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

// 2. Poll for result
$attempts = 0;
$max_attempts = 30;
$image_url = '';

while ($attempts < $max_attempts) {
    sleep(1);
    // Note: Status URL format might differ for alpha model? Usually standard queue format.
    // Assuming standard queue URL structure works for both.
    $status_url_check = "https://queue.fal.run/fal-ai/beta-image-232/requests/$request_id/status";
    // Wait, if using alpha model, check if status URL base is different.
    // Usually queue.fal.run/REQUEST_ID/status works regardless of model path if using request_id.
    // But let's be safe and use the status_url from response if available, or construct based on model.
    
    if (isset($queue_result['status_url'])) {
        $status_url_check = $queue_result['status_url'];
    } else {
        // Fallback construction
        $model_path = !empty($image_url_input) ? "fal-ai/alpha-image-232/edit-image" : "fal-ai/beta-image-232";
        $status_url_check = "https://queue.fal.run/$model_path/requests/$request_id/status";
    }

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
            // Fetch result from response_url
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
    $stmt = $pdo->prepare("INSERT INTO apps (uuid, prompt, image_url, sponsor, short_code, user_id) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$app_uuid, $prompt, $image_url, $sponsor, $short_code, $user_id]);
    
    echo json_encode([
        'success' => true,
        'app' => [
            'uuid' => $app_uuid,
            'prompt' => $prompt,
            'image_url' => $image_url,
            'sponsor' => $sponsor,
            'short_code' => $short_code,
            'share_url' => "http://" . $_SERVER['HTTP_HOST'] . "/app/" . $short_code
        ]
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'DB Error: ' . $e->getMessage()]);
}
?>
