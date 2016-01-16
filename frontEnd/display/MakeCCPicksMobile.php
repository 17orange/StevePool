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
      <span>Making Picks for Conference Championship</span>
      <button class="bigButton" onClick="PickAllHomeTeamsMobile()">All Home Teams</button>
      <button class="bigButton" onClick="PickAllAwayTeamsMobile()">All Away Teams</button>
      <br/>
      <table style="width:100%; border-spacing:0px; text-align:center; font-size: 14px;">
        <tr><td class="noBorder" colspan=6>&nbsp;</td></tr>
        <tr>
          <td class="noBorder" colspan=2>&nbsp;</td>
          <td class="noBorder" colspan=1 style="text-align:center; font-size: 30px;">Winner</td>
          <td class="noBorder" colspan=2>&nbsp;</td>
        </tr>
        <tr><td class="noBorder" colspan=5>&nbsp;</td></tr>
<?php
  // grab their picks for this week
  $pickResults = RunQuery( "select gameID, points, winner, gameTime, homeTeam, awayTeam, lockTime > now() as canChange, " . 
                           "status, timeLeft, homeScore, awayScore, type from Pick join Game using (gameID) " . 
                           "join Session using (userID) where sessionID=" . $_SESSION["spsID"] . " and weekNumber=" . 
                           $result["weekNumber"] . " and season=" . $result["season"], false );
  $picks = array();
  foreach( $pickResults as $thisPick )
  {
    $picks[5 - $thisPick["points"]] = $thisPick;
  }

  // fill in the rows
  $saveButtonEnabled = true;
  for($i=1; $i<5; $i++)
  {
    // show the pick type header
    echo "        <tr style=\"font-size:24px;\" class=\"montserrat\">\n";
    $offset = (isset($picks[$i]) && ($picks[$i]["winner"] == $picks[$i]["homeTeam"])) ? 1 : 
              ((isset($picks[$i]) && ($picks[$i]["winner"] == $picks[$i]["awayTeam"])) ? 3 : 2);
    for( $j=1; $j<4; $j++ ) {
      echo "          <td id=\"mpH" . $j . "_" . $i . "\" class=\"" . (($offset == $j) ? "mpMobilePickType" : "noBorder") . "\" colspan=\"" . 
          (($offset == $j) ? "3" : 1) . "\">" . (($offset == $j) ? (($picks[$i]["type"] == "winner") ? "Final" : "Halftime") : "") . "</td>\n";
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
            ? (5 - $i)
            : ($picks[$i]["awayTeam"] . "<br><div class=\"imgDiv\"><img class=\"teamLogo\" src=\"" . 
               getIcon($picks[$i]["awayTeam"], $result["season"]) . "\" draggable=\"false\" ondragstart=\"return false;\" /></div>");
    echo "          <td id=\"mp1_" . $i . "\"" . $style . $drag . ">" . $text . "</td>\n";

    // fill in the second element
    $style = (!isset($picks[$i]) || ($picks[$i]["winner"] == $picks[$i]["awayTeam"])) 
             ? " class=\"noBorder mpMobileBGText mpMobileWipeTop\"" 
             : (($picks[$i]["winner"] == $picks[$i]["homeTeam"]) 
               ? " class=\"mpMobileGameInfo" . (($picks[$i]["canChange"] == 0) ? "Locked" : "") . " mpMobileWipeTop\"" 
               : " class=\"mpImgTD mpMobileAwayTeam" . (($picks[$i]["canChange"] == 0) ? "Locked" : "") . " mpMobileWipeTop\"");
    $drag = (isset($picks[$i]) && ($picks[$i]["winner"] == "") && ($picks[$i]["canChange"] != 0)) 
            ? " onClick=\"SetWinnerMobile(this.id.slice(this.id.indexOf('_') + 1), false);\"" 
            : "";
    $text = (!isset($picks[$i]) || ($picks[$i]["winner"] == $picks[$i]["awayTeam"])) 
            ? "--->"
            : (($picks[$i]["winner"] == $picks[$i]["homeTeam"]) 
              ? ((($picks[$i]["canChange"] == 1) 
                ? ("<button onClick=\"MoveRowMobile(parentElement.id.slice(parentElement.id.indexOf('_') + 1), -1);\" " .
                   "class=\"bigButton\">Move Up</button><br>") 
                : "") . 
              (($picks[$i]["type"] == "winner") ? formatTime($picks[$i]) : "TIE<br>Halftime<br>&nbsp;") . 
              (($picks[$i]["canChange"] == 1) 
                ? ("<br><button onClick=\"MoveRowMobile(parentElement.id.slice(parentElement.id.indexOf('_') + 1), 1);\" " .
                   "class=\"bigButton\">Move Down</button>") 
                : ""))
              : ($picks[$i]["awayTeam"] . "<br><div class=\"imgDiv\"><img class=\"teamLogo\" src=\"" . 
                 getIcon($picks[$i]["awayTeam"], $result["season"]) . "\" draggable=\"false\" ondragstart=\"return false;\" /></div>"));
    echo "          <td id=\"mp2_" . $i . "\"" . $style . $drag . ">" . $text . "</td>\n";

    // fill in the third element
    $style = (!isset($picks[$i]) || ($picks[$i]["canChange"] == 0))
             ? " class=\"mpImgTD mpLockedSelection\""
             : (($picks[$i]["winner"] == $picks[$i]["awayTeam"]) || ($picks[$i]["winner"] == $picks[$i]["homeTeam"])
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
                : ((($picks[$i]["canChange"] == 1) 
                    ? ("<button onClick=\"MoveRowMobile(parentElement.id.slice(parentElement.id.indexOf('_') + 1), -1);\" " .
                       "class=\"bigButton\">Move Up</button><br>") 
                    : "") . 
                  (($picks[$i]["type"] == "winner") 
                    ? formatTime($picks[$i]) 
                    : ("<br><button class=\"bigButton\" onClick=\"SetWinnerMobile(parentElement.id.slice(parentElement.id.indexOf('_') + 1), 'TIE');" . 
                       " return false;\">TIE</button><br>")) . 
                  (($picks[$i]["canChange"] == 1) 
                    ? ("<br><button onClick=\"MoveRowMobile(parentElement.id.slice(parentElement.id.indexOf('_') + 1), 1);\" " .
                       "class=\"bigButton\">Move Down</button>") 
                    : "")))));
    echo "          <td id=\"mp3_" . $i . "\"" . $style . ">" . $text . "</td>\n";

    // fill in the fourth element
    $style = (!isset($picks[$i]) || ($picks[$i]["winner"] == $picks[$i]["homeTeam"])) 
             ? " class=\"noBorder mpMobileBGText mpMobileWipeTop\"" 
             : (($picks[$i]["winner"] == $picks[$i]["awayTeam"]) 
               ? " class=\"mpMobileGameInfo" . (($picks[$i]["canChange"] == 0) ? "Locked" : "") . " mpMobileWipeTop\"" 
               : " class=\"mpImgTD mpMobileHomeTeam" . (($picks[$i]["canChange"] == 0) ? "Locked" : "") . " mpMobileWipeTop\"");
    $drag = (isset($picks[$i]) && ($picks[$i]["winner"] == "") && ($picks[$i]["canChange"] != 0)) 
            ? " onClick=\"SetWinnerMobile(this.id.slice(this.id.indexOf('_') + 1), true);\"" 
            : "";
    $text = (!isset($picks[$i]) || ($picks[$i]["winner"] == $picks[$i]["homeTeam"])) 
            ? "<---"
            : (($picks[$i]["winner"] == $picks[$i]["awayTeam"]) 
              ? ((($picks[$i]["canChange"] == 1) 
                ? ("<button onClick=\"MoveRowMobile(parentElement.id.slice(parentElement.id.indexOf('_') + 1), -1);\" " .
                   "class=\"bigButton\">Move Up</button><br>") 
                : "") . 
              (($picks[$i]["type"] == "winner") ? formatTime($picks[$i]) : "TIE<br>Halftime<br>&nbsp;") . 
              (($picks[$i]["canChange"] == 1) 
                ? ("<br><button onClick=\"MoveRowMobile(parentElement.id.slice(parentElement.id.indexOf('_') + 1), 1);\" " .
                   "class=\"bigButton\">Move Down</button>") 
                : ""))
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
            ? (5 - $i)
            : ($picks[$i]["homeTeam"] . "<br><div class=\"imgDiv\"><img class=\"teamLogo\" src=\"" . 
               getIcon($picks[$i]["homeTeam"], $result["season"]) . "\" draggable=\"false\" ondragstart=\"return false;\" /></div>");
    echo "          <td id=\"mp5_" . $i . "\"" . $style . $drag . ">" . $text . "</td>\n";

    echo "        </tr>\n";
  }
?>
              <tr><td class="noBorder" colspan=5>&nbsp;</td></tr>
              <tr>
                <td class="noBorder" colspan=5 style="text-align:center; font-size:30px;">Tiebreakers</td>
              </tr>
              <tr style="height:20px"><td class="noBorder">&nbsp;</td></tr>
<?php
  $games = RunQuery( "select homeTeam, awayTeam from Game where weekNumber=" . $result["weekNumber"] . 
                     " and season=" . $result["season"] . " order by gameTime desc" );
  $tieBreakers = RunQuery( "select tieBreaker1, tieBreaker2, tieBreaker3, tieBreaker4 from PlayoffResult " . 
                           "join Session using (userID) where sessionID=" . $_SESSION["spsID"] . 
                           " and weekNumber=" . $result["weekNumber"] . " and season=" . $result["season"] );
  $count = 1;
  $halftimeTBs = "";
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
    echo "                  <span style=\"font-size:30px;\">Combined final score</span>\n";
    echo "                  <input id=\"tieBreak" . $count . "\" name=\"tieBreak" . $count . "\" type=\"text\" maxlength=\"3\" " . 
        "onKeyUp=\"NumbersOnly('tieBreak" . $count . "'); ToggleSaveButtonMobile();\" value=\"" . $tieBreakers[0]["tieBreaker" . $count] . 
        "\" style=\"width:60px; font-size:30px;\" />\n";  
    echo "              </tr>\n";
    echo "              <tr style=\"height:30px\"><td class=\"noBorder\">&nbsp;</td></tr>\n";

    $halftimeTBs .= "              <tr>\n";
    $halftimeTBs .= "                <td class=\"noBorder\">&nbsp;</td>\n"; 
    $halftimeTBs .= "                <td class=\"noBorder\" style=\"font-size:30px;\">" . $thisGame["awayTeam"] . 
                    "<br><div class=\"imgDiv\"><img class=\"teamLogo\" src=\"" .  getIcon($thisGame["awayTeam"], $result["season"]) . 
                    "\" draggable=\"false\" ondragstart=\"return false;\" /></div></td>\n";
    $halftimeTBs .= "                <td class=\"noBorder\" style=\"font-size:40px;\">@</td>\n";
    $halftimeTBs .= "                <td class=\"noBorder\" style=\"font-size:30px;\">" . $thisGame["homeTeam"] . 
                    "<br><div class=\"imgDiv\"><img class=\"teamLogo\" src=\"" . getIcon($thisGame["homeTeam"], $result["season"]) . 
                    "\" draggable=\"false\" ondragstart=\"return false;\" /></div></td>\n";
    $halftimeTBs .= "                <td class=\"noBorder\">&nbsp;</td>\n"; 
    $halftimeTBs .= "              </tr>\n";
    $halftimeTBs .= "              <tr>\n";
    $halftimeTBs .= "                <td class=\"noBorder\" colspan=5>\n";
    $halftimeTBs .= "                  <span style=\"font-size:30px;\">Combined halftime score</span>\n";
    $halftimeTBs .= "                  <input id=\"tieBreak" . ($count + 2) . "\" name=\"tieBreak" . $count . "\" type=\"text\" " . 
                    "maxlength=\"3\" onKeyUp=\"NumbersOnly('tieBreak" . ($count + 2) . "'); ToggleSaveButtonMobile();\" value=\"" . 
                    $tieBreakers[0]["tieBreaker" . ($count + 2)] . "\" style=\"width:60px; font-size:30px;\" />\n";  
    $halftimeTBs .= "              </tr>\n";
    $halftimeTBs .= "              <tr style=\"height:30px\"><td class=\"noBorder\">&nbsp;</td></tr>\n";
    $count++;
  }
  echo $halftimeTBs;
?>
        <tr style="height:75px;">
          <td class="noBorder" colspan="5">
            <form action="." method="post" id="makePicksForm">
              <input type="hidden" id="windowScrollPos" name="windowScrollPos" value="0">
<?php
  echo "              <input type=\"hidden\" id=\"picksType\" name=\"picksType\" value=\"conference\">\n";
  for( $i=1; $i<5; $i++ )
  {
    if( isset($picks[$i]) && ($picks[$i]["canChange"] != 0) )
    {
      echo "              <input type=\"hidden\" id=\"game" . $i . "\" name=\"game" . $i . "\" value=\"" . 
          $picks[$i]["gameID"] . "\">\n";
      echo "              <input type=\"hidden\" id=\"pickType" . $i . "\" name=\"pickType" . $i . "\" value=\"" . 
          $picks[$i]["type"] . "\">\n";
      echo "              <input type=\"hidden\" id=\"homeTeam" . $i . "\" name=\"homeTeam" . $i . "\" value=\"" . 
          $picks[$i]["homeTeam"] . "\">\n";
      echo "              <input type=\"hidden\" id=\"awayTeam" . $i . "\" name=\"awayTeam" . $i . "\" value=\"" . 
          $picks[$i]["awayTeam"] . "\">\n";
      echo "              <input type=\"hidden\" id=\"pts" . $i . "\" name=\"pts" . $i . "\" value=\"" . (5 - $i) . "\">\n";
      echo "              <input type=\"hidden\" id=\"winner" . $i . "\" name=\"winner" . $i . "\" value=\"\">\n";
      echo "              <input type=\"hidden\" id=\"tb" . $i . "\" name=\"tb" . $i . "\" value=\"\">\n";
    }
  }
?>
              <button id="saveRosterButton" style="font-size:48px;" class="bigButton" onclick="document.getElementById('windowScrollPos').value = $(window).scrollTop(); showWarning = false; document.getElementById('makePicksForm').submit();"<?php 
  echo $saveButtonEnabled ? "" : " disabled"; ?>>Save Picks</button>
            </form>
          </td>
        </tr>
      </table>
