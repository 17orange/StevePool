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
    for( $i=1; $i<=23; $i++ )
    {
      if( $i != 22 ) {
        echo "        <option value=\"" . $i . "\"" . 
             (($weekSet && ($_SESSION["picksWeek"] == $i)) ? " selected" : "") . ">Week " . $i . "</option>\n";
      }
    }
?>
        <option value="consolation"<?php echo (($weekSet && ($_SESSION["picksWeek"] == "consolation")) ? " selected" : "")?>>Consolation</option>
      </select>
      <br/>
      <input type="submit" value="Select This User And Week" />
    </form>
    <br/>
    <br/>
<?php
    if( $userSet && $weekSet && $_SESSION["picksWeek"] == "consolation" )
    {
      // ordering of the games
      $indices = ["wc1AFC", "wc2AFC", "wc3AFC", "wc1NFC", "wc2NFC", "wc3NFC", "div1AFC", "div2AFC", "div1NFC", "div2NFC", "confAFC", "confNFC", "superBowl"];

      // grab their info
      $thesePicksData = mysqli_fetch_assoc(runQuery( "select " . implode(", ", $indices) . ", tieBreaker from ConsolationResult where userID=" . 
                                                     $_SESSION["picksUserID"] . " and season=" . $thisSeason));

      // see who is playing this postseason
      $gameData = runQuery( "select homeTeam, awayTeam from Game where season=" . $thisSeason . " and weekNumber>=19 order by gameID asc" );
      $games = [];
      $AFCteams = [];
      $NFCteams = [];
      while( ($game = mysqli_fetch_assoc( $gameData )) != null )
      {
        $games[] = $game;
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
        </tr>
<?php
      if( $thesePicksData != null ) {
        // wild card games
        for( $i=0; $i<6; $i++ )
        {
?>
        <tr>
          <td><?php echo $games[$i]["awayTeam"] . "@" . $games[$i]["homeTeam"]; ?></td>
          <td>
            <select name="picks<?php echo $indices[$i]; ?>">
<?php
          echo "              <option value=\"" . $games[$i]["homeTeam"] . "\"" . 
              (($games[$i]["homeTeam"] == $thesePicksData[$indices[$i]]) ? " selected" : "") . ">" . $games[$i]["homeTeam"] . "</option>\n";
          echo "              <option value=\"null\"" .  (("" == $thesePicksData[$indices[$i]]) ? " selected" : "") . ">No pick</option>\n";
          echo "              <option value=\"" . $games[$i]["awayTeam"] . "\"" . 
              (($games[$i]["awayTeam"] == $thesePicksData[$indices[$i]]) ? " selected" : "") . ">" . $games[$i]["awayTeam"] . "</option>\n";

          if( $i < 3 ) {
            $AFCteams[] = $games[$i]["homeTeam"];
            $AFCteams[] = $games[$i]["awayTeam"];
          } else {
            $NFCteams[] = $games[$i]["homeTeam"];
            $NFCteams[] = $games[$i]["awayTeam"];
          }
?>
            </select>
          </td>
        </tr>
<?php
        }

        // divisional games
        for( $i=6; $i<10; $i++ )
        {
?>
        <tr>
          <td><?php echo (($i % 2) ? ((($i<8) ? "A" : "N") . "FCDiv #2") : ("?@" . $games[$i]["homeTeam"])); ?></td>
          <td>
            <select name="picks<?php echo $indices[$i]; ?>">
<?php
          if( !($i % 2) ) {
            echo "              <option value=\"" . $games[$i]["homeTeam"] . "\"" . 
                (($games[$i]["homeTeam"] == $thesePicksData[$indices[$i]]) ? " selected" : "") . ">" . $games[$i]["homeTeam"] . "</option>\n";
          }
          echo "              <option value=\"null\"" .  (("" == $thesePicksData[$indices[$i]]) ? " selected" : "") . ">No pick</option>\n";
          for( $j=0;$j<6;$j++ )
          {
            $confTeams = ($i < 8) ? $AFCteams : $NFCteams;
            echo "              <option value=\"" . $confTeams[$j] . "\"" . 
                (($confTeams[$j] == $thesePicksData[$indices[$i]]) ? " selected" : "") . ">" . $confTeams[$j] . "</option>\n";
          }
        }
        $AFCteams[] = $games[6]["homeTeam"];
        $NFCteams[] = $games[8]["homeTeam"];

        // conf champ games
        for( $i=10; $i<12; $i++ )
        {
?>
        <tr>
          <td><?php echo (($i == 10) ? "A" : "N") . "FC Champ"; ?></td>
          <td>
            <select name="picks<?php echo $indices[$i]; ?>">
<?php
          echo "              <option value=\"null\"" .  (("" == $thesePicksData[$indices[$i]]) ? " selected" : "") . ">No pick</option>\n";
          for( $j=0;$j<7;$j++ )
          {
            $confTeams = ($i == 10) ? $AFCteams : $NFCteams;
            echo "              <option value=\"" . $confTeams[$j] . "\"" . 
                (($confTeams[$j] == $thesePicksData[$indices[$i]]) ? " selected" : "") . ">" . $confTeams[$j] . "</option>\n";
          }
        }

        // Super bowl
?>
        <tr>
          <td>Super Bowl</td>
          <td>
            <select name="picks<?php echo $indices[$i]; ?>">
<?php
        echo "              <option value=\"null\"" .  (("" == $thesePicksData[$indices[$i]]) ? " selected" : "") . ">No pick</option>\n";
        for( $j=0;$j<7;$j++ )
        {
          echo "              <option value=\"" . $AFCteams[$j] . "\"" . 
              (($AFCteams[$j] == $thesePicksData[$indices[$i]]) ? " selected" : "") . ">" . $AFCteams[$j] . "</option>\n";
          echo "              <option value=\"" . $NFCteams[$j] . "\"" . 
              (($NFCteams[$j] == $thesePicksData[$indices[$i]]) ? " selected" : "") . ">" . $NFCteams[$j] . "</option>\n";
        }
?>
            </select>
          </td>
        </tr>
        <tr>
          <td>SB Score</td>
          <td colspan=2><input name="picksTiebreaker" value="<?php echo $thesePicksData["tieBreaker"]; ?>" /></td>
        </tr>
        <tr>
          <td colspan=3>
            <input type="submit" value="Set Picks" />
          </td>
        </tr>
<?php
      }
?>
        <tr>
          <td colspan=3>
            <span style="color:#FF0000; font-size:18px; font-weight:bold;"><?php echo isset($managePicksError) ? $managePicksError : ""; ?></span>
          </td>
        </tr>
      </table>
    </form>
<?php
    }
    else if( $userSet && $weekSet )
    {
      // grab their info
      $thesePicksData = runQuery( "select gameID, homeTeam, awayTeam, winner, type, points from Pick join Game using (gameID) " . 
                                  "where userID=" . $_SESSION["picksUserID"] . " and weekNumber=" . $_SESSION["picksWeek"] . 
                                  " and season=" . $thisSeason . " order by points desc");

      // see who is on bye this week
      $byes = array();
      $byeData = runQuery( "select teamID from Team where teamID not in (select homeTeam from Game where season=" . $thisSeason . 
                           " and weekNumber=" . $_SESSION["picksWeek"] . ") and teamID not in (select awayTeam from Game " . 
                           "where season=" . $thisSeason . " and weekNumber=" . $_SESSION["picksWeek"] . ") and isActive='Y' " );
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
          <td><?php echo $game["awayTeam"] . "@" . $game["homeTeam"] . " (" . $game["type"] . ")"; ?></td>
          <td>
            <input name="picksGame<?php echo $gameCount; ?>ID" type="hidden" value="<?php echo $game["gameID"]; ?>" />
            <input name="picksGame<?php echo $gameCount; ?>Type" type="hidden" value="<?php echo $game["type"]; ?>" />
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
        if( $_SESSION["picksWeek"] < 19 )
        {
          for( $i=16; $i>(count($byes) / 2); $i-- )
          {
            echo "              <option value=\"" . $i . "\"" .  (($i == $game["points"]) ? " selected" : "") . 
                ">" . $i . "</option>\n";
          }
        }
        else if( $_SESSION["picksWeek"] < 22 )
        { 
          for( $i=(($_SESSION["picksWeek"] == 19) ? 25 : 17); $i>0; $i-- )
          {
            echo "              <option value=\"" . $i . "\"" .  (($i == $game["points"]) ? " selected" : "") . 
                ">" . $i . "</option>\n";
          }
        }
        else if( $_SESSION["picksWeek"] == 23 )
        { 
          echo "              <option value=\"" . $game["points"] . "\" selected>" . $game["points"] . "</option>\n";
        }
?>
            </select>
          </td>
        </tr>
<?php
        $gameCount++;
      }

      if( $_SESSION["picksWeek"] < 19 )
      {
        // see what they have as the tiebreaker
        $thisTB = mysqli_fetch_assoc( runQuery( "select tieBreaker from WeekResult where userID=" . $_SESSION["picksUserID"] . 
                                              " and weekNumber=" . $_SESSION["picksWeek"] . " and season=" . $thisSeason ));
?>
        <tr>
          <td>MNF Score</td>
          <td colspan=2><input name="picksMNF" value="<?php echo $thisTB["tieBreaker"]; ?>" /></td>
        </tr>
<?php
      }
      else
      {
        // see what they have as the tiebreaker
        $thisTB = mysqli_fetch_assoc( runQuery( "select tieBreaker1, tieBreaker2, tieBreaker3, tieBreaker4, tieBreaker5, tieBreaker6 " . 
                                                "from PlayoffResult where userID=" . $_SESSION["picksUserID"] . 
                                                " and weekNumber=" . $_SESSION["picksWeek"] . " and season=" . $thisSeason ));
?>
        <tr>
          <td>Tiebreaker 1</td>
          <td colspan=2><input name="picksTB1" value="<?php echo $thisTB["tieBreaker1"]; ?>" /></td>
        </tr>
        <tr>
          <td>Tiebreaker 2</td>
          <td colspan=2><input name="picksTB2" value="<?php echo $thisTB["tieBreaker2"]; ?>" /></td>
        </tr>
        <tr>
          <td>Tiebreaker 3</td>
          <td colspan=2><input name="picksTB3" value="<?php echo $thisTB["tieBreaker3"]; ?>" /></td>
        </tr>
        <tr>
          <td>Tiebreaker 4</td>
          <td colspan=2><input name="picksTB4" value="<?php echo $thisTB["tieBreaker4"]; ?>" /></td>
        </tr>
        <tr>
          <td>Tiebreaker 5</td>
          <td colspan=2><input name="picksTB5" value="<?php echo $thisTB["tieBreaker5"]; ?>" /></td>
        </tr>
        <tr>
          <td>Tiebreaker 6</td>
          <td colspan=2><input name="picksTB6" value="<?php echo $thisTB["tieBreaker6"]; ?>" /></td>
        </tr>
<?php
      }
?>
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

