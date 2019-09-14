<?php
header('Content-type: application/json');
header('Access-Control-Allow-Origin: *');

require_once 'config/functions.php';
$fn = new GenFunctions();

//Load composer's autoloader
require_once 'vendor/autoload.php';

if (isset($_REQUEST['SaveInviteConsultant'])) {

    $message = "Your CustomerID is 1234";
    $data    = array(
        'Email'   => 'arpit8atoz@gmail.com',
        'Name'    => 'Arpit Jain',
        'Subject' => 'MySmartPro: Recovery email for forgot Customer ID',
        'Message' => $message,
    );

    $fn->sendMailer($data);

    // echo urldecode('forgot=%7B%22Type%22%3A%22password%22%2C%22Channel%22%3A%22email%22%2C%22ID%22%3A%22rajamanikkam.S%40gmail.com%22%7D');
    // echo urldecode('get-properties-not-user=%7B%22UserId%22%3A%220521346850%22%7D');

    // $mail = new PHPMailer;

    // try {

    //     // Server Settings
    //     $mail->SMTPDebug = 2;
    //     $mail->isSMTP();
    //     $mail->Host     = "mail.almaskanengineering.com";
    //     $mail->SMTPAuth = true;
    //     // $mail->AuthType = 'PLAIN';
    //     $mail->Username = "mysmartpro@qolsofts.com";
    //     $mail->Password = "j[60E@";
    //     // $mail->SMTPSecure  = 'tls';
    //     $mail->Port        = 25;
    //     $mail->Debugoutput = 'html';
    //     $mail->SMTPAutoTLS = false;

    //     // User Data
    //     // $sendTo     = $args['Email'];
    //     // $sendToName = $args['Name'];
    //     // $subject    = $args['Subject'];
    //     // $message    = $args['Message'];

    //     // Recipients
    //     $mail->setFrom("mysmartpro@qolsofts.com", 'Info MySmartPro');
    //     $mail->addAddress("arpit8atoz@gmail.com", "Arpit");
    //     /*$mail->addCC('');
    //     $mail->addBCC('');*/

    //     // Content
    //     $mail->IsHTML(false);
    //     $mail->Subject = "Hello Moto";
    //     $mail->Body    = "Test mail";
    //     $mail->AltBody = "Test mail";

    //     //send the message, check for errors
    //     if (!$mail->send()) {
    //         echo "Mailer Error: " . $mail->ErrorInfo;
    //         // return false;
    //     } else {
    //         echo "mail sent";
    //         // return true;
    //     }
    // } catch (Exception $e) {
    //     echo "Mailer Error: " . $mail->ErrorInfo;
    //     // return false;
    // }
}
