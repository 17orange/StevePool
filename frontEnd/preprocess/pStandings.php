<?php
  // see if they changed to a different season
  if( isset($_POST["showStandingsSeason"]) )
  {
    $_SESSION["showStandingsSeason"] = $_POST["showStandingsSeason"];
  }
  if( isset($_POST["showStandingsSplit"]) )
  {
    $_SESSION["showStandingsSplit"] = $_POST["showStandingsSplit"];
  }

  // if they dont have anything set, default to now
  if( !isset($_SESSION["showStandingsSeason"]) )
  {
    $results = RunQuery( "select season from Game join WeekResult using (weekNumber, season) order by gameID desc limit 1" );
    $_SESSION["showStandingsSeason"] = $results[0]["season"];
  }
  if( !isset($_SESSION["showStandingsSplit"]) )
  {
    $_SESSION["showStandingsSplit"] = "overall";
  }
?>
