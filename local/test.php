<?php
    # Sending Raw JSON Data via HTTP Header - Content Type 'application/x-www-form-urlencoded'
    $apiUrl = "http://webprojects.work/smartpro/api/project-management.php";

/*    $data_array = array(
      'UserID' => '0565779237',
      'ProjectID' => '5001001',
    );*/

/*    $data_array = array(
      'Truthy' => 'TRUE',
    );*/

/*    $data_array = array(
      'Type' => 'password',
      'Channel' => 'email',
      'ID' => 'Sudarsan@gmail.com'
    );*/

    $data_array = array(
      'ProjectID' => '5001001',
    );

/*    $data_array = array(
      'CommentID' => '1',
    );*/

/*    $data_array = array(
      'ProjectID' => '5001001',
    );*/

/*    $data_array = array(
      'ReportID' => 'cd03a322_S',
    );*/

/*    $data_array = array(
      'ProjectID' => '5001001',
      'ReqType' => 'All'
    );*/

/*    $data_array = array(
      'DocumentAttachID' => '101',
      'Status' => 'NotApprove',
      'Feedback' => 'NULL',
    );*/

/*    $data_array = array(
      'ProjectID' => '5001001',
      'ReqType' => 'Unapproved',
    );*/

/*    $data_array = array(
      'ProjectID' => '5001001',
    );*/

/*    $data_array = array(
      'UserID' => '0565779237',
      'OldPassword' => 'Sudarsan123',
      'NewPassword' => 'Sudarsan',
    );*/

/*    $data_array = array(
      'UserID' => '0565779237',
      'ProjectID' => '5001001',
    );*/

/*    $data_array = array(
      'UserID' => '0565779237',
      'CompanyID' => 'AL1632',
      'Password' => 'Sudarsan'
    );*/

    $data = "milestonedetail=".urlencode(json_encode($data_array));

    function curl($dataStr, $url) {
      $options = array(
        CURLOPT_POST => TRUE,
        CURLOPT_HTTPHEADER => array('Content-Type: application/x-www-form-urlencoded'),
        CURLOPT_POSTFIELDS => $dataStr,
        CURLOPT_HEADER => 1,
        CURLINFO_HEADER_OUT => true,
      );
      $ch = curl_init($url);
      curl_setopt_array($ch, $options);
      $response = curl_exec($ch);
      curl_close($ch);

      return $response;
    }

    $result = curl($data, $apiUrl);

    var_dump($result);
?>
