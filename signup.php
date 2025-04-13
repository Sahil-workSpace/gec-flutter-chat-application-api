<?php
include 'db.php';

function signup_user($request) {
    if (
        !isset($request['user_id']) ||
        !isset($request['access_token']) ||
        !isset($request['user_name']) ||
        !isset($request['email_address'])
    ) {
        return json_encode([
            "status" => "error",
            "message" => "Missing required fields: user_id, access_token, user_name, and email_address."
        ]);
    }

    global $conn;
    $user_id = $request['user_id'];
    $access_token = $request['access_token'];
    $user_name = $request['user_name'];
    $email_address = $request['email_address'];
    $login_device_type = isset($request['login_device_type']) ? (int)$request['login_device_type'] : 0;


    // Fetch user by ID
    $query = $conn->prepare("SELECT * FROM `user-details` WHERE user_id = ?");
    $query->bind_param("i", $user_id);
    $query->execute();
    $result = $query->get_result();

    if ($result->num_rows > 0) {
        $user_details = $result->fetch_assoc();

        // Check if access_token matches
        if ($user_details['access_token'] !== $access_token) {
            return json_encode([
                "status" => 4,
                "message" => "Unauthorized access. Invalid access token."
            ]);
        }

        // Update user details
        $update = $conn->prepare("UPDATE `user-details` SET user_name = ?, email_address = ?, is_register = 1, login_device_type = ? WHERE user_id = ?");
        $update->bind_param("ssii", $user_name, $email_address, $login_device_type, $user_id);
        $update->execute();

        // Fetch updated user data
        $query = $conn->prepare("SELECT * FROM `user-details` WHERE user_id = ?");
        $query->bind_param("i", $user_id);
        $query->execute();
        $updated_user = $query->get_result()->fetch_assoc();

        return json_encode([
            "status" => 1,
            "message" => "Sucess",
            "user_details" => $updated_user
        ]);
    } else {
        return json_encode([
            "status" => 0,
            "message" => "User not found for signup."
        ]);
    }
}

// Handle raw JSON body (for Flutter / Postman raw JSON)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');

    $input = json_decode(file_get_contents("php://input"), true);

    if (is_array($input)) {
        echo signup_user($input);
    } else {
        echo json_encode([
            "status" => 0,
            "message" => "Invalid or empty JSON body."
        ]);
    }
}
?>
