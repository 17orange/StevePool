<?php
    // default to week 1
    if( !isset($_SESSION["manageGameWeek"]) )
    {
      $_SESSION["manageGameWeek"] = 1;
    }
?>
    <span style="font-size:18px; font-weight:bold;">Manage Games for <?php echo $thisSeason; ?> Season</span>
    <form action="." method="post">
      <input type="hidden" name="adminTask" value="manageGames" />
<?php
    for( $i=1; $i<19; $i+=1 )
    {
      echo "      <input type=\"radio\" name=\"manageGameWeek\" value=\"" . $i . "\" " .
           (($_SESSION["manageGameWeek"] == $i) ? "checked " : "") . "/><span>" . $i . "</span><br/>\n";
    }
?>
      <input type="submit" value="Change Week" />
    </form>
<?php
    // show the feedback
    if( isset($weekError) && $weekError != "" )
    {
      echo "    <span style=\"color:#FF0000; font-weight:bold;\">" . $weekError . "</span>\n";
    }
?>
    <br/><br/>
    <span style="font-size:18px; font-weight:bold;">Week <?php echo $_SESSION["manageGameWeek"]; ?> Games</span>
    <form action="." method="post">
      <input type="hidden" name="adminTask" value="manageGames" />
      <table>
<?php
    // grab the games in the current week
    $gameResults = runQuery( "select gameID, gameTime, lockTime, T1.nickname as home, T2.nickname as away " .
                             "from Game join Team as T1 on (homeTeam=T1.teamID) join Team as T2 on " .
                             "(awayTeam=T2.teamID) where season=" . $thisSeason . " and weekNumber=" .
                             $_SESSION["manageGameWeek"] . " order by gameTime asc, gameID asc" );

    echo "        <tr>\n";
    echo "          <td style=\"font-weight:bold\">Matchup</td>\n";
    echo "          <td style=\"font-weight:bold\">Game Time</td>\n";
    echo "          <td style=\"font-weight:bold\">Lock Time</td>\n";
    echo "          <td style=\"font-weight:bold\">Disaster Button</td>\n";
    echo "        </tr>\n";

    // dump them out into the table
    while( ($row = mysqli_fetch_assoc($gameResults)) != null )
    {
      echo "        <tr>\n";
      echo "          <td>" . $row["away"] . " at " . $row["home"] . "</td>\n";
      echo "          <td><input type=\"text\" name=\"gameTime" . $row["gameID"] . "\" value=\"" .
           $row["gameTime"] . "\" /></td>\n";
      echo "          <td><input type=\"text\" name=\"lockTime" . $row["gameID"] . "\" value=\"" .
           $row["lockTime"] . "\" /></td>\n";
      echo "          <td><input type=\"checkbox\" name=\"disaster" . $row["gameID"] . "\"/></td>\n";
      echo "        </tr>\n";
    }
?>
      </table>
      <input type="submit" value="Update Times" />
    </form>
<?php
    if( isset($gameError) && $gameError != "" )
    {
      echo "    <span style=\"color:#FF0000; font-weight:bold;\">" . $gameError . "</span>\n";
    }
?>

