<?php
include 'db.php';

function get_user_info($request) {
    if (
        !isset($request['user_id']) ||
        !isset($request['access_token'])
    ) {
        return json_encode([
            "status" => 0,
            "message" => "Missing required fields: user_id and access_token."
        ]);
    }

    global $conn;
    $user_id = $request['user_id'];
    $access_token = $request['access_token'];

    // Check if user exists
    $query = $conn->prepare("SELECT * FROM `user-details` WHERE user_id = ?");
    $query->bind_param("i", $user_id);
    $query->execute();
    $result = $query->get_result();

    if ($result->num_rows === 0) {
        return json_encode([
            "status" => 0,
            "message" => "User not found."
        ]);
    }

    $user = $result->fetch_assoc();

    // Token check
    if ($user['access_token'] !== $access_token) {
        return json_encode([
            "status" => 4,
            "message" => "Unauthorized access. Invalid access token."
        ]);
    }

    return json_encode([
        "status" => 1,
        "message" => "User info fetched successfully.",
        "user" => $user
    ]);
}

// Handle raw JSON body
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    $input = json_decode(file_get_contents("php://input"), true);

    if (is_array($input)) {
        echo get_user_info($input);
    } else {
        echo json_encode([
            "status" => 0,
            "message" => "Invalid or empty JSON body."
        ]);
    }
}
?>
