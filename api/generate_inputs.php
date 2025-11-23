<?php
header('Content-Type: application/json');
require_once '../config.php';

// Allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit;
}

// Get input
$input = json_decode(file_get_contents('php://input'), true);
$appName = $input['appName'] ?? '';
$appDescription = $input['appDescription'] ?? '';
$inputsSchema = $input['inputsSchema'] ?? [];
$userVibe = $input['userVibe'] ?? '';

if (empty($appName) || empty($inputsSchema)) {
    echo json_encode(['success' => false, 'error' => 'Missing app context']);
    exit;
}

$apiKey = getenv('GEMINI_API_KEY');
if (!$apiKey) {
    echo json_encode(['success' => false, 'error' => 'Server configuration error: GEMINI_API_KEY missing']);
    exit;
}

// Filter for text inputs only
$textInputs = array_filter($inputsSchema, function($i) {
    return isset($i['type']) && $i['type'] === 'text';
});

if (empty($textInputs)) {
    echo json_encode(['success' => false, 'error' => 'No text inputs to fill']);
    exit;
}

// Construct Prompt
$fieldsList = [];
foreach ($textInputs as $i) {
    $fieldsList[] = "- {$i['label']} (Role: {$i['role']})";
}
$fieldsStr = implode("\n", $fieldsList);

$systemInstruction = "You are a creative AI assistant for a generative app platform using the FLUX.1 model.
Your goal is to invent creative, fun, and coherent details for a user's app creation.

App Name: $appName
App Description: $appDescription

The user wants to fill these text fields:
$fieldsStr

User's Vibe/Idea: " . ($userVibe ? $userVibe : "Surprise me with something cool/funny/creative.") . "

Output MUST be valid JSON where keys are the 'Role' and values are the creative content.

CRITICAL PROMPTING RULES (FLUX.1):
1. **Natural Language**: Do NOT use lists or 'key: value' formats in the output values. Write full, descriptive sentences.
2. **Text Rendering**: If generating text content (names, stats), write: \"The text 'XYZ' is written clearly...\" or \"with the word 'XYZ' displayed...\".
3. **Visuals**: Describe textures, lighting, and style vividly.
4. **No Placeholders**: Do not use [brackets] in your output.

Example:
{
  \"prompt\": \"A cinematic shot of a golden robot named Zorg. The name 'ZORG' is written in neon blue letters on its chest. It has a rusty texture and glowing eyes.\"
}
";

// Call Gemini API
$url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash-lite:generateContent?key=" . $apiKey;

$data = [
    "contents" => [
        [
            "parts" => [
                ["text" => $systemInstruction]
            ]
        ]
    ],
    "generationConfig" => [
        "response_mime_type" => "application/json"
    ]
];

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 200) {
    echo json_encode(['success' => false, 'error' => 'Gemini API Error: ' . $response]);
    exit;
}

require_once '../logger.php';

$result = json_decode($response, true);
$generatedText = $result['candidates'][0]['content']['parts'][0]['text'] ?? '';

// Extract Token Usage
$tokenCount = $result['usageMetadata']['totalTokenCount'] ?? null;

// Log the call
logApiCall('generate_inputs', $systemInstruction, $generatedText, 'gemini-2.5-flash-lite', $tokenCount);

// Extract JSON
if (preg_match('/```json\s*(.*?)\s*```/s', $generatedText, $matches)) {
    $jsonStr = $matches[1];
} else {
    $jsonStr = $generatedText;
}

$data = json_decode($jsonStr, true);

if ($data) {
    echo json_encode(['success' => true, 'data' => $data]);
} else {
    echo json_encode(['success' => false, 'error' => 'Failed to parse Gemini response', 'raw' => $generatedText]);
}
?>
