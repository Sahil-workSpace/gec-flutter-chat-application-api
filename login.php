<?php
include 'db.php';

function login_user($request) {
    file_put_contents('debug_log.txt', print_r($request, true), FILE_APPEND);

    // Validate required fields
    if (
        !isset($request['phone_number']) ||
        !isset($request['country_code']) ||
        !isset($request['access_token'])
    ) {
        return json_encode([
            "status" => 0,
            "message" => "Missing required fields: phone_number, country_code, and access_token."
        ]);
    }

    global $conn;
    $phone = trim($request['phone_number']);
    $code = trim($request['country_code']);
    $access_token = trim($request['access_token']);
    

    // Check if user exists
    $query = $conn->prepare("SELECT * FROM `user-details` WHERE phone_number = ? AND country_code = ?");
    $query->bind_param("ss", $phone, $code);
    $query->execute();
    $result = $query->get_result();

    if ($result->num_rows > 0) {
        $user_details = $result->fetch_assoc();
    } else {
        // Double check in case of race condition
        $check = $conn->prepare("SELECT * FROM `user-details` WHERE phone_number = ? AND country_code = ?");
        $check->bind_param("ss", $phone, $code);
        $check->execute();
        $existing = $check->get_result();

        if ($existing->num_rows === 0) {
            $default_email = $phone . "@placeholder.com";
            $stmt = $conn->prepare("INSERT INTO `user-details` (user_name, phone_number, country_code, email_address) VALUES (?, ?, ?, ?)");
            $user_name = NULL;
            $stmt->bind_param("ssss", $user_name, $phone, $code, $default_email);
            $stmt->execute();

            $user_id = $stmt->insert_id;
        } else {
            $user_id = $existing->fetch_assoc()['user_id'];
        }

        $query = $conn->prepare("SELECT * FROM `user-details` WHERE user_id = ?");
        $query->bind_param("i", $user_id);
        $query->execute();
        $user_details = $query->get_result()->fetch_assoc();
    }

    // Add access_token to the response only (not updating DB here)
    $user_details['access_token'] = $access_token;
    $user_details['is_verified'] = 0;

    return json_encode([
        "status" => 1,
        "message" => "Login successful",
        "user_details" => $user_details
    ]);
}

// Handle raw JSON body (for Flutter / Postman raw JSON)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');

    $input = json_decode(file_get_contents("php://input"), true);

    if (is_array($input)) {
        echo login_user($input);
    } else {
        echo json_encode([
            "status" => 0,
            "message" => "Invalid or empty JSON body."
        ]);
    }
}
?>
