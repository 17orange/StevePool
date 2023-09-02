<?php
  // start the session if we dont have one
  if(session_id() == '') {
  //if (session_status() == PHP_SESSION_NONE) {
    session_start();

    include "../util.php";
  }

  // make sure we've got the week right
  if( !isset($result) )
  {
    $result = RunQuery("select weekNumber, season from Game where lockTime >= now() and status!=19 order by weekNumber limit 1", false);
    $result = $result[0];
  }
?>
      <span>Making Picks for Week <?php echo $result["weekNumber"]; ?></span>
      <button onClick="PickAllHomeTeamsMobile()">All Home Teams</button>
      <button onClick="PickAllAwayTeamsMobile()">All Away Teams</button>
      <button onClick="PrintPicks()">Print Picks Worksheet</button>
      <form action="helpers/changeAccountDetails.php" method="post" id="cbmForm" target="taskWindow" style="display:inline-block">
        <input type="hidden" name="task" value="cbm" />
        <input type="hidden" name="acctCBM" id="cbmVal" value="<?php echo (($_SESSION["cbm"] ?? "N") == "Y") ? "N" : "Y"; ?>" />
        <button onClick="$('#cbmForm').submit();">Alternate Colors</button>
      </form>
      <br/>
      <table style="width:100%; border-spacing:0px; text-align:center; margin:auto" class="testTable" id="mobilePicksTable"><tbody>
        <tr><td class="noBorder" colspan=5>&nbsp;</td></tr>
        <tr>
          <td class="noBorder warningZone" colspan=2>&nbsp;</td>
          <td class="noBorder" colspan=1 style="text-align:center; font-size: 20px;">Winner</td>
          <td class="noBorder warningZone" colspan=2>&nbsp;</td>
        </tr>
<?php
  // grab their picks for this week
  $pickResults = RunQuery( "select gameID, points, winner, tieBreakOrder, gameTime, homeTeam, awayTeam, lockTime > now() as canChange, " . 
                           "status, timeLeft, homeScore, awayScore from Pick join Game using (gameID) " . 
                           "join Session using (userID) where sessionID=" . $_SESSION["spsID"] . " and weekNumber=" . 
                           $result["weekNumber"] . " and season=" . $result["season"], false );
  $picks = array();
  $playedTeams = "'XQZ'";
  $MNFgame = null;
  foreach( $pickResults as $thisPick )
  {
    $picks[17 - $thisPick["points"]] = $thisPick;
    $playedTeams .= ",'" . $thisPick["homeTeam"] . "','" . $thisPick["awayTeam"] . "'";
    if( $MNFgame == null || ($MNFgame["tieBreakOrder"] < $thisPick["tieBreakOrder"]) || ($MNFgame["gameTime"] < $thisPick["gameTime"]) || (($MNFgame["gameTime"] == $thisPick["gameTime"]) && ($MNFgame["gameID"] < $thisPick["gameID"])) )
    {
      $MNFgame = $thisPick;
    }
  }

  // grab the teams on bye
  $byes = array();
  $byeCount = 0;
  $byeResults = RunQuery( "select teamID from Team where teamID not in (" . $playedTeams . ") and isActive='Y' ");
  foreach( $byeResults as $thisBye )
  {
    $byes[count($byes)] = $thisBye["teamID"];
  }

  // dump out the info
  for($i=1; $i<17; $i++)
  {
    echo "            <tr class=\"montserrat mobileRow\">\n";

    // first row
    $style = (!isset($picks[$i]) || ($picks[$i]["winner"] != $picks[$i]["homeTeam"])) 
             ? " class=\"mobileCell noBorder noMouse\"" 
             : " class=\"mobileCell mpImgTD mpMobileAwayTeam" . (($picks[$i]["canChange"] == 0) ? "Locked" : "") . "\"";
    $text = (!isset($picks[$i]) || ($picks[$i]["winner"] != $picks[$i]["homeTeam"])) 
            ? ""
            : ("<table class=\"innerMobileTable\"><tr><td class=\"noBorder\" style=\"width:40%\">" . $teamAliases[$picks[$i]["awayTeam"]] . 
               "<br>&nbsp;</td><td class=\"noBorder\" style=\"width:60%\"><div class=\"imgDiv\">" . 
               "<img class=\"teamLogo\" src=\"" . getIcon($picks[$i]["awayTeam"], $result["season"]) . 
               "\" draggable=\"false\" ondragstart=\"return false;\" /></div></td></tr></table>");
    echo "              <td id=\"mp1_" . $i . "\"" . $style . ">" . $text . "</td>\n";

    // second row
    $style = (!isset($picks[$i]) || ($picks[$i]["winner"] == $picks[$i]["awayTeam"])) 
             ? " class=\"mobileCell noBorder noMouse\"" 
             : (($picks[$i]["winner"] == $picks[$i]["homeTeam"]) 
               ? " class=\"mobileCell mpMobileGameInfo" . (($picks[$i]["canChange"] == 0) ? "Locked" : "") . "\"" 
               : " class=\"mobileCell mpImgTD mpMobileAwayTeam" . (($picks[$i]["canChange"] == 0) ? "Locked" : "") . "\"");
    $text = (!isset($picks[$i]) || ($picks[$i]["winner"] == $picks[$i]["awayTeam"])) 
            ? ""
            : (($picks[$i]["winner"] == $picks[$i]["homeTeam"]) 
              ? formatTimeMobile($picks[$i]) 
              : ("<table class=\"innerMobileTable\"><tr><td class=\"noBorder\" style=\"width:40%\">" . $teamAliases[$picks[$i]["awayTeam"]] . 
                 "<br>&nbsp;</td><td class=\"noBorder\" style=\"width:60%\"><div class=\"imgDiv\">" . 
                 "<img class=\"teamLogo\" src=\"" . getIcon($picks[$i]["awayTeam"], $result["season"]) . 
                 "\" draggable=\"false\" ondragstart=\"return false;\" /></div></td></tr></table>"));
    // special case for teams on bye
    if( $i > 0 && !isset($picks[$i]) )
    {
      $style = " class=\"mobileCell mpImgTD mpMobileAwayTeamLocked\"";
      $text = "<table class=\"innerMobileTable\"><tr><td class=\"noBorder\" style=\"width:40%\">" . $byes[$byeCount] . 
              "<br>&nbsp;</td><td class=\"noBorder\" style=\"width:60%\"><div class=\"imgDiv\">" . 
              "<img class=\"teamLogo\" src=\"" . getIcon($byes[$byeCount], $result["season"]) . 
              "\" draggable=\"false\" ondragstart=\"return false;\" /></div></td></tr></table>";
      $byeCount++;
    }
    echo "              <td id=\"mp2_" . $i . "\"" . $style . ">" . $text . "</td>\n";

    // third row
    $saveButtonEnabled = true;
    $style = (!isset($picks[$i]) || ($picks[$i]["canChange"] == 0))
             ? (($i==0)
               ? " class=\"mobileCell noBorder fjalla\" style=\"font-size: 20px;\""
               : " class=\"mobileCell mpImgTD mpLockedSelection\"") 
             : (($picks[$i]["winner"] == $picks[$i]["awayTeam"]) || ($picks[$i]["winner"] == $picks[$i]["homeTeam"])
               ? " class=\"mobileCell mpImgTD mpValidSelection" . ($_SESSION["cbm"] ? " CBM" : "") .  "\""
               : " class=\"mobileCell mpImgTD mpInvalidSelection" . ($_SESSION["cbm"] ? " CBM" : "") .  "\"" );
    if( $saveButtonEnabled )
    {
      $saveButtonEnabled = ($style == " class=\"mpInvalidSelection" . ($_SESSION["cbm"] ? " CBM" : "") .  "\"");
    }
    $text = ($i==0) 
            ? "Winner" 
            : (!isset($picks[$i])
              ? "Bye Week"
              : (($picks[$i]["winner"] == $picks[$i]["awayTeam"])
                ? ("<table class=\"innerMobileTable\"><tr><td class=\"noBorder\" style=\"width:40%\">" . $teamAliases[$picks[$i]["awayTeam"]] . 
                   "<br>" . $picks[$i]["points"] . "</td><td class=\"noBorder\" style=\"width:60%\">" . 
                   "<div class=\"imgDiv\"><img class=\"teamLogo\" src=\"" . getIcon($picks[$i]["awayTeam"], $result["season"]) . 
                   "\" draggable=\"false\" ondragstart=\"return false;\" /></div></td></tr></table>")
                : (($picks[$i]["winner"] == $picks[$i]["homeTeam"]) 
                  ? ("<table class=\"innerMobileTable\"><tr><td class=\"noBorder\" style=\"width:40%\">" . $teamAliases[$picks[$i]["homeTeam"]] . 
                     "<br>" . $picks[$i]["points"] . "</td><td class=\"noBorder\" style=\"width:60%\">" . 
                     "<div class=\"imgDiv\"><img class=\"teamLogo\" src=\"" . getIcon($picks[$i]["homeTeam"], $result["season"]) . 
                     "\" draggable=\"false\" ondragstart=\"return false;\" /></div></td></tr></table>")
                  : formatTimeMobile($picks[$i]))));
    echo "            <td id=\"mp3_" . $i . "\"" . $style . ">" . $text . "</td>\n";

    // fourth row
    $style = (!isset($picks[$i]) || ($picks[$i]["winner"] == $picks[$i]["homeTeam"])) 
             ? " class=\"mobileCell noBorder noMouse\"" 
             : (($picks[$i]["winner"] == $picks[$i]["awayTeam"]) 
               ? " class=\"mobileCell mpMobileGameInfo" . (($picks[$i]["canChange"] == 0) ? "Locked" : "") . "\"" 
               : " class=\"mobileCell mpImgTD mpMobileHomeTeam" . (($picks[$i]["canChange"] == 0) ? "Locked" : "") . "\"");
    $text = (!isset($picks[$i]) || ($picks[$i]["winner"] == $picks[$i]["homeTeam"])) 
            ? ""
            : (($picks[$i]["winner"] == $picks[$i]["awayTeam"]) 
              ? formatTimeMobile($picks[$i]) 
              : ("<table class=\"innerMobileTable\"><tr><td class=\"noBorder\" style=\"width:40%\">" . $teamAliases[$picks[$i]["homeTeam"]] . 
                 "<br>&nbsp;</td><td class=\"noBorder\" style=\"width:60%\"><div class=\"imgDiv\">" . 
                 "<img class=\"teamLogo\" src=\"" . getIcon($picks[$i]["homeTeam"], $result["season"]) . 
                 "\" draggable=\"false\" ondragstart=\"return false;\" /></div></td></tr></table>"));
    // special case for teams on bye
    if( $i > 0 && !isset($picks[$i]) )
    {
      $style = " class=\"mobileCell mpImgTD mpMobileHomeTeamLocked\"";
      $text = "<table class=\"innerMobileTable\"><tr><td class=\"noBorder\" style=\"width:40%\">" . $byes[$byeCount] . 
              "<br>&nbsp;</td><td class=\"noBorder\" style=\"width:60%\"><div class=\"imgDiv\">" . 
              "<img class=\"teamLogo\" src=\"" . getIcon($byes[$byeCount], $result["season"]) . 
              "\" draggable=\"false\" ondragstart=\"return false;\" /></div></td></tr></table>";
      $byeCount++;
    }
    echo "              <td id=\"mp4_" . $i . "\"" . $style . ">" . $text . "</td>\n";

    // fifth row
    $style = (!isset($picks[$i]) || ($picks[$i]["winner"] != $picks[$i]["awayTeam"])) 
             ? " class=\"mobileCell noBorder noMouse\"" 
             : " class=\"mobileCell mpImgTD mpMobileHomeTeam" . (($picks[$i]["canChange"] == 0) ? "Locked" : "") . "\"";
    $drag = ""; //(isset($picks[$i]) && ($picks[$i]["canChange"] != 0)) ? " onMouseDown=\"startDrag(5, " . $i . ");\"" : "";
    $text = (!isset($picks[$i]) || ($picks[$i]["winner"] != $picks[$i]["awayTeam"])) 
            ? ""
            : ("<table class=\"innerMobileTable\"><tr><td class=\"noBorder\" style=\"width:40%\">" . $teamAliases[$picks[$i]["homeTeam"]] . 
               "<br>&nbsp;</td><td class=\"noBorder\" style=\"width:60%\"><div class=\"imgDiv\">" . 
               "<img class=\"teamLogo\" src=\"" . getIcon($picks[$i]["homeTeam"], $result["season"]) . 
               "\" draggable=\"false\" ondragstart=\"return false;\" /></div></td></tr></table>");
    echo "              <td id=\"mp5_" . $i . "\"" . $style . ">" . $text . "</td>\n";

    echo "            </tr>\n";
  }
?>
        <tr style="height:50px;">
          <td class="noBorder" colspan="5">
            <form action="." method="post" id="makePicksForm">
              <input type="hidden" id="picksType" name="picksType" value="regularSeason">
              <input type="hidden" id="windowScrollPos" name="windowScrollPos" value="0">
<?php
  for( $i=1; $i<17; $i++ )
  {
    if( isset($picks[$i]) && ($picks[$i]["canChange"] != 0) )
    {
      echo "              <input type=\"hidden\" id=\"game" . $i . "\" name=\"game" . $i . "\" value=\"" . 
          $picks[$i]["gameID"] . "\">\n";
      echo "              <input type=\"hidden\" id=\"homeTeam" . $i . "\" name=\"homeTeam" . $i . "\" value=\"" . 
          $picks[$i]["homeTeam"] . "\">\n";
      echo "              <input type=\"hidden\" id=\"awayTeam" . $i . "\" name=\"awayTeam" . $i . "\" value=\"" . 
          $picks[$i]["awayTeam"] . "\">\n";
      echo "              <input type=\"hidden\" id=\"pts" . $i . "\" name=\"pts" . $i . "\" value=\"" . (17 - $i) . "\">\n";
      echo "              <input type=\"hidden\" id=\"winner" . $i . "\" name=\"winner" . $i . "\" value=\"\">\n";
    }
  }

  // grab the tiebreaker they set
  $TBresult = RunQuery( "select tieBreaker from WeekResult join Session using (userID) where sessionID=" . $_SESSION["spsID"] . 
                        " and weekNumber=" . $result["weekNumber"] . " and season=" . $result["season"] );
?>
              <span style="font-size: 20px"><?php echo ($MNFgame["awayTeam"] . " @ " . $MNFgame["homeTeam"]); ?> Combined Score</span>
              <input id="tieBreak" name="tieBreak" type="text" maxlength="3" onKeyUp="NumbersOnly(); ToggleSaveButtonMobile();" value="<?php
  echo ($TBresult[0]["tieBreaker"] != 0) ? $TBresult[0]["tieBreaker"] : "";
?>" style="width:35px;margin-right:50px" />
              <button id="saveRosterButton" class="bigButton" onclick="document.getElementById('windowScrollPos').value = $(window).scrollTop(); showWarning = false; document.getElementById('makePicksForm').submit();"<?php 
  echo $saveButtonEnabled ? "" : " disabled"; ?>>Save Picks</button>
            </form>
          </td>
        </tr>
      </table>
      <div id="PrintWorksheet" style="color:#000000; display:none;">
        <table style="border-spacing:0px;">
          <tr><td colspan=3 style="border:3px solid #000000; padding: 5px;">Week <?php echo $result["weekNumber"]; ?></td></tr>
<?php
  $theseGames = RunQuery( "select gameTime, concat(hTeam.city, ' ', hTeam.nickname) as homeTeam, " . 
                          "concat(aTeam.city, ' ', aTeam.nickname) as awayTeam from Game join Team as hTeam " . 
                          "on (homeTeam=hTeam.teamID) join Team as aTeam on (awayTeam=aTeam.teamID) " . 
                          "where weekNumber=" . $result["weekNumber"] . " and season=" . $result["season"] . " order by gameID" );
  $day = "";
  foreach( $theseGames as $thisGame ) {
    // build the day string
    $thisDay = explode("<br>", formatTimeMobile($thisGame));
    $thisDay = $thisDay[0] . " " . $thisDay[1];
    if( $day != $thisDay ) {
?>
          <tr><td colspan=3 style="border:3px solid #000000; padding: 5px;"><?php echo $thisDay; ?></td></tr>
          <tr>
            <td style="border:3px solid #000000; padding: 5px;">Away Team</td>
            <td style="border:3px solid #000000; padding: 5px;">Home Team</td>
            <td style="border:3px solid #000000; padding: 5px;">Points</td>
          </tr>
<?php
      $day = $thisDay;
    }
?>
          <tr>
            <td style="border:3px solid #000000; padding: 5px;"><?php echo $thisGame["awayTeam"]; ?></td>
            <td style="border:3px solid #000000; padding: 5px;"><?php echo $thisGame["homeTeam"]; ?></td>
            <td style="border:3px solid #000000; padding: 5px;">&nbsp;</td>
          </tr>
<?php
  }
?>
          <tr>
            <td colspan=2 style="border:3px solid #000000; padding: 5px;">Tiebreaker Score</td>
            <td style="border:3px solid #000000; padding: 5px;">&nbsp;</td>
          </tr>
<?php
  $byeTeams = RunQuery( "select concat(city, ' ', nickname) as name from Team where teamID not in " . 
                        "(select homeTeam from Game where weekNumber=" . $result["weekNumber"] . 
                        " and season=" . $result["season"] . ") and teamID not in (select awayTeam " . 
                        "from Game where weekNumber=" . $result["weekNumber"] . 
                        " and season=" . $result["season"] . ")" );
  if( count($byeTeams) > 0 ) {
?>
          <tr><td colspan=3 style="border:3px solid #000000; padding: 5px;">Bye Teams</td></tr>
<?php
    foreach( $byeTeams as $thisTeam ) {
?>
          <tr><td colspan=3 style="border:3px solid #000000; padding: 5px;"><?php echo $thisTeam["name"]; ?></td></tr>
<?php
    }
  }
?>
        </tbody></table>        
      </div>
