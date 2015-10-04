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
  if( isset($_SESSION["spsID"]) )
  {
    $results = RunQuery( "select coalesce(userID, 0) as userID from Session where sessionID=" . $_SESSION["spsID"] );
    $myID = $results[0]["userID"];
  }

  // grab the next refresh time
  $gamesLive = 0;
  $lastWeek = 18;
  $results = RunQuery( "select count(*) as num, weekNumber from Game where season=" . 
                       $_SESSION["showStandingsSeason"] . " and status=2 order by gameTime, gameID" );
  if( $results[0]["num"] > 0 )
  {
    $gamesLive = $results[0]["num"];
    $lastWeek = $results[0]["weekNumber"];
  }

  $results = RunQuery( "select gameTime, weekNumber from Game where season=" . $_SESSION["showStandingsSeason"] . 
                       " and status=1 order by gameTime asc limit 1" );
  if( count($results) > 0 )
  {
    $results = $results[0];
    $firstRefresh = $results["gameTime"];
    if( $results["weekNumber"] < $lastWeek )
    {
      $lastWeek = $results["weekNumber"];
    }
  }

  // grab all of the rows
  $section = -1;
  $results = RunQuery( "select userID, concat(firstName, ' ', lastName) as pName, WeekResult.points as wPts, " .
                       "SeasonResult.points as sPts, Division.name as dName, Conference.name as cName, weekNumber, " .
                       (($_SESSION["showStandingsSplit"] == "division") ? "divID" : 
                        (($_SESSION["showStandingsSplit"] == "conference") ? "confID" : "1")) . " as section, " .
                       "weeklyWins, missedWeeks, correctPicks, inPlayoffs, firstRoundBye " .    
                       "from WeekResult join SeasonResult using (userID, season) join User using (userID) " . 
                       "join Division using (divID) join Conference using (confID) " . 
                       "where season=" . $_SESSION["showStandingsSeason"] . 
                       " order by section, sPts desc, weeklyWins desc, missedWeeks, correctPicks desc, userID, weekNumber" );
  $userID = -1;
  $currRank = 0;
  $playerCount = 0;
  $currScore = 50000;
  foreach( $results as $num => $thisWeek )
  {
    $nextWeek = ($num < (count($results) - 1)) ? $results[$num + 1] : null;

    // see if we need to start a new table
    if( $thisWeek["section"] != $section )
    {
      $section = $thisWeek["section"];

      // reset the variables
      if( $userID != -1 )
      {
        echo "        <tr>\n";
        echo "          <td class=\"noBorder\" style=\"height:30px;\"colspan=\"25\"></td>";
        echo "        </tr>\n";
      }
      $userID = -1;
      $currRank = 0;
      $playerCount = 0;
      $currRow = array(50000, 0, 0, 0);

      // show the title
      echo "        <tr>\n";
      echo "          <td class=\"headerBackgroundTable\" colspan=\"25\" style=\"font-size:24px\">";
      if( $_SESSION["showStandingsSplit"] == "division" )
      {
        echo $thisWeek["cName"] . " " . $thisWeek["dName"] . " Division";
      }
      else if( $_SESSION["showStandingsSplit"] == "conference" )
      {
        echo $thisWeek["cName"] . " Conference";
      }
      else
      {
        echo "Overall";
      }
      echo " Standings</td>\n";
      echo "        </tr>\n";
?>
        <tr>
          <td class="headerBackgroundTable">Rank</td>
          <td class="headerBackgroundTable" style="cursor:pointer;" onClick="SortTable('name');">Player</td>
<?php
      // show the games from that week
      for( $i=1; $i<18; $i++ )
      {
        echo "          <td class=\"headerBackgroundTable\" style=\"cursor:pointer;\" onClick=\"SortTable('w" . 
            $i . "');\">W" . $i . "</td>\n";
      }
?>
          <td class="headerBackgroundTable" style="cursor:pointer;" onClick="SortTable('points');">Total<br/>Points</td>
          <td class="headerBackgroundTable" style="cursor:pointer;" onClick="SortTable('wins');">Weekly<br/>Wins</td>
          <td class="headerBackgroundTable" style="cursor:pointer;" onClick="SortTable('misses');">Missed<br/>Weeks</td>
          <td class="headerBackgroundTable" style="cursor:pointer;" onClick="SortTable('picks');">Correct<br/>Picks</td>
          <td class="headerBackgroundTable">Playoffs</td>
          <td class="headerBackgroundTable">Bye</td>
        </tr>
<?php
    }

    // print their rank and name
    if( $thisWeek["userID"] != $userID )
    {
      // get their rank
      $playerCount++;
      if( ($thisWeek["sPts"] < $currRow[0]) || 
          (($thisWeek["sPts"] == $currRow[0]) && (($thisWeek["weeklyWins"] < $currRow[1]) ||
          (($thisWeek["weeklyWins"] == $currRow[1]) && ((($currRow[2] == 0) && ($thisWeek["missedWeeks"] > 0)) ||
          ($thisWeek["correctPicks"] < $currRow[3]))))) )
      {
        $currRank = $playerCount;
        $currRow = array($thisWeek["sPts"], $thisWeek["weeklyWins"], $thisWeek["missedWeeks"], $thisWeek["correctPicks"]);
      }

      $userID = $thisWeek["userID"];
      echo "        </tr>\n        <tr class=\"" . (($myID == $thisWeek["userID"]) ? "my" : "table") . "Row\" style=\"color:" . 
          (($thisWeek["inPlayoffs"] == "Y") ? "#007500" : (($thisWeek["inPlayoffs"] == "N") ? "#AF0000" : "#888800")) . "\"" . 
          ">\n          <td class=\"lightBackgroundTable\">" . $currRank . "</td>\n          <td class=\"lightBackgroundTable" . 
          (($myID == $thisWeek["userID"]) ? " myName\" id=\"myStanding" : "") . "\">" . $thisWeek["pName"] . "</td>\n";
    }

    // show their pick
    echo "          <td class=\"lightBackgroundTable\">";
    if($thisWeek["weekNumber"] > $lastWeek)
    {
      echo "&nbsp;";
    }
    else
    {
      echo $thisWeek["wPts"];
    }
    echo "</td>\n";

    // see if that ends this person's picks
    if( ($nextWeek == null) || ($nextWeek["userID"] != $thisWeek["userID"]) )
    {
      echo "          <td class=\"lightBackgroundTable\">" . $thisWeek["sPts"] . "</td>\n";
      echo "          <td class=\"lightBackgroundTable\">" . $thisWeek["weeklyWins"] . "</td>\n";
      echo "          <td class=\"lightBackgroundTable\">" . $thisWeek["missedWeeks"] . "</td>\n";
      echo "          <td class=\"lightBackgroundTable\">" . $thisWeek["correctPicks"] . "</td>\n";
      echo "          <td class=\"lightBackgroundTable\">" . (($thisWeek["inPlayoffs"] == "Y") ? "Yes" : 
          (($thisWeek["inPlayoffs"] == "N") ? "No" : "Maybe")) . "</td>\n";
      echo "          <td class=\"lightBackgroundTable\">" . (($thisWeek["firstRoundBye"] == "Y") ? "Yes" : 
          (($thisWeek["firstRoundBye"] == "N") ? "No" : "Maybe")) . "</td>\n";
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
                var compareIndex = rows[i1].cells.length - 24;
                if( arg == "picks" )
                {
                  compareIndex += 21;
                }
                else if( arg == "misses" )
                {
                  compareIndex += 20;
                }
                else if( arg == "wins" )
                {
                  compareIndex += 19;
                }
                else if( arg == "points" )
                {
                  compareIndex += 18;
                }
                else if( arg != "name")
                {
                  compareIndex += parseInt(arg.substring(1));
                }

                // do the heavy lifting of the sort
                SmartSort(i1, i2, compareIndex, (arg != "name"));

                // increment the counter
                i1 = i2;
              }
            }

            FixAdvance();

            // tell them it's safe
            sorting = false;
          }

          // this does a hybrid of mergesort and insertion sort
          function SmartSort(start, end, compareIndex, isNumeric)
          {
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
              var thisWins = parseInt(rows[j].cells[rows[j].cells.length - 5].innerHTML);
              var thisMisses = parseInt(rows[j].cells[rows[j].cells.length - 4].innerHTML);
              var thisPicks = parseInt(rows[j].cells[rows[j].cells.length - 3].innerHTML);
              var thisRow = [j, thisVal, isNumeric, thisWins, true, thisMisses, false, thisPicks, true];
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
              rows[j + start].className = (rows[j + start].contains(document.getElementById("myStanding")) ? "myRow" : "tableRow");
            }
          }

          function FixAdvance()
          {            
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
                  var advance = (rows[j].cells[rows[j].cells.length - 2].innerHTML == "Yes");
                  var maybe = (rows[j].cells[rows[j].cells.length - 2].innerHTML == "Maybe");
                  rows[j].style.color = (advance ? "#007500": (maybe ? "888800" : "#AF0000"));
                }
                
                i1 = i2;
              }
              else
              {
                i1++;
              }
            }
          }

<?php
  if( $gamesLive > 0 || (isset($firstRefresh) && $firstRefresh != "") )
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
                SortTable(mostRecentSort);
                setTimeout(function() { ReloadPage("<?php echo $standingsType; ?>") }, <?php echo $delayTime; ?>);
              }
            }

            xmlhttp.open("GET", "display/ShowStandingsTable.php?type=" + args, true);
            xmlhttp.send();
          }

          setTimeout(function() { ReloadPage("<?php echo $standingsType; ?>") }, <?php echo $delayTime; ?>);
<?php
  }
?>
        </script>

