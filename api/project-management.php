<?php
header('Content-type: application/json');
header('Access-Control-Allow-Origin: *');
require_once '../config/database.php';
require_once '../config/functions.php';
$db       = new Database();
$fn       = new GenFunctions();
$response = array();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    # Project Selection
    if (isset($_REQUEST['project-select'])) {
        $jsonStr = stripslashes(trim($_REQUEST['project-select']));

        # Decode JSON => Assoc Array
        $assocArray = json_decode($jsonStr, true);

        # Extract Assoc Array => Values
        $userID    = $assocArray['UserID'];
        $projectID = $assocArray['ProjectID'];

        # Get Project Details
        $projectRow = $db->GetRow("SELECT * FROM CompanyAllProjects WHERE CustomerID = '$userID' AND ProjectID = '$projectID'");

        if ($projectRow) {
            # Fetching Slider Images - Table ProjectMonthlyReport
            $sliderResult = $db->GetResults("SELECT TOP 1 * FROM ProjectMonthlyReport WHERE ProjectID = '$projectID' ORDER BY ReportDate DESC");
            $sliderImages = array();

            # Regular Expression to identify attachment fields
            $regEx = '/attachment\d\d?$/i';

            foreach ($sliderResult as $sliderRow) {
                foreach ($sliderRow as $key => $value) {
                    if (preg_match_all($regEx, $key) && $value) {
                        array_push($sliderImages, $value);
                    }
                }
                unset($key, $value);
            }
            unset($sliderRow);

            # Fetching DocNeedApproval - Table DocumentsAttach
            $doc_row = $db->GetRow("SELECT COUNT(DocStatus) AS UnApprovedDoc FROM DocumentsAttach WHERE ProjectID = '$projectID' AND (DocStatus = 'NotApprove' OR DocStatus = 'Need Decision')");

            # Letters
            $letter_row = $db->GetRow("SELECT COUNT(LetterViewed) AS LetterNotViewed FROM LetterManagement WHERE ProjectID = '$projectID' AND LetterViewed = '0'");

            # Reports
            $report_row = $db->GetRow("SELECT COUNT(ReportViewed) AS ReportNotViewed FROM ProjectMonthlyReport WHERE ProjectID = '$projectID' AND ReportViewed = '0'");

            # Remaining Balance
            $balance_result = $db->GetResults("SELECT * FROM InvoiceProjectDetails WHERE ProjectID = '$projectID' AND TransactionTypeID IN ('Cash', 'Income')");
            $totalIncome    = 0;
            $totalCash      = 0;
            $balance        = 0;

            if ($balance_result) {
                foreach ($balance_result as $balance_row) {
                    $txnType = $balance_row['TransactionTypeID'];

                    if ($txnType == 'Cash') {
                        $totalIncome += $balance_row['InvoiceTotal'];
                    } elseif ($txnType == 'Income') {
                        $totalCash += $balance_row['InvoiceTotal'];
                    }
                }
                unset($balance_row);
                $balance = $totalCash - $totalIncome;
            }

            # Get Project Percentage
            $project_per_res   = $db->GetResults("SELECT CompletePercentage FROM ProjectMileStone WHERE ProjectID = '$projectID'");
            $milestone_count   = 0;
            $project_per_total = 0;
            $project_per       = 0;

            if ($project_per_res) {
                foreach ($project_per_res as $project_per_row) {
                    ++$milestone_count;
                    $project_per_total += (int) $project_per_row['CompletePercentage'];
                }
                unset($project_per_row);
                $project_per = (string) round($project_per_total / $milestone_count);
            }

            # Get project detail from ProjectMapping table, passing CustomerID and ProjectID as the criteria
            $project_result = $db->GetRow("SELECT * FROM ProjectMapping WHERE CustomerID = '$userID' AND ProjectId = '$projectID'");

            $company_id = $project_result['CompanyId'];

            if ($company_id == '' || is_null($company_id)) {
                $company_name  = "Individual Account";
                $company_logo  = "http://www.mysmartpro.com/images/My%20Smart%20Pro%20Logo-04.png";
                $company_phone = "Individual Account";
                $company_email = "Individual Account";
            } else {
                # Fetch company details based on company id fetched from ProjectMapping table
                $company_sql = "SELECT CompanyName, CompanyLogo, Phone, Email1 FROM CompanyDetails WHERE CompanyID = '$company_id'";
                $company_row = $db->GetRow($company_sql);

                $company_logo_row = $db->GetRow("SELECT LogoImg FROM Csstable WHERE CompanyID = '$company_id'");

                $company_name  = $company_row['CompanyName'];
                $company_logo  = $company_logo_row['LogoImg'];
                $company_phone = $company_row['Phone'];
                $company_email = $company_row['Email1'];
            }

            # Announcements
            $announcements = $db->GetResults("SELECT Title AS AnnouncementTitle, Descriptions AS AnnouncementDescription FROM Announcements WHERE CompanyID = '$company_id'");

            $step_start_date = json_decode(json_encode($projectRow['StepStartDate']), true);

            $response = array(
                'ResponseCode'       => '200',
                'ResponseMsg'        => 'Success',
                'ProjectID'          => $projectRow['ProjectID'],
                'ProjectName'        => $projectRow['ProjectName'],
                'Location'           => $projectRow['Location'],
                'ProjectType'        => $projectRow['ProjectTypeId'],
                'Cost'               => $projectRow['BudgetLimit'],
                'SitePlan'           => $projectRow['CrockeyUrl'],
                'FloorLevel'         => $projectRow['FloorLevel'],
                'Description'        => $projectRow['Description'],
                'Latitude'           => $projectRow['Latitude'],
                'Longitude'          => $projectRow['Longitude'],
                'StepStartDate'      => $step_start_date['date'],
                'City'               => $projectRow['City'],
                'Country'            => $projectRow['country'],
                'ProjectPercent'     => $project_per,
                'RemainingAmount'    => $balance,
                'TotalUnviewReport'  => $letter_row['LetterNotViewed'],
                'TotalUnApprovedDoc' => $doc_row['unApprovedDoc'],
                'TotalUnviewLetter'  => $report_row['ReportNotViewed'],
                'SliderImages'       => $sliderImages,
                'CompanyID'          => $company_id,
                'CompanyName'        => $company_name,
                'CompanyLogo'        => $company_logo,
                'CompanyPhone'       => $company_phone,
                'CompanyEmail'       => $company_email,
                'Announcements'      => $announcements,
            );

        } else {
            $response = array(
                'ResponseCode' => '201',
                'ResponseMsg'  => 'Could not find the desired project. Select a valid project.',
            );
        }
    } # End Project Selection

    # Project Detail
    elseif (isset($_REQUEST['projectdetail'])) {
        $jsonStr = stripslashes(trim($_REQUEST['projectdetail']));

        # Decode JSON => Assoc Array
        $assocArray = json_decode($jsonStr, true);

        # Extract Assoc Array => Values
        $userID    = $assocArray['UserID'];
        $projectID = $assocArray['ProjectID'];

        # Get Project Details
        $projectRow = $db->GetRow("SELECT * FROM CompanyAllProjects WHERE CustomerID = '$userID' AND ProjectID = '$projectID'");

        if ($projectRow) {
            $step_start_date = json_decode(json_encode($projectRow['StepStartDate']), true);

            # Preparing Response
            $response = array(
                'ResponseCode'  => '200',
                'ResponseMsg'   => 'Success',
                'ProjectID'     => $projectRow['ProjectID'],
                'ProjectName'   => $projectRow['ProjectName'],
                'Location'      => $projectRow['Location'],
                'ProjectType'   => $projectRow['ProjectTypeId'],
                'Cost'          => $projectRow['BudgetLimit'],
                'SitePlan'      => $projectRow['CrockeyUrl'],
                'FloorLevel'    => $projectRow['FloorLevel'],
                'Description'   => $projectRow['Description'],
                'Latitude'      => $projectRow['Latitude'],
                'Longitude'     => $projectRow['Longitude'],
                'StepStartDate' => $step_start_date['date'],
                'City'          => $projectRow['City'],
                'Country'       => $projectRow['country'],
            );
        } else {
            $response = array(
                'ResponseCode' => '201',
                'ResponseMsg'  => 'Could not find the desired project. Select a valid project.',
            );
        }
    } # End Project Detail

    # Get Map Details
    elseif (isset($_REQUEST['map'])) {
        $jsonStr = stripslashes(trim($_REQUEST['map']));

        # Decode JSON => Assoc Array
        $assocArray = json_decode($jsonStr, true);

        # Extract Assoc Array => Values
        $projectID = $assocArray['ProjectID'];

        # Get Project Details
        $result = $db->GetRow("SELECT Latitude, Longitude FROM CompanyAllProjects WHERE ProjectID = '$projectID'");

        if ($result) {
            $response = array(
                'ResponseCode' => '200',
                'ResponseMsg'  => 'Success',
                'Latitude'     => $result['Latitude'],
                'Longitude'    => $result['Longitude'],
            );
        } else {
            $response = array(
                'ResponseCode' => '201',
                'ResponseMsg'  => 'Could not find the project you are looking for. Please select a valid project.',
            );
        }
    } # End Get Map Details

    # Get Balance Detail
    elseif (isset($_REQUEST['payment-detail'])) {
        $jsonStr = stripslashes(trim($_REQUEST['payment-detail']));

        # Decode JSON => Assoc Array
        $assocArray = json_decode($jsonStr, true);

        # Extract Assoc Array => Values
        $userID    = $assocArray['UserID'];
        $projectID = $assocArray['ProjectID'];

        # Get Project
        $project_row  = $db->GetRow("SELECT BudgetLimit FROM CompanyAllProjects WHERE ProjectID = '$projectID'");
        $total_amount = $project_row['BudgetLimit'];

        # Get Project Payment Schedule
        $paymentResult               = $db->GetRow("SELECT SUM(PaymentAmount) as PaidAmount FROM ProjectPaymentsSchedule WHERE ProjectID = '$projectID'");
        $PaidAmount                  = $paymentResult['PaidAmount'];
        $RemainingAmount             = $total_amount - $PaidAmount;
        $paid_amount_percentage      = $PaidAmount / $total_amount * 100;
        $remaining_amount_percentage = $RemainingAmount / $total_amount * 100;

        $response = array(
            'ResponseCode'              => '200',
            'ResponseMsg'               => 'Success',
            'TotalAmount'               => $total_amount,
            'RemainingAmount'           => $RemainingAmount,
            'PaidAmount'                => $PaidAmount,
            'PaidAmountPercentage'      => $paid_amount_percentage,
            'RemainingAmountPercentage' => $remaining_amount_percentage,
        );

    } # End Get Balance Detail

    # Get Balance Detail
    elseif (isset($_REQUEST['getbalance'])) {
        $jsonStr = stripslashes(trim($_REQUEST['getbalance']));

        # Decode JSON => Assoc Array
        $assocArray = json_decode($jsonStr, true);

        # Extract Assoc Array => Values
        $projectID = $assocArray['ProjectID'];

        # Get Project Details
        $result       = $db->GetResults("SELECT * FROM InvoiceProjectDetails WHERE ProjectID = '$projectID' AND TransactionTypeID IN ('Cash', 'Income')");
        $transactions = array();

        if ($result) {
            foreach ($result as $row) {
                $invoicedate = json_decode(json_encode($row['InvoiceDate']), true);
                $transaction = array(
                    #'InvoiceNumber' => $row['InvoiceNumber'],
                    'InvoiceNumber'     => $row['TxnNo'],
                    'TransactionTypeID' => $row['TransactionTypeID'],
                    'InvoiceDate'       => $invoicedate['date'],
                    'InvoiceTotal'      => $row['InvoiceTotal'],
                    'Notes'             => "TBD",
                );
                array_push($transactions, $transaction);
            }
            unset($row);

            $response = array(
                'ResponseCode' => '200',
                'ResponseMsg'  => 'Success',
                'Transactions' => $transactions,
            );
        } else {
            $response = array(
                'ResponseCode' => '200',
                'ResponseMsg'  => 'There are not transactions as of now for this project.',
            );
        }
    } # End Get Balance Detail

    # Get Balance Summary
    elseif (isset($_REQUEST['balancesummary'])) {
        $jsonStr = stripslashes(trim($_REQUEST['balancesummary']));

        # Decode JSON => Assoc Array
        $assocArray = json_decode($jsonStr, true);

        # Extract Assoc Array => Values
        $projectID = $assocArray['ProjectID'];

        # Get Project Details
        $result      = $db->GetResults("SELECT * FROM InvoiceProjectDetails WHERE ProjectID = '$projectID' AND TransactionTypeID IN ('Cash', 'Income') ORDER BY InvoiceDate DESC");
        $totalIncome = 0;
        $totalCash   = 0;
        $balance     = 0;

        if ($result) {
            $invoices = array();
            $receipts = array();

            foreach ($result as $row) {
                $txnType      = $row['TransactionTypeID'];
                $invoice_date = json_decode(json_encode($row['InvoiceDate']), true);

                if ($txnType == 'Income') {
                    $totalCash += $row['InvoiceTotal'];
                    $invoice = array(
                        // 'InvoiceNumber' => $row['InvoiceNumber'],
                        'InvoiceNumber'     => $row['TxnNo'],
                        'TransactionTypeID' => $row['TransactionTypeID'],
                        'InvoiceDate'       => $invoice_date['date'],
                        'InvoiceTotal'      => $row['InvoiceTotal'],
                        'Notes'             => $row['Descriptions'],
                    );

                    array_push($invoices, $invoice);

                } elseif ($txnType == 'Cash') {
                    $totalIncome += $row['InvoiceTotal'];

                    $receipt = array(
                        // 'InvoiceNumber' => $row['InvoiceNumber'],
                        'InvoiceNumber'     => $row['TxnNo'],
                        'TransactionTypeID' => $row['TransactionTypeID'],
                        'InvoiceDate'       => $invoice_date['date'],
                        'InvoiceTotal'      => $row['InvoiceTotal'],
                        'Notes'             => $row['Notes'],
                    );

                    array_push($receipts, $receipt);
                }
            }
            unset($row);

            $balance = $totalCash - $totalIncome;

            $response = array(
                'ResponseCode'  => '200',
                'ResponseMsg'   => 'Success',
                'ProjectID'     => $projectID,
                'TotalCash'     => $totalCash,
                'TotalIncome'   => $totalIncome,
                'Balance'       => $balance,
                'InvoiceDetail' => $invoices,
                'ReceiptDetail' => $receipts,
            );
        } else {
            $response = array(
                'ResponseCode' => '200',
                'ResponseMsg'  => 'There are not any transactions as of now for this project.',
            );
        }
    } # End Get Balance Summary

    # Get Letters
    elseif (isset($_REQUEST['letters'])) {
        $jsonStr = stripslashes(trim($_REQUEST['letters']));

        # Decode JSON => Assoc Array
        $assocArray = json_decode($jsonStr, true);

        # Extract Assoc Array => Values
        $projectID = $assocArray['ProjectID'];

        # Get Letters
        $result    = $db->GetResults("SELECT * FROM LetterManagement WHERE ProjectID = '$projectID' ORDER BY LetterDate DESC, ReceiveDate DESC");
        $senders   = array();
        $receivers = array();

        if ($result) {
            foreach ($result as $row) {
                $senderType   = $row['SenderType'];
                $receiverType = $row['ReceiverType'];

                $letterdate = json_decode(json_encode($row['LetterDate']), true);

                if ($senderType) {
                    $sender = array(
                        'LetterID'   => $row['LetterID'],
                        'SenderName' => $row['SenderName'],
                        'SendDate'   => $letterdate['date'],
                        'Subject'    => $row['Subject'],
                        'LetterUrl'  => $row['LetterUrl'],
                    );

                    array_push($senders, $sender);
                }

                $receivedate = json_decode(json_encode($row['ReceiveDate']), true);

                if ($receiverType) {
                    $receiver = array(
                        'LetterID'     => $row['LetterID'],
                        'ReceiverName' => $row['ReceiverName'],
                        'ReceiveDate'  => $receivedate['date'],
                        'Subject'      => $row['Subject'],
                        'LetterUrl'    => $row['LetterUrl'],
                    );

                    array_push($receivers, $receiver);
                }
            }
            unset($row);

            $update_letters = "UPDATE LetterManagement SET LetterViewed = 1 WHERE ProjectID = '$projectID'";
            $update_status  = $db->QueryDML($update_letters);

            $response = array(
                'ResponseCode' => '200',
                'ResponseMsg'  => 'Success',
                'ProjectID'    => $projectID,
                'Senders'      => $senders,
                'Receivers'    => $receivers,
            );
        } else {
            $response = array(
                'ResponseCode' => '200',
                'ResponseMsg'  => 'There are not any letters for this project.',
            );
        }
    } # End Get Letters

    elseif (isset($_REQUEST['milestonedetail'])) {
        $jsonStr = stripslashes(trim($_REQUEST['milestonedetail']));

        # Decode JSON => Assoc Array
        $assocArray = json_decode($jsonStr, true);

        # Extract Assoc Array => Values
        # $userID = $assocArray['UserID'];
        $projectID = $assocArray['ProjectID'];

        # Fetching Milestones For the Same Project - Table ProjectMileStone
        $milestoneResult = $db->GetResults("SELECT MM.MilestoneName As 'MainMileStoneName', C.ProjectName, C.ProjectID, C.StepStatusId, M.MileStoneID, PM.MilestoneName, M.PlanEndDate, M.ActualEndDate, M.CompletePercentage, M.Weightage, M.totalPercentage FROM MileStone MM INNER JOIN ProjectMasterMileStone PM on MM.Id = PM.MileStoneID INNER JOIN ProjectMileStone M on PM.Id = M.MileStoneID INNER JOIN CompanyAllProjects C on m.ProjectID = C.ProjectID WHERE C.ProjectID = '$projectID' ORDER BY MM.MilestoneName ASC");
        $milestones      = array();

        foreach ($milestoneResult as $milestoneRow) {
            $PlanEndDate        = $milestoneRow['PlanEndDate'];
            $ActualEndDate      = $milestoneRow['ActualEndDate'];
            $CompletePercentage = $milestoneRow['CompletePercentage'];

            $complete_per = substr($CompletePercentage, 0, -1);

            if ($CompletePercentage == "0") {
                $milestoneStatus = 1; # Not Start
            } elseif ($CompletePercentage < "100") {
                $milestoneStatus = 3; # Progress
            } else {
                $milestoneStatus = 4; # Complete
            }

            # Extract Date
            $actual_end_date = json_decode(json_encode($milestoneRow['ActualEndDate']), true);
            $plan_end_date   = json_decode(json_encode($milestoneRow['PlanEndDate']), true);

            $milestone = array(
                'MilestoneID'        => $milestoneRow['MileStoneID'],
                'MilestoneName'      => $milestoneRow['MainMileStoneName'],
                'MainMileStoneName'  => $milestoneRow['MilestoneName'],
                'PlanEndDate'        => $plan_end_date['date'],
                'ActualEndDate'      => $actual_end_date['date'],
                'CompletePercentage' => $milestoneRow['CompletePercentage'],
                'TotalPercentage'    => $milestoneRow['totalPercentage'],
                'Status'             => $milestoneStatus,
            );

            array_push($milestones, $milestone);
        }
        unset($milestoneRow);

        $response = array(
            'ResponseCode' => '200',
            'ResponseMsg'  => 'Success Milestones.',
            'Milestones'   => $milestones,
        );
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
