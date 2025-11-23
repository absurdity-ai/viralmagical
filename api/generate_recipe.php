<?php
header('Content-Type: application/json');
require_once '../config.php'; // Ensure we have access to env vars

// Allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit;
}

// Get input
$input = json_decode(file_get_contents('php://input'), true);
$appName = $input['appName'] ?? '';
$description = $input['description'] ?? '';

if (empty($appName)) {
    echo json_encode(['success' => false, 'error' => 'App Name is required']);
    exit;
}

$apiKey = getenv('GEMINI_API_KEY');
if (!$apiKey) {
    echo json_encode(['success' => false, 'error' => 'Server configuration error: GEMINI_API_KEY missing']);
    exit;
}

// Construct Prompt
$systemInstruction = "You are an expert AI App Designer for a platform called ViralMagical. 
Your goal is to create a JSON recipe for a generative AI app based on a user's vague name or description.

The App Structure is:
1. Inputs: What the user uploads (usually 'character') or types.
2. Mapping: A prompt template for each panel.

Output MUST be valid JSON with this structure:
{
  \"inputs\": [
    { \"role\": \"character\", \"label\": \"Character Image\", \"type\": \"image\", \"required\": true },
    { \"role\": \"topic\", \"label\": \"Topic\", \"type\": \"text\", \"required\": false }
  ],
  \"panels\": [
    { \"name\": \"Panel 1\", \"prompt\": \"A cinematic shot of [character]...\" },
    { \"name\": \"Panel 2\", \"prompt\": \"Close up of [character]...\" }
  ]
}

Rules:
- 'role' keys must be unique (e.g., character, style, background).
- 'prompt' must be descriptive and visual. Use [role] to insert inputs.
- If the user input implies a specific style (e.g., 'Pokemon Card', 'Wanted Poster'), bake that style into the 'prompt' text heavily.
- **CRITICAL FOR TEXT**: If the app involves text (cards, posters, signs), you MUST explicitly state: "The text '[role]' is written clearly...". Do NOT ask for "relevant stats" or "descriptions" unless you have an input for them, otherwise the AI will generate garbled gibberish.
- Keep prompts focused on visual composition.
- Create 1-3 panels depending on the complexity.
";

$userPrompt = "Create an app recipe for: '$appName'. Description: '$description'.";

// Call Gemini API
$url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash-lite:generateContent?key=" . $apiKey;

$data = [
    "contents" => [
        [
            "parts" => [
                ["text" => $systemInstruction . "\n\nUser Request: " . $userPrompt]
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
logApiCall('generate_recipe', $systemInstruction . "\n\nUser Request: " . $userPrompt, $generatedText, 'gemini-2.5-flash-lite', $tokenCount);

// Extract JSON from text (in case it's wrapped in markdown)
if (preg_match('/```json\s*(.*?)\s*```/s', $generatedText, $matches)) {
    $jsonStr = $matches[1];
} else {
    $jsonStr = $generatedText;
}

$recipe = json_decode($jsonStr, true);

if ($recipe) {
    echo json_encode(['success' => true, 'recipe' => $recipe]);
} else {
    echo json_encode(['success' => false, 'error' => 'Failed to parse Gemini response', 'raw' => $generatedText]);
}
?>
