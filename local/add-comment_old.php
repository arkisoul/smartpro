<?php
  header('Content-type: application/json');
  header('Access-Control-Allow-Origin: *');
  require_once('../config/database.php');
  require_once('../config/functions.php');
  $db = new Database();
  $fn = new GenFunctions();
  $response = array();
  ini_set('display_errors',1);
  error_reporting(E_ALL);

  $action = isset($_POST['Action']) ? strtolower($_POST['Action']) : null;
  $uploadDir = "attachments/";

  if ( $action == 'newcc' ) {
    # Extract Data (Strings)
    $projectID = $_POST['ProjectID'];
    $customerID = $_POST['CustomerID'];
    $comment = $_POST['Comment'];
    $date = $_POST['Date'];
    $source = $_POST['Source']; # if:gen then add comment in respect to ProjectID only else: add comment in respect to ProjectID and populate ReportID field with ReportID
    $date = $db->SqlDateFormat($date);

    # Convert Comment in UTF-8
    $comment = $fn->utf16_to_utf8($comment);

    # Extract File if Exist else set it to NULL
    if ( isset($_FILES['Attach']['tmp_name']) ) {
      $pathinfo = pathinfo($_FILES['Attach']['name']);
      $filename = $pathinfo['filename'];
      $ext = $pathinfo['extension'];

      # Fetch Customer Info From CustomerInformation Table
      $custSql = "SELECT CompanyID, DivisionID, DepartmentID FROM CustomerInformation WHERE CustomerID = '$customerID'";
      $custResult = $db->GetRow($custSql);
      $divisionID = $custResult['DivisionID'];
      $departmentID = $custResult['DepartmentID'];

      # Fetch Company ID from ProjectMapping table based on Project ID
      $project_result = $db->GetRow("SELECT * FROM ProjectMapping WHERE CustomerID = '$customerID' AND ProjectId = '$projectID'");
      $companyID = $project_result['CompanyId'];

      # Prepare Values
      $realpath = realpath('../attachments/');
      $newfilename = uniqid(). "_" . $companyID. "_" . $filename. "." . $ext;
      $destination = $uploadDir . $newfilename;
      $realdestination = $realpath . "/" . $newfilename;
      $tmp_name = $_FILES['Attach']['tmp_name'];
      // $uploadStatus = move_uploaded_file($tmp_name, $realdestination);
      $uploadStatus = false;
      if ( $uploadStatus ) {
        $uploadpath = $db->getBaseUrl() . $destination;
      } else {
        $uploadpath = 'NULL';
        # echo "Failed to upload image";
      }
    } else {
      $uploadpath = 'NULL';
      # echo "Image not set";
    }

    # Prepare Insert Statement
    $table = 'CustomerComments';
    $fields = array('CompanyID', 'DivisionID', 'DepartmentID', 'CustomerID', 'CommentDate', 'CommentType', 'Comment', 'ProjectID', 'UploadFileUrl');
    $values = array("$companyID", "$divisionID", "$departmentID", "$customerID", "$date", "$commentType", "$comment", "$projectID", "$uploadpath");

    if ( strtolower($source) == 'gen' ) {
      $id = $db->Insert($table, $fields, $values);
    } else {
      array_push($fields, 'ReportID');
      array_push($values, $source);
      $id = $db->Insert($table, $fields, $values);
    }

    if ( $id >= 0 ) {
      $response = array(
        'ResponseCode' => '200',
        'ResponseMsg' => 'Comment posted successfully.'
      );
    } else {
      $response = array(
        'ResponseCode' => '201',
        'ResponseMsg' => 'Something went wrong while posting your comment.'
      );
    }
  }

  # Add New Comment in ReplyForCustomerComments Table
  if ( $action == 'newrcc' ) {
    # Extract Data (Strings)
    $customerID = $_POST['UserID'];
    $commentID = $_POST['CommentID'];
    $comment = $_POST['Comment'];
    $date = $_POST['Date'];
    $date = $db->SqlDateFormat($date);

    # Convert Comment in UTF-8
    $comment = $fn->utf16_to_utf8($comment);

    # Extract File if Exist else set it to NULL
    if ( isset($_FILES['Attach']['tmp_name']) ) {
      $pathinfo = pathinfo($_FILES['Attach']['name']);
      $filename = $pathinfo['filename'];
      $ext = $pathinfo['extension'];

      # Prepare Values
      $realpath = realpath('../attachments/');
      $newfilename = uniqid(). "_" . $commentID. "_" . $filename. "." . $ext;
      $destination = $uploadDir . $newfilename;
      $realdestination = $realpath . "/" . $newfilename;
      $tmp_name = $_FILES['Attach']['tmp_name'];
      $uploadStatus = move_uploaded_file($tmp_name, $realdestination);
      // $uploadStatus = false;
      if ( $uploadStatus ) {
        $uploadpath = $db->getBaseUrl() . $destination;
      } else {
        $uploadpath = 'NULL';
        # echo "Failed to upload image".print_r($_FILES);
      }
    } else {
      $uploadpath = 'NULL';
      # echo "Image is not set";
    }

    # Fetch Customer Info From CustomerInformation Table
    $custSql = "SELECT CompanyID, DivisionID FROM CustomerInformation WHERE CustomerID = '$customerID'";
    $custResult = $db->GetRow($custSql);
    $companyID = $custResult['CompanyID'];
    $divisionID = $custResult['DivisionID'];

    # Some Default Values
    $replyFrom = 'Customer';
    $status = 'Open';
    $isCustomerCheck = 1;
    $isEmpCheck = 0;

    # Prepare Insert Statement
    $table = 'ReplyForCustomerComments';
    $fields = array('CompanyID', 'DivisionID', 'ResponseId', 'CommentID', 'Reply', 'ReplyDate', 'Replyfrom', 'status', 'IsCustomercheck', 'Isemployeecheck', 'Url');
    $values = array("$companyID", "$divisionID", "$customerID", "$commentID", "$comment", "$date", "$replyFrom", "$status", $isCustomerCheck, $isEmpCheck, "$uploadpath");

    $id = $db->Insert($table, $fields, $values);

    if ( $id >= 0 ) {
      # Preparing Query
      $comment_sql = "SELECT * FROM CustomerComments WHERE CommentLineID = '$commentID'";
      $comment_row = $db->GetRow($comment_sql);

      $comment_date = json_decode(json_encode($comment_row['CommentDate']), true);

      $userID = $comment_row['CustomerID'];
      $companyId = $comment_row['CompanyID'];

      # Customer Photo
      $customer_sql = "SELECT CustomerPhoto FROM CustomerInformation WHERE CustomerID = '$userID'";
      $customer_row = $db->GetRow($customer_sql);
      $customer_photo = $customer_row['CustomerPhoto'];

      # Company Logo
      $company_sql = "SELECT LogoImg FROM Csstable WHERE CompanyID = '$companyId'";
      $company_row = $db->GetRow($company_sql);
      $company_logo = $company_row['LogoImg'];

      # Preparing SQL
      $sql = "SELECT * FROM ReplyForCustomerComments WHERE CommentID = '$commentID' ORDER BY ReplyDate ASC";
      $result = $db->Query($sql);

      if ( $result ) {
        $cus_comment = array(
          'CommentID' => $comment_row['CommentLineID'],
          'ReplyID' => 'na',
          'ResponseID' => 'na',
          'Comment' => $comment_row['Comment'],
          'ReplyDate' => $comment_date['date'],
          'ReplyFrom' => 'Customer',
          'Status' => $comment_row['CommentStatus'],
          'Url' => $comment_row['UploadFileUrl'],
          'CustomerPhoto' => $customer_photo,
          'CompanyLogo' => $company_logo,
        );

        $comments = array();
        array_push($comments, $cus_comment);

        while ( $row = $db->GetRows($result) ) {
          $replydate = json_decode(json_encode($row['ReplyDate']), true);

          $comment = array(
            'CommentID' => $row['CommentID'],
            'ReplyID' => $row['ReplyID'],
            'ResponseID' => $row['ResponseId'],
            'Comment' => $row['Reply'],
            'ReplyDate' => $replydate['date'],
            'ReplyFrom' => $row['Replyfrom'],
            'Status' => $row['status'],
            'Url' => $row['Url'],
            'CustomerPhoto' => $customer_photo,
            'CompanyLogo' => $company_logo,
          );

          array_push($comments, $comment);
        }

        $response = array(
          'ResponseCode' => '200',
          'ResponseMsg' => 'Success',
          'Comments' => $comments
        );
      }
    } else {
      $response = array(
        'ResponseCode' => '201',
        'ResponseMsg' => 'Something went wrong while posting your comment.'
      );
    }
  } # End Add New Comment in ReplyForCustomerComments Table

  echo json_encode($response);

?>
