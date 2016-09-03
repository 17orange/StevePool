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
      <span>Making Mobile Picks for Super Bowl</span>
<?php
  // grab the two teams
  $superBowlTeams = RunQuery( "select awayTeam as AFC, homeTeam as NFC from Game where weekNumber=" . 
                              $result["weekNumber"] . " and season=" . $result["season"] );
?>
      <button class="bigButton" onClick="PickAllAwayTeamsMobile()">All <?php echo $superBowlTeams[0]["AFC"]; ?></button>
      <button class="bigButton" onClick="PickAllHomeTeamsMobile()">All <?php echo $superBowlTeams[0]["NFC"]; ?></button>
      <br/>
      <table style="width:100%; border-spacing:0px; text-align:center; font-size: 14px;">
        <tr><td class="noBorder" colspan=6>&nbsp;</td></tr>
        <tr>
          <td class="noBorder" colspan=2>&nbsp;</td>
          <td class="noBorder" colspan=1 style="text-align:center; font-size: 30px;">My Pick</td>
          <td class="noBorder" colspan=2>&nbsp;</td>
        </tr>
        <tr><td class="noBorder" colspan=5>&nbsp;</td></tr>
<?php
  // grab their picks for this week
  $pickResults = RunQuery( "select gameID, points, winner, gameTime, homeTeam, awayTeam, lockTime > now() as canChange, " . 
                           "status, timeLeft, homeScore, awayScore, type, if(type='winner', 1, if(type='winner3Q', 2, " . 
                           "if(type='winner2Q', 3, if(type='winner1Q', 4, if(type='passYds', 5, if(type='passYds2Q', 6, " . 
                           "if(type='rushYds', 7, if(type='rushYds2Q', 8, if(type='TDs', 9,10))))))))) as ord from Pick " . 
                           "join Game using (gameID) join Session using (userID) where sessionID=" . $_SESSION["spsID"] . 
                           " and weekNumber=" . $result["weekNumber"] . " and season=" . $result["season"], false );
  $picks = array();
  foreach( $pickResults as $thisPick )
  {
    $picks[$thisPick["ord"]] = $thisPick;
  }

  // fill in the rows
  $saveButtonEnabled = true;
  $caption = array("", "Super Bowl Winner", "Leader 3Q", "Leader 2Q", "Leader 1Q", "Pass Yds Final", 
                   "Pass Yds 2Q", "Rush Yds Final", "Rush Yds 2Q", "TDs Final", "TDs 2Q");
  for($i=1; $i<11; $i++)
  {
    // show the pick type header
    echo "        <tr style=\"font-size:24px;\" class=\"montserrat\">\n";
    $offset = (isset($picks[$i]) && ($picks[$i]["winner"] == $picks[$i]["homeTeam"])) ? 1 : 
              ((isset($picks[$i]) && ($picks[$i]["winner"] == $picks[$i]["awayTeam"])) ? 3 : 2);
    for( $j=1; $j<4; $j++ ) {
      echo "          <td id=\"mpH" . $j . "_" . $i . "\" class=\"" . (($offset == $j) ? "mpMobilePickType" : "noBorder") . "\" colspan=\"" . 
          (($offset == $j) ? "3" : 1) . "\">" . (($offset == $j) ? $caption[$i] : "") . "</td>\n";
    }
    echo "        </tr>\n";
    echo "        <tr style=\"height:75px; font-size:24px;\" class=\"montserrat\">\n";

    // fill in the first element
    $style = (!isset($picks[$i]) || ($picks[$i]["winner"] != $picks[$i]["homeTeam"])) 
             ? " class=\"noBorder mpMobileBGText mpMobileWipeTop\" style=\"text-align: right;\"" 
             : " class=\"mpImgTD mpMobileAwayTeam" . (($picks[$i]["canChange"] == 0) ? "Locked" : "") . " mpMobileWipeTop\"";
    $drag = (isset($picks[$i]) && ($picks[$i]["winner"] == $picks[$i]["homeTeam"]) && ($picks[$i]["canChange"] != 0)) 
            ? " onClick=\"SetWinnerMobile(this.id.slice(this.id.indexOf('_') + 1), false);\"" 
            : "";
    $text = (!isset($picks[$i]) || ($picks[$i]["winner"] != $picks[$i]["homeTeam"])) 
            ? (($picks[$i]["points"] == 0) ? "" : $picks[$i]["points"])
            : ($picks[$i]["awayTeam"] . "<br><div class=\"imgDiv\"><img class=\"teamLogo\" src=\"" . 
               getIcon($picks[$i]["awayTeam"], $result["season"]) . "\" draggable=\"false\" ondragstart=\"return false;\" /></div>");
    echo "          <td id=\"mp1_" . $i . "\"" . $style . $drag . ">" . $text . "</td>\n";

    // fill in the second element
    $style = (!isset($picks[$i]) || ($picks[$i]["winner"] == $picks[$i]["awayTeam"])) 
             ? " class=\"noBorder mpMobileBGText mpMobileWipeTop\"" 
             : (($picks[$i]["winner"] == $picks[$i]["homeTeam"]) 
               ? " class=\"mpMobileGameInfo" . (($picks[$i]["canChange"] == 0) ? "Locked" : "") . " mpMobileWipeTop\"" 
               : " class=\"mpImgTD mpMobileAwayTeam" . (($picks[$i]["canChange"] == 0) ? "Locked" : "") . " mpMobileWipeTop\"");
    $drag = (isset($picks[$i]) && ($picks[$i]["canChange"] != 0) && 
             (($picks[$i]["winner"] == "") || (($picks[$i]["type"] != "winner") && ($picks[$i]["winner"] == "TIE")))) 
            ? " onClick=\"SetWinnerMobile(this.id.slice(this.id.indexOf('_') + 1), false);\"" 
            : "";
    $text = (!isset($picks[$i]) || ($picks[$i]["winner"] == $picks[$i]["awayTeam"])) 
            ? (($picks[$i]["points"] == 0) ? "" : "--->")
            : (($picks[$i]["winner"] == $picks[$i]["homeTeam"]) 
              ? (($picks[$i]["type"] == "winner") 
                ? formatTime($picks[$i]) 
                : "<button class=\"bigButton\" onClick=\"SetWinnerMobile(parentElement.id.slice(parentElement.id.indexOf('_') + 1), 'TIE');" . 
                  " return false;\">TIE</button>")
              : ($picks[$i]["awayTeam"] . "<br><div class=\"imgDiv\"><img class=\"teamLogo\" src=\"" . 
                 getIcon($picks[$i]["awayTeam"], $result["season"]) . "\" draggable=\"false\" ondragstart=\"return false;\" /></div>"));
    echo "          <td id=\"mp2_" . $i . "\"" . $style . $drag . ">" . $text . "</td>\n";

    // fill in the third element
    $style = (!isset($picks[$i]) || ($picks[$i]["canChange"] == 0))
             ? " class=\"mpImgTD mpLockedSelection\""
             : ((($picks[$i]["winner"] == $picks[$i]["awayTeam"]) || ($picks[$i]["winner"] == $picks[$i]["homeTeam"]) || 
                (($picks[$i]["type"] != "winner") && ($picks[$i]["winner"] == "TIE")))
               ? " class=\"mpImgTD mpValidSelection\"" 
               : " class=\"mpImgTD mpInvalidSelection\"" );
    if( $saveButtonEnabled )
    {
      $saveButtonEnabled = ($style == " class=\"mpInvalidSelection\"");
    }
    $text = (!isset($picks[$i])
            ? "Bye Week"
            : (($picks[$i]["winner"] == $picks[$i]["awayTeam"])
              ? ($picks[$i]["awayTeam"] . "<br><div class=\"imgDiv\"><img class=\"teamLogo\" src=\"" . 
                 getIcon($picks[$i]["awayTeam"], $result["season"]) . "\" draggable=\"false\" ondragstart=\"return false;\" /></div>")
              : (($picks[$i]["winner"] == $picks[$i]["homeTeam"]) 
                ? ($picks[$i]["homeTeam"] . "<br><div class=\"imgDiv\"><img class=\"teamLogo\" src=\"" . 
                   getIcon($picks[$i]["homeTeam"], $result["season"]) . "\" draggable=\"false\" ondragstart=\"return false;\" /></div>")
                : (($picks[$i]["type"] == "winner") 
                  ? formatTime($picks[$i]) 
                  : ("<br><button class=\"bigButton\" onClick=\"SetWinnerMobile(parentElement.id.slice(parentElement.id.indexOf('_') + 1), 'TIE');" . 
                     " return false;\">TIE</button><br>")))));
    echo "          <td id=\"mp3_" . $i . "\"" . $style . ">" . $text . "</td>\n";

    // fill in the fourth element
    $style = (!isset($picks[$i]) || ($picks[$i]["winner"] == $picks[$i]["homeTeam"])) 
             ? " class=\"noBorder mpMobileBGText mpMobileWipeTop\"" 
             : (($picks[$i]["winner"] == $picks[$i]["awayTeam"]) 
               ? " class=\"mpMobileGameInfo" . (($picks[$i]["canChange"] == 0) ? "Locked" : "") . " mpMobileWipeTop\"" 
               : " class=\"mpImgTD mpMobileHomeTeam" . (($picks[$i]["canChange"] == 0) ? "Locked" : "") . " mpMobileWipeTop\"");
    $drag = (isset($picks[$i]) && ($picks[$i]["canChange"] != 0) && 
             (($picks[$i]["winner"] == "") || (($picks[$i]["type"] != "winner") && ($picks[$i]["winner"] == "TIE")))) 
            ? " onClick=\"SetWinnerMobile(this.id.slice(this.id.indexOf('_') + 1), true);\"" 
            : "";
    $text = (!isset($picks[$i]) || ($picks[$i]["winner"] == $picks[$i]["homeTeam"])) 
            ? (($picks[$i]["points"] == 0) ? "" : "<---")
            : (($picks[$i]["winner"] == $picks[$i]["awayTeam"]) 
              ? (($picks[$i]["type"] == "winner") 
                ? formatTime($picks[$i]) 
                : "<button class=\"bigButton\" onClick=\"SetWinnerMobile(parentElement.id.slice(parentElement.id.indexOf('_') + 1), 'TIE');" . 
                  " return false;\">TIE</button>")
              : ($picks[$i]["homeTeam"] . "<br><div class=\"imgDiv\"><img class=\"teamLogo\" src=\"" . 
                 getIcon($picks[$i]["homeTeam"], $result["season"]) . "\" draggable=\"false\" ondragstart=\"return false;\" /></div>"));
    echo "          <td id=\"mp4_" . $i . "\"" . $style . $drag . ">" . $text . "</td>\n";

    // fill in the fifth element
    $style = (!isset($picks[$i]) || ($picks[$i]["winner"] != $picks[$i]["awayTeam"])) 
             ? " class=\"noBorder mpMobileBGText\" style=\"text-align:left;\"" 
             : " class=\"mpImgTD mpMobileHomeTeam" . (($picks[$i]["canChange"] == 0) ? "Locked" : "") . " mpMobileWipeTop\"";
    $drag = (isset($picks[$i]) && ($picks[$i]["winner"] == $picks[$i]["awayTeam"]) && ($picks[$i]["canChange"] != 0)) 
            ? " onClick=\"SetWinnerMobile(this.id.slice(this.id.indexOf('_') + 1), true);\"" 
            : "";
    $text = (!isset($picks[$i]) || ($picks[$i]["winner"] != $picks[$i]["awayTeam"])) 
            ? (($picks[$i]["points"] == 0) ? "" : $picks[$i]["points"])
            : ($picks[$i]["homeTeam"] . "<br><div class=\"imgDiv\"><img class=\"teamLogo\" src=\"" . 
               getIcon($picks[$i]["homeTeam"], $result["season"]) . "\" draggable=\"false\" ondragstart=\"return false;\" /></div>");
    echo "          <td id=\"mp5_" . $i . "\"" . $style . $drag . ">" . $text . "</td>\n";

    echo "        </tr>\n";
  }
?>
              <tr><td class="noBorder" colspan=5>&nbsp;</td></tr>
              <tr>
                <td class="noBorder" colspan=5 style="text-align:center; font-size:30px;">Tiebreaker</td>
              </tr>
              <tr style="height:20px"><td class="noBorder">&nbsp;</td></tr>
<?php
  $games = RunQuery( "select homeTeam, awayTeam from Game where weekNumber=" . $result["weekNumber"] . 
                     " and season=" . $result["season"] . " order by gameTime desc" );
  $tieBreakers = RunQuery( "select tieBreaker1 from PlayoffResult join Session using (userID) where sessionID=" . $_SESSION["spsID"] . 
                           " and weekNumber=" . $result["weekNumber"] . " and season=" . $result["season"] );
  foreach( $games as $thisGame )
  {
    echo "              <tr>\n";
    echo "                <td class=\"noBorder\">&nbsp;</td>\n"; 
    echo "                <td class=\"noBorder\" style=\"font-size:30px;\">" . $thisGame["awayTeam"] . 
        "<br><div class=\"imgDiv\"><img class=\"teamLogo\" src=\"" .  getIcon($thisGame["awayTeam"], $result["season"]) . 
        "\" draggable=\"false\" ondragstart=\"return false;\" /></div></td>\n";
    echo "                <td class=\"noBorder\" style=\"font-size:40px;\">@</td>\n";
    echo "                <td class=\"noBorder\" style=\"font-size:30px;\">" . $thisGame["homeTeam"] . 
        "<br><div class=\"imgDiv\"><img class=\"teamLogo\" src=\"" . getIcon($thisGame["homeTeam"], $result["season"]) . 
        "\" draggable=\"false\" ondragstart=\"return false;\" /></div></td>\n";
    echo "                <td class=\"noBorder\">&nbsp;</td>\n"; 
    echo "              </tr>\n";
    echo "              <tr>\n";
    echo "                <td class=\"noBorder\" colspan=5>\n";
    echo "                  <span style=\"font-size:30px;\">Combined Super Bowl score</span>\n";
    echo "                  <input id=\"tieBreak\" name=\"tieBreak\" type=\"text\" maxlength=\"3\" " . 
        "onKeyUp=\"NumbersOnly('tieBreak'); ToggleSaveButtonMobile();\" value=\"" . $tieBreakers[0]["tieBreaker1"] . 
        "\" style=\"width:60px; font-size:30px;\" />\n";  
    echo "              </tr>\n";
    echo "              <tr style=\"height:30px\"><td class=\"noBorder\">&nbsp;</td></tr>\n";
  }
?>
        <tr style="height:75px;">
          <td class="noBorder" colspan="5">
            <form action="." method="post" id="makePicksForm">
              <input type="hidden" id="windowScrollPos" name="windowScrollPos" value="0">
<?php
  echo "              <input type=\"hidden\" id=\"picksType\" name=\"picksType\" value=\"superBowl\">\n";
  echo "              <input type=\"hidden\" id=\"tieBreakReal\" name=\"tieBreak\" value=\"\">\n";
  for( $i=1; $i<11; $i++ )
  {
    if( isset($picks[$i]) && ($picks[$i]["canChange"] != 0) )
    {
      echo "              <input type=\"hidden\" id=\"pickType" . $i . "\" name=\"pickType" . $i . "\" value=\"" . 
          $picks[$i]["type"] . "\">\n";
      echo "              <input type=\"hidden\" id=\"winner" . $i . "\" name=\"winner" . $i . "\" value=\"\">\n";
    }
  }
?>
              <button id="saveRosterButton" style="font-size:48px;" class="bigButton" onclick="document.getElementById('windowScrollPos').value = $(window).scrollTop(); showWarning = false; document.getElementById('makePicksForm').submit();"<?php 
  echo $saveButtonEnabled ? "" : " disabled"; ?>>Save Picks</button>
            </form>
          </td>
        </tr>
      </table>
