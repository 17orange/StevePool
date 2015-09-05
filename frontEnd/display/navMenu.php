    <div class="mainHeader">
      <img class="poolLogo" src="includes/poolLogo.png">
      <div class="deadlineInfo">
        <table>
<?php
  $eraResults = runQuery( "select if(now() > (select value from Constants where name='preseasonEnd'), 'SUCCESS', if(now() > (select value from Constants where name='preseasonStart'), 'Preseason', 'Offseason')) as heading");
  $era = mysqli_fetch_assoc($eraResults);

  $results = runQuery( "select season, weekNumber, if(now()>=openTime, 'Y', 'N') as openYet, date_Format(openTime, '%l:%i%p %W') as openStr " . 
                       "from Game join WeekDeadline using (season, weekNumber) where status < 3 order by gameID asc limit 1" );
  if( mysqli_num_rows($results) == 0 )
  {
    $results = mysqli_fetch_assoc( runQuery( "select season, weekNumber, if(now()>=openTime, 'Y', 'N') as openYet, " . 
                                             "date_Format(openTime, '%l:%i%p %W') as openStr from Game " . 
                                             "join WeekDeadline using (season, weekNumber) order by gameID desc limit 1" ) );
  }
  else
  {
    $results = mysqli_fetch_assoc( $results );
  }

  if( $era["heading"] == "SUCCESS" )
  {
    if( $results["openYet"] == "N" )
    {
      echo "          <tr><td class='noBorder'>Picks for next week will open at " . $results["openStr"] . "</td></tr>\n";
    }
    else
    {
      echo "          <tr><td class='noBorder'>Current Week Deadlines:</td></tr>\n";

      $lockResults = mysqli_fetch_assoc( runQuery( "select count(*) as num from Game where season=" . $results["season"] . 
                                                   " and weekNumber=" . $results["weekNumber"] . " and lockTime <= now()" ));
      if( $lockResults["num"] > 0 )
      {
        echo "          <tr><td class='noBorder'>" . $lockResults["num"] . " game" . (($lockResults["num"] == 1) ? " is" : "s are") . 
            " locked!</td></tr>\n";
      }

      $lockResults = runQuery( "select count(*) as num, date_Format(lockTime, if(lockTime<date_add(now(), interval 1 week), '%l:%i%p %W', " . 
                               "'%l:%i%p %b %e')) as lockStr from Game where season=" . $results["season"] . " and weekNumber=" . 
                               $results["weekNumber"] . " and lockTime > now() group by lockTime order by lockTime" );
      while( ($thisLock = mysqli_fetch_assoc($lockResults)) != null )
      {
        echo "          <tr><td class='noBorder'>" . $thisLock["num"] . " game" . (($thisLock["num"] == 1) ? "" : "s") . " lock" . 
            (($thisLock["num"] == 1) ? "s" : "") . " at " . $thisLock["lockStr"] . "</td></tr>\n";
      }
    }
  }
?>
        </table>
      </div>
      <span class="loggedInInfo"><?php
  if( isset($_SESSION["spsID"]) && isset($_SESSION["playerName"]) ) {
    echo "Logged in as: " . $_SESSION["playerName"];
  }
?></span>
      <span class="mainTitle">Steve's <?php echo $results["season"]; ?> NFL Office Pool</span>
      <span class="mainSubtitle"><?php
  if( $era["heading"] != "SUCCESS" )
  {
    echo $era["heading"];
  }
  else if( $results["weekNumber"] == 18 )
  {
    echo "Wild Card Round";
  }
  else if( $results["weekNumber"] == 19 )
  {
    echo "Divisional Round";
  }
  else if( $results["weekNumber"] == 20 )
  {
    echo "Conference Championship";
  }
  else if( $results["weekNumber"] == 22 )
  {
    echo "Super Bowl";
  }
  else
  {
    echo "Week " . $results["weekNumber"];
  }
?></span>
    </div>
    <div id="navMenu" class="navMenuDiv">
      <ul class="navMenu">
        <li class="navLi"><?php
  if($_SESSION["pageName"] != "standings") {
    echo "<a class=\"wash\" href=\"./?newPage=standings\">";
  }
  echo "<span class=\"navButton";
  if($_SESSION["pageName"] == "standings") {
    echo "Active";
  }
  echo "\">Standings</span>";
  if($_SESSION["pageName"] != "standings") {
    echo "</a>";
  }
  echo "</li>\n";

  if( isset($_SESSION["spsID"]) )
  {
?>
        <li class="navLi"><?php
    if($_SESSION["pageName"] != "makePicks") {
      echo "<a class=\"wash\" href=\"./?newPage=makePicks\">";
    }
    echo "<span class=\"navButton";
    if($_SESSION["pageName"] == "makePicks") {
      echo "Active";
    }
    echo "\">Make Picks</span>";
    if($_SESSION["pageName"] != "makePicks") {
      echo "</a>";
    }
    echo "</li>\n";
  }
?>
        <li class="navLi"><?php
  if($_SESSION["pageName"] != "showPicks") {
    echo "<a class=\"wash\" href=\"./?newPage=showPicks\">";
  }
  echo "<span class=\"navButton";
  if($_SESSION["pageName"] == "showPicks") {
    echo "Active";
  }
  echo "\">Show Picks</span>";
  if($_SESSION["pageName"] != "showPicks") {
    echo "</a>";
  }
  echo "</li>\n";

  if( isset($_SESSION["spsID"]) )
  {
?>
        <li class="navLi"><?php
    if($_SESSION["pageName"] != "possibleOutcomes") {
      echo "<a class=\"wash\" href=\"./?newPage=possibleOutcomes\">";
    }
    echo "<span class=\"navButton";
    if($_SESSION["pageName"] == "possibleOutcomes") {
      echo "Active";
    }
    echo "\">Possibilities</span>";
    if($_SESSION["pageName"] != "possibleOutcomes") {
      echo "</a>";
    }
    echo "</li>\n";
  }
?>
        <li class="navLi"<?php
  if( isset($_SESSION["spsID"]) )
  {
    echo " onclick=\"$('#accountDialog').slideToggle('fast');\">" . 
         "<span class=\"navButton\" style=\"cursor:pointer;\">My Account</span></li>\n";
    echo "        <li class=\"navLi\"><a class=\"wash\" href=\"helpers/logout.php\" target=\"taskWindow\">" . 
         "<span class=\"navButton\" style=\"cursor:pointer;\">Logout</span></a>";
  }
  else
  {
    echo " onclick=\"$('#loginDialog').slideToggle('fast');\">" . 
         "<span class=\"navButton\" style=\"cursor:pointer;\">Sign In</span>";
  }
?></li>
      </ul>
    </div>
    <div class="clearfix"></div>

