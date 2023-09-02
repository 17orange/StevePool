<?php
  // start the session if we dont have one
  if(session_id() == '') {
  //if (session_status() == PHP_SESSION_NONE) {
    session_start();

    include "../util.php";
  }

  // set the palette
  $palette = ($_SESSION["cbm"] ?? false) ? ["#0072B2","#000000","#D55E00"] : ["#007500","#888800","#BF0000"];
  $palette2 = ($_SESSION["cbm"] ?? false) ? ["#42BBFF","#888888","#FF913B", "#0072B2"] : ["#00AA00","#FFFF00","#FF0000", "#409840"];

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
    $results = RunQuery( "select gameID, winner, if(homeTeam=winner, awayTeam, homeTeam) as loser " . 
                         "from Pick join Game using (gameID) where userID=" . $myID . 
                         " and weekNumber=" . $_SESSION["showPicksWeek"] . " and season=" . $_SESSION["showPicksSeason"] . 
                         " and status != 3" );
    foreach( $results as $thisPick )
    {
      $_SESSION["forcedWinners"][$thisPick["gameID"]] = $thisPick[($standingsType == "best") ? "winner" : "loser"];
    }
  }

  // grab the games from that week
  $games = array();
  $results = RunQuery( "select *, if(lockTime>now(), 0, 1) as isLocked from Game where weekNumber=" . $_SESSION["showPicksWeek"] . 
                       " and season=" . $_SESSION["showPicksSeason"] . " order by tieBreakOrder, gameTime, gameID", false );
  foreach( $results as $thisGame )
  {
    $MNFscore = $thisGame["homeScore"] + $thisGame["awayScore"];
    if( isset($_SESSION["forcedWinners"][$thisGame["gameID"]]) )
    {
      $thisGame["awayScore"] = (($thisGame["awayTeam"] == $_SESSION["forcedWinners"][$thisGame["gameID"]]) ? 1 : 0);
      $thisGame["homeScore"] = (($thisGame["homeTeam"] == $_SESSION["forcedWinners"][$thisGame["gameID"]]) ? 1 : 0);
    }
    $games[count($games)] = $thisGame;
  }
  $section = -1;

  // grab all of the rows
  $tbUnlocked = ($games[count($games) - 1]["isLocked"] == 0);
  $results = RunQuery( "select userID, concat(firstName, ' ', lastName) as pName, winner, tieBreaker, Pick.points as pPts, " . 
                       "if(homeScore>awayScore, homeTeam, if(awayScore>homeScore, awayTeam, '')) as leader, gameID, " .
                       "WeekResult.points as wPts, SeasonResult.points as sPts, if(lockTime>now(), 1, 0) as status, " . 
                       "abs(tieBreaker - " . $MNFscore . ") as tb1, Division.name as dName, Conference.name as cName, " .
                       (($_SESSION["showPicksSplit"] == "division") ? "divID" : 
                        (($_SESSION["showPicksSplit"] == "conference") ? "confID" : "1")) . " as section, " .   
                       "if(Game.status=3, 1, 0) as isFinal " . 
                       "from WeekResult join SeasonResult using (userID, season) join User using (userID) " . 
                       "join Game using (weekNumber, season) join Pick using (userID, gameID) " . 
                       "join Division using (divID) join Conference using (confID) " . 
                       "where weekNumber=" . $_SESSION["showPicksWeek"] . " and season=" . $_SESSION["showPicksSeason"] . 
                       " order by section, wPts desc" . ($tbUnlocked ? "" : ", tb1, tieBreaker") . ", userID, tieBreakOrder, gameTime, gameID" );
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
      // fill in the totals for this guy
      for($j=$startIndex; $j<$i; $j++)
      {
        $pickBank[$j]["wPts"] = $points;
        $pickBank[$j]["sPts"] = $ytd;
      }

      $thisUser = $pickBank[$i]["userID"];
      $startIndex = $i;
      $points = $pickBank[$i]["wPts"];
      $ytd = $pickBank[$i]["sPts"];
    }

    // see if we need to adjust this at all
    if( $pickBank[$i]["status"] == 0 && isset($_SESSION["forcedWinners"][$pickBank[$i]["gameID"]]) )
    {
      // pull out their points
      $force = $_SESSION["forcedWinners"][$pickBank[$i]["gameID"]];
      if( $pickBank[$i]["winner"] != $force && $pickBank[$i]["winner"] != "" && $pickBank[$i]["winner"] == $pickBank[$i]["leader"] )
      {
        $points -= $pickBank[$i]["pPts"];
        $ytd -= $pickBank[$i]["pPts"];
      }
      else if( $pickBank[$i]["winner"] == $force && $pickBank[$i]["winner"] != $pickBank[$i]["leader"] )
      {
        $points += $pickBank[$i]["pPts"];
        $ytd += $pickBank[$i]["pPts"];
      }
    }
    if( $pickBank[$i]["status"] == 1 && isset($_SESSION["forcedWinners"][$pickBank[$i]["gameID"]]) )
    {
      // pull out their points
      $force = $_SESSION["forcedWinners"][$pickBank[$i]["gameID"]];

      if( $standingsType == "best" )
      {
        if( $pickBank[$i]["userID"] == $myID )
        {
          $points += $pickBank[$i]["pPts"];
          $ytd += $pickBank[$i]["pPts"];
        }
        else
        {
          $pickBank[$i]["winner"] = "AUTOLOSE";
        }
      }
      else if( $standingsType == "worst" )
      {
        if( $pickBank[$i]["userID"] != $myID )
        {
          $pickBank[$i]["winner"] = "AUTOWIN";
          $points += $pickBank[$i]["pPts"];
          $ytd += $pickBank[$i]["pPts"];
        }
      }
      else if( ($pickBank[$i]["userID"] == $myID) && ($pickBank[$i]["winner"] == $force) )
      {
          $points += $pickBank[$i]["pPts"];
          $ytd += $pickBank[$i]["pPts"];
      }
    }
  }
  // fill in the totals for the last guy
  for($j=$startIndex; $j<$i; $j++)
  {
    $pickBank[$j]["wPts"] = $points;
    $pickBank[$j]["sPts"] = $ytd;
  }

  // functions we need for the sort
  function ComparePicks($row1, $row2)
  {
    // section, wPts desc, tb1, tieBreaker
    return ($row2["section"] < $row1["section"]) || (($row2["section"] == $row1["section"]) && 
           (($row2["wPts"] > $row1["wPts"]) || (($row2["wPts"] == $row1["wPts"]) &&
           (($row2["tb1"] < $row1["tb1"]) || (($row2["tb1"] == $row1["tb1"]) &&
           ($row2["tieBreaker"] > $row1["tieBreaker"]))))));
  }
  function SwapUsers(&$bank, $index1, $index2, $length)
  {
    for($i=0; $i<$length; $i++)
    {
      $tempObj = $bank[$index1 + $i];
      $bank[$index1 + $i] = $bank[$index2 + $i];
      $bank[$index2 + $i] = $tempObj;
    }
  }

  // sort the rankings
  for($i=0; $i<count($pickBank); $i+=count($games))
  {
    $bestIndex = $i;
    for($j=$i + count($games); $j<count($pickBank); $j+=count($games))
    {
      if( ComparePicks($pickBank[$bestIndex], $pickBank[$j]) )
      {
        $bestIndex = $j;
      }
    }

    if( $i != $bestIndex )
    {
      SwapUsers($pickBank, $i, $bestIndex, count($games));
    }
  }

  $userID = -1;
  $currRank = 0;
  $playerCount = 0;
  $currScore = 500;
  $grouping = 0;
  for( $jk=0; $jk<count($pickBank); $jk++ )
  {
    $thisPick = $pickBank[$jk];
    $nextPick = (($jk + 1) < count($pickBank)) ? $pickBank[$jk + 1] : null;
    $gameIndex = $jk % count($games);

    // see if we need to start a new table
    if( $thisPick["section"] != $section )
    {
      $section = $thisPick["section"];
      $grouping += 1;

      // reset the variables
      if( $userID != -1 )
      {
        echo "        <tr>\n";
        echo "          <td class=\"noBorder\" style=\"height:30px;\"colspan=\"" . (5 + count($games)) . "\"></td>";
        echo "        </tr>\n";
      }
      $userID = -1;
      $wagerList = array();
      $currRank = 0;
      $playerCount = 0;
      $currScore = 500;

      // show the title
      echo "        <tr>\n";
      echo "          <td class=\"headerBackgroundTable\" colspan=\"" . (5 + count($games)) . "\" style=\"font-size:24px;\">";
      if( $_SESSION["showPicksSplit"] == "division" )
      {
        echo $thisPick["cName"] . " " . $thisPick["dName"] . " Division";
      }
      else if( $_SESSION["showPicksSplit"] == "conference" )
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
          <td class="headerBackgroundTable" style="width:3%;">W<?php echo $_SESSION["showPicksWeek"]; ?><br>Rank</td>
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
            (($games[$i]["awayScore"] > $games[$i]["homeScore"]) ? $palette2[3] : "#D9DCE3") . ";\"><div class=\"posTeam\">" .
            $teamAliases[$games[$i]["awayTeam"]] . "<div class=\"imgDiv\"><img class=\"teamLogo\" src=\"" . getIcon($games[$i]["awayTeam"], $_SESSION["showPicksSeason"]) .
            "\"/></div></div></td>\n";
        echo "              </tr>\n";
        echo "              <tr onClick=\"ForceWinner(" . $games[$i]["gameID"] . ",'TIE');\">\n";
        echo "                <td class=\"posOther\" style=\"background-color:" .
            (($games[$i]["awayScore"] == $games[$i]["homeScore"]) ? $palette2[3] : "#D9DCE3") . ";\">Tie</td>\n";
        echo "              </tr>\n";
        echo "              <tr onClick=\"ForceWinner(" . $games[$i]["gameID"] . ",'" . $games[$i]["homeTeam"] . "');\">\n";
        echo "                <td class=\"posOther\" style=\"background-color:" .
            (($games[$i]["awayScore"] < $games[$i]["homeScore"]) ? $palette2[3] : "#D9DCE3") . ";\"><div class=\"posTeam\">" .
            $teamAliases[$games[$i]["homeTeam"]] . "<div class=\"imgDiv\"><img class=\"teamLogo\" src=\"" . getIcon($games[$i]["homeTeam"], $_SESSION["showPicksSeason"]) .
            "\"/></div></div></td>\n";
        echo "              </tr>\n";
        echo "              <tr>\n";
        echo "                <td colspan=\"2\" style=\"height:35px;\" class=\"noBorder\">";
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
      }
?>
          <td class="headerBackgroundTable" style="width:3%; border-left:none;">
            <input type="submit" onclick="AdjustMNF(1);" value="+"><br>
            Score<br><input id="adjust<?php echo $grouping; ?>" value="<?php echo $MNFscore; ?>" style="display:none; width:60%" 
            onFocusOut="SubmitScoreBox(<?php echo $grouping; ?>);" onKeyUp="KeyUpScoreBox(event, <?php echo $grouping; ?>);">
            <span id="caption<?php echo $grouping; ?>" style="cursor:pointer" onClick="ShowScoreBox(<?php echo $grouping; ?>);"><?php echo $MNFscore; ?></span><br>
            <input type="submit" onclick="AdjustMNF(-1);" value="-">
          </td>
          <td class="headerBackgroundTable" style="width:3%; cursor:pointer;" onClick="SortTable('weekPts');">PTS</td>
          <td class="headerBackgroundTable" style="width:3%; cursor:pointer;" onClick="SortTable('ytdPts');">YTD</td>
<?php
/*
<table style="width:10px; height:100%; border:2px solid #314972; background-color:#D9DCE3; margin:auto;"><tr style="width:100%; height:100%;"><td style="width:100%; height:100%;">&nbsp;</td></tr></table>
*/
    }

    // print their rank and name
    if( $thisPick["userID"] != $userID )
    {
      // get their rank
      $playerCount++;
      if( $thisPick["wPts"] < $currScore )
      {
        $currRank = $playerCount;
        $currScore = $thisPick["wPts"];
      }

      $userID = $thisPick["userID"];
      $wagerList = array();
      echo "        </tr>\n        <tr class=\"" . (($myID == $thisPick["userID"]) ? "my" : "table") .
          "Row\">\n          <td class=\"lightBackgroundTable\">" . $currRank . "</td>\n          " .
          "<td class=\"lightBackgroundTable" . (($myID == $thisPick["userID"]) ? " myName\" id=\"myPicks" : "") .
          "\">" . $thisPick["pName"] . "</td>\n";
    }

    // show their pick
    echo "          <td class=\"lightBackgroundTable\">";
    if($thisPick["winner"] == "" && $thisPick["status"] == 1)
    {
      echo "--";
    }
    else if($thisPick["winner"] == "")
    {
      echo "<div class=\"cellShadeOuter\">\n";
      echo "<div class=\"cellShadeBG\" style=\"background-color:" . $palette2[2] . "\"></div>\n";
      echo "<table class=\"cellShadeTable\"><tr><td class=\"noBorder\"><div class=\"centerIt\" style=\"color:" . $palette[2] . "\"><span>Missed<br>(" .
           $thisPick["pPts"] . ")</span></div><span class=\"blankIt\">MIS 19</span><br>";
      echo "<div class=\"imgDiv blankIt\"><img class=\"teamLogo\" src=\"" . getIcon("BUF", $_SESSION["showPicksSeason"]) . "\"/></div>";
      echo "</td></tr></table>";
      echo "</div>\n";
    }
    else if( $thisPick["status"] == 1 && $thisPick["userID"] != $myID && $standingsType == "actual")
    {
      echo "X";
    }
    else
    {
      $green = ($thisPick["winner"] == $thisPick["leader"]);
      if( isset($_SESSION["forcedWinners"][$thisPick["gameID"]]) )
      {
        $green = ($_SESSION["forcedWinners"][$thisPick["gameID"]] == $thisPick["winner"]);
      }
      if( $thisPick["winner"] == "AUTOWIN" )
      {
        $nextWager = 16;
        while( isset($wagerList[$nextWager]) )
        {
          $nextWager--;
        }
        echo "<div class=\"cellShadeOuter\">\n";
        echo "<div class=\"cellShadeBG\" style=\"background-color:" . $palette2[0] . "\"></div>\n";
        echo "<table class=\"cellShadeTable\"><tr><td class=\"noBorder\"><span class=\"blankIt\">MIS 19</span><br>";
        echo "<div class=\"imgDiv blankIt\"><img class=\"teamLogo\" src=\"" . getIcon("BUF", $_SESSION["showPicksSeason"]) . "\"/></div>";
        echo "<div class=\"centerIt\" style=\"" . $palette[0] . "\">X<br>(" . $nextWager . ")</div></td></tr></table>";
        echo "</div>\n";
        $wagerList[$nextWager] = true;
      }
      else if( $thisPick["winner"] == "AUTOLOSE" )
      {
        $nextWager = 16;
        while( isset($wagerList[$nextWager]) )
        {
          $nextWager--;
        }
        echo "<div class=\"cellShadeOuter\">\n";
        echo "<div class=\"cellShadeBG\" style=\"background-color:" . $palette2[2] . "\"></div>\n";
        echo "<table class=\"cellShadeTable\"><tr><td class=\"noBorder\"><span class=\"blankIt\">MIS 19</span><br>";
        echo "<div class=\"imgDiv blankIt\"><img class=\"teamLogo\" src=\"" . getIcon("BUF", $_SESSION["showPicksSeason"]) . "\"/></div>";
        echo "<div class=\"centerIt\" style=\"color:" . $palette[2] . "\">X<br>(" . $nextWager . ")</div></td></tr></table>";
        echo "</div>\n";
        $wagerList[$nextWager] = true;
      }
      else
      {
        echo "<div class=\"cellShadeOuter\">\n";
        echo "<div class=\"cellShadeBG\" style=\"background-color:" . ($green ? $palette2[0] : $palette2[2]) . ";\"></div>\n";
        $span = "<span style=\"color:" . ($green ? $palette[0] : $palette[2]) . ";\">" . $teamAliases[$thisPick["winner"]] . " " . $thisPick["pPts"] . "</span>";
        echo "<table class=\"cellShadeTable\"><tr><td class=\"noBorder\">" . ($logosHidden ? 
            ("<div class=\"centerIt\">" . $span . "</div><div class=\"blankIt\">") : "") . $span . "<br>";
        echo "<div class=\"imgDiv\"><img class=\"teamLogo\" src=\"" . getIcon($thisPick["winner"], $_SESSION["showPicksSeason"]) . "\"/></div>";
        echo ($logosHidden ? "</div>" : "") . "</td></tr></table>";
        echo "</div>\n";
        $wagerList[$thisPick["pPts"]] = true;
      }
    }
    echo "</td>\n";

    // see if that ends this person's picks
    if( ($nextPick == null) || ($nextPick["userID"] != $thisPick["userID"]) )
    {
      //echo "          <td class=\"lightBackgroundTable\">" . (($tbUnlocked && $thisPick["userID"] != $myID) 
      //    ? "X" : $thisPick["tieBreaker"]) . "</td>\n";
      echo "          <td class=\"lightBackgroundTable\">" . (($thisPick["status"] == 1 && $thisPick["userID"] != $myID) 
          ? "X" : $thisPick["tieBreaker"]) . "</td>\n";
      echo "          <td class=\"lightBackgroundTable\">" . $thisPick["wPts"] . "</td>\n";
      echo "          <td class=\"lightBackgroundTable\">" . $thisPick["sPts"] . "</td>\n";
    }
  }
  echo "        </tr>\n";
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
                // fix the heading
                if( mostRecentSort == "weekPts" || mostRecentSort == "ytdPts" )
                {
                  rows[i1-1].cells[0].innerHTML = ((mostRecentSort == "weekPts") ? "W<?php echo $_SESSION["showPicksWeek"];
                  ?>" : "YTD") + "<br>Rank";
                }

                // find the end of this section
                var i2 = i1;
                while( i2 >= 0 && i2 < rows.length && rows[i2].cells[0].className == "lightBackgroundTable" )
                {
                  i2 += 1;
                }

                // sort these rows
                var compareIndex = rows[i1].cells.length - 2;
                if( arg == "ytdPts" )
                {
                  compareIndex += 1;
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
            $('html, body').scrollTop(scrollTop);
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
              var thisTB = parseInt(rows[j].cells[rows[j].cells.length - 3].innerHTML);
              var thisRow = [j, thisVal, isNumeric<?php echo ($tbUnlocked ? "" : ", Math.abs(MNF - thisTB), false, thisTB, false"); ?>];
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
              if( mostRecentSort == "weekPts" || mostRecentSort == "ytdPts" )
              {
                thisScore = parseInt(rows[j+start].cells[rows[j+start].cells.length - ((mostRecentSort == "weekPts") ? 2 : 1)].innerHTML);
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
              }
            }

            xmlhttp.open("GET", "display/ShowWeekPossibilitiesTable.php?type=" + args, true);
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
                  for( var j=2; j<rows[i1].cells.length - 3; j++ )
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
                      t.rows[0].cells[0].style.backgroundColor = (teamAliases[winner] == aTeam) ? "<?php echo $palette2[3]; ?>" : "#D9DCE3";
                      t.rows[1].cells[0].style.backgroundColor = (winner == "TIE") ? "<?php echo $palette2[3]; ?>" : "#D9DCE3";
                      var hTeam = t.rows[2].cells[0].firstElementChild.innerHTML;
                      hTeam = hTeam.slice(0, hTeam.indexOf("<"));
                      t.rows[2].cells[0].style.backgroundColor = (teamAliases[winner] == hTeam) ? "<?php echo $palette2[3]; ?>" : "#D9DCE3";
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
                    var nowRight = (txt.innerHTML.slice(0, teamAliases[winner].length) == teamAliases[winner]);
                    BG.style.backgroundColor = (nowRight ? "<?php echo $palette2[0]; ?>": "<?php echo $palette2[2]; ?>");
                    txt.style.color = (nowRight ? "<?php echo $palette[0]; ?>": "<?php echo $palette[2]; ?>");
                    // update their scores if we need to
                    if( wasRight != nowRight )
                    {
                      var score = parseInt(txt.innerHTML.slice(txt.innerHTML.indexOf(" ") + 1)) * (nowRight ? 1 : -1);
                      var pts = rows[j].cells[rows[j].cells.length - 2];
                      var ytd = rows[j].cells[rows[j].cells.length - 1];
                      pts.innerHTML = parseInt(pts.innerHTML) + score;
                      ytd.innerHTML = parseInt(ytd.innerHTML) + score;
                    }
                  }
                }

                i1 = i2;
              }
            }

            SortTable((mostRecentSort=="ytdPts") ? "ytdPts" : "weekPts");

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

            SortTable((mostRecentSort=="ytdPts") ? "ytdPts" : "weekPts");
          }
        </script>
