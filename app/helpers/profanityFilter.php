<?php
function containsProfanity(string $text): bool {
    // Example using PrugoMalum free API
    $apiKey = 'YOUR_API_KEY'; // replace with your key
    $url = 'https://www.purgomalum.com/service/containsprofanity?text=' . urlencode($text);

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $result = curl_exec($ch);
    curl_close($ch);

    return $result === 'true';
}

/**
 * Check multiple fields at once
 */
function checkFieldsForProfanity(array $fields): bool {
    foreach ($fields as $field) {
        if (containsProfanity((string)$field)) return true;
    }
    return false;
}
