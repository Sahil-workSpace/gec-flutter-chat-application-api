<?php

$host = "sql208.infinityfree.com";
$user = "if0_38742192";
$pass = "j1IKgDw7yq";
$db = "if0_38742192_gec_flutter_chat_application";

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
