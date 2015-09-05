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
<?php
  if( isset($_POST["task"]) && $_POST["task"] == "login" )
  {
    // clean it
    $uname = mysqli_real_escape_string( $link, $_POST["loginUser"] );
    $pass = mysqli_real_escape_string( $link, $_POST["loginPW"] );

    // test the values they sent
    $results = runQuery( "select * from User where username='" . $uname . "' or email='" . $uname . "'" );
    $results2 = runQuery( "select * from User where (username='" . $uname . "' or email='" . $uname . "') and password=md5('" . $pass . "')" );

    // user doesnt exist
    if( mysqli_num_rows( $results ) == 0 )
    {
?>
      parent.document.getElementById("loginError").innerHTML = "No user matching that username/email found.";
<?php
    }
    // user exists, but password is bad
    else if( mysqli_num_rows( $results2 ) == 0 )
    {
?>
      parent.document.getElementById("loginError").innerHTML = "Wrong password.";
<?php
    }
    // password is good, so log them in
    else
    {
      // grab their userID
      $results = mysqli_fetch_assoc( $results2 );
      $uid = $results["userID"];
      runQuery( "call Login(" . $uid . ",md5('" . $pass . "'),'" . $_SERVER['REMOTE_ADDR'] . "')");

      // save the session ID for future use
      $results = mysqli_fetch_assoc( runQuery( "select sessionID from Session where userID=" . $uid ));
      $_SESSION["spsID"] = $results["sessionID"];

      setcookie("spsID", $_SESSION["spsID"], time() + 3600 * 24 * 30, "/", $_SERVER["SERVER_NAME"]);
?>
      parent.location.search = "?refresh=" + Math.random();
<?php
    }
  }
?>
    </script>
  </body>
</html>
