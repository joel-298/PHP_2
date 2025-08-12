<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require '../vendor/autoload.php'; // auto load php mailer 


function sendEmail($name, $email, $MailContent, $filePath = null)
{   

    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = SMTP_HOST ;
        $mail->SMTPAuth = true;
        $mail->Username = SMTP_USERNAME ;
        $mail->Password = SMTP_PASSWORD ;
        $mail->SMTPSecure = 'SSL';
        $mail->Port = SMTP_PORT;


        // Recipents 
        $mail->setFrom('joel@orientaloutsourcing.com', 'JOEL MATTHEW');
        $mail->addAddress("$email", "$name");
        // content 
        $mail->isHTML(true);
        $mail->Subject = "From Oriental Outsourcing !";
        $mail->Body = "$MailContent";


 
        if (!empty($filePath) && is_array($filePath)) {
            foreach ($filePath as $file) {
                if (file_exists($file)) {
                    $mail->addAttachment($file);
                }
            }
        } else {
            if (!empty($filePath) && file_exists($filePath)) {
                $mail->addAttachment($filePath);
            }
        }
        $mail->send();

    } catch (Exception $e) {
        echo "<script>console.log('Mailer Error: {$mail->ErrorInfo}');</script>"; // WILL HIT INTERNAL SERVER ERROR !
    }
}
?>