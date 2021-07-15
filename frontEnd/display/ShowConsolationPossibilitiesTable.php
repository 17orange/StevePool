<?php
  // start the session if we dont have one
  if(session_id() == '') {
  //if (session_status() == PHP_SESSION_NONE) {
    session_start();

    include "../util.php";
  }

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
  $minGameID = -1;
  $results = RunQuery( "select *, if(homeScore > awayScore, homeTeam, if(awayScore > homeScore, awayTeam, '')) as leader, " .
                       "if(lockTime>now(), 0, 1) as isLocked " . 
                       "from Game where weekNumber>" . (($_SESSION["showPicksSeason"] <= 2020) ? "17" : "18") .
                       " and season=" . $_SESSION["showPicksSeason"] . " order by gameTime, gameID", false );
  foreach( $results as $thisGame )
  {
    if( $minGameID == -1 || $thisGame["gameID"] < $minGameID )
    {
      $minGameID = $thisGame["gameID"];
    }

    $games[count($games)] = $thisGame;
    if( $thisGame["status"] == "2" )
    {
      $gamesLive++;
    }
    else if( $thisGame["status"] == "1" || $thisGame["status"] == "19" )
    {
      if( $firstRefresh == "" )
      {
        $firstRefresh = $thisGame["gameTime"];
      }
    }
  }
  $MNFscore = $games[count($games) - 1]["homeScore"] + $games[count($games) - 1]["awayScore"];

  // load up this shortcutter
  $gameIDtoIndex = array();
  for( $i=0; $i<count($games); $i+=1 )
  {
    $gameIDtoIndex[$games[$i]["gameID"]] = $i;
  }

  // see if they forced a winner
  if( isset($_GET["forcedWinnerGameID"]) && isset($_GET["forcedWinner"]) )
  {
    $_SESSION["forcedWinners"][$_GET["forcedWinnerGameID"]] = $_GET["forcedWinner"];

    // see if this wipes any of the winners from higher up the chain
    $index = $_GET["forcedWinnerGameID"] - $minGameID;
    // pre-2020 logic
    if( $_SESSION["showPicksSeason"] < 2020 ) {
      if( $index == 0 || $index == 1 )
      {
        $_SESSION["forcedWinners"][$minGameID + 4] = "TBD";
        $_SESSION["forcedWinners"][$minGameID + 5] = "TBD";
      }
      else if( $index == 2 || $index == 3 )
      {
        $_SESSION["forcedWinners"][$minGameID + 6] = "TBD";
        $_SESSION["forcedWinners"][$minGameID + 7] = "TBD";
      }
      if( $index == 0 || $index == 1 || $index == 4 || $index == 5 )
      {
        $_SESSION["forcedWinners"][$minGameID + 8] = "TBD";
      }
      else if( $index == 2 || $index == 3 || $index == 6 || $index == 7 )
      {
        $_SESSION["forcedWinners"][$minGameID + 9] = "TBD";
      }
      if( $index < 10 )
      {
        $_SESSION["forcedWinners"][$minGameID + 10] = "TBD";
      }
    // post-2020 logic
    } else {
      if( $index == 0 || $index == 1 || $index == 2 )
      {
        $_SESSION["forcedWinners"][$minGameID + 6] = "TBD";
        $_SESSION["forcedWinners"][$minGameID + 7] = "TBD";
      }
      else if( $index == 3 || $index == 4 || $index == 5 )
      {
        $_SESSION["forcedWinners"][$minGameID + 8] = "TBD";
        $_SESSION["forcedWinners"][$minGameID + 9] = "TBD";
      }
      if( $index == 0 || $index == 1 || $index == 2 || $index == 6 || $index == 7 )
      {
        $_SESSION["forcedWinners"][$minGameID + 10] = "TBD";
      }
      else if($index == 3 || $index == 4 || $index == 5 || $index == 8 || $index == 9 )
      {
        $_SESSION["forcedWinners"][$minGameID + 11] = "TBD";
      }
      if( $index < 12 )
      {
        $_SESSION["forcedWinners"][$minGameID + 12] = "TBD";
      }
    }
  }
  else if( !isset($_SESSION["forcedWinners"]) || $standingsType == "actual" )
  {
    $_SESSION["forcedWinners"] = array();
  }

  // grab their picks if they're trying to show best or worst
  if( $standingsType == "best" || $standingsType == "worst" )
  {
    $myPicks = RunQuery( "select wc1AFC, wc2AFC, wc3AFC, wc1NFC, wc2NFC, wc3NFC, div1AFC, div2AFC, div1NFC, div2NFC, confAFC, confNFC, " . 
                         "superBowl from ConsolationResult where userID=" . $myID . 
                         " and season=" . $_SESSION["showPicksSeason"] );
    $myPicks = $myPicks[0];
    $columns = ($_SESSION["showPicksSeason"] < 2020) 
               ? array("wc1AFC", "wc2AFC", "wc1NFC", "wc2NFC", "div1AFC", "div2AFC", "div1NFC", "div2NFC", "confAFC", "confNFC", "superBowl")
               : array("wc1AFC", "wc2AFC", "wc3AFC", "wc1NFC", "wc2NFC", "wc3NFC", "div1AFC", "div2AFC", "div1NFC", "div2NFC", "confAFC", "confNFC", "superBowl");
    $eliminatedTeams = array();
    for( $i=0; $i<count($games); $i++ )
    {
      $thisGame = $games[$gameIDtoIndex[$minGameID + $i]];
      if( $thisGame["status"] != "3" )
      {
        if( !isset($eliminatedTeams[$myPicks[$columns[$i]]]) ) {
          $_SESSION["forcedWinners"][$minGameID + $i] = (($standingsType == "best") ? "" : "NOT_") . $myPicks[$columns[$i]];
        }
      } else {
        $eliminatedTeams[(($thisGame["homeScore"] > $thisGame["awayScore"]) ? $thisGame["awayTeam"] : $thisGame["homeTeam"])] = true;
      }
    }
  }

  // see if there are any forced winners to account for
  $actualUpsets = ($_SESSION["showPicksSeason"] < 2020) 
                  ? array(false, false, false, false, false, false, false, false, false, false, false)
                  : array(false, false, false, false, false, false, false, false, false, false, false, false, false);
  $forcedUpsets = ($_SESSION["showPicksSeason"] < 2020) 
                  ? array(false, false, false, false, false, false, false, false, false, false, false)
                  : array(false, false, false, false, false, false, false, false, false, false, false, false, false);
  for( $i=0; $i<count($games); $i+=1 )
  {
    // this particular game has a forced winner
    $testID = $minGameID + $i;
    $thisIndex = $gameIDtoIndex[$testID];
    $actualUpsets[$i] = ($games[$thisIndex]["leader"] == $games[$thisIndex]["awayTeam"]);
    if( isset($_SESSION["forcedWinners"][$testID]) )
    {
      // force the winner
      if( substr($_SESSION["forcedWinners"][$testID], 0, 4) == "NOT_" )
      {
        $games[$thisIndex]["leader"] = (substr($_SESSION["forcedWinners"][$testID], 4) == $games[$thisIndex]["homeTeam"]) 
                                       ? $games[$thisIndex]["awayTeam"]
                                       : $games[$thisIndex]["homeTeam"];
      }
      else
      {
        $games[$thisIndex]["leader"] = $_SESSION["forcedWinners"][$testID];
      }
      $forcedUpsets[$i] = ($games[$thisIndex]["leader"] == $games[$thisIndex]["awayTeam"]);
    }

    // pre-2020 logic
    if( $_SESSION["showPicksSeason"] < 2020 ) {
      // this is the AFC 3-6 game
      if( $i == 0 )
      {
        $swap = $forcedUpsets[$i] || (!isset($_SESSION["forcedWinners"][$testID]) && $actualUpsets[$i]);
        if( ($swap && ($games[$gameIDtoIndex[$minGameID + 4]]["awayTeam"] != $games[$thisIndex]["leader"])) ||
            (!$swap && ($games[$gameIDtoIndex[$minGameID + 5]]["awayTeam"] != $games[$thisIndex]["leader"])) )
        {
          $games[$gameIDtoIndex[$minGameID + ($swap ? 5 : 4)]]["awayTeam"] = $games[$gameIDtoIndex[$minGameID + ($swap ? 4 : 5)]]["awayTeam"];
          $games[$gameIDtoIndex[$minGameID + ($swap ? 4 : 5)]]["awayTeam"] = $games[$thisIndex]["leader"];
        }
      }
      // this is the AFC 4-5 game
      else if( $i == 1 )
      {
        $games[$gameIDtoIndex[$minGameID + ($swap ? 5 : 4)]]["awayTeam"] = $games[$thisIndex]["leader"];
      }
      // this is the NFC 3-6 game
      else if( $i == 2 )
      {
        $swap = $forcedUpsets[$i] || (!isset($_SESSION["forcedWinners"][$testID]) && $actualUpsets[$i]);
        if( ($swap && ($games[$gameIDtoIndex[$minGameID + 6]]["awayTeam"] != $games[$thisIndex]["leader"])) ||
            (!$swap && ($games[$gameIDtoIndex[$minGameID + 7]]["awayTeam"] != $games[$thisIndex]["leader"])) )
        {
          $games[$gameIDtoIndex[$minGameID + ($swap ? 7 : 6)]]["awayTeam"] = $games[$gameIDtoIndex[$minGameID + ($swap ? 6 : 7)]]["awayTeam"];
          $games[$gameIDtoIndex[$minGameID + ($swap ? 6 : 7)]]["awayTeam"] = $games[$thisIndex]["leader"];
        }
      }
      // this is the NFC 4-5 game
      else if( $i == 3 )
      {
        $games[$gameIDtoIndex[$minGameID + ($swap ? 7 : 6)]]["awayTeam"] = $games[$thisIndex]["leader"];
      }
      // this is the AFC 1-X game
      else if( $i == 4 )
      {
        $swap = $forcedUpsets[$i] || (!isset($_SESSION["forcedWinners"][$testID]) && $actualUpsets[$i]);
        if( ($swap && ($games[$gameIDtoIndex[$minGameID + 8]]["awayTeam"] != $games[$thisIndex]["leader"])) ||
            (!$swap && ($games[$gameIDtoIndex[$minGameID + 8]]["homeTeam"] != $games[$thisIndex]["leader"])) )
        {
          $games[$gameIDtoIndex[$minGameID + 8]][$swap ? "homeTeam" : "awayTeam"] = $games[$gameIDtoIndex[$minGameID + 8]][$swap ? "awayTeam" : "homeTeam"];
          $games[$gameIDtoIndex[$minGameID + 8]][$swap ? "awayTeam" : "homeTeam"] = $games[$thisIndex]["leader"];
        }
      }
      // this is the AFC 2-Y game
      else if( $i == 5 )
      {
        $games[$gameIDtoIndex[$minGameID + 8]][$swap ? "homeTeam" : "awayTeam"] = $games[$thisIndex]["leader"];
      }
      // this is the NFC 1-X game
      else if( $i == 6 )
      {
        $swap = $forcedUpsets[$i] || (!isset($_SESSION["forcedWinners"][$testID]) && $actualUpsets[$i]);
        if( ($swap && ($games[$gameIDtoIndex[$minGameID + 9]]["awayTeam"] != $games[$thisIndex]["leader"])) ||
            (!$swap && ($games[$gameIDtoIndex[$minGameID + 9]]["homeTeam"] != $games[$thisIndex]["leader"])) )
        {
          $games[$gameIDtoIndex[$minGameID + 9]][$swap ? "homeTeam" : "awayTeam"] = $games[$gameIDtoIndex[$minGameID + 9]][$swap ? "awayTeam" : "homeTeam"];
          $games[$gameIDtoIndex[$minGameID + 9]][$swap ? "awayTeam" : "homeTeam"] = $games[$thisIndex]["leader"];
        }
      }
      // this is the NFC 2-Y game
      else if( $i == 7 )
      {
        $games[$gameIDtoIndex[$minGameID + 9]][$swap ? "homeTeam" : "awayTeam"] = $games[$thisIndex]["leader"];
      }
      // this is the AFC CC game
      else if( $i == 8 )
      {
        $games[$gameIDtoIndex[$minGameID + 10]]["awayTeam"] = $games[$thisIndex]["leader"];
      }
      // this is the NFC CC game
      else if( $i == 9 )
      {
        $games[$gameIDtoIndex[$minGameID + 10]]["homeTeam"] = $games[$thisIndex]["leader"];
      }
    // post-2020 logic
    } else {
      // this is the AFC 2-7 game
      if( $i == 0 )
      {
        $upset = $forcedUpsets[$i] || (!isset($_SESSION["forcedWinners"][$testID]) && $actualUpsets[$i]);
        $games[$gameIDtoIndex[$minGameID + ($upset ? 6 : 7)]][$upset ? "awayTeam" : "homeTeam"] = $games[$thisIndex]["leader"];        
      }
      // this is the AFC 3-6 game
      else if( $i == 1 )
      {
        $upset2 = $forcedUpsets[$i] || (!isset($_SESSION["forcedWinners"][$testID]) && $actualUpsets[$i]);
        $games[$gameIDtoIndex[$minGameID + ((!$upset && $upset2) ? 6 : 7)]][($upset && !$upset2) ? "homeTeam" : "awayTeam"] = $games[$thisIndex]["leader"];
      }
      // this is the AFC 4-5 game
      else if( $i == 2 )
      {
        $games[$gameIDtoIndex[$minGameID + (($upset || $upset2) ? 7 : 6)]][($upset && $upset2) ? "homeTeam" : "awayTeam"] = $games[$thisIndex]["leader"];
      }
      // this is the NFC 2-7 game
      else if( $i == 3 )
      {
        $upset = $forcedUpsets[$i] || (!isset($_SESSION["forcedWinners"][$testID]) && $actualUpsets[$i]);
        $games[$gameIDtoIndex[$minGameID + ($upset ? 8 : 9)]][$upset ? "awayTeam" : "homeTeam"] = $games[$thisIndex]["leader"];        
      }
      // this is the NFC 3-6 game
      else if( $i == 4 )
      {
        $upset2 = $forcedUpsets[$i] || (!isset($_SESSION["forcedWinners"][$testID]) && $actualUpsets[$i]);
        $games[$gameIDtoIndex[$minGameID + ((!$upset && $upset2) ? 8 : 9)]][($upset && !$upset2) ? "homeTeam" : "awayTeam"] = $games[$thisIndex]["leader"];
      }
      // this is the NFC 4-5 game
      else if( $i == 5 )
      {
        $games[$gameIDtoIndex[$minGameID + (($upset || $upset2) ? 9 : 8)]][($upset && $upset2) ? "homeTeam" : "awayTeam"] = $games[$thisIndex]["leader"];
      }
      // this is the AFC 1-X game
      else if( $i == 6 )
      {
        $swap = $forcedUpsets[$i] || (!isset($_SESSION["forcedWinners"][$testID]) && $actualUpsets[$i]);
        if( ($swap && ($games[$gameIDtoIndex[$minGameID + 10]]["awayTeam"] != $games[$thisIndex]["leader"])) ||
            (!$swap && ($games[$gameIDtoIndex[$minGameID + 10]]["homeTeam"] != $games[$thisIndex]["leader"])) )
        {
          $games[$gameIDtoIndex[$minGameID + 10]][$swap ? "homeTeam" : "awayTeam"] = $games[$gameIDtoIndex[$minGameID + 10]][$swap ? "awayTeam" : "homeTeam"];
          $games[$gameIDtoIndex[$minGameID + 10]][$swap ? "awayTeam" : "homeTeam"] = $games[$thisIndex]["leader"];
        }
      }
      // this is the AFC Y-Z game
      else if( $i == 7 )
      {
        $games[$gameIDtoIndex[$minGameID + 10]][$swap ? "homeTeam" : "awayTeam"] = $games[$thisIndex]["leader"];
      }
      // this is the NFC 1-X game
      else if( $i == 8 )
      {
        $swap = $forcedUpsets[$i] || (!isset($_SESSION["forcedWinners"][$testID]) && $actualUpsets[$i]);
        if( ($swap && ($games[$gameIDtoIndex[$minGameID + 11]]["awayTeam"] != $games[$thisIndex]["leader"])) ||
            (!$swap && ($games[$gameIDtoIndex[$minGameID + 11]]["homeTeam"] != $games[$thisIndex]["leader"])) )
        {
          $games[$gameIDtoIndex[$minGameID + 11]][$swap ? "homeTeam" : "awayTeam"] = $games[$gameIDtoIndex[$minGameID + 11]][$swap ? "awayTeam" : "homeTeam"];
          $games[$gameIDtoIndex[$minGameID + 11]][$swap ? "awayTeam" : "homeTeam"] = $games[$thisIndex]["leader"];
        }
      }
      // this is the NFC Y-Z game
      else if( $i == 9 )
      {
        $games[$gameIDtoIndex[$minGameID + 11]][$swap ? "homeTeam" : "awayTeam"] = $games[$thisIndex]["leader"];
      }
      // this is the AFC CC game
      else if( $i == 10 )
      {
        $games[$gameIDtoIndex[$minGameID + 12]]["awayTeam"] = $games[$thisIndex]["leader"];
      }
      // this is the NFC CC game
      else if( $i == 11 )
      {
        $games[$gameIDtoIndex[$minGameID + 12]]["homeTeam"] = $games[$thisIndex]["leader"];
      }
    }
  }

  // grab all of the rows
  $poolLocked = (($games[0]["isLocked"] == 1) && ($games[0]["status"] != 19));
  $results = RunQuery( "select userID, concat(firstName, ' ', lastName) as pName, ConsolationResult.points as cPts, " . 
                       "wc1AFC, wc2AFC, wc3AFC, wc1NFC, wc2NFC, wc3NFC, div1AFC, div2AFC, div1NFC, div2NFC, confAFC, confNFC, " . 
                       "superBowl, picksCorrect, tieBreaker, abs(tieBreaker - " . $MNFscore . ") as tb2, " . 
                       "(" . $MNFscore . " - tieBreaker) as tb3, SeasonResult.points as tb4, SeasonResult.weeklyWins as tb5, " . 
                       "if(wc1AFC is null, 2, 1) as filter " . 
                       "from ConsolationResult join SeasonResult using (userID, season) join User using (userID) " . 
                       "where season=" . $_SESSION["showPicksSeason"] . " order by filter asc, cPts desc, picksCorrect desc, " . 
                       ($poolLocked ? "tb2 asc, tb3 desc, " : "") . "tb4 desc, tb5 desc" );
  $pickBank = array();
  foreach( $results as $thisPick )
  {
    $pickBank[count($pickBank)] = $thisPick;
  }

  // adjust the picks for any forced winners there may be
  $thisUser = 0;
  $startIndex = 0;
  $points = 0;
  $ytd = 0;
  for( $i=0; $i<count($pickBank); $i++ )
  {
    // update the values
    if( $pickBank[$i]["userID"] != $thisUser )
    {
/*
      // fill in the totals for this guy
      for($j=$startIndex; $j<$i; $j++)
      {
        $pickBank[$j]["wPts"] = $points;
        $pickBank[$j]["sPts"] = $ytd;
      }
*/

      $thisUser = $pickBank[$i]["userID"];
      $startIndex = $i;
//      $points = $pickBank[$i]["wPts"];
//      $ytd = $pickBank[$i]["sPts"];
    }
  }
/*
  // fill in the totals for the last guy
  for($j=$startIndex; $j<$i; $j++)
  {
    $pickBank[$j]["wPts"] = $points;
    $pickBank[$j]["sPts"] = $ytd;
  }
*/

  // if any of the games have an unknown team, make it TBD
  for( $i=0; $i<count($games); $i++ ) {
    if( $games[$i]["homeTeam"] == "" ) {
      $games[$i]["homeTeam"] = "TBD";
    }
    if( $games[$i]["awayTeam"] == "" ) {
      $games[$i]["awayTeam"] = "TBD";
    }
  } 

  // start the new table
?>
        <tr>
          <td colspan="<?php echo ($_SESSION["showPicksSeason"] < 2020) ? 19 : 21; ?>" class="headerBackgroundTable" style="font-size:24px;">Consolation Pool Standings</td>
        </tr>
        <tr>
          <td colspan="2" class="headerBackgroundTable">&nbsp;</td>
          <td colspan="<?php echo ($_SESSION["showPicksSeason"] < 2020) ? 4 : 6; ?>" class="headerBackgroundTable">Wild Card</td>
          <td colspan="4" class="headerBackgroundTable">Divisional Round</td>
          <td colspan="2" class="headerBackgroundTable">Conference Championships</td>
          <td colspan="2" class="headerBackgroundTable">Super Bowl</td>
          <td colspan="5" class="headerBackgroundTable">&nbsp;</td>
        </tr>
        <tr>
          <td class="headerBackgroundTable" style="width:3%;">Rank</td>
          <td class="headerBackgroundTable" style="width:16%; cursor:pointer;" onClick="SortTable('name');">Player</td>
<?php
  // show the games from that week
  for( $i=0; $i<count($games); $i++ )
  {
    echo "          <td class=\"headerBackgroundTable\" style=\"width:4.5%; font-size:10px;" . 
        (($i==(count($games) - 1)) ? " border-right:none;" : "") . "\">\n";
    echo "            <table class=\"gameScoreTable\" name=\"game" . $games[$i]["gameID"] . "\">\n";
    echo "              <tr onClick=\"ForceWinner(" . $games[$i]["gameID"] . ",'" . $games[$i]["awayTeam"] . "');\">\n";
    echo "                <td class=\"posTop\" style=\"background-color:" . 
        (($games[$i]["awayTeam"] != "TBD" && ($games[$i]["leader"] == $games[$i]["awayTeam"])) ? "#409840" : "#D9DCE3") . ";\"><div class=\"posTeam\">" . 
        $teamAliases[$games[$i]["awayTeam"]] . "<div class=\"imgDiv\"><img class=\"teamLogo\" src=\"" . getIcon($games[$i]["awayTeam"], $_SESSION["showPicksSeason"]) . 
        "\"/></div></div></td>\n";
    echo "              </tr>\n";
    echo "              <tr onClick=\"ForceWinner(" . $games[$i]["gameID"] . ",'" . $games[$i]["homeTeam"] . "');\">\n";
    echo "                <td class=\"posOther\" style=\"background-color:" . 
        (($games[$i]["homeTeam"] != "TBD" && ($games[$i]["leader"] == $games[$i]["homeTeam"])) ? "#409840" : "#D9DCE3") . ";\"><div class=\"posTeam\">" . 
        $teamAliases[$games[$i]["homeTeam"]] . "<div class=\"imgDiv\"><img class=\"teamLogo\" src=\"" . getIcon($games[$i]["homeTeam"], $_SESSION["showPicksSeason"]) . 
        "\"/></div></div></td>\n";
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
    else if( $games[$i]["status"] == "1" || $games[$i]["status"] == "19" )
    {
      echo strftime("%b %e<br>%l:%M%p", strtotime($games[$i]["gameTime"]));
    }
    echo "</td>\n";
    echo "              </tr>\n";
    echo "            </table>\n";
    echo "          </td>\n";
  }
?>
          <td class="headerBackgroundTable" style="width:3%; border-left:none;">
            <input type="submit" onclick="AdjustMNF(1);" value="+"><br>
            Score<br><input id="adjust1" value="<?php echo $MNFscore; ?>" style="display:none; width:60%" 
            onFocusOut="SubmitScoreBox(1);" onKeyUp="KeyUpScoreBox(event, 1);">
            <span id="caption1" style="cursor:pointer" onClick="ShowScoreBox(1);"><?php echo $MNFscore; ?></span><br>
            <input type="submit" onclick="AdjustMNF(-1);" value="-">
          </td>
          <td class="headerBackgroundTable" style="width:3%; cursor:pointer;" onClick="SortTable('weekPts');">Total Points</td>
          <td class="headerBackgroundTable" style="width:3%; cursor:pointer;" onClick="SortTable('maxPts');">Max</td>
          <td class="headerBackgroundTable" style="width:3%; cursor:pointer;" onClick="SortTable('picks');">Correct Picks</td>
          <td class="headerBackgroundTable" style="width:3%; cursor:pointer;" onClick="SortTable('ytdPts');">Regular Season</td>
          <td class="headerBackgroundTable" style="width:3%; cursor:pointer;" onClick="SortTable('wins');">Weekly Wins</td>
<?php

  $userID = -1;
  $currRank = 0;
  $playerCount = 0;
  $currScore = 500;
  $possibleMax = 0;
  for( $jk=0; $jk<count($pickBank); $jk++ )
  {
    $thisPick = $pickBank[$jk];

    // get their rank
    $playerCount++;
    if( $thisPick["cPts"] < $currScore )
    {
      $currRank = $playerCount;
      $currScore = $thisPick["cPts"];
    }
    $possibleMax = 0;
    $displayScore = 0;
    $correctPicks = 0;

    $userID = $thisPick["userID"];
    echo "        </tr>\n        <tr class=\"" . (($myID == $thisPick["userID"]) ? "my" : "table") . 
        "Row\">\n          <td class=\"lightBackgroundTable\">" . $currRank . "</td>\n          " . 
        "<td class=\"lightBackgroundTable" . (($myID == $thisPick["userID"]) ? " myName\" id=\"myPicks" : "") . 
        "\">" . $thisPick["pName"] . "</td>\n";

    // show their picks
    $isMe = ($userID == $myID);
    $columns = ($_SESSION["showPicksSeason"] < 2020) 
               ? array("wc1AFC", "wc2AFC", "wc1NFC", "wc2NFC", "div1AFC", "div2AFC", "div1NFC", "div2NFC", "confAFC", "confNFC", "superBowl")
               : array("wc1AFC", "wc2AFC", "wc3AFC", "wc1NFC", "wc2NFC", "wc3NFC", "div1AFC", "div2AFC", "div1NFC", "div2NFC", "confAFC", "confNFC", "superBowl");
    $pointVals = ($_SESSION["showPicksSeason"] < 2020) ? array(1,1,1,1,2,2,2,2,4,4,8) : array(1,1,1,1,1,1,2,2,2,2,4,4,8);
    $eliminatedTeams = array();
    $divWinners = array();
    $swapColIndex = ($_SESSION["showPicksSeason"] < 2020) ? 4 : 6;
    for( $ind=0; $ind<count($games); $ind++ )
    {
      $columnIndex = $games[$ind]["gameID"] - $minGameID;
      $thePick = $thisPick[$columns[$columnIndex]];
      $gameToDisplay = $games[$ind];
      if( $columnIndex >= $swapColIndex && $columnIndex < ($swapColIndex + 4) )
      {
        $checkIndex = (($columnIndex < ($swapColIndex + 2)) ? $swapColIndex : ($swapColIndex + 2)) + (($columnIndex + 1) % 2);
        // find it
        for( $cInd=$swapColIndex; $cInd<($swapColIndex + 4); $cInd++ )
        {
          if( ($games[$cInd]["gameID"] - $minGameID) == $checkIndex )
          {
            if( $thePick == $games[$cInd]["homeTeam"] || $thePick == $games[$cInd]["awayTeam"] )
            {
              $gameToDisplay = $games[$cInd];
            }
          }
        }
      }
      ShowPick($thePick, $gameToDisplay, $pointVals[$ind], $isMe, $poolLocked, $eliminatedTeams);

      $forced = isset($_SESSION["forcedWinners"][$gameToDisplay["gameID"]]) &&
                ($_SESSION["forcedWinners"][$gameToDisplay["gameID"]] != "TBD");
      if( (($gameToDisplay["status"] == 2) || ($gameToDisplay["status"] == 3) || $forced) && ($thePick != $gameToDisplay["leader"]) && ("TBD" != $gameToDisplay["leader"]) )
      {
        $eliminatedTeams[$thePick] = 19;
      }

      // keep track of their picks in the divisional round
      if( $ind>=$swapColIndex && $ind<($swapColIndex + 4) ) {
        $divWinners[] = $thePick;
      }

      // factor it into the max
      if( $poolLocked || ($thisPick["userID"] == $myID)) {
        $started = (($gameToDisplay["status"] != 1) && ($gameToDisplay["status"] != 19));
        $possibleMax += ((isset($eliminatedTeams[$thePick])) ||       // team eliminated
                         ($started && ($thePick == "")))              // they missed it
                        ? 0 : $pointVals[$ind];
        $displayScore += (($thePick != "") && ($thePick == $gameToDisplay["leader"])) ? $pointVals[$ind] : 0;
        $correctPicks += (($thePick != "") && ($thePick == $gameToDisplay["leader"])) ? 1 : 0;

        // check for double scoop in the divisional round
        if( $ind==($swapColIndex + 4) ) {
          for( $ind2=$swapColIndex; $ind2<($swapColIndex+4); $ind2++ ) {
            if( in_array($games[$ind2]["homeTeam"], $divWinners) && in_array($games[$ind2]["awayTeam"], $divWinners) && ("TBD" == $games[$ind2]["leader"]) ) {
              $possibleMax -= $pointVals[$ind2];
            }
          }
        }
      } else {
        $possibleMax += $pointVals[$ind];
      }
    }

    // see if that ends this person's picks
    echo "          <td class=\"lightBackgroundTable\">" . (($thisPick["tieBreaker"] == 0) ? "--" : 
        (($games[0]["isLocked"] == 0 && $thisPick["userID"] != $myID) ? "X" : $thisPick["tieBreaker"])) . "</td>\n";
    echo "          <td class=\"lightBackgroundTable\">" . $displayScore . "</td>\n";
    echo "          <td class=\"lightBackgroundTable\">" . $possibleMax . "</td>\n";
    echo "          <td class=\"lightBackgroundTable\">" . $correctPicks . "</td>\n";
    echo "          <td class=\"lightBackgroundTable\">" . $thisPick["tb4"] . "</td>\n";
    echo "          <td class=\"lightBackgroundTable\">" . $thisPick["tb5"] . "</td>\n";
  }
  echo "        </tr>\n";

  function ShowPick($teamID, $gameData, $points, $isMe, $poolLocked, $eliminatedTeams)
  {
    global $teamAliases;
    $logosHidden = (isset($_SESSION["spHideLogos"]) && $_SESSION["spHideLogos"] == "TRUE");
    echo "          <td class=\"lightBackgroundTable\" style=\"height:100%;\">";
    if(!$poolLocked && $teamID == "" && (($gameData["status"] == 1) || ($gameData["status"] == 19)) )
    {
      echo "--";
    }
    else if($teamID == "")
    {
      echo "<div class=\"cellShadeOuter\">\n";
      echo "<div class=\"cellShadeBG\" style=\"background-color:#FF0000;\"></div>\n";
      echo "<table class=\"cellShadeTable\"><tr><td class=\"noBorder\"><span class=\"blankIt\">MIS 19</span><br>";
      echo "<div class=\"imgDiv blankIt\"><img class=\"teamLogo\" src=\"" . getIcon("BUF", $_SESSION["showPicksSeason"]) . "\"/></div>";
      echo "<div class=\"centerIt\" style=\"color:#BF0000;\">Missed<br>(" . $points . ")</div></td></tr></table>";
      echo "</div>\n";
    }
    else if( !$isMe && !$poolLocked )
    {
      echo "X";
    }
    else
    {
      echo "<div class=\"cellShadeOuter\">\n";
      echo "<div class=\"cellShadeBG\"" . 
//          ((!isset($eliminatedTeams[$teamID]) && (($gameData["status"] == 1) || ($gameData["status"] == 19))) ? "" :
//          (" style=\"background-color:#" . (($teamID == $gameData["leader"]) ? "00AA00": "FF0000"))) . ";\"></div>\n";
          ((!isset($eliminatedTeams[$teamID]) && in_array($gameData["leader"], ["", "TBD"])) ? "" :
          (" style=\"background-color:#" . (($teamID == $gameData["leader"]) ? "00AA00": "FF0000"))) . ";\"></div>\n";
//      $span = "<span" . ((!isset($eliminatedTeams[$teamID]) && (($gameData["status"] == 1) || ($gameData["status"] == 19))) ? "" :
//          (" style=\"color:#" . (($teamID == $gameData["leader"]) ? "007500": "BF0000") . ";\"")) . ">" . $teamAliases[$teamID] . " " .
      $span = "<span" . ((!isset($eliminatedTeams[$teamID]) && in_array($gameData["leader"], ["", "TBD"])) ? "" :
          (" style=\"color:#" . (($teamID == $gameData["leader"]) ? "007500": "BF0000") . ";\"")) . ">" . $teamAliases[$teamID] . " " . 
          $points . "</span>";
      echo "<table class=\"cellShadeTable\"><tr><td class=\"noBorder\">" . ($logosHidden ? 
            ("<div class=\"centerIt\">" . $span . "</div><div class=\"blankIt\">") : "") . $span . "<br>";
      echo "<div class=\"imgDiv\"><img class=\"teamLogo\" src=\"" . getIcon($teamID, $_SESSION["showPicksSeason"]) . "\"/></div>";
      echo ($logosHidden ? "</div>" : "") . "</td></tr></table>";
      echo "</div>\n";
    }
    echo "</td>\n";
  }
?>
        <script type="text/javascript">
          var mostRecentSort;
          SortTable("weekPts");

          var sorting = false;
          function SortTable(arg)
          {
            // guard against threading
            if( sorting )
            {
              return;
            }
            sorting = true;

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
                var compareIndex = <?php echo ($_SESSION["showPicksSeason"] < 2020) ? 14 : 16; ?>;
                if( arg == "maxPts" )
                {
                  compareIndex += 1;
                }
                else if( arg == "picks" )
                {
                  compareIndex += 2;
                }
                else if( arg == "ytdPts" )
                {
                  compareIndex += 3;
                }
                else if( arg == "wins" )
                {
                  compareIndex += 4;
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

            // tell them it's safe
            sorting = false;
          }

          // this does a hybrid of mergesort and insertion sort
          function SmartSort(start, end, compareIndex, isNumeric)
          {
            // grab the MNF score
            var MNF = parseInt( document.getElementById("caption1").innerHTML );

            // create the initial arrays
            var rows = document.getElementById("reloadableTable").rows;
            var myRows = [];
            for( var j=start; j<end; j+=1 )
            {
              var thisVal = rows[j].cells[compareIndex].innerHTML;
              if( isNumeric )
              {
                thisVal = parseInt(thisVal);
              }
              else
              {
                thisVal = thisVal.slice(thisVal.lastIndexOf(" ")) + " " + thisVal.slice(0, thisVal.lastIndexOf(" "));                
              }
              var thisPicks = parseInt(rows[j].cells[rows[j].cells.length - 3].innerHTML);
              var thisTB = parseInt(rows[j].cells[rows[j].cells.length - 6].innerHTML);
              var thisSeason = parseInt(rows[j].cells[rows[j].cells.length - 2].innerHTML);
              var thisWins = parseInt(rows[j].cells[rows[j].cells.length - 1].innerHTML);
              var thisRow = [j, thisVal, isNumeric, thisPicks, true<?php 
  echo ($poolLocked ? ", Math.abs(MNF - thisTB), false, thisTB, false" : ""); ?>, thisSeason, true, thisWins, true];
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
              if( mostRecentSort == "weekPts" )
              {
                thisScore = parseInt(rows[j+start].cells[rows[j+start].cells.length - 5].innerHTML);
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
              }
            }

            xmlhttp.open("GET", "display/ShowConsolationPossibilitiesTable.php?type=" + args, true);
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

            mostRecentSort = "weekPts";
            ReloadPage("force&forcedWinnerGameID=" + gameID + "&forcedWinner=" + winner );

            // tell them it's safe
            recalculating = false;
          }

          function ShowScoreBox(grouping)
          {
            $("#caption" + grouping).css("display","none");
            $("#adjust" + grouping).css("display","inline-block");
          }

          function KeyUpScoreBox(e, grouping)
          {
            if( e.keyCode == 13 )
            {
              SubmitScoreBox(grouping);
            }
          }

          function SubmitScoreBox(grouping)
          {
            $("#adjust" + grouping).css("display","none");
            $("#caption" + grouping).css("display","inline");

            var elem = document.getElementById("caption" + grouping);
            var elem2 = document.getElementById("adjust" + grouping);
            var currScore = parseInt(elem.innerHTML);
            var newScore = parseInt(elem2.value);
            AdjustMNF(newScore - currScore);
          }

          function AdjustMNF(delta)
          {
            // move the draggers to match
            var MNFIndex = 1;
            var elem = document.getElementById("caption" + MNFIndex);
            var elem2 = document.getElementById("adjust" + MNFIndex);
            while( elem != null && elem2 != null )
            {
              // dont go into negatives
              var score = parseInt(elem.innerHTML);
              if( score + delta <= 0 )
              {
                delta = 0 - score;
              }
              elem.innerHTML = score + delta;
              elem2.value = score + delta;

              MNFIndex += 1;
              elem = document.getElementById("caption" + MNFIndex);
              elem2 = document.getElementById("adjust" + MNFIndex);
            }

            SortTable("points");
          }
        </script>
