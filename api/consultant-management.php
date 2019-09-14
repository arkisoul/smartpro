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
    #
    ######################################### 1 view quotes services ####################################################
    if (isset($_REQUEST['view-quotes'])) {
        $jsonStr = stripslashes(trim($_REQUEST['view-quotes']));

        # Decode JSON => Assoc Array
        $assocArray = json_decode($jsonStr, true);

        # Extract Assoc Array => Values
        $userID    = $assocArray['UserID'];
        $projectid = $assocArray['ProjectId'];

        $sql = "SELECT Id , Description , SubmittedDate , ApprovedTo, QuotesIn FROM QuotesRequest WHERE CustomerId = '$userID' AND ProjectId = '$projectid' ORDER BY SubmittedDate DESC ";

        if ($db->NumberRow($sql) > 0) {
            $Quotes_details = array();
            $quote_requests = $db->GetResults($sql);
            // echo "Quote Requests\n";
            // print_r($quote_requests);

            foreach ($quote_requests as $quote_request) {
                $requestid = $quote_request['Id'];

                $sql1 = "SELECT COUNT(Id) AS NumReq  FROM ProjectCompanyRequestList WHERE RequestId = '$requestid' ";

                if ($db->NumberRow($sql1) > 0) {
                    $result1 = $db->GetRow($sql1);
                }

                # Get Service IDs from ProjectServiceRequestList with respect to the RequestID

                $service_ids = $db->GetResults("SELECT Id FROM  ProjectServiceRequestList WHERE RequestId = '$requestid'");

                if ($service_ids) {
                    $total_response = 0;
                    // echo "Service Request List\n";
                    $service_request_id = array();
                    foreach ($service_ids as $service_ids_row) {
                        array_push($service_request_id, $service_ids_row['Id']);
                    }
                    unset($service_ids_row);
                    $values = " ('" . implode("', '", $service_request_id) . "') ";
                    // print_r($values);
                    $res_row = $db->GetRow("SELECT COUNT(DISTINCT CompanyId) AS TotalRes FROM QuotationDetails WHERE ServiceRequestedId IN $values");
                    if ($res_row) {
                        $total_response += $res_row['TotalRes'];
                    }
                }

                $submitted_date = json_decode(json_encode($quote_request['SubmittedDate']), true);

                $Quotes_detail = array(
                    'RequestId'     => $quote_request['Id'],
                    'Description'   => $quote_request['Description'],
                    'QuotesIn'      => $quote_request['QuotesIn'],
                    'Date'          => $submitted_date['date'],
                    'ApprovedTo'    => $quote_request['ApprovedTo'],
                    'TotalRequest'  => $result1['NumReq'],
                    'TotalResponse' => $total_response,
                );
                array_push($Quotes_details, $Quotes_detail);
            }

            $response = array(
                'ResponseCode'     => '200',
                'ResponseMsg'      => 'Success',
                'Quotes_details'   => $Quotes_details,
                'QuotationDetails' => $QuotationDetails,
            );
        } else {
            $response = array(
                'ResponseCode' => '201',
                'ResponseMsg'  => 'No data found for this Userid or Project ID  ',
            );
        }
        echo json_encode($response);
    }
######################################## 2 quotes-details srvices ###############################################
    if (isset($_REQUEST['quotes-details'])) {
        $jsonStr = stripslashes(trim($_REQUEST['quotes-details']));

        # Decode JSON => Assoc Array
        $assocArray = json_decode($jsonStr, true);

        # Extract Assoc Array => Values

        $RequestId = $assocArray['RequestId'];

        # Get Service IDs from ProjectServiceRequestList with respect to the RequestID
        $service_ids = $db->GetResults("SELECT * FROM ProjectServiceRequestList WHERE RequestId = '$RequestId'");

        if ($service_ids) {
            $service_id = array();
            foreach ($service_ids as $service_ids_row) {
                array_push($service_id, $service_ids_row['Id']);
            }

            $values = " ('" . implode("', '", $service_id) . "') ";

            $companies   = array();
            $company_ids = $db->GetResults("SELECT DISTINCT(CompanyId) FROM QuotationDetails WHERE ServiceRequestedId IN $values");

            if ($company_ids) {
                foreach ($company_ids as $company_row) {
                    $companyId = $company_row['CompanyId'];

                    $company_details = $db->GetRow("SELECT CompanyID , CompanyName , Latitude , longitude FROM CompanyDetails WHERE CompanyID = '$companyId'");

                    $company = array(
                        "CompanyID"   => $companyId,
                        "CompanyName" => $company_details['CompanyName'],
                        "Latitude"    => $company_details['Latitude'],
                        "Longitude"   => $company_details['longitude'],
                    );
                    array_push($companies, $company);
                }
            }
            unset($service_ids_row, $company_row);

            $response = array(
                'ResponseCode' => '200',
                'ResponseMsg'  => 'Success',
                "Companies"    => $companies,
            );

        } else {
            $response = array(
                'ResponseCode' => '201',
                'ResponseMsg'  => 'No Records Founds',
            );
        }

        echo json_encode($response);
    }

######################################## 3 GetCompanyQuotes web srvices ###########################################
    if (isset($_REQUEST['GetCompanyQuotes'])) {
        $jsonStr = stripslashes(trim($_REQUEST['GetCompanyQuotes']));

        # Decode JSON => Assoc Array
        $assocArray = json_decode($jsonStr, true);

        # Extract Assoc Array => Values

        $RequestId = $assocArray['RequestId'];
        $CompanyId = $assocArray['CompanyId'];

        $services = $db->GetResults("SELECT * FROM ProjectServiceRequestList WHERE RequestId = '$RequestId'");
        $quotes   = array();

        if ($services) {
            foreach ($services as $service) {
                $id           = $service['Id'];
                $serviceId    = $service['ProjectServiceId'];
                $subServiceId = $service['ProjectSubServiceId'];

                $getValue      = $db->GetRow("SELECT Value FROM QuotationDetails WHERE ServiceRequestedId = '$id' AND CompanyId = '$CompanyId'");
                $getService    = $db->GetRow("SELECT * FROM ProjectServices WHERE Id = '$serviceId'");
                $getSubService = $db->GetRow("SELECT * FROM ProjectSubServices WHERE Id = '$subServiceId'");

                $quote = array(
                    "ServiceName"    => $getService['ServiceName'],
                    "SubServiceName" => $getSubService['SubServicesName'],
                    "Value"          => $getValue['Value'],
                );

                array_push($quotes, $quote);
            }

            $response = array(
                "ResponseCode" => "200",
                "ResponseMsg"  => "Company Quotes Details",
                "Quotes"       => $quotes,
            );
        } else {
            $response = array(
                "ResponseCode" => "201",
                "ResponseMsg"  => "No records found for this request Id and Company Id.",
            );
        }
        echo json_encode($response);
    }
######################################## 6  SaveInviteConsultant  ###############################################

    if (isset($_REQUEST['SaveInviteConsultant'])) {

        $jsonStr = stripslashes(trim($_REQUEST['SaveInviteConsultant']));

        # Decode JSON => Assoc Array
        $assocArray = json_decode($jsonStr, true);

        # Extract Assoc Array => Values
        $Projectid   = $assocArray['ProjectId'];
        $Userid      = $assocArray['UserID'];
        $Description = $assocArray['Description'];
        $QuotesIn    = $assocArray['QuotesIn'];
        $submitdate  = $db->SqlDateFormat("now");

        $insertCon = "INSERT INTO QuotesRequest  (CustomerId,ProjectId,SubmittedDate,Description,QuotesIn) VALUES ('$Userid','$Projectid','$submitdate','$Description','$QuotesIn')";

        $id = $db->SimpleInsert($insertCon);

        if ($id) {
            $selectRequestid = "SELECT TOP 1 Id FROM QuotesRequest WHERE CustomerId = '$Userid' AND ProjectId = '$Projectid' ORDER BY Id DESC";

            if ($db->NumberRow($selectRequestid) > 0) {
                $result = $db->GetRow($selectRequestid);
            }

            $LastInsertRequestId = $result['Id'];

            if ($LastInsertRequestId != '') {
                # Insert Into Table ProjectServiceRequestList
                $services     = $assocArray['Services'];
                $serviceCount = 0;
                foreach ($services as $service) {
                    $Serviceid    = $service['ServiceId'];
                    $SubServiceId = $service['SubServiceId'];

                    $insertService = "INSERT INTO ProjectServiceRequestList (RequestId,ProjectServiceId,ProjectSubServiceId) VALUES ('$LastInsertRequestId','$Serviceid','$SubServiceId') ";

                    $service_id = $db->SimpleInsert($insertService);

                    if ($service_id) {
                        $serviceCount += 1;
                    }
                }

                # Insert Into Table ProjectSelectedAttachment
                $attachmentCount = 0;
                $attachments     = $assocArray['Attachment'];
                foreach ($attachments as $attachment) {
                    $AttachmentId = $attachment['AttachmentId'];

                    $insertAttachment = "INSERT INTO ProjectSelectedAttachment (ReqeustId,DocAttachId) VALUES ('$LastInsertRequestId','$AttachmentId') ";

                    $attachment_id = $db->SimpleInsert($insertAttachment);

                    if ($attachment_id) {
                        $attachmentCount += 1;
                    }
                }

                # Insert Into Table ProjectCompanyRequestList
                $companyCount = 0;
                $companies    = $assocArray['Company'];
                foreach ($companies as $company) {
                    $companyid = $company['companyid'];
                    $distance  = $company['distance'];

                    $insertCompanyId = "INSERT INTO ProjectCompanyRequestList (RequestId,CompanyId) VALUES ('$LastInsertRequestId','$companyid') ";

                    $ins_company_id = $db->SimpleInsert($insertCompanyId);

                    # Create a record each for a Company Id in Notification table
                    $notif_title      = "Invite";
                    $notif_msg        = "Received a my consultant invite, QuotesRequest Id " . $LastInsertRequestId;
                    $notif_createdOn  = $db->SqlDateFormat("now");
                    $notif_createdFor = $companyid;
                    $notif_isViewed   = 0;

                    $notif_insert = $db->SimpleInsert("INSERT INTO Notifications (Title, Message, CreatedOn, CreatedFor, IsViewed) VALUES ('$notif_title', '$notif_msg', '$notif_createdOn', '$notif_createdFor', '$notif_isViewed')");

                    if ($ins_company_id) {
                        $companyCount += 1;
                    }
                }

                $response = array(
                    'ResponseCode'        => '200',
                    'ResponseMsg'         => 'Consultant added successfully',
                    'LastInsertRequestId' => $LastInsertRequestId,
                );
            } else {
                $response = array(
                    'ResponseCode' => '201',
                    'ResponseMsg'  => 'Can\'t insert your records.',
                );
            }
        } else {
            $response = array(
                'ResponseCode' => '201',
                'ResponseMsg'  => 'Can\'t insert your records.',
            );
        }
        echo json_encode($response);
    }

######################################## Save Invite My Consultant  ###############################################

    if (isset($_REQUEST['SaveInviteMyConsultant'])) {

        $jsonStr = stripslashes(trim($_REQUEST['SaveInviteMyConsultant']));

        # Decode JSON => Assoc Array
        $assocArray = json_decode($jsonStr, true);

        # Extract Assoc Array => Values

        $Projectid   = $assocArray['ProjectId'];
        $Userid      = $assocArray['UserID'];
        $Description = $assocArray['Description'];
        $companyid   = $assocArray['companyid'];
        $submitdate  = $db->SqlDateFormat("now");
        $status      = "Submitted";

        $insertCon = "INSERT INTO RequestList (CustomerId, CompanyId, ProjectId, Status, SubmittedDate) VALUES ('$Userid','$companyid', '$Projectid', '$status', '$submitdate')";

        $id = $db->SimpleInsert($insertCon);

        if ($id) {
            $selectRequestid = "SELECT TOP 1 Id FROM RequestList WHERE CustomerId = '$Userid' AND ProjectId = '$Projectid' ORDER BY Id DESC";

            if ($db->NumberRow($selectRequestid) > 0) {
                $result = $db->GetRow($selectRequestid);
            }

            $LastInsertRequestId = $result['Id'];

            if ($LastInsertRequestId != '') {
                # Insert Into Table ProjectSelectedAttachment
                $attachmentCount = 0;
                $attachments     = $assocArray['Attachment'];
                foreach ($attachments as $attachment) {
                    $AttachmentId = $attachment['AttachmentId'];

                    $insertAttachment = "INSERT INTO MyConsultantAttachments (RequestId,DocumentAttachId) VALUES ('$LastInsertRequestId','$AttachmentId') ";

                    $attachment_id = $db->SimpleInsert($insertAttachment);

                    if ($attachment_id) {
                        $attachmentCount += 1;
                    }
                }

                # Create a record in Notification table
                $notif_title      = "Invite";
                $notif_msg        = "Received a my consultant invite, RequestList Id " . $LastInsertRequestId;
                $notif_createdOn  = $db->SqlDateFormat("now");
                $notif_createdFor = $companyid;
                $notif_isViewed   = 0;

                $notif_insert = $db->SimpleInsert("INSERT INTO Notifications (Title, Message, CreatedOn, CreatedFor, IsViewed) VALUES ('$notif_title', '$notif_msg', '$notif_createdOn', '$notif_createdFor', '$notif_isViewed')");

                $response = array(
                    'ResponseCode' => '200',
                    'ResponseMsg'  => 'Consultant added successfully',
                );
            } else {
                $response = array(
                    'ResponseCode' => '201',
                    'ResponseMsg'  => 'Can\'t insert your records.',
                );
            }
        } else {
            $response = array(
                'ResponseCode' => '201',
                'ResponseMsg'  => 'Can\'t insert your records.',
            );
        }
        echo json_encode($response);
    }

######################################## End Save Invite My Consultant  ###############################################

######################################## 4  get service type  ###############################################
    if (isset($_REQUEST['GetServicesType'])) {

        $sql = "SELECT Id , ServiceName FROM ProjectServices";

        if ($db->NumberRow($sql) > 0) {
            $result1 = $db->GetResults($sql);
        }

        $Services = array();
        foreach ($result1 as $value) {
            $serviceid   = $value['Id'];
            $serviceName = $value['ServiceName'];

            $sql = "SELECT Id , SubServicesName   FROM  ProjectSubServices  WHERE ServiceID = $serviceid ";

            if ($db->NumberRow($sql) > 0) {
                $result = $db->GetResults($sql);
            }

            $services = array(
                'ServiceId'   => $value['Id'],
                'ServiceName' => $serviceName = $value['ServiceName'],
                'SubServices' => $result,
            );

            array_push($Services, $services);

        }

        $response = array(
            'ResponseCode' => '200',
            'ResponseMsg'  => 'Success',
            'Services'     => $Services,
        );

        echo json_encode($response);

    }
######################################## get doc  web service   ###############################################
    if (isset($_REQUEST['GetDoc'])) {

        $jsonStr = stripslashes(trim($_REQUEST['GetDoc']));

        # Decode JSON => Assoc Array
        $assocArray = json_decode($jsonStr, true);

        # Extract Assoc Array => Values
        $ProjectId = $assocArray['ProjectId'];

        $sql = "SELECT DocumentAttachID , Subject , AttachTypeId  FROM DocumentsAttach WHERE ProjectID = '$ProjectId ' ";

        if ($db->NumberRow($sql) > 0) {
            $result   = $db->GetResults($sql);
            $response = array(
                'ResponseCode' => '200',
                'ResponseMsg'  => 'Success',
                'Documents'    => $result,
            );
        } else {
            $response = array(
                'ResponseCode' => '201',
                'ResponseMsg'  => 'No Documents founds for this project id ',
            );
        }
        echo json_encode($response);
    }

######################################## 5  get consultant web service#############################################

    if (isset($_REQUEST['GetConsultant'])) {

        $jsonStr = stripslashes(trim($_REQUEST['GetConsultant']));

        # Decode JSON => Assoc Array
        $assocArray = json_decode($jsonStr, true);

        # Extract Assoc Array => Values

        $CityId = $assocArray['CityId'];

        $sql = "SELECT CompanyDetails.CompanyID , CompanyDetails.CompanyName , CompanyDetails.Latitude , CompanyDetails.longitude , CompanyDetails.WebAddress , CompanyDetails.Street + ' , ' + CompanyDetails.Place  AS Address ,  CompanyDetails.Email1 AS Email , CompanyDetails.MobileNo , Csstable.LogoImg  FROM CompanyDetails INNER JOIN Csstable ON CompanyDetails.CompanyID = Csstable.CompanyID WHERE CompanyDetails.State = '$CityId' ";

        if ($db->NumberRow($sql) > 0) {
            $result   = $db->GetResults($sql);
            $response = array(
                'ResponseCode'   => '200',
                'ResponseMsg'    => 'Success',
                'CompanyDetails' => $result,
            );
        } else {
            $response = array(
                'ResponseCode' => '201',
                'ResponseMsg'  => 'No Company Details  founds for this city  id ',
            );
        }
        echo json_encode($response);
    }

###################################### 7  Approve Consultant Webservices  ###########################################

    if (isset($_REQUEST['ApproveConsultant'])) {

        $jsonStr = stripslashes(trim($_REQUEST['ApproveConsultant']));

        # Decode JSON => Assoc Array
        $assocArray = json_decode($jsonStr, true);

        # Extract Assoc Array => Values
        $RequestId  = $assocArray['RequestId'];
        $CompanyId  = $assocArray['CompanyId'];
        $CustomerId = $assocArray['CustomerId'];
        $ProjectId  = $assocArray['ProjectId'];

        if ($db->BeginTransaction()) {
            $sql          = "UPDATE QuotesRequest SET ApprovedTo = '$CompanyId' WHERE Id = '$RequestId'";
            $UpdateQuotes = $db->QueryDML($sql);

            # Insert a Record in Customer Mapping Table
            $customer_mapping = $db->SimpleInsert("INSERT INTO CustomerMapping (CustomerId, CompanyId) VALUES('$CustomerId', '$CompanyId')");

            # Insert a Record in Project Mapping Table
            $project_mapping = $db->QueryDML("UPDATE ProjectMapping SET CompanyId = '$CompanyId' WHERE ProjectId = '$ProjectId'");

            if ($UpdateQuotes['Success'] && $customer_mapping && $project_mapping['Success']) {

                $db->Commit();

                # Create Project Specific folders in Company Folders
                # # Prepare Path - Check if it exist if not create a new one
                $realpath        = realpath('../../Documents/');
                $realpathCompany = $realpath . "/" . $CompanyId;
                if (!file_exists($realpathCompany)) {
                    mkdir($realpathCompany, 0777, true);
                }

                $realpathProject = $realpath_company . "/" . $ProjectId;
                if (!file_exists($realpathProject)) {
                    mkdir($realpathProject, 0777, true);
                }

                $realpathLetter = $realpathProject . "/Letter Documents/";
                if (!file_exists($realpathLetter)) {
                    mkdir($realpathLetter, 0777, true);
                }

                $realpathReport = $realpathProject . "/Report Documents/";
                if (!file_exists($realpathReport)) {
                    mkdir($realpathReport, 0777, true);
                }

                $realpathProjectDoc = $realpathProject . "/Projects Documents/";
                if (!file_exists($realpathProjectDoc)) {
                    mkdir($realpathProjectDoc, 0777, true);
                }

                # Create a record in Notification table
                $notif_title      = "Approve";
                $notif_msg        = "Approved consultant for QuotesRequest Id " . $RequestId;
                $notif_createdOn  = $db->SqlDateFormat("now");
                $notif_createdFor = $CompanyId;
                $notif_isViewed   = 0;

                $notif_insert = $db->SimpleInsert("INSERT INTO Notifications (Title, Message, CreatedOn, CreatedFor, IsViewed) VALUES ('$notif_title', '$notif_msg', '$notif_createdOn', '$notif_createdFor', '$notif_isViewed')");

                $response = array(
                    'ResponseCode' => '200',
                    'ResponseMsg'  => 'Company Approve Successfully',
                );
            } else {
                $db->Rollback();

                $response = array(
                    'ResponseCode' => '201',
                    'ResponseMsg'  => 'An error occurred',
                );
            }
        } else {
            $response = array(
                'ResponseCode' => '201',
                'ResponseMsg'  => 'An error occurred',
            );
        }
        echo json_encode($response);
    }

}
