<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


define('API_BASE_URL', 'http://127.0.0.1:8000/api');


function callAPI($method, $endpoint, $data = false)
{
    $curl = curl_init();
    $url = API_BASE_URL . $endpoint;


    $headers = [
        'Accept: application/json',
        'Content-Type: application/json'
    ];


    if (isset($_SESSION['api_token'])) {
        $headers[] = 'Authorization: Bearer ' . $_SESSION['api_token'];
    }


    switch (strtoupper($method)) {
        case "POST":
            curl_setopt($curl, CURLOPT_POST, 1);
            if ($data) {
                curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
            }
            break;
        case "PUT":
            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "PUT");
            if ($data) {
                curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
            }
            break;
        default: // Jika GET
            if ($data) {
                $url = sprintf("%s?%s", $url, http_build_query($data));
            }
    }


    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);


    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);


    $result = curl_exec($curl);


    if ($result === false) {
        $error_msg = curl_error($curl);
        curl_close($curl);
        return [
            'status_code' => 500,
            'response' => ['message' => 'cURL Error: ' . $error_msg]
        ];
    }

    $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);

    return [
        'status_code' => $httpCode,
        'response' => json_decode($result, true)
    ];
}
?>