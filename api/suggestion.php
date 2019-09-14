<?php
header('Content-type: application/json');
header('Access-Control-Allow-Origin: *');
require_once '../config/database.php';
require_once '../config/functions.php';
$db       = new Database();
$fn       = new GenFunctions();
$response = array();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    # Successfully received a POST request #

    if (isset($_REQUEST['add-suggestion'])) {
        $jsonStr = stripslashes(trim($_REQUEST['add-suggestion']));

        # Decode JSON => Assoc Array
        $assocArray = json_decode($jsonStr, true);

        # Extract Assoc Array => Values
        $customerId = $assocArray['CustomerID'];
        $projectId  = array_key_exists('ProjectID', $assocArray) ? $assocArray['ProjectID'] : null;
        $suggestion = $assocArray['Suggestion'];
        $createDate = $db->SqlDateFormat('now');

        # Create a suggestion
        $inser_id = $db->SimpleInsert("INSERT INTO Suggestions (CustomerID, ProjectID, Suggestion, CreateDate) VALUES('$customerId', '$projectId', '$suggestion', '$createDate')");

        if ($inser_id) {
            $response = array('ResponseCode' => '200', 'ResponseMsg' => 'We have received your suggestion. The concern team will get back to you asap.');
        } else {
            $response = array('ResponseCode' => '201', 'ResponseMsg' => 'We are facing some issue registering your suggestion at the moment. Please try again later.');
        }
    }
}

echo json_encode($response);
