<?php
    $dumpSet = isset($_POST["dataDumpID"]);
    if( $dumpSet )
    {
      // get the season
      $season = mysqli_fetch_assoc( runQuery( "select value from Constants where name='fetchSeason' " ) );
      $season = $season["value"];
      $week = $_POST["dataDumpWeek"];

      // regular season standings
      if( $_POST["dataDumpID"] == "SPW" && $week < 19 )
      {
        $MNF = mysqli_fetch_assoc( runQuery( "select homeScore + awayScore as score from Game where weekNumber=" . $week .
                                             " and season=" . $season . " order by gameTime desc limit 1" ) );
        $dumpResults = runQuery( "select userID, concat(firstName, ' ', lastName) as pName, winner, alias, tieBreaker, Pick.points as pPts, " .
                                 "WeekResult.points as wPts, SeasonResult.points as sPts, abs(tieBreaker - " . $MNF["score"] .
                                 ") as tb1, Division.name as dName, Conference.name as cName, weeklyWins from WeekResult join SeasonResult " .
                                 "using (userID, season) join User using (userID) join Game using (weekNumber, season) join Pick " .
                                 "using (userID, gameID) join Division using (divID) join Conference using (confID) left join Team " .
                                 "on (winner=teamID) where weekNumber=" . $week . " and season=" . $season .
                                 " order by wPts desc, tb1, tieBreaker, userID, gameTime, gameID" );
      }
      // playoff season standings
      else if( $_POST["dataDumpID"] == "SPW" && $week >= 19 )
      {
        $query = "select userID, concat(firstName, ' ', lastName) as pName, winner, if(winner='TIE', winner, alias) as alias, type, " .
                 "tieBreaker1, tieBreaker2, tieBreaker3, tieBreaker4, tieBreaker5, tieBreaker6, Pick.points as pPts, advances, " . 
                 "prevWeek1, prevWeek2, prevWeek3, firstRoundBye, PlayoffResult.points as wPts, SeasonResult.points as sPts, " . 
                 "Conference.name as cName, Division.name as dName, weeklyWins, ";

        // fill in the tiebreaker data
        if( $week == 23 )
        {
          $MNF = mysqli_fetch_assoc( runQuery( "select homeScore + awayScore as score from Game where weekNumber=" . $week .
                                               " and season=" . $season . " order by gameTime desc limit 1" ) );
          $query .= "if(prevWeek1>=0, if(prevWeek2>=0, if(prevWeek3>=0, 0, 10 + prevWeek3), 25 + prevWeek2), 40 + prevWeek1) as tb1, " .
                    "abs(tieBreaker1 - " . $MNF["score"] . ") as tb2, ";
          $query .= "if(type='winner', 10, if(type='winner3Q', 9, if(type='winner2Q', 8, if(type='winner1Q', 7, " .
                    "if(type='passYds', 6, if(type='passYds2Q', 5, if(type='rushYds', 4, if(type='rushYds2Q', 3, " .
                    "if(type='TDs', 2, 1))))))))) as typeSort ";
          $sort = "tb1 asc, wPts desc, tb2 asc, tieBreaker1";
        }
        else if( $week == 21 )
        {
          $games = runQuery( "select (homeScore + awayScore) as final, (homeScore2Q + awayScore2Q) as half from Game where weekNumber=" . $week .
                             " and season=" . $season . " order by gameTime asc" );
          $G1 = mysqli_fetch_assoc( $games );
          $G2 = mysqli_fetch_assoc( $games );
          $query .= "if(prevWeek1>=0, if(prevWeek2>=0, 0, 10 + prevWeek2), 25 + prevWeek1) as tb1, ";
          $query .= "abs(tieBreaker1 - " . $G2["final"] . ") as tb2, abs(tieBreaker2 - " . $G1["final"] . ") as tb3, ";
          $query .= "abs(tieBreaker3 - " . $G2["half"] . ") as tb4, abs(tieBreaker4 - " . $G1["half"] . ") as tb5, ";
          $query .= "if(type='winner2Q', 1, 2) as typeSort ";
          $sort = "tb1 asc, wPts desc, tb2 asc, tieBreaker1, tb3 asc, tieBreaker2, tb4 asc, tieBreaker3, tb5 asc, tieBreaker4";
        }
        else if( $week == 20 )
        {
          $games = runQuery( "select (homeScore + awayScore) as score from Game where weekNumber=" . $week .
                             " and season=" . $season . " order by gameTime asc" );
          $G1 = mysqli_fetch_assoc( $games );
          $G2 = mysqli_fetch_assoc( $games );
          $G3 = mysqli_fetch_assoc( $games );
          $G4 = mysqli_fetch_assoc( $games );
          $query .= "if(prevWeek1>=0, -20, 10 + prevWeek1) as tb1, ";
          $query .= "abs(tieBreaker1 - " . $G4["score"] . ") as tb2, abs(tieBreaker2 - " . $G3["score"] . ") as tb3, ";
          $query .= "abs(tieBreaker3 - " . $G2["score"] . ") as tb4, abs(tieBreaker4 - " . $G1["score"] . ") as tb5, ";
          $query .= "1 as typeSort ";
          $sort = "tb1 asc, wPts desc, tb2 asc, tieBreaker1, tb3 asc, tieBreaker2, tb4 asc, tieBreaker3, tb5 asc, tieBreaker4";
        }
        else
        {
          $games = runQuery( "select (homeScore + awayScore) as score from Game where weekNumber=" . $week .
                             " and season=" . $season . " order by gameTime asc" );
          $G1 = mysqli_fetch_assoc( $games );
          $G2 = mysqli_fetch_assoc( $games );
          $G3 = mysqli_fetch_assoc( $games );
          $G4 = mysqli_fetch_assoc( $games );
          $G5 = mysqli_fetch_assoc( $games );
          $G6 = mysqli_fetch_assoc( $games );
          $query .= "if(firstRoundBye='Y', 1, 2) as tb1, ";
          $query .= "abs(tieBreaker1 - " . $G6["score"] . ") as tb2, abs(tieBreaker2 - " . $G5["score"] . ") as tb3, ";
          $query .= "abs(tieBreaker3 - " . $G4["score"] . ") as tb4, abs(tieBreaker4 - " . $G3["score"] . ") as tb5, ";
          $query .= "abs(tieBreaker5 - " . $G2["score"] . ") as tb6, abs(tieBreaker6 - " . $G1["score"] . ") as tb7, ";
          $query .= "1 as typeSort ";
          $sort = "tb1 asc, wPts desc, tb2 asc, tieBreaker1, tb3 asc, tieBreaker2, tb4 asc, tieBreaker3, " . 
                  "tb5 asc, tieBreaker4, tb6 asc, tieBreaker5, tb7 asc, tieBreaker6";
        }

        // get the rest of the query
        $query .= "from SeasonResult join PlayoffResult using (userID, season) join User using (userID) join Game " .
                  "using (weekNumber, season) left join Pick using (userID, gameID) join Division using (divID) join " .
                  "Conference using (confID) left join Team on (winner=teamID) where weekNumber=" . $week . " and season=" .
                  $season . " order by " . $sort . ", sPts desc, userID, gameTime, gameID, typeSort";
        $dumpResults = runQuery( $query );
      }
      // consolation pool standings
      else if( $_POST["dataDumpID"] == "CPS" )
      {
        $MNF = mysqli_fetch_assoc( runQuery( "select homeScore + awayScore as score from Game where weekNumber=23 and season=" .
                                             $season . " order by gameTime desc limit 1" ) );
        $dumpResults = runQuery( "select userID, concat(firstName, ' ', lastName) as pName, ConsolationResult.points as cPts, " .
                                 "wc1AFC, wc2AFC, wc3AFC, wc1NFC, wc2NFC, wc3NFC, div1AFC, div2AFC, div1NFC, div2NFC, confAFC, confNFC, superBowl, " .
                                 "wc1A.alias as wc1AFCalias, wc2A.alias as wc2AFCalias, wc3A.alias as wc3AFCalias, wc1N.alias as wc1NFCalias, " . 
                                 "wc2N.alias as wc2NFCalias, wc3N.alias as wc3NFCalias, d1A.alias as div1AFCalias, d2A.alias as div2AFCalias, " .
                                 "d1N.alias as div1NFCalias, d2N.alias as div2NFCalias, cA.alias as confAFCalias, cN.alias as confNFCalias, " .
                                 "sb.alias as superBowlalias, picksCorrect, tieBreaker, abs(tieBreaker - " . $MNF["score"] . ") as tb2, " . 
                                 "SeasonResult.points as tb4, SeasonResult.weeklyWins as tb5, if(wc1AFC is null, 2, 1) as filter from ConsolationResult " .
                                 "left join Team as wc1A on (wc1AFC=wc1A.teamID) left join Team as wc2A on (wc2AFC=wc2A.teamID) " .
                                 "left join Team as wc3A on (wc3AFC=wc3A.teamID) left join Team as wc1N on (wc1NFC=wc1N.teamID) " .
                                 "left join Team as wc2N on (wc2NFC=wc2N.teamID) left join Team as wc3N on (wc3NFC=wc3N.teamID) " .
                                 "left join Team as d1A on (div1AFC=d1A.teamID) left join Team as d2A on (div2AFC=d2A.teamID) " .
                                 "left join Team as d1N on (div1NFC=d1N.teamID) left join Team as d2N on (div2NFC=d2N.teamID) " .
                                 "left join Team as cA on (confAFC=cA.teamID) left join Team as cN on (confNFC=cN.teamID) " .
                                 "left join Team as sb on (superBowl=sb.teamID) " .
                                 "join SeasonResult using (userID, season) join User using (userID) where season=" . $season .
                                 " order by filter asc, cPts desc, picksCorrect desc, tb2 asc, tieBreaker asc, tb4 desc, tb5 desc" );
      }
    }
?>
    <span style="font-size:18px; font-weight:bold;">Data Dump</span>
    <form action="." method="post">
      <span>Selected Dump</span>
      <select name="dataDumpID">
        <option value="SPW"<?php echo (($dumpSet && ($_POST["dataDumpID"] == "SPW")) ? " selected" : ""); ?>>Standings/Picks By Week</option>
        <option value="CPS"<?php echo (($dumpSet && ($_POST["dataDumpID"] == "CPS")) ? " selected" : ""); ?>>Consolation Pool Standings/Picks</option>
      </select>
      <br>
      <span>Selected Week</span>
      <select name="dataDumpWeek">
<?php
    for( $i=1; $i<24; $i++ )
    {
      if( $i != 22 )
      {
        echo "        <option value=\"" . $i . "\"" . (($dumpSet && ($_POST["dataDumpWeek"] == $i)) ? " selected" : "") . ">" . $i . "</option>\n";
      }
    }
?>
      </select>
      <br>
      <input type="submit" value="Select This Dump" />
    </form>
    <br/><br/>
<?php
    if( isset($dumpResults) )
    {
      // regular season
      if( $_POST["dataDumpID"] == "SPW" && $_POST["dataDumpWeek"] < 19 )
      {
        // header
        echo "Name,Conf,Div";
        $games = runQuery( "select concat(aT.alias, '@', hT.alias) as info from Game join Team as aT on (awayTeam=aT.teamID) " .
                           "join Team as hT on (homeTeam=hT.teamID) where weekNumber=" . $_POST["dataDumpWeek"] . " and season=" .
                           $season . " order by gameTime, gameID" );
        while( ($thisGame = mysqli_fetch_assoc($games)) != null )
        {
          echo "," . $thisGame["info"] . ",Points";
        }
        echo ",Week Points,Diff MNF,MNF,Season Points,Weekly Wins<br>\n";

        // data
        $userID = -1;
        $info = "";
        $endStr = "";
        while( ($thisRow = mysqli_fetch_assoc( $dumpResults )) != null )
        {
          if( $thisRow["userID"] != $userID )
          {
            if( $info != "" )
            {
              echo "      <span>" . $info . $endStr . "</span><br>\n";
            }
            $info = $thisRow["pName"] . "," . $thisRow["cName"] . "," . $thisRow["dName"];
            $userID = $thisRow["userID"];
            $endStr = "," . $thisRow["wPts"] . "," . $thisRow["tb1"] . "," . $thisRow["tieBreaker"] . "," . $thisRow["sPts"] . "," . $thisRow["weeklyWins"];
          }
          $info .= "," . $thisRow["alias"] . "," . $thisRow["pPts"];
        }
        if( $info != "" )
        {
          echo "      <span>" . $info . $endStr . "</span><br>\n";
        }
      // wild card/divisional
      } else if( $_POST["dataDumpID"] == "SPW" && $_POST["dataDumpWeek"] < 21 ) {
        // header
        echo "Name,Conf,Div";
        $games = runQuery( "select concat(aT.alias, '@', hT.alias) as info from Game join Team as aT on (awayTeam=aT.teamID) " .
                           "join Team as hT on (homeTeam=hT.teamID) where weekNumber=" . $_POST["dataDumpWeek"] . " and season=" .
                           $season . " order by gameTime, gameID" );
        while( ($thisGame = mysqli_fetch_assoc($games)) != null )
        {
          echo "," . $thisGame["info"] . ",Points";
        }
        echo ",WC Points," . (($_POST["dataDumpWeek"] == 20) ? "Div Points," : "") . "Diff1,Tiebreak1,Diff2,Tiebreak2,Diff3,Tiebreak3,Diff4,Tiebreak4" . 
            (($_POST["dataDumpWeek"] == 19) ? ",Diff5,Tiebreak5,Diff6,Tiebreak6" : "") . "<br>\n";

        // data
        $userID = -1;
        $info = "";
        $endStr = "";
        while( ($thisRow = mysqli_fetch_assoc( $dumpResults )) != null )
        {
          if( $thisRow["userID"] != $userID )
          {
            if( $info != "" )
            {
              echo "      <span>" . $info . $endStr . "</span><br>\n";
            }
            $info = $thisRow["pName"] . "," . $thisRow["cName"] . "," . $thisRow["dName"];
            $userID = $thisRow["userID"];
            $endStr = "," . (($_POST["dataDumpWeek"] == 20) ? ($thisRow["prevWeek1"] . ",") : "") . $thisRow["wPts"] . "," . $thisRow["tb2"] . "," .
                      $thisRow["tieBreaker1"] . "," . $thisRow["tb3"] . "," . $thisRow["tieBreaker2"] . "," . $thisRow["tb4"] . "," .
                      $thisRow["tieBreaker3"] . "," . $thisRow["tb5"] . "," . $thisRow["tieBreaker4"] . (($_POST["dataDumpWeek"] == 19) 
                      ? ("," . $thisRow["tb6"] . "," . $thisRow["tieBreaker5"] . "," . $thisRow["tb7"] . "," . $thisRow["tieBreaker6"]) : "");
          }
          $info .= "," . $thisRow["alias"] . "," . $thisRow["pPts"];
        }
        if( $info != "" )
        {
          echo "      <span>" . $info . $endStr . "</span><br>\n";
        }
      // conference championship
      } else if( $_POST["dataDumpID"] == "SPW" && $_POST["dataDumpWeek"] == 21 ) {
        // header
        echo "Name,Conf,Div";
        $games = runQuery( "select concat(aT.alias, '@', hT.alias) as info from Game join Team as aT on (awayTeam=aT.teamID) " .
                           "join Team as hT on (homeTeam=hT.teamID) where weekNumber=" . $_POST["dataDumpWeek"] . " and season=" .
                           $season . " order by gameTime, gameID" );
        $game1 = mysqli_fetch_assoc($games);
        $game2 = mysqli_fetch_assoc($games);
        echo "," . $game1["info"] . " Half,Points," . $game2["info"] . " Half,Points," . $game1["info"] . " Final,Points," . $game2["info"] .
             " Final,Points,WC Points,Div Points,Conf Points,Diff1,Tiebreak1,Diff2,Tiebreak2,Diff3,Tiebreak3,Diff4,Tiebreak4<br>";

        // data
        $userID = -1;
        $info = "";
        $endStr = "";
        while( ($thisRow = mysqli_fetch_assoc( $dumpResults )) != null )
        {
          if( $thisRow["userID"] != $userID )
          {
            if( $info != "" )
            {
              echo "      <span>" . $info . $endStr . "</span><br>\n";
            }
            $info = $thisRow["pName"] . "," . $thisRow["cName"] . "," . $thisRow["dName"];
            $userID = $thisRow["userID"];
            $endStr = "," . $thisRow["prevWeek1"] . "," . $thisRow["prevWeek2"] . "," . $thisRow["wPts"] . "," . $thisRow["tb2"] . "," .
                      $thisRow["tieBreaker1"] . "," . $thisRow["tb3"] . "," . $thisRow["tieBreaker2"] . "," . $thisRow["tb4"] . "," .
                      $thisRow["tieBreaker3"] . "," . $thisRow["tb5"] . "," . $thisRow["tieBreaker4"];
          }
          $info .= "," . $thisRow["alias"] . "," . $thisRow["pPts"];
        }
        if( $info != "" )
        {
          echo "      <span>" . $info . $endStr . "</span><br>\n";
        }
      // super bowl
      } else if( $_POST["dataDumpID"] == "SPW" && $_POST["dataDumpWeek"] == 23 ) {
        // header
        echo "Name,Conf,Div,Half TDs,Final TDs,Half Rush,Final Rush,Half Pass,Final Pass,1Q,Half,3Q,Final," .
            "WC Points,Div Points,Conf Points,SB Points,Diff,Tiebreak,Regular Points,Weekly Wins<br>";

        // data
        $userID = -1;
        $info = "";
        $endStr = "";
        while( ($thisRow = mysqli_fetch_assoc( $dumpResults )) != null )
        {
          if( $thisRow["userID"] != $userID )
          {
            if( $info != "" )
            {
              echo "      <span>" . $info . $endStr . "</span><br>\n";
            }
            $info = $thisRow["pName"] . "," . $thisRow["cName"] . "," . $thisRow["dName"];
            $userID = $thisRow["userID"];
            $endStr = "," . $thisRow["prevWeek1"] . "," . $thisRow["prevWeek2"] . "," . $thisRow["prevWeek3"] . "," . $thisRow["wPts"] .
                      "," . $thisRow["tb2"] . "," . $thisRow["tieBreaker1"] . "," . $thisRow["sPts"] . "," . $thisRow["weeklyWins"];
          }
          $info .= "," . $thisRow["alias"];
        }
        if( $info != "" )
        {
          echo "      <span>" . $info . $endStr . "</span><br>\n";
        }
      // consolation pool
      } else if( $_POST["dataDumpID"] == "CPS" ) {
        // header
        echo "Name,AFC 2-7,AFC 3-6,AFC 4-5,NFC 2-7,NFC 3-6,NFC 4-5,AFC 1-X,AFC 2-X,NFC 1-X,NFC 2-X,AFC CC,NFC CC,SB," .
             "Points,Correct Picks,Diff,Tiebreak,Regular Points,Weekly Wins<br>\n";

        // data
        while( ($thisRow = mysqli_fetch_assoc( $dumpResults )) != null )
        {
          echo "      <span>" . $thisRow["pName"] . "," . $thisRow["wc1AFCalias"] . "," . $thisRow["wc2AFCalias"] . "," .
               $thisRow["wc3AFCalias"] . "," . $thisRow["wc1NFCalias"] . "," . $thisRow["wc2NFCalias"] . "," . 
               $thisRow["wc3NFCalias"] . "," . $thisRow["div1AFCalias"] . "," . $thisRow["div2AFCalias"] . "," . 
               $thisRow["div1NFCalias"] . "," . $thisRow["div2NFCalias"] . "," . $thisRow["confAFCalias"] . "," .
               $thisRow["confNFCalias"] . "," . $thisRow["superBowlalias"] . "," . $thisRow["cPts"] . "," .
               $thisRow["picksCorrect"] . "," . $thisRow["tb2"] . "," . $thisRow["tieBreaker"] . "," . $thisRow["tb4"] . "," .
               $thisRow["tb5"] . "</span><br>\n";
        }
      }
    }
?>
