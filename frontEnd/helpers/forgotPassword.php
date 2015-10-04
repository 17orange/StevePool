<?php
  session_start();

  include "../util.php";
?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" lang="en">
  <head>
    <script type="text/javascript" src="../includes/jquery-1.11.1.js"></script>
  </head>
  <body>
    <script type="text/javascript">
      parent.document.getElementById("loginError").innerHTML = "Z";
<?php
  if( isset($_POST["task"]) && $_POST["task"] == "login" )
  {
    // clean it
    $uname = mysqli_real_escape_string( $link, $_POST["loginUser"] );
    $bInfo = mysqli_real_escape_string( $link, $_POST["browserInfo"] );

    // test the values they sent
    $results = RunQuery( "select * from User where username='" . $uname . "' or email='" . $uname . "'" );

    // user doesnt exist
    if( count( $results ) == 0 )
    {
?>
      parent.document.getElementById("loginError").innerHTML = "No user matching that username/email found.";
<?php
    }
    // user exists, but has no email
    else if( count( $results ) == 1 && $results[0]["email"] == "" )
    {
?>
      parent.document.getElementById("loginError").innerHTML = "No email specified for that username.";
<?php
    }
    // everything is cool
    else
    {
?>
      parent.document.getElementById("loginError").innerHTML = "Test?";
<?php
      // grab their userID
      RunQuery( "call Login(" . $results[0]["userID"] . ",'" . $results[0]["password"] . "','" . $_SERVER['REMOTE_ADDR'] . "','" . $bInfo . "')", false);

      // save the session ID for future use
      $sessionResults = RunQuery( "select sessionID from Session where userID=" . $results[0]["userID"] );
      mail($results[0]["email"], "Steve's NFL Pool Password Reset", "Click the following link to reset your password for Steve's NFL Pool:  http://bradplusplus.com/stevePool/helpers/passwordReset.php?session=" . $sessionResults[0]["sessionID"], "From: StevePool@bradplusplus.com");
?>
      parent.document.getElementById("loginError").innerHTML = "Check your email for the reset link!<br>(Be sure to check in your spam folder too.)";
<?php
    }
  }
?>
    </script>
  </body>
</html>
