<?php 

require_once '../vendor/autoload.php';

use Google\Client;
use Google\Service\Drive;
use Google\Service\Sheets\ValueRange;

session_start();

if(isset($_POST['r'])){

    if($_POST['r'] == 'fetchAllData'){

        $client = new Google_Client();
        //$client->setAuthConfig('../secrets/client_secret.json'); // Path to your client secret JSON file
        //$client->addScope(Google_Service_Sheets::SPREADSHEETS_READONLY); // Scope for read-only access to spreadsheets
        //$client->addScope('https://www.googleapis.com/auth/drive');
        //$client->addScope('https://www.googleapis.com/auth/drive.readonly');
        //$client->addScope('https://www.googleapis.com/auth/spreadsheets');

        // Redirect URI for the OAuth 2.0 flow (should match the one set in the Google Developer Console)
        //$client->setRedirectUri('http://' . $_SERVER['HTTP_HOST']);
        $client->setRedirectUri('https://ebac-service.com');

        // Check if access token is available in the session
        if (isset($_SESSION['accessToken']) && $_SESSION['accessToken']) {
            
            //print_r($_SESSION['accessToken']);
            // Set the access token on the client
            $client->setAccessToken($_SESSION['accessToken']);

            // Create Google Sheets service
            $service = new Google_Service_Sheets($client);

            // Make request to Google Sheets API
            $spreadsheetId = '1ZXGgG5PZyhFZoTzwB5teMVJ1mFf82YxPbnHggC6iVyA';
            $range = 'Sheet1';
            
            
            // $response = $service->spreadsheets_values->get($spreadsheetId, $range);
            // $values = $response->getValues();

            // if (empty($values)) {
            //     print "No data found.\n";
            // } else {
            //     print "Cell values:\n";
            //     foreach ($values as $row) {
            //         print_r($row);
            //     }
            // }

            try {
                $values = [['a1', 'a2', 'a3']]; //add the values to be appended
                //execute the request
                $body = new Google_Service_Sheets_ValueRange([
                    'values' => $values
                ]);
                $params = [
                    'valueInputOption' => 'RAW'
                ];
                $result = $service->spreadsheets_values->append($spreadsheetId, $range, $body, $params);
                printf("%d cells appended.", $result->getUpdates()->getUpdatedCells());
                return $result;
            } catch (Exception $e) {
                // TODO(developer) - handle error appropriately
                echo 'Message: ' . $e->getMessage();
            }

            


        } else {
            //If the access token is not available, redirect the user to the OAuth 2.0 consent screen
            $authUrl = $client->createAuthUrl();
            header('Location: ' . filter_var($authUrl, FILTER_SANITIZE_URL));
        }

    }
}

?>