<?php

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    if (isset($_POST['data'])) 
    {
        $data = $_POST['data'];
        $data = json_decode($data);
        //google data
        $gEmail = $data->email;
        $gName = $data->name;
        $gPicture = $data->picture;

        $appName = "EBC-SERVICE";
        $privilegeCode = "NONE";

        session_start();

        $_SESSION['userName'] = $gEmail;
        $_SESSION['appName'] = $appName;
        $_SESSION['priviledgeCode'] = $privilegeCode;
        $_SESSION['name'] = $gName;
        $_SESSION['picture'] = $gPicture;
        $_SESSION['accessToken'] = $data->accessToken;

        
        echo(json_encode(['success' => true]));
        exit;
    }
}
?>
