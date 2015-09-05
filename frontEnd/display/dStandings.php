    <div class="mainTable montserrat" id="mainTable">
      <table style="width:100%;">
        <tr>
          <td class="noBorder fjalla" style="width:35%; text-align:left;">
            <span>Showing Standings for <?php echo $_SESSION["showStandingsSeason"]; ?> Season</span>
          </td>
          <td class="noBorder fjalla" style="width:15%; text-align:center;">
<?php
  if( isset($_SESSION["spsID"]) )
  {
    echo "            <button onClick=\"$('html, body').scrollTop($('#myStanding').offset().top);\">Jump to me</button>\n";
  }
?>
          </td>
          <td class="noBorder fjalla" style="width:50%; text-align:right;">
            <form action="." method="post" id="changeStandings">
              <span>Change to</span>
              <select name="showStandingsSplit" onchange="document.getElementById('changeStandings').submit();">
                <option value="overall"<?php echo ($_SESSION["showStandingsSplit"] == "overall") ? " selected" : ""; ?>>Overall Standings</option>
                <option value="conference"<?php echo ($_SESSION["showStandingsSplit"] == "conference") ? " selected" : ""; ?>>Conference Standings</option>
                <option value="division"<?php echo ($_SESSION["showStandingsSplit"] == "division") ? " selected" : ""; ?>>Division Standings</option>
              </select>
              <span>for</span>
              <select name="showStandingsSeason" onchange="document.getElementById('changeStandings').submit();">
<?php
  $results = runQuery( "select distinct(season) as season from SeasonResult order by season" );
  while( ($row = mysqli_fetch_assoc( $results )) != null )
  {
    echo "                <option value=\"" . $row["season"] . "\"" . 
        (($row["season"] == $_SESSION["showStandingsSeason"]) ? " selected" : "") . ">" . $row["season"] . "</option>\n";
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
  include "ShowStandingsTable.php";
?>
      </table>
    </div>
