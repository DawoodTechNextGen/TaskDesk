<?php
include_once '../include/config.php';
include_once '../include/connection.php';

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
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json']
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

/**
 * Sends a file via WhatsApp API
 */
function whatsappFileApi($chatId, $fileUrl, $fileName, $caption = "") {
    $chatId = preg_replace('/[^0-9]/', '', $chatId);
    if (!str_starts_with($chatId, '92')) {
        $chatId = '92' . ltrim($chatId, '0');
    }

    // Determine mimetype based on extension
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

    // The sendFile endpoint is usually different or handled via query params in some versions
    // Based on user snippet: https://wawp.net/wp-json/awp/v1/sendFile
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
