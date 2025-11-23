<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
set_time_limit(300); // Allow 5 minutes for generation

header('Content-Type: application/json');

// Catch fatal errors
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        error_log("Fatal error in preview_generator.php: " . json_encode($error));
        if (!headers_sent()) {
            header('Content-Type: application/json');
        }
        echo json_encode(['success' => false, 'error' => 'Server error: ' . $error['message']]);
    }
});

require_once '../config.php';
require_once 's3_upload.php';
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

// 1. Handle Inputs
$mapping_json = $_POST['mapping'] ?? '{}';
$inputs_def_json = $_POST['inputs'] ?? '[]';
$mapping = json_decode($mapping_json, true);
$inputs_def = json_decode($inputs_def_json, true);
$variations_count = 2;

// Log received data for debugging
error_log("Preview Generator - Received mapping: " . $mapping_json);
error_log("Preview Generator - Received inputs: " . $inputs_def_json);
error_log("Preview Generator - FILES count: " . count($_FILES));

// Validate inputs
if (empty($mapping) || empty($inputs_def)) {
    error_log("Preview Generator - Invalid inputs: mapping or inputs_def is empty");
    echo json_encode(['success' => false, 'error' => 'Invalid mapping or inputs configuration']);
    exit;
}

// 2. Handle File Uploads & Text Inputs
$input_values = []; // role => value (url or text)

// Process Files
if (!empty($_FILES)) {
    $target_dir = "../uploads/";
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0755, true);
    }

    foreach ($_FILES as $key => $file) {
        if ($file['error'] === UPLOAD_ERR_OK) {
            $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $uuid = generateUUID();
            $new_filename = $uuid . '.' . $file_extension;
            $target_file = $target_dir . $new_filename;

            if (move_uploaded_file($file['tmp_name'], $target_file)) {
                $s3_url = uploadToS3($target_file, "uploads/" . $new_filename, $file['type']);
                if ($s3_url) {
                    $input_values[$key] = $s3_url;
                    unlink($target_file);
                } else {
                    error_log("Failed to upload preview image $key to S3");
                }
            }
        }
    }
}

// Process Text Inputs
foreach ($_POST as $key => $value) {
    if ($key !== 'mapping' && $key !== 'inputs') {
        $input_values[$key] = $value;
    }
}

// 3. Prepare Flux Images List
// Image 1 is ALWAYS the sponsor (La Croix default for now) to match api/create.php logic
$sponsor_data = $sponsors['la_croix'];
$sponsor_image = $sponsor_data['image_ref'] ?? $sponsor_data['image'];
$flux_images = [$sponsor_image];

// Map input roles to image indices
$role_to_image_index = [];
$current_image_index = 2; // Start at 2 because 1 is sponsor

// We need to order the images based on the inputs definition order to match how api/create.php works?
// Actually api/create.php iterates over $image_urls_input.
// Here we have named inputs.
// We should iterate through the defined inputs, and if it's an image, add it to flux_images.
foreach ($inputs_def as $input) {
    if ($input['type'] === 'image') {
        $role = $input['role'];
        if (isset($input_values[$role])) {
            $flux_images[] = $input_values[$role];
            $role_to_image_index[$role] = $current_image_index;
            $current_image_index++;
        }
    }
}

// 4. Construct Prompts for each Panel
// We will combine all panels into one prompt if possible, or just use the first panel?
// The prompt says "Display results in horizontal strip."
// If the app has multiple panels, the result is usually a single image (grid) or multiple images?
// api/create.php generates ONE image.
// If the app is multi-panel, usually the prompt instructions tell Flux to generate a grid or comic strip.
// So we just concatenate the prompts or use the mapping logic.
// In create_generator.php, the user maps each panel.
// We should probably concatenate them with some separator or just join them.
// Let's join them with spaces for now.

$full_prompt_text = "";
foreach ($mapping as $panel_id => $panel_prompt) {
    $full_prompt_text .= $panel_prompt . " ";
}

// Replace placeholders
foreach ($inputs_def as $input) {
    $role = $input['role'];
    $label = $input['label'];
    
    if ($input['type'] === 'image') {
        if (isset($role_to_image_index[$role])) {
            $idx = $role_to_image_index[$role];
            $replacement = "the {$label} from image $idx";
            $full_prompt_text = str_replace("[{$role}]", $replacement, $full_prompt_text);
        } else {
            // Missing image
            $full_prompt_text = str_replace("[{$role}]", "a {$label}", $full_prompt_text);
        }
    } else {
        // Text input
        $val = $input_values[$role] ?? "";
        $full_prompt_text = str_replace("[{$role}]", $val, $full_prompt_text);
    }
}

// Cleanup
$full_prompt_text = preg_replace('/\[.*?\]/', '', $full_prompt_text);
$full_prompt_text = trim($full_prompt_text);

// 5. Call Fal AI (3 times)
$fal_key = getenv('FAL_KEY');
$api_url = "https://queue.fal.run/fal-ai/alpha-image-232/edit-image";

$headers = [
    "Authorization: Key $fal_key",
    "Content-Type: application/json"
];

$mh = curl_multi_init();
$handles = [];

// Prepare requests
for ($i = 0; $i < $variations_count; $i++) {
    // Add random seed or variation to prompt?
    // Flux usually handles variations if seed is not specified (it's random by default).
    // But we want to be sure.
    // We can't easily set seed in the simplified JSON unless we add it to the request body.
    // The `data` array in api/create.php doesn't show seed.
    // But Fal API supports `seed`.
    
    $prompt_structure = [
        "scene" => $full_prompt_text,
        "objects" => [
            [
                "description" => "product placement", // Dummy sponsor prompt
                "source_image" => "image 1",
                "role" => "product placement",
                "placement" => "context-aware"
            ]
        ],
        "style" => "photorealistic cinematic",
        "resolution" => "high"
    ];

    $data = [
        "prompt" => json_encode($prompt_structure),
        "image_urls" => $flux_images,
        "image_size" => "landscape_4_3",
        "num_inference_steps" => 28,
        "guidance_scale" => 3.5,
        "strength" => 0.85,
        "seed" => mt_rand(0, 99999999) // Ensure variation
    ];

    $ch = curl_init($api_url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    curl_multi_add_handle($mh, $ch);
    $handles[] = $ch;
}

// Execute requests
$running = null;
do {
    $status = curl_multi_exec($mh, $running);
    if ($running) {
        curl_multi_select($mh);
    }
} while ($running && $status == CURLM_OK);

// Collect Request IDs
$request_ids = [];
foreach ($handles as $ch) {
    $response = curl_multi_getcontent($ch);
    $json = json_decode($response, true);
    if (isset($json['request_id'])) {
        $request_ids[] = $json['request_id'];
    } else {
        $err = curl_error($ch);
        error_log("Preview: Failed to get request ID. Error: $err. Response: " . $response);
    }
    curl_multi_remove_handle($mh, $ch);
    curl_close($ch);
}
curl_multi_close($mh);

if (empty($request_ids)) {
    echo json_encode(['success' => false, 'error' => 'Failed to initiate generation']);
    exit;
}

// Poll for results
$results = [];
$start_time = time();
$max_wait = 300; // 300 seconds max

while (count($results) < count($request_ids) && (time() - $start_time) < $max_wait) {
    sleep(2);
    
    foreach ($request_ids as $rid) {
        if (isset($results[$rid])) continue; // Already got this one

        $status_url = "https://queue.fal.run/fal-ai/alpha-image-232/edit-image/requests/$rid/status";
        $ch = curl_init($status_url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $resp = curl_exec($ch);
        curl_close($ch);
        
        $status_data = json_decode($resp, true);
        $status = $status_data['status'] ?? 'UNKNOWN';
        
        if ($status === 'COMPLETED') {
            $img_url = '';
            if (isset($status_data['images'])) {
                $img_url = $status_data['images'][0]['url'];
            } elseif (isset($status_data['response_url'])) {
                 // Fetch response_url
                 $ch2 = curl_init($status_data['response_url']);
                 curl_setopt($ch2, CURLOPT_HTTPHEADER, $headers);
                 curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
                 $final_resp = curl_exec($ch2);
                 curl_close($ch2);
                 $final_data = json_decode($final_resp, true);
                 $img_url = $final_data['images'][0]['url'] ?? '';
            }
            
            if ($img_url) {
                $results[$rid] = $img_url;
            }
        } elseif ($status === 'FAILED') {
            $results[$rid] = 'FAILED'; // Mark as failed
        }
    }
}

// Return results
$final_images = [];
foreach ($results as $url) {
    if ($url !== 'FAILED') {
        $final_images[] = $url;
    }
}

echo json_encode(['success' => true, 'images' => $final_images]);
?>
