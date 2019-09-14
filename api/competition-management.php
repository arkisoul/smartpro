<?php
  header('Content-type: application/json');
  header('Access-Control-Allow-Origin: *');
  require_once('../config/database.php');
  require_once('../config/functions.php');
  $db = new Database();
  $fn = new GenFunctions();
  $response = array();

  if ( $_SERVER['REQUEST_METHOD'] == 'POST' ) {

    # Get Competition Types from Engineer Type Table
    if ( isset($_REQUEST['getCompetitionType']) ) {
      $results = $db->GetResults("SELECT EngineerTypeId, EngineerType FROM EngineerType");

      if ($results) {
        $response = array('ResponseCode' => '200', 'ResponseMsg' => 'Competition types', 'CompetitionTypes' => $results);
      } else {
        $response = array('ResponseCode' => '201', 'ResponseMsg' => 'No records found');
      }

    } # End Get Competition Types

    # Get all competition
    if ( isset($_REQUEST['getAllCompetition']) ) {
      # Fetch JSON string
      $jsonStr = stripcslashes( trim($_REQUEST['getAllCompetition']) );

      # Decode JSON string in Assoc Array
      $assocArray = json_decode($jsonStr, TRUE);

      # Retrieve values from Assoc Array
      $CustomerId = $assocArray['CustomerId'];

      $results =$db->GetResults("SELECT * FROM CompetitionTable WHERE CustomerId = '$CustomerId'");

      $competitions = array();

      foreach ($results as $key => $value) {
        $start_date = json_decode(json_encode($value['StartDate']), true);
        $end_date = json_decode(json_encode($value['EndDate']), true);
        $create_date = json_decode(json_encode($value['CreateDate']), true);
        $value['StartDate'] = $start_date['date'];
        $value['EndDate'] = $end_date['date'];
        $value['CreateDate'] = $create_date['date'];
        $competition = array(
          'Id' => $value['Id'],
          'CustomerId' => $value['CustomerId'],
          'CompetitionName' => $value['CompetitionName'],
          'CompetitionType' => $value['CompetitionType'],
          'Amount' => $value['Amount'],
          'SpecificationImage' => $value['SpecificationImage'],
          'StartDate' => $value['StartDate'],
          'EndDate' => $value['EndDate'],
          'CreateDate' => $value['CreateDate'],
        );
        array_push($competitions, $competition);
      }
      unset($key, $value);

      if ($results) {
        $response = array('ResponseCode' => '200', 'ResponseMsg' => 'Competition types', 'Competitions' => $competitions);
      } else {
        $response = array('ResponseCode' => '201', 'ResponseMsg' => 'No records found for this customer');
      }

    } # End get all competition

    # Delete a competition
    if ( isset($_REQUEST['deleteCompetition']) ) {
      # Fetch JSON string
      $jsonStr = stripcslashes( trim($_REQUEST['deleteCompetition']) );

      # Decode JSON string in Assoc Array
      $assocArray = json_decode($jsonStr, TRUE);

      # Retrieve values from Assoc Array
      $competitionId = $assocArray['CompetitionId'];

      # Delete record of requested competition Id
      $deleteCompetitionRecord = $db->QueryDML("DELETE FROM CompetitionTable WHERE Id = '$competitionId'");

      if ($deleteCompetitionRecord['Success']) {
        $response = array('ResponseCode' => '200', 'ResponseMsg' => 'Competition record deleted successfully.');
      } else {
        $response = array('ResponseCode' => '201', 'ResponseMsg' => 'Unable to delete requested competition record. Try again later');
      }

    } # End Delete Competition

  } # End Request Method Check if

  # if Request Method is not POST
  else {
    $response = array('ResponseCode' => '201', 'ResponseMsg' => 'Incorrect request method.');
  }

  echo json_encode($response);
?>
