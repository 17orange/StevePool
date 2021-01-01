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
  $results = RunQuery( "select *, if(lockTime>now(), 0, 1) as isLocked from Game where weekNumber=" . $_SESSION["showPicksWeek"] . 
                       " and season=" . $_SESSION["showPicksSeason"] . " order by tieBreakOrder, gameTime, gameID", false );
  foreach( $results as $thisGame )
  {
    $games[count($games)] = $thisGame;
    if( $thisGame["status"] == "2" )
    {
      $gamesLive++;
    }
    else if( $thisGame["status"] == "1" )
    {
      if( $firstRefresh == "" )
      {
        $firstRefresh = $thisGame["gameTime"];
      }
    }
  }
  $MNFscore = $games[count($games) - 1]["homeScore"] + $games[count($games) - 1]["awayScore"];
  $section = -1;

  // grab all of the rows
  $tbUnlocked = ($games[count($games) - 1]["isLocked"] == 0);
  $results = RunQuery( "select userID, concat(firstName, ' ', lastName) as pName, winner, tieBreaker, Pick.points as pPts, " . 
                       "if(homeScore>awayScore, homeTeam, if(awayScore>homeScore, awayTeam, '')) as leader, " .
                       "WeekResult.points as wPts, SeasonResult.points as sPts, if(lockTime>now(), 1, 0) as status, " . 
                       "abs(tieBreaker - " . $MNFscore . ") as tb1, Division.name as dName, Conference.name as cName, " .
                       (($_SESSION["showPicksSplit"] == "division") ? "divID" : 
                        (($_SESSION["showPicksSplit"] == "conference") ? "confID" : "1")) . " as section, " .   
                       "if(Game.status=3, 1, 0) as isFinal, homeScore, awayScore, if(Game.status=1, 1, 0) as inFuture " . 
                       "from WeekResult join SeasonResult using (userID, season) join User using (userID) " . 
                       "join Game using (weekNumber, season) join Pick using (userID, gameID) " . 
                       "join Division using (divID) join Conference using (confID) " . 
                       "where weekNumber=" . $_SESSION["showPicksWeek"] . " and season=" . $_SESSION["showPicksSeason"] . 
                       " order by section, wPts desc" . ($tbUnlocked ? "" : ", tb1, tieBreaker") . 
                       ", userID, tieBreakOrder, gameTime, gameID" );
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
        echo "          <td class=\"noBorder\" style=\"height:30px;\"colspan=\"" . (6 + count($games)) . "\"></td>";
        echo "        </tr>\n";
      }
      $userID = -1;
      $currRank = 0;
      $playerCount = 0;
      $currScore = 500;
      $possibleMax = 0;

      // show the title
      echo "        <tr>\n";
      echo "          <td colspan=\"" . (6 + count($games)) . "\" class=\"headerBackgroundTable\" style=\"font-size:24px;\">";
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
      }
?>
          <td class="headerBackgroundTable" style="width:3%; border-left:none;">Score<br><span id="caption<?php echo $grouping; ?>"><?php echo $MNFscore; ?></span></td>
          <td class="headerBackgroundTable" style="width:3%; cursor:pointer;" onClick="SortTable('points');">Total<br/>Points</td>
          <td class="headerBackgroundTable" style="width:3%; cursor:pointer;" onClick="SortTable('maxPts');">Max</td>
          <td class="headerBackgroundTable" style="width:3%; cursor:pointer;" onClick="SortTable('ytdPts');">YTD</td>
<?php
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
      $possibleMax = 0;

      $userID = $thisPick["userID"];
      echo "        </tr>\n        <tr class=\"" . (($myID == $thisPick["userID"]) ? "my" : "table") . 
          "Row\">\n          <td class=\"lightBackgroundTable\">" . $currRank . "</td>\n          " . "<td class=\"lightBackgroundTable" . 
          (($myID == $thisPick["userID"]) ? " myName\" id=\"myPicks" : "") . "\">" . $thisPick["pName"] . "</td>\n";
    }

    // show their pick
    echo "          <td class=\"lightBackgroundTable\" style=\"height:100%;\">";
    if($thisPick["winner"] == "" && $thisPick["status"] == 1)
    {
      echo "--";
    }
    else if($thisPick["winner"] == "")
    {
      echo "<div class=\"cellShadeOuter\">\n";
      echo "<div class=\"cellShadeBG\" style=\"background-color:#FF0000;\"></div>\n";
      echo "<table class=\"cellShadeTable\"><tr><td class=\"noBorder\"><span class=\"blankIt\">MIS 19</span><br>";
      echo "<div class=\"imgDiv blankIt\"><img class=\"teamLogo\" src=\"" . getIcon("BUF", $_SESSION["showPicksSeason"]) . "\"/></div>";
      echo "<div class=\"centerIt pickText\" style=\"color:#BF0000;\">Missed<br>(" . $thisPick["pPts"] . ")</div></td></tr></table>";
      echo "</div>\n";
    }
    else if( $thisPick["status"] == 1 && $thisPick["userID"] != $myID && $standingsType == "actual" )
    {
      echo "X";
    }
    else
    {
      echo "<div class=\"cellShadeOuter\">\n";
      echo "<div class=\"cellShadeBG\" style=\"background-color:#" . (($thisPick["winner"] == $thisPick["leader"]) ? "00AA00" : 
          (($thisPick["inFuture"] == 1) ? "D9DCE3" : ((!$thisPick["isFinal"] && ($thisPick["homeScore"] == $thisPick["awayScore"])) 
          ? "FFFF00" : "FF0000"))) . ";\"></div>\n";
      $span = "<span class=\"pickText\" style=\"color:#" . 
          (($thisPick["winner"] == $thisPick["leader"]) ? "007500" : (($thisPick["inFuture"] == 1) ? "0A1F42" : 
          ((!$thisPick["isFinal"] && ($thisPick["homeScore"] == $thisPick["awayScore"])) ? "888800" : "BF0000"))) . ";" . 
          ((!$thisPick["inFuture"] && !$thisPick["isFinal"]) ? " font-style:italic;" : "") . "\">" . $teamAliases[$thisPick["winner"]] . 
          " " . $thisPick["pPts"] . "</span>";
      echo "<table class=\"cellShadeTable\"><tr><td class=\"noBorder\">" . ($logosHidden ? "<div class=\"blankIt\">" : "") . $span . "<br>";
      echo "<div class=\"imgDiv\"><img class=\"teamLogo" . ($logosHidden ? " blankIt" : "") . "\" src=\"" . 
          getIcon($thisPick["winner"], $_SESSION["showPicksSeason"]) . "\"/></div>";
      echo ($logosHidden ? ("</div><div class=\"centerIt\">" . $span . "</div>") : "") . "</td></tr></table>";
      echo "</div>\n";
    }
    echo "</td>\n";

    // factor it into the max
    $possibleMax += (($thisPick["isFinal"] && ($thisPick["winner"] != $thisPick["leader"])) ||  // its final and they picked wrong
                     (($thisPick["status"] != 1) && ($thisPick["winner"] == "")))               // they missed it
                    ? 0 : $thisPick["pPts"];

    // see if that ends this person's picks
    if( ($nextPick == null) || ($nextPick["userID"] != $thisPick["userID"]) )
    {
      //echo "          <td class=\"lightBackgroundTable\">" . (($thisPick["tieBreaker"] == "") ? "--" : 
      //    (($tbUnlocked && $thisPick["userID"] != $myID) ? "X" : $thisPick["tieBreaker"])) . "</td>\n";
      echo "          <td class=\"lightBackgroundTable\">" . (($thisPick["tieBreaker"] == "") ? "--" : 
          (($thisPick["status"] == 1 && $thisPick["userID"] != $myID) ? "X" : $thisPick["tieBreaker"])) . "</td>\n";
      echo "          <td class=\"lightBackgroundTable\">" . $thisPick["wPts"] . "</td>\n";
      echo "          <td class=\"lightBackgroundTable\">" . $possibleMax . "</td>\n";
      echo "          <td class=\"lightBackgroundTable\">" . $thisPick["sPts"] . "</td>\n";
    }
  }
  echo "        </tr>\n";
?>
        <script type="text/javascript">
          var mostRecentSort = "points";
          function SortTable(arg)
          {
            var i1 = 0;
            mostRecentSort = arg;
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
                if( mostRecentSort == "points" || mostRecentSort == "ytdPts" )
                {
                  rows[i1-1].cells[0].innerHTML = ((mostRecentSort == "points") ? "W<?php echo $_SESSION["showPicksWeek"];
                  ?>" : "YTD") + "<br>Rank";
                }

                // find the end of this section
                var i2 = i1;
                while( i2 >= 0 && i2 < rows.length && rows[i2].cells[0].className == "lightBackgroundTable" )
                {
                  i2 += 1;
                }

                // sort these rows
                var compareIndex = rows[i1].cells.length - 3;
                if( arg == "maxPts" )
                {
                  compareIndex += 1;
                }
                else if( arg == "ytdPts" )
                {
                  compareIndex += 2;
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
              var thisTB = parseInt(rows[j].cells[rows[j].cells.length - 4].innerHTML);
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
              if( mostRecentSort == "points" || mostRecentSort == "ytdPts" )
              {
                thisScore = parseInt(rows[j+start].cells[rows[j+start].cells.length - ((mostRecentSort == "points") ? 3 : 1)].innerHTML);
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
<?php
  if( $gamesLive > 0 || $firstRefresh != "" )
  {
    $delayTime = ($gamesLive > 0) ? 60000 : ((strtotime($firstRefresh) - time()) * 1000);
    if( $delayTime > 86400000 )
    {
      $delayTime = 86400000;
    }
?>
                SortTable(mostRecentSort);
                setTimeout(function() { ReloadPage("<?php echo $standingsType; ?>") }, <?php echo $delayTime; ?>);
<?php
  }
?>
              }
            }

            xmlhttp.open("GET", "display/ShowWeekPicksTable.php?type=" + args, true);
            xmlhttp.send();
          }

<?php
  if( $gamesLive > 0 || $firstRefresh != "" )
  {
?>
          setTimeout(function() { ReloadPage("<?php echo $standingsType; ?>") }, <?php echo $delayTime; ?>);
<?php
  }
?>
        </script>
