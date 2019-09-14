<?php
header('Content-type: application/json');
header('Access-Control-Allow-Origin: *');
require_once '../config/database.php';
require_once '../config/functions.php';
$db       = new Database();
$fn       = new GenFunctions();
$response = array();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    # Get All Property Type Names
    if (isset($_REQUEST['get-all-property-types'])) {
        # Fetch all Property Types From Table ** PropertyType **
        $result = $db->GetResults("SELECT Id as PropertyTypeId, PropertyTypeName, Descriptions FROM PropertyType");

        if ($result) {
            $response = array(
                'ResponseCode'  => '200',
                'ResponseMsg'   => 'All property types',
                'PropertyTypes' => $result,
            );
        } else {
            $response = array(
                'ResponseCode' => '200',
                'ResponseMsg'  => 'No property types',
            );
        }
    }

    # Fetch a Property Type by property type id
    elseif (isset($_REQUEST['get-property-type-by-id'])) {
        $jsonStr = stripslashes(trim($_REQUEST['get-property-type-by-id']));

        # Decode JSON => Assoc Array
        $assocArray = json_decode($jsonStr, true);

        # Extract Assoc Array => Values
        $propertyTypeId = $assocArray['PropertyTypeId'];

        # Fetch Property Type by Property Type Id from Table ** PropertyType **
        $row = $db->GetRow("SELECT Id, PropertyTypeName, Descriptions FROM PropertyType WHERE Id = '$propertyTypeId'");

        if ($row) {
            $response = array(
                'ResponseCode'     => '200',
                'ResponseMsg'      => 'Requested property type',
                'PropertyTypeId'   => $row['Id'],
                'PropertyTypeName' => $row['PropertyTypeName'],
                'Description'      => $row['Descriptions'],
            );
        } else {
            $response = array(
                'ResponseCode' => '201',
                'ResponseMsg'  => 'Error! Incorrect property type id',
            );
        }
    } elseif (isset($_REQUEST['add-property-request'])) {
        $jsonStr = stripslashes(trim($_REQUEST['add-property-request']));

        # Decode JSON => Assoc Array
        $assocArray = json_decode($jsonStr, true);

        # Extract Assoc Array => Values
        $userId     = $assocArray['UserId'];
        $propertyId = $assocArray['PropertyId'];

        $table  = 'PropertyInterestList';
        $fields = array('PropertyId', 'UserId');
        $values = array($propertyId, $userId);

        $id = $db->Insert($table, $fields, $values);

        if ($id) {
            $response = array(
                'ResponseCode' => '200',
                'ResponseMsg'  => 'Request submitted successfully',
            );
        } else {
            $response = array(
                'ResponseCode' => '201',
                'ResponseMsg'  => 'Error! an error occured during submitting request. Try again later');
        }
    }

    # Delete a Property record from Table ** PropertyList **
    elseif (isset($_REQUEST['delete-property'])) {
        $jsonStr = stripslashes(trim($_REQUEST['delete-property']));

        # Decode JSON => Assoc Array
        $assocArray = json_decode($jsonStr, true);

        # Extract Assoc Array => Values
        $propertyId = $assocArray['PropertyId'];

        $delete = $db->QueryDML("DELETE FROM PropertyList WHERE Id = '$propertyId'");

        if ($delete['Success']) {
            $response = array(
                'ResponseCode' => '200',
                'ResponseMsg'  => 'Requested property deleted successfully');
        } else {
            $response = array(
                'ResponseCode' => '201',
                'ResponseMsg'  => 'Error! an error occured while deleting requested property, try again later.',
            );
        }
    }

    # Get all properties of a User from Table ** PropertyList **
    elseif (isset($_REQUEST['get-properties-user'])) {
        $jsonStr = stripslashes(trim($_REQUEST['get-properties-user']));

        # Decode JSON => Assoc Array
        $assocArray = json_decode($jsonStr, true);

        # Extract Assoc Array => Values
        $userId = $assocArray['UserId'];

        # Fetch data
        $properties = $db->GetResults("SELECT PL.*, PT.ProjectTypeID AS ProjectTypeName FROM PropertyList PL LEFT JOIN ProjectTypes PT ON PT.ID = PL.PropertyTypeId WHERE PL.UserId = $userId");

        if ($properties) {
            $response = array(
                'ResponseCode' => '200',
                'ResponseMsg'  => 'Properties list for the user',
                'Properties'   => $properties,
            );
        } else {
            $response = array(
                'ResponseCode' => '201',
                'ResponseMsg'  => 'No property associated with this user',
            );
        }
    }

    # Get all properties which does not belong to a User from Table ** PropertyList **
    elseif (isset($_REQUEST['get-properties-not-user'])) {
        $jsonStr = stripslashes(trim($_REQUEST['get-properties-not-user']));

        # Decode JSON => Assoc Array
        $assocArray = json_decode($jsonStr, true);

        # Extract Assoc Array => Values
        $userId = $assocArray['UserId'];

        # Fetch data
        $properties = $db->GetResults("SELECT PL.*, PT.ProjectTypeID AS ProjectTypeName FROM PropertyList PL LEFT JOIN ProjectTypes PT ON PT.ID = PL.PropertyTypeId WHERE PL.UserId <> $userId");

        if ($properties) {
            $response = array(
                'ResponseCode' => '200',
                'ResponseMsg'  => 'Properties list',
                'Properties'   => $properties,
            );
        } else {
            $response = array(
                'ResponseCode' => '201',
                'ResponseMsg'  => 'No property found',
            );
        }
    } else {
        $response = array(
            'ResponseCode' => '201',
            'ResponseMsg'  => 'Unidentified json string. Please send a valid json string.',
        );
    }
} else {
    $response = array(
        'ResponseCode' => '201',
        'ResponseMsg'  => 'Incorrect request method. Please send data via POST.',
    );
}

echo json_encode($response);
