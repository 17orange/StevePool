    <span style="font-size:18px; font-weight:bold;">Manage Picks for <?php echo $thisSeason; ?> Season</span>
<?php
    $userSet = isset($_SESSION["picksUserID"]);
    $weekSet = isset($_SESSION["picksWeek"]);

    // grab all user info
    $userDataResults = runQuery( "select * from User left join SeasonResult using (userID) where season=" . 
                                 $thisSeason . " or season is null order by lastName asc, firstName asc" );
?>
    <br/>
    <span style="font-size:18px; font-weight:bold;">Picks for User</span>
    <br/>
    <form action="." method="post">
      <span>Selected User</span>
      <input type="hidden" name="adminTask" value="managePicks" />
      <select name="selectedUserID">
<?php
    while( ($userRow = mysqli_fetch_assoc( $userDataResults )) != null )
    {
      echo "        <option value=\"" . $userRow["userID"] . "\"" . 
           (($userSet && ($_SESSION["picksUserID"] == $userRow["userID"])) ? " selected" : "") . ">" . 
           $userRow["firstName"] . " " . $userRow["lastName"] . "</option>\n";
    }
?>
      </select>
      <br/>
      <span>Selected Week</span>
      <select name="selectedWeek">
<?php
    for( $i=1; $i<=17; $i++ )
    {
      echo "        <option value=\"" . $i . "\"" . 
           (($weekSet && ($_SESSION["picksWeek"] == $i)) ? " selected" : "") . ">Week " . $i . "</option>\n";
    }
?>
      </select>
      <br/>
      <input type="submit" value="Select This User And Week" />
    </form>
    <br/>
    <br/>
<?php
    if( $userSet && $weekSet )
    {
      // grab their info
      $thesePicksData = runQuery( "select gameID, homeTeam, awayTeam, winner, points from Pick join Game using (gameID) " . 
                                  "where userID=" . $_SESSION["picksUserID"] . " and weekNumber=" . $_SESSION["picksWeek"] . 
                                  " and season=" . $thisSeason . " order by points desc");

      // see who is on bye this week
      $byes = array();
      $byeData = runQuery( "select teamID from Team where teamID not in (select homeTeam from Game where season=" . $thisSeason . 
                           " and weekNumber=" . $_SESSION["picksWeek"] . ") and teamID not in (select awayTeam from Game " . 
                           "where season=" . $thisSeason . " and weekNumber=" . $_SESSION["picksWeek"] . ")" );
      while( ($thisBye = mysqli_fetch_assoc( $byeData )) != null )
      {
        $byes[count($byes)] = $thisBye["teamID"];
      }
?>
    <span style="font-size:18px; font-weight:bold;">Current Picks</span>
    <br/>
    <form action="." method="post">
      <input type="hidden" name="adminTask" value="managePicks" />
      <table>
        <tr>
          <td style="font-weight:bold;">Matchup</td>
          <td style="font-weight:bold;">Winner</td>
          <td style="font-weight:bold;">Points</td>
        </tr>
<?php
      $gameCount = 1;
      while( ($game = mysqli_fetch_assoc( $thesePicksData )) != null )
      {
?>
        <tr>
          <td><?php echo $game["awayTeam"] . "@" . $game["homeTeam"]; ?></td>
          <td>
            <input name="picksGame<?php echo $gameCount; ?>ID" type="hidden" value="<?php echo $game["gameID"]; ?>" />
            <select name="picksGame<?php echo $gameCount; ?>Winner">
<?php
        echo "              <option value=\"" . $game["homeTeam"] . "\"" . 
            (($game["homeTeam"] == $game["winner"]) ? " selected" : "") . ">" . $game["homeTeam"] . "</option>\n";
        echo "              <option value=\"null\"" .  (("" == $game["winner"]) ? " selected" : "") . ">No pick</option>\n";
        echo "              <option value=\"" . $game["awayTeam"] . "\"" . 
            (($game["awayTeam"] == $game["winner"]) ? " selected" : "") . ">" . $game["awayTeam"] . "</option>\n";
?>
            </select>
          </td>
          <td>
            <select name="picksGame<?php echo $gameCount; ?>Points">
<?php
        for( $i=16; $i>(count($byes) / 2); $i-- )
        {
          echo "              <option value=\"" . $i . "\"" .  (($i == $game["points"]) ? " selected" : "") . 
              ">" . $i . "</option>\n";
        }
?>
            </select>
          </td>
        </tr>
<?php
        $gameCount++;
      }

      // see what they have as the tiebreaker
      $thisTB = mysqli_fetch_assoc( runQuery( "select tieBreaker from WeekResult where userID=" . $_SESSION["picksUserID"] . 
                                              " and weekNumber=" . $_SESSION["picksWeek"] . " and season=" . $thisSeason ));
?>
        <tr>
          <td>MNF Score</td>
          <td colspan=2><input name="picksMNF" value="<?php echo $thisTB["tieBreaker"]; ?>" /></td>
        </tr>
        <tr>
          <td colspan=3>
            <input type="submit" value="Set Picks" />
          </td>
        </tr>
        <tr>
          <td colspan=3>
            <span style="color:#FF0000; font-size:18px; font-weight:bold;"><?php echo isset($managePicksError) ? $managePicksError : ""; ?></span>
          </td>
        </tr>
      </table>
    </form>
<?php
    }
?>

