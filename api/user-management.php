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

            # Get Projects of current user
            // $projectSql = "SELECT * FROM CompanyAllProjects WHERE CustomerID = '$userID'";
            // $projectResult = $db->Query($projectSql);

            $projects = array();
            $count    = 1;

            if ($project_result) {
                foreach ($project_result as $projectRow) {
                    $projectID = $projectRow['ProjectId'];

                    $single_project = $db->GetRow("SELECT * FROM CompanyAllProjects WHERE ProjectID = '$projectID'");

                    $project = array(
                        'ProjectID'   => $projectRow['ProjectId'],
                        'ProjectName' => $single_project['ProjectName'],
                    );

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

                        # Get Project Details
                        $balance_result = $db->GetResults("SELECT * FROM InvoiceProjectDetails WHERE ProjectID = '$projectID' AND TransactionTypeID IN ('Cash', 'Income')");
                        $totalIncome    = 0;
                        $totalCash      = 0;
                        $balance        = 0;

                        if ($balance_result) {
                            foreach ($balance_result as $row) {
                                $txnType = $row['TransactionTypeID'];

                                if ($txnType == 'Cash') {
                                    $totalIncome += $row['InvoiceTotal'];
                                } elseif ($txnType == 'Income') {
                                    $totalCash += $row['InvoiceTotal'];
                                }
                            }
                            unset($row);
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
                                ++$report_count;
                            }
                            unset($sliderRow);
                        }

                        # Fetching DocNeedApproval - Table DocumentsAttach
                        $docResult       = $db->GetRow("SELECT COUNT(DocStatus) AS unApprovedDoc FROM DocumentsAttach WHERE ProjectID = '$projectID' AND (DocStatus = 'NotApprove' OR DocStatus = 'Need Decision')");
                        $un_approved_doc = $docResult['unApprovedDoc'];

                        # Get Letters Count
                        $letter_result = $db->GetRow("SELECT COUNT(LetterID) as letterCount FROM LetterManagement WHERE ProjectID = '$projectID' AND LetterViewed = 0");
                        $letter_count  = $letter_result['letterCount'];

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
                            $project_per = (string) round($project_per_total / $milestone_count);
                        }
                    }

                    array_push($projects, $project);
                    ++$count;
                }
                unset($projectRow);

                $response = array(
                    'ResponseCode'    => '200',
                    'ResponseMsg'     => 'Welcome back ' . ucwords($result['CustomerName']) . '!',
                    'UserID'          => $result['CustomerID'],
                    'CompanyID'       => $result['CompanyID'],
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
                );
            } else {
                $response = array(
                    'ResponseCode' => '200',
                    'ResponseMsg'  => 'Welcome back ' . ucwords($result['CustomerName']) . '!',
                    'UserID'       => $result['CustomerID'],
                    'CustomerName' => $result['CustomerFirstName'],
                );
            }
        } else {
            # Incorrect login credential or Customer does not exist.
            $response = array(
                'ResponseCode' => '201',
                'ResponseMsg'  => 'Sorry, incorrect login credential. Try again later.',
            );
        }

    } # End Login Services

    elseif (isset($_REQUEST['home'])) {
        # Home Service
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

            # Announcements
            $announcements = $db->GetResults("SELECT Title AS AnnouncementTitle, Descriptions AS AnnouncementDescription FROM Announcements WHERE CompanyID = '$company_id'");

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

            $projects = $db->GetResults("SELECT PM.ProjectId as ProjectID, CP.ProjectName FROM ProjectMapping PM INNER JOIN CompanyAllProjects CP ON PM.ProjectId = CP.ProjectID WHERE PM.CustomerID = '$userID'");

            # Get Project Details
            $balance_result = $db->GetResults("SELECT * FROM InvoiceProjectDetails WHERE ProjectID = '$projectID' AND TransactionTypeID IN ('Cash', 'Income')");
            $totalIncome    = 0;
            $totalCash      = 0;
            $balance        = 0;

            if ($balance_result) {
                foreach ($balance_result as $row) {
                    $txnType = $row['TransactionTypeID'];

                    if ($txnType == 'Cash') {
                        $totalIncome += $row['InvoiceTotal'];
                    } elseif ($txnType == 'Income') {
                        $totalCash += $row['InvoiceTotal'];
                    }
                }
                unset($row);
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
                    ++$report_count;
                }
                unset($sliderRow);
            }

            # Fetching DocNeedApproval - Table DocumentsAttach
            $docResult       = $db->GetRow("SELECT COUNT(DocStatus) AS unApprovedDoc FROM DocumentsAttach WHERE ProjectID = '$projectID' AND (DocStatus = 'NotApprove' OR DocStatus = 'Need Decision')");
            $un_approved_doc = $docResult['unApprovedDoc'];

            # Get Letters Count
            $letter_result = $db->GetRow("SELECT COUNT(LetterID) as letterCount FROM LetterManagement WHERE ProjectID = '$projectID' AND LetterViewed = 0");
            $letter_count  = $letter_result['letterCount'];

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

            $response = array(
                'ResponseCode'    => '200',
                'ResponseMsg'     => 'Welcome back ' . ucwords($result['CustomerName']) . '!',
                'UserID'          => $result['CustomerID'],
                'CompanyID'       => $result['CompanyID'],
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
            );
        } else {
            # Incorrect login credential or Customer does not exist.
            $response = array(
                'ResponseCode' => '201',
                'ResponseMsg'  => 'Sorry, incorrect Customer ID. Try again later.',
            );
        }

    } # End Home Services

    elseif (isset($_REQUEST['forgot'])) {
        # Forgot Password or UserID/CustomerID
        $jsonStr = stripslashes(trim($_REQUEST['forgot']));

        # Decode JSON => Assoc Array
        $assocArray = json_decode($jsonStr, true);

        # Extract Assoc Array => Values
        $type    = strtolower($assocArray['Type']);
        $channel = strtolower($assocArray['Channel']);
        $id      = $assocArray['ID'];

        switch ($channel) {
            case 'sms':
                if (preg_match_all('/^(00971)(\d{9})/iD', $id, $matches, PREG_SET_ORDER, 0)) {
                    $id = substr($id, 5);
                } elseif (preg_match_all('/^(\+971)(\d{9})/iD', $id, $matches, PREG_SET_ORDER, 0)) {
                    $id = substr($id, 4);
                } elseif (preg_match_all('/^(0)(\d{9})/iD', $id, $matches, PREG_SET_ORDER, 0)) {
                    $id = substr($id, 1);
                }

                # When customer opts SMS channel to retrieve Password/CustomerID
                $sql = "SELECT * FROM CustomerInformation WHERE CustomerPhone = '$id'";
                if ($db->NumberRow($sql) > 0) {
                    # Successful match found. Preparing to send CustomerID/Password to user
                    $row      = $db->GetRow($sql);
                    $userID   = $row['CustomerID'];
                    $password = $row['CustomerPassword'];
                    $mobile   = $id; # $row['CustomerPhone']

                    switch ($type) {
                        case 'userid':
                            # Sending Customer ID to customer on Registered Mobile Number
                            $message = "Your CustomerID is " . $userID;
                            $url     = $fn->smsURL($mobile, $message);
                            $res     = $fn->openURL($url);

                            $response = array(
                                'ResponseCode' => '200',
                                'ResponseMsg'  => 'Customer id sent to your mobile number',
                            );
                            break;

                        case 'password':
                            # Sending Password to customer on Registered Mobile Number
                            $message = "Your password is " . $password;
                            $url     = $fn->smsURL($mobile, $message);
                            $res     = $fn->openURL($url);

                            $response = array(
                                'ResponseCode' => '200',
                                'ResponseMsg'  => 'We have sent your Password on your registered mobile number.',
                            );
                            break;

                        default:
                            # Unidentified/Incorrect Request Type
                            $response = array(
                                'ResponseCode' => '201',
                                'ResponseMsg'  => 'Incorrect request Type. You can either request for CustomerID or Password.',
                            );
                            break;
                    }
                } else {
                    # Does not match
                    $response = array(
                        'ResponseCode' => '201',
                        'ResponseMsg'  => 'Your mobile number does not match with registered mobile number.',
                    );
                }
                break;

            case 'email':
                # When customer opts Email channel to retrieve Password/CustomerID
                $sql = "SELECT * FROM CustomerInformation WHERE CustomerEmail = '$id'";
                if ($db->NumberRow($sql) > 0) {
                    # Successful match found. Preparing to send CustomerID/Password to user
                    $row      = $db->GetRow($sql);
                    $userID   = $row['CustomerID'];
                    $password = $row['CustomerPassword'];
                    $email    = $id; # $row['CustomerEmail']
                    $username = $row['CustomerFirstName'] . " " . $row['CustomerLastName'];

                    switch ($type) {
                        case 'userid':
                            # Sending Customer ID to customer on Registered Email ID
                            $message = "Your CustomerID is " . $userID;
                            $data    = array(
                                'Email'   => $email,
                                'Name'    => $username,
                                'Subject' => 'MySmartPro: Recovery email for forgot Customer ID',
                                'Message' => $message,
                            );

                            if ($fn->sendMailer($data)) {
                                $response = array(
                                    'ResponseCode' => '200',
                                    'ResponseMsg'  => 'We have sent your Password on your registered email ID.',
                                );
                            } else {
                                $response = array(
                                    'ResponseCode' => '201',
                                    'ResponseMsg'  => 'We are unable to process your request at the moment. Please try again later.',
                                );
                            }

                            break;

                        case 'password':
                            # Sending Password to customer on Registered Email ID
                            $message = "Your password is " . $password;
                            $data    = array(
                                'Email'   => $email,
                                'Name'    => $username,
                                'Subject' => 'MySmartPro: Recovery email for forgot Password',
                                'Message' => $message,
                            );

                            if ($fn->sendMailer($data)) {
                                $response = array(
                                    'ResponseCode' => '200',
                                    'ResponseMsg'  => 'We have sent your Password on your registered email ID.',
                                );
                            } else {
                                $response = array(
                                    'ResponseCode' => '201',
                                    'ResponseMsg'  => 'We are unable to process your request at the moment. Please try again later.',
                                );
                            }

                            break;

                        default:
                            # Unidentified/Incorrect Request Type
                            $response = array(
                                'ResponseCode' => '201',
                                'ResponseMsg'  => 'Incorrect request Type. You can either request for CustomerID or Password.',
                            );
                            break;
                    }
                } else {
                    # Does not match
                    $response = array(
                        'ResponseCode' => '201',
                        'ResponseMsg'  => 'Your email does not match with registered email id.',
                    );
                }
                break;

            default:
                # Incorrect/Unidentified Com Channel
                $response = array(
                    'ResponseCode' => '201',
                    'ResponseMsg'  => 'Incorrect communication channel. Please select a valid communication mode either sms or email.',
                );
                break;
        }
    } # End Forgot Password or UserID/CustomerID

    elseif (isset($_REQUEST['changepass'])) {
        # Request to Change Password
        $jsonStr = stripslashes(trim($_REQUEST['changepass']));

        # Decode JSON => Assoc Array
        $assocArray = json_decode($jsonStr, true);

        # Extract Assoc Array => Values
        $userID      = $assocArray['UserID'];
        $oldPassword = $assocArray['OldPassword'];
        $newPassword = $assocArray['NewPassword'];

        # Checking user existence
        $sql = "SELECT * FROM CustomerInformation WHERE CustomerID = '$userID' AND CustomerPassword = '$oldPassword'";

        if ($db->NumberRow($sql)) {
            $today         = date('Y-m-d H:i:s');
            $update_result = $db->QueryDML("UPDATE CustomerInformation SET CustomerPassword = '$newPassword', CustomerPasswordOld = '$oldPassword', CustomerPasswordDate = '$today' WHERE CustomerID = '$userID'");

            if ($update_result['Success']) {
                $response = array(
                    'ResponseCode' => '200',
                    'ResponseMsg'  => 'Success! Your new password has been set successfully.',
                );
            } else {
                $response = array(
                    'ResponseCode' => '201',
                    'ResponseMsg'  => 'Something went wrong while updating your password. Try again later.',
                );
            }
        } else {
            $response = array(
                'ResponseCode' => '201',
                'ResponseMsg'  => 'Incorrect old Password.',
            );
        }
    } # End Change Password

    # Register FCM ID
    elseif (isset($_REQUEST['reg-fcm'])) {

        $jsonStr = stripslashes(trim($_REQUEST['reg-fcm']));

        # Decode JSON => Assoc Array
        $assocArray = json_decode($jsonStr, true);

        # Extract Assoc Array => Values
        $userID = $assocArray['UserID'];
        $FCMID  = $assocArray['FCMID'];

        # Checking user existence
        $sql = "SELECT * FROM CustomerInformation WHERE CustomerID = '$userID'";

        if ($db->NumberRow($sql)) {
            $update_result = $db->QueryDML("UPDATE CustomerInformation SET FCMID = '$FCMID' WHERE CustomerID = '$userID'");

            if ($update_result['Success']) {
                $response = array(
                    'ResponseCode' => '200',
                    'ResponseMsg'  => 'Success! FCMID updated successfully.',
                );
            } else {
                $response = array(
                    'ResponseCode' => '201',
                    'ResponseMsg'  => 'Something went wrong while updating FCMID. Try again later.',
                );
            }
        } else {
            $response = array(
                'ResponseCode' => '201',
                'ResponseMsg'  => 'Incorrect customer ID or customer does not exist.',
            );
        }
    } # End Register FCM ID

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
