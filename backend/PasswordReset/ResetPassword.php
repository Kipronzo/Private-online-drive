<?php

function ResetPassword($emailAddress) { //comes from forgotPassword.php
  require 'includes/mysql_connection.php';
  require 'includes/config.php';

  //creating two seperate tokens for validation
  $selector = bin2hex(random_bytes(8));
  $token = random_bytes(32);

  //Url for validation
  $url = "www.devbridge.tk/index?action=createnewpassword&selector=" . $selector . "&validator=" . bin2hex($token);

  //Timer when the token expires in database
  $expires = date("U") + 1200;

  $userEmail = $emailAddress;

  //Delete the same users tokens that were not used
  $sql = "DELETE FROM pwdReset WHERE pwdResetEmail=?;";
  $statement = mysqli_stmt_init($conn); //stmt
  if(!mysqli_stmt_prepare($statement, $sql)){
    echo "Couldnt prepare";
    exit();
  }else{
    mysqli_stmt_bind_param($statement, "s", $userEmail);
    mysqli_stmt_execute($statement);
  }

  //Create new token for the user
  $sql = "INSERT INTO pwdReset (pwdResetEmail, pwdResetSelector, pwdResetToken, pwdResetExpires) VALUES (?, ?, ?, ?);";
  $statement = mysqli_stmt_init($conn); //stmt
  if(!mysqli_stmt_prepare($statement, $sql)){
    echo "Couldnt prepare";
    exit();
  }else{
    $hashedToken = password_hash($token, PASSWORD_DEFAULT);
    mysqli_stmt_bind_param($statement, "ssss", $userEmail, $selector, $hashedToken, $expires);
    mysqli_stmt_execute($statement);
  }

  mysqli_stmt_close($statement);
  mysqli_close($conn);

  //Email message
  $to = $userEmail;
  $subject = 'Reset your password for private-online-drive';
  $message = '<p>We received a password reset request.</p>';
  $message .='<p>Here is your password reset link: </br>';
  $message .='<a href="' . $url . '">' . $url . '</a></p>';

  //headers for email message
  //Pop/Imap server is for receiving email
  //Smtp is for sending email
  $headers = "From: private-online-drive <mail.devbridge.tk> \r\n";
  $headers .= "Reply-To: mail.devbridge.tk \r\n";
  $headers .= "Content-type: text/html\r\n";

  mail($to, $subject, $message, $headers);

  header("Location: ./index?action=forgotpassword&reset=success");
}

?>
