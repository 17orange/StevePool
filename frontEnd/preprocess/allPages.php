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
    $results = runQuery( "select * from Session where sessionID=" . $spsID . " and IP='" . 
                         $_SERVER["REMOTE_ADDR"] . "'" );
    if( mysqli_num_rows( $results ) == 0 )
    {
      unset( $_SESSION["spsID"] );
    }
    else
    {
      setcookie("spsID", $_SESSION["spsID"], time() + 3600 * 24 * 30, "/", $_SERVER["SERVER_NAME"]);
    }
  }
  if( isset($_SESSION["spsID"]) && !isset($_SESSION["playerName"]) )
  {
    $results = mysqli_fetch_assoc( runQuery( "select concat(firstName, ' ' , lastName) as playerName from User " . 
                                             "join Session using (userID) where sessionID=" . $_SESSION["spsID"] ) );
    $_SESSION["playerName"] = $results["playerName"];
  }

  // see if they hid the logos
  if( !isset($_SESSION["spHideLogos"]) && isset($_COOKIE["spHideLogos"]) )
  {
    $_SESSION["spHideLogos"] = $_COOKIE["spHideLogos"];
  }
?>
