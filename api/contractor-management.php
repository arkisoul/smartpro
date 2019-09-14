<?php
header('Content-type: application/json');
header('Access-Control-Allow-Origin: *');
require_once '../config/database.php';
require_once '../config/functions.php';
$db       = new Database();
$fn       = new GenFunctions();
$response = array();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    # Successfully received a POST request
    if (isset($_REQUEST['SaveInviteContractor'])) {
######################################### 1 SaveInviteContractor web  services  ###############################
        $jsonStr = stripslashes(trim($_REQUEST['SaveInviteContractor']));

        # Decode JSON => Assoc Array
        $assocArray = json_decode($jsonStr, true);

        # Extract Assoc Array => Values
        $requestto = strtolower($assocArray['RequestTo']);

        # Common Keys for Contractor and Consultant
        $userID      = $assocArray['UserID'];
        $projectid   = $assocArray['ProjectId'];
        $desc        = $assocArray['Desc'];
        $companyid   = $assocArray['CompanyId'];
        $submitdate  = $db->SqlDateFormat('now');
        $contractors = $assocArray['Contractor'];
        $attachments = $assocArray['Attachment'];

        switch ($requestto) {
            case 'consultant':
                $IsConsultantApproved = 0;
                $table                = 'ContractorRequest';
                $fields               = array("CustomerId", "ProjectId", "Descriptions", "RequestTo", "SubmittedDate", "CompanyId", "IsConsultantApproved");
                $values               = array("$userID", "$projectid", "$desc", "$requestto", "$submitdate", "$companyid", "$IsConsultantApproved");

                $insert_id = $db->Insert($table, $fields, $values);

                if ($insert_id) {
                    $request_row = $db->GetRow("SELECT TOP 1 Id FROM ContractorRequest WHERE CustomerId = '$userID' AND ProjectId = '$projectid' ORDER BY Id DESC");

                    $lastinsertid = $request_row['Id'];

                    # Insert all the attachments in ContractorRequestAttachment Table
                    foreach ($attachments as $attachment) {
                        $attachmentid = $attachment['AttachmentId'];

                        $attachment_sql = $db->SimpleInsert("INSERT INTO ContractorRequestAttachment ( RequestId , AttachmentId ) VALUES ('$lastinsertid','$attachmentid')");
                    }

                    # Insert all contractors details in ContractorRequestList Table
                    $suggested_by = 'Consultant';
                    foreach ($contractors as $contractor) {
                        $contractorid = $contractor['ContractorId'];
                        $distance     = trim($contractor['distance']);

                        $sql = $db->SimpleInsert("INSERT INTO ContractorRequestList ( RequestId , ContractorId , Distance, SuggestedBy) VALUES ('$lastinsertid','$contractorid' , '$distance', '$suggested_by')");
                    }

                    # Notification table entry -> CreateFor -> CompanyId
                    # Create a notification record
                    $table_notif        = "Notifications";
                    $table_notif_fields = array('Title', 'Message', 'CreatedOn', 'CreatedFor', 'IsViewed');
                    $table_notif_values = array('Contractor Invite', 'Contractor invite by consultant', '$submitdate', '$companyid', '0');
                    $insert_notif       = $db->Insert($table_notif, $table_notif_fields, $table_notif_values);

                    /*$ContractorRequestList = $db->GetResults("SELECT * FROM ContractorRequestList");
                    $ContractorRequestAttachment = $db->GetResults("SELECT * FROM ContractorRequestAttachment");*/

                    $response = array(
                        'ResponseCode' => '200',
                        'ResponseMsg'  => 'Success, records created successfully.',
                        /*'LastInsertID' => $lastinsertid,
                    'ContractorRequestList' => $ContractorRequestList,
                    'ContractorRequestAttachment' => $ContractorRequestAttachment*/
                    );
                } else {
                    $response = array(
                        'ResponseCode' => '201',
                        'ResponseMsg'  => 'Failure an error occurred while creating records.',
                    );
                }
                break;

            case 'contractor':
                # Contractor specific Keys
                // $tendertime = $assocArray['TenderTime'];
                $tenderdate = $assocArray['TenderDateTime'];
                // $tenderdatetime = $tenderdate . $tendertime;
                $tenderdatetime       = $db->SqlDateFormat($tenderdate);
                $IsConsultantApproved = 1;

                $table  = 'ContractorRequest';
                $fields = array("CustomerId", "ProjectId", "Descriptions", "RequestTo", "SubmittedDate", "CompanyId", "TenderOpeningDateTime", "IsConsultantApproved");
                $values = array("$userID", "$projectid", "$desc", "$requestto", "$submitdate", "$companyid", "$tenderdate", "$IsConsultantApproved");

                $insert_id = $db->Insert($table, $fields, $values);

                if ($insert_id) {
                    $request_row = $db->GetRow("SELECT TOP 1 Id FROM ContractorRequest WHERE CustomerId = '$userID' AND ProjectId = '$projectid' ORDER BY Id DESC");

                    $lastinsertid = $request_row['Id'];

                    # Insert all the attachments in ContractorRequestAttachment Table
                    foreach ($attachments as $attachment) {
                        $attachmentid = $attachment['AttachmentId'];

                        $attachment_sql = $db->SimpleInsert("INSERT INTO ContractorRequestAttachment ( RequestId , AttachmentId ) VALUES ('$lastinsertid','$attachmentid')");
                    }

                    # Create a notification record
                    $table_notif        = "Notifications";
                    $table_notif_fields = array('Title', 'Message', 'CreatedOn', 'CreatedFor', 'IsViewed');

                    # Insert all contractors details in ContractorRequestList Table
                    $suggested_by = 'Customer';
                    foreach ($contractors as $contractor) {
                        $contractorid = $contractor['ContractorId'];
                        $distance     = trim($contractor['distance']);

                        $sql = $db->SimpleInsert("INSERT INTO ContractorRequestList ( RequestId , ContractorId , Distance, SuggestedBy) VALUES ('$lastinsertid','$contractorid' , '$distance', '$suggested_by')");

                        # Notification table entry -> CreateFor -> ContractorID
                        $table_notif_values = array('Contractor Invite', 'Contractor invite by contractor', '$submitdate', '$contractorid', '0');
                        $insert_notif       = $db->Insert($table_notif, $table_notif_fields, $table_notif_values);
                    }

                    /*$ContractorRequestList = $db->GetResults("SELECT * FROM ContractorRequestList");
                    $ContractorRequestAttachment = $db->GetResults("SELECT * FROM ContractorRequestAttachment");*/

                    $response = array(
                        'ResponseCode' => '200',
                        'ResponseMsg'  => 'Success, records created successfully.',
                        /*'LastInsertID' => $lastinsertid,
                    'ContractorRequestList' => $ContractorRequestList,
                    'ContractorRequestAttachment' => $ContractorRequestAttachment*/
                    );
                } else {
                    $response = array(
                        'ResponseCode' => '201',
                        'ResponseMsg'  => 'Failure, an error occurred while creating records.',
                    );
                }
                break;
        }
        echo json_encode($response);
    }
######################################### 2 GetTenderRequest web  services  ###############################
    if (isset($_REQUEST['GetTenderRequest'])) {

        $jsonStr = stripslashes(trim($_REQUEST['GetTenderRequest']));

        # Decode JSON => Assoc Array
        $assocArray = json_decode($jsonStr, true);

        # Extract Assoc Array => Values

        $projectid = $assocArray['ProjectId'];
        $userid    = $assocArray['UserID'];

        $result = $db->GetResults("SELECT * FROM ContractorRequest WHERE ProjectId = '$projectid' AND CustomerId = '$userid' ORDER BY SubmittedDate DESC");

        $tenders = array();

        if ($result) {
            foreach ($result as $request) {
                $tender                 = array();
                $requestStatus          = $request['ApprovedTo'];
                $requestId              = $request['Id'];
                $tender['RequestId']    = $requestId;
                $tender['RequestTo']    = $request['RequestTo'];
                $tender['Descriptions'] = $request['Descriptions'];
                $tender['ApprovedTo']   = $requestStatus;

                $submit_date = json_decode(json_encode($request['SubmittedDate']), true);
                $tender_date = json_decode(json_encode($request['TenderOpeningDateTime']), true);

                $tender['SubmittedDate']         = $submit_date['date'];
                $tender['TenderOpeningDateTime'] = $tender_date['date'];

                if ($requestStatus == "") {
                    $tender['ApprovedTo'] = "Waiting";
                }

                $fetch_contreqlist = $db->GetResults("SELECT Value FROM ContractorRequestList WHERE RequestId = '$requestId'");

                $tender['TotalRequest']  = 0;
                $tender['TotalResponse'] = 0;

                if ($fetch_contreqlist) {
                    foreach ($fetch_contreqlist as $key => $value) {
                        if ($value['Value'] != '') {
                            $tender['TotalResponse'] += 1;
                        }
                        $tender['TotalRequest'] += 1;
                    }
                }
                unset($key, $value);

                $tender['TotalRemaining'] = $tender['TotalRequest'] - $tender['TotalResponse'];
                array_push($tenders, $tender);
            }
            $response = array(
                'ResponseCode' => '200',
                'ResponseMsg'  => 'Success',
                'Request'      => $tenders,
            );
        } else {
            $response = array(
                'ResponseCode' => '201',
                'ResponseMsg'  => 'No records found',
                'Request'      => $tenders,
            );
        }

        echo json_encode($response);
    }
######################################### 3  GetViewTender web  services  ###############################
    if (isset($_REQUEST['GetViewTender'])) {
        $jsonStr = stripslashes(trim($_REQUEST['GetViewTender']));

        # Decode JSON => Assoc Array
        $assocArray = json_decode($jsonStr, true);

        # Extract Assoc Array => Values
        $requestid = $assocArray['RequestId'];

        # RequestID -> ContractorRequest -> Contractor or Consultant
        # Consultant -> Min & Max -> ContractorRequestList -> With Value not equal to null -> Compare with Min or Max -> Set Type -> Also ContractorName, Lat, Lang from ContractorDetails -> ContractorID, ContractorName, Lag, Lang, Value, Type < Min -> 1, > Min 2
        # Attachment Array From ContractorAttachmentList take ID and get name of Document from DocumentAttach
        # Contractor -> Min & Max -> ContractorRequestList -> With Value not equal to null -> Compare with Min or Max -> Set Type -> Also ContractorName, Lat, Lang from ContractorDetails -> ContractorID, ContractorName, Lag, Lang, Value, Type = 3

        $checkReqto = "SELECT * FROM ContractorRequest WHERE Id = '$requestid' ";

        if ($db->NumberRow($checkReqto) > 0) {
            $fetchReqto = $db->GetRow($checkReqto);
            $reqto      = strtolower($fetchReqto['RequestTo']);
            $minprice   = $fetchReqto['Minprice'];
            $maxprice   = $fetchReqto['Maxprice'];

            switch ($reqto) {
                case 'consultant':
                    $contractor_info = $db->GetResults("SELECT CRL.ContractorId, CRL.Value, CRL.Distance, CD.ContractorName, CD.Latitude, CD.longitude FROM ContractorRequestList CRL INNER JOIN ContractorsDetails CD ON CRL.ContractorId = CD.ContractorID WHERE CRL.RequestId = '$requestid'");

                    print_r($db->LastError());

                    $contractors = array();
                    $attachments = array();

                    if ($contractor_info) {
                        foreach ($contractor_info as $key => $value) {
                            $price = $value['Value'];

                            if ($price != '') {
                                $contractor = array(
                                    'ContractorId'   => $value['ContractorId'],
                                    'ContractorName' => $value['ContractorName'],
                                    'Value'          => $value['Value'],
                                    'Distance'       => $value['Distance'],
                                    'Latitude'       => $value['Latitude'],
                                    'longitude'      => $value['longitude'],
                                );

                                if ($price <= $minprice) {
                                    $contractor['ContractorType'] = 1;
                                } else {
                                    $contractor['ContractorType'] = 2;
                                }
                                array_push($contractors, $contractor);
                            }
                        }

                        $attachments = $db->GetResults("SELECT CRA.AttachmentId, DA.Subject AS DocumentName FROM ContractorRequestAttachment CRA INNER JOIN DocumentsAttach DA ON DA.DocumentAttachID = CRA.AttachmentId WHERE RequestId = '$requestid' ");

                        $response = array(
                            'ResponseCode' => '200',
                            'ResponseMsg'  => 'Success',
                            'Details'      => $contractors,
                            'Attachments'  => $attachments,
                            'CurrentTime'  => $db->SqlDateFormat("now"),
                        );
                    } else {
                        $response = array(
                            'ResponseCode' => '201',
                            'ResponseMsg'  => 'No records found',
                            'Details'      => $contractors,
                            'Attachments'  => $attachments,
                        );
                    }
                    break;

                case 'contractor':
                    $contractor_info = $db->GetResults("SELECT CRL.ContractorId, CRL.Value, CRL.Distance, CD.ContractorName, CD.Latitude, CD.longitude FROM ContractorRequestList CRL
                        INNER JOIN ContractorsDetails CD ON CRL.ContractorId = CD.ContractorID
                        WHERE CRL.RequestId = '$requestid'");

                    $contractors = array();
                    $attachments = array();

                    if ($contractor_info) {
                        foreach ($contractor_info as $key => $value) {
                            $price = $value['Value'];

                            if ($price != '') {
                                $contractor = array(
                                    'ContractorId'   => $value['ContractorId'],
                                    'ContractorName' => $value['ContractorName'],
                                    'Value'          => $value['Value'],
                                    'Distance'       => $value['Distance'],
                                    'Latitude'       => $value['Latitude'],
                                    'longitude'      => $value['longitude'],
                                    'ContractorType' => 3,
                                );
                                array_push($contractors, $contractor);
                            }
                        }

                        $attachments = $db->GetResults("SELECT CRA.AttachmentId, DA.Subject AS DocumentName FROM ContractorRequestAttachment CRA INNER JOIN DocumentsAttach DA ON DA.DocumentAttachID = CRA.AttachmentId WHERE RequestId = '$requestid' ");

                        $response = array(
                            'ResponseCode' => '200',
                            'ResponseMsg'  => 'Success',
                            'Details'      => $contractors,
                            'Attachments'  => $attachments,
                            'CurrentTime'  => $db->SqlDateFormat("now"),
                        );
                    } else {
                        $response = array(
                            'ResponseCode' => '201',
                            'ResponseMsg'  => 'No records found',
                            'Details'      => $contractors,
                            'Attachments'  => $attachments,
                        );
                    }
                    break;
            }
        } else {
            $response = array(
                'ResponseCode' => '201',
                'ResponseMsg'  => 'Incorrect request Id.',
            );
        }
        echo json_encode($response);
    }

###################### Get Contractor Details ###############################
    if (isset($_REQUEST['GetContractor'])) {

        $jsonStr = stripslashes(trim($_REQUEST['GetContractor']));

        # Decode JSON => Assoc Array
        $assocArray = json_decode($jsonStr, true);

        # Extract Assoc Array => Values
        $CityId = $assocArray['CityId'];

        // $result = $db->GetResults("SELECT * FROM ContractorsDetails");
        $result = $db->GetResults("SELECT ContractorName, ContractorID, Latitude, longitude, ContractorAddress + ', ' + ContractorAddress1 AS Address, ContractorEmail AS Email, ContractorMobileNo FROM ContractorsDetails WHERE StateId = '$CityId'");

        if ($result) {
            $response = array(
                'ResponseCode'      => '200',
                'ResponseMsg'       => 'Success',
                'ContractorDetails' => $result,
            );
        } else {
            $response = array(
                'ResponseCode' => '201',
                'ResponseMsg'  => 'No contractor details founds for this city id ',
            );
        }
        echo json_encode($response);
    }

###################### Approve Contractor Request ###############################
    if (isset($_REQUEST['ApproveContractorRequest'])) {
        $jsonStr = stripslashes(trim($_REQUEST['ApproveContractorRequest']));

        # Decode JSON => Assoc Array
        $assocArray = json_decode($jsonStr, true);

        # Extract Assoc Array => Values
        $requestId    = $assocArray['RequestId'];
        $contractorId = $assocArray['ContractorId'];
        $approvedDate = $db->SqlDateFormat('now');

        $update_cont_req = $db->QueryDML("UPDATE ContractorRequest SET ApprovedTo = '$contractorId', ApprovedDate = '$approvedDate' WHERE Id = '$requestId'");
        # ApprovedDate
        # RequestTo = Consultant -> CompanyAllProjects -> ContractorID (Update contractor id)
        # ContractorMapping (Table) -> CompanyId and ContractorId combination entry if yes than nothing if no then add a record
        # Notification Table -> CreatedFor -> CompanyID
        # RequestTo = Contractor -> CompanyAllProjects -> ContractorID (Update contractor id)
        # Notification Table -> CreateFor -> ContractorID (For all contractor IDs from ContractorRequestList)

        if ($update_cont_req['Success']) {
            $contractor_info = $db->GetRow("SELECT RequestTo, CompanyId, ProjectId FROM ContractorRequest WHERE Id = '$requestId'");
            $companyId       = $contractor_info['CompanyId'];
            $projectId       = $contractor_info['ProjectId'];
            $requestTo       = strtolower($contractor_info['RequestTo']);
            switch ($requestTo) {
                case 'consultant':
                    $update_com_all_proj = $db->QueryDML("UPDATE CompanyAllProjects SET ContractorID = '$contractorId' WHERE ProjectID = '$projectId'");

                    $is_contractor_mapped = $db->NumberRow("SELECT * FROM ContractorMapping WHERE ContractorId = '$contractorId' AND CompanyId = '$companyId'");

                    # Map Contractor and Company In Contractor Mapping Table
                    if ($is_contractor_mapped <= 0) {
                        $map_contractor = $db->SimpleInsert("INSERT INTO ContractorMapping(ContractorId, CompanyId) VALUES('$contractorId', '$companyId')");
                    }

                    # Create a notification record
                    $table_notif        = "Notifications";
                    $table_notif_fields = array('Title', 'Message', 'CreatedOn', 'CreatedFor', 'IsViewed');
                    $table_notif_values = array('Contractor approved', 'Contractor approved by consultant', '$approvedDate', '$companyId', '0');
                    $insert_notif       = $db->Insert($table_notif, $table_notif_fields, $table_notif_values);
                    break;

                case 'contractor':
                    $update_com_all_proj = $db->QueryDML("UPDATE CompanyAllProjects SET ContractorID = '$contractorId' WHERE ProjectID = '$projectId'");

                    # Create a notification record for each contractor Id in ContractorRequestList
                    $contractor_requests = $db->GetResults("SELECT ContractorId FROM ContractorRequestList WHERE RequestId = '$requestId'");

                    if ($contractor_requests) {
                        $table_notif        = "Notifications";
                        $table_notif_fields = array('Title', 'Message', 'CreatedOn', 'CreatedFor', 'IsViewed');
                        foreach ($$contractor_requests as $contractor_request) {
                            $contractor_id      = $contractor_request['ContractorId'];
                            $table_notif_values = array('Contractor approved', 'Contractor approved by contractor', '$approvedDate', '$contractor_id', '0');
                            $insert_notif       = $db->Insert($table_notif, $table_notif_fields, $table_notif_values);
                        }
                    }
                    break;
            }
            $response = array(
                'ResponseCode' => '200',
                'ResponseMsg'  => 'Success, contractor approved',
            );
        } else {
            $response = array(
                'ResponseCode' => '201',
                'ResponseMsg'  => 'Error, failed to approve contractor request',
            );
        }
        echo json_encode($response);
    }

###################### Test ###############################
    if (isset($_REQUEST['testContractor'])) {
        // $result = $db->GetResults("SELECT * from ProjectMonthlyReport");
        $result  = $db->GetResults("SELECT * from INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME='ProjectTypes'");
        $result1 = $db->GetResults("SELECT * from ProjectTypes");
        // $result1 = $db->GetResults("SELECT * from INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME='ProjectType'");
        echo "Result " . json_encode($result);
        echo "Result " . json_encode($result1);
        // echo "Result1 " . json_encode($result1);
    }

}
