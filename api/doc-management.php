<?php
header('Content-type: application/json');
header('Access-Control-Allow-Origin: *');
require_once '../config/database.php';
require_once '../config/functions.php';
$db       = new Database();
$fn       = new GenFunctions();
$response = array();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    # Documents That Need Approval
    if (isset($_REQUEST['getdocs'])) {
        $jsonStr = stripslashes(trim($_REQUEST['getdocs']));

        # Decode JSON => Assoc Array
        $assocArray = json_decode($jsonStr, true);

        # Extract Assoc Array => Values
        $projectID = $assocArray['ProjectID'];
        $reqType   = 'all'; # strtolower($assocArray['ReqType']); # all/unapproved
        $result    = $db->GetResults("SELECT * FROM DocumentsAttach WHERE ProjectID = '$projectID' ORDER BY AttachDate DESC");
        $docs      = array();

        if ($result) {
            foreach ($result as $row) {
                $feedback          = empty($row['Feedback']) ? 'No Feedback' : $row['Feedback'];
                $attachdate        = json_decode(json_encode($row['AttachDate']), true);
                $document_attachID = $row['DocumentAttachID'];
                $urls              = array();

                $url_result = $db->GetResults("SELECT Url FROM AttachmentURL WHERE DocumentAttachID = '$document_attachID'");
                foreach ($url_result as $url_row) {
                    array_push($urls, $url_row['Url']);
                }

                $doc = array(
                    'DocumentAttachID' => $row['DocumentAttachID'],
                    'Subject'          => $row['Subject'],
                    'AttachTypeId'     => $row['AttachTypeId'],
                    'AttachDate'       => $attachdate['date'],
                    'Url'              => $urls,
                    'DocStatus'        => $row['DocStatus'],
                    'Feedback'         => $feedback,
                );

                array_push($docs, $doc);
            }

            $response = array(
                'ResponseCode' => '200',
                'ResponseMsg'  => 'Success',
                'ProjectID'    => $projectID,
                'DocDetails'   => $docs,
            );
        } else {
            $response = array(
                'ResponseCode' => '200',
                'ResponseMsg'  => 'No docs for this project',
                'ProjectID'    => $projectID,
                'DocDetails'   => $docs,
            );
        }
    }

    # Get Map Details
    elseif (isset($_REQUEST['update'])) {
        $jsonStr = stripslashes(trim($_REQUEST['update']));

        # Decode JSON => Assoc Array
        $assocArray = json_decode($jsonStr, true);

        # Extract Assoc Array => Values
        $docID    = $assocArray['DocumentAttachID'];
        $status   = $assocArray['Status'];
        $feedback = $assocArray['Feedback'];

        $sql = "SELECT * FROM DocumentsAttach WHERE DocumentAttachID = '$docID'";

        if ($db->NumberRow($sql)) {
            # Update Doc Status
            if (strtolower($feedback) != 'null') {
                $update = "UPDATE DocumentsAttach SET DocStatus = '$status', Feedback = '$feedback' WHERE DocumentAttachID = '$docID'";
            } else {
                $update = "UPDATE DocumentsAttach SET DocStatus = '$status', Feedback = NULL WHERE DocumentAttachID = '$docID'";
            }

            $result      = $db->QueryDML($update);
            $res_success = $result['Success'];

            if ($res_success) {
                $response = array(
                    'ResponseCode' => '200',
                    'ResponseMsg'  => 'Doc Status updated successfully.',
                );
            } else {
                $response = array(
                    'ResponseCode' => '201',
                    'ResponseMsg'  => 'Something went wrong while updating doc status. Try again later.',
                );
            }

        } else {
            $response = array(
                'ResponseCode' => '200',
                'ResponseMsg'  => 'Can not locate the Doc. Try again later.',
            );
        }
    } else {
        $response = array(
            'ResponseCode' => '201',
            'ResponseMsg'  => 'Unidentified json string. Please send a valid json string.',
        );
    }
} else {
    # code...
    $response = array(
        'ResponseCode' => '201',
        'ResponseMsg'  => 'Incorrect request method. Please send data via POST.',
    );
}

echo json_encode($response);
