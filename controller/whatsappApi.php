<?php
// test_whatsapp.php
include '../include/config.php'; // Adjust path as needed
require_once '../include/connection.php'; // Adjust path as needed

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
function whatsappApi($chatId, $message)
{
    // Format chat ID properly (remove spaces, ensure 92 format)
    $chatId = preg_replace('/[^0-9]/', '', $chatId);
    if (!str_starts_with($chatId, '92')) {
        $chatId = '92' . ltrim($chatId, '0');
    }
    
    $params = [
        'instance_id'   => WHATSAPP_INSTANCE_ID,
        'access_token'  => WHATSAPP_ACCESS_TOKEN,
        'chatId'        => $chatId,
        'message'       => $message,
    ];

    $url = WHATSAPP_API_URL . '?' . http_build_query($params);
    
    // Log the request for debugging
    error_log("WhatsApp API Request: " . json_encode([
        'url' => $url,
        'chatId' => $chatId,
        'message_length' => strlen($message)
    ]));

    $curl = curl_init();

    curl_setopt_array($curl, [
        CURLOPT_URL            => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST  => 'POST',
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_SSL_VERIFYPEER => false, // For testing, remove in production
        CURLOPT_SSL_VERIFYHOST => false, // For testing, remove in production
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'Accept: application/json'
        ]
    ]);

    $response = curl_exec($curl);
    $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    $error = curl_error($curl);
    
    curl_close($curl);

    // Log raw response for debugging
    error_log("WhatsApp API Raw Response (HTTP $httpCode): " . $response);
    if ($error) {
        error_log("WhatsApp API cURL Error: " . $error);
    }

    $check = checkWhatsAppResponse($response);

    if ($check['success']) {
        error_log("WhatsApp Message Sent Successfully - ID: " . $check['message_id']);
        return [
            'success' => true, 
            'message' => 'Message sent successfully',
            'message_id' => $check['message_id'],
            'http_code' => $httpCode
        ];
    } else {
        error_log("WhatsApp API Error: " . $check['message'] . " (Code: " . ($check['error_code'] ?? 'N/A') . ")");
        return [
            'success' => false, 
            'message' => "Error: " . $check['message'],
            'error_code' => $check['error_code'] ?? null,
            'http_code' => $httpCode,
            'response' => $response // Return raw response for debugging
        ];
    }
}
function checkWhatsAppResponse($response)
{
    // Check if response is empty
    if (empty($response)) {
        return [
            'success' => false,
            'message' => 'Empty response from WhatsApp API',
            'raw' => $response
        ];
    }
    
    // If response is JSON string, decode it
    if (is_string($response)) {
        $data = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            // Try to see if it's HTML/other format
            if (str_contains($response, '<html') || str_contains($response, '<!DOCTYPE')) {
                return [
                    'success' => false,
                    'message' => 'Received HTML instead of JSON (possible server error)',
                    'raw' => substr($response, 0, 200) . '...'
                ];
            }
            return [
                'success' => false,
                'message' => 'Invalid JSON response: ' . json_last_error_msg(),
                'error' => json_last_error_msg(),
                'raw' => substr($response, 0, 200) . '...'
            ];
        }
    } else {
        $data = $response;
    }
    
    // Check for error response
    if (isset($data['code'])) {
        return [
            'success' => false,
            'message' => $data['message'] ?? 'Unknown error',
            'error_code' => $data['code'],
            'status_code' => $data['data']['status'] ?? 500,
            'details' => $data['data']['details'] ?? [],
            'full_response' => $data
        ];
    }
    
    // Check for specific WhatsApp API error patterns
    if (isset($data['error'])) {
        return [
            'success' => false,
            'message' => $data['error'] . (isset($data['message']) ? ': ' . $data['message'] : ''),
            'error_code' => $data['error'] ?? 'unknown',
            'full_response' => $data
        ];
    }
    
    // Check for success response
    if (isset($data['id']) && strpos($data['id'], 'true_') === 0) {
        $result = [
            'success' => true,
            'message' => 'Message processed successfully',
            'message_id' => $data['id']
        ];
        
        // Add more details if available
        if (isset($data['_data'])) {
            $result['timestamp'] = $data['_data']['Info']['Timestamp'] ?? null;
            $result['chat'] = $data['_data']['Info']['Chat'] ?? null;
            $result['is_from_me'] = $data['_data']['Info']['IsFromMe'] ?? null;
            $result['data'] = $data['_data'];
        }
        
        $result['full_response'] = $data;
        
        return $result;
    }
    
    // Check for alternative success format (some APIs use different structure)
    if (isset($data['sent']) && $data['sent'] === true) {
        return [
            'success' => true,
            'message' => 'Message sent successfully',
            'message_id' => $data['id'] ?? $data['messageId'] ?? null,
            'full_response' => $data
        ];
    }
    
    // Unknown response format
    return [
        'success' => false,
        'message' => 'Unexpected response format from WhatsApp API',
        'raw_response' => $data
    ];
}
?>