<?php
  // make sure they've submitted some picks
  $showSuccess = false;
  $showFailure = false;
  if( isset($_POST["picksType"]) && $_POST["picksType"] == "regularSeason" )
  {
    // make sure these are valid game ids
    $query = "select weekNumber from Game where gameID in (-1";
    $winners = array();
    for( $i=1; $i<17; $i++ )
    {
      if( isset($_POST["game" . $i]) )
      {
        $query .= "," . mysqli_real_escape_string($link, $_POST["game" . $i]);
        $winners[$_POST["pts" . $i]] = $_POST["winner" . $i];
      }
    }
    $query .= ")";
    $weekResults = runQuery( $query );
    if( mysqli_num_rows( $results ) == 1 )
    {
      // grab that week number
      $weekNum = mysqli_fetch_assoc( $weekResults );
      $weekNum = $weekNum["weekNumber"];

      // clean the tiebreaker
      $tieBreak = mysqli_real_escape_string($link, $_POST["tieBreak"]);

      // build the query
      $saveQuery = "call SavePicks(" . mysqli_real_escape_string( $link, $_SESSION["spsID"] ) . 
                   ",'" . $_SERVER["REMOTE_ADDR"] . "'," . $weekNum . "," . $tieBreak;
      for( $i=16; $i>0; $i-- )
      {
        $saveQuery .= ",'" . (isset($winners[$i]) ? mysqli_real_escape_string($link, $winners[$i]) : "") . "'";
      }
      $saveQuery .= ")";

      // run it
      runQuery( $saveQuery );

      // let them know if it worked
      $showSuccess = true;
      for( $i=1; $i<17 && $showSuccess; $i++ )
      {
        if( isset($_POST["game" . $i]) ) 
        {
          $checkResult = mysqli_fetch_assoc( runQuery( "select winner, points from Pick join Session using (userID) where gameID=" . 
                                                       $_POST["game" . $i] . " and sessionID=" . $_SESSION["spsID"] ) );
          $showFailure = ($checkResult["winner"] != $winners[$_POST["pts" . $i]]) || ($checkResult["points"] != $_POST["pts" . $i]);
          $showSuccess = !$showFailure;
        }
      }
    }
  }
  else if( isset($_POST["picksType"]) && isset($_POST["game1"]) && $_POST["picksType"] == "wildCard" )
  {
    // make sure these are valid game ids
    $query = "select weekNumber from Game where gameID in (-1";
    $winners = array();
    for( $i=1; $i<5; $i++ )
    {
      if( isset($_POST["game" . $i]) )
      {
        $query .= "," . mysqli_real_escape_string($link, $_POST["game" . $i]);
        $winners[$_POST["pts" . $i]] = $_POST["winner" . $i];
      }
    }
    $query .= ")";
    $weekResults = runQuery( $query );
    if( mysqli_num_rows( $results ) == 1 )
    {
      // grab that week number
      $weekNum = mysqli_fetch_assoc( $weekResults );
      $weekNum = $weekNum["weekNumber"];

      // clean the tiebreakers
      $tieBreak1 = mysqli_real_escape_string($link, $_POST["tb1"]);
      $tieBreak2 = mysqli_real_escape_string($link, $_POST["tb2"]);
      $tieBreak3 = mysqli_real_escape_string($link, $_POST["tb3"]);
      $tieBreak4 = mysqli_real_escape_string($link, $_POST["tb4"]);

      // build the query
      $saveQuery = "call SaveWildCardPicks(" . mysqli_real_escape_string( $link, $_SESSION["spsID"] ) . 
                   ",'" . $_SERVER["REMOTE_ADDR"] . "'," . $tieBreak1 . "," . $tieBreak2 . "," . $tieBreak3 . "," . $tieBreak4;
      for( $i=4; $i>0; $i-- )
      {
        $saveQuery .= ",'" . (isset($winners[$i]) ? mysqli_real_escape_string($link, $winners[$i]) : "") . "'";
      }
      $saveQuery .= ")";

      // run it
      runQuery( $saveQuery );

      // let them know it worked
      $showSuccess = true;
    }
  }
  else if( isset($_POST["picksType"]) && isset($_POST["game1"]) && $_POST["picksType"] == "divisional" )
  {
    // make sure these are valid game ids
    $query = "select weekNumber from Game where gameID in (-1";
    $winners = array();
    for( $i=1; $i<5; $i++ )
    {
      if( isset($_POST["game" . $i]) )
      {
        $query .= "," . mysqli_real_escape_string($link, $_POST["game" . $i]);
        $winners[$_POST["pts" . $i]] = $_POST["winner" . $i];
      }
    }
    $query .= ")";
    $weekResults = runQuery( $query );
    if( mysqli_num_rows( $results ) == 1 )
    {
      // grab that week number
      $weekNum = mysqli_fetch_assoc( $weekResults );
      $weekNum = $weekNum["weekNumber"];

      // clean the tiebreakers
      $tieBreak1 = mysqli_real_escape_string($link, $_POST["tb1"]);
      $tieBreak2 = mysqli_real_escape_string($link, $_POST["tb2"]);
      $tieBreak3 = mysqli_real_escape_string($link, $_POST["tb3"]);
      $tieBreak4 = mysqli_real_escape_string($link, $_POST["tb4"]);

      // build the query
      $saveQuery = "call SaveDivisionalPicks(" . mysqli_real_escape_string( $link, $_SESSION["spsID"] ) . 
                   ",'" . $_SERVER["REMOTE_ADDR"] . "'," . $tieBreak1 . "," . $tieBreak2 . "," . $tieBreak3 . "," . $tieBreak4;
      for( $i=4; $i>0; $i-- )
      {
        $saveQuery .= ",'" . (isset($winners[$i]) ? mysqli_real_escape_string($link, $winners[$i]) : "") . "'";
      }
      $saveQuery .= ")";

      // run it
      runQuery( $saveQuery );

      // let them know it worked
      $showSuccess = true;
    }
  }
  else if( isset($_POST["picksType"]) && isset($_POST["game1"]) && $_POST["picksType"] == "conference" )
  {
    // make sure these are valid game ids
    $query = "select weekNumber from Game where gameID in (-1";
    $winners = array();
    for( $i=1; $i<5; $i++ )
    {
      if( isset($_POST["game" . $i]) )
      {
        $query .= "," . mysqli_real_escape_string($link, $_POST["game" . $i]);
        $winners["id" . $_POST["pts" . $i]] = $_POST["game" . $i];
        $winners[$_POST["pts" . $i]] = $_POST["winner" . $i];
        $winners["type" . $_POST["pts" . $i]] = $_POST["pickType" . $i];
      }
    }
    $query .= ")";
    $weekResults = runQuery( $query );
    if( mysqli_num_rows( $results ) == 1 )
    {
      // grab that week number
      $weekNum = mysqli_fetch_assoc( $weekResults );
      $weekNum = $weekNum["weekNumber"];

      // clean the tiebreakers
      $tieBreak1 = mysqli_real_escape_string($link, $_POST["tb1"]);
      $tieBreak2 = mysqli_real_escape_string($link, $_POST["tb2"]);
      $tieBreak3 = mysqli_real_escape_string($link, $_POST["tb3"]);
      $tieBreak4 = mysqli_real_escape_string($link, $_POST["tb4"]);

      // build the query
      $saveQuery = "call SaveConferencePicks(" . mysqli_real_escape_string( $link, $_SESSION["spsID"] ) . 
                   ",'" . $_SERVER["REMOTE_ADDR"] . "'," . $tieBreak1 . "," . $tieBreak2 . "," . $tieBreak3 . "," . $tieBreak4;
      for( $i=4; $i>0; $i-- )
      {
        $saveQuery .= "," . (isset($winners["id" . $i]) ? mysqli_real_escape_string($link, $winners["id" . $i]) : "");
        $saveQuery .= ",'" . (isset($winners[$i]) ? mysqli_real_escape_string($link, $winners[$i]) : "") . "'";
        $saveQuery .= ",'" . (isset($winners["type" . $i]) ? mysqli_real_escape_string($link, $winners["type" . $i]) : "") . "'";
      }
      $saveQuery .= ")";

      // run it
      runQuery( $saveQuery );

      // let them know it worked
      $showSuccess = true;
    }
  }
  else if( isset($_POST["picksType"]) && isset($_POST["winner1"]) && $_POST["picksType"] == "superBowl" )
  {
    // clean the tiebreaker
    $tieBreak1 = mysqli_real_escape_string($link, $_POST["tieBreak"]);

    // build the query
    $saveQuery = "call SaveSuperBowlPicks(" . mysqli_real_escape_string( $link, $_SESSION["spsID"] ) . 
                 ",'" . $_SERVER["REMOTE_ADDR"] . "'," . $tieBreak1;
    for( $i=1; $i<11; $i++ )
    {
      $saveQuery .= ",'" . (isset($_POST["winner" . $i]) ? mysqli_real_escape_string($link, $_POST["winner" . $i]) : "") . "'";
    }
    $saveQuery .= ")";

    // run it
    runQuery( $saveQuery );

    // let them know it worked
    $showSuccess = true;
  }
  else if( isset($_POST["picksType"]) && $_POST["picksType"] == "consolation" )
  {
    // clean the inputs
    $tieBreak = mysqli_real_escape_string($link, $_POST["tieBreaker"]);
    $wc1AFC = mysqli_real_escape_string($link, $_POST["afcWC1"]);
    $wc2AFC = mysqli_real_escape_string($link, $_POST["afcWC2"]);
    $wc1NFC = mysqli_real_escape_string($link, $_POST["nfcWC1"]);
    $wc2NFC = mysqli_real_escape_string($link, $_POST["nfcWC2"]);
    $div1AFC = mysqli_real_escape_string($link, $_POST["afcDiv1"]);
    $div2AFC = mysqli_real_escape_string($link, $_POST["afcDiv2"]);
    $div1NFC = mysqli_real_escape_string($link, $_POST["nfcDiv1"]);
    $div2NFC = mysqli_real_escape_string($link, $_POST["nfcDiv2"]);
    $confAFC = mysqli_real_escape_string($link, $_POST["afcCC"]);
    $confNFC = mysqli_real_escape_string($link, $_POST["nfcCC"]);
    $superBowl = mysqli_real_escape_string($link, $_POST["SB"]);

    // build the query
    $saveQuery = "call SaveConsolationPicks(" . mysqli_real_escape_string( $link, $_SESSION["spsID"] ) . 
                 ",'" . $_SERVER["REMOTE_ADDR"] . "','" . $wc1AFC . "','" . $wc2AFC . "','" . $wc1NFC . "','" . 
                 $wc2NFC . "','" . $div1AFC . "','" . $div2AFC . "','" . $div1NFC . "','" . $div2NFC . "','" . 
                 $confAFC . "','" . $confNFC . "','" . $superBowl . "'," . $tieBreak . ")";

    // run it
    runQuery( $saveQuery );

    // let them know it worked
    $showSuccess = true;
  }
?>