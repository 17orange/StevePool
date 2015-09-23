<?php
  // navigation logic
  if( isset($_GET["newPage"]) )
  {
    $_SESSION["pageName"] = $_GET["newPage"];
    unset( $_SESSION["showPicksWeek"] );
    unset( $_SESSION["showPicksSeason"] );
    unset( $_SESSION["showPicksSplit"] );
  }
  if( !isset($_SESSION["pageName"]) )
  {
    $_SESSION["pageName"] = "standings";
  }

  // make sure their session is still good
  if( !isset($_SESSION["spsID"]) && isset($_COOKIE["spsID"]) )
  {
    $_SESSION["spsID"] = $_COOKIE["spsID"];
  }
  if( isset($_SESSION["spsID"]) )
  {
    $spsID = mysqli_real_escape_string( $link, $_SESSION["spsID"] );
    $results = RunQuery( "select * from Session where sessionID=" . $spsID . " and IP='" . $_SERVER["REMOTE_ADDR"] . "'", false );
    if( count( $results ) == 0 )
    {
      unset( $_SESSION["spsID"] );
      setcookie("spsID", null, time() - 3600 * 24 * 30, "/", $_SERVER["SERVER_NAME"]);
    }
    else
    {
      setcookie("spsID", $_SESSION["spsID"], time() + 3600 * 24 * 30, "/", $_SERVER["SERVER_NAME"]);
    }
  }
  if( isset($_SESSION["spsID"]) && !isset($_SESSION["playerName"]) )
  {
    $results = RunQuery( "select concat(firstName, ' ' , lastName) as playerName from User " . 
                         "join Session using (userID) where sessionID=" . $_SESSION["spsID"] );
    $_SESSION["playerName"] = $results[0]["playerName"];
  }

  // see if they hid the logos
  if( !isset($_SESSION["spHideLogos"]) && isset($_COOKIE["spHideLogos"]) )
  {
    $_SESSION["spHideLogos"] = $_COOKIE["spHideLogos"];
  }
?>
