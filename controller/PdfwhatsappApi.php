<?php
include_once __DIR__ . '/../include/config.php';
include_once __DIR__ . '/../include/connection.php';

/**
 * Checks the response from WhatsApp API
 */
function checkWhatsAppResponse($response)
{
    if (empty($response)) {
        return ['success' => false, 'message' => 'Empty response from WhatsApp API', 'raw' => $response];
    }
    
    if (is_string($response)) {
        $data = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            if (str_contains($response, '<html') || str_contains($response, '<!DOCTYPE')) {
                return ['success' => false, 'message' => 'Received HTML instead of JSON', 'raw' => substr($response, 0, 200) . '...'];
            }
            return ['success' => false, 'message' => 'Invalid JSON response: ' . json_last_error_msg(), 'raw' => substr($response, 0, 200) . '...'];
        }
    } else {
        $data = $response;
    }
    
    if (isset($data['code'])) {
        return [
            'success' => false,
            'message' => $data['message'] ?? 'Unknown error',
            'error_code' => $data['code'],
            'status_code' => $data['data']['status'] ?? 500,
            'details' => $data['data']['details'] ?? []
        ];
    }
    
    if (isset($data['error'])) {
        return [
            'success' => false,
            'message' => $data['error'] . (isset($data['message']) ? ': ' . $data['message'] : ''),
            'error_code' => $data['error'] ?? 'unknown'
        ];
    }
    
    if (isset($data['id']) && strpos($data['id'], 'true_') === 0) {
        return ['success' => true, 'message' => 'Message processed successfully', 'message_id' => $data['id']];
    }
    
    if (isset($data['sent']) && $data['sent'] === true) {
        return ['success' => true, 'message' => 'Message sent successfully', 'message_id' => $data['id'] ?? $data['messageId'] ?? null];
    }
    
    return ['success' => false, 'message' => 'Unexpected response format from WhatsApp API', 'raw_response' => $data];
}

/**
 * Sends a message via WhatsApp API
 */
function whatsappApi($chatId, $message) {
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

    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL            => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST  => 'POST',
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json', 'Accept: application/json']
    ]);

    $response = curl_exec($curl);
    $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);

    $check = checkWhatsAppResponse($response);
    if ($check['success']) {
        return ['success' => true, 'message' => 'Message sent successfully', 'message_id' => $check['message_id'], 'http_code' => $httpCode];
    } else {
        return ['success' => false, 'message' => "Error: " . $check['message'], 'http_code' => $httpCode, 'response' => $response];
    }
}

/**
 * Sends a file via WhatsApp API
 */
function whatsappFileApi($chatId, $fileUrl, $fileName, $caption = "") {
    $chatId = preg_replace('/[^0-9]/', '', $chatId);
    if (!str_starts_with($chatId, '92')) {
        $chatId = '92' . ltrim($chatId, '0');
    }

    $ext = pathinfo($fileName, PATHINFO_EXTENSION);
    $mimetype = ($ext == 'pdf') ? 'application/pdf' : 'application/octet-stream';

    $params = [
        'instance_id'  => WHATSAPP_INSTANCE_ID,
        'access_token' => WHATSAPP_ACCESS_TOKEN,
        'chatId'       => $chatId,
        'file' => [
            'url'      => $fileUrl,
            'filename' => $fileName,
            'mimetype' => $mimetype
        ],
        'caption'      => $caption
    ];

    $apiUrl = str_replace('/send', '/sendFile', WHATSAPP_API_URL);
    $url = $apiUrl . '?' . http_build_query($params);

    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL            => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST  => 'POST',
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_HTTPHEADER     => ['Accept: application/json']
    ]);

    $response = curl_exec($curl);
    $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);

    return [
        'success' => ($httpCode == 200),
        'response' => $response,
        'http_code' => $httpCode
    ];
}
