<?php
  // start the session if we dont have one
  if(session_id() == '') {
  //if (session_status() == PHP_SESSION_NONE) {
    session_start();

    include "../util.php";
  }

  // set the palette
  $palette = ($_SESSION["cbm"] ?? false) ? ["#0072B2","#000000","#D55E00"] : ["#007500","#888800","#BF0000"];
  $palette2 = ($_SESSION["cbm"] ?? false) ? ["#42BBFF","#888888","#FF913B"] : ["#00AA00","#FFFF00","#FF0000"];

  // see what this type is
  $standingsType = isset($_GET["type"]) ? $_GET["type"] : "actual";

  // grab their userID so we can show their picks
  $myID = 0;
  $logosHidden = false;
  if( isset($_SESSION["spsID"]) )
  {
    $results = RunQuery( "select coalesce(userID, 0) as userID from Session where sessionID=" . $_SESSION["spsID"] );
    $myID = $results[0]["userID"];
    $logosHidden = (isset($_SESSION["spHideLogos"]) && $_SESSION["spHideLogos"] == "TRUE");
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
           "as status, Conference.name as cName, " . 
           (($_SESSION["showPicksWeek"] == (($_SESSION["showPicksSeason"] <= 2020) ? 22 : 23)) ? "1" : "confID") . " as section, " .
           "if(Game.status=3, 1, 0) as isFinal, Game.status as gStatus, weeklyWins, ";

  // fill in the tiebreaker data
  if( $_SESSION["showPicksWeek"] == (($_SESSION["showPicksSeason"] <= 2020) ? 22 : 23) )
  {
    $query .= "if(prevWeek1>=0, if(prevWeek2>=0, if(prevWeek3>=0, " . ($poolLocked ? "0" : "-prevWeek3") . 
              ", 100 + prevWeek3), 200 + prevWeek2), 300 + prevWeek1) as tb1, ";
    $query .= ($poolLocked ? ("abs(tieBreaker1 - " . ($games[0]["homeScore"] + $games[0]["awayScore"]) . ")") : "1") . " as tb2, ";
    $query .= "if(type='winner', 10, if(type='winner3Q', 9, if(type='winner2Q', 8, if(type='winner1Q', 7, " . 
              "if(type='passYds', 6, if(type='passYds2Q', 5, if(type='rushYds', 4, if(type='rushYds2Q', 3, " . 
              "if(type='TDs', 2, 1))))))))) as typeSort ";
    $sort = ", tb1 asc, wPts desc, tb2 asc" . ($poolLocked ? ", tieBreaker1" : "");
  }
  else if( $_SESSION["showPicksWeek"] == (($_SESSION["showPicksSeason"] <= 2020) ? 20 : 21) )
  {
    $query .= "if(prevWeek1>=0, if(prevWeek2>=0, " . ($poolLocked ? "0" : "-prevWeek2") . ", 100 + prevWeek2), 200 + prevWeek1) as tb1, ";
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
    $adjust = ($_SESSION["showPicksWeek"] == (($_SESSION["showPicksSeason"] <= 2020) ? 18 : 19)) ? 2 : 0;
    $query .= (($_SESSION["showPicksWeek"] == (($_SESSION["showPicksSeason"] <= 2020) ? 18 : 19)) ? "if(firstRoundBye='Y', 1, 2)" : 
               ($poolLocked ? "if(prevWeek1>=0, -50, 100 + prevWeek1)"
                            : "if(prevWeek1=0, -50, if(prevWeek1>0, -prevWeek1, 100 + prevWeek1))")) . " as tb1, ";
    $query .= ($poolLocked ? ("abs(tieBreaker1 - " . ($games[3 + $adjust]["homeScore"] + $games[3 + $adjust]["awayScore"]) . ")") : "1") . " as tb2, ";
    $query .= ($poolLocked ? ("abs(tieBreaker2 - " . ($games[2 + $adjust]["homeScore"] + $games[2 + $adjust]["awayScore"]) . ")") : "1") . " as tb3, ";
    $query .= ($poolLocked ? ("abs(tieBreaker3 - " . ($games[1 + $adjust]["homeScore"] + $games[1 + $adjust]["awayScore"]) . ")") : "1") . " as tb4, ";
    $query .= ($poolLocked ? ("abs(tieBreaker4 - " . ($games[0 + $adjust]["homeScore"] + $games[0 + $adjust]["awayScore"]) . ")") : "1") . " as tb5, ";
    $query .= (($poolLocked && $adjust) ? ("abs(tieBreaker5 - " . ($games[1]["homeScore"] + $games[1]["awayScore"]) . ")") : "1") . " as tb6, ";
    $query .= (($poolLocked && $adjust) ? ("abs(tieBreaker6 - " . ($games[0]["homeScore"] + $games[0]["awayScore"]) . ")") : "1") . " as tb7, ";
    $query .= "1 as typeSort ";
    $sort = ", tb1 asc, wPts desc, tb2 asc" . ($poolLocked ? ", tieBreaker1" : "") . ", tb3 asc" . 
           ($poolLocked ? ", tieBreaker2" : "") . ", tb4 asc" . ($poolLocked ? ", tieBreaker3" : "") . ", tb5 asc" . 
           ($poolLocked ? ", tieBreaker4" : "") . ", tb6 asc" . ($poolLocked ? ", tieBreaker5" : "") . ", tb7 asc" . 
           ($poolLocked ? ", tieBreaker6" : "");
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
  $colSpan = ($_SESSION["showPicksWeek"] == (($_SESSION["showPicksSeason"] <= 2020) ? 18 : 19)) ? (($_SESSION["showPicksSeason"] < 2020) ? 14 : 18) : 
             (($_SESSION["showPicksWeek"] == (($_SESSION["showPicksSeason"] <= 2020) ? 19 : 20)) ? 15 : 
             (($_SESSION["showPicksWeek"] == (($_SESSION["showPicksSeason"] <= 2020) ? 20 : 21)) ? 16 : 19));
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
      if( $_SESSION["showPicksWeek"] < (($_SESSION["showPicksSeason"] <= 2020) ? 22 : 23) )
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
      if( $_SESSION["showPicksWeek"] > (($_SESSION["showPicksSeason"] <= 2020) ? 18 : 19) )
      {
        echo "          <td class=\"headerBackgroundTable\" style=\"width:3%; cursor:pointer;\" onClick=\"SortTable('wcPts');\">Wild Card Round</td>\n";
      }
      if( $_SESSION["showPicksWeek"] > (($_SESSION["showPicksSeason"] <= 2020) ? 19 : 20) )
      {
        echo "          <td class=\"headerBackgroundTable\" style=\"width:3%; cursor:pointer;\" onClick=\"SortTable('divPts');\">Divisional Round</td>\n";
      }
      if( $_SESSION["showPicksWeek"] > (($_SESSION["showPicksSeason"] <= 2020) ? 20 : 21) )
      {
        echo "          <td class=\"headerBackgroundTable\" style=\"width:3%; cursor:pointer;\" onClick=\"SortTable('confPts');\">Conference Championship</td>\n";
      }

      // show the games from that week
      for( $i=0; $i<count($games); $i++ )
      {
        if( $_SESSION["showPicksWeek"] >= (($_SESSION["showPicksSeason"] <= 2020) ? 20 : 21) )
        {           
          if( $_SESSION["showPicksWeek"] == (($_SESSION["showPicksSeason"] <= 2020) ? 22 : 23) )
          {           
            echo "          <td style=\"display:none;\">Weekly Wins</td>\n";
            echo "          <td class=\"headerBackgroundTable\" style=\"width:4.5%; font-size:10px;\">\n";
            echo "            <table class=\"gameScoreTable\">\n";
            echo "              <tr>\n";
            echo "                <td>" . $teamAliases[$games[$i]["awayTeam"]] . "</td>\n";
            echo "                <td class=\"gsTL\">" . $games[$i]["awayTDs2Q"] . "</td>\n";
            echo "              </tr>\n";
            echo "              <tr>\n";
            echo "                <td class=\"gsBR\">" . $teamAliases[$games[$i]["homeTeam"]] . "</td>\n";
            echo "                <td class=\"gsBL\">" . $games[$i]["homeTDs2Q"] . "</td>\n";
            echo "              </tr>\n";
            echo "              <tr>\n";
            echo "                <td colspan=\"2\" class=\"noBorder\" style=\"height:35px;\">Halftime TDs</td>\n";
            echo "              </tr>\n";
            echo "            </table>\n";
            echo "          </td>\n";
            echo "          <td class=\"headerBackgroundTable\" style=\"width:4.5%; font-size:10px;\">\n";
            echo "            <table class=\"gameScoreTable\">\n";
            echo "              <tr>\n";
            echo "                <td>" . $teamAliases[$games[$i]["awayTeam"]] . "</td>\n";
            echo "                <td class=\"gsTL\">" . $games[$i]["awayTDs"] . "</td>\n";
            echo "              </tr>\n";
            echo "              <tr>\n";
            echo "                <td class=\"gsBR\">" . $teamAliases[$games[$i]["homeTeam"]] . "</td>\n";
            echo "                <td class=\"gsBL\">" . $games[$i]["homeTDs"] . "</td>\n";
            echo "              </tr>\n";
            echo "              <tr>\n";
            echo "                <td colspan=\"2\" class=\"noBorder\" style=\"height:35px;\">Final TDs</td>\n";
            echo "              </tr>\n";
            echo "            </table>\n";
            echo "          </td>\n";
            echo "          <td class=\"headerBackgroundTable\" style=\"width:4.5%; font-size:10px;\">\n";
            echo "            <table class=\"gameScoreTable\">\n";
            echo "              <tr>\n";
            echo "                <td>" . $teamAliases[$games[$i]["awayTeam"]] . "</td>\n";
            echo "                <td class=\"gsTL\">" . $games[$i]["awayRushYds2Q"] . "</td>\n";
            echo "              </tr>\n";
            echo "              <tr>\n";
            echo "                <td class=\"gsBR\">" . $teamAliases[$games[$i]["homeTeam"]] . "</td>\n";
            echo "                <td class=\"gsBL\">" . $games[$i]["homeRushYds2Q"] . "</td>\n";
            echo "              </tr>\n";
            echo "              <tr>\n";
            echo "                <td colspan=\"2\" class=\"noBorder\" style=\"height:35px;\">Halftime Rush&nbsp;Yds</td>\n";
            echo "              </tr>\n";
            echo "            </table>\n";
            echo "          </td>\n";
            echo "          <td class=\"headerBackgroundTable\" style=\"width:4.5%; font-size:10px;\">\n";
            echo "            <table class=\"gameScoreTable\">\n";
            echo "              <tr>\n";
            echo "                <td>" . $teamAliases[$games[$i]["awayTeam"]] . "</td>\n";
            echo "                <td class=\"gsTL\">" . $games[$i]["awayRushYds"] . "</td>\n";
            echo "              </tr>\n";
            echo "              <tr>\n";
            echo "                <td class=\"gsBR\">" . $teamAliases[$games[$i]["homeTeam"]] . "</td>\n";
            echo "                <td class=\"gsBL\">" . $games[$i]["homeRushYds"] . "</td>\n";
            echo "              </tr>\n";
            echo "              <tr>\n";
            echo "                <td colspan=\"2\" class=\"noBorder\" style=\"height:35px;\">Final Rush&nbsp;Yds</td>\n";
            echo "              </tr>\n";
            echo "            </table>\n";
            echo "          </td>\n";
            echo "          <td class=\"headerBackgroundTable\" style=\"width:4.5%; font-size:10px;\">\n";
            echo "            <table class=\"gameScoreTable\">\n";
            echo "              <tr>\n";
            echo "                <td>" . $teamAliases[$games[$i]["awayTeam"]] . "</td>\n";
            echo "                <td class=\"gsTL\">" . $games[$i]["awayPassYds2Q"] . "</td>\n";
            echo "              </tr>\n";
            echo "              <tr>\n";
            echo "                <td class=\"gsBR\">" . $teamAliases[$games[$i]["homeTeam"]] . "</td>\n";
            echo "                <td class=\"gsBL\">" . $games[$i]["homePassYds2Q"] . "</td>\n";
            echo "              </tr>\n";
            echo "              <tr>\n";
            echo "                <td colspan=\"2\" class=\"noBorder\" style=\"height:35px;\">Halftime Pass&nbsp;Yds</td>\n";
            echo "              </tr>\n";
            echo "            </table>\n";
            echo "          </td>\n";
            echo "          <td class=\"headerBackgroundTable\" style=\"width:4.5%; font-size:10px;\">\n";
            echo "            <table class=\"gameScoreTable\">\n";
            echo "              <tr>\n";
            echo "                <td>" . $teamAliases[$games[$i]["awayTeam"]] . "</td>\n";
            echo "                <td class=\"gsTL\">" . $games[$i]["awayPassYds"] . "</td>\n";
            echo "              </tr>\n";
            echo "              <tr>\n";
            echo "                <td class=\"gsBR\">" . $teamAliases[$games[$i]["homeTeam"]] . "</td>\n";
            echo "                <td class=\"gsBL\">" . $games[$i]["homePassYds"] . "</td>\n";
            echo "              </tr>\n";
            echo "              <tr>\n";
            echo "                <td colspan=\"2\" class=\"noBorder\" style=\"height:35px;\">Final Pass&nbsp;Yds</td>\n";
            echo "              </tr>\n";
            echo "            </table>\n";
            echo "          </td>\n";
            echo "          <td class=\"headerBackgroundTable\" style=\"width:4.5%; font-size:10px;\">\n";
            echo "            <table class=\"gameScoreTable\">\n";
            echo "              <tr>\n";
            echo "                <td>" . $teamAliases[$games[$i]["awayTeam"]] . "</td>\n";
            echo "                <td class=\"gsTL\">" . $games[$i]["awayScore1Q"] . "</td>\n";
            echo "              </tr>\n";
            echo "              <tr>\n";
            echo "                <td class=\"gsBR\">" . $teamAliases[$games[$i]["homeTeam"]] . "</td>\n";
            echo "                <td class=\"gsBL\">" . $games[$i]["homeScore1Q"] . "</td>\n";
            echo "              </tr>\n";
            echo "              <tr>\n";
            echo "                <td colspan=\"2\" class=\"noBorder\" style=\"height:35px;\">1Q Score</td>\n";
            echo "              </tr>\n";
            echo "            </table>\n";
            echo "          </td>\n";
          }
          echo "          <td class=\"headerBackgroundTable\" style=\"width:4.5%; font-size:10px;" . 
              (($_SESSION["showPicksWeek"] == (($_SESSION["showPicksSeason"] <= 2020) ? 20 : 21)) ? " border-right:none;" : "") . "\">\n";
          echo "            <table class=\"gameScoreTable\">\n";
          echo "              <tr>\n";
          echo "                <td>" . $teamAliases[$games[$i]["awayTeam"]] . "</td>\n";
          echo "                <td class=\"gsTL\">" . $games[$i]["awayScore2Q"] . "</td>\n";
          echo "              </tr>\n";
          echo "              <tr>\n";
          echo "                <td class=\"gsBR\">" . $teamAliases[$games[$i]["homeTeam"]] . "</td>\n";
          echo "                <td class=\"gsBL\">" . $games[$i]["homeScore2Q"] . "</td>\n";
          echo "              </tr>\n";
          echo "              <tr>\n";
          echo "                <td colspan=\"2\" class=\"noBorder\" style=\"height:35px;\">Halftime Score</td>\n";
          echo "              </tr>\n";
          echo "            </table>\n";
          echo "          </td>\n";
          if( $_SESSION["showPicksWeek"] == (($_SESSION["showPicksSeason"] <= 2020) ? 20 : 21) )
          {
            echo "          <td class=\"headerBackgroundTable\" style=\"width:3%; border-left:none;\">Halftime Score<br>" . 
                ($games[$i]["homeScore2Q"] + $games[$i]["awayScore2Q"]) . "</td>\n";
          }
          else
          {           
            echo "          <td class=\"headerBackgroundTable\" style=\"width:4.5%; font-size:10px;\">\n";
            echo "            <table class=\"gameScoreTable\">\n";
            echo "              <tr>\n";
            echo "                <td>" . $teamAliases[$games[$i]["awayTeam"]] . "</td>\n";
            echo "                <td class=\"gsTL\">" . $games[$i]["awayScore3Q"] . "</td>\n";
            echo "              </tr>\n";
            echo "              <tr>\n";
            echo "                <td class=\"gsBR\">" . $teamAliases[$games[$i]["homeTeam"]] . "</td>\n";
            echo "                <td class=\"gsBL\">" . $games[$i]["homeScore3Q"] . "</td>\n";
            echo "              </tr>\n";
            echo "              <tr>\n";
            echo "                <td colspan=\"2\" class=\"noBorder\" style=\"height:35px;\">3Q Score</td>\n";
            echo "              </tr>\n";
            echo "            </table>\n";
            echo "          </td>\n";
          }
        }
        echo "          <td class=\"headerBackgroundTable\" style=\"width:4.5%; font-size:10px; border-right:none;\">\n";
        echo "            <table class=\"gameScoreTable\">\n";
        echo "              <tr>\n";
        echo "                <td>" . $teamAliases[$games[$i]["awayTeam"]] . "</td>\n";
        echo "                <td class=\"gsTL\">" . $games[$i]["awayScore"] . "</td>\n";
        echo "              </tr>\n";
        echo "              <tr>\n";
        echo "                <td class=\"gsBR\">" . $teamAliases[$games[$i]["homeTeam"]] . "</td>\n";
        echo "                <td class=\"gsBL\">" . $games[$i]["homeScore"] . "</td>\n";
        echo "              </tr>\n";
        echo "              <tr>\n";
        echo "                <td colspan=\"2\" class=\"noBorder\" style=\"height:35px;\">";
        if( $games[$i]["status"] == "3" )
        {
          echo "Final";
        }
        else if( $games[$i]["status"] == "2" )
        {
          echo (substr($games[$i]["timeLeft"], 0, 1) == "Q") 
               ? (substr($games[$i]["timeLeft"], 0, 2) . "<br>" . substr($games[$i]["timeLeft"], 3)) 
               : $games[$i]["timeLeft"];
        }
        else if( $games[$i]["status"] == "1" )
        {
          echo strftime("%b %e<br>%l:%M%p", strtotime($games[$i]["gameTime"]));
        }
        echo "</td>\n";
        echo "              </tr>\n";
        echo "            </table>\n";
        echo "          </td>\n";
        echo "          <td class=\"headerBackgroundTable\" style=\"width:3%; border-left:none;\">Score<br>" . 
            ($games[$i]["homeScore"] + $games[$i]["awayScore"]) . "</td>\n";
      }
?>
          <td style="display:none">TB1</td>
          <td style="display:none">TB2</td>
          <td style="display:none">TB3</td>
          <td class="headerBackgroundTable" style="width:3%; cursor:pointer;" onClick="SortTable('points');">Total Points</td>
          <td class="headerBackgroundTable" style="width:3%; cursor:pointer;" onClick="SortTable('maxPts');">Max</td>
<?php
      if( $_SESSION["showPicksWeek"] < (($_SESSION["showPicksSeason"] <= 2020) ? 22 : 23) )
      {
        echo "          <td class=\"headerBackgroundTable\" style=\"width:3%;\">Advance</td>\n";
      }
    }

    // print their rank and name
    if( $thisPick["userID"] != $userID )
    {
      $hasBye = ($_SESSION["showPicksWeek"] == (($_SESSION["showPicksSeason"] <= 2020) ? 18 : 19) && $thisPick["firstRoundBye"] == "Y");
      $eliminated = ($_SESSION["showPicksWeek"] >= (($_SESSION["showPicksSeason"] <= 2020) ? 19 : 20) && $thisPick["prevWeek1"] < 0) || 
                    ($_SESSION["showPicksWeek"] >= (($_SESSION["showPicksSeason"] <= 2020) ? 20 : 21) && $thisPick["prevWeek2"] < 0) || 
                    ($_SESSION["showPicksWeek"] == (($_SESSION["showPicksSeason"] <= 2020) ? 22 : 23) && $thisPick["prevWeek3"] < 0);
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
      echo "        </tr>\n        <tr class=\"" . (($myID == $thisPick["userID"]) ? "my" : "table") . "Row\" style=\"color:" .
          (($thisPick["advances"] == "Y") ? $palette[0] : (($thisPick["advances"] == "N") ? $palette[2] : $palette[1])) .
          "\">\n          <td class=\"lightBackgroundTable\">" . (($hasBye || $eliminated) ? "--" : $currRank) . "</td>\n          " .
          "<td class=\"lightBackgroundTable" . (($myID == $thisPick["userID"]) ? " myName\" id=\"myPicks" : "") .
          "\">" . $thisPick["pName"] . "</td>\n";
      echo "          <td class=\"lightBackgroundTable\">" . $thisPick["sPts"] . "</td>\n";

      // show wild card score
      if( $_SESSION["showPicksWeek"] > (($_SESSION["showPicksSeason"] <= 2020) ? 18 : 19) )
      {
        echo "          <td class=\"lightBackgroundTable\" style=\"height:100%;\">" . (($thisPick["prevWeek1"] == 0)
            ? "Bye" : (($thisPick["prevWeek1"] < 0) ? (($thisPick["prevWeek1"] + 1) * -1) : $thisPick["prevWeek1"])) . "</td>\n";
      }
      // show divisional score
      if( $_SESSION["showPicksWeek"] > (($_SESSION["showPicksSeason"] <= 2020) ? 19 : 20) )
      {
        echo "          <td class=\"lightBackgroundTable\" style=\"height:100%;\">" . (($thisPick["prevWeek1"] < 0)
            ? "--&nbsp;" : (($thisPick["prevWeek2"] < 0) ? (($thisPick["prevWeek2"] + 1) * -1) : $thisPick["prevWeek2"])) . "</td>\n";
      }
      // show conference score
      if( $_SESSION["showPicksWeek"] > (($_SESSION["showPicksSeason"] <= 2020) ? 20 : 21) )
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
      echo "<div class=\"cellShadeBG\" style=\"background-color:" . $palette2[2] . "\"></div>\n";
      echo "<table class=\"cellShadeTable\"><tr><td class=\"noBorder\"><span class=\"blankIt\">MIS 19</span><br>";
      echo "<div class=\"imgDiv blankIt\"><img class=\"teamLogo\" src=\"" . getIcon("BUF", $_SESSION["showPicksSeason"]) . "\"/></div>";
      echo "<div class=\"centerIt\" style=\"color:" . $palette[2] . "\">Missed<br>(" . $thisPick["pPts"] . ")</div></td></tr></table>";
      echo "</div>\n";
    }
    else if( !$poolLocked && $thisPick["userID"] != $myID && $standingsType == "actual" )
    {
      echo "X";
    }
    else
    {
      echo "<div class=\"cellShadeOuter\">\n";
      echo "<div class=\"cellShadeBG\"" . ((($thisPick["gStatus"] == 1) || ($thisPick["gStatus"] == 19)) ? "" :
          (" style=\"background-color:" . (($thisPick["winner"] == $thisPick["leader"]) ? $palette2[0] :
          (($thisPick["leader"] == "TIE" && $thisPick["gStatus"] != 3) ? $palette2[1] : $palette2[2])) . ";\"")) . "></div>\n";
      $span = "<span style=\"color:" .
          ((($thisPick["gStatus"] == 1) || ($thisPick["gStatus"] == 19)) ? "#0A1F42" :
          (($thisPick["winner"] == $thisPick["leader"]) ? $palette[0] :
          (($thisPick["leader"] == "TIE" && $thisPick["gStatus"] != 3) ? $palette[1] : $palette[2]))) . ";" .
          (($thisPick["gStatus"] == 2) ? " font-style:italic;" : "") . "\">" .
          $teamAliases[$thisPick["winner"]] . (($thisPick["pPts"] > 0) ? (" " . $thisPick["pPts"]) : "") . "</span>";
      echo "<table class=\"cellShadeTable\"><tr><td class=\"noBorder\">" . ($logosHidden ?
            ("<div class=\"centerIt\">" . $span . "</div><div class=\"blankIt\">") : "") . $span . "<br>";
      echo "<div class=\"imgDiv\"><img class=\"teamLogo\" src=\"" . (($thisPick["winner"] != "TIE") ?
          getIcon($thisPick["winner"], $_SESSION["showPicksSeason"]) : getIcon("", $_SESSION["showPicksSeason"])) . "\"/></div>";
      echo ($logosHidden ? "</div>" : "") . "</td></tr></table>";
      echo "</div>\n";
    }
    echo "</td>\n";

    // dump the extra slots
    if( $_SESSION["showPicksWeek"] >= (($_SESSION["showPicksSeason"] <= 2020) ? 20 : 21) && $eliminated )
    {
      $extras = ($_SESSION["showPicksWeek"] == (($_SESSION["showPicksSeason"] <= 2020) ? 20 : 21)) ? 2 : 9;
      for( $z=0; $z<$extras; $z++ )
      {
        echo "          <td class=\"lightBackgroundTable\" style=\"height:100%;\">--</td>\n";
      }
    }

    // factor it into the max
    $possibleMax += (($thisPick["isFinal"] && ($thisPick["winner"] != $thisPick["leader"])) ||      // final and theyre wrong
                     ($poolLocked && ($thisPick["winner"] == "")))                                  // they missed it
                    ? 0 : $thisPick["pPts"];

    if( $_SESSION["showPicksWeek"] < (($_SESSION["showPicksSeason"] <= 2020) ? 22 : 23) )
    {
      // show their score pick
      $radix = ((($_SESSION["showPicksWeek"] == (($_SESSION["showPicksSeason"] <= 2020) ? 18 : 19)) && ($_SESSION["showPicksSeason"] > 2019)) ? 6 : 4);
      $toggle = (($jk - $baseJK) % $radix);
      $toggle = (($toggle == 1) && ($_SESSION["showPicksWeek"] == (($_SESSION["showPicksSeason"] <= 2020) ? 20 : 21))) ? 2 :
                ((($toggle == 2) && ($_SESSION["showPicksWeek"] == (($_SESSION["showPicksSeason"] <= 2020) ? 20 : 21))) ? 1 : $toggle);
      $tbName = "tieBreaker" . ($radix - ($toggle % $radix));
      echo "          <td class=\"lightBackgroundTable\">" . (($hasBye || $eliminated) ? "--" : (($thisPick[$tbName] == "0")
          ? "--" : ((!$poolLocked && $thisPick["userID"] != $myID)
                   ? "X" : $thisPick[$tbName]))) . "</td>\n";
    }

    // see if that ends this person's picks
    if( ($nextPick == null) || ($nextPick["userID"] != $thisPick["userID"]) )
    {
      if( $_SESSION["showPicksWeek"] == (($_SESSION["showPicksSeason"] <= 2020) ? 22 : 23) )
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
      if( $_SESSION["showPicksWeek"] < (($_SESSION["showPicksSeason"] <= 2020) ? 22 : 23) )
      {
        echo "          <td class=\"lightBackgroundTable\" style=\"color:" . (($thisPick["advances"] == "Y") ? $palette[0] :
            (($thisPick["advances"] == "N") ? $palette[2] : $palette[1])) . "\">" . (($thisPick["advances"] == "Y") ? "Yes" :
            (($thisPick["advances"] == "N") ? "No" : "Maybe")) . "</td>\n";
      }
    }
  }
  echo "        </tr>\n";
?>
        <script type="text/javascript">
          var mostRecentSort = "points";
          function SortTable(arg)
          {
            var i1 = 0;
            var rows = document.getElementById("reloadableTable").rows;
            mostRecentSort = arg;
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
                var compareIndex = rows[i1].cells.length - <?php echo (($_SESSION["showPicksWeek"] == (($_SESSION["showPicksSeason"] <= 2020) ? 22 : 23)) ? "2" : "3"); ?>;
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

                // selection sort
                for( var j=i1; j<i2-1; j+=1 )
                {
                  var maxIndex = j;
                  var maxTest = rows[maxIndex].cells[compareIndex].innerHTML;
                  if( arg != "name" && ((maxTest == "Bye") || (maxTest == "--")) )
                  {
                    maxTest = 50;
                  }
                  else if( arg != "name" && ((maxTest == "Out") || (maxTest == "--&nbsp;")) )
                  {
                    maxTest = -50;
                  }
                  else if( arg != "name" )
                  {
                    maxTest = parseInt(maxTest);
                  }
                  else
                  {
                    maxTest = maxTest.slice(maxTest.lastIndexOf(" ")) + " " + maxTest.slice(0, maxTest.lastIndexOf(" "));
                  }
                  for( var k=j+1; k<i2; k+=1 )
                  {
                    var thisTest = rows[k].cells[compareIndex].innerHTML;
                    if( arg != "name" && ((thisTest == "Bye") || (thisTest == "--")) )
                    {
                      thisTest = 50;
                    }
                    else if( arg != "name" )
                    {
                      thisTest = parseInt(thisTest);
                    }
                    else
                    {
                      thisTest = thisTest.slice(thisTest.lastIndexOf(" ")) + " " + thisTest.slice(0, thisTest.lastIndexOf(" "));
                    }
                    if( (arg != "name" && thisTest > maxTest) || (arg == "name" && thisTest < maxTest) )
                    {
                      maxIndex = k;
                      maxTest = thisTest;
                    }
                  }

                  // if they need to swap, do it
                  if( maxIndex != j )
                  {
                    var swap = rows[j].innerHTML;
                    rows[j].innerHTML = rows[maxIndex].innerHTML;
                    rows[maxIndex].innerHTML = swap;
                    swap = rows[j].style.color;
                    rows[j].style.color = rows[maxIndex].style.color;
                    rows[maxIndex].style.color = swap;
                    // fix the row so it highlights me
                    rows[j].className = (rows[j].contains(document.getElementById("myPicks")) ? "myRow" : "tableRow");
                    rows[maxIndex].className = (rows[maxIndex].contains(document.getElementById("myPicks")) ? "myRow" : "tableRow");
                  }
                }

                // increment the counter
                i1 = i2;
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
                      // fix the row so it highlights me
                      rows[k].className = (rows[k].contains(document.getElementById("myPicks")) ? "myRow" : "tableRow");
                      rows[maxIndex].className = (rows[maxIndex].contains(document.getElementById("myPicks")) ? "myRow" : "tableRow");
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
                SortTable(mostRecentSort);
<?php
  if( $_SESSION["showPicksWeek"] == (($_SESSION["showPicksSeason"] <= 2020) ? 22 : 23) )
  {
    echo "                SuperBowlTiebreakers();\n";
  }
  if( $gamesLive > 0 || $firstRefresh != "" )
  {
    $delayTime = ($gamesLive > 0) ? 60000 : ((strtotime($firstRefresh) - time()) * 1000);
    if( $delayTime > 86400000 )
    {
      $delayTime = 86400000;
    }
?>
                setTimeout(function() { ReloadPage("<?php echo $standingsType; ?>") }, <?php echo $delayTime; ?>);
<?php
  }
?>
              }
            }

            xmlhttp.open("GET", "display/ShowPlayoffPicksTable.php?type=" + args, true);
            xmlhttp.send();
          }

          SortTable(mostRecentSort);
<?php
  if( $_SESSION["showPicksWeek"] == (($_SESSION["showPicksSeason"] <= 2020) ? 22 : 23) )
  {
    echo "          SuperBowlTiebreakers();\n";
  }
  if( $gamesLive > 0 || $firstRefresh != "" )
  {
?>
          setTimeout(function() { ReloadPage("<?php echo $standingsType; ?>") }, <?php echo $delayTime; ?>);
<?php
  }
?>
        </script>
