<?php
    // set the desired variables
    if( isset($_POST["selectedUserID"]) && isset($_POST["selectedWeek"]) )
    {
      $_SESSION["picksUserID"] = $_POST["selectedUserID"];
      $_SESSION["picksWeek"] = $_POST["selectedWeek"];
    }

    // theyre trying to set some picks
    if( isset($_POST["picksGame1Winner"]) )
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
?>
