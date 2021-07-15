<?php
  // see what week they need to be setting
  $poolResults = RunQuery( "select inPlayoffs, firstRoundBye from SeasonResult join Session using (userID) where sessionID=" . 
                           $_SESSION["spsID"] . " and season=(select value from Constants where name='fetchSeason')" );
  $poolResults = (count($poolResults) > 0) ? $poolResults[0] : null;
  $gamesStillTBP = 1;
  $result = RunQuery("select weekNumber, season from Game where lockTime >= now() and status!=19 order by weekNumber limit 1", false);
  $freezeResult = RunQuery("select count(*) as num from FrozenUser join User using (userID) join Session using (userID) " .
                           "where sessionID=" . $_SESSION["spsID"]);
  if( count( $result ) == 0 )
  {
    $gamesStillTBP = 0;
  }
  else
  {
    $result = $result[0];

    if( $result["weekNumber"] < 19 )
    {
      include "MakeRegularSeasonPicksJavascript.php";
    }
    else if( $poolResults["inPlayoffs"] == "N" )
    {
      include "MakeConsolationPicksJavascript.php";
    }
    else if( $result["weekNumber"] < 21 )
    {
      include "MakeWCDivPicksJavascript.php";
    }
    else if( $result["weekNumber"] < 22 )
    {
      include "MakeCCPicksJavascript.php";
    }
    else if( $result["weekNumber"] < 24 )
    {
      include "MakeSuperBowlPicksJavascript.php";
    }
  }
?>
    <div class="mainTable" id="mainTable">
<?php
  if( isset($gamesStillTBP) && $gamesStillTBP == 0 )
  {
    $playoffsPlayed = RunQuery( "select count(*) as num from Game where weekNumber>18 and season=(select value " . 
                                "from Constants where name='fetchSeason') and status not in (1,19)" );
    $gamesLeft = RunQuery( "select count(*) as num from Game where weekNumber>18 and season=(select value " . 
                           "from Constants where name='fetchSeason') and status in (1,19)" );
    if( $poolResults["inPlayoffs"] == "N" && $playoffsPlayed[0]["num"] > 0 )
    {
      echo "      <div style=\"height:100px;\"></div>\n";
      echo "      <div style=\"width:100%; text-align:center; font-size:32px;\">Picks closed for consolation pool</div>\n";
      echo "      <div style=\"height:100px;\"></div>\n";
    }
    else if( $gamesLeft[0]["num"] > 0 )
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
    if( $result["weekNumber"] >= 19 && $poolResults["inPlayoffs"] == "Y" )
    {
      $outOfPlayoffs = RunQuery( "select prevWeek1, prevWeek2, prevWeek3 from PlayoffResult join Session using (userID) where sessionID=" . 
                                 $_SESSION["spsID"] . " and weekNumber=23 and season=(select value " . 
                                "from Constants where name='fetchSeason')" );
    }

    // they're not in the pool for this season
    if( $poolResults == null )
    {
      echo "      <div style=\"height:100px;\"></div>\n";
      echo "      <div style=\"width:100%; text-align:center; font-size:32px;\">You have not entered the pool for this season.</div>\n";
      echo "      <div style=\"height:100px;\"></div>\n";
    }
    // their account is frozen
    else if( isset($freezeResult[0]["num"]) && ($freezeResult[0]["num"] > 0) )
    {
      echo "      <div style=\"height:100px;\"></div>\n";
      echo "      <div style=\"width:100%; text-align:center; font-size:32px;\">Your account has been frozen! Please contact the pool " .
          "commissioner for more information.</div>\n";
      echo "      <div style=\"height:100px;\"></div>\n";
    }
    // the picks for this week aren't open yet
    else if( $openResults["openYet"] == "N" )
    {
      echo "      <div style=\"height:100px;\"></div>\n";
      echo "      <div style=\"width:100%; text-align:center; font-size:32px;\">Picks for next week will open at " . 
          $openResults["openStr"] . "</div>\n";
      echo "      <div style=\"height:100px;\"></div>\n";
    }
    // regular season picks
    else if( $result["weekNumber"] < 19 )
    {
      include "MakeRegularSeasonPicks.php";
    }
    // consolation pool picks
    else if( $poolResults["inPlayoffs"] == "N" )
    {
      if( $result["weekNumber"] == 19 )
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
    else if( ($result["weekNumber"] == 20 && $outOfPlayoffs[0]["prevWeek1"] < 0) || 
             ($result["weekNumber"] == 21 && $outOfPlayoffs[0]["prevWeek2"] < 0) || 
             ($result["weekNumber"] == 23 && $outOfPlayoffs[0]["prevWeek3"] < 0))
    {
      echo "      <div style=\"height:100px;\"></div>\n";
      echo "      <div style=\"width:100%; text-align:center; font-size:32px;\">You have been eliminated from the playoff pool!  Better luck next year!</div>\n";
      echo "      <div style=\"height:100px;\"></div>\n";
    }
    // wild card and divisional rounds
    else if( $result["weekNumber"] < 21 )
    {
      // people with byes
      if( $poolResults["firstRoundBye"] == "Y" && $result["weekNumber"] == 19 )
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
    else if( $result["weekNumber"] < 22 )
    {
      include "MakeCCPicks.php";
    }
    // super bowl
    else if( $result["weekNumber"] < 24 )
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
