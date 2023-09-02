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
  if( isset($_POST["task"]) && $_POST["task"] == "login" )
  {
    // clean it
    $uname = mysqli_real_escape_string( $link, $_POST["loginUser"] );
    $pass = mysqli_real_escape_string( $link, $_POST["loginPW"] );
    $bInfo = mysqli_real_escape_string( $link, $_POST["browserInfo"] );

    // test the values they sent
    $results = RunQuery( "select * from User where username='" . $uname . "' or email='" . $uname . "'", false );
    $results2 = RunQuery( "select * from User where (username='" . $uname . "' or email='" . $uname . "') and password=md5('" . $pass . "')", false );

    // user doesnt exist
    if( count( $results ) == 0 )
    {
?>
      parent.document.getElementById("loginError").innerHTML = "No user matching that username/email found.";
<?php
    }
    // user exists, but password is bad
    else if( count( $results2 ) == 0 )
    {
?>
      parent.document.getElementById("loginError").innerHTML = "Wrong password.";
<?php
    }
    // password is good, so log them in
    else
    {
      // grab their userID
      $uid = $results2[0]["userID"];
      RunQuery( "call Login(" . $uid . ",md5('" . $pass . "'),'" . $_SERVER['REMOTE_ADDR'] . "','" . $bInfo . "')", false);

      // save the session ID for future use
      $results = RunQuery( "select sessionID from Session where userID=" . $uid, false );
      $_SESSION["spsID"] = $results[0]["sessionID"];
      $_SESSION["browserInfo"] = $bInfo;
      // see if we're in colorblind mode
      $_SESSION["cbm"] = ((RunQuery( "select colorblindMode from User join Session using (userID) where sessionID=" . $_SESSION["spsID"] ))[0]["colorblindMode"] == "Y");

      setcookie("spsID", $_SESSION["spsID"], time() + 3600 * 24 * 30, "/", $_SERVER["SERVER_NAME"]);
      setcookie("browserInfo", $_SESSION["browserInfo"], time() + 3600 * 24 * 30, "/", $_SERVER["SERVER_NAME"]);
?>
      parent.location.search = "?refresh=" + Math.random();
<?php
    }
  }
?>
    </script>
  </body>
</html>
