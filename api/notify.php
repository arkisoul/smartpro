<?php
header('Content-type: application/json');
header('Access-Control-Allow-Origin: *');
require_once '../config/database.php';
require_once '../config/functions.php';
$db       = new Database();
$fn       = new GenFunctions();
$response = array();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    # Get Customer Specific Notifications
    if (isset($_REQUEST['get-notify'])) {
        $jsonStr = stripslashes(trim($_REQUEST['get-notify']));

        # Decode JSON => Assoc Array
        $assocArray = json_decode($jsonStr, true);

        # Extract Assoc Array => Values
        $custId = $assocArray['CustomerID'];

        # Fetch Notifications
        $result = $db->GetResults("SELECT * FROM CustomerNotification c WHERE c.customerId = '$custId' ORDER BY c.Date DESC");
        $notifs = array();

        if ($result) {
            foreach ($result as $row) {
                $date  = json_decode(json_encode($row['Date']), true);
                $notif = array(
                    'ID'                  => $row['ID'],
                    'ProjectId'           => $row['ProjectId'],
                    'Title'               => $row['Title'],
                    'Msg'                 => $row['NotifyMessages'],
                    'Date&Time'           => $date['date'],
                    'Type'                => $row['Type'],
                    'Status'              => $row['IsCustViewd'],
                    'ArabicNofifyMessage' => $row['ArabicNofifyMessage'],
                    'ArabicTitle'         => $row['ArabicTitle'],
                );

                array_push($notifs, $notif);
            }

            $update_result = $db->QueryDML("UPDATE CustomerNotification SET IsCustViewd = 1 WHERE customerId = '$custId'");
            $res_success   = $result['Success'];

            if ($update_result['Success']) {
                $response = array(
                    'ResponseCode' => '200',
                    'ResponseMsg'  => 'Success',
                    'Notifs'       => $notifs,
                );
            } else {
                $response = array(
                    'ResponseCode' => '200',
                    'ResponseMsg'  => 'Error, could not update notification status.',
                    'Notifs'       => $notifs,
                );
            }
        } else {
            $response = array(
                'ResponseCode' => '200',
                'ResponseMsg'  => 'No notification',
            );
        }
    }

    # Delete a notification
    elseif (isset($_REQUEST['delete-notify'])) {
        $jsonStr = stripslashes(trim($_REQUEST['delete-notify']));

        # Decode JSON => Assoc Array
        $assocArray = json_decode($jsonStr, true);

        # Extract Assoc Array => Values
        $custId = $assocArray['CustomerID'];
        $ID     = $assocArray['ID'];

        $notif_exist = $db->NumberRow("SELECT * FROM CustomerNotification c WHERE c.customerId = '$custId' AND c.ID = '$ID'");

        if ($notif_exist > 0) {
            $notif_delete         = $db->QueryDML("DELETE FROM CustomerNotification WHERE customerId = '$custId' AND ID = '$ID'");
            $notif_delete_success = $notif_delete['Success'];

            if ($notif_delete_success) {
                $response = array(
                    'ResponseCode' => '200',
                    'ResponseMsg'  => 'Notification deleted successfully.',
                );
            } else {
                $response = array(
                    'ResponseCode' => '201',
                    'ResponseMsg'  => 'We are unable to process your request now. Please try again later.',
                );
            }
        }
    }

    # Delete a notification
    elseif (isset($_REQUEST['delete-all'])) {
        $jsonStr = stripslashes(trim($_REQUEST['delete-all']));

        # Decode JSON => Assoc Array
        $assocArray = json_decode($jsonStr, true);

        # Extract Assoc Array => Values
        $custId = $assocArray['CustomerID'];

        $notif_exist = $db->NumberRow("SELECT * FROM CustomerNotification c WHERE c.customerId = '$custId'");

        if ($notif_exist > 0) {
            $notif_delete         = $db->QueryDML("DELETE FROM CustomerNotification WHERE customerId = '$custId'");
            $notif_delete_success = $notif_delete['Success'];

            if ($notif_delete_success) {
                $response = array(
                    'ResponseCode' => '200',
                    'ResponseMsg'  => 'Notifications deleted successfully.',
                );
            } else {
                $response = array(
                    'ResponseCode' => '201',
                    'ResponseMsg'  => 'We are unable to process your request now. Please try again later.',
                );
            }
        }
    } else {
        $response = array(
            'ResponseCode' => '201',
            'ResponseMsg'  => 'Unidentified json string. Please send a valid json string.',
        );
    }
} else {
    $response = array(
        'ResponseCode' => '201',
        'ResponseMsg'  => 'Incorrect request method. Please send data via POST.',
    );
}

echo json_encode($response);
