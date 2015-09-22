<?php
  session_start();

  include "../util.php";

  $error = "";
  $session = isset($_GET["session"]) ? $_GET["session"] : -1;
  if( isset($_POST["task"]) && $_POST["task"] == "reset" )
  {
    // clean it
    $pw1 = mysqli_real_escape_string( $link, $_POST["resetPW1"] );
    $pw2 = mysqli_real_escape_string( $link, $_POST["resetPW2"] );
    $session = mysqli_real_escape_string( $link, $_POST["session"] );

    // passwords aren't the same
    if( $pw1 == "" )
    {
      $error = "Please enter a new password.";
    }
    // passwords aren't the same
    else if( $pw2 == "" )
    {
      $error = "Please confirm your password";
    }
    // passwords aren't the same
    else if( $pw1 != $pw2 )
    {
      $error = "Passwords do not match";
    }
    // it's all good. update it
    else
    {
      $results = RunQuery( "call EditAccount(" . $session . ", null, null, md5('" . $pw1 . "'))", false );
      $error = "Password has been changed!<br>Go back <a href=\"/stevePool\">here</a> and try to log in.";
    }
  }
?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" lang="en">
  <head>
    <script type="text/javascript" src="../includes/jquery-1.11.1.js"></script>
  </head>
  <body>
    <form action="./passwordReset.php" method="post">
      <input type="hidden" name="task" value="reset">
      <input type="hidden" name="session" value="<?php echo $session; ?>">
      <table>
        <tr>
          <td><span>New Password</span></td>
          <td><input type="password" name="resetPW1" value="<?php 
  echo isset($_POST["resetPW1"]) ? $_POST["resetPW1"] : ""; ?>"></td>
        </tr>
        <tr>
          <td><span>Confirm New Password</span></td>
          <td><input type="password" name="resetPW2" value="<?php 
  echo isset($_POST["resetPW2"]) ? $_POST["resetPW2"] : ""; ?>"></td>
        </tr>
        <tr>
          <td colspan=2 style="text-align:center;"><input type="submit"></td>
        </tr>
        <tr>
          <td colspan=2 style="color:#FF0000; text-align:center;"><?php echo $error; ?></td>
        </tr>
      </table>
    </form>
  </body>
</html>
