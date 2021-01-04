<?php
  // start the session if we dont have one
  if(session_id() == '') {
  //if (session_status() == PHP_SESSION_NONE) {
    session_start();

    include "../util.php";
  }

  // see what this type is
  $standingsType = isset($_GET["type"]) ? $_GET["type"] : "actual";

  // see if they forced a winner
  if( isset($_GET["forcedWinnerGameID"]) && isset($_GET["forcedWinner"]) )
  {
    $_SESSION["forcedWinners"][$_GET["forcedWinnerGameID"]] = $_GET["forcedWinner"];
  }
  else if( !isset($_SESSION["forcedWinners"]) || $standingsType == "actual" )
  {
    $_SESSION["forcedWinners"] = array();
  }

  // grab their userID so we can show their picks
  $myID = 0;
  $logosHidden = false;
  if( isset($_SESSION["spsID"]) )
  {
    $results = RunQuery( "select coalesce(userID, 0) as userID from Session where sessionID=" . $_SESSION["spsID"] );
    $myID = $results[0]["userID"];
    $logosHidden = (isset($_SESSION["spHideLogos"]) && $_SESSION["spHideLogos"] == "TRUE");
  }

  // grab their picks if they're trying to show best or worst
  if( $standingsType == "best" || $standingsType == "worst" )
  {
    $results = RunQuery( "select gameID, winner, if(homeTeam=winner, awayTeam, homeTeam) as loser from Pick " . 
                         "join Game using (gameID) where userID=" . $myID . " and weekNumber=" . 
                         $_SESSION["showPicksWeek"] . " and season=" . $_SESSION["showPicksSeason"] . " and status != 3" );
    foreach( $results as $thisPick )
    {
      $_SESSION["forcedWinners"][$thisPick["gameID"]] = $thisPick[($standingsType == "best") ? "winner" : "loser"];
    }
  }

  // grab the games from that week
  $games = array();
  $gamesLive = 0;
  $firstRefresh = "";
  $results = RunQuery( "select *, if(lockTime>now(), 0, 1) as isLocked from Game where weekNumber=" . $_SESSION["showPicksWeek"] . 
                       " and season=" . $_SESSION["showPicksSeason"] . " order by tieBreakOrder, gameTime, gameID", false );
  foreach( $results as $thisGame )
  {
    $games[count($games)] = $thisGame;
    if( $thisGame["status"] == "2" )
    {
      $gamesLive++;
    }
    else if( $thisGame["status"] == "1" || $thisGame["status"] == 19 )
    {
      if( $firstRefresh == "" )
      {
        $firstRefresh = $thisGame["gameTime"];
      }
    }
  }
  $section = -1;

  // grab all of the rows
  $poolLocked = (($games[0]["isLocked"] == 1) && ($games[0]["status"] != 19));
  $query = "select userID, concat(firstName, ' ', lastName) as pName, winner, tieBreaker1, tieBreaker2, tieBreaker3, " . 
           "tieBreaker4, tieBreaker5, tieBreaker6, Pick.points as pPts, advances, prevWeek1, prevWeek2, prevWeek3, firstRoundBye, " . 
           "if(type='winner', if(homeScore>awayScore, homeTeam, if(awayScore>homeScore, awayTeam, 'TIE')), " . 
           "if(type='winner3Q', if(homeScore3Q>awayScore3Q, homeTeam, if(awayScore3Q>homeScore3Q, awayTeam, 'TIE')), " . 
           "if(type='winner2Q', if(homeScore2Q>awayScore2Q, homeTeam, if(awayScore2Q>homeScore2Q, awayTeam, 'TIE')), " . 
           "if(type='winner1Q', if(homeScore1Q>awayScore1Q, homeTeam, if(awayScore1Q>homeScore1Q, awayTeam, 'TIE')), " . 
           "if(type='passYds', if(homePassYds>awayPassYds, homeTeam, if(awayPassYds>homePassYds, awayTeam, 'TIE')), " . 
           "if(type='passYds2Q', if(homePassYds2Q>awayPassYds2Q, homeTeam, if(awayPassYds2Q>homePassYds2Q, awayTeam, 'TIE')), " . 
           "if(type='rushYds', if(homeRushYds>awayRushYds, homeTeam, if(awayRushYds>homeRushYds, awayTeam, 'TIE')), " . 
           "if(type='rushYds2Q', if(homeRushYds2Q>awayRushYds2Q, homeTeam, if(awayRushYds2Q>homeRushYds2Q, awayTeam, 'TIE')), " . 
           "if(type='TDs', if(homeTDs>awayTDs, homeTeam, if(awayTDs>homeTDs, awayTeam, 'TIE')), " . 
           "if(type='TDs2Q', if(homeTDs2Q>awayTDs2Q, homeTeam, if(awayTDs2Q>homeTDs2Q, awayTeam, 'TIE')), " . 
           "'')))))))))) as leader, PlayoffResult.points as wPts, SeasonResult.points as sPts, if(lockTime>now(), 1, 0) " . 
           "as status, Conference.name as cName, " . (($_SESSION["showPicksWeek"] == 22) ? "1" : "confID") . " as section, " .
           "if(Game.status=3, 1, 0) as isFinal, Game.status as gStatus, weeklyWins, ";

  // fill in the tiebreaker data
  if( $_SESSION["showPicksWeek"] == 22 )
  {
    $query .= "if(prevWeek1>=0, if(prevWeek2>=0, if(prevWeek3>=0, " . ($poolLocked ? "0" : "-prevWeek3") . 
              ", 25 + prevWeek3), 50 + prevWeek2), 75 + prevWeek1) as tb1, ";
    $query .= ($poolLocked ? ("abs(tieBreaker1 - " . ($games[0]["homeScore"] + $games[0]["awayScore"]) . ")") : "1") . " as tb2, ";
    $query .= "if(type='winner', 10, if(type='winner3Q', 9, if(type='winner2Q', 8, if(type='winner1Q', 7, " . 
              "if(type='passYds', 6, if(type='passYds2Q', 5, if(type='rushYds', 4, if(type='rushYds2Q', 3, " . 
              "if(type='TDs', 2, 1))))))))) as typeSort ";
    $sort = ", tb1 asc, wPts desc, tb2 asc" . ($poolLocked ? ", tieBreaker1" : "");
  }
  else if( $_SESSION["showPicksWeek"] == 20 )
  {
    $query .= "if(prevWeek1>=0, if(prevWeek2>=0, " . ($poolLocked ? "0" : "-prevWeek2") . ", 25 + prevWeek2), 50 + prevWeek1) as tb1, ";
    $query .= ($poolLocked ? ("abs(tieBreaker1 - " . ($games[1]["homeScore"] + $games[1]["awayScore"]) . ")") : "1") . " as tb2, ";
    $query .= ($poolLocked ? ("abs(tieBreaker2 - " . ($games[0]["homeScore"] + $games[0]["awayScore"]) . ")") : "1") . " as tb3, ";
    $query .= ($poolLocked ? ("abs(tieBreaker3 - " . ($games[1]["homeScore2Q"] + $games[1]["awayScore2Q"]) . ")") : "1") . " as tb4, ";
    $query .= ($poolLocked ? ("abs(tieBreaker4 - " . ($games[0]["homeScore2Q"] + $games[0]["awayScore2Q"]) . ")") : "1") . " as tb5, ";
    $query .= "if(type='winner2Q', 1, 2) as typeSort ";
    $sort = ", tb1 asc, wPts desc, tb2 asc" . ($poolLocked ? ", tieBreaker1" : "") . ", tb3 asc" . 
           ($poolLocked ? ", tieBreaker2" : "") . ", tb4 asc" . ($poolLocked ? ", tieBreaker3" : "") . ", tb5 asc" . 
           ($poolLocked ? ", tieBreaker4" : "");
  }
  else
  {
    $query .= (($_SESSION["showPicksWeek"] == 18) ? "if(firstRoundBye='Y', 1, 2)" : 
               ($poolLocked ? "if(prevWeek1>=0, -20, 25 + prevWeek1)"
                            : "if(prevWeek1=0, -20, if(prevWeek1>0, -prevWeek1, 25 + prevWeek1))")) . " as tb1, ";
    $query .= ($poolLocked ? ("abs(tieBreaker1 - " . ($games[3]["homeScore"] + $games[3]["awayScore"]) . ")") : "1") . " as tb2, ";
    $query .= ($poolLocked ? ("abs(tieBreaker2 - " . ($games[2]["homeScore"] + $games[2]["awayScore"]) . ")") : "1") . " as tb3, ";
    $query .= ($poolLocked ? ("abs(tieBreaker3 - " . ($games[1]["homeScore"] + $games[1]["awayScore"]) . ")") : "1") . " as tb4, ";
    $query .= ($poolLocked ? ("abs(tieBreaker4 - " . ($games[0]["homeScore"] + $games[0]["awayScore"]) . ")") : "1") . " as tb5, ";
    $query .= "1 as typeSort ";
    $sort = ", tb1 asc, wPts desc, tb2 asc" . ($poolLocked ? ", tieBreaker1" : "") . ", tb3 asc" . 
           ($poolLocked ? ", tieBreaker2" : "") . ", tb4 asc" . ($poolLocked ? ", tieBreaker3" : "") . ", tb5 asc" . 
           ($poolLocked ? ", tieBreaker4" : "") ;
  }

  // get the rest of the query
  $query .= "from SeasonResult join PlayoffResult using (userID, season) join User using (userID) join Game " . 
            "using (weekNumber, season) left join Pick using (userID, gameID) join Division using (divID) join " . 
            "Conference using (confID) where weekNumber=" . $_SESSION["showPicksWeek"] . " and season=" . 
            $_SESSION["showPicksSeason"] . " order by section" . $sort . ", sPts desc, userID, tieBreakOrder, gameTime, gameID, typeSort";
  $results = RunQuery( $query );
  $pickBank = array();
  foreach( $results as $thisPick )
  {
    $pickBank[count($pickBank)] = $thisPick;
  }

  $userID = -1;
  $currRank = 0;
  $playerCount = 0;
  $currScore = 500;
  $possibleMax = 0;
  $grouping = 0;
  $colSpan = ($_SESSION["showPicksWeek"] == 18) ? (($_SESSION["showPicksSeason"] < 2020) ? 14 : 18) : (($_SESSION["showPicksWeek"] == 19) ? 15 : (($_SESSION["showPicksWeek"] == 20) ? 16 : 19));
  for( $jk=0; $jk<count($pickBank); $jk++ )
  {
    $thisPick = $pickBank[$jk];
    $nextPick = (($jk + 1) < count($pickBank)) ? $pickBank[$jk + 1] : null;
    $gameIndex = $jk % count($games);

    // see if we need to start a new table
    if( $thisPick["section"] != $section )
    {
      $section = $thisPick["section"];

      // reset the variables
      if( $userID != -1 )
      {
        echo "        <tr>\n";
        echo "          <td class=\"noBorder\" style=\"height:30px;\"colspan=\"" . $colSpan . "\"></td>";
        echo "        </tr>\n";
      }
      $userID = -1;
      $currRank = 0;
      $playerCount = 0;
      $currScore = 500;
      $possibleMax = 0;

      // show the title
      echo "        <tr>\n";
      echo "          <td colspan=\"" . $colSpan . "\" class=\"headerBackgroundTable\" style=\"font-size:24px;\">";
      if( $_SESSION["showPicksWeek"] < 22 )
      {
        echo $thisPick["cName"] . " Conference";
      }
      else
      {
        echo "Overall";
      }
      echo " Standings</td>\n";
      echo "        </tr>\n";
?>
        <tr>
          <td class="headerBackgroundTable" style="width:3%;">Rank</td>
          <td class="headerBackgroundTable" style="width:16%; cursor:pointer;" onClick="SortTable('name');">Player</td>
          <td class="headerBackgroundTable" style="width:3%; cursor:pointer;" onClick="SortTable('ytdPts');">Regular Season</td>
<?php
      if( $_SESSION["showPicksWeek"] > 18 )
      {
        echo "          <td class=\"headerBackgroundTable\" style=\"width:3%; cursor:pointer;\" onClick=\"SortTable('wcPts');\">Wild Card Round</td>\n";
      }
      if( $_SESSION["showPicksWeek"] > 19 )
      {
        echo "          <td class=\"headerBackgroundTable\" style=\"width:3%; cursor:pointer;\" onClick=\"SortTable('divPts');\">Divisional Round</td>\n";
      }
      if( $_SESSION["showPicksWeek"] > 20 )
      {
        echo "          <td class=\"headerBackgroundTable\" style=\"width:3%; cursor:pointer;\" onClick=\"SortTable('confPts');\">Conference Championship</td>\n";
      }

      // show the games from that week
      for( $i=0; $i<count($games); $i++ )
      {
        if( $_SESSION["showPicksWeek"] >= 20 )
        {           
          if( $_SESSION["showPicksWeek"] == 22 )
          {           
            echo "          <td style=\"display:none;\">Weekly Wins</td>\n";
            echo "          <td class=\"headerBackgroundTable\" style=\"width:4.5%; font-size:10px;\">\n";
            echo "            <table class=\"gameScoreTable\" name=\"game" . $games[$i]["gameID"] . "-TDs2Q\">\n";
            echo "              <tr onClick=\"ForceWinner('" . $games[$i]["gameID"] . "-TDs2Q','" . $games[$i]["awayTeam"] . "');\">\n";
            echo "                <td class=\"posTop\" style=\"background-color:" . 
                (($games[$i]["awayTDs2Q"] > $games[$i]["homeTDs2Q"]) ? "#409840" : "#D9DCE3") . ";\"><div class=\"posTeam\">" . 
                $teamAliases[$games[$i]["awayTeam"]] . "<div class=\"imgDiv\"><img class=\"teamLogo\" src=\"" . 
                getIcon($games[$i]["awayTeam"], $_SESSION["showPicksSeason"]) . "\"/></div></div></td>\n";
            echo "              </tr>\n";
            echo "              <tr onClick=\"ForceWinner('" . $games[$i]["gameID"] . "-TDs2Q','TIE');\">\n";
            echo "                <td class=\"posOther\" style=\"background-color:" . 
                (($games[$i]["awayTDs2Q"] == $games[$i]["homeTDs2Q"]) ? "#409840" : "#D9DCE3") . ";\">Tie</td>\n";
            echo "              </tr>\n";
            echo "              <tr onClick=\"ForceWinner('" . $games[$i]["gameID"] . "-TDs2Q','" . $games[$i]["homeTeam"] . "');\">\n";
            echo "                <td class=\"posOther\" style=\"background-color:" . 
                (($games[$i]["awayTDs2Q"] < $games[$i]["homeTDs2Q"]) ? "#409840" : "#D9DCE3") . ";\"><div class=\"posTeam\">" . 
                $teamAliases[$games[$i]["homeTeam"]] . "<div class=\"imgDiv\"><img class=\"teamLogo\" src=\"" .
                getIcon($games[$i]["homeTeam"], $_SESSION["showPicksSeason"]) . "\"/></div></div></td>\n";
            echo "              </tr>\n";
            echo "              <tr>\n";
            echo "                <td colspan=\"2\" class=\"noBorder\" style=\"height:35px;\">Halftime TDs</td>\n";
            echo "              </tr>\n";
            echo "            </table>\n";
            echo "          </td>\n";
            echo "          <td class=\"headerBackgroundTable\" style=\"width:4.5%; font-size:10px;\">\n";
            echo "            <table class=\"gameScoreTable\" name=\"game" . $games[$i]["gameID"] . "-TDs\">\n";
            echo "              <tr onClick=\"ForceWinner('" . $games[$i]["gameID"] . "-TDs','" . $games[$i]["awayTeam"] . "');\">\n";
            echo "                <td class=\"posTop\" style=\"background-color:" . 
                (($games[$i]["awayTDs"] > $games[$i]["homeTDs"]) ? "#409840" : "#D9DCE3") . ";\"><div class=\"posTeam\">" . 
                $teamAliases[$games[$i]["awayTeam"]] . "<div class=\"imgDiv\"><img class=\"teamLogo\" src=\"" . 
                getIcon($games[$i]["awayTeam"], $_SESSION["showPicksSeason"]) . "\"/></div></div></td>\n";
            echo "              </tr>\n";
            echo "              <tr onClick=\"ForceWinner('" . $games[$i]["gameID"] . "-TDs','TIE');\">\n";
            echo "                <td class=\"posOther\" style=\"background-color:" . 
                (($games[$i]["awayTDs"] == $games[$i]["homeTDs"]) ? "#409840" : "#D9DCE3") . ";\">Tie</td>\n";
            echo "              </tr>\n";
            echo "              <tr onClick=\"ForceWinner('" . $games[$i]["gameID"] . "-TDs','" . $games[$i]["homeTeam"] . "');\">\n";
            echo "                <td class=\"posOther\" style=\"background-color:" . 
                (($games[$i]["awayTDs"] < $games[$i]["homeTDs"]) ? "#409840" : "#D9DCE3") . ";\"><div class=\"posTeam\">" . 
                $teamAliases[$games[$i]["homeTeam"]] . "<div class=\"imgDiv\"><img class=\"teamLogo\" src=\"" .
                getIcon($games[$i]["homeTeam"], $_SESSION["showPicksSeason"]) . "\"/></div></div></td>\n";
            echo "              </tr>\n";
            echo "              <tr>\n";
            echo "                <td colspan=\"2\" class=\"noBorder\" style=\"height:35px;\">Final TDs</td>\n";
            echo "              </tr>\n";
            echo "            </table>\n";
            echo "          </td>\n";
            echo "          <td class=\"headerBackgroundTable\" style=\"width:4.5%; font-size:10px;\">\n";
            echo "            <table class=\"gameScoreTable\" name=\"game" . $games[$i]["gameID"] . "-RushYds2Q\">\n";
            echo "              <tr onClick=\"ForceWinner('" . $games[$i]["gameID"] . "-RushYds2Q','" . $games[$i]["awayTeam"] . "');\">\n";
            echo "                <td class=\"posTop\" style=\"background-color:" . 
                (($games[$i]["awayRushYds2Q"] > $games[$i]["homeRushYds2Q"]) ? "#409840" : "#D9DCE3") . ";\"><div class=\"posTeam\">" . 
                $teamAliases[$games[$i]["awayTeam"]] . "<div class=\"imgDiv\"><img class=\"teamLogo\" src=\"" . 
                getIcon($games[$i]["awayTeam"], $_SESSION["showPicksSeason"]) . "\"/></div></div></td>\n";
            echo "              </tr>\n";
            echo "              <tr onClick=\"ForceWinner('" . $games[$i]["gameID"] . "-RushYds2Q','TIE');\">\n";
            echo "                <td class=\"posOther\" style=\"background-color:" . 
                (($games[$i]["awayRushYds2Q"] == $games[$i]["homeRushYds2Q"]) ? "#409840" : "#D9DCE3") . ";\">Tie</td>\n";
            echo "              </tr>\n";
            echo "              <tr onClick=\"ForceWinner('" . $games[$i]["gameID"] . "-RushYds2Q','" . $games[$i]["homeTeam"] . "');\">\n";
            echo "                <td class=\"posOther\" style=\"background-color:" . 
                (($games[$i]["awayRushYds2Q"] < $games[$i]["homeRushYds2Q"]) ? "#409840" : "#D9DCE3") . ";\"><div class=\"posTeam\">" . 
                $teamAliases[$games[$i]["homeTeam"]] . "<div class=\"imgDiv\"><img class=\"teamLogo\" src=\"" .
                getIcon($games[$i]["homeTeam"], $_SESSION["showPicksSeason"]) . "\"/></div></div></td>\n";
            echo "              </tr>\n";
            echo "              <tr>\n";
            echo "                <td colspan=\"2\" class=\"noBorder\" style=\"height:35px;\">Halftime Rush&nbsp;Yds</td>\n";
            echo "              </tr>\n";
            echo "            </table>\n";
            echo "          </td>\n";
            echo "          <td class=\"headerBackgroundTable\" style=\"width:4.5%; font-size:10px;\">\n";
            echo "            <table class=\"gameScoreTable\" name=\"game" . $games[$i]["gameID"] . "-RushYds\">\n";
            echo "              <tr onClick=\"ForceWinner('" . $games[$i]["gameID"] . "-RushYds','" . $games[$i]["awayTeam"] . "');\">\n";
            echo "                <td class=\"posTop\" style=\"background-color:" . 
                (($games[$i]["awayRushYds"] > $games[$i]["homeRushYds"]) ? "#409840" : "#D9DCE3") . ";\"><div class=\"posTeam\">" . 
                $teamAliases[$games[$i]["awayTeam"]] . "<div class=\"imgDiv\"><img class=\"teamLogo\" src=\"" . 
                getIcon($games[$i]["awayTeam"], $_SESSION["showPicksSeason"]) . "\"/></div></div></td>\n";
            echo "              </tr>\n";
            echo "              <tr onClick=\"ForceWinner('" . $games[$i]["gameID"] . "-RushYds','TIE');\">\n";
            echo "                <td class=\"posOther\" style=\"background-color:" . 
                (($games[$i]["awayRushYds"] == $games[$i]["homeRushYds"]) ? "#409840" : "#D9DCE3") . ";\">Tie</td>\n";
            echo "              </tr>\n";
            echo "              <tr onClick=\"ForceWinner('" . $games[$i]["gameID"] . "-RushYds','" . $games[$i]["homeTeam"] . "');\">\n";
            echo "                <td class=\"posOther\" style=\"background-color:" . 
                (($games[$i]["awayRushYds"] < $games[$i]["homeRushYds"]) ? "#409840" : "#D9DCE3") . ";\"><div class=\"posTeam\">" . 
                $teamAliases[$games[$i]["homeTeam"]] . "<div class=\"imgDiv\"><img class=\"teamLogo\" src=\"" .
                getIcon($games[$i]["homeTeam"], $_SESSION["showPicksSeason"]) . "\"/></div></div></td>\n";
            echo "              </tr>\n";
            echo "              <tr>\n";
            echo "                <td colspan=\"2\" class=\"noBorder\" style=\"height:35px;\">Final Rush&nbsp;Yds</td>\n";
            echo "              </tr>\n";
            echo "            </table>\n";
            echo "          </td>\n";
            echo "          <td class=\"headerBackgroundTable\" style=\"width:4.5%; font-size:10px;\">\n";
            echo "            <table class=\"gameScoreTable\" name=\"game" . $games[$i]["gameID"] . "-PassYds2Q\">\n";
            echo "              <tr onClick=\"ForceWinner('" . $games[$i]["gameID"] . "-PassYds2Q','" . $games[$i]["awayTeam"] . "');\">\n";
            echo "                <td class=\"posTop\" style=\"background-color:" . 
                (($games[$i]["awayPassYds2Q"] > $games[$i]["homePassYds2Q"]) ? "#409840" : "#D9DCE3") . ";\"><div class=\"posTeam\">" . 
                $teamAliases[$games[$i]["awayTeam"]] . "<div class=\"imgDiv\"><img class=\"teamLogo\" src=\"" . 
                getIcon($games[$i]["awayTeam"], $_SESSION["showPicksSeason"]) . "\"/></div></div></td>\n";
            echo "              </tr>\n";
            echo "              <tr onClick=\"ForceWinner('" . $games[$i]["gameID"] . "-PassYds2Q','TIE');\">\n";
            echo "                <td class=\"posOther\" style=\"background-color:" . 
                (($games[$i]["awayPassYds2Q"] == $games[$i]["homePassYds2Q"]) ? "#409840" : "#D9DCE3") . ";\">Tie</td>\n";
            echo "              </tr>\n";
            echo "              <tr onClick=\"ForceWinner('" . $games[$i]["gameID"] . "-PassYds2Q','" . $games[$i]["homeTeam"] . "');\">\n";
            echo "                <td class=\"posOther\" style=\"background-color:" . 
                (($games[$i]["awayPassYds2Q"] < $games[$i]["homePassYds2Q"]) ? "#409840" : "#D9DCE3") . ";\"><div class=\"posTeam\">" . 
                $teamAliases[$games[$i]["homeTeam"]] . "<div class=\"imgDiv\"><img class=\"teamLogo\" src=\"" .
                getIcon($games[$i]["homeTeam"], $_SESSION["showPicksSeason"]) . "\"/></div></div></td>\n";
            echo "              </tr>\n";
            echo "              <tr>\n";
            echo "                <td colspan=\"2\" class=\"noBorder\" style=\"height:35px;\">Halftime Pass&nbsp;Yds</td>\n";
            echo "              </tr>\n";
            echo "            </table>\n";
            echo "          </td>\n";
            echo "          <td class=\"headerBackgroundTable\" style=\"width:4.5%; font-size:10px;\">\n";
            echo "            <table class=\"gameScoreTable\" name=\"game" . $games[$i]["gameID"] . "-PassYds\">\n";
            echo "              <tr onClick=\"ForceWinner('" . $games[$i]["gameID"] . "-PassYds','" . $games[$i]["awayTeam"] . "');\">\n";
            echo "                <td class=\"posTop\" style=\"background-color:" . 
                (($games[$i]["awayPassYds"] > $games[$i]["homePassYds"]) ? "#409840" : "#D9DCE3") . ";\"><div class=\"posTeam\">" . 
                $teamAliases[$games[$i]["awayTeam"]] . "<div class=\"imgDiv\"><img class=\"teamLogo\" src=\"" . 
                getIcon($games[$i]["awayTeam"], $_SESSION["showPicksSeason"]) . "\"/></div></div></td>\n";
            echo "              </tr>\n";
            echo "              <tr onClick=\"ForceWinner('" . $games[$i]["gameID"] . "-PassYds','TIE');\">\n";
            echo "                <td class=\"posOther\" style=\"background-color:" . 
                (($games[$i]["awayPassYds"] == $games[$i]["homePassYds"]) ? "#409840" : "#D9DCE3") . ";\">Tie</td>\n";
            echo "              </tr>\n";
            echo "              <tr onClick=\"ForceWinner('" . $games[$i]["gameID"] . "-PassYds','" . $games[$i]["homeTeam"] . "');\">\n";
            echo "                <td class=\"posOther\" style=\"background-color:" . 
                (($games[$i]["awayPassYds"] < $games[$i]["homePassYds"]) ? "#409840" : "#D9DCE3") . ";\"><div class=\"posTeam\">" . 
                $teamAliases[$games[$i]["homeTeam"]] . "<div class=\"imgDiv\"><img class=\"teamLogo\" src=\"" .
                getIcon($games[$i]["homeTeam"], $_SESSION["showPicksSeason"]) . "\"/></div></div></td>\n";
            echo "              </tr>\n";
            echo "              <tr>\n";
            echo "                <td colspan=\"2\" class=\"noBorder\" style=\"height:35px;\">Final Pass&nbsp;Yds</td>\n";
            echo "              </tr>\n";
            echo "            </table>\n";
            echo "          </td>\n";
            echo "          <td class=\"headerBackgroundTable\" style=\"width:4.5%; font-size:10px;\">\n";
            echo "            <table class=\"gameScoreTable\" name=\"game" . $games[$i]["gameID"] . "-Score1Q\">\n";
            echo "              <tr onClick=\"ForceWinner('" . $games[$i]["gameID"] . "-Score1Q','" . $games[$i]["awayTeam"] . "');\">\n";
            echo "                <td class=\"posTop\" style=\"background-color:" . 
                (($games[$i]["awayScore1Q"] > $games[$i]["homeScore1Q"]) ? "#409840" : "#D9DCE3") . ";\"><div class=\"posTeam\">" . 
                $teamAliases[$games[$i]["awayTeam"]] . "<div class=\"imgDiv\"><img class=\"teamLogo\" src=\"" . 
                getIcon($games[$i]["awayTeam"], $_SESSION["showPicksSeason"]) . "\"/></div></div></td>\n";
            echo "              </tr>\n";
            echo "              <tr onClick=\"ForceWinner('" . $games[$i]["gameID"] . "-Score1Q','TIE');\">\n";
            echo "                <td class=\"posOther\" style=\"background-color:" . 
                (($games[$i]["awayScore1Q"] == $games[$i]["homeScore1Q"]) ? "#409840" : "#D9DCE3") . ";\">Tie</td>\n";
            echo "              </tr>\n";
            echo "              <tr onClick=\"ForceWinner('" . $games[$i]["gameID"] . "-Score1Q','" . $games[$i]["homeTeam"] . "');\">\n";
            echo "                <td class=\"posOther\" style=\"background-color:" . 
                (($games[$i]["awayScore1Q"] < $games[$i]["homeScore1Q"]) ? "#409840" : "#D9DCE3") . ";\"><div class=\"posTeam\">" . 
                $teamAliases[$games[$i]["homeTeam"]] . "<div class=\"imgDiv\"><img class=\"teamLogo\" src=\"" .
                getIcon($games[$i]["homeTeam"], $_SESSION["showPicksSeason"]) . "\"/></div></div></td>\n";
            echo "              </tr>\n";
            echo "              <tr>\n";
            echo "                <td colspan=\"2\" class=\"noBorder\" style=\"height:35px;\">1Q Score</td>\n";
            echo "              </tr>\n";
            echo "            </table>\n";
            echo "          </td>\n";
          }
          echo "          <td class=\"headerBackgroundTable\" style=\"width:4.5%; font-size:10px;" . 
              (($_SESSION["showPicksWeek"]==20) ? " border-right:none;" : "") . "\">\n";
          echo "            <table class=\"gameScoreTable\" name=\"game" . $games[$i]["gameID"] . "-Score2Q\">\n";
          echo "              <tr onClick=\"ForceWinner('" . $games[$i]["gameID"] . "-Score2Q','" . $games[$i]["awayTeam"] . "');\">\n";
          echo "                <td class=\"posTop\" style=\"background-color:" . 
              (($games[$i]["awayScore2Q"] > $games[$i]["homeScore2Q"]) ? "#409840" : "#D9DCE3") . ";\"><div class=\"posTeam\">" . 
              $teamAliases[$games[$i]["awayTeam"]] . "<div class=\"imgDiv\"><img class=\"teamLogo\" src=\"" . 
              getIcon($games[$i]["awayTeam"], $_SESSION["showPicksSeason"]) . "\"/></div></div></td>\n";
          echo "              </tr>\n";
          echo "              <tr onClick=\"ForceWinner('" . $games[$i]["gameID"] . "-Score2Q','TIE');\">\n";
          echo "                <td class=\"posOther\" style=\"background-color:" . 
              (($games[$i]["awayScore2Q"] == $games[$i]["homeScore2Q"]) ? "#409840" : "#D9DCE3") . ";\">Tie</td>\n";
          echo "              </tr>\n";
          echo "              <tr onClick=\"ForceWinner('" . $games[$i]["gameID"] . "-Score2Q','" . $games[$i]["homeTeam"] . "');\">\n";
          echo "                <td class=\"posOther\" style=\"background-color:" . 
              (($games[$i]["awayScore2Q"] < $games[$i]["homeScore2Q"]) ? "#409840" : "#D9DCE3") . ";\"><div class=\"posTeam\">" . 
              $teamAliases[$games[$i]["homeTeam"]] . "<div class=\"imgDiv\"><img class=\"teamLogo\" src=\"" . 
              getIcon($games[$i]["homeTeam"], $_SESSION["showPicksSeason"]) . "\"/></div></div></td>\n";
          echo "              </tr>\n";
          echo "              <tr>\n";
          echo "                <td colspan=\"2\" class=\"noBorder\" style=\"height:35px;\">Halftime Score</td>\n";
          echo "              </tr>\n";
          echo "            </table>\n";
          echo "          </td>\n";
          if( $_SESSION["showPicksWeek"] == 20 )
          {
?>
          <td class="headerBackgroundTable" style="width:3%; border-left:none;">
            <input type="submit" onclick="AdjustScore(<?php echo ($grouping . "," . ($grouping + (($grouping < 4) ? 4 : -4))); ?>,1);" value="+"><br>
            Halftime Score<br><span id="caption<?php echo $grouping; ?>"><?php echo ($games[$i]["awayScore2Q"] + $games[$i]["homeScore2Q"]); ?></span><br>
            <input type="submit" onclick="AdjustScore(<?php echo ($grouping . "," . ($grouping + (($grouping < 4) ? 4 : -4))); ?>,-1);" value="-">
          </td>
<?php
            $grouping++;
          }
          else
          {           
            echo "          <td class=\"headerBackgroundTable\" style=\"width:4.5%; font-size:10px;\">\n";
            echo "            <table class=\"gameScoreTable\" name=\"game" . $games[$i]["gameID"] . "-Score3Q\">\n";
            echo "              <tr onClick=\"ForceWinner('" . $games[$i]["gameID"] . "-Score3Q','" . $games[$i]["awayTeam"] . "');\">\n";
            echo "                <td class=\"posTop\" style=\"background-color:" . 
                (($games[$i]["awayScore3Q"] > $games[$i]["homeScore3Q"]) ? "#409840" : "#D9DCE3") . ";\"><div class=\"posTeam\">" . 
                $teamAliases[$games[$i]["awayTeam"]] . "<div class=\"imgDiv\"><img class=\"teamLogo\" src=\"" . 
                getIcon($games[$i]["awayTeam"], $_SESSION["showPicksSeason"]) . "\"/></div></div></td>\n";
            echo "              </tr>\n";
            echo "              <tr onClick=\"ForceWinner('" . $games[$i]["gameID"] . "-Score3Q','TIE');\">\n";
            echo "                <td class=\"posOther\" style=\"background-color:" . 
                (($games[$i]["awayScore3Q"] == $games[$i]["homeScore3Q"]) ? "#409840" : "#D9DCE3") . ";\">Tie</td>\n";
            echo "              </tr>\n";
            echo "              <tr onClick=\"ForceWinner('" . $games[$i]["gameID"] . "-Score3Q','" . $games[$i]["homeTeam"] . "');\">\n";
            echo "                <td class=\"posOther\" style=\"background-color:" . 
                (($games[$i]["awayScore3Q"] < $games[$i]["homeScore3Q"]) ? "#409840" : "#D9DCE3") . ";\"><div class=\"posTeam\">" . 
                $teamAliases[$games[$i]["homeTeam"]] . "<div class=\"imgDiv\"><img class=\"teamLogo\" src=\"" .
                getIcon($games[$i]["homeTeam"], $_SESSION["showPicksSeason"]) . "\"/></div></div></td>\n";
            echo "              </tr>\n";
            echo "              <tr>\n";
            echo "                <td colspan=\"2\" class=\"noBorder\" style=\"height:35px;\">3Q Score</td>\n";
            echo "              </tr>\n";
            echo "            </table>\n";
            echo "          </td>\n";
          }
        }
        echo "          <td class=\"headerBackgroundTable\" style=\"width:4.5%; font-size:10px; border-right:none;\">\n";
        echo "            <table class=\"gameScoreTable\" name=\"game" . $games[$i]["gameID"] . "\">\n";
        echo "              <tr onClick=\"ForceWinner(" . $games[$i]["gameID"] . ",'" . $games[$i]["awayTeam"] . "');\">\n";
        echo "                <td class=\"posTop\" style=\"background-color:" . 
            (($games[$i]["awayScore"] > $games[$i]["homeScore"]) ? "#409840" : "#D9DCE3") . ";\"><div class=\"posTeam\">" . 
            $teamAliases[$games[$i]["awayTeam"]] . "<div class=\"imgDiv\"><img class=\"teamLogo\" src=\"" . getIcon($games[$i]["awayTeam"], $_SESSION["showPicksSeason"]) . 
            "\"/></div></div></td>\n";
        echo "              </tr>\n";
        echo "              <tr onClick=\"ForceWinner(" . $games[$i]["gameID"] . ",'TIE');\">\n";
        echo "                <td class=\"posOther\" style=\"background-color:" . 
            (($games[$i]["awayScore"] == $games[$i]["homeScore"]) ? "#409840" : "#D9DCE3") . ";\">Tie</td>\n";
        echo "              </tr>\n";
        echo "              <tr onClick=\"ForceWinner(" . $games[$i]["gameID"] . ",'" . $games[$i]["homeTeam"] . "');\">\n";
        echo "                <td class=\"posOther\" style=\"background-color:" . 
            (($games[$i]["awayScore"] < $games[$i]["homeScore"]) ? "#409840" : "#D9DCE3") . ";\"><div class=\"posTeam\">" . 
            $teamAliases[$games[$i]["homeTeam"]] . "<div class=\"imgDiv\"><img class=\"teamLogo\" src=\"" . getIcon($games[$i]["homeTeam"], $_SESSION["showPicksSeason"]) . 
            "\"/></div></div></td>\n";
        echo "              </tr>\n";
        if( $_SESSION["showPicksWeek"] >= 20 )
        {
          echo "              <tr>\n";
          echo "                <td colspan=\"2\" class=\"noBorder\" style=\"height:35px;\">Final</td>\n";
          echo "              </tr>\n";
        }
        echo "            </table>\n";
        echo "          </td>\n";
?>
          <td class="headerBackgroundTable" style="width:3%; border-left:none;">
            <input type="submit" onclick="AdjustScore(<?php echo ($grouping . "," . ($grouping + (($grouping < 4) ? 4 : -4))); ?>,1);" value="+"><br>
            Score<br><span id="caption<?php echo $grouping; ?>"><?php echo ($games[$i]["awayScore"] + $games[$i]["homeScore"]); ?></span><br>
            <input type="submit" onclick="AdjustScore(<?php echo ($grouping . "," . ($grouping + (($grouping < 4) ? 4 : -4))); ?>,-1);" value="-">
          </td>
<?php
        $grouping++;
      }
?>
          <td style="display:none">TB1</td>
          <td style="display:none">TB2</td>
          <td style="display:none">TB3</td>
          <td class="headerBackgroundTable" style="width:3%; cursor:pointer;" onClick="SortTable('points');">Total Points</td>
          <td class="headerBackgroundTable" style="width:3%; cursor:pointer;" onClick="SortTable('maxPts');">Max</td>
<?php
      if( $_SESSION["showPicksWeek"] < 22 )
      {
        echo "          <td class=\"headerBackgroundTable\" style=\"width:3%;\">Advance</td>\n";
      }
    }

    // print their rank and name
    if( $thisPick["userID"] != $userID )
    {
      $hasBye = ($_SESSION["showPicksWeek"] == 18 && $thisPick["firstRoundBye"] == "Y");
      $eliminated = ($_SESSION["showPicksWeek"] >= 19 && $thisPick["prevWeek1"] < 0) || 
                    ($_SESSION["showPicksWeek"] >= 20 && $thisPick["prevWeek2"] < 0) || 
                    ($_SESSION["showPicksWeek"] == 22 && $thisPick["prevWeek3"] < 0);
      $baseJK = $jk;

      // get their rank if theyre not on a bye
      if( !$hasBye && !$eliminated )
      {
        $playerCount++;
        if( $thisPick["wPts"] < $currScore )
        {
          $currRank = $playerCount;
          $currScore = $thisPick["wPts"];
        }
      }
      $possibleMax = 0;

      $userID = $thisPick["userID"];
      echo "        </tr>\n        <tr class=\"" . (($myID == $thisPick["userID"]) ? "my" : "table") . "Row\" style=\"color:#" . 
          (($thisPick["advances"] == "Y") ? "007500" : (($thisPick["advances"] == "N") ? "AF0000" : "888800")) . 
          "\">\n          <td class=\"lightBackgroundTable\">" . (($hasBye || $eliminated) ? "--" : $currRank) . 
          "</td>\n          " . "<td class=\"lightBackgroundTable" . (($myID == $thisPick["userID"]) ? " myName\" id=\"myPicks" : "") . 
          "\">" . $thisPick["pName"] . "</td>\n";
      echo "          <td class=\"lightBackgroundTable\">" . $thisPick["sPts"] . "</td>\n";

      // show wild card score
      if( $_SESSION["showPicksWeek"] > 18 )
      {
        echo "          <td class=\"lightBackgroundTable\" style=\"height:100%;\">" . (($thisPick["prevWeek1"] == 0) 
            ? "Bye" : (($thisPick["prevWeek1"] < 0) ? (($thisPick["prevWeek1"] + 1) * -1) : $thisPick["prevWeek1"])). "</td>\n";
      }
      // show divisional score
      if( $_SESSION["showPicksWeek"] > 19 )
      {
        echo "          <td class=\"lightBackgroundTable\" style=\"height:100%;\">" . (($thisPick["prevWeek1"] < 0) 
            ? "--&nbsp;" : (($thisPick["prevWeek2"] < 0) ? (($thisPick["prevWeek2"] + 1) * -1) : $thisPick["prevWeek2"])) . "</td>\n";
      }
      // show conference score
      if( $_SESSION["showPicksWeek"] > 20 )
      {
        echo "          <td class=\"lightBackgroundTable\" style=\"height:100%;\">" . 
            ((($thisPick["prevWeek1"] < 0) || ($thisPick["prevWeek2"] < 0)) ? "--&nbsp;" : 
            (($thisPick["prevWeek3"] < 0) ? (($thisPick["prevWeek3"] + 1) * -1) : $thisPick["prevWeek3"])) . "</td>\n";
        echo "          <td style=\"display:none;\">" . $thisPick["weeklyWins"] . "</td>\n";
      }
    }

    // show their pick
    echo "          <td class=\"lightBackgroundTable\" style=\"height:100%;\">";
    if($hasBye || $eliminated)
    {
      echo "--";
    }
    else if($thisPick["winner"] == "" && !$poolLocked)
    {
      echo "--";
    }
    else if($thisPick["winner"] == "")
    {
      echo "<div class=\"cellShadeOuter\">\n";
      echo "<div class=\"cellShadeBG\" style=\"background-color:#FF0000;\"></div>\n";
      echo "<table class=\"cellShadeTable\"><tr><td class=\"noBorder\"><span class=\"blankIt\">MIS 19</span><br>";
      echo "<div class=\"imgDiv blankIt\"><img class=\"teamLogo\" src=\"" . getIcon("BUF", $_SESSION["showPicksSeason"]) . "\"/></div>";
      echo "<div class=\"centerIt\" style=\"color:#BF0000;\">Missed<br>(" . $thisPick["pPts"] . ")</div></td></tr></table>";
      echo "</div>\n";
    }
    else if( !$poolLocked && $thisPick["userID"] != $myID )
    {
      echo "X";
    }
    else
    {
      echo "<div class=\"cellShadeOuter\">\n";
      echo "<div class=\"cellShadeBG\"" . ((($thisPick["gStatus"] == 1) || ($thisPick["gStatus"] == 19)) ? "" : 
          (" style=\"background-color:#" . 
          (($thisPick["winner"] == $thisPick["leader"]) ? "00AA00": "FF0000") . ";\"")) . "></div>\n";
      $span = "<span style=\"color:#" . 
          ((($thisPick["gStatus"] == 1) || ($thisPick["gStatus"] == 19)) ? "0A1F42" : 
          (($thisPick["winner"] == $thisPick["leader"]) ? "007500": "BF0000")) . ";\">" . $teamAliases[$thisPick["winner"]] . 
          (($thisPick["pPts"] > 0) ? (" " . $thisPick["pPts"]) : "") . "</span>";
      echo "<table class=\"cellShadeTable\"><tr><td class=\"noBorder\">" . ($logosHidden ? 
            ("<div class=\"centerIt\">" . $span . "</div><div class=\"blankIt\">") : "") . $span . "<br>";
      echo "<div class=\"imgDiv\"><img class=\"teamLogo\" src=\"" . (($thisPick["winner"] != "TIE") ? 
          getIcon($thisPick["winner"], $_SESSION["showPicksSeason"]) : 
          getIcon("", $_SESSION["showPicksSeason"])) . "\"/></div>";
      echo ($logosHidden ? "</div>" : "") . "</td></tr></table>";
      echo "</div>\n";
    }
    echo "</td>\n";

    // dump the extra slots
    if( $_SESSION["showPicksWeek"] >= 20 && $eliminated )
    {
      $extras = ($_SESSION["showPicksWeek"] == 20) ? 2 : 9;
      for( $z=0; $z<$extras; $z++ )
      {
        echo "          <td class=\"lightBackgroundTable\" style=\"height:100%;\">--</td>\n";
      }
    }

    // factor it into the max
    $possibleMax += ((($thisPick["gStatus"] != 1) && ($thisPick["winner"] != $thisPick["leader"])) ||      // final and theyre wrong
                     ($poolLocked && ($thisPick["winner"] == "")))                                  // they missed it
                    ? 0 : $thisPick["pPts"];

    if( $_SESSION["showPicksWeek"] < 22 )
    {
      // show their score pick
      $radix = ((($_SESSION["showPicksWeek"] == 18) && ($_SESSION["showPicksSeason"] > 2019)) ? 6 : 4);
      $toggle = (($jk - $baseJK) % $radix);
      $toggle = (($toggle == 1) && ($_SESSION["showPicksWeek"] == 20)) ? 2 : 
                ((($toggle == 2) && ($_SESSION["showPicksWeek"] == 20)) ? 1 : $toggle);
      $tbName = "tieBreaker" . ($radix - ($toggle % $radix));
      echo "          <td class=\"lightBackgroundTable\">" . (($hasBye || $eliminated) ? "--" : (($thisPick[$tbName] == "0") 
          ? "--" : ((!$poolLocked && $thisPick["userID"] != $myID) 
                   ? "X" : $thisPick[$tbName]))) . "</td>\n";
    }

    // see if that ends this person's picks
    if( ($nextPick == null) || ($nextPick["userID"] != $thisPick["userID"]) )
    {
      if( $_SESSION["showPicksWeek"] == 22 )
      {
        echo "          <td class=\"lightBackgroundTable\">" . (($hasBye || $eliminated) ? "--" : (($thisPick["tieBreaker1"] == "0") 
            ? "--" : ((!$poolLocked && $thisPick["userID"] != $myID) 
                     ? "X" : $thisPick["tieBreaker1"]))) . "</td>\n";
      }
      echo "          <td style=\"display:none\">" . $thisPick["tb1"] . "</td>\n";
      echo "          <td style=\"display:none\">" . $thisPick["tb2"] . "</td>\n";
      echo "          <td style=\"display:none\">" . (($games[0]["status"] == 1) ? "0" : $thisPick["tieBreaker1"]) . "</td>\n";
      echo "          <td class=\"lightBackgroundTable\">" . ($hasBye ? "Bye" : ($eliminated ? "Out" : $thisPick["wPts"])) . "</td>\n";
      echo "          <td class=\"lightBackgroundTable\">" . ($hasBye ? "--" : ($eliminated ? "--&nbsp;" : $possibleMax)) . "</td>\n";
      if( $_SESSION["showPicksWeek"] < 22 )
      {
        echo "          <td class=\"lightBackgroundTable\">" . (($thisPick["advances"] == "Y") ? "Yes" : 
            (($thisPick["advances"] == "N") ? "No" : "Maybe")) . "</td>\n";
      }
    }
  }
  echo "        </tr>\n";
?>
        <script type="text/javascript">
          var mostRecentSort = "points";

          var sorting = false;
          function SortTable(arg)
          {
            // guard against threading
            if( sorting )
            {
              return;
            }
            sorting = true;
            var scrollTop = $(window).scrollTop();

            mostRecentSort = arg;
            var i1 = 0;
            var rows = document.getElementById("reloadableTable").rows;
            while( i1 < rows.length )
            {
              // skip non-data rows
              if( rows[i1].cells[0].className != "lightBackgroundTable" )
              {
                i1 += 1;
              }
              else
              {
                // find the end of this section
                var i2 = i1;
                while( i2 >= 0 && i2 < rows.length && rows[i2].cells[0].className == "lightBackgroundTable" )
                {
                  i2 += 1;
                }

                // sort these rows
                var compareIndex = rows[i1].cells.length - <?php echo (($_SESSION["showPicksWeek"] == 22) ? "2" : "3"); ?>;
                if( arg == "maxPts" )
                {
                  compareIndex += 1;
                }
                else if( arg == "confPts" )
                {
                  compareIndex = 5;
                }
                else if( arg == "divPts" )
                {
                  compareIndex = 4;
                }
                else if( arg == "wcPts" )
                {
                  compareIndex = 3;
                }
                else if( arg == "ytdPts" )
                {
                  compareIndex = 2;
                }
                else if( arg == "name" )
                {
                  compareIndex = 1;
                }

                // do the heavy lifting of the sort
                SmartSort(i1, i2, compareIndex, (arg != "name"));

                // increment the counter
                i1 = i2;
              }
            }

<?php
  if( $_SESSION["showPicksWeek"] == 22 )
  {
    echo "            SuperBowlTiebreakers();\n";
  } 
  else
  {
    echo "            FixAdvance();\n";
  }
?>

            // tell them it's safe
            sorting = false;
            $('html, body').scrollTop(scrollTop);
          }

          // this does a hybrid of mergesort and insertion sort
          function SmartSort(start, end, compareIndex, isNumeric)
          {
            // grab the MNF score
<?php
  if( $_SESSION["showPicksWeek"] < 22 )
  {
    if( $_SESSION["showPicksWeek"] == 20 )
    {
?>
            var TB2 = parseInt( document.getElementById("caption1").innerHTML );
            var TB3 = parseInt( document.getElementById("caption2").innerHTML );
<?php
    }
    else
    {
?>
            var TB2 = parseInt( document.getElementById("caption2").innerHTML );
            var TB3 = parseInt( document.getElementById("caption1").innerHTML );
<?php
    }
?>
            var TB1 = parseInt( document.getElementById("caption3").innerHTML );
            var TB4 = parseInt( document.getElementById("caption0").innerHTML );
<?php
  }
  else
  {
?>
            var TB1 = parseInt( document.getElementById("caption0").innerHTML );
<?php
  }
?>

            // create the initial arrays
            var rows = document.getElementById("reloadableTable").rows;
            var myRows = [];
            for( var j=start; j<end; j+=1 )
            {
              var thisVal = rows[j].cells[compareIndex].innerHTML;
              if( isNumeric && ((thisVal == "Bye") || (thisVal == "--")) )
              {
                thisVal = 100;
              }
              else if( isNumeric && ((thisVal == "Out") || (thisVal == "--&nbsp;")) )
              {
                thisVal = -100;
              }
              else if( isNumeric )
              {
                thisVal = parseInt(thisVal);
              }
              else
              {
                thisVal = thisVal.slice(thisVal.lastIndexOf(" ")) + " " + thisVal.slice(0, thisVal.lastIndexOf(" "));
              }

<?php
  if( $_SESSION["showPicksWeek"] < 22 )
  {
    if( $_SESSION["showPicksWeek"] == 20 )
    {
?>
              var thisTB2 = parseInt(rows[j].cells[rows[j].cells.length - 11].innerHTML );
              var thisTB3 = parseInt(rows[j].cells[rows[j].cells.length - 9].innerHTML );
<?php
    }
    else
    {
?>
              var thisTB2 = parseInt(rows[j].cells[rows[j].cells.length - 9].innerHTML );
              var thisTB3 = parseInt(rows[j].cells[rows[j].cells.length - 11].innerHTML );
<?php
    }
?>
              var thisTB1 = parseInt(rows[j].cells[rows[j].cells.length - 7].innerHTML );
              var thisTB4 = parseInt(rows[j].cells[rows[j].cells.length - 13].innerHTML );
              var thisTB5 = parseInt(rows[j].cells[2].innerHTML );
<?php
  }
  else
  {
?>
              var thisTB1 = parseInt(rows[j].cells[rows[j].cells.length - 3].innerHTML );
              var thisTB2 = parseInt(rows[j].cells[rows[j].cells.length - 6].innerHTML );
              var thisTB3 = parseInt(rows[j].cells[rows[j].cells.length - 5].innerHTML );
              var thisTB4 = parseInt(rows[j].cells[rows[j].cells.length - 4].innerHTML );
<?php
  }
?>
              var thisRow = [j, thisVal, isNumeric<?php 
  echo ($poolLocked ? (", Math.abs(TB1 - thisTB1), false, thisTB1, false" . (($_SESSION["showPicksWeek"] < 22) 
      ? (", Math.abs(TB2 - thisTB2), false, thisTB2, false, Math.abs(TB3 - thisTB3), false, thisTB3, false, Math.abs(TB4 - thisTB4), false, thisTB4, false" . 
        (($_SESSION["showPicksWeek"] == 18) ? ", thisTB5, true" : ""))
      : "")) : ""); ?>];
              myRows.push(thisRow);
            }

            // now run the helper
            SmartSortHelper(myRows, 0, myRows.length);

            // now dump it all back out to the visuals
            var myHTMLs = [];
            var rank = 1, count = 0, max = 3000, thisScore;
            for( var j=0; j<myRows.length; j+=1 )
            {
              myHTMLs.push( rows[myRows[j][0]].innerHTML );
            }
            for( var j=0; j<myHTMLs.length; j+=1 )
            {
              rows[j + start].innerHTML = myHTMLs[j];
              // fix the row so it highlights me
              rows[j + start].className = (rows[j + start].contains(document.getElementById("myPicks")) ? "myRow" : "tableRow");
              thisScore = rows[j+start].cells[rows[j+start].cells.length - <?php 
                echo ($_SESSION["showPicksWeek"] == 22) ? 2 : 3; ?>].innerHTML;
              if( thisScore != "Bye" && thisScore != "Out" && mostRecentSort == "points" ) 
              {
                thisScore = parseInt(thisScore);
                if( thisScore < max )
                {
                  max = thisScore;
                  rank += count;
                  count = 0;
                }
                rows[j+start].cells[0].innerHTML = rank.toString();
                count++;
              }
            }
          }

          function SuperBowlTiebreakers()
          {
            var i1 = 0;
            var rows = document.getElementById("reloadableTable").rows;

            // sort these rows
            var pointsIndex = rows[6].cells.length - 2;
            var tb0Index = pointsIndex - 3;
            var tb1Index = pointsIndex - 2;
            var tb2Index = pointsIndex - 1;
            while( i1 < rows.length )
            {
              // skip non-data rows
              if( rows[i1].cells[0].className != "lightBackgroundTable" )
              {
                i1 += 1;
              }
              else
              {
                var i2 = i1 + 10;

                // selection sort
                for( var j=i1; j<i2; j+=1 )
                {
                  var maxIndex = j;
                  var maxTB0 = parseInt(rows[maxIndex].cells[tb0Index].innerHTML);
                  var maxPoints = parseInt(rows[maxIndex].cells[pointsIndex].innerHTML);
                  var maxTB1 = parseInt(rows[maxIndex].cells[tb1Index].innerHTML);
                  var maxTB2 = parseInt(rows[maxIndex].cells[tb2Index].innerHTML);
                  for( var k=j+1; k<i2; k+=1 )
                  {
                    var thisTB0 = parseInt(rows[k].cells[tb0Index].innerHTML);
                    var thisPoints = parseInt(rows[k].cells[pointsIndex].innerHTML);
                    var thisTB1 = parseInt(rows[k].cells[tb1Index].innerHTML);
                    var thisTB2 = parseInt(rows[k].cells[tb2Index].innerHTML);
                    if( (thisTB0 == maxTB0) && (thisPoints == maxPoints) && (thisTB1 == maxTB1) && (thisTB2 == maxTB2) )
                    {
                      maxIndex = k;
                    }
                  }

                  // if they need to be reordered, do it
                  var useWC = true;
                  for( var k=j; k<maxIndex && useWC; k+=1 )
                  {
                    useWC = (rows[k].cells[3].innerHTML != "Bye");
                  }
                  // selection sort
                  for( var k=j; k<maxIndex; k+=1 )
                  {
                    var maxInnerIndex = k;
                    var maxTB3 = parseInt(rows[maxInnerIndex].cells[4].innerHTML) + parseInt(rows[maxInnerIndex].cells[5].innerHTML);
                    if( useWC )
                    {
                      maxTB3 += parseInt(rows[maxInnerIndex].cells[3].innerHTML);
                    }
                    var maxTB4 = parseInt(rows[maxInnerIndex].cells[2].innerHTML);
                    var maxTB5 = parseInt(rows[maxInnerIndex].cells[6].innerHTML);
                    for( var m=k+1; m<maxIndex + 1; m+=1 )
                    {
                      var thisTB3 = parseInt(rows[m].cells[4].innerHTML) + parseInt(rows[m].cells[5].innerHTML);
                      if( useWC )
                      {
                        thisTB3 += parseInt(rows[m].cells[3].innerHTML);
                      }
                      var thisTB4 = parseInt(rows[m].cells[2].innerHTML);
                      var thisTB5 = parseInt(rows[m].cells[6].innerHTML);
                      if( (thisTB3 > maxTB3) || ((thisTB3 == maxTB3) && ((thisTB4 > maxTB4) || ((thisTB4 == maxTB4) && (thisTB5 > maxTB5)))) )
                      {
                        maxInnerIndex = m;
                        maxTB3 = thisTB3;
                        maxTB4 = thisTB4;
                        maxTB5 = thisTB5;
                      }
                    }

                    // if they need to swap, do it
                    if( maxInnerIndex != k )
                    {
                      var swap = rows[k].innerHTML;
                      rows[k].innerHTML = rows[maxInnerIndex].innerHTML;
                      rows[maxInnerIndex].innerHTML = swap;
                      swap = rows[k].style.color;
                      rows[k].style.color = rows[maxInnerIndex].style.color;
                      rows[maxInnerIndex].style.color = swap;
                    }
                  }
                }

                // increment the counter
                i1 = rows.length;
              }
            }
          }

          function ReloadPage(args)
          {
            var xmlhttp;
            if (window.XMLHttpRequest)
            {//code for IE7+, Firefox, Chrome, Opera, Safari
              xmlhttp=new XMLHttpRequest();
            }
            else
            {
              xmlhttp=new ActiveXObject("Microsoft.XMLHTTP");
            }

            xmlhttp.onreadystatechange=function()
            {
              if (xmlhttp.readyState==4 && xmlhttp.status==200)
              {
                // tack on the new elements
                document.getElementById("reloadableTable").innerHTML = xmlhttp.responseText;
              }
            }

            xmlhttp.open("GET", "display/ShowPlayoffPossibilitiesTable.php?type=" + args, true);
            xmlhttp.send();
          }

          var recalculating = false;
          function ForceWinner(gameID, winner)
          {
            // guard against threading
            if( recalculating )
            {
              return;
            }
            recalculating = true;

/*
            mostRecentSort = "weekPts";
            ReloadPage("force&forcedWinnerGameID=" + gameID + "&forcedWinner=" + winner );
/**/
            var i1 = 0;
            var rows = document.getElementById("reloadableTable").rows;
            var checkIndex = -1;
            var teamAliases = {<?php
              foreach($teamAliases as $thisID => $thisAlias) {
                echo $thisID . ":\"" . $thisAlias . "\",";
              }
              echo "19:19";
            ?>};
            while( i1 < rows.length )
            {
              // skip non-data rows
              if( rows[i1].cells[0].className != "lightBackgroundTable" )
              {
                // see if its the scores row, and if so, lets look for the right game
                if( rows[i1].cells.length > 1 )
                {
                  for( var j=<?php echo (($_SESSION["showPicksWeek"] == 18) ? 3 : 
                                        (($_SESSION["showPicksWeek"] == 19) ? 4 : 
                                        (($_SESSION["showPicksWeek"] == 20) ? 5 : 7))) ?>; j<rows[i1].cells.length - <?php
  echo (($_SESSION["showPicksWeek"] == 22) ? 6 : 7) ?>; j+= <?php echo (($_SESSION["showPicksWeek"] == 22) ? 1 : 2) ?>)
                  {
                    var t = rows[i1].cells[j].firstElementChild;
                    if( t.getAttribute("name") == "game" + gameID )
                    {
                      if( checkIndex == -1 )
                      {
                        checkIndex = j;
                      }
                      var aTeam = t.rows[0].cells[0].firstElementChild.innerHTML;
                      aTeam = aTeam.slice(0, aTeam.indexOf("<"));
                      t.rows[0].cells[0].style.backgroundColor = (teamAliases[winner] == aTeam) ? "#409840" : "#D9DCE3";
                      t.rows[1].cells[0].style.backgroundColor = (winner == "TIE") ? "#409840" : "#D9DCE3";
                      var hTeam = t.rows[2].cells[0].firstElementChild.innerHTML;
                      hTeam = hTeam.slice(0, hTeam.indexOf("<"));
                      t.rows[2].cells[0].style.backgroundColor = (teamAliases[winner] == hTeam) ? "#409840" : "#D9DCE3";
                    }
                  }
                }

                i1 += 1;
              }
              else
              {
                // find the end of this section
                var i2 = i1;
                while( i2 >= 0 && i2 < rows.length && rows[i2].cells[0].className == "lightBackgroundTable" )
                {
                  i2 += 1;
                }

                // iterate over these rows and see whether that person made the pick we just changed
                for( var j=i1; j<i2; j+=1 )
                {
                  if( rows[j].cells[checkIndex].firstElementChild != null )
                  {
                    var BG = rows[j].cells[checkIndex].firstElementChild.firstElementChild;
                    var txt = BG.nextElementSibling.rows[0].cells[0].firstElementChild<?php echo ($logosHidden ? ".firstElementChild" : ""); ?>;
                    var wasRight = (BG.style.backgroundColor == "rgb(0, 170, 0)");
                    var wasWrong = (BG.style.backgroundColor == "rgb(255, 0, 0)");
                    var nowRight = (txt.innerHTML.slice(0, teamAliases[winner].length) == teamAliases[winner]);
                    //var nowRight = (txt.innerHTML.slice(0, teamAliases[winner.length]) == ((winner == "NONE") ? "TIE " : teamAliases[winner]));
                    BG.style.backgroundColor = (nowRight ? "#00AA00": "#FF0000");
                    txt.style.color = (nowRight ? "#007500": "#AF0000");
                    // update their scores if we need to
                    if( ((wasRight != nowRight) || (!wasWrong && !wasRight)) <?php echo (($_SESSION["showPicksWeek"] == 22) ? " && checkIndex != 16" : ""); ?> )
                    {
                      var score = parseInt(txt.innerHTML.slice(txt.innerHTML.indexOf(" ") + 1)) * (nowRight ? 1 : -1);
                      if( nowRight || wasRight ) {
                        var pts = rows[j].cells[rows[j].cells.length - 3];
                        pts.innerHTML = parseInt(pts.innerHTML) + score;
                      }
                      if( !nowRight || wasWrong ) {
                        var max = rows[j].cells[rows[j].cells.length - 2];
                        max.innerHTML = parseInt(max.innerHTML) + score;
                      }
                    }
<?php
  if( $_SESSION["showPicksWeek"] == 22 )
  {
?>
                    // fix their row color
                    else if( checkIndex == 16 )
                    {
                      rows[j].style.color = (nowRight ? "#007500": "#AF0000");
                    }
<?php
  }
?>
                  }
                }

                i1 = i2;
              }
            }

            SortTable("points");
/**/

            // tell them it's safe
            recalculating = false;
          }

          function AdjustScore(index1, index2, delta)
          {            
            // move the draggers to match
            var elem = document.getElementById("caption" + index1);
            if( elem != null )
            {
              // dont go into negatives
              var score = parseInt(elem.innerHTML);
              if( score == 0 && delta < 0 )
              {
                return;
              }
              else
              {
                elem.innerHTML = score + delta;
              }
            }

            var elem = document.getElementById("caption" + index2);
            if( index1 != index2 && elem != null )
            {
              // dont go into negatives
              var score = parseInt(elem.innerHTML);
              if( score == 0 && delta < 0 )
              {
                return;
              }
              else
              {
                elem.innerHTML = score + delta;
              }
            }

            SortTable("points");
          }

          function FixAdvance()
          {
<?php
  if( !$poolLocked ) {
?>
            // pool isn't locked yet, so skip all this nonsense
            return; 
<?php
  }
?>
            // fix the advance column
            var i1 = 0;
            var rows = document.getElementById("reloadableTable").rows;            
            while( i1 < rows.length )
            {
              if( rows[i1].cells[0].className == "lightBackgroundTable" )
              {
                // find the end of this section
                var i2 = i1;
                while( i2 >= 0 && i2 < rows.length && rows[i2].cells[0].className == "lightBackgroundTable" )
                {
                  i2 += 1;
                }

                // iterate over these rows and see whether that person made the pick we just changed
                for( var j=i1; j<i2; j+=1 )
                {
                  var advance = (rows[j].cells[rows[j].cells.length - 1].innerHTML == "Yes");
                  if( mostRecentSort == "points" )
                  {
                    advance = (j - i1) < <?php echo ($_SESSION["showPicksWeek"] == 20 ) ? 5 : (($_SESSION["showPicksWeek"] == 19) ? 10 : (($_SESSION["showPicksSeason"] < 2017) ? 20 : 21)); ?>;
                    rows[j].cells[rows[j].cells.length - 1].innerHTML = (advance ? "Yes" : "No");
                  }
                  rows[j].style.color = (advance ? "#007500": "#AF0000");
                }
                
                i1 = i2;
              }
              else
              {
                i1++;
              }
            }
          }

          // default sort
          SortTable(mostRecentSort);
        </script>
