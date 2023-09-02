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
  if( ($_POST["task"] ?? null) == "cbm" )
  {
    // clean it
    $cbm = mysqli_real_escape_string( $link, $_POST["acctCBM"] );
    $_SESSION["cbm"] = ($cbm == "Y");

    // save their choice
    if( isset($_SESSION["spsID"]) ) {
      // clean it
      $sid = mysqli_real_escape_string( $link, $_SESSION["spsID"] );
      $results = RunQuery( "call SetColorblindMode(" . $sid . ", '" . $cbm . "')", false );

      $_SESSION["cbm"] = ((RunQuery( "select colorblindMode from User join Session using (userID) where sessionID=" . $_SESSION["spsID"], true, true ))[0]["colorblindMode"] == "Y");
    }
?>
      parent.location.reload();
<?php
  }
  else if( isset($_SESSION["spsID"]) )
  {
    // clean it
    $sid = mysqli_real_escape_string( $link, $_SESSION["spsID"] );
    $username = mysqli_real_escape_string( $link, $_POST["acctUser"] );
    $email = mysqli_real_escape_string( $link, $_POST["acctEmail"] );
    $pword = mysqli_real_escape_string( $link, $_POST["acctPW"] );
    $pword2 = (isset($_POST["acctPW2"]) && ($_POST["acctPW2"] != "")) ? mysqli_real_escape_string( $link, $_POST["acctPW2"] ) : "";

    // make sure nobody else has that username
    $thisGuy = RunQuery( "select userID from User join Session using (userID) where sessionID=" . $sid );
    $results = RunQuery( "select count(*) as num from User where userID != " . $thisGuy[0]["userID"] . " and username='" . $username . "'" );
    if( $results[0]["num"] != 0 )
    {
?>
      parent.document.getElementById('acctError').innerHTML = "Username already in use";
<?php
    }
    else
    {
      // make sure nobody else has that email
      $results = RunQuery( "select count(*) as num from User where userID != " . $thisGuy[0]["userID"] . " and email='" . $email . "'" );
      if( $results[0]["num"] != 0 )
      {
?>
      parent.document.getElementById('acctError').innerHTML = "Email already in use";
<?php
      }
      else
      {
        // make sure he sent the right password
        $results = RunQuery( "select count(*) as num from User where userID = " . $thisGuy[0]["userID"] . " and password=md5('" . $pword . "')" );
        if( $results[0]["num"] != 1 )
        {
?>
      parent.document.getElementById('acctError').innerHTML = "Password not correct";
<?php
        }
        else
        {
          // ok go ahead and update it
          $newPW = ($pword2 != "") ? ("md5('" . $pword2 . "')") : "null";
          $results = RunQuery( "call EditAccount(" . $sid . ", '" . $username . "', '" . $email . "', " . $newPW . ")", false );
?>
      parent.document.getElementById('acctError').innerHTML = "Info updated!";
<?php
        }
      }
    }
  }
?>
    </script>
  </body>
</html>
