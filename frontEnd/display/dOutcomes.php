<?php
  // grab the current week and season
  if( !isset($_SESSION["showPicksWeek"]) )
  {
    $results = runQuery( "select weekNumber from Game join WeekResult using (weekNumber, season) where status < 3 order by gameID limit 1" );
    if( mysqli_num_rows( $results ) > 0 )
    {
      $results = mysqli_fetch_assoc( $results );
      $_SESSION["showPicksWeek"] = $results["weekNumber"];
    }
    else
    {
      $_SESSION["showPicksWeek"] = 22;
    }
  }
  if( !isset($_SESSION["showPicksSeason"]) )
  {
    $results = mysqli_fetch_assoc( runQuery( "select season from Game join WeekResult using (weekNumber, season) order by gameID desc limit 1" ) );
    $_SESSION["showPicksSeason"] = $results["season"];
  }
  if( !isset($_SESSION["showPicksSplit"]) )
  {
    if( $_SESSION["showPicksWeek"] < 18 )
    {
      $_SESSION["showPicksSplit"] = "overall";
    }
    else if( !isset($_SESSION["spsID"]) )
    {
      $_SESSION["showPicksSplit"] = "playoffs";
    }
    else
    {
      $results = mysqli_fetch_assoc( runQuery( "select inPlayoffs from SeasonResult join Session using (userID) " . 
                                               "where sessionID=" . $_SESSION["spsID"] . " and season=" . 
                                               $_SESSION["showPicksSeason"] ) );
      $_SESSION["showPicksSplit"] = (($results["inPlayoffs"] == "Y") ? "playoffs" : "consolation");
    }
  }


  // see whether we should show the buttons
  $gamesToGo = mysqli_fetch_assoc( runQuery( "select count(*) as num from Game where season=" . $_SESSION["showPicksSeason"] . 
                                             " and weekNumber=" . $_SESSION["showPicksWeek"] . " and status in (1,2) " ) );
?>
    <div class="mainTable montserrat" id="mainTable">
      <table style="width:100%;">
        <tr>
          <td class="noBorder fjalla" style="width:35%; text-align:left;">
            <span>Showing Possibilities for <?php
  if( $_SESSION["showPicksSplit"] == "consolation" )
  {
    echo "Consolation Pool";
  }
  else if( $_SESSION["showPicksSplit"] == "playoffs" && $_SESSION["showPicksWeek"] == 18 )
  {
    echo "Wild Card Round";
  }
  else if( $_SESSION["showPicksSplit"] == "playoffs" && $_SESSION["showPicksWeek"] == 19 )
  {
    echo "Divisional Round";
  }
  else if( $_SESSION["showPicksSplit"] == "playoffs" && $_SESSION["showPicksWeek"] == 20 )
  {
    echo "Conference Championship";
  }
  else if( $_SESSION["showPicksSplit"] == "playoffs" && $_SESSION["showPicksWeek"] == 22 )
  {
    echo "Super Bowl";
  }
  else
  {
    echo "Week " . $_SESSION["showPicksWeek"]; 
  }
?> of <?php echo $_SESSION["showPicksSeason"]; ?> Season</span>
          </td>
          <td class="noBorder fjalla" style="width:20%; text-align:center;">
<?php
  if( isset($_SESSION["spsID"]) )
  {
    $logosHidden = (isset($_SESSION["spHideLogos"]) && $_SESSION["spHideLogos"] == "TRUE");
?>
            <table>
              <tr>
                <td class="noBorder"><button onClick="$('html, body').scrollTop($('#myPicks').offset().top);">Jump to me</button></td>
                <td class="noBorder"><button onClick="ReloadPage('best');">Best Outcome</button></td>
              </tr><tr>
                <td class="noBorder">
                  <button onClick="document.getElementById('hideLogosForm').submit();"><?php echo ($logosHidden ? "Show" : "Hide"); ?> logos</button>
                  <form action="helpers/hideLogos.php" method="post" id="hideLogosForm" target="taskWindow">
                    <input type="hidden" name="doIt" value="<?php echo ($logosHidden ? "false" : "true"); ?>" />
                  </form>
                </td>
                <td class="noBorder"><button onClick="ReloadPage('actual');">Actual Results</button></td>
              </tr><tr>
                <td class="noBorder"></td>
                <td class="noBorder"><button onClick="ReloadPage('worst');">Worst Outcome</button></td>
              </tr>
            </table>
<?php
  }
?>
          </td>
          <td class="noBorder fjalla" style="width:45%; text-align:right;">
            <form action="." method="post" id="changeOutcomeWeek">
              <span>Change to</span>
              <select name="showPicksSplit" onchange="document.getElementById('changeOutcomeWeek').submit();">
                <option value="overall"<?php echo ($_SESSION["showPicksSplit"] == "overall") ? " selected" : ""; ?>>Overall Standings</option>
                <option value="conference"<?php echo ($_SESSION["showPicksSplit"] == "conference") ? " selected" : ""; ?>>Conference Standings</option>
                <option value="division"<?php echo ($_SESSION["showPicksSplit"] == "division") ? " selected" : ""; ?>>Division Standings</option>
                <option value="consolation"<?php
  $seasonResult = mysqli_fetch_assoc( runQuery( "select value from Constants where name='fetchSeason'" ) );
  $weekResult = mysqli_fetch_assoc( runQuery( "select value from Constants where name='fetchWeek'" ) );
  
  echo ($_SESSION["showPicksSplit"] == "consolation") ? " selected" : ""; 
  if( $_SESSION["showPicksSeason"] >= $seasonResult["value"] && $weekResult["value"] < 18 )
  {
    echo " disabled";
  }
?>>Consolation Standings</option>
                <option value="playoffs"<?php
  echo ($_SESSION["showPicksSplit"] == "playoffs") ? " selected" : ""; 
  if( $_SESSION["showPicksSeason"] >= $seasonResult["value"] && $weekResult["value"] < 18 )
  {
    echo " disabled";
  }
?>>Playoff Standings</option>
              </select>
<?php
  // div to hide this
  if( $_SESSION["showPicksSplit"] == "consolation" )
  {
    echo "<div style=\"position: absolute; overflow: hidden; clip: rect(0 0 0 0); height: 1px; width: 1px; " . 
         "margin: -1px; padding: 0; border: 0;\">\n";
  }

  // week picker
  echo "              <span>for" . (($_SESSION["showPicksSplit"] == "playoffs") ? "" : " Week") . "</span>\n";
  echo "              <select name=\"showPicksWeek\" onchange=\"document.getElementById('changeOutcomeWeek').submit();\">\n";
  if( $_SESSION["showPicksSplit"] == "playoffs" || $_SESSION["showPicksSplit"] == "consolation" )
  {
    echo "                <option value=\"18\"" . ((18 == $_SESSION["showPicksWeek"]) ? " selected" : "") . 
          ">Wild Card Round</option>\n";
    echo "                <option value=\"19\"" . ((19 == $_SESSION["showPicksWeek"]) ? " selected" : "") . 
         (($_SESSION["showPicksSeason"] >= $seasonResult["value"] && $weekResult["value"] < 19 ) ? " disabled" : "") .
         ">Divisional Round</option>\n";
    echo "                <option value=\"20\"" . ((20 == $_SESSION["showPicksWeek"]) ? " selected" : "") . 
         (($_SESSION["showPicksSeason"] >= $seasonResult["value"] && $weekResult["value"] < 20 ) ? " disabled" : "") .
         ">Conference Championship</option>\n";
    echo "                <option value=\"22\"" . ((22 == $_SESSION["showPicksWeek"]) ? " selected" : "") . 
         (($_SESSION["showPicksSeason"] >= $seasonResult["value"] && $weekResult["value"] < 22 ) ? " disabled" : "") .
         ">Super Bowl</option>\n";
  }
  else
  {
    for( $i=1; $i<18; $i++ )
    {
      echo "                <option value=\"" . $i . "\"" . (($i == $_SESSION["showPicksWeek"]) ? " selected" : "") . 
          ((($_SESSION["showPicksSeason"] >= $seasonResult["value"]) && ($i > $weekResult["value"])) ? " disabled" : "") . 
          ">" . $i . "</option>\n";
    }
  }
  echo "              </select>\n";

  // end the hidden section
  if( $_SESSION["showPicksSplit"] == "consolation" )
  {
    echo "</div>\n";
  }
?>
              <span>of</span>
              <select name="showPicksSeason" onchange="document.getElementById('changeOutcomeWeek').submit();">
<?php
  $results = runQuery( "select distinct(season) as season from SeasonResult order by season" );
  while( ($row = mysqli_fetch_assoc( $results )) != null )
  {
    echo "                <option value=\"" . $row["season"] . "\"" . (($row["season"] == $_SESSION["showPicksSeason"]) ? " selected" : "") . 
        ((($row["season"] >= $seasonResult["value"]) && ($_SESSION["showPicksWeek"] > $weekResult["value"])) ? " disabled" : "") . 
        ">" . $row["season"] . "</option>\n";
  }
?>
              </select>
            </form>
          </td>
        </tr>
      </table>
      <br />
      <table class="reloadableTable" id="reloadableTable">
<?php
  if( $_SESSION["showPicksSplit"] == "consolation" )
  {
    include "ShowConsolationPossibilitiesTable.php";
  }
  else if( $_SESSION["showPicksSplit"] == "playoffs" )
  {
    include "ShowPlayoffPossibilitiesTable.php";
  }
  else
  {
    include "ShowWeekPossibilitiesTable.php";
  }
?>
      </table>
    </div>
