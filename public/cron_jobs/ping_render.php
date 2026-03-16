<?php

// Your Render app URL
$url = 'https://pdf3converter.onrender.com/api/v1';

// Initialize cURL
$ch = curl_init($url);

curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

// Execute request
$response = curl_exec($ch);

// Check for errors
if (curl_errno($ch)) {
  echo 'Ping Failed: ' . curl_error($ch);
} else {
  echo 'Ping Successful at ' . date('Y-m-d H:i:s');
}

curl_close($ch);
