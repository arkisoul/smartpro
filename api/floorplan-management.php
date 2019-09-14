<?php
header('Content-type: application/json');
header('Access-Control-Allow-Origin: *');
require_once '../config/database.php';
require_once '../config/functions.php';
$db       = new Database();
$fn       = new GenFunctions();
$response = array();
defined('BASE_URL') or define('BASE_URL', 'http://' . $_SERVER['HTTP_HOST'] . '/smartproapi/');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    # Get all floor plan types
    if (isset($_REQUEST['getFloorTypes'])) {

        # Preparing Query
        $floor_plans = $db->GetResults("SELECT Id AS FloorTypeId, Name AS FloorType FROM FloorType ORDER BY NAME");

        if ($floor_plans) {

            $response = array(
                'ResponseCode' => '200',
                'ResponseMsg'  => 'Success',
                'FloorPlans'   => $floor_plans,
            );
        } else {
            $response = array(
                'ResponseCode' => '201',
                'ResponseMsg'  => 'No floor plans available.',
            );
        }
    } # End Get all floor plan types

    # Get Floor Plan Details
    elseif (isset($_REQUEST['getFloorPlanDetails'])) {
        # Retrieve json string from request
        $jsonStr = stripcslashes(trim($_REQUEST['getFloorPlanDetails']));

        # Convert json string into assoc array
        $assocArray = json_decode($jsonStr, true);

        # Fetch Floor Plan Id from AssocArray
        if (empty($assocArray)) {
            $response = array('ResponseCode' => '201', 'ResponseMsg' => 'Floor type id is must. It is missing in request.');
        } else {

            $floor_plan_id = $assocArray['FloorTypeId'];

            $floor_plan_details = $db->GetResults("SELECT BMI.Name AS ItemName, BMR.Qty AS Quantity, BMI.Price AS UnitPrice, BMR.Percentage, (BMI.Price * BMR.Qty) AS ItemPrice FROM BuildingMaterialRequirement BMR INNER JOIN BuildingMaterialItems BMI ON BMR.BuildingMaterialItemId = BMI.Id WHERE BMR.FloorTypeId = '$floor_plan_id' ORDER BY BMI.Name");

            if ($floor_plan_details) {
                $response = array('ResponseCode' => '200', 'ResponseMsg' => 'Floor plan details', 'FloorPlanDetails' => $floor_plan_details);
            } else {
                $response = array('ResponseCode' => '201', 'ResponseMsg' => 'No records found for this floor type id.');
            }
        }
    } # End Get Floor Plan Details

    # Send Floor Plan Request
    elseif (isset($_REQUEST['sendFloorPlanRequest'])) {
        # Retrieve json string from request
        $jsonStr = stripcslashes(trim($_REQUEST['sendFloorPlanRequest']));

        # Convert json string into assoc array
        $assocArray = json_decode($jsonStr, true);

        # Fetch Floor Plan Id from AssocArray
        if (empty($assocArray)) {
            $response = array('ResponseCode' => '201', 'ResponseMsg' => 'Missing necessary information in request.');
        } else {
            $customer_id    = $assocArray['UserID'];
            $floor_plan_id  = $assocArray['FloorTypeId'];
            $requested_date = $db->SqlDateFormat('now');

            # Fetch Floor Plan Details
            $floor_plan_details = $db->GetResults("SELECT BMI.Name AS ItemName, BMR.Qty AS Quantity, BMI.Price AS UnitPrice, BMR.Percentage, (BMI.Price * BMR.Qty) AS ItemPrice FROM BuildingMaterialRequirement BMR LEFT OUTER JOIN BuildingMaterialItems BMI ON BMR.BuildingMaterialItemId = BMI.Id WHERE BMR.FloorTypeId = '$floor_plan_id'");

            # Fetch Floor Plan Name
            $floor_plan      = $db->GetRow("SELECT Name FROM FloorType WHERE Id = '$floor_plan_id'");
            $floor_plan_name = $floor_plan['Name'];

            # Fetch Customer Details
            $customer_info  = $db->GetRow("SELECT CustomerPhone, CustomerName, CustomerEmail FROM CustomerInformation WHERE CustomerID = '$customer_id'");
            $customer_name  = $customer_info['CustomerName'];
            $customer_phone = $customer_info['CustomerPhone'];
            $customer_email = $customer_info['CustomerEmail'];

            # Prepare HTML Body
            $msg           = "You have received a floor plan request for " . $floor_plan_name . " from " . $customer_name . " at " . $requested_date;
            $table_content = '<tr>';
            $count         = 1;
            foreach ($floor_plan_details as $floor_plan_detail) {
                $table_content .= '<td style="padding: 8px; vertical-align: middle; border: 1px solid #dddddd;">' . $count . '</td>';
                $table_content .= '<td style="padding: 8px; vertical-align: middle; border: 1px solid #dddddd;">' . $floor_plan_detail['ItemName'] . '</td>';
                $table_content .= '<td style="padding: 8px; vertical-align: middle; border: 1px solid #dddddd;">' . $floor_plan_detail['UnitPrice'] . '</td>';
                $table_content .= '<td style="padding: 8px; vertical-align: middle; border: 1px solid #dddddd;">' . $floor_plan_detail['Quantity'] . '</td>';
                $table_content .= '<td style="padding: 8px; vertical-align: middle; border: 1px solid #dddddd;">' . $floor_plan_detail['ItemPrice'] . '</td>';
                $table_content .= '<td style="padding: 8px; vertical-align: middle; border: 1px solid #dddddd;">' . $floor_plan_detail['Percentage'] . '</td>';
                $count++;
            }

            unset($floor_plan_detail);
            $table_content .= '</tr>';
            $html    = file_get_contents(BASE_URL . '/mail/send_floor_plan_request.html');
            $find    = array("{floor_plan_name}", "{customer_name}", "{requested_date}", "{customer_email}", "{customer_phone}", "{table_content}");
            $replace = array($floor_plan_name, $customer_name, $requested_date, $customer_email, $customer_phone, $table_content);
            $html    = str_replace($find, $replace, $html);
            # End Prepare HTML Body

            # Prepare Email Details and Send Floor Plan Request
            $email_data = array('SendTo' => 'rajamanikkam.S@gmail.com', 'SendName' => 'MySmartPro', 'Subject' => 'Floor Plan Request', 'Message' => $msg, 'HTMLBody' => $html);

            // deepender.it@gmail.com

            # Send Floor Plan Request
            if ($fn->sendFloorPlanRequest($email_data)) {
                $insert_request = $db->Query("INSERT INTO FloorPlanRequest (CustomerId, FloorTypeId, RequestedDate) VALUES ($customer_id, $floor_plan_id, '$requested_date')");

                if ($insert_request) {
                    $insert_request_info = $db->GetRow("SELECT Id FROM FloorPlanRequest WHERE CustomerId = '$customer_id' AND RequestedDate = '$requested_date' ORDER BY Id DESC");

                    $insert_request_id = $insert_request_info['Id'];

                    foreach ($floor_plan_details as $floor_plan_detail) {
                        $item_name       = $floor_plan_detail['ItemName'];
                        $item_unit_price = $floor_plan_detail['UnitPrice'];
                        $item_qty        = $floor_plan_detail['Quantity'];

                        $insert_request_detail = $db->Query("INSERT INTO FloorPlanRequestDetail (FloorPlanRequestId, BuildingMaterialItem, Qty, Price) VALUES ($insert_request_id, '$item_name', $item_qty, $item_unit_price)");
                    }

                    unset($floor_plan_detail);

                    $response = array('ResponseCode' => '200', 'ResponseMsg' => 'Request sent successfully.');
                } else {
                    $response = array('ResponseCode' => '201', 'ResponseMsg' => 'We are unable to submit your request at the moment. Please try again later.');
                }

            } else {
                $response = array('ResponseCode' => '201', 'ResponseMsg' => 'We are unable to send your request at the moment. Please try again later.');
            }

        }
    } # End Send Floor Plan Request

    # Get Floor Plan Request of a Customer
    elseif (isset($_REQUEST['getFloorPlanRequests'])) {
        # Retrieve json string from request
        $jsonStr = stripcslashes(trim($_REQUEST['getFloorPlanRequests']));

        # Convert json string into assoc array
        $assocArray = json_decode($jsonStr, true);

        # Fetch Floor Plan Id from AssocArray
        if (empty($assocArray)) {
            $response = array('ResponseCode' => '201', 'ResponseMsg' => 'Missing necessary information in request.');
        } else {
            $customer_id = $assocArray['UserID'];

            # Fetch floor plan requests
            $requests = $db->GetResults("SELECT FPR.Id AS RequestId, FT.Name AS FloorType, FPR.FloorTypeId, FPR.RequestedDate FROM FloorPlanRequest FPR LEFT OUTER JOIN FloorType FT ON FT.Id = FPR.FloorTypeId WHERE CustomerId = '$customer_id'");

            if ($requests) {
                $response = array('ResponseCode' => '200', 'ResponseMsg' => 'Floor plan requests', 'Requests' => $requests);
            } else {
                $response = array('ResponseCode' => '201', 'ResponseMsg' => 'You haven\'t made any floor plan request', 'Requests' => $requests);
            }

        }
    } # End Get Floor Plan Request of a Customer

    # Get Floor Plan Request Details
    elseif (isset($_REQUEST['getFloorPlanRequestDetail'])) {
        # Retrieve json string from request
        $jsonStr = stripcslashes(trim($_REQUEST['getFloorPlanRequestDetail']));

        # Convert json string into assoc array
        $assocArray = json_decode($jsonStr, true);

        # Fetch Floor Plan Id from AssocArray
        if (empty($assocArray)) {
            $response = array('ResponseCode' => '201', 'ResponseMsg' => 'Missing necessary information in request.');
        } else {
            $request_id = $assocArray['RequestId'];

            $request_detail = $db->GetResults("SELECT BuildingMaterialItem AS ItemName, Price AS UnitPrice, Qty AS Quantity, (Price * Qty) AS ItemPrice FROM FloorPlanRequestDetail WHERE FloorPlanRequestId = '$request_id'");

            if ($request_detail) {
                $response = array('ResponseCode' => '200', 'ResponseMsg' => 'Request details', 'RequestDetail' => $request_detail);
            } else {
                $response = array('ResponseCode' => '201', 'ResponseMsg' => 'No record found');
            }

        }
    } # End Get Floor Plan Request Details

    else {
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
