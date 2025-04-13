<?php
include 'db.php';

function verify_otp($request) {
    // Validate required fields
    if (
        !isset($request['user_id']) ||
        !isset($request['otp']) ||
        !isset($request['access_token'])
    ) {
        return json_encode([
            "status" => 0,
            "message" => "Missing required fields: user_id, otp, and access_token."
        ]);
    }

    global $conn;

    $user_id = $request['user_id'];
    $otp = $request['otp'];
    $access_token = $request['access_token'];
    $login_device_type = isset($request['login_device_type']) ? (int)$request['login_device_type'] : 0;


    $valid_otp = "123456"; // Hardcoded OTP for demo

    // Check if OTP is correct
    if ($otp !== $valid_otp) {
        return json_encode([
            "status" => 0,
            "message" => "Invalid OTP."
        ]);
    }

    // Check if user exists
    $query = $conn->prepare("SELECT * FROM `user-details` WHERE user_id = ?");
    $query->bind_param("i", $user_id);
    $query->execute();
    $result = $query->get_result();

    if ($result->num_rows > 0) {
        // Update verification status and access token
        $update = $conn->prepare("UPDATE `user-details` SET is_verified = 1, access_token = ?, login_device_type = ? WHERE user_id = ?");
        $update->bind_param("sii", $access_token, $login_device_type, $user_id);
        $update->execute();

        // Fetch updated user data
        $query = $conn->prepare("SELECT * FROM `user-details` WHERE user_id = ?");
        $query->bind_param("i", $user_id);
        $query->execute();
        $user_details = $query->get_result()->fetch_assoc();

        return json_encode([
            "status" => 1,
            "message" => "OTP verified successfully.",
            "user_details" => $user_details
        ]);
    } else {
        return json_encode([
            "status" => 0,
            "message" => "User not found."
        ]);
    }
}

// Handle raw JSON POST input
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');

    $input = json_decode(file_get_contents("php://input"), true);

    if (is_array($input)) {
        echo verify_otp($input);
    } else {
        echo json_encode([
            "status" => 0,
            "message" => "Invalid or empty JSON body."
        ]);
    }
}
?>
