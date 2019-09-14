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

    if (isset($_REQUEST['login'])) {
        # Login Services
        $jsonStr = stripslashes(trim($_REQUEST['login']));

        # Decode JSON => Assoc Array
        $assocArray = json_decode($jsonStr, true);

        # Extract Assoc Array => Values
        $userID    = $assocArray['UserID'];
        $companyId = $assocArray['CompanyID'];
        $password  = $assocArray['Password'];

        # Preparing Query
        $sql = "SELECT * FROM CustomerInformation WHERE CustomerID = '$userID' AND CustomerPassword = '$password'";

        if ($db->NumberRow($sql) > 0) {
            # Customer Exist
            $result = $db->GetRow($sql);

            # Update LoginAs in Customer Information Table
            if (array_key_exists('LoginAs', $assocArray)) {
                $loginAs   = $assocArray['LoginAs'];
                $update_ci = $db->QueryDML("UPDATE CustomerInformation SET LoginAs = '$loginAs' WHERE CustomerID = '$userID'");
            }

            # Get FCM ID if set from the device and save it in table
            if (array_key_exists('FCMID', $assocArray)) {
                $FCMID = $assocArray['FCMID'];
                # Update FCM ID in DB
                $fcmUpdate   = "UPDATE CustomerInformation SET FCMID = '$FCMID' WHERE CustomerID = '$userID'";
                $fcmRes      = $db->QueryDML($fcmUpdate);
                $fcm_success = $fcmRes['Success'];
                if ($fcm_success) {
                    $FCMStatus = "Updated";
                } else {
                    $FCMStatus = "Failed";
                }
                $response['FCMStatus'] = $FCMStatus;
            } else {
                $FCMStatus             = "Failed";
                $response['FCMStatus'] = $FCMStatus;
            }

            # Get project list from ProjectMapping table, passing Customer ID as the criteria
            $project_result = $db->GetResults("SELECT * FROM ProjectMapping WHERE CustomerID = '$userID'");

            $projects = array();
            $count    = 1;

            foreach ($project_result as $projectRow) {
                $projectID = $projectRow['ProjectId'];

                $single_project = $db->GetRow("SELECT * FROM CompanyAllProjects WHERE ProjectID = '$projectID'");

                $project = array(
                    'ProjectID'   => $projectRow['ProjectId'],
                    'ProjectName' => $single_project['ProjectName'],
                );

                array_push($projects, $project);

                if ($count == 1) {
                    $company_id = $projectRow['CompanyId'];

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

                    # Fetch Announcements from Announcements table
                    $announcements = $db->GetResults("SELECT Title AS AnnouncementTitle, Descriptions AS AnnouncementDescription FROM Announcements WHERE CompanyID = '$company_id'");
                }

                ++$count;
            }

            $response = array(
                'ResponseCode'  => '200',
                'ResponseMsg'   => 'Welcome back ' . ucwords($result['CustomerName']) . '!',
                'UserID'        => $result['CustomerID'],
                'CompanyID'     => $company_id,
                'CustomerName'  => $result['CustomerFirstName'],
                'CustomerPhoto' => $result['CustomerPhoto'],
                'CompanyName'   => $company_name,
                'CompanyLogo'   => $company_logo,
                'CompanyPhone'  => $company_phone,
                'CompanyEmail'  => $company_email,
                'LoginType'     => $result['LoginType'],
                'Projects'      => $projects,
                'Announcements' => $announcements,
            );

        } else {
            # Incorrect login credential or Customer does not exist.
            $response = array(
                'ResponseCode' => '201',
                'ResponseMsg'  => 'Sorry, incorrect UserID. Try again later.',
            );
        }

    } # End Login Services

    # Home API
    else if (isset($_REQUEST['home'])) {
        # Login Services
        $jsonStr = stripslashes(trim($_REQUEST['home']));

        # Decode JSON => Assoc Array
        $assocArray = json_decode($jsonStr, true);

        # Extract Assoc Array => Values
        $userID    = $assocArray['UserID'];
        $projectID = $assocArray['ProjectID'];

        # Preparing Query
        $sql = "SELECT * FROM CustomerInformation WHERE CustomerID = '$userID'";

        if ($db->NumberRow($sql) > 0) {
            # Customer Exist
            $result = $db->GetRow($sql);

            # Get project list from ProjectMapping table, passing Customer ID as the criteria
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

            # Fetch Announcements from Announcements table
            $announcements = $db->GetResults("SELECT Title AS AnnouncementTitle, Descriptions AS AnnouncementDescription FROM Announcements WHERE CompanyID = '$company_id'");

            # Fetch Customer's Projects from Project Mapping Table and Project Names from Company All Projects Table
            $projects = $db->GetResults("SELECT PM.ProjectId as ProjectID, CP.ProjectName FROM ProjectMapping PM INNER JOIN CompanyAllProjects CP ON PM.ProjectId = CP.ProjectID WHERE PM.CustomerID = '$userID'");

            $response = array(
                'ResponseCode'  => '200',
                'ResponseMsg'   => 'Welcome back ' . ucwords($result['CustomerName']) . '!',
                'UserID'        => $result['CustomerID'],
                'CompanyID'     => $company_id,
                'CustomerName'  => $result['CustomerFirstName'],
                'CustomerPhoto' => $result['CustomerPhoto'],
                'CompanyName'   => $company_name,
                'CompanyLogo'   => $company_logo,
                'CompanyPhone'  => $company_phone,
                'CompanyEmail'  => $company_email,
                'LoginType'     => $result['LoginType'],
                'Projects'      => $projects,
                'Announcements' => $announcements,
            );

        } else {
            # Incorrect login credential or Customer does not exist.
            $response = array(
                'ResponseCode' => '201',
                'ResponseMsg'  => 'Sorry, incorrect UserID. Try again later.',
            );
        }

    } # End Login Services

    # Start Project Home
    elseif (isset($_REQUEST['project-home'])) {
        # Home Service
        $jsonStr = stripslashes(trim($_REQUEST['project-home']));

        # Decode JSON => Assoc Array
        $assocArray = json_decode($jsonStr, true);

        # Extract Assoc Array => Values
        $userID    = $assocArray['UserID'];
        $projectID = $assocArray['ProjectID'];

        # Preparing Query
        $sql = "SELECT * FROM CustomerInformation WHERE CustomerID = '$userID'";

        if ($db->NumberRow($sql) > 0) {
            # Customer Exist
            $result = $db->GetRow($sql);

            # Get project list from ProjectMapping table, passing Customer ID as the criteria
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

            # Fetch Announcements from Announcements table
            $announcements = $db->GetResults("SELECT Title AS AnnouncementTitle, Descriptions AS AnnouncementDescription FROM Announcements WHERE CompanyID = '$company_id'");

            # Fetch Customer's Projects from Project Mapping Table and Project Names from Company All Projects Table
            $projects = $db->GetResults("SELECT PM.ProjectId as ProjectID, CP.ProjectName FROM ProjectMapping PM INNER JOIN CompanyAllProjects CP ON PM.ProjectId = CP.ProjectID WHERE PM.CustomerID = '$userID' ORDER BY CP.ProjectName ASC");

            # Get Project Details
            $projectRow = $db->GetRow("SELECT * FROM CompanyAllProjects WHERE CustomerID = '$userID' AND ProjectID = '$projectID'");

            $step_start_date = json_decode(json_encode($projectRow['StepStartDate']), true);

            # Get Project Details
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

                $balance = $totalCash - $totalIncome;
            }

            # Fetching Slider Images - Table ProjectMonthlyReport
            $sliderResult           = $db->GetResults("SELECT * FROM ProjectMonthlyReport WHERE ProjectID = '$projectID' ORDER BY ReportDate DESC");
            $report_count           = 1;
            $sliderImages           = array();
            $totalReports           = 0;
            $recentReports          = 0;
            $currentMonth           = date('F');
            $firstDayofCurrentMonth = date('Y-m-d H:i:s', strtotime('first day of ' . $currentMonth));
            $lastDayofCurrentMonth  = date('Y-m-d H:i:s', strtotime('last day of ' . $currentMonth));

            if ($sliderResult) {
                foreach ($sliderResult as $sliderRow) {
                    if (!$sliderRow['ReportViewed']) {
                        ++$totalReports;
                    }

                    if ($report_count == 1) {
                        $regEx = '/attachment\d\d?$/i';
                        foreach ($sliderRow as $key => $value) {
                            if (preg_match_all($regEx, $key) && $value) {
                                array_push($sliderImages, $value);
                            }
                        }
                    }
                    $report_count++;
                }
            }

            # Fetching DocNeedApproval - Table DocumentsAttach
            $docSql          = "SELECT COUNT(DocStatus) AS unApprovedDoc FROM DocumentsAttach WHERE ProjectID = '$projectID' AND (DocStatus = 'NotApprove' OR DocStatus = 'Need Decision')";
            $docResult       = $db->GetRow($docSql);
            $un_approved_doc = $docResult['unApprovedDoc'];

            # Get Letters Count
            $letter_sql    = "SELECT COUNT(LetterID) as letterCount FROM LetterManagement WHERE ProjectID = '$projectID' AND LetterViewed = 0";
            $letter_result = $db->GetRow($letter_sql);
            $letter_count  = $letter_result['letterCount'];

            # Get Project Percentage
            $project_per_result  = $db->GetResults("SELECT CompletePercentage FROM ProjectMileStone WHERE ProjectID = '$projectID'");
            $project_per_total   = 0;
            $project_per_records = 0;
            $project_per         = 0;

            if ($project_per_result) {
                foreach ($project_per_result as $project_per_row) {
                    $project_per_records++;
                    $project_per_total += (float) $project_per_row['CompletePercentage'];
                }
                $project_per = $project_per_total / $project_per_records;
            }

            if (array_key_exists('FCMID', $assocArray)) {
                $FCMID = $assocArray['FCMID'];
                # Update FCM ID in DB
                $fcmUpdate   = "UPDATE CustomerInformation SET FCMID = '$FCMID' WHERE CustomerID = '$userID'";
                $fcmRes      = $db->QueryDML($fcmUpdate);
                $fcm_success = $fcmRes['Success'];
                if ($fcm_success) {
                    $FCMStatus = "Updated";
                } else {
                    $FCMStatus = "Failed";
                }
                $response['FCMStatus'] = $FCMStatus;
            } else {
                $FCMStatus             = "Failed";
                $response['FCMStatus'] = $FCMStatus;
            }

            $response = array(
                'ResponseCode'    => '200',
                'ResponseMsg'     => 'Welcome back ' . ucwords($result['CustomerName']) . '!',
                'UserID'          => $result['CustomerID'],
                'CompanyID'       => $company_id,
                'CustomerName'    => $result['CustomerFirstName'],
                'CustomerPhoto'   => $result['CustomerPhoto'],
                'CompanyName'     => $company_name,
                'CompanyLogo'     => $company_logo,
                'CompanyPhone'    => $company_phone,
                'CompanyEmail'    => $company_email,
                'LoginType'       => $result['LoginType'],
                'Projects'        => $projects,
                'ProjectPercent'  => $project_per,
                'SliderImages'    => $sliderImages,
                'UnApprovedDoc'   => $un_approved_doc,
                'TotalReports'    => $totalReports,
                'RemainingAmount' => $balance,
                'LetterCount'     => $letter_count,
                'Announcements'   => $announcements,
                'ProjectID'       => $projectRow['ProjectID'],
                'ProjectName'     => $projectRow['ProjectName'],
                'Location'        => $projectRow['Location'],
                'ProjectType'     => $projectRow['ProjectTypeId'],
                'Cost'            => $projectRow['BudgetLimit'],
                'SitePlan'        => $projectRow['CrockeyUrl'],
                'FloorLevel'      => $projectRow['FloorLevel'],
                'Description'     => $projectRow['Description'],
                'Latitude'        => $projectRow['Latitude'],
                'Longitude'       => $projectRow['Longitude'],
                'StepStartDate'   => $step_start_date['date'],
                'City'            => $projectRow['City'],
                'Country'         => $projectRow['country'],
            );

        } else {
            # Incorrect login credential or Customer does not exist.
            $response = array(
                'ResponseCode' => '201',
                'ResponseMsg'  => 'Sorry, incorrect Customer ID. Try again later.',
            );
        }
    } # End Project Home

    # Login As Service
    elseif (isset($_REQUEST['login-as'])) {
        # Fetch json string
        $jsonStr = stripslashes(trim($_REQUEST['login-as']));

        # Decode json string => Assoc array
        $assocArray = json_decode($jsonStr, true);

        # Fetch values from Assoc array
        $loginAs = $assocArray['LoginAs'];
        $userID  = $assocArray['UserID'];

        $update_ci = $db->QueryDML("UPDATE CustomerInformation SET LoginAs = '$loginAs' WHERE CustomerID = '$userID'");

        if ($update_ci['Success']) {
            $response = array('ResponseCode' => '200', 'ResponseMsg' => 'Login as updated successfully.');
        } else {
            $response = array('ResponseCode' => '201', 'ResponseMsg' => 'Error, please try again later.');
        }
    } # End Login As Service

    # Check Unique Mobile in Customer Information Table
    elseif (isset($_REQUEST['unique-mobile'])) {
        # Fetch json string
        $jsonStr = stripslashes(trim($_REQUEST['unique-mobile']));

        # Decode json string => Assoc array
        $assocArray = json_decode($jsonStr, true);

        # Fetch values from Assoc array
        $mobile = $assocArray['Mobile'];

        # Check if mobile number is already registered
        $is_unique = $db->GetRow("SELECT CustomerID FROM CustomerInformation WHERE CustomerPhone = '$mobile'");

        if ($is_unique) {
            $response = array('ResponseCode' => '201', 'ResponseMsg' => 'Mobile is already registered.');
        } else {
            $response = array('ResponseCode' => '200', 'ResponseMsg' => 'Mobile isn\'t already registered.');
        }
    } # End Check Unique Mobile in Customer Information Table

    # Check Unique Email in Customer Information Table
    elseif (isset($_REQUEST['unique-email'])) {
        # Fetch json string
        $jsonStr = stripslashes(trim($_REQUEST['unique-email']));

        # Decode json string => Assoc array
        $assocArray = json_decode($jsonStr, true);

        # Fetch values from Assoc array
        $email = $assocArray['Email'];

        # Check if email number is already registered
        $is_unique = $db->GetRow("SELECT CustomerID FROM CustomerInformation WHERE CustomerEmail = '$email'");

        if ($is_unique) {
            $response = array('ResponseCode' => '201', 'ResponseMsg' => 'Email is already registered.');
        } else {
            $response = array('ResponseCode' => '200', 'ResponseMsg' => 'Email isn\'t already registered.');
        }
    } # End Check Unique Email in Customer Information Table

    # Can't find the requested parent key
    else {
        # Unidentified Request
        $response = array(
            'ResponseCode' => '201',
            'ResponseMsg'  => 'Unidentified json string. Please send a valid json string.',
        );
    } # End Unidentified Request

} else {
    # Not a POST Request
    $response = array(
        'ResponseCode' => '201',
        'ResponseMsg'  => 'Incorrect request method. Please send data via POST.',
    );
} # End Not a POST Request

echo json_encode($response);
