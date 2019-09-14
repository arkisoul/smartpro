<?php
/*  require_once('Mail.php');
require_once('Mail/mime.php');*/
require_once '../PHPMailer/PHPMailerAutoload.php';

// Import PHPMailer classes into the global namespace
// use PHPMailer\PHPMailer\Exception;
// use PHPMailer\PHPMailer\PHPMailer;

//Load composer's autoloader
// require_once 'vendor/autoload.php';

/**
 * Generic Functions' Definition
 */
class GenFunctions
{

    # Redirect
    public function route($url)
    {
        if (headers_sent()) {
            die('<script type="text/javascript">window.location=\'' . $url . '\';</script‌​>');
        } else {
            header('Location: ' . $url);
            die();
        }
    }

    public function array_push_assoc($array, $key, $value)
    {
        $array[$key] = $value;
        return $array;
    }

    # cURL
    public function openURL($url)
    {
        $ch      = curl_init($url);
        $options = array(
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_POST           => 1,
            CURLOPT_POSTFIELDS     => "",
            CURLOPT_RETURNTRANSFER => 2,
        );
        curl_setopt_array($ch, $options);
        $response = curl_exec($ch);
        curl_close($ch);

        return json_decode($response, true);
    }

    # SMS URL
    public function smsURL($mobile, $msg)
    {
        $userId   = "mysmart@febnosms.com";
        $password = "msp01";
        $message  = urlencode($msg);
        $unicode  = 1;
        $mobile   = "00971" . $mobile;

        $url = "http://www.febnosms.com/http2/SubmitHTTP.aspx?MessageText=$message&MobileNumber=$mobile&UserId=$userId&Password=$password&unicode=$unicode";

        return sprintf($url);
    }

    # Email Setup
    public function sendMailer($args)
    {
        $mail = new PHPMailer(true);

        try {

            // Server Settings
            $mail->SMTPDebug = 2;
            $mail->isSMTP();
            $mail->Host     = "mail.almaskanengineering.com";
            $mail->SMTPAuth = true;
            $mail->AuthType = 'LOGIN';
            $mail->Username = "mysmartpro@qolsofts.com";
            $mail->Password = "j[60E@";
            $mail->Port     = 25;

            $mail->SMTPOptions = array(
                'ssl' => array(
                    'verify_peer'       => false,
                    'verify_peer_name'  => false,
                    'allow_self_signed' => true,
                ),
            );

            // User Data
            $sendTo     = $args['Email'];
            $sendToName = $args['Name'];
            $subject    = $args['Subject'];
            $message    = $args['Message'];

            // Recipients
            $mail->setFrom("mysmartpro@qolsofts.com", 'Info MySmartPro');
            $mail->addAddress($sendTo, $sendToName);
            /*$mail->addCC('');
            $mail->addBCC('');*/

            // Content
            $mail->IsHTML(false);
            $mail->Subject = $subject;
            $mail->Body    = $message;
            $mail->AltBody = $message;

            //send the message, check for errors
            if (!$mail->send()) {
                // echo "Mailer Error: " . $mail->ErrorInfo;
                return false;
            } else {
                // echo "mail sent";
                return true;
            }
        } catch (Exception $e) {
            // echo "Mailer Error: " . $mail->ErrorInfo;
            return false;
        }
    }

    public function sendFloorPlanRequest($args)
    {
        $mail = new PHPMailer(true);

        try {
            // Server Settings
            $mail->SMTPDebug = 2;
            $mail->isSMTP();
            $mail->Host     = "mail.almaskanengineering.com";
            $mail->SMTPAuth = true;
            $mail->AuthType = 'LOGIN';
            $mail->Username = "mysmartpro@qolsofts.com";
            $mail->Password = "j[60E@";
            $mail->Port     = 25;

            $mail->SMTPOptions = array(
                'ssl' => array(
                    'verify_peer'       => false,
                    'verify_peer_name'  => false,
                    'allow_self_signed' => true,
                ),
            );

            // User Data
            $username   = "mysmartpro@qolsofts.com";
            $sendTo     = $args['SendTo'];
            $sendToName = $args['SendName'];
            $subject    = $args['Subject'];
            $message    = $args['Message'];
            $html       = $args['HTMLBody'];

            // Recipients
            $mail->setFrom($username, 'Info MySmartPro');
            $mail->addAddress($sendTo, $sendToName);
            $mail->IsHTML(true);
            /*$mail->addCC('');
            $mail->addBCC('');*/

            // Content
            $mail->msgHTML($html);
            $mail->AltBody = $message;
            $mail->Subject = $subject;

            //send the message, check for errors
            if (!$mail->send()) {
                // echo "Mailer Error: " . $mail->ErrorInfo;
                return false;
            } else {
                return true;
                // echo "mail send";
            }
        } catch (Exception $e) {
            return false;
        }
    }

    # Random Number 1000-9999
    public function getRand($min = 1000, $max = 9999)
    {
        return $rand = mt_rand($min, $max);
    }

    # Random Password
    public function randPass($length = 3)
    {
        $bytes = openssl_random_pseudo_bytes($length, $cstrong);
        $hex   = bin2hex($bytes);
        return $hex;
    }

    # Validate Null Value
    public function ValNull($args = '')
    {
        return (($args == '' || strtolower($args) == 'null') ? false : true);
    }

    # Validate Email ID
    public function ValEmail($args = '')
    {
        return (filter_var($args, FILTER_VALIDATE_EMAIL) ? false : true);
    }

    # UTF_16 to UTF_8 converter
    public function utf16_to_utf8($str)
    {

        $c0 = ord($str[0]);
        $c1 = ord($str[1]);

        if ($c0 == 0xFE && $c1 == 0xFF) {
            $be = true;
        } else if ($c0 == 0xFF && $c1 == 0xFE) {
            $be = false;
        } else {
            return $str;
        }

        $str = substr($str, 2);
        $len = strlen($str);
        $dec = '';
        for ($i = 0; $i < $len; $i += 2) {
            $c = ($be) ? ord($str[$i]) << 8 | ord($str[$i + 1]) :
            ord($str[$i + 1]) << 8 | ord($str[$i]);
            if ($c >= 0x0001 && $c <= 0x007F) {
                $dec .= chr($c);
            } else if ($c > 0x07FF) {
                $dec .= chr(0xE0 | (($c >> 12) & 0x0F));
                $dec .= chr(0x80 | (($c >> 6) & 0x3F));
                $dec .= chr(0x80 | (($c >> 0) & 0x3F));
            } else {
                $dec .= chr(0xC0 | (($c >> 6) & 0x1F));
                $dec .= chr(0x80 | (($c >> 0) & 0x3F));
            }
        }
        return $dec;
    }

    public function convert_file_to_utf8($csvfile)
    {
        $utfcheck = file_get_contents($csvfile);
        $utfcheck = utf16_to_utf8($utfcheck);
        file_put_contents($csvfile, $utfcheck);
    }

    public static function delTree($dir)
    {
        $files = array_diff(scandir($dir), array('.', '..'));
        foreach ($files as $file) {
            (is_dir("$dir/$file")) ? delTree("$dir/$file") : unlink("$dir/$file");
        }
        return rmdir($dir);
    }
}
