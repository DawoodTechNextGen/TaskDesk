<?php
// test_whatsapp.php
require_once 'PdfwhatsappApi.php';

function testWhatsAppApi() {
    $test_number = "923061061544"; // Test with your number
    $test_message = "Test message from API at " . date('Y-m-d H:i:s');
    
    $result = whatsappApi($test_number, $test_message);
    
    echo "<h2>WhatsApp API Test Result:</h2>";
    echo "<pre>";
    print_r($result);
    echo "</pre>";
    
    if (isset($result['response'])) {
        echo "<h3>Raw Response:</h3>";
        echo "<pre>" . htmlspecialchars($result['response']) . "</pre>";
    }
}

testWhatsAppApi();
?>
?>