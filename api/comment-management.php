<?php
header('Content-type: application/json');
header('Access-Control-Allow-Origin: *');
require_once '../config/database.php';
require_once '../config/functions.php';
$db       = new Database();
$fn       = new GenFunctions();
$response = array();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    if (isset($_REQUEST['fetch'])) {
        # Get Comments Related to a Project
        $jsonStr = stripslashes(trim($_REQUEST['fetch']));

        # Decode JSON => Assoc Array
        $assocArray = json_decode($jsonStr, true);

        # Extract Assoc Array => Values
        $projectID = $assocArray['ProjectID'];

        # Preparing Query
        $result   = $db->GetResults("SELECT * FROM CustomerComments WHERE ProjectID = '$projectID' ORDER BY CommentDate DESC");
        $comments = array();

        if ($result) {
            foreach ($result as $row) {
                $commentdate    = json_decode(json_encode($row['CommentDate']), true);
                $readComments   = 0;
                $unreadComments = 0;
                $commentID      = $row['CommentLineID'];
                $userID         = $row['CustomerID'];
                $companyId      = $row['CompanyID'];

                $commentResult = $db->GetResults("SELECT * FROM ReplyForCustomerComments WHERE CommentID = '$commentID'");
                foreach ($commentResult as $commentRow) {
                    if ($commentRow['IsCustomercheck'] == 0) {
                        ++$unreadComments;
                    }
                    if ($commentRow['IsCustomercheck'] == 1) {
                        ++$readComments;
                    }
                }

                $comment_sql = "SELECT TOP 1 * FROM ReplyForCustomerComments WHERE CommentID = '$commentID' ORDER BY ReplyDate DESC";

                if ($db->NumberRow($comment_sql) > 0) {
                    $comment_result = $db->GetRow($comment_sql);
                    $reply_from     = $comment_result['Replyfrom'];

                    if ($reply_from == 'Customer') {
                        # Customer Photo
                        $customer_sql = "SELECT CustomerPhoto FROM CustomerInformation WHERE CustomerID = '$userID'";
                        $customer_row = $db->GetRow($customer_sql);
                        $photo        = $customer_row['CustomerPhoto'];
                    }

                    if ($reply_from == 'Employee') {
                        # Company Logo
                        $company_sql = "SELECT LogoImg FROM Csstable WHERE CompanyID = '$companyId'";
                        $company_row = $db->GetRow($company_sql);
                        $photo       = $company_row['LogoImg'];
                    }
                } else {
                    # Customer Photo
                    $customer_sql = "SELECT CustomerPhoto FROM CustomerInformation WHERE CustomerID = '$userID'";
                    $customer_row = $db->GetRow($customer_sql);
                    $photo        = $customer_row['CustomerPhoto'];
                }

                $comment = array(
                    'CommentID'      => $row['CommentLineID'],
                    'Comment'        => $row['Comment'],
                    'CommentDate'    => $db->HRDate($commentdate['date']),
                    'ReadComments'   => $readComments,
                    'UnReadComments' => $unreadComments,
                    'Status'         => $row['CommentStatus'],
                    'Photo'          => $photo,
                );

                array_push($comments, $comment);
            }
            unset($row);

            $response = array(
                'ResponseCode' => '200',
                'ResponseMsg'  => 'Success',
                'ProjectID'    => $projectID,
                'Comments'     => $comments,
            );
        } else {
            $response = array(
                'ResponseCode' => '200',
                'ResponseMsg'  => 'There are not any comment for this project.',
            );
        }
    }

    # Fetch Complete Comment Trail Related To CommentLineID of CustomerComments Table from ReplyForCustomerComments Table
    elseif (isset($_REQUEST['fetch-all'])) {
        $jsonStr = stripslashes(trim($_REQUEST['fetch-all']));

        # Decode JSON => Assoc Array
        $assocArray = json_decode($jsonStr, true);

        # Extract Assoc Array => Values
        $commentID = $assocArray['CommentID'];

        # Preparing Query
        $comment_sql = "SELECT * FROM CustomerComments WHERE CommentLineID = '$commentID'";
        $comment_row = $db->GetRow($comment_sql);

        $comment_date = json_decode(json_encode($comment_row['CommentDate']), true);

        $userID    = $comment_row['CustomerID'];
        $companyId = $comment_row['CompanyID'];

        # Customer Photo
        $customer_sql   = "SELECT CustomerPhoto FROM CustomerInformation WHERE CustomerID = '$userID'";
        $customer_row   = $db->GetRow($customer_sql);
        $customer_photo = $customer_row['CustomerPhoto'];

        # Company Logo
        $company_sql  = "SELECT LogoImg FROM Csstable WHERE CompanyID = '$companyId'";
        $company_row  = $db->GetRow($company_sql);
        $company_logo = $company_row['LogoImg'];

        # Preparing SQL
        $result = $db->GetResults("SELECT * FROM ReplyForCustomerComments WHERE CommentID = '$commentID' ORDER BY ReplyDate ASC");

        $cus_comment = array(
            'CommentID'     => $comment_row['CommentLineID'],
            'ReplyID'       => 'na',
            'ResponseID'    => 'na',
            'Comment'       => $comment_row['Comment'],
            'ReplyDate'     => $db->HRDate($comment_date['date']),
            'ReplyFrom'     => 'Customer',
            'Status'        => $comment_row['CommentStatus'],
            'Url'           => $comment_row['UploadFileUrl'],
            'CustomerPhoto' => $customer_photo,
            'CompanyLogo'   => $company_logo,
        );

        $comments = array();
        array_push($comments, $cus_comment);

        if ($result) {
            foreach ($result as $row) {
                $replydate = json_decode(json_encode($row['ReplyDate']), true);

                $comment = array(
                    'CommentID'     => $row['CommentID'],
                    'ReplyID'       => $row['ReplyID'],
                    'ResponseID'    => $row['ResponseId'],
                    'Comment'       => $row['Reply'],
                    'ReplyDate'     => $db->HRDate($replydate['date']),
                    'ReplyFrom'     => $row['Replyfrom'],
                    'Status'        => $row['status'],
                    'Url'           => $row['Url'],
                    'CustomerPhoto' => $customer_photo,
                    'CompanyLogo'   => $company_logo,
                );

                array_push($comments, $comment);
            }
            unset($row);

            $update         = "UPDATE ReplyForCustomerComments SET IsCustomercheck = 1 WHERE CommentID = '$commentID'";
            $updateResponse = $db->QueryDML($update);
            $update_success = $updateResponse['Success'];

            if ($update_success) {
                $response = array(
                    'ResponseCode' => '200',
                    'ResponseMsg'  => 'Success',
                    'Comments'     => $comments,
                );
            } else {
                $response = array(
                    'ResponseCode' => '200',
                    'ResponseMsg'  => 'Error, could not update comment status.',
                    'Comments'     => $comments,
                );
            }
        } else {
            $response = array(
                'ResponseCode' => '200',
                'ResponseMsg'  => 'You have not received any reply from employee yet.',
                'Comments'     => $comments,
            );
        }
    } elseif (isset($_REQUEST['close'])) {
        $jsonStr = stripslashes(trim($_REQUEST['close']));

        # Decode JSON => Assoc Array
        $assocArray = json_decode($jsonStr, true);

        # Extract Assoc Array => Values
        $userID    = $assocArray['UserID'];
        $commentID = $assocArray['CommentID'];
        $rating    = $assocArray['Rating'];
        $status    = 'Close';

        # Update Comment Status in CustomerComments and ReplyForCustomerComments table
        $updateCC  = "UPDATE CustomerComments SET CommentStatus = '$status', CommentRating = '$rating' WHERE CommentLineID = '$commentID' AND CustomerID = '$userID'";
        $updateRCC = "UPDATE ReplyForCustomerComments SET status = '$status' WHERE CommentID = '$commentID'";
        $resultCC  = $db->QueryDML($updateCC);
        $resultRCC = $db->QueryDML($updateRCC);

        if ($resultCC && $resultRCC) {
            $response = array(
                'ResponseCode' => '200',
                'ResponseMsg'  => 'Comment status updated successfully',
            );
        } else {
            $response = array(
                'ResponseCode' => '201',
                'ResponseMsg'  => 'Something went wrong while updating comment status. Try again later.',
            );
        }
    } elseif (isset($_REQUEST['getall'])) {
        $jsonStr = stripslashes(trim($_REQUEST['getall']));

        # Decode JSON => Assoc Array
        $assocArray = json_decode($jsonStr, true);

        # Extract Assoc Array => Values
        $flag = $assocArray['Truthy'];

        # Preparing SQL
        $sql    = "SELECT * FROM CustomerInformation";
        $result = $db->Query($sql);
        $r      = $db->GetResults($sql);
        echo "<pre>";
        var_dump($r);
        echo "<pre>";

        /*$alter = "ALTER TABLE [CustomerInformation] ALTER COLUMN [FCMID] [nvarchar](255)";
        $res = $db->Query($alter);
        if ($res) {
        echo "success";
        } else {
        echo "fail";
        }*/

        if ($result) {
            $comments = array();
            while ($row = $db->GetRows($result)) {
                $comments[] = $row;
            }

            $response = array(
                'ResponseCode' => '200',
                'ResponseMsg'  => 'Success',
                'Comments'     => $comments,
            );
        } else {
            $response = array(
                'ResponseCode' => '200',
                'ResponseMsg'  => 'You have not received any reply from employee yet.',
            );
        }
    } elseif (isset($_POST['Action'])) {
        # elseif ( isset($_FILE['Attach']['tmp_name']) ) {
        # Add New Comment in CustomerComments Table
        $uploadDir = "attachments/";
        if (strtolower($_POST['Action']) == 'newcc') {
            # Extract Data (Strings)
            $projectID  = $_POST['ProjectID'];
            $customerID = $_POST['CustomerID'];
            $comment    = $_POST['Comment'];
            # $commentType = $_POST['CommentType'];
            $date = date('d/m/Y');
            $date = $db->SqlDateFormat($date);

            # Extract File
            $pathinfo = pathinfo($_FILES['Attach']['name']);
            $filename = $pathinfo['filename'];
            $ext      = $pathinfo['extension'];

            # Fetch Customer Info From CustomerInformation Table
            $custSql      = "SELECT CompanyID, DivisionID, DepartmentID FROM CustomerInformation WHERE CustomerID = '$customerID'";
            $custResult   = $db->GetRow($custSql);
            $companyID    = $custResult['CompanyID'];
            $divisionID   = $custResult['DivisionID'];
            $departmentID = $custResult['DepartmentID'];

            # Prepare Values
            $date        = $db->SqlDateFormat($date);
            $newfilename = uniqid() . "_" . $companyID . "_" . $filename . "." . $ext;
            $destination = $uploadDir . $newfilename;
            if (move_uploaded_file($_FILE['Attach']['tmp_name'], $destination)) {
                $uploadpath = $db->getBaseUrl() . $destination;
            } else {
                $uploadpath = null;
            }

            # Prepare Insert Statement
            $table  = 'CustomerComments';
            $fields = array('CompanyID', 'DivisionID', 'DepartmentID', 'CustomerID', 'CommentDate', 'CommentType', 'Comment', 'ProjectID', 'UploadFileUrl');
            $values = array("$companyID", "$divisionID", "$departmentID", "$customerID", "$date", "$commentType", "$comment", "$projectID", "$uploadpath");

            $id = $db->Insert($table, $fields, $values);

            if ($id >= 0) {
                $response = array(
                    'ResponseCode' => '200',
                    'ResponseMsg'  => 'Comment posted successfully.',
                );
            } else {
                $response = array(
                    'ResponseCode' => '201',
                    'ResponseMsg'  => 'Something went wrong while posting your comment.',
                );
            }
        } # End Add New Comment in CustomerComments Table

        # Add New Comment in ReplyForCustomerComments Table
        if (strtolower($_POST['Action']) == 'newrcc') {
            # Extract Data (Strings)
            $commentID = $_POST['CommentID'];
            $comment   = $_POST['Comment'];
            $date      = date('d/m/Y');
            $date      = $db->SqlDateFormat($date);

            # Extract File
            $pathinfo = pathinfo($_FILES['Attach']['name']);
            $filename = $pathinfo['filename'];
            $ext      = $pathinfo['extension'];

            # Prepare Values
            $date        = $db->SqlDateFormat($date);
            $newfilename = uniqid() . "_" . $commentID . "_" . $filename . "." . $ext;
            $destination = $uploadDir . $newfilename;
            if (move_uploaded_file($_FILE['Attach']['tmp_name'], $destination)) {
                $uploadpath = $db->getBaseUrl() . $destination;
            } else {
                $uploadpath = null;
            }

            # Prepare Insert Statement
            $table  = 'CustomerComments';
            $fields = array('CompanyID', 'DivisionID', 'DepartmentID', 'CustomerID', 'CommentDate', 'CommentType', 'Comment', 'ProjectID', 'UploadFileUrl');
            $values = array("$companyID", "$divisionID", "$departmentID", "$customerID", "$date", "$commentType", "$comment", "$projectID", "$uploadpath");

            $id = $db->Insert($table, $fields, $values);

            if ($id >= 0) {
                # Preparing SQL
                $sql    = "SELECT * FROM ReplyForCustomerComments WHERE CommentID = '$commentID'";
                $result = $db->Query($sql);

                if ($result) {
                    $comments = array();
                    while ($row = $db->GetRows($result)) {
                        $replydate = json_decode(json_encode($row['ReplyDate']), true);

                        $comment = array(
                            'CommentID'  => $row['CommentID'],
                            'ReplyID'    => $row['ReplyID'],
                            'ResponseID' => $row['ResponseId'],
                            'Comment'    => $row['Reply'],
                            'ReplyDate'  => $replydate['date'],
                            'ReplyFrom'  => $row['Replyfrom'],
                            'Url'        => $row['Url'],
                        );

                        array_push($comments, $comment);
                    }

                    $response = array(
                        'ResponseCode' => '200',
                        'ResponseMsg'  => 'Success',
                        'Comments'     => $comments,
                    );

                } else {
                    $response = array(
                        'ResponseCode' => '201',
                        'ResponseMsg'  => 'Something went wrong while posting your comment.',
                    );
                }
            } # End Add New Comment in ReplyForCustomerComments Table
        } else {
            $response = array(
                'ResponseCode' => '201',
                'ResponseMsg'  => 'Unidentified json string. Please send a valid json string.',
            );
        }
    }
} else {
    $response = array(
        'ResponseCode' => '201',
        'ResponseMsg'  => 'Incorrect request method. Please send data via POST.',
    );
}

echo json_encode($response);
