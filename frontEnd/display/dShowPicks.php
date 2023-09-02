<?php
  // grab the current week and season
  if( !isset($_SESSION["showPicksSeason"]) )
  {
    $results = RunQuery( "select season from Game join WeekResult using (weekNumber, season) order by gameID desc limit 1" );
    $_SESSION["showPicksSeason"] = $results[0]["season"];
  }
  if( !isset($_SESSION["showPicksWeek"]) )
  {
    $results = RunQuery( "select weekNumber from Game join WeekResult using (weekNumber, season) where status in (1,2,19) " .
                         "or (status >= 3 and date(date_add(now(), interval -56 hour)) <= date(gameTime)) order by gameTime asc limit 1" );
    if( count( $results ) > 0 )
    {
      $_SESSION["showPicksWeek"] = $results[0]["weekNumber"];
    }
    else
    {
      $results = RunQuery( "select weekNumber from Game join PlayoffResult using (weekNumber, season) where status in (1,2,19) " .
                           "or (status >= 3 and date(date_add(now(), interval -56 hour)) <= date(gameTime)) order by gameTime asc limit 1" );
      if( count( $results ) > 0 )
      {
        $_SESSION["showPicksWeek"] = $results[0]["weekNumber"];
      }
      else
      {
        $_SESSION["showPicksWeek"] = (($_SESSION["showPicksSeason"] <= 2020) ? 22 : 23);
      }
    }
  }
  if( !isset($_SESSION["showPicksSplit"]) )
  {
    if( $_SESSION["showPicksWeek"] < (($_SESSION["showPicksSeason"] <= 2020) ? 18 : 19) )
    {
      $_SESSION["showPicksSplit"] = "overall";
    }
    else if( !isset($_SESSION["spsID"]) )
    {
      $_SESSION["showPicksSplit"] = "playoffs";
    }
    else
    {
      $results = RunQuery( "select inPlayoffs from SeasonResult join Session using (userID) where sessionID=" . 
                           $_SESSION["spsID"] . " and season=" . $_SESSION["showPicksSeason"] );
      $_SESSION["showPicksSplit"] = (($results[0]["inPlayoffs"] == "Y") ? "playoffs" : "consolation");
    }
  }
?>
    <div class="mainTable montserrat" id="mainTable">
      <table style="width:100%;">
        <tr>
          <td class="noBorder fjalla" style="width:35%; text-align:left;">
            <span>Showing Picks for <?php
  if( $_SESSION["showPicksSplit"] == "consolation" )
  {
    echo "Consolation Pool";
  }
  else if( $_SESSION["showPicksSplit"] == "playoffs" && $_SESSION["showPicksWeek"] == (($_SESSION["showPicksSeason"] <= 2020) ? 18 : 19) )
  {
    echo "Wild Card Round";
  }
  else if( $_SESSION["showPicksSplit"] == "playoffs" && $_SESSION["showPicksWeek"] == (($_SESSION["showPicksSeason"] <= 2020) ? 19 : 20) )
  {
    echo "Divisional Round";
  }
  else if( $_SESSION["showPicksSplit"] == "playoffs" && $_SESSION["showPicksWeek"] == (($_SESSION["showPicksSeason"] <= 2020) ? 20 : 21) )
  {
    echo "Conference Championship";
  }
  else if( $_SESSION["showPicksSplit"] == "playoffs" && $_SESSION["showPicksWeek"] == (($_SESSION["showPicksSeason"] <= 2020) ? 22 : 23) )
  {
    echo "Super Bowl";
  }
  else
  {
    echo "Week " . $_SESSION["showPicksWeek"]; 
  }
?> of <?php echo $_SESSION["showPicksSeason"]; ?> Season</span>
          </td>
          <td class="noBorder fjalla" style="width:15%; text-align:center;">
            <table>
              <tr><td>
                <form action="helpers/changeAccountDetails.php" method="post" id="cbmForm" target="taskWindow">
                  <input type="hidden" name="task" value="cbm" />
                  <input type="hidden" name="acctCBM" id="cbmVal" value="<?php echo (($_SESSION["cbm"] ?? "N") == "Y") ? "N" : "Y"; ?>" />
                  <button onClick="$('#cbmForm').submit();">Alternate Colors</button>
                </form>
              </td></tr>
<?php
  $logosHidden = false;
  if( isset($_SESSION["spsID"]) )
  {
    $logosHidden = (isset($_SESSION["spHideLogos"]) && $_SESSION["spHideLogos"] == "TRUE");
?>
              <tr>
                <td class="noBorder"><button onClick="$('html, body').scrollTop($('#myPicks').offset().top);">Jump to me</button></td>
              </tr>
              <tr>
                <td class="noBorder">
                  <button onClick="document.getElementById('hideLogosForm').submit();"><?php echo ($logosHidden ? "Show" : "Hide"); ?> logos</button>
                  <form action="helpers/hideLogos.php" method="post" id="hideLogosForm" target="taskWindow">
                    <input type="hidden" name="doIt" value="<?php echo ($logosHidden ? "false" : "true"); ?>" />
                  </form>
                </td>
              </tr>
<?php
  }
?>
            </table>
          </td>
          <td class="noBorder fjalla" style="width:50%; text-align:right;">
            <form action="." method="post" id="changeShowPicksWeek">
              <span>Change to</span>
              <select name="showPicksSplit" onchange="document.getElementById('changeShowPicksWeek').submit();">
                <option value="overall"<?php echo ($_SESSION["showPicksSplit"] == "overall") ? " selected" : ""; ?>>Overall Standings</option>
                <option value="conference"<?php echo ($_SESSION["showPicksSplit"] == "conference") ? " selected" : ""; ?>>Conference Standings</option>
                <option value="division"<?php echo ($_SESSION["showPicksSplit"] == "division") ? " selected" : ""; ?>>Division Standings</option>
                <option value="consolation"<?php
  $seasonResult = RunQuery( "select value from Constants where name='fetchSeason'" );
  $weekResult = RunQuery( "select value from Constants where name='fetchWeek'" );
  
  echo ($_SESSION["showPicksSplit"] == "consolation") ? " selected" : ""; 
  if( $_SESSION["showPicksSeason"] >= $seasonResult[0]["value"] && $weekResult[0]["value"] < (($_SESSION["showPicksSeason"] <= 2020) ? 18 : 19) )
  {
    echo " disabled";
  }
?>>Consolation Standings</option>
                <option value="playoffs"<?php
  echo ($_SESSION["showPicksSplit"] == "playoffs") ? " selected" : ""; 
  if( $_SESSION["showPicksSeason"] >= $seasonResult[0]["value"] && $weekResult[0]["value"] < (($_SESSION["showPicksSeason"] <= 2020) ? 18 : 19) )
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
  echo "              <select name=\"showPicksWeek\" onchange=\"document.getElementById('changeShowPicksWeek').submit();\">\n";
  if( $_SESSION["showPicksSplit"] == "playoffs" || $_SESSION["showPicksSplit"] == "consolation" )
  {
    echo "                <option value=\"" . (($_SESSION["showPicksSeason"] <= 2020) ? "18" : "19") . "\"" . 
         (((($_SESSION["showPicksSeason"] <= 2020) ? 18 : 19) == $_SESSION["showPicksWeek"]) ? " selected" : "") . 
          ">Wild Card Round</option>\n";
    echo "                <option value=\"" . (($_SESSION["showPicksSeason"] <= 2020) ? "19" : "20") . "\"" . 
         (((($_SESSION["showPicksSeason"] <= 2020) ? 19 : 20) == $_SESSION["showPicksWeek"]) ? " selected" : "") . 
         (($_SESSION["showPicksSeason"] >= $seasonResult[0]["value"] && $weekResult[0]["value"] < (($_SESSION["showPicksSeason"] <= 2020) ? 19 : 20) ) ? " disabled" : "") .
         ">Divisional Round</option>\n";
    echo "                <option value=\"" . (($_SESSION["showPicksSeason"] <= 2020) ? "20" : "21") . "\"" . 
         (((($_SESSION["showPicksSeason"] <= 2020) ? 20 : 21) == $_SESSION["showPicksWeek"]) ? " selected" : "") . 
         (($_SESSION["showPicksSeason"] >= $seasonResult[0]["value"] && $weekResult[0]["value"] < (($_SESSION["showPicksSeason"] <= 2020) ? 20 : 21) ) ? " disabled" : "") .
         ">Conference Championship</option>\n";
    echo "                <option value=\"" . (($_SESSION["showPicksSeason"] <= 2020) ? "22" : "23") . "\"" . 
         (((($_SESSION["showPicksSeason"] <= 2020) ? 22 : 23) == $_SESSION["showPicksWeek"]) ? " selected" : "") . 
         (($_SESSION["showPicksSeason"] >= $seasonResult[0]["value"] && $weekResult[0]["value"] < (($_SESSION["showPicksSeason"] <= 2020) ? 22 : 23) ) ? " disabled" : "") .
         ">Super Bowl</option>\n";
  }
  else
  {
    for( $i=1; $i<(($_SESSION["showPicksSeason"] <= 2020) ? 18 : 19); $i++ )
    {
      echo "                <option value=\"" . $i . "\"" . (($i == $_SESSION["showPicksWeek"]) ? " selected" : "") . 
          ((($_SESSION["showPicksSeason"] >= $seasonResult[0]["value"]) && ($i > $weekResult[0]["value"])) ? " disabled" : "") . 
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
              <select name="showPicksSeason" onchange="document.getElementById('changeShowPicksWeek').submit();">
<?php
  $results = RunQuery( "select distinct(season) as season from SeasonResult order by season" );
  foreach( $results as $row )
  {
    echo "                <option value=\"" . $row["season"] . "\"" . 
        (($row["season"] == $_SESSION["showPicksSeason"]) ? " selected" : "") . ">" . $row["season"] . "</option>\n";
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
    include "ShowConsolationPicksTable.php";
  }
  else if( $_SESSION["showPicksSplit"] == "playoffs" )
  {
    include "ShowPlayoffPicksTable.php";
  }
  else
  {
    include "ShowWeekPicksTable.php";
  }
?>
      </table>
    </div>
