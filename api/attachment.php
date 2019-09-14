<?php
header('Content-type: application/json');
header('Access-Control-Allow-Origin: *');
require_once '../config/database.php';
require_once '../config/functions.php';
$db       = new Database();
$fn       = new GenFunctions();
$response = array();

$action    = isset($_POST['Action']) ? $_POST['Action'] : null;
$uploadDir = "Documents/";

# Project Attachment
if ($action == 'project-doc') {
    # Extract Data (Strings)
    $projectID = $_POST['ProjectID'];
    $companyID = $_POST['CompanyID'];

    # Extract File if Exist else set it to NULL
    if (isset($_FILES['Attach']['tmp_name'])) {
        $pathinfo = pathinfo($_FILES['Attach']['name']);
        $filename = $pathinfo['filename'];
        $ext      = $pathinfo['extension'];

        # Prepare Path - Check if it exist if not create a new one
        $realpath = realpath('../../Documents/');
        $realpath = $realpath . "/" . $companyID;
        if (!file_exists($realpath)) {
            mkdir($realpath, 0777, true);
        }

        $realpath = $realpath . "/" . $projectID;
        if (!file_exists($realpath)) {
            mkdir($realpath, 0777, true);
        }

        $realpath .= "/Projects Documents/";
        if (!file_exists($realpath)) {
            mkdir($realpath, 0777, true);
        }

        # Final Relative Path
        $uploadDir = $uploadDir . $companyID . "/" . $projectID . "/Projects Documents/";

        # Prepare Values
        $newfilename     = uniqid() . "_" . $companyID . "_" . $filename . "." . $ext;
        $destination     = $uploadDir . $newfilename;
        $realdestination = $realpath . $newfilename;
        $tmp_name        = $_FILES['Attach']['tmp_name'];
        $uploadStatus    = move_uploaded_file($tmp_name, $realdestination);
        if ($uploadStatus) {
            $uploadpath = $db->getBaseUrl() . $destination;
        } else {
            $uploadpath = 'NULL';
        }
    } else {
        $uploadpath = 'NULL';
    }

    $response = array(
        'ResponseCode' => '200',
        'ResponseMsg'  => 'Success',
        'Attachment'   => $uploadpath,
    );
} # End Project Document Attachment

# Add User Profile Picture
if ($action == 'user-image') {
    $companyID  = $_POST['CompanyID'];
    $customerID = $_POST['CustomerID'];

    # Extract File if Exist else set it to NULL
    if (isset($_FILES['Attach']['tmp_name'])) {
        $pathinfo = pathinfo($_FILES['Attach']['name']);
        $filename = $pathinfo['filename'];
        $ext      = $pathinfo['extension'];

        # Prepare Path - Check if it exist if not create a new one
        $realpath = realpath('../../Documents/');
        $realpath = $realpath . "/" . $companyID;
        if (!file_exists($realpath)) {
            mkdir($realpath, 0777, true);
        }

        # Final Relative Path
        $uploadDir .= $companyID . "/";

        # Preparing Query
        $user_info      = $db->GetRow("SELECT CustomerFirstName, CustomerPhoto FROM CustomerInformation WHERE CustomerID = '$customerID'");
        $user_name      = $user_info['CustomerFirstName'];
        $user_old_photo = $user_info['CustomerPhoto'];

        # Prepare Values
        $newfilename     = uniqid() . "_" . $companyID . "_" . $user_name . "." . $ext;
        $destination     = $uploadDir . $newfilename;
        $realdestination = $realpath . "/" . $newfilename;
        $tmp_name        = $_FILES['Attach']['tmp_name'];
        $uploadStatus    = move_uploaded_file($tmp_name, $realdestination);

        if ($uploadStatus) {
            $uploadpath      = $db->getBaseUrl() . $destination;
            $update_user_pic = $db->QueryDML("UPDATE CustomerInformation SET CustomerPhoto = '$uploadpath' WHERE  CustomerID = '$customerID'");
            $update_success  = $update_user_pic['Success'];

            if ($update_success) {
                # Unlink old document from the drive
                $oldfile_info = pathinfo($user_old_photo);
                $oldfile_name = $oldfile_info['basename'];
                unlink($realpath . "/" . $oldfile_name);

                $response = array(
                    'ResponseCode' => '200',
                    'ResponseMsg'  => 'Success',
                    'Attachment'   => $uploadpath,
                );
            } else {
                $response = array(
                    'ResponseCode' => '201',
                    'ResponseMsg'  => 'Failed to save customer image. Try again later.',
                    'Attachment'   => $user_old_photo,
                );
            }
        } else {
            $response = array(
                'ResponseCode' => '201',
                'ResponseMsg'  => 'Failed to save customer image. Try again later.',
                'Attachment'   => $user_old_photo,
            );
        }
    } else {
        $response = array(
            'ResponseCode' => '201',
            'ResponseMsg'  => 'Failed to save customer image. Try again later.',
            'Attachment'   => $user_old_photo,
        );
    }
} # End User Profile Picture

# Add attachment
if ($action == 'attachment') {
    $companyID = $_POST['CompanyID'];

    # Extract File if Exist else set it to NULL
    if (isset($_FILES['Attach']['tmp_name'])) {
        $pathinfo = pathinfo($_FILES['Attach']['name']);
        $filename = $pathinfo['filename'];
        $ext      = $pathinfo['extension'];

        # Prepare Path - Check if it exist if not create a new one
        $realpath = realpath('../../Documents/');
        $realpath = $realpath . "/" . $companyID;
        if (!file_exists($realpath)) {
            mkdir($realpath, 0777, true);
        }

        # Final Relative Path
        $uploadDir .= $companyID . "/";

        # Prepare Values
        $newfilename     = uniqid() . "_" . $companyID . "_" . $filename . "." . $ext;
        $destination     = $uploadDir . $newfilename;
        $realdestination = $realpath . "/" . $newfilename;
        $tmp_name        = $_FILES['Attach']['tmp_name'];
        $uploadStatus    = move_uploaded_file($tmp_name, $realdestination);

        if ($uploadStatus) {
            $uploadpath = $db->getBaseUrl() . $destination;
        } else {
            $uploadpath = 'NULL';
        }
    } else {
        $uploadpath = 'NULL';
    }
    $response = array(
        'ResponseCode' => '200',
        'ResponseMsg'  => 'Success',
        'Attachment'   => $uploadpath,
    );
} # End Attachment

# Add a new Property in Table ** PropertyList **
if ($action == 'add-property') {
    # Extract Values
    $userId         = $_POST['UserId'];
    $propertyTypeId = $_POST['PropertyTypeId'];
    $countryId      = $_POST['CountryId'];
    $stateId        = $_POST['StateId'];
    $countryName    = $_POST['CountryName'];
    $stateName      = $_POST['StateName'];
    $city           = $_POST['City'];
    $lat            = array_key_exists('Latitude', $_POST) ? $_POST['Latitude'] : null;
    $long           = array_key_exists('Longitude', $_POST) ? $_POST['Longitude'] : null;
    $address        = $_POST['Address'];
    $typeId         = $_POST['TypeId'];
    $type           = $_POST['Type'];
    $landSqrFt      = array_key_exists('LandSqrFt', $_POST) ? $_POST['LandSqrFt'] : null;
    $buildingSqrFt  = array_key_exists('BuildingSqrFt', $_POST) ? $_POST['BuildingSqrFt'] : null;
    $amount         = $_POST['Amount'];
    $createDate     = $db->SqlDateFormat("now");

    # Extract File if Exist else set it to NULL - Attachment 1
    if (isset($_FILES['Attach_1']['tmp_name'])) {
        $pathinfo = pathinfo($_FILES['Attach_1']['name']);
        $filename = $pathinfo['filename'];
        $ext      = $pathinfo['extension'];

        # Prepare Path - Check if it exist if not create a new one
        $realpath = realpath('../../Documents/');
        $realpath = $realpath . "/Property Documents";
        if (!file_exists($realpath)) {
            mkdir($realpath, 0777, true);
        }

        # Final Relative Path
        $uploadDir .= "Property Documents/";

        # Prepare Values
        $newfilename     = uniqid() . "_" . $filename . "." . $ext;
        $destination     = $uploadDir . $newfilename;
        $realdestination = $realpath . "/" . $newfilename;
        $tmp_name        = $_FILES['Attach_1']['tmp_name'];
        $uploadStatus    = move_uploaded_file($tmp_name, $realdestination);

        if ($uploadStatus) {
            $uploadpath_1 = $db->getBaseUrl() . $destination;
        } else {
            $uploadpath_1 = 'Failed to upload';
        }
    } else {
        $uploadpath_1 = 'NULL';
    }

    # Extract File if Exist else set it to NULL - Attachment 2
    if (isset($_FILES['Attach_2']['tmp_name'])) {
        $pathinfo = pathinfo($_FILES['Attach_2']['name']);
        $filename = $pathinfo['filename'];
        $ext      = $pathinfo['extension'];

        # Prepare Path - Check if it exist if not create a new one
        $realpath = realpath('../../Documents/');
        $realpath = $realpath . "/Property Documents";
        if (!file_exists($realpath)) {
            mkdir($realpath, 0777, true);
        }

        # Final Relative Path
        # $uploadDir .= "Property Documents/";

        # Prepare Values
        $newfilename     = uniqid() . "_" . $filename . "." . $ext;
        $destination     = $uploadDir . $newfilename;
        $realdestination = $realpath . "/" . $newfilename;
        $tmp_name        = $_FILES['Attach_2']['tmp_name'];
        $uploadStatus    = move_uploaded_file($tmp_name, $realdestination);

        if ($uploadStatus) {
            $uploadpath_2 = $db->getBaseUrl() . $destination;
        } else {
            $uploadpath_2 = 'Failed to upload';
        }
    } else {
        $uploadpath_2 = 'NULL';
    }

    # Extract File if Exist else set it to NULL - Attachment 3
    if (isset($_FILES['Attach_3']['tmp_name'])) {
        $pathinfo = pathinfo($_FILES['Attach_3']['name']);
        $filename = $pathinfo['filename'];
        $ext      = $pathinfo['extension'];

        # Prepare Path - Check if it exist if not create a new one
        $realpath = realpath('../../Documents/');
        $realpath = $realpath . "/Property Documents";
        if (!file_exists($realpath)) {
            mkdir($realpath, 0777, true);
        }

        # Final Relative Path
        # $uploadDir .= "Property Documents/";

        # Prepare Values
        $newfilename     = uniqid() . "_" . $filename . "." . $ext;
        $destination     = $uploadDir . $newfilename;
        $realdestination = $realpath . "/" . $newfilename;
        $tmp_name        = $_FILES['Attach_3']['tmp_name'];
        $uploadStatus    = move_uploaded_file($tmp_name, $realdestination);

        if ($uploadStatus) {
            $uploadpath_3 = $db->getBaseUrl() . $destination;
        } else {
            $uploadpath_3 = 'Failed to upload';
        }
    } else {
        $uploadpath_3 = 'NULL';
    }

    $table  = 'PropertyList';
    $fields = array('UserId', 'PropertyTypeId', 'CountryId', 'StateId', 'CountryName', 'StateName', 'City', 'Latitude', 'Longitude', 'Address', 'TypeId', 'Type', 'LandSqrFt', 'BuildingSqrFt', 'Amount', 'CreateDate', 'Attachment1', 'Attachment2', 'Attachment3');
    $values = array("$userId", "$propertyTypeId", "$countryId", "$stateId", "$countryName", "$stateName", "$city", "$lat", "$long", "$address", "$typeId", "$type", "$landSqrFt", "$buildingSqrFt", "$amount", "$createDate", "$uploadpath_1", "$uploadpath_2", "$uploadpath_3");

    $id = $db->Insert($table, $fields, $values);

    if ($id) {
        $response = array(
            'ResponseCode' => '200',
            'ResponseMsg'  => 'Property added successfully',
        );
    } else {
        $response = array(
            'ResponseCode' => '201',
            'ResponseMsg'  => 'Error! an error occurred while submitting new property, try again later.');
    }
}

# Update an existing Property in Table ** PropertyList **
if ($action == 'update-property') {
    # Extract Values
    $propertyId     = $_POST['PropertyId'];
    $userId         = $_POST['UserId'];
    $propertyTypeId = $_POST['PropertyTypeId'];
    $countryId      = $_POST['CountryId'];
    $stateId        = $_POST['StateId'];
    $countryName    = $_POST['CountryName'];
    $stateName      = $_POST['StateName'];
    $city           = $_POST['City'];
    $lat            = array_key_exists('Latitude', $_POST) ? $_POST['Latitude'] : null;
    $long           = array_key_exists('Longitude', $_POST) ? $_POST['Longitude'] : null;
    $address        = $_POST['Address'];
    $typeId         = $_POST['TypeId'];
    $type           = $_POST['Type'];
    $landSqrFt      = array_key_exists('LandSqrFt', $_POST) ? $_POST['LandSqrFt'] : null;
    $buildingSqrFt  = array_key_exists('BuildingSqrFt', $_POST) ? $_POST['BuildingSqrFt'] : null;
    $amount         = $_POST['Amount'];
    $createDate     = $db->SqlDateFormat("now");

    # Preparing Query to Extract old attachment values
    $property_list_info = $db->GetRow("SELECT Attachment1, Attachment2, Attachment3 FROM PropertyList WHERE Id = '$propertyId'");
    $oldAttachment_1    = $property_list_info['Attachment1'];
    $oldAttachment_2    = $property_list_info['Attachment2'];
    $oldAttachment_3    = $property_list_info['Attachment3'];

    # Extract File if Exist else set it to NULL - Attachment 1
    if (isset($_FILES['Attach_1']['tmp_name'])) {
        $pathinfo = pathinfo($_FILES['Attach_1']['name']);
        $filename = $pathinfo['filename'];
        $ext      = $pathinfo['extension'];

        # Prepare Path - Check if it exist if not create a new one
        $realpath = realpath('../../Documents/');
        $realpath = $realpath . "/Property Documents";
        if (!file_exists($realpath)) {
            mkdir($realpath, 0777, true);
        }

        # Final Relative Path
        $uploadDir .= "Property Documents/";

        # Prepare Values
        $newfilename     = uniqid() . "_" . $filename . "." . $ext;
        $destination     = $uploadDir . $newfilename;
        $realdestination = $realpath . "/" . $newfilename;
        $tmp_name        = $_FILES['Attach_1']['tmp_name'];
        $uploadStatus    = move_uploaded_file($tmp_name, $realdestination);

        if ($uploadStatus) {
            # Unlink old document from the drive
            $oldfile_info           = pathinfo($oldAttachment_1);
            $oldfile_name           = $oldfile_info['basename'];
            $oldFileRealDestination = $realpath . "/" . $oldfile_name;
            if (!file_exists($oldFileRealDestination)) {
                unlink($oldFileRealDestination);
            }

            $uploadpath_1 = $db->getBaseUrl() . $destination;
        } else {
            $uploadpath_1 = $oldAttachment_1;
        }
    } else {
        $uploadpath_1 = $oldAttachment_1;
    }

    # Extract File if Exist else set it to NULL - Attachment 2
    if (isset($_FILES['Attach_2']['tmp_name'])) {
        $pathinfo = pathinfo($_FILES['Attach_2']['name']);
        $filename = $pathinfo['filename'];
        $ext      = $pathinfo['extension'];

        # Prepare Path - Check if it exist if not create a new one
        $realpath = realpath('../../Documents/');
        $realpath = $realpath . "/Property Documents";
        if (!file_exists($realpath)) {
            mkdir($realpath, 0777, true);
        }

        # Final Relative Path
        # $uploadDir .= "Property Documents/";

        # Prepare Values
        $newfilename     = uniqid() . "_" . $filename . "." . $ext;
        $destination     = $uploadDir . $newfilename;
        $realdestination = $realpath . "/" . $newfilename;
        $tmp_name        = $_FILES['Attach_2']['tmp_name'];
        $uploadStatus    = move_uploaded_file($tmp_name, $realdestination);

        if ($uploadStatus) {
            # Unlink old document from the drive
            $oldfile_info           = pathinfo($oldAttachment_2);
            $oldfile_name           = $oldfile_info['basename'];
            $oldFileRealDestination = $realpath . "/" . $oldfile_name;
            if (!file_exists($oldFileRealDestination)) {
                unlink($oldFileRealDestination);
            }

            $uploadpath_2 = $db->getBaseUrl() . $destination;
        } else {
            $uploadpath_2 = $oldAttachment_2;
        }
    } else {
        $uploadpath_2 = $oldAttachment_2;
    }

    # Extract File if Exist else set it to NULL - Attachment 3
    if (isset($_FILES['Attach_3']['tmp_name'])) {
        $pathinfo = pathinfo($_FILES['Attach_3']['name']);
        $filename = $pathinfo['filename'];
        $ext      = $pathinfo['extension'];

        # Prepare Path - Check if it exist if not create a new one
        $realpath = realpath('../../Documents/');
        $realpath = $realpath . "/Property Documents";
        if (!file_exists($realpath)) {
            mkdir($realpath, 0777, true);
        }

        # Final Relative Path
        # $uploadDir .= "Property Documents/";

        # Prepare Values
        $newfilename     = uniqid() . "_" . $filename . "." . $ext;
        $destination     = $uploadDir . $newfilename;
        $realdestination = $realpath . "/" . $newfilename;
        $tmp_name        = $_FILES['Attach_3']['tmp_name'];
        $uploadStatus    = move_uploaded_file($tmp_name, $realdestination);

        if ($uploadStatus) {
            # Unlink old document from the drive
            $oldfile_info           = pathinfo($oldAttachment_3);
            $oldfile_name           = $oldfile_info['basename'];
            $oldFileRealDestination = $realpath . "/" . $oldfile_name;
            if (!file_exists($oldFileRealDestination)) {
                unlink($oldFileRealDestination);
            }

            $uploadpath_3 = $db->getBaseUrl() . $destination;
        } else {
            $uploadpath_3 = $oldAttachment_3;
        }
    } else {
        $uploadpath_3 = $oldAttachment_3;
    }

    $update = $db->QueryDML("UPDATE PropertyList SET UserId = '$userId', PropertyTypeId = '$propertyTypeId', CountryId = '$countryId', StateId = '$stateId', CountryName = '$countryName', StateName = '$stateName', City = '$city', Latitude = '$lat', Longitude = '$long', Address = '$address', TypeId = '$typeId', Type = '$type', LandSqrFt = '$landSqrFt', BuildingSqrFt = '$buildingSqrFt', Amount = '$amount', Attachment1 = '$uploadpath_1', Attachment2 = '$uploadpath_2', Attachment3 = '$uploadpath_3' WHERE Id = '$propertyId'");

    if ($update['Success']) {
        $response = array(
            'ResponseCode' => '200',
            'ResponseMsg'  => 'Property details updated successfully',
        );
    } else {
        $response = array(
            'ResponseCode' => '201',
            'ResponseMsg'  => 'Error! an error occured while updating property details, try again later.');
    }
}

if ($action == 'addCompetition') {
    $CustomerId      = $_POST['CustomerId'];
    $CompetitionName = $_POST['CompetitionName'];
    $CompetitionType = $_POST['CompetitionType'];
    $Amount          = $_POST['Amount'];
    $StartDate       = $db->SqlDateFormat($_POST['StartDate']);
    $EndDate         = $db->SqlDateFormat($_POST['EndDate']);

    if (isset($_FILES['SpecificationImage']['tmp_name'])) {
        $pathinfo = pathinfo($_FILES['SpecificationImage']['name']);
        $filename = $pathinfo['filename'];
        $ext      = $pathinfo['extension'];

        # Prepare Path - Check if it exist if not create a new one
        $realpath = realpath('../../Documents/');
        $realpath = $realpath . "/Competition Documents";
        if (!file_exists($realpath)) {
            mkdir($realpath, 0777, true);
        }

        # Final Relative Path
        $uploadDir = $uploadDir . "Competition Documents/";

        # Prepare Values
        $newfilename     = uniqid() . "_" . $filename . "." . $ext;
        $destination     = $uploadDir . $newfilename;
        $realdestination = $realpath . "/" . $newfilename;
        $tmp_name        = $_FILES['SpecificationImage']['tmp_name'];
        $uploadStatus    = move_uploaded_file($tmp_name, $realdestination);
        if ($uploadStatus) {
            $uploadpath = $db->getBaseUrl() . $destination;
        } else {
            $uploadpath = 'Unable to upload';
        }
    } else {
        $uploadpath = 'No attachment';
    }

    $CreateDate = $db->SqlDateFormat("now");

    $table  = "CompetitionTable";
    $fields = array('CustomerId', 'CompetitionName', 'CompetitionType', 'Amount', 'SpecificationImage', 'StartDate', 'EndDate', 'CreateDate');
    $values = array($CustomerId, $CompetitionName, $CompetitionType, $Amount, $uploadpath, $StartDate, $EndDate, $CreateDate);

    $create_competition = $db->Insert($table, $fields, $values);

    if ($create_competition) {
        $response = array('ResponseCode' => '200', 'ResponseMsg' => 'Competition record created successfully.');
    } else {
        $response = array('ResponseCode' => '201', 'ResponseMsg' => 'Unable to create competition record now. Try again later.');
    }

}

if ($action == 'updatedCompetition') {
    $CompetitionId   = $_POST['Id'];
    $CustomerId      = $_POST['CustomerId'];
    $CompetitionName = $_POST['CompetitionName'];
    $CompetitionType = $_POST['CompetitionType'];
    $Amount          = $_POST['Amount'];
    $StartDate       = $db->SqlDateFormat($_POST['StartDate']);
    $EndDate         = $db->SqlDateFormat($_POST['EndDate']);

    # Preparing Query
    $competition_info      = $db->GetRow("SELECT SpecificationImage FROM CompetitionTable WHERE Id = '$CompetitionId'");
    $oldSpecificationImage = $competition_info['SpecificationImage'];

    if (isset($_FILES['SpecificationImage']['tmp_name'])) {
        $pathinfo = pathinfo($_FILES['SpecificationImage']['name']);
        $filename = $pathinfo['filename'];
        $ext      = $pathinfo['extension'];

        # Prepare Path - Check if it exist if not create a new one
        $realpath = realpath('../../Documents/');
        $realpath = $realpath . "/Competition Documents";
        if (!file_exists($realpath)) {
            mkdir($realpath, 0777, true);
        }

        # Final Relative Path
        $uploadDir = $uploadDir . "Competition Documents/";

        # Prepare Values
        $newfilename     = uniqid() . "_" . $filename . "." . $ext;
        $destination     = $uploadDir . $newfilename;
        $realdestination = $realpath . "/" . $newfilename;
        $tmp_name        = $_FILES['SpecificationImage']['tmp_name'];
        $uploadStatus    = move_uploaded_file($tmp_name, $realdestination);
        if ($uploadStatus) {
            # Unlink old document from the drive
            $oldfile_info = pathinfo($oldSpecificationImage);
            $oldfile_name = $oldfile_info['basename'];
            unlink($realpath . "/" . $oldfile_name);
            $uploadpath = $db->getBaseUrl() . $destination;
        } else {
            $uploadpath = $oldSpecificationImage;
        }
    } else {
        $uploadpath = $oldSpecificationImage;
    }

    $update_competition = $db->QueryDML("UPDATE CompetitionTable SET CustomerId = '$CustomerId', CompetitionName = '$CompetitionName', CompetitionType = '$CompetitionType', Amount = '$Amount', SpecificationImage = '$uploadpath', StartDate = '$StartDate', EndDate = '$EndDate' WHERE Id = '$CompetitionId'");

    // print($db->LastError());

    if ($update_competition['Success']) {
        $response = array('ResponseCode' => '200', 'ResponseMsg' => 'Competition record updated successfully.');
    } else {
        $response = array('ResponseCode' => '201', 'ResponseMsg' => 'Unable to update competition record now. Try again later.');
    }

}

echo json_encode($response);
