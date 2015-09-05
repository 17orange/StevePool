      <span>Making Picks for Conference Championship</span>
      <button onClick="PickAllHomeTeams()">All Home Teams</button>
      <button onClick="PickAllAwayTeams()">All Away Teams</button>
      <br/>
      <table style="width:100%; border-spacing:0px; text-align:center; font-size: 14px;">
        <tr>
          <td style="width:50%;" class="noBorder">
            <table style="width:100%; border-spacing:0px; text-align:center; font-size: 14px;">
              <tr><td class="noBorder" colspan=5>&nbsp;</td></tr>
              <tr>
                <td class="noBorder" colspan=1>&nbsp;</td>
                <td class="noBorder" colspan=4 style="text-align:center; font-size: 20px;">Confidence Points</td>
              </tr>
              <tr><td class="noBorder" colspan=5>&nbsp;</td></tr>
<?php
  // fill in the point values
  echo "              <tr>\n";
  echo "                <td class=\"noBorder\">&nbsp;</td>\n";
  for($i=0; $i<4; $i++)
  {
    echo "                <td class=\"noBorder\" style=\"width:20%; font-size: 20px;\">" . (4 - $i) . "</td>\n";
  }
  echo "              </tr>\n";
  echo "              <tr><td class=\"noBorder\" style=\"height:25px;\" colspan=5>&nbsp;</td></tr>\n";

  // grab their picks for this week
  $pickResults = RunQuery( "select gameID, points, winner, gameTime, homeTeam, awayTeam, lockTime > now() as canChange, " . 
                           "status, timeLeft, homeScore, awayScore, type from Pick join Game using (gameID) " . 
                           "join Session using (userID) where sessionID=" . $_SESSION["spsID"] . " and weekNumber=" . 
                           $result["weekNumber"] . " and season=" . $result["season"] );
  $picks = array();
  while( ($thisPick = mysqli_fetch_assoc($pickResults)) != null )
  {
    $picks[5 - $thisPick["points"]] = $thisPick;
  }

  // make a function to build the type block
  function makeTypeBlock($caption)
  {
    $innerHTML = "<div style=\"height:18px; position:absolute; top:-25px; left:-2px; width:100%; " . 
                 "border-bottom:4px solid #314972;\" class=\"mpAwayTeam\">" . $caption . "</div>";
    return $innerHTML;
  }

  // fill in the first row
  echo "              <tr style=\"height:96px\" class=\"montserrat\">\n";
  for($i=0; $i<5; $i++)
  {
    $style = (!isset($picks[$i]) || ($picks[$i]["winner"] != $picks[$i]["homeTeam"])) 
             ? " class=\"noBorder\""
             : " class=\"mpImgTD mpAwayTeam" . (($picks[$i]["canChange"] == 0) ? "Locked" : "") . "\"";
    $drag = (isset($picks[$i]) && ($picks[$i]["canChange"] != 0)) ? " onMouseDown=\"startDrag(1, " . $i . ");\"" : "";
    $text = (!isset($picks[$i]) || ($picks[$i]["winner"] != $picks[$i]["homeTeam"])) 
            ? ""
            : ($picks[$i]["awayTeam"] . "<br><div class=\"imgDiv\"><img class=\"teamLogo\" src=\"" . 
               getIcon($picks[$i]["awayTeam"], $result["season"]) . "\" draggable=\"false\" ondragstart=\"return false;\" /></div>" . 
               makeTypeBlock(($picks[$i]["type"] == "winner") ? "Final" : "Halftime"));
    echo "                <td id=\"mp1_" . $i . "\"" . $style . $drag . ">" . $text . "</td>\n";
  }
  echo "              </tr>\n";

  // fill in the second row
  echo "              <tr style=\"height:96px\" class=\"montserrat\">\n";
  for($i=0; $i<5; $i++)
  {
    $style = (!isset($picks[$i]) || ($picks[$i]["winner"] == $picks[$i]["awayTeam"])) 
             ? " class=\"noBorder\"" 
             : (($picks[$i]["winner"] == $picks[$i]["homeTeam"]) 
               ? " class=\"mpGameInfo" . (($picks[$i]["canChange"] == 0) ? "Locked" : "") . "\"" 
               : " class=\"mpImgTD mpAwayTeam" . (($picks[$i]["canChange"] == 0) ? "Locked" : "") . "\"");
    $drag = (isset($picks[$i]) && ($picks[$i]["canChange"] != 0)) ? " onMouseDown=\"startDrag(2, " . $i . ");\"" : "";
    $text = (!isset($picks[$i]) || ($picks[$i]["winner"] == $picks[$i]["awayTeam"])) 
            ? ""
            : (($picks[$i]["winner"] == $picks[$i]["homeTeam"]) 
              ? (($picks[$i]["type"] == "winner2Q") 
                ? ("TIE <br><img style=\"position:absolute; height:0px; width:0px;\" src=\"" . getIcon("", $result["season"]) . "\">") 
                : formatTime($picks[$i]))
              : ($picks[$i]["awayTeam"] . "<br><div class=\"imgDiv\"><img class=\"teamLogo\" src=\"" . 
                 getIcon($picks[$i]["awayTeam"], $result["season"]) . "\" draggable=\"false\" ondragstart=\"return false;\" /></div>" . 
                 makeTypeBlock(($picks[$i]["type"] == "winner") ? "Final" : "Halftime")));
    echo "                <td id=\"mp2_" . $i . "\"" . $style . $drag . ">" . $text . "</td>\n";
  }
  echo "              </tr>\n";

  // fill in the third row
  echo "              <tr style=\"height:96px\" class=\"montserrat\">\n";
  $saveButtonEnabled = true;
  for($i=0; $i<5; $i++)
  {
    $style = (!isset($picks[$i]) || ($picks[$i]["canChange"] == 0))
             ? (($i==0)
               ? " class=\"noBorder fjalla\" style=\"font-size: 20px;\""
               : " class=\"mpImgTD mpLockedSelection\"") 
             : ((($picks[$i]["winner"] == $picks[$i]["awayTeam"]) || 
                 ($picks[$i]["winner"] == $picks[$i]["homeTeam"]) || 
                 ($picks[$i]["winner"] == "TIE"))
               ? " class=\"mpImgTD mpValidSelection\"" 
               : " class=\"mpImgTD mpInvalidSelection\"" );
    if( $saveButtonEnabled )
    {
      $saveButtonEnabled = ($style == " class=\"mpInvalidSelection\"");
    }
    $drag = (isset($picks[$i]) && ($picks[$i]["canChange"] != 0)) ? " onMouseDown=\"startDrag(3, " . $i . ");\"" : "";
    $text = ($i==0) 
            ? "Winner" 
            : (!isset($picks[$i])
              ? "Bye Week"
              : (($picks[$i]["winner"] == $picks[$i]["awayTeam"])
                ? ($picks[$i]["awayTeam"] . " " . (5 - $i) . "<br><div class=\"imgDiv\"><img class=\"teamLogo\" src=\"" . 
                   getIcon($picks[$i]["awayTeam"], $result["season"]) . "\" draggable=\"false\" ondragstart=\"return false;\" /></div>" . 
                   makeTypeBlock(($picks[$i]["type"] == "winner") ? "Final" : "Halftime"))
                : (($picks[$i]["winner"] == $picks[$i]["homeTeam"]) 
                  ? ($picks[$i]["homeTeam"] . " " . (5 - $i) . "<br><div class=\"imgDiv\"><img class=\"teamLogo\" src=\"" . 
                     getIcon($picks[$i]["homeTeam"], $result["season"]) . "\" draggable=\"false\" ondragstart=\"return false;\" /></div>")
                  : (($picks[$i]["type"] == "winner2Q") ? ("TIE " . (5 - $i) . "<br><img style=\"position:absolute; height:0px; width:0px;\" src=\"" . getIcon("", $result["season"]) . "\">") : formatTime($picks[$i])))));
    echo "                <td id=\"mp3_" . $i . "\"" . $style . $drag . ">" . $text . "</td>\n";
  }
  echo "              </tr>\n";

  // fill in the fourth row
  echo "              <tr style=\"height:96px\" class=\"montserrat\">\n";
  for($i=0; $i<5; $i++)
  {
    $style = (!isset($picks[$i]) || ($picks[$i]["winner"] == $picks[$i]["homeTeam"])) 
             ? " class=\"noBorder\"" 
             : (($picks[$i]["winner"] == $picks[$i]["awayTeam"]) 
               ? " class=\"mpGameInfo" . (($picks[$i]["canChange"] == 0) ? "Locked" : "") . "\"" 
               : " class=\"mpImgTD mpHomeTeam" . (($picks[$i]["canChange"] == 0) ? "Locked" : "") . "\"");
    $drag = (isset($picks[$i]) && ($picks[$i]["canChange"] != 0)) ? " onMouseDown=\"startDrag(4, " . $i . ");\"" : "";
    $text = (!isset($picks[$i]) || ($picks[$i]["winner"] == $picks[$i]["homeTeam"])) 
            ? ""
            : (($picks[$i]["winner"] == $picks[$i]["awayTeam"]) 
              ? (($picks[$i]["type"] == "winner2Q") 
                 ? ("TIE <br><img style=\"position:absolute; height:0px; width:0px;\" src=\"" . getIcon("", $result["season"]) . "\">") 
                 : formatTime($picks[$i])) 
              : ($picks[$i]["homeTeam"] . "<br><div class=\"imgDiv\"><img class=\"teamLogo\" src=\"" . 
                 getIcon($picks[$i]["homeTeam"], $result["season"]) . "\" draggable=\"false\" ondragstart=\"return false;\" /></div>"));
    echo "                <td id=\"mp4_" . $i . "\"" . $style . $drag . ">" . $text . "</td>\n";
  }
  echo "              </tr>\n";

  // fill in the fifth row
  echo "              <tr style=\"height:96px\" class=\"montserrat\">\n";
  for($i=0; $i<5; $i++)
  {
    $style = (!isset($picks[$i]) || ($picks[$i]["winner"] != $picks[$i]["awayTeam"])) 
             ? " class=\"noBorder\"" 
             : " class=\"mpImgTD mpHomeTeam" . (($picks[$i]["canChange"] == 0) ? "Locked" : "") . "\"";
    $drag = (isset($picks[$i]) && ($picks[$i]["canChange"] != 0)) ? " onMouseDown=\"startDrag(5, " . $i . ");\"" : "";
    $text = (!isset($picks[$i]) || ($picks[$i]["winner"] != $picks[$i]["awayTeam"])) 
            ? ""
            : ($picks[$i]["homeTeam"] . "<br><div class=\"imgDiv\"><img class=\"teamLogo\" src=\"" . 
               getIcon($picks[$i]["homeTeam"], $result["season"]) . "\" draggable=\"false\" ondragstart=\"return false;\" /></div>");
    echo "                <td id=\"mp5_" . $i . "\"" . $style . $drag . ">" . $text . "</td>\n";
  }
  echo "              </tr>\n";
?>
            </table>
          </td>
          <td style="width:50%;" class="noBorder">
            <table style="width:100%; border-spacing:0px; text-align:center; font-size: 14px;">
              <tr><td class="noBorder" colspan=5>&nbsp;</td></tr>
              <tr>
                <td class="noBorder" colspan=5 style="text-align:center; font-size:20px;">Tiebreakers</td>
              </tr>
              <tr style="height:20px"><td class="noBorder">&nbsp;</td></tr>
<?php
  $games = RunQuery( "select homeTeam, awayTeam from Game where weekNumber=" . $result["weekNumber"] . 
                     " and season=" . $result["season"] . " order by gameTime desc" );
  $tieBreakers = mysqli_fetch_assoc( RunQuery( "select tieBreaker1, tieBreaker2, tieBreaker3, tieBreaker4 from PlayoffResult " . 
                                               "join Session using (userID) where sessionID=" . $_SESSION["spsID"] . 
                                               " and weekNumber=" . $result["weekNumber"] . " and season=" . $result["season"] ) );
  $count = 1;
  $halftimeTBs = "";
  while( ($thisGame = mysqli_fetch_assoc($games)) != null )
  {
    echo "              <tr>\n";
    echo "                <td class=\"noBorder\" style=\"width:25%;\">&nbsp;</td>\n"; 
    echo "                <td class=\"noBorder\" style=\"width:20%; font-size:30px;\">" . $thisGame["awayTeam"] . 
        "<br><div class=\"imgDiv\"><img class=\"teamLogo\" src=\"" .  getIcon($thisGame["awayTeam"], $result["season"]) . 
        "\" draggable=\"false\" ondragstart=\"return false;\" /></div></td>\n";
    echo "                <td class=\"noBorder\" style=\"width:10%; font-size:40px;\">@</td>\n";
    echo "                <td class=\"noBorder\" style=\"width:20%; font-size:30px;\">" . $thisGame["homeTeam"] . 
        "<br><div class=\"imgDiv\"><img class=\"teamLogo\" src=\"" . getIcon($thisGame["homeTeam"], $result["season"]) . 
        "\" draggable=\"false\" ondragstart=\"return false;\" /></div></td>\n";
    echo "                <td class=\"noBorder\" style=\"width:25%;\">&nbsp;</td>\n"; 
    echo "              </tr>\n";
    echo "              <tr>\n";
    echo "                <td class=\"noBorder\" colspan=5>\n";
    echo "                  <span>Combined final score</span>\n";
    echo "                  <input id=\"tieBreak" . $count . "\" name=\"tieBreak" . $count . "\" type=\"text\" maxlength=\"3\" " . 
        "onKeyUp=\"NumbersOnly('tieBreak" . $count . "'); ToggleSaveButton();\" value=\"" . $tieBreakers["tieBreaker" . $count] . 
        "\" style=\"width:35px;\" />\n";  
    echo "              </tr>\n";
    echo "              <tr style=\"height:30px\"><td class=\"noBorder\">&nbsp;</td></tr>\n";

    $halftimeTBs .= "              <tr>\n";
    $halftimeTBs .= "                <td class=\"noBorder\" style=\"width:25%;\">&nbsp;</td>\n"; 
    $halftimeTBs .= "                <td class=\"noBorder\" style=\"width:20%; font-size:30px;\">" . $thisGame["awayTeam"] . 
                    "<br><div class=\"imgDiv\"><img class=\"teamLogo\" src=\"" .  getIcon($thisGame["awayTeam"], $result["season"]) . 
                    "\" draggable=\"false\" ondragstart=\"return false;\" /></div></td>\n";
    $halftimeTBs .= "                <td class=\"noBorder\" style=\"width:10%; font-size:40px;\">@</td>\n";
    $halftimeTBs .= "                <td class=\"noBorder\" style=\"width:20%; font-size:30px;\">" . $thisGame["homeTeam"] . 
                    "<br><div class=\"imgDiv\"><img class=\"teamLogo\" src=\"" . getIcon($thisGame["homeTeam"], $result["season"]) . 
                    "\" draggable=\"false\" ondragstart=\"return false;\" /></div></td>\n";
    $halftimeTBs .= "                <td class=\"noBorder\" style=\"width:25%;\">&nbsp;</td>\n"; 
    $halftimeTBs .= "              </tr>\n";
    $halftimeTBs .= "              <tr>\n";
    $halftimeTBs .= "                <td class=\"noBorder\" colspan=5>\n";
    $halftimeTBs .= "                  <span>Combined halftime score</span>\n";
    $halftimeTBs .= "                  <input id=\"tieBreak" . ($count + 2) . "\" name=\"tieBreak" . $count . "\" type=\"text\" " . 
                    "maxlength=\"3\" onKeyUp=\"NumbersOnly('tieBreak" . ($count + 2) . "'); ToggleSaveButton();\" value=\"" . 
                    $tieBreakers["tieBreaker" . ($count + 2)] . "\" style=\"width:35px;\" />\n";  
    $halftimeTBs .= "              </tr>\n";
    $halftimeTBs .= "              <tr style=\"height:30px\"><td class=\"noBorder\">&nbsp;</td></tr>\n";
    $count++;
  }
  echo $halftimeTBs;
?>
            </table>
          </td>
        </tr>
              <tr style="height:75px;">
<!--                <td class="noBorder">&nbsp;</td> -->
                <td class="noBorder" colspan="2">
                  <form action="." method="post" id="makePicksForm">
                    <input type="hidden" id="windowScrollPos" name="windowScrollPos" value="0">
<?php
  echo "                    <input type=\"hidden\" id=\"picksType\" name=\"picksType\" value=\"conference\">\n";
  for( $i=1; $i<5; $i++ )
  {
    if( isset($picks[$i]) && ($picks[$i]["canChange"] != 0) )
    {
      echo "                    <input type=\"hidden\" id=\"game" . $i . "\" name=\"game" . $i . "\" value=\"" . 
          $picks[$i]["gameID"] . "\">\n";
      echo "                    <input type=\"hidden\" id=\"pickType" . $i . "\" name=\"pickType" . $i . "\" value=\"" . 
          $picks[$i]["type"] . "\">\n";
      echo "                    <input type=\"hidden\" id=\"homeTeam" . $i . "\" name=\"homeTeam" . $i . "\" value=\"" . 
          $picks[$i]["homeTeam"] . "\">\n";
      echo "                    <input type=\"hidden\" id=\"awayTeam" . $i . "\" name=\"awayTeam" . $i . "\" value=\"" . 
          $picks[$i]["awayTeam"] . "\">\n";
      echo "                    <input type=\"hidden\" id=\"pts" . $i . "\" name=\"pts" . $i . "\" value=\"" . (5 - $i) . "\">\n";
      echo "                    <input type=\"hidden\" id=\"winner" . $i . "\" name=\"winner" . $i . "\" value=\"\">\n";
      echo "                    <input type=\"hidden\" id=\"tb" . $i . "\" name=\"tb" . $i . "\" value=\"\">\n";
    }
  }
?>
                    <button id="saveRosterButton" onclick="document.getElementById('windowScrollPos').value = $(window).scrollTop(); document.getElementById('makePicksForm').submit();"<?php 
  echo $saveButtonEnabled ? "" : " disabled"; ?> style="font-size:20px;">Save Picks</button>
                  </form>
                </td>
              </tr>
      </table>
