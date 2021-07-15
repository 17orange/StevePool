<?php
    // set the desired variables
    if( isset($_POST["selectedUserID"]) && isset($_POST["selectedWeek"]) )
    {
      $_SESSION["picksUserID"] = $_POST["selectedUserID"];
      $_SESSION["picksWeek"] = $_POST["selectedWeek"];
    }

    // theyre trying to set some regular season picks
    if( ($_SESSION["picksWeek"] < 19) && isset($_POST["picksGame1Winner"]) )
    {
      // see which picks we are looking for
      $needToCheck = array();
      $pickGameIDs = array();
      $pickWinners = array();
      $stopAt = 16;
      for( $i=1; $i<17; $i++ )
      {
        if( isset($_POST["picksGame" . $i . "Winner"]) )
        {
          $points = $_POST["picksGame" . $i . "Points"];
          $pickGameIDs[$points] = mysqli_real_escape_string($link, $_POST["picksGame" . $i . "ID"]);
          $pickWinners[$points] = mysqli_real_escape_string($link, $_POST["picksGame" . $i . "Winner"]);
          $stopAt--; 
        }
      }

      // now build the query
      $query = "call AdminSetPicks(" . $_SESSION["picksUserID"];
      $managePicksError = "";
      for( $i=16; $i>$stopAt; $i-- )
      {
        if( !isset($pickWinners[$i]) )
        {
          $managePicksError = "No pick for the " . $i . " point" . (($i > 1) ? "s" : "") . " level";
          $i = 0;
        }
        else
        {
          $query .= ", " . $pickGameIDs[$i] . ", '" . (($pickWinners[$i] == "null") ? "" : $pickWinners[$i]) . "'";
        }
      }

      // if there was no error, save the picks
      if( $managePicksError == "" )
      {
        for( $i=$stopAt; $i>0; $i-- )
        {
          $query .= ", 0, ''";
        }
        $query .= "," . mysqli_real_escape_string($link, $_POST["picksMNF"]) . ")";
        runQuery( $query );
        $managePicksError = "Picks Saved!";
      }
    }
    else if( ($_SESSION["picksWeek"] < 24) && isset($_POST["picksGame1Winner"]) )
    {
      // see which picks we are looking for
      $needToCheck = array();
      $pickGameIDs = array();
      $pickTypes = array();
      $pickWinners = array();
      $pickPoints = array();
      $stopAt = ($_SESSION["picksWeek"] == 23) ? 10 : (($_SESSION["picksWeek"] == 19) ? 6 : 4);
      for( $i=1; $i<$stopAt + 1; $i++ )
      {
        if( isset($_POST["picksGame" . $i . "Winner"]) )
        {
          $pickPoints[] = $_POST["picksGame" . $i . "Points"];
          $pickGameIDs[] = mysqli_real_escape_string($link, $_POST["picksGame" . $i . "ID"]);
          $pickTypes[] = mysqli_real_escape_string($link, $_POST["picksGame" . $i . "Type"]);
          $pickWinners[] = mysqli_real_escape_string($link, $_POST["picksGame" . $i . "Winner"]);
        }
      }

      // now build the query
      $query = "call AdminSetPlayoffPicks(" . $_SESSION["picksUserID"];
      $managePicksError = "";
      for( $i=0; $i<$stopAt; $i++ )
      {
        if( !isset($pickWinners[$i]) )
        {
          $managePicksError = "Missing pick for game " . $i;
          $i = $stopAt;
        }
        else
        {
          $query .= ", " . $pickGameIDs[$i] . ", '" . $pickTypes[$i] . "', '" . $pickWinners[$i] . "', " . $pickPoints[$i];
        }
      }

      // if there was no error, save the picks
      if( $managePicksError == "" )
      {
        for( $i=$stopAt; $i<10; $i++ )
        {
          $query .= ", 0, '', '', 0";
        }
        $query .= "," . mysqli_real_escape_string($link, $_POST["picksTB1"]) . "," . mysqli_real_escape_string($link, $_POST["picksTB2"]) . 
                  "," . mysqli_real_escape_string($link, $_POST["picksTB3"]) . "," . mysqli_real_escape_string($link, $_POST["picksTB4"]) . 
                  "," . mysqli_real_escape_string($link, $_POST["picksTB5"]) . "," . mysqli_real_escape_string($link, $_POST["picksTB6"]) . ")";
        runQuery( $query );
        $managePicksError = "Picks Saved!";
      }
    }

    // theyre trying to set consolation picks
    if( isset($_POST["pickswc1AFC"]) )
    {
      $indices = ["wc1AFC", "wc2AFC", "wc3AFC", "wc1NFC", "wc2NFC", "wc3NFC", "div1AFC", "div2AFC", "div1NFC", "div2NFC", "confAFC", "confNFC", "superBowl"];
      $query = "call AdminSetConsolationPicks(" . $_SESSION["picksUserID"];
      $managePicksError = "";
      for( $i=0; $i<count($indices); $i++ )
      {
        if( isset($_POST["picks" . $indices[$i]]) )
        {
          $query .= ",'" . $_POST["picks" . $indices[$i]] . "'";
        }
        else
        {
          $managePicksError = "No pick for the " . $indices[$i] . " game";
        }
      }

      // if there was no error, save the picks
      if( $managePicksError == "" )
      {
        $query .= "," . mysqli_real_escape_string($link, $_POST["picksTiebreaker"]) . ")";
        runQuery( $query );
        $managePicksError = "Picks Saved!" . " => " . $query;
      }
    }
?>
