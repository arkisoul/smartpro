<?php
header('Content-type: application/json');
header('Access-Control-Allow-Origin: *');
require_once '../config/database.php';
require_once '../config/functions.php';
$db       = new Database();
$fn       = new GenFunctions();
$response = array();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    if (isset($_REQUEST['getreports'])) {
        # Get Reports of A Project
        $jsonStr = stripslashes(trim($_REQUEST['getreports']));

        # Decode JSON => Assoc Array
        $assocArray = json_decode($jsonStr, true);

        # Extract Assoc Array => Values
        $projectID = $assocArray['ProjectID'];
        $reqType   = 'all'; #strtolower($assocArray['ReqType']); # all/recent
        $result    = $db->GetResults("SELECT * FROM ProjectMonthlyReport WHERE ProjectID = '$projectID' ORDER BY ReportDate DESC");
        $reports   = array();

        if ($result) {
            foreach ($result as $row) {
                if ($row['Attachment1']) {
                    $images = "Yes";
                } else {
                    $images = null;
                }

                if ($row['Report']) {
                    $reportDetail = $row['Report'];
                } else {
                    $reportDetail = null;
                }

                $reportID = $row['ProjectMonthlyReportId'];

                # Get Comments Specific to a Report of a Project
                /*$commentResult = $db->GetResults("SELECT CommentLineID, Comment, CommentDate FROM CustomerComments WHERE ProjectID = '$projectID' AND 'ReportID' = '$reportID'");

                if ( $commentResult ) {
                foreach ($commentResult as $commentRow) {
                $commentdate = json_decode(json_encode($commentRow['CommentDate']), true);
                $comment[] = array(
                'CommentID' => $commentRow['CommentLineID'],
                'Comment' => $commentRow['Comment'],
                'CommentDate' => $commentdate['date'],
                );
                }
                } else {
                $comment = array();
                }*/

                $reportdate = json_decode(json_encode($row['ReportDate']), true);

                $report = array(
                    'ProjectID'    => $projectID,
                    'ReportID'     => $row['ProjectMonthlyReportId'],
                    'ReportDate'   => $reportdate['date'],
                    'Images'       => $images,
                    'ReportDetail' => $reportDetail,
                    'ReportDoc'    => $row['ReportDoc'],
                    'ReportViewed' => $row['ReportViewed'],
                );

                array_push($reports, $report);
            }
            unset($row);

            $update_status = $db->QueryDML("UPDATE ProjectMonthlyReport SET ReportViewed = 1 WHERE ProjectID = '$projectID'");

            if ($update_status['Success']) {
                $response = array(
                    'ResponseCode' => '200',
                    'ResponseMsg'  => 'Success',
                    'Reports'      => $reports,
                );
            } else {
                $response = array(
                    'ResponseCode' => '200',
                    'ResponseMsg'  => 'Error, failed updating report status.',
                    'Reports'      => $reports,
                );
            }
        } else {
            $response = array(
                'ResponseCode' => '200',
                'ResponseMsg'  => 'No reports available for this project.',
            );
        }

        # Preparing Query
        /*switch ( $reqType ) {
    case 'all':
    $sql = "SELECT * FROM ProjectMonthlyReport WHERE ProjectID = '$projectID' ORDER BY ReportDate DESC";
    break;

    case 'recent':
    $currentMonth = date('F');
    $firstDayofCurrentMonth = date('Y-m-d H:i:s', strtotime('first day of '. $currentMonth));
    $lastDayofCurrentMonth = date('Y-m-d H:i:s', strtotime('last day of '. $currentMonth));
    $sql = "SELECT * FROM ProjectMonthlyReport WHERE ProjectID = '$projectID' AND ReportDate >= 'firstDayofCurrentMonth' AND ReportDate <= 'lastDayofCurrentMonth' ORDER BY ReportDate DESC";
    break;

    default:
    # Unidentified JSON Method
    $response = array(
    'ResponseCode' => '201',
    'ResponseMsg' => 'Unidentified request type. Try again later.'
    );
    break;
    }*/
    } # End Get Reports

    # Get Images of a specific report
    elseif (isset($_REQUEST['getimages'])) {
        $jsonStr = stripslashes(trim($_REQUEST['getimages']));

        # Decode JSON => Assoc Array
        $assocArray = json_decode($jsonStr, true);

        # Extract Assoc Array => Values
        $reportID = $assocArray['ReportID'];

        # Fetching Slider Images - Table ProjectMonthlyReport
        $result = $db->GetRow("SELECT * FROM ProjectMonthlyReport WHERE ProjectMonthlyReportId = '$reportID'");
        $images = array();

        if ($result) {
            $regEx = '/attachment\d\d?$/i';
            foreach ($result as $key => $value) {
                if (preg_match_all($regEx, $key) && $value) {
                    array_push($images, $value);
                }
            }
            unset($key, $value);

            $response = array(
                'ResponseCode' => '200',
                'ResponseMsg'  => 'Success',
                'ReportID'     => $reportID,
                'ReportDoc'    => $result['ReportDoc'],
                'Images'       => $images,
            );
        } else {
            $response = array(
                'ResponseCode' => '201',
                'ResponseMsg'  => 'Error, no result found for this report id.',
            );
        }
    } # End Get Images of a specific Report

    else {
        # Unidentified JSON Method
        $response = array(
            'ResponseCode' => '201',
            'ResponseMsg'  => 'Unidentified json string. Please send a valid json string.',
        );
    }

} else {
    # Incorrect Request Method
    $response = array(
        'ResponseCode' => '201',
        'ResponseMsg'  => 'Incorrect request method. Please send data via POST.',
    );
}

echo json_encode($response);
