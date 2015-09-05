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
    $results = mysqli_fetch_assoc( runQuery( "select season from Game join WeekResult using (weekNumber, season) order by gameID desc limit 1" ) );
    $_SESSION["showStandingsSeason"] = $results["season"];
  }
  if( !isset($_SESSION["showStandingsSplit"]) )
  {
    $_SESSION["showStandingsSplit"] = "overall";
  }
?>
