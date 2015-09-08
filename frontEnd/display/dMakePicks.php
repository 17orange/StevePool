<?php
  // see what week they need to be setting
  $poolResults = mysqli_fetch_assoc( RunQuery( "select inPlayoffs, firstRoundBye from SeasonResult join Session using (userID) " . 
                                               "where sessionID=" . $_SESSION["spsID"] . " and season=(select value from Constants " .
                                               "where name='fetchSeason')" ) );
  $gamesStillTBP = 1;
  $result = RunQuery("select weekNumber, season from Game where lockTime >= now() and status!=19 order by weekNumber limit 1");
  if( mysqli_num_rows( $result ) == 0 )
  {
    $gamesStillTBP = 0;
  }
  else
  {
    $result = mysqli_fetch_assoc( $result );

    if( $result["weekNumber"] < 18 )
    {
      include "MakeRegularSeasonPicksJavascript.php";
    }
    else if( $poolResults["inPlayoffs"] == "N" )
    {
      include "MakeConsolationPicksJavascript.php";
    }
    else if( $result["weekNumber"] < 20 )
    {
      include "MakeWCDivPicksJavascript.php";
    }
    else if( $result["weekNumber"] < 21 )
    {
      include "MakeCCPicksJavascript.php";
    }
    else if( $result["weekNumber"] < 23 )
    {
      include "MakeSuperBowlPicksJavascript.php";
    }
  }
?>
    <div class="mainTable" id="mainTable">
<?php
  if( $gamesStillTBP == 0 )
  {
    $playoffsPlayed = mysqli_fetch_assoc( RunQuery( "select count(*) as num from Game where weekNumber>17 and season=(select value " . 
                                                    "from Constants where name='fetchSeason') and status not in (1,19)" ) );
    $gamesLeft = mysqli_fetch_assoc( RunQuery( "select count(*) as num from Game where weekNumber>17 and season=(select value " . 
                                               "from Constants where name='fetchSeason') and status in (1,19)" ) );
    if( $poolResults["inPlayoffs"] == "N" && $playoffsPlayed["num"] > 0 )
    {
      echo "      <div style=\"height:100px;\"></div>\n";
      echo "      <div style=\"width:100%; text-align:center; font-size:32px;\">Picks closed for consolation pool</div>\n";
      echo "      <div style=\"height:100px;\"></div>\n";
    }
    else if( $gamesLeft["num"] > 0 )
    {
      echo "      <div style=\"height:100px;\"></div>\n";
      echo "      <div style=\"width:100%; text-align:center; font-size:32px;\">Upcoming matchups yet to be determined</div>\n";
      echo "      <div style=\"height:100px;\"></div>\n";
    }
    else
    {
      echo "      <div style=\"height:100px;\"></div>\n";
      echo "      <div style=\"width:100%; text-align:center; font-size:32px;\">Picks are closed for the season.  See you next year!</div>\n";
      echo "      <div style=\"height:100px;\"></div>\n";
    }
  }
  else
  {
    // see if they got eliminated already
    if( $result["weekNumber"] >= 18 && $poolResults["inPlayoffs"] == "Y" )
    {
      $outOfPlayoffs = mysqli_fetch_assoc( RunQuery( "select prevWeek1, prevWeek2, prevWeek3 from PlayoffResult " . 
                                                     "join Session using (userID) where sessionID=" . 
                                                     $_SESSION["spsID"] . " and weekNumber=22") );
    }

    // they're not in the pool for this season
    if( $poolResults == null )
    {
      echo "      <div style=\"height:100px;\"></div>\n";
      echo "      <div style=\"width:100%; text-align:center; font-size:32px;\">You have not entered the pool for this season.</div>\n";
      echo "      <div style=\"height:100px;\"></div>\n";
    }
    // the picks for this week aren't open yet
    else if( $results["openYet"] == "N" )
    {
      echo "      <div style=\"height:100px;\"></div>\n";
      echo "      <div style=\"width:100%; text-align:center; font-size:32px;\">Picks for next week will open at " . 
          $results["openStr"] . "</div>\n";
      echo "      <div style=\"height:100px;\"></div>\n";
    }
    // regular season picks
    else if( $result["weekNumber"] < 18 )
    {
      include "MakeRegularSeasonPicks.php";
    }
    // consolation pool picks
    else if( $poolResults["inPlayoffs"] == "N" )
    {
      if( $result["weekNumber"] == 18 )
      {
        include "MakeConsolationPicks.php";
      }
      else 
      {
        echo "      <div style=\"height:100px;\"></div>\n";
        echo "      <div style=\"width:100%; text-align:center; font-size:32px;\">Picks closed for consolation pool</div>\n";
        echo "      <div style=\"height:100px;\"></div>\n";
      }
    }
    // they've been eliminated
    else if( ($result["weekNumber"] == 19 && $outOfPlayoffs["prevWeek1"] < 0) || 
             ($result["weekNumber"] == 20 && $outOfPlayoffs["prevWeek2"] < 0) || 
             ($result["weekNumber"] == 22 && $outOfPlayoffs["prevWeek3"] < 0))
    {
      echo "      <div style=\"height:100px;\"></div>\n";
      echo "      <div style=\"width:100%; text-align:center; font-size:32px;\">You have been eliminated from the playoff pool!  Better luck next year!</div>\n";
      echo "      <div style=\"height:100px;\"></div>\n";
    }
    // wild card and divisional rounds
    else if( $result["weekNumber"] < 20 )
    {
      // people with byes
      if( $poolResults["firstRoundBye"] == "Y" and $result["weekNumber"] == 18 )
      {
        echo "      <div style=\"height:100px;\"></div>\n";
        echo "      <div style=\"width:100%; text-align:center; font-size:32px;\">You have earned a first round bye!  Enjoy the week off!</div>\n";
        echo "      <div style=\"height:100px;\"></div>\n";
      }
      // people who need to pick
      else
      {
        include "MakeWCDivPicks.php";
      }
    }
    // conference championships
    else if( $result["weekNumber"] < 21 )
    {
      include "MakeCCPicks.php";
    }
    // super bowl
    else if( $result["weekNumber"] < 23 )
    {
      include "MakeSuperBowlPicks.php";
    }
  }

  if( isset($showSuccess) && $showSuccess )
  {
?>
  <script type="text/javascript">
    $('#pickConfirmDialog').slideToggle('fast');
  </script>
<?php
  }
  else if( isset($showFailure) && $showFailure )
  {
?>
  <script type="text/javascript">
    $('#pickErrorDialog').slideToggle('fast');
  </script>
<?php
  }

  if( isset($_POST["windowScrollPos"]) )
  {
?>
  <script type="text/javascript">
    $('html,body').scrollTop(<?php echo $_POST["windowScrollPos"]; ?>);
  </script>
<?php
  }
?>
    </div>