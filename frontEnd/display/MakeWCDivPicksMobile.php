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
      <span>Making Picks for <?php echo (($result["weekNumber"] == 19) ? "Wild Card" : "Divisional"); ?> Round</span>
      <form action="helpers/changeAccountDetails.php" method="post" id="cbmForm" target="taskWindow" style="display:inline-block;margin-left:200px">
        <input type="hidden" name="task" value="cbm" />
        <input type="hidden" name="acctCBM" id="cbmVal" value="<?php echo (($_SESSION["cbm"] ?? "N") == "Y") ? "N" : "Y"; ?>" />
        <button onClick="$('#cbmForm').submit();">Alternate Colors</button>
      </form>
      <br/>
      <table style="width:100%; border-spacing:0px; text-align:center; font-size: 14px;">
        <tr>
          <td class="noBorder">
            <table style="width:100%; border-spacing:0px; text-align:center; font-size: 14px">
              <tr><td class="noBorder" colspan=6>&nbsp;</td></tr>
              <tr>
                <td class="noBorder" colspan=8 style="text-align:center; font-size: 20px;"><table style="width:100%"><tr>
                  <td class="noBorder warningZone" style="font-size: 20px">&nbsp;</td>
                  <td class="noBorder" style="text-align:center; font-size: 20px;"><?php echo (($result["weekNumber"] == 19) ? "Wild Card" : "Divisional"); ?> Games</td>
                  <td class="noBorder warningZone" style="font-size: 20px">&nbsp;</td>
                </tr></table></td>
              </tr>
              <tr><td class="noBorder" colspan=6 id="pointsLeft"></td></tr>
              <tr><td class="noBorder" colspan=6>&nbsp;</td></tr>
<?php
  // grab their picks for this week
  $pickResults = RunQuery( "select gameID, points, winner, tieBreakOrder, gameTime, homeTeam, awayTeam, lockTime > now() as canChange, " . 
                           "status, timeLeft, homeScore, awayScore from Pick join Game using (gameID) " . 
                           "join Session using (userID) where sessionID=" . $_SESSION["spsID"] . " and weekNumber=" . 
                           $result["weekNumber"] . " and season=" . $result["season"] . " order by tieBreakOrder, gameTime", false );
  $picks = array();
  foreach( $pickResults as $thisPick )
  {
    $picks[1 + count($picks)] = $thisPick;
  }

  // slider guts
  $sliderCount = (($result["weekNumber"] == 19) ? 6 : 4);
  $totalPoints = (($result["weekNumber"] == 19) ? 30 : 20);
  echo "              <tr style=\"display:none\"><td>\n";
  for($i=0; $i<=$sliderCount; $i++)
  {
    echo "<div id=\"sliderHandle" . $i . "\">";
    echo "<div class=\"handleGuts\"></div>\n";
    echo "</div>";
  }
  echo "              </td></tr>\n";

  // fill in the rows
?>
  <tr>
    <td class="noBorder" style="min-width:68px">&nbsp;</td>
    <td class="noBorder" style="min-width:68px">&nbsp;</td>
    <td class="noBorder" style="min-width:78px; font-size:20px">Winner</td>
    <td class="noBorder" style="min-width:68px">&nbsp;</td>
    <td class="noBorder" style="min-width:68px">&nbsp;</td>
    <td class="noBorder" colspan="2" style="min-width:68px; font-size:20px">Confidence Points</td>
  </tr>
<?php
  for($i=1; $i<=$sliderCount; $i++) {
?>
  <tr style="height:75px" class="montserrat mobileRow">
<?php
    // fill in first box
    $style = (!isset($picks[$i]) || ($picks[$i]["winner"] != $picks[$i]["homeTeam"])) 
             ? " class=\"noBorder\"" 
             : " class=\"mpImgTD mpWCDivAwayTeam" . (($picks[$i]["canChange"] == 0) ? "Locked" : "") . "\"";
    $drag = (isset($picks[$i]) && ($picks[$i]["canChange"] != 0)) ? " onMouseDown=\"startDrag(1, " . $i . ");\"" : "";
    $text = (!isset($picks[$i]) || ($picks[$i]["winner"] != $picks[$i]["homeTeam"])) 
            ? ""
            : ($teamAliases[$picks[$i]["awayTeam"]] . " <br><div class=\"imgDiv\"><img class=\"teamLogo\" src=\"" . 
               getIcon($picks[$i]["awayTeam"], $result["season"]) . "\" draggable=\"false\" ondragstart=\"return false;\" /></div>");
    echo "                <td id=\"mp1_" . $i . "\"" . $style . $drag . ">" . $text . "</td>\n";

    // fill in second box
    $style = (!isset($picks[$i]) || ($picks[$i]["winner"] == $picks[$i]["awayTeam"])) 
             ? " class=\"noBorder\"" 
             : (($picks[$i]["winner"] == $picks[$i]["homeTeam"]) 
               ? " class=\"mpWCDivGameInfo" . (($picks[$i]["canChange"] == 0) ? "Locked" : "") . "\"" 
               : " class=\"mpImgTD mpWCDivAwayTeam" . (($picks[$i]["canChange"] == 0) ? "Locked" : "") . "\"");
    $drag = (isset($picks[$i]) && ($picks[$i]["canChange"] != 0)) ? " onMouseDown=\"startDrag(2, " . $i . ");\"" : "";
    $text = (!isset($picks[$i]) || ($picks[$i]["winner"] == $picks[$i]["awayTeam"])) 
            ? ""
            : (($picks[$i]["winner"] == $picks[$i]["homeTeam"]) 
              ? formatTime($picks[$i]) 
              : ($teamAliases[$picks[$i]["awayTeam"]] . " <br><div class=\"imgDiv\"><img class=\"teamLogo\" src=\"" . 
                 getIcon($picks[$i]["awayTeam"], $result["season"]) . "\" draggable=\"false\" ondragstart=\"return false;\" /></div>"));
    echo "                <td id=\"mp2_" . $i . "\"" . $style . $drag . ">" . $text . "</td>\n";

    // fill in third box
    $style = (!isset($picks[$i]) || ($picks[$i]["canChange"] == 0))
             ? " class=\"mpImgTD mpLockedSelection\""
             : (($picks[$i]["winner"] == $picks[$i]["awayTeam"]) || ($picks[$i]["winner"] == $picks[$i]["homeTeam"])
               ? " class=\"mpImgTD mpValidSelection" . ($_SESSION["cbm"] ? " CBM" : "") .  "\""
               : " class=\"mpImgTD mpInvalidSelection" . ($_SESSION["cbm"] ? " CBM" : "") .  "\"" );
    if( $saveButtonEnabled )
    {
      $saveButtonEnabled = ($style == " class=\"mpInvalidSelection" . ($_SESSION["cbm"] ? " CBM" : "") .  "\"");
    }
    $drag = (isset($picks[$i]) && ($picks[$i]["canChange"] != 0)) ? " onMouseDown=\"startDrag(3, " . $i . ");\"" : "";
    $text = !isset($picks[$i])
            ? "Bye Week"
            : (($picks[$i]["winner"] == $picks[$i]["awayTeam"])
              ? ($teamAliases[$picks[$i]["awayTeam"]] . " " . (5 - $i) . " <br><div class=\"imgDiv\"><img class=\"teamLogo\" src=\"" . 
                 getIcon($picks[$i]["awayTeam"], $result["season"]) . "\" draggable=\"false\" ondragstart=\"return false;\" /></div>")
              : (($picks[$i]["winner"] == $picks[$i]["homeTeam"]) 
                ? ($teamAliases[$picks[$i]["homeTeam"]] . " " . (5 - $i) . " <br><div class=\"imgDiv\"><img class=\"teamLogo\" src=\"" . 
                   getIcon($picks[$i]["homeTeam"], $result["season"]) . "\" draggable=\"false\" ondragstart=\"return false;\" /></div>")
                : formatTime($picks[$i])));
    echo "                <td id=\"mp3_" . $i . "\"" . $style . $drag . ">" . $text . "</td>\n";

    // fill in fourth box
    $style = (!isset($picks[$i]) || ($picks[$i]["winner"] == $picks[$i]["homeTeam"])) 
             ? " class=\"noBorder\"" 
             : (($picks[$i]["winner"] == $picks[$i]["awayTeam"]) 
               ? " class=\"mpWCDivGameInfo" . (($picks[$i]["canChange"] == 0) ? "Locked" : "") . "\"" 
               : " class=\"mpImgTD mpWCDivHomeTeam" . (($picks[$i]["canChange"] == 0) ? "Locked" : "") . "\"");
    $drag = (isset($picks[$i]) && ($picks[$i]["canChange"] != 0)) ? " onMouseDown=\"startDrag(4, " . $i . ");\"" : "";
    $text = (!isset($picks[$i]) || ($picks[$i]["winner"] == $picks[$i]["homeTeam"])) 
            ? ""
            : (($picks[$i]["winner"] == $picks[$i]["awayTeam"]) 
              ? formatTime($picks[$i]) 
              : ($teamAliases[$picks[$i]["homeTeam"]] . " <br><div class=\"imgDiv\"><img class=\"teamLogo\" src=\"" . 
                 getIcon($picks[$i]["homeTeam"], $result["season"]) . "\" draggable=\"false\" ondragstart=\"return false;\" /></div>"));
    echo "                <td id=\"mp4_" . $i . "\"" . $style . $drag . ">" . $text . "</td>\n";

    // fill in the fifth row
    $style = (!isset($picks[$i]) || ($picks[$i]["winner"] != $picks[$i]["awayTeam"])) 
             ? " class=\"noBorder\"" 
             : " class=\"mpImgTD mpWCDivHomeTeam" . (($picks[$i]["canChange"] == 0) ? "Locked" : "") . "\"";
    $drag = (isset($picks[$i]) && ($picks[$i]["canChange"] != 0)) ? " onMouseDown=\"startDrag(5, " . $i . ");\"" : "";
    $text = (!isset($picks[$i]) || ($picks[$i]["winner"] != $picks[$i]["awayTeam"])) 
            ? ""
            : ($teamAliases[$picks[$i]["homeTeam"]] . " <br><div class=\"imgDiv\"><img class=\"teamLogo\" src=\"" . 
               getIcon($picks[$i]["homeTeam"], $result["season"]) . "\" draggable=\"false\" ondragstart=\"return false;\" /></div>");
    echo "                <td id=\"mp5_" . $i . "\"" . $style . $drag . ">" . $text . "</td>\n";
?>
    <td class="noBorder" style="width:100%;">
      <table style="width:100%; font-size: 30px;"><tr>
        <td class="noBorder" style="min-width:32px;text-align:right">0</td>
        <td class="noBorder" style="min-width:5px">&nbsp;</td>
        <td class="noBorder sliderTD<?php echo ($_SESSION["cbm"] ? " CBM" : ""); ?>">
          <div class="sliderDummy<?php echo ($_SESSION["cbm"] ? " CBM" : ""); ?>"></div>
          <div class="sliderReal"><div class="pointSlider" id="slider<?php echo $i; ?>"><div class="sliderGood<?php echo ($_SESSION["cbm"] ? " CBM" : ""); ?>"></div></div></div>
        </td>
        <td class="noBorder" style="min-width:5px">&nbsp;</td>
        <td class="noBorder" style="width:50px"><?php echo ($totalPoints + 1 - $sliderCount);?></td>
      </tr></table>
    </td>
    <td class="noBorder" colspan=1 style="display:none; text-align:center; font-size: 20px;"><?php echo $picks[$i]["points"]?> points</td>
  </tr>
<?php
  }
?>
            </table>
          </td>
        </tr>
        <tr>
          <td class="noBorder">
            <table style="width:100%; border-spacing:0px; text-align:center; font-size: 14px;">
              <tr><td class="noBorder" colspan=<?php echo (($result["weekNumber"] == 19) ? 23 : 15); ?>>&nbsp;</td></tr>
              <tr>
                <td class="noBorder" colspan=<?php echo (($result["weekNumber"] == 19) ? 23 : 15); ?> style="text-align:center; font-size:20px;">Tiebreakers</td>
              </tr>
              <tr style="height:20px"><td class="noBorder" colspan=<?php echo (($result["weekNumber"] == 19) ? 23 : 15); ?>>&nbsp;</td></tr>
<?php
  $games = RunQuery( "select homeTeam, awayTeam from Game where weekNumber=" . $result["weekNumber"] . 
                     " and season=" . $result["season"] . " order by tieBreakOrder desc, gameTime desc" );
  $tieBreakers = RunQuery( "select tieBreaker1, tieBreaker2, tieBreaker3, tieBreaker4, tieBreaker5, tieBreaker6 from PlayoffResult " . 
                           "join Session using (userID) where sessionID=" . $_SESSION["spsID"] . 
                           " and weekNumber=" . $result["weekNumber"] . " and season=" . $result["season"] );

  $count = 0;
  echo "              <tr>\n";
  foreach( $games as $thisGame )
  {
    if( $count ) {
      echo "                <td class=\"noBorder\" style=\"min-width:30px;\">&nbsp;</td>\n";
    }
    echo "                <td class=\"noBorder\" style=\"font-size:30px;width:" . (($result["weekNumber"] == 19) ? 6 : 9.375) . "%\">" . $teamAliases[$thisGame["awayTeam"]] . "</td>\n";
    echo "                <td class=\"noBorder\" style=\"width:" . (($result["weekNumber"] == 19) ? 2 : 3.125) . "%\">&nbsp;</td>\n";
    echo "                <td class=\"noBorder\" style=\"font-size:30px;width:" . (($result["weekNumber"] == 19) ? 6 : 9.375) . "%\">" . $teamAliases[$thisGame["homeTeam"]] . "</td>\n";
    $count++;
  }
  echo "              </tr>\n";

  $count = 0;
  echo "              <tr>\n";
  foreach( $games as $thisGame )
  {
    if( $count ) {
      echo "                <td class=\"noBorder\" style=\"min-width:30px;\">&nbsp;</td>\n";
    }
    echo "                <td class=\"noBorder\"><div class=\"imgDiv\"><img class=\"teamLogo\" src=\"" .  
        getIcon($thisGame["awayTeam"], $result["season"]) . "\" draggable=\"false\" ondragstart=\"return false;\" /></div></td>\n";
    echo "                <td class=\"noBorder\" style=\"font-size:40px;\">@</td>\n";
    echo "                <td class=\"noBorder\"><div class=\"imgDiv\"><img class=\"teamLogo\" src=\"" . 
        getIcon($thisGame["homeTeam"], $result["season"]) . "\" draggable=\"false\" ondragstart=\"return false;\" /></div></td>\n";
    $count++;
  }
  echo "              </tr>\n";

  $count = 1;
  echo "              <tr>\n";
  foreach( $games as $thisGame )
  {
    if( $count > 1 ) {
      echo "                <td class=\"noBorder\" style=\"min-width:30px;\">&nbsp;</td>\n";
    }
    echo "                <td class=\"noBorder\" colspan=3>\n";
    echo "                  <span>Combined score</span>\n";
    echo "                  <input id=\"tieBreak" . $count . "\" name=\"tieBreak" . $count . "\" type=\"text\" maxlength=\"3\" " . 
        "onKeyUp=\"NumbersOnly('tieBreak" . $count . "'); ToggleSaveButton();\" value=\"" . $tieBreakers[0]["tieBreaker" . $count] . 
        "\" style=\"width:35px;\" />\n";
    $count++;
  }
  echo "              </tr>\n";
?>
            </table>
          </td>
        </tr>
        <tr style="height:75px;">
          <td class="noBorder" colspan="2">
            <form action="." method="post" id="makePicksForm">
              <input type="hidden" id="windowScrollPos" name="windowScrollPos" value="0">
<?php
  echo "              <input type=\"hidden\" id=\"picksType\" name=\"picksType\" value=\"" . 
      (($result["weekNumber"] == 19) ? "wildCard" : "divisional") . "\">\n";
  for( $i=1; $i<=$sliderCount; $i++ )
  {
    if( isset($picks[$i]) && ($picks[$i]["canChange"] != 0) )
    {
      echo "              <input type=\"hidden\" id=\"game" . $i . "\" name=\"game" . $i . "\" value=\"" . 
          $picks[$i]["gameID"] . "\">\n";
      echo "              <input type=\"hidden\" id=\"homeTeam" . $i . "\" name=\"homeTeam" . $i . "\" value=\"" . 
          $picks[$i]["homeTeam"] . "\">\n";
      echo "              <input type=\"hidden\" id=\"awayTeam" . $i . "\" name=\"awayTeam" . $i . "\" value=\"" . 
          $picks[$i]["awayTeam"] . "\">\n";
      echo "              <input type=\"hidden\" id=\"pts" . $i . "\" name=\"pts" . $i . "\" value=\"" . $picks[$i]["points"] . "\">\n";
      echo "              <input type=\"hidden\" id=\"winner" . $i . "\" name=\"winner" . $i . "\" value=\"\">\n";
      echo "              <input type=\"hidden\" id=\"tb" . $i . "\" name=\"tb" . $i . "\" value=\"\">\n";
    }
  }
?>
                    <button id="saveRosterButton" onclick="document.getElementById('windowScrollPos').value = $(window).scrollTop(); showWarning = false; document.getElementById('makePicksForm').submit();"<?php 
  echo $saveButtonEnabled ? "" : " disabled"; ?> style="font-size:20px;">Save Picks</button>
            </form>
          </td>
        </tr>
      </table>
