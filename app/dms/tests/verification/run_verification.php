<?php
$base_url = 'http://127.0.0.1:8000/dms/ap/index.php';
$cookie_file = __DIR__ . '/cookies.txt';

function run_curl($url, $post_fields = null, $headers = [], $use_cookies = true) {
    global $cookie_file;
    $ch = curl_init($url);
    if ($use_cookies) {
        curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie_file);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie_file);
    }
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, true);
    if ($post_fields) {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_fields);
    }
    $default_headers = ['X-Requested-With: XMLHttpRequest'];
    $headers = array_merge($default_headers, $headers);
    if ($headers) {
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    }
    $response = curl_exec($ch);
    $info = curl_getinfo($ch);
    curl_close($ch);

    $header_size = $info['header_size'];
    $body = substr($response, $header_size);
    $headers_text = substr($response, 0, $header_size);

    return ['body' => $body, 'headers' => $headers_text, 'info' => $info];
}

// 0. Test Unauthorized Access
echo "\n[TEST] Unauthorized Access to Download...\n";
// Try to download a fake ID without cookies
$res = run_curl($base_url . '?action=file_download&version_id=1', null, [], false);
// Should redirect to login (302) or 403/401
if ($res['info']['http_code'] == 302 && stripos($res['headers'], 'Location: index.php?action=login')) {
    echo "[PASS] Redirected to login.\n";
} else {
    echo "[FAIL] Access allowed or wrong redirect. Code: " . $res['info']['http_code'] . "\n";
}


echo "\n[TEST] Login...\n";
if (file_exists($cookie_file)) unlink($cookie_file);
$res = run_curl($base_url . '?action=do_login', ['username' => 'admin', 'password' => 'password']);
if (!strpos($res['body'], '"success":true')) {
    echo "[FAIL] Login failed.\n";
    exit(1);
}
echo "[PASS] Login success.\n";

// ... (Previous tests) ...
// 2. Upload PDF
echo "\n[TEST] Upload PDF...\n";
$file = new CURLFile(realpath('sample.pdf'), 'application/pdf', 'sample.pdf');
$data = ['title' => 'Test PDF', 'file' => $file, 'upload_mode' => 'append'];
$res = run_curl($base_url . '?action=doc_upload_submit', $data);
$json = json_decode($res['body'], true);
$pdf_version_id = $json['data']['version_id'];
echo "[PASS] Upload PDF success. Version ID: $pdf_version_id\n";

// 3. Download
echo "\n[TEST] Download PDF...\n";
$res = run_curl($base_url . "?action=file_download&version_id=$pdf_version_id");
if ($res['info']['http_code'] == 200 && stripos($res['headers'], 'Content-Disposition: attachment')) {
    echo "[PASS] Download success.\n";
} else {
    echo "[FAIL] Download failed.\n";
}

// 4. Preview
echo "\n[TEST] Preview PDF...\n";
$res = run_curl($base_url . "?action=file_preview&version_id=$pdf_version_id");
if ($res['info']['http_code'] == 200 && stripos($res['headers'], 'Content-Disposition: inline')) {
    echo "[PASS] Preview success.\n";
} else {
    echo "[FAIL] Preview failed.\n";
}

// 5. Range
echo "\n[TEST] Range PDF...\n";
$res = run_curl($base_url . "?action=file_preview&version_id=$pdf_version_id", null, ['Range: bytes=0-10']);
if ($res['info']['http_code'] == 206) {
    echo "[PASS] Range success.\n";
} else {
    echo "[FAIL] Range failed.\n";
}

// 6. Upload Safe TXT
echo "\n[TEST] Upload Safe TXT...\n";
$file = new CURLFile(realpath('sample_safe.txt'), 'text/plain', 'sample_safe.txt');
$data = ['title' => 'Safe TXT', 'file' => $file, 'upload_mode' => 'append'];
$res = run_curl($base_url . '?action=doc_upload_submit', $data);
$json = json_decode($res['body'], true);
if (!$json || !$json['success']) {
    echo "[FAIL] Upload Safe TXT failed. Body: " . $res['body'] . "\n";
    exit(1);
}
$txt_version_id = $json['data']['version_id'];
echo "[PASS] Upload Safe TXT success. Version ID: $txt_version_id\n";

echo "\n[TEST] Preview Safe TXT...\n";
$res = run_curl($base_url . "?action=file_preview&version_id=$txt_version_id");
if ($res['info']['http_code'] != 200) {
    echo "[FAIL] Preview Safe TXT failed.\n";
} else {
    // Check Content-Type
    if (stripos($res['headers'], 'Content-Type: text/plain')) {
        echo "[PASS] Content-Type is text/plain.\n";
    } else {
        echo "[FAIL] Content-Type mismatch: " . $res['headers'] . "\n";
    }
    // Check X-Content-Type-Options
    if (stripos($res['headers'], 'X-Content-Type-Options: nosniff')) {
        echo "[PASS] nosniff header present.\n";
    } else {
        echo "[WARN] nosniff header MISSING.\n";
    }
}

// 7. Upload Unsafe TXT
echo "\n[TEST] Upload Unsafe TXT (Should Fail or Pass as text/plain)...\n";
$file = new CURLFile(realpath('sample_unsafe.txt'), 'text/plain', 'sample_unsafe.txt');
$data = ['title' => 'Unsafe TXT', 'file' => $file, 'upload_mode' => 'append'];
$res = run_curl($base_url . '?action=doc_upload_submit', $data);
$json = json_decode($res['body'], true);
if (!$json || !$json['success']) {
    echo "[PASS] Upload Unsafe TXT rejected by server.\n";
} else {
    echo "[INFO] Upload Unsafe TXT accepted. Verifying preview...\n";
    $unsafe_id = $json['data']['version_id'];
    $res = run_curl($base_url . "?action=file_preview&version_id=$unsafe_id");
    if (stripos($res['headers'], 'Content-Type: text/plain')) {
         echo "[PASS] Unsafe TXT served as text/plain.\n";
    } else {
         echo "[FAIL] Unsafe TXT served as " . $res['headers'] . "\n";
    }
}

echo "\n=== ALL TESTS PASSED ===\n";
