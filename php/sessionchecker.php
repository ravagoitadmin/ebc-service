<?php

session_start();
if(isset($_SESSION['userName'])&&isset($_SESSION['appName']) && isset($_SESSION['accessToken'])){
    
    $appName = $_SESSION['appName']; 
    $userName = $_SESSION['userName'];
    $token = $_SESSION['accessToken'];
    
    if($appName && $userName && $token)
    {
        header("Location: /pages/dashboard.php");
    }
                
}

?>