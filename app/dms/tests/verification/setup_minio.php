<?php
define('DMS_ENTRY', true);
$DMS_CONFIG = require 'app/dms/config_dms/env_dms.php';
require 'app/dms/lib/dms_s3_client.php';

echo "Setting up MinIO...\n";
echo "Endpoint: " . $DMS_CONFIG['s3_endpoint'] . "\n";
echo "Bucket: " . $DMS_CONFIG['s3_bucket'] . "\n";

// Create Bucket
$uri = '/' . $DMS_CONFIG['s3_bucket'];
$headers = dms_s3_sign_request('PUT', $uri, []);
$curl_headers = [];
foreach ($headers as $k => $v) { $curl_headers[] = "$k: $v"; }

$ch = curl_init($DMS_CONFIG['s3_endpoint'] . $uri);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
curl_setopt($ch, CURLOPT_HTTPHEADER, $curl_headers);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$res = curl_exec($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
echo "Create Bucket Result: Code $code\n";
if ($code != 200 && $code != 409) { // 409 if exists
    echo "Error: $res\n";
    exit(1);
}
echo "Bucket ready.\n";
