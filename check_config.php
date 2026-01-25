<?php
// check_config.php
require_once 'include/config.php';

header('Content-Type: text/html');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Config Diagnostic</title>
    <style>
        body { font-family: monospace; background: #1a1a1a; color: #00ff00; padding: 20px; }
        .success { color: #00ff00; }
        .error { color: #ff0000; font-weight: bold; }
        .info { color: #00ffff; }
        hr { border: 1px solid #333; }
    </style>
</head>
<body>
    <h1>Server Diagnostic</h1>
    <hr>
    
    <h3>1. Environment Variables</h3>
    <p>BASE_URL: <span class="info"><?php echo htmlspecialchars(BASE_URL); ?></span></p>
    <p>WHATSAPP_API_URL: <span class="info"><?php echo htmlspecialchars(WHATSAPP_API_URL); ?></span></p>
    <p>WHATSAPP_INSTANCE_ID: <span class="info"><?php echo htmlspecialchars(WHATSAPP_INSTANCE_ID); ?></span></p>
    
    <hr>
    
    <h3>2. Directory Permissions</h3>
    <?php
    $tempDir = __DIR__ . '/temp';
    echo "<p>Temp Directory Path: <span class='info'>$tempDir</span></p>";
    
    if (!is_dir($tempDir)) {
        echo "<p class='error'>[FAIL] Temp directory does not exist.</p>";
        if (mkdir($tempDir, 0777, true)) {
            echo "<p class='success'>[FIXED] Created temp directory.</p>";
        } else {
            echo "<p class='error'>[FAIL] Failed to create temp directory.</p>";
        }
    } else {
        echo "<p class='success'>[OK] Temp directory exists.</p>";
    }
    
    if (is_writable($tempDir)) {
        echo "<p class='success'>[OK] Temp directory is writable.</p>";
        
        $testFile = $tempDir . '/test_write.txt';
        if (file_put_contents($testFile, 'test')) {
            echo "<p class='success'>[OK] Successfully wrote a test file.</p>";
            unlink($testFile);
        } else {
            echo "<p class='error'>[FAIL] Failed to write test file even though directory is 'writable'.</p>";
        }
    } else {
        echo "<p class='error'>[FAIL] Temp directory is NOT writable. Please check permissions (CHMOD 777).</p>";
    }
    ?>
    
    <hr>
    
    <h3>3. PHP Configuration</h3>
    <p>Curl Enabled: <?php echo function_exists('curl_init') ? '<span class="success">Yes</span>' : '<span class="error">No</span>'; ?></p>
    <p>OpenSSL Enabled: <?php echo extension_loaded('openssl') ? '<span class="success">Yes</span>' : '<span class="error">No</span>'; ?></p>
    <p>PHP Version: <span class="info"><?php echo PHP_VERSION; ?></span></p>

    <hr>
    <p class="info">Please check the values above. Specifically, ensure BASE_URL matches your live website exactly.</p>
</body>
</html>
