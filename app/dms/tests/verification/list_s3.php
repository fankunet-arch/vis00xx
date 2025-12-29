<?php
define('DMS_ENTRY', true);
$DMS_CONFIG = require 'app/dms/config_dms/env_dms.php';
require 'app/dms/lib/dms_s3_client.php';

echo "Listing bucket contents...\n";
$uri = '/' . $DMS_CONFIG['s3_bucket'] . '?list-type=2';
$headers = dms_s3_sign_request('GET', $uri, []);
$curl_headers = [];
foreach ($headers as $k => $v) { $curl_headers[] = "$k: $v"; }

$ch = curl_init($DMS_CONFIG['s3_endpoint'] . $uri);
curl_setopt($ch, CURLOPT_HTTPHEADER, $curl_headers);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$res = curl_exec($ch);
echo $res;
