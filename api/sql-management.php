<?php
  header('Content-type: application/json');
  header('Access-Control-Allow-Origin: *');
  require_once('../config/database.php');
  require_once('../config/functions.php');
  $db = new Database();
  $fn = new GenFunctions();
  $response = array();

  if ( $_SERVER['REQUEST_METHOD'] == 'POST' ) {

    # Run SQL
    if ( isset($_REQUEST['run-sql']) ) {
      $sql = file_get_contents('./sql.txt');

      $sql_run = $db->Query($sql);

      if ($sql_run === false) {
        $response = array('ResponseCode' => '201', 'ResponseMsg' => 'Cannot run sql', 'Errors' => $db->LastError());
      } else {
        $response = array('ResponseCode' => '200', 'ResponseMsg' => 'SQL run successful');
      }

    } # End Run SQL

  } # End Request Method Check

  echo json_encode($response);
?>
