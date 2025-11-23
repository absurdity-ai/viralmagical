<?php
// api/proxy_image.php

// Allow access from any origin (or restrict to your domain in production)
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET");

if (!isset($_GET['url'])) {
    http_response_code(400);
    die('Missing URL parameter');
}

$url = $_GET['url'];

// Basic validation: ensure it's a valid URL
if (!filter_var($url, FILTER_VALIDATE_URL)) {
    http_response_code(400);
    die('Invalid URL');
}

// Optional: Restrict to specific domains if needed for security
// $allowed_domains = ['viralmagical.s3.us-east-1.amazonaws.com', 'other-trusted-domain.com'];
// $host = parse_url($url, PHP_URL_HOST);
// if (!in_array($host, $allowed_domains)) { ... }

// Initialize cURL
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Use with caution in production
curl_setopt($ch, CURLOPT_USERAGENT, 'ViralMagical-Proxy/1.0');

$data = curl_exec($ch);
$contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);

curl_close($ch);

if ($httpCode !== 200 || !$data) {
    http_response_code(500);
    die('Failed to fetch image: ' . ($error ? $error : "HTTP $httpCode"));
}

// Forward the content type
header("Content-Type: $contentType");
echo $data;
