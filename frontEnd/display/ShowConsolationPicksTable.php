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
                       "from Game where weekNumber>17 and season=" . $_SESSION["showPicksSeason"] . " order by gameTime, gameID", false );
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
    else if( $thisGame["status"] == "1" || $thisGame["status"] == 19 )
    {
      if( $firstRefresh == "" )
      {
        $firstRefresh = $thisGame["gameTime"];
      }
    }
  }
  $MNFscore = $games[count($games) - 1]["homeScore"] + $games[count($games) - 1]["awayScore"];

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
          <td class="headerBackgroundTable" style="width:3%; border-left:none;">Score<br><span id="caption1"><?php echo $MNFscore; ?></span></td>
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
    $swapColIndex = ($_SESSION["showPicksSeason"] < 2020) ? 4 : 6;
    for( $ind=0; $ind<count($pointVals); $ind++ )
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

//      if( (($games[$ind]["status"] == 2) || ($games[$ind]["status"] == 3)) &&
//          (($thePick == $games[$ind]["homeTeam"]) || ($thePick == $games[$ind]["awayTeam"])) &&
//          ($thePick != $games[$ind]["leader"]) && !isset($eliminatedTeams[$thePick]) )
// ^-- commented out that to correct crossover (see NO-MIN in 2017 consolation) and enabled this ----v
      if( (($gameToDisplay["status"] == 2) || ($gameToDisplay["status"] == 3)) &&
          ($thePick != $gameToDisplay["leader"]) && !isset($eliminatedTeams[$thePick]) )
      {
        $eliminatedTeams[$thePick] = $games[$ind]["status"];
      }

      // factor it into the max
      $started = (($games[$ind]["status"] != 1) && ($games[$ind]["status"] != 19));
      $possibleMax += ((isset($eliminatedTeams[$thePick]) && ($eliminatedTeams[$thePick]== 3)) || // team eliminated
                       ($started && ($thePick == "")))                                            // they missed it
                      ? 0 : $pointVals[$ind];
    }

    // see if that ends this person's picks
    echo "          <td class=\"lightBackgroundTable\">" . (($thisPick["tieBreaker"] == 0) ? "--" : 
        (($games[0]["isLocked"] == 0 && $thisPick["userID"] != $myID) ? "X" : $thisPick["tieBreaker"])) . "</td>\n";
    echo "          <td class=\"lightBackgroundTable\">" . $thisPick["cPts"] . "</td>\n";
    echo "          <td class=\"lightBackgroundTable\">" . $possibleMax . "</td>\n";
    echo "          <td class=\"lightBackgroundTable\">" . $thisPick["picksCorrect"] . "</td>\n";
    echo "          <td class=\"lightBackgroundTable\">" . $thisPick["tb4"] . "</td>\n";
    echo "          <td class=\"lightBackgroundTable\">" . $thisPick["tb5"] . "</td>\n";
  }
  echo "        </tr>\n";

  function ShowPick($teamID, $gameData, $points, $isMe, $poolLocked, $eliminatedTeams)
  {
    global $logosHidden, $teamAliases;
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
          ((!isset($eliminatedTeams[$teamID]) && (($gameData["status"] == 1) || ($gameData["status"] == 19))) ? "" : 
          (" style=\"background-color:#" . (($teamID == $gameData["leader"]) ? "00AA00" : 
          ((($gameData["status"] <= 3 && $gameData["leader"] != '') || ($eliminatedTeams[$teamID] == 3)) ? "FF0000" : "FFFF00")))) . ";\"></div>\n";
      $span = "<span" . 
          ((!isset($eliminatedTeams[$teamID]) && (($gameData["status"] == 1) || ($gameData["status"] == 19))) ? "" : 
          (" style=\"color:#" . (($teamID == $gameData["leader"]) ? "007500" : 
          ((($gameData["status"] <= 3 && $gameData["leader"] != "") || ($eliminatedTeams[$teamID] == 3)) ? "BF0000" : "888800")) . ";" . 
          (($gameData["status"] == 2) ? " font-style:italic;" : "") . "\"")) . ">" . $teamAliases[$teamID] . " " . $points . "</span>";
      echo "<table class=\"cellShadeTable\"><tr><td class=\"noBorder\">" . ($logosHidden ? 
            ("<div class=\"centerIt\">" . $span . "</div><div class=\"blankIt\">") : "") . $span . "<br>";
      echo "<div class=\"imgDiv\"><img class=\"teamLogo\" src=\"" . getIcon($teamID, $_SESSION["showPicksSeason"]) . "\"/></div></td></tr></table>";
      echo "</div>\n";
    }
    echo "</td>\n";
  }
?>
        <script type="text/javascript">
          var mostRecentSort;

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
            for( var j=0; j<myRows.length; j+=1 )
            {
              myHTMLs.push( rows[myRows[j][0]].innerHTML );
            }
            for( var j=0; j<myHTMLs.length; j+=1 )
            {
              rows[j + start].innerHTML = myHTMLs[j];
              // fix the row so it highlights me
              rows[j + start].className = (rows[j + start].contains(document.getElementById("myPicks")) ? "myRow" : "tableRow");
            }
          }

<?php
  if( $gamesLive > 0 || $firstRefresh != "" )
  {
    $delayTime = ($gamesLive > 0) ? 60000 : ((strtotime($firstRefresh) - time()) * 1000);
    if( $delayTime > 86400000 )
    {
      $delayTime = 86400000;
    }
?>
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
                setTimeout(function() { ReloadPage("<?php echo $standingsType; ?>") }, <?php echo $delayTime; ?>);
              }
            }

            xmlhttp.open("GET", "display/ShowConsolationPicksTable.php?type=" + args, true);
            xmlhttp.send();
          }

          setTimeout(function() { ReloadPage("<?php echo $standingsType; ?>") }, <?php echo $delayTime; ?>);
<?php
  }
?>
        </script>
