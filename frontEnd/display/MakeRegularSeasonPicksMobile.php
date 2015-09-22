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
      <span style="font-size: 30px">Making Picks for Week <?php echo $result["weekNumber"]; ?>
        <button class="bigButton" onClick="PickAllAwayTeamsMobile()">All Away Teams</button>
        <button class="bigButton" onClick="PickAllHomeTeamsMobile()">All Home Teams</button>
      </span>
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
    if( $MNFgame == null || $MNFgame["gameTime"] < $thisPick["gameTime"] )
    {
      $MNFgame = $thisPick;
    }
  }

  // grab the teams on bye
  $byes = array();
  $byeCount = 0;
  $byeResults = RunQuery( "select teamID from Team where teamID not in (" . $playedTeams . ")");
  foreach( $byeResults as $thisBye )
  {
    $byes[count($byes)] = $thisBye["teamID"];
  }

  // fill in the rows
  $saveButtonEnabled = true;
  for($i=1; $i<17; $i++)
  {
    echo "        <tr style=\"height:75px; font-size:24px;\" class=\"montserrat\">\n";

    // fill in the first element
    $style = (!isset($picks[$i]) || ($picks[$i]["winner"] != $picks[$i]["homeTeam"])) 
             ? " class=\"noBorder mpMobileBGText\" style=\"text-align: right;\"" 
             : " class=\"mpImgTD mpMobileAwayTeam" . (($picks[$i]["canChange"] == 0) ? "Locked" : "") . "\"";
    $drag = (isset($picks[$i]) && ($picks[$i]["winner"] == $picks[$i]["homeTeam"]) && ($picks[$i]["canChange"] != 0)) 
            ? " onClick=\"SetWinnerMobile(this.id.slice(this.id.indexOf('_') + 1), false);\"" 
            : "";
    $text = (!isset($picks[$i]) || ($picks[$i]["winner"] != $picks[$i]["homeTeam"])) 
            ? (17 - $i)
            : ($picks[$i]["awayTeam"] . "<br><div class=\"imgDiv\"><img class=\"teamLogo\" src=\"" . 
               getIcon($picks[$i]["awayTeam"], $result["season"]) . "\" draggable=\"false\" ondragstart=\"return false;\" /></div>");
    echo "          <td id=\"mp1_" . $i . "\"" . $style . $drag . ">" . $text . "</td>\n";

    // fill in the second element
    $style = (!isset($picks[$i]) || ($picks[$i]["winner"] == $picks[$i]["awayTeam"])) 
             ? " class=\"noBorder mpMobileBGText\"" 
             : (($picks[$i]["winner"] == $picks[$i]["homeTeam"]) 
               ? " class=\"mpMobileGameInfo" . (($picks[$i]["canChange"] == 0) ? "Locked" : "") . "\"" 
               : " class=\"mpImgTD mpMobileAwayTeam" . (($picks[$i]["canChange"] == 0) ? "Locked" : "") . "\"");
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
                formatTime($picks[$i]) . 
                (($picks[$i]["canChange"] == 1) 
                ? ("<br><button onClick=\"MoveRowMobile(parentElement.id.slice(parentElement.id.indexOf('_') + 1), 1);\" " .
                   "class=\"bigButton\">Move Down</button>") 
                : ""))
              : ($picks[$i]["awayTeam"] . "<br><div class=\"imgDiv\"><img class=\"teamLogo\" src=\"" . 
                 getIcon($picks[$i]["awayTeam"], $result["season"]) . "\" draggable=\"false\" ondragstart=\"return false;\" /></div>"));
    // special case for teams on bye
    if( !isset($picks[$i]) )
    {
      $style = " class=\"mpImgTD mpAwayTeamLocked\"";
      $text = $byes[$byeCount] . "<br><div class=\"imgDiv\"><img class=\"teamLogo\" src=\"" . getIcon($byes[$byeCount], $result["season"]) . 
              "\" draggable=\"false\" ondragstart=\"return false;\" /></div>";
      $byeCount++;
    }
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
                  formatTime($picks[$i]) . 
                  (($picks[$i]["canChange"] == 1) 
                  ? ("<br><button onClick=\"MoveRowMobile(parentElement.id.slice(parentElement.id.indexOf('_') + 1), 1);\" " .
                     "class=\"bigButton\">Move Down</button>") 
                  : "")))));
    echo "          <td id=\"mp3_" . $i . "\"" . $style . ">" . $text . "</td>\n";

    // fill in the fourth element
    $style = (!isset($picks[$i]) || ($picks[$i]["winner"] == $picks[$i]["homeTeam"])) 
             ? " class=\"noBorder mpMobileBGText\"" 
             : (($picks[$i]["winner"] == $picks[$i]["awayTeam"]) 
               ? " class=\"mpMobileGameInfo" . (($picks[$i]["canChange"] == 0) ? "Locked" : "") . "\"" 
               : " class=\"mpImgTD mpMobileHomeTeam" . (($picks[$i]["canChange"] == 0) ? "Locked" : "") . "\"");
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
                formatTime($picks[$i]) . 
                (($picks[$i]["canChange"] == 1) 
                ? ("<br><button onClick=\"MoveRowMobile(parentElement.id.slice(parentElement.id.indexOf('_') + 1), 1);\" " .
                   "class=\"bigButton\">Move Down</button>") 
                : ""))
              : ($picks[$i]["homeTeam"] . "<br><div class=\"imgDiv\"><img class=\"teamLogo\" src=\"" . 
                 getIcon($picks[$i]["homeTeam"], $result["season"]) . "\" draggable=\"false\" ondragstart=\"return false;\" /></div>"));
    // special case for teams on bye
    if( !isset($picks[$i]) )
    {
      $style = " class=\"mpImgTD mpHomeTeamLocked\"";
      $text = $byes[$byeCount] . "<br><div class=\"imgDiv\"><img class=\"teamLogo\" src=\"" . getIcon($byes[$byeCount], $result["season"]) . 
              "\" draggable=\"false\" ondragstart=\"return false;\" /></div>";
      $byeCount++;
    }
    echo "          <td id=\"mp4_" . $i . "\"" . $style . $drag . ">" . $text . "</td>\n";

    // fill in the fifth element
    $style = (!isset($picks[$i]) || ($picks[$i]["winner"] != $picks[$i]["awayTeam"])) 
             ? " class=\"noBorder mpMobileBGText\" style=\"text-align:left;\"" 
             : " class=\"mpImgTD mpMobileHomeTeam" . (($picks[$i]["canChange"] == 0) ? "Locked" : "") . "\"";
    $drag = (isset($picks[$i]) && ($picks[$i]["winner"] == $picks[$i]["awayTeam"]) && ($picks[$i]["canChange"] != 0)) 
            ? " onClick=\"SetWinnerMobile(this.id.slice(this.id.indexOf('_') + 1), true);\"" 
            : "";
    $text = (!isset($picks[$i]) || ($picks[$i]["winner"] != $picks[$i]["awayTeam"])) 
            ? (17 - $i)
            : ($picks[$i]["homeTeam"] . "<br><div class=\"imgDiv\"><img class=\"teamLogo\" src=\"" . 
               getIcon($picks[$i]["homeTeam"], $result["season"]) . "\" draggable=\"false\" ondragstart=\"return false;\" /></div>");
    echo "          <td id=\"mp5_" . $i . "\"" . $style . $drag . ">" . $text . "</td>\n";

    echo "        </tr>\n";
  }
?>
        <tr style="height:75px;">
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
              <br/>
              <div>
                <span style="font-size: 30px;"><?php echo ($MNFgame["awayTeam"] . " @ " . $MNFgame["homeTeam"]); ?> Combined Score</span>
                <input id="tieBreak" name="tieBreak" type="text" maxlength="3" onKeyUp="NumbersOnly(); ToggleSaveButtonMobile();" value="<?php
  echo ($TBresult[0]["tieBreaker"] != 0) ? $TBresult[0]["tieBreaker"] : "";
?>" style="width:60px; font-size: 30px;" />
              </div>
              <br/>
              <button id="saveRosterButton" style="font-size:48px;" class="bigButton" onclick="document.getElementById('windowScrollPos').value = $(window).scrollTop(); showWarning = false; document.getElementById('makePicksForm').submit();"<?php 
  echo $saveButtonEnabled ? "" : " disabled"; ?>>Save Picks</button>
            </form>
          </td>
        </tr>
      </table>
