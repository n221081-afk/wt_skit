<?php
// firestore_helper.php
// Procedural helper to create a `users` document in Firestore via REST API
// Usage: ensure_user_document($uid, $email, $idToken);

require_once __DIR__ . '/firebase_config.php';

/**
 * Ensure a user document exists in Firestore under collection `users` with document ID = $uid.
 * Uses the Firebase ID token for authentication (Authorization: Bearer <idToken>).
 *
 * @param string $uid Firebase user UID
 * @param string $email User email
 * @param string $idToken Firebase ID token (from signInWithPassword response)
 * @return array|false Response data array on success or false on failure
 */
function ensure_user_document($uid, $email, $idToken) {
    if (empty($uid) || empty($email) || empty($idToken)) {
        return false;
    }

    // Firestore REST endpoint for creating documents with a specific ID
    $project = FIREBASE_PROJECT_ID;
    $baseUrl = 'https://firestore.googleapis.com/v1/projects/' . urlencode($project) . '/databases/(default)/documents/users';
    $url = $baseUrl . '?documentId=' . urlencode($uid);

    // Prepare Firestore document fields per REST API format
    $now = gmdate('Y-m-d\TH:i:s\Z');
    $body = [
        'fields' => [
            'uid' => ['stringValue' => $uid],
            'email' => ['stringValue' => $email],
            'role' => ['stringValue' => 'user'],
            'created_at' => ['timestampValue' => $now]
        ]
    ];

    $payload = json_encode($body);

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $idToken
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr = curl_error($ch);
    curl_close($ch);

    if ($response === false) {
        return false;
    }

    $data = json_decode($response, true);

    // 200 OK means created; 409 means document already exists (conflict)
    if (in_array($httpCode, [200, 201])) {
        return $data;
    }

    // If the document already exists, Firestore returns 409; we can ignore that as success
    if ($httpCode === 409) {
        return $data;
    }

    return false;
}

/**
 * Retrieve a user document from Firestore.
 *
 * @param string $uid Firebase user UID
 * @param string $idToken Firebase ID token for authorization
 * @return array|false Document data converted to PHP array, or false on failure
 */
function get_user_document($uid, $idToken) {
    if (empty($uid) || empty($idToken)) {
        return false;
    }

    $project = FIREBASE_PROJECT_ID;
    $url = 'https://firestore.googleapis.com/v1/projects/' . urlencode($project) . '/databases/(default)/documents/users/' . urlencode($uid);

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $idToken
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($response === false) {
        return false;
    }

    $data = json_decode($response, true);
    if ($httpCode === 200 && isset($data['fields'])) {
        // convert Firestore field format to simple key=>value
        $result = [];
        foreach ($data['fields'] as $key => $value) {
            // assume simple string or integer values
            if (isset($value['stringValue'])) {
                $result[$key] = $value['stringValue'];
            } elseif (isset($value['integerValue'])) {
                $result[$key] = $value['integerValue'];
            } elseif (isset($value['doubleValue'])) {
                $result[$key] = $value['doubleValue'];
            } elseif (isset($value['timestampValue'])) {
                $result[$key] = $value['timestampValue'];
            }
            // add more types as needed
        }
        return $result;
    }

    return false;
}

/**
 * Retrieve a user document by email using a Firestore structured query.
 *
 * @param string $email User email to search for
 * @param string $idToken Firebase ID token for authorization
 * @return array|false Document data converted to PHP array, or false on failure/not found
 */
function get_user_document_by_email($email, $idToken) {
    if (empty($email) || empty($idToken)) {
        return false;
    }

    $project = FIREBASE_PROJECT_ID;
    $url = 'https://firestore.googleapis.com/v1/projects/' . urlencode($project) . '/databases/(default)/documents:runQuery';

    // build structured query
    $body = [
        'structuredQuery' => [
            'from' => [[ 'collectionId' => 'users' ]],
            'where' => [
                'fieldFilter' => [
                    'field' => [ 'fieldPath' => 'email' ],
                    'op' => 'EQUAL',
                    'value' => [ 'stringValue' => $email ]
                ]
            ],
            'limit' => 1
        ]
    ];

    $payload = json_encode($body);

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $idToken
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr = curl_error($ch);
    curl_close($ch);

    // DEBUG logging
    error_log('Firestore email query - HTTP: ' . $httpCode . ', Response: ' . substr($response, 0, 300));

    if ($response === false || $curlErr) {
        error_log('Firestore email query failed: ' . $curlErr);
        return false;
    }

    $results = json_decode($response, true);
    if ($httpCode === 200 && is_array($results)) {
        foreach ($results as $row) {
            if (isset($row['document']['fields'])) {
                $fields = $row['document']['fields'];
                $result = [];
                foreach ($fields as $key => $value) {
                    if (isset($value['stringValue'])) {
                        $result[$key] = $value['stringValue'];
                    } elseif (isset($value['integerValue'])) {
                        $result[$key] = $value['integerValue'];
                    } elseif (isset($value['doubleValue'])) {
                        $result[$key] = $value['doubleValue'];
                    } elseif (isset($value['timestampValue'])) {
                        $result[$key] = $value['timestampValue'];
                    }
                }
                return $result;
            }
        }
    }
    return false;
}

?>
