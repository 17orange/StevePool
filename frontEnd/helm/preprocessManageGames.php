<?php
    // see if they changed a week
    if( isset($_POST["manageGameWeek"]) )
    {
      $_SESSION["manageGameWeek"] = $_POST["manageGameWeek"];
      $weekError = "Week Changed!";
    }
    // they changed a lock time
    else
    {
      // grab the games in question, and check what their times were
      $gameResults = runQuery( "select gameID, lockTime from Game where season=" . $thisSeason . " and weekNumber=" . 
                               $_SESSION["manageGameWeek"] . " order by gameTime asc, gameID asc" );

      // make sure none of them changed
      while( ($row = mysqli_fetch_assoc($gameResults)) != null )
      {
        if( isset($_POST["lockTime" . $row["gameID"]]) && $_POST["lockTime" . $row["gameID"]] != $row["lockTime"] )
        {
          runQuery( "call ChangeLockTime(" . $row["gameID"] . ", '" . $_POST["lockTime" . $row["gameID"]] . "')" );
        }
      }

      // check for dupes
      $gameError = "Lock Times Updated!";
    }
?>
