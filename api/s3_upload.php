<?php
require_once __DIR__ . '/../config.php';

function uploadToS3($file_path, $s3_key, $content_type = 'image/png') {
    $access_key = getenv('S3_ACCESS');
    $secret_key = getenv('S3_SECRET');
    $bucket = getenv('S3_BUCKET');
    $region = getenv('S3_REGION');
    
    if (!$access_key || !$secret_key || !$bucket || !$region) {
        error_log("S3 Credentials missing - Access: " . ($access_key ? 'SET' : 'MISSING') . 
                  ", Secret: " . ($secret_key ? 'SET' : 'MISSING') . 
                  ", Bucket: " . ($bucket ?: 'MISSING') . 
                  ", Region: " . ($region ?: 'MISSING'));
        return false;
    }
    
    if (!file_exists($file_path)) {
        error_log("S3 Upload: File does not exist: $file_path");
        return false;
    }

    $host_name = "$bucket.s3.$region.amazonaws.com";
    $service = 's3';
    $timestamp = gmdate('Ymd\THis\Z');
    $date = gmdate('Ymd');

    // Prepare headers for signature
    $headers = [
        "content-type" => $content_type,
        "host" => $host_name,
        "x-amz-content-sha256" => hash_file('sha256', $file_path),
        "x-amz-date" => $timestamp,
    ];
    
    ksort($headers);
    $canonical_headers = "";
    $signed_headers = "";
    foreach ($headers as $key => $value) {
        $canonical_headers .= strtolower($key) . ":" . trim($value) . "\n";
        $signed_headers .= strtolower($key) . ";";
    }
    $signed_headers = rtrim($signed_headers, ";");

    $canonical_request = "PUT\n" .
        "/" . $s3_key . "\n" .
        "\n" . // Query string
        $canonical_headers . "\n" .
        $signed_headers . "\n" .
        $headers['x-amz-content-sha256'];

    $algorithm = "AWS4-HMAC-SHA256";
    $credential_scope = "$date/$region/$service/aws4_request";
    $string_to_sign = "$algorithm\n$timestamp\n$credential_scope\n" . hash('sha256', $canonical_request);

    $kSecret = 'AWS4' . $secret_key;
    $kDate = hash_hmac('sha256', $date, $kSecret, true);
    $kRegion = hash_hmac('sha256', $region, $kDate, true);
    $kService = hash_hmac('sha256', $service, $kRegion, true);
    $kSigning = hash_hmac('sha256', 'aws4_request', $kService, true);
    $signature = hash_hmac('sha256', $string_to_sign, $kSigning);

    $authorization = "$algorithm Credential=$access_key/$credential_scope, SignedHeaders=$signed_headers, Signature=$signature";

    $url = "https://$host_name/$s3_key";
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_PUT, 1);
    curl_setopt($ch, CURLOPT_INFILE, fopen($file_path, 'r'));
    curl_setopt($ch, CURLOPT_INFILESIZE, filesize($file_path));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Host: $host_name",
        "x-amz-date: $timestamp",
        "x-amz-content-sha256: " . $headers['x-amz-content-sha256'],
        "Authorization: $authorization",
        "Content-Type: $content_type"
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);

    // Log all responses for debugging
    error_log("S3 Upload attempt: $s3_key | HTTP Code: $http_code | File: $file_path");

    if ($http_code == 200) {
        error_log("S3 Upload SUCCESS: $url");
        return $url;
    } else {
        $error_msg = "S3 Upload Failed: HTTP $http_code";
        if ($curl_error) {
            $error_msg .= " | CURL Error: $curl_error";
        }
        if ($response) {
            $error_msg .= " | Response: $response";
        }
        error_log($error_msg);
        return false;
    }
}
?>
