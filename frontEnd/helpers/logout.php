<?php
  session_start();

  include "../util.php";
?>
<!DOCTYPE html>
<html xmlns="https://www.w3.org/1999/xhtml" lang="en">
  <head>
    <script type="text/javascript" src="../includes/jquery-1.11.1.js"></script>
  </head>
  <body>
    <script type="text/javascript">
<?php
  if( isset($_SESSION["spsID"]) )
  {
    // clean it
    $sid = mysqli_real_escape_string( $link, $_SESSION["spsID"] );

    // log them out and reload the parent
    RunQuery( "call Logout(" . $sid . ",'" . $_SERVER['REMOTE_ADDR'] . "')", false);

    // save the session ID for future use
    unset($_SESSION["spsID"]);
    unset($_SESSION["playerName"]);
    unset($_SESSION["cbm"]);
    setcookie("spsID", "", time() - 3600 * 24 * 30, "/", $_SERVER["SERVER_NAME"]);

    if( isset($_SESSION["pageName"]) && $_SESSION["pageName"] != "makePicks" && $_SESSION["pageName"] != "possibleOutcomes" )
    {
?>
      parent.location.search = "?refresh=" + Math.random();
<?php
    }
    else
    {
      unset($_SESSION["pageName"]);
?>
      parent.location = "/stevePool/";
<?php
    }
  }
?>
    </script>
  </body>
</html>
