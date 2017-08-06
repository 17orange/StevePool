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
      <span>Making Picks for Super Bowl</span>
      <br/>
      <table style="width:100%; border-spacing:0px; text-align:center; font-size: 14px;">
        <tr><td class="noBorder" colspan=11>&nbsp;</td></tr>
        <tr>
          <td class="noBorder warningZone" style="font-size: 20px" colspan=3>&nbsp;</td>
          <td class="noBorder" colspan=6 style="text-align:center; font-size: 20px;">Confidence Points</td>
          <td class="noBorder warningZone" style="font-size: 20px" colspan=3>&nbsp;</td>
        </tr>
        <tr><td class="noBorder" colspan=11>&nbsp;</td></tr>
        <tr>
          <td class="noBorder" style="width:9%; font-size: 20px;">&nbsp;</td>
          <td class="noBorder" style="width:9%; font-size: 20px;">&nbsp;</td>
          <td class="noBorder" style="width:9%; font-size: 20px;">9</td>
          <td class="noBorder" style="width:9%; font-size: 20px;">8</td>
          <td class="noBorder" style="width:9%; font-size: 20px;">7</td>
          <td class="noBorder" style="width:9%; font-size: 20px;">5</td>
          <td class="noBorder" style="width:9%; font-size: 20px;">3</td>
          <td class="noBorder" style="width:9%; font-size: 20px;">5</td>
          <td class="noBorder" style="width:9%; font-size: 20px;">3</td>
          <td class="noBorder" style="width:9%; font-size: 20px;">2</td>
          <td class="noBorder" style="width:9%; font-size: 20px;">1</td>
        </tr>
        <tr><td class="noBorder" style="height:45px;" colspan=11>&nbsp;</td></tr>
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

  // make a function to build the type block
  function makeTypeBlock($captionNum)
  {
    $caption = array("", "Super Bowl<br>Winner", "Leader<br>3Q", "Leader<br>2Q", "Leader<br>1Q", "Pass Yds<br>Final", 
                     "Pass Yds<br>2Q", "Rush Yds<br>Final", "Rush Yds<br>2Q", "TDs<br>Final", "TDs<br>2Q");
    $innerHTML = "<div class=\"mpSBCaption\">" . $caption[$captionNum] . "</div>";
    return $innerHTML;
  }

  // fill in the first row
  echo "        <tr style=\"height:96px\" class=\"montserrat\">\n";
  for($i=0; $i<11; $i++)
  {
    $style = (!isset($picks[$i]) || ($picks[$i]["winner"] != $picks[$i]["homeTeam"])) 
             ? " class=\"mobileRow noBorder\""
             : (" class=\"mobileRow mpImgTD mpAwayTeam" . (($picks[$i]["canChange"] == 0) ? "Locked" : "") . "\"");
    $drag = (isset($picks[$i]) && ($picks[$i]["canChange"] != 0)) ? (" onMouseDown=\"startDrag(1, " . $i . ");\"") : "";
    $text = (!isset($picks[$i]) || ($picks[$i]["winner"] != $picks[$i]["homeTeam"])) 
            ? ""
            : ($teamAliases[$picks[$i]["awayTeam"]] . " <br><div class=\"imgDiv\"><img class=\"teamLogo\" src=\"" . 
               getIcon($picks[$i]["awayTeam"], $result["season"]) . "\" draggable=\"false\" ondragstart=\"return false;\" /></div>" . 
               makeTypeBlock($i));
    echo "          <td id=\"mp1_" . $i . "\"" . $style . $drag . ">" . $text . "</td>\n";
  }
  echo "        </tr>\n";

  // fill in the second row
  echo "        <tr style=\"height:96px\" class=\"montserrat\">\n";
  for($i=0; $i<11; $i++)
  {
    $style = (!isset($picks[$i]) || ($picks[$i]["winner"] == $picks[$i]["awayTeam"])) 
             ? " class=\"mobileRow noBorder\"" 
             : (($picks[$i]["winner"] == $picks[$i]["homeTeam"]) 
               ? " class=\"mobileRow mpGameInfo" . (($picks[$i]["canChange"] == 0) ? "Locked" : "") . "\"" 
               : " class=\"mobileRow mpImgTD mpAwayTeam" . (($picks[$i]["canChange"] == 0) ? "Locked" : "") . "\"");
    $drag = (isset($picks[$i]) && ($picks[$i]["canChange"] != 0)) ? " onMouseDown=\"startDrag(2, " . $i . ");\"" : "";
    $text = (!isset($picks[$i]) || ($picks[$i]["winner"] == $picks[$i]["awayTeam"])) 
            ? ""
            : (($picks[$i]["winner"] == $picks[$i]["homeTeam"]) 
              ? (($picks[$i]["type"] != "winner") 
                ? ("TIE <br><img style=\"position:absolute; height:0px; width:0px;\" src=\"" . getIcon("", $result["season"]) . "\">") 
                : formatTime($picks[$i]))
              : ($teamAliases[$picks[$i]["awayTeam"]] . " <br><div class=\"imgDiv\"><img class=\"teamLogo\" src=\"" . 
                 getIcon($picks[$i]["awayTeam"], $result["season"]) . "\" draggable=\"false\" ondragstart=\"return false;\" /></div>" . 
                 makeTypeBlock($i)));
    echo "          <td id=\"mp2_" . $i . "\"" . $style . $drag . ">" . $text . "</td>\n";
  }
  echo "        </tr>\n";

  // fill in the third row
  echo "        <tr style=\"height:96px\" class=\"montserrat\">\n";
  $saveButtonEnabled = true;
  for($i=0; $i<11; $i++)
  {
    $style = (!isset($picks[$i]) || ($picks[$i]["canChange"] == 0))
             ? (($i==0)
               ? " class=\"noBorder fjalla\" style=\"font-size: 20px;\""
               : " class=\"mobileRow mpImgTD mpLockedSelection\"") 
             : ((($picks[$i]["winner"] == $picks[$i]["awayTeam"]) || 
                 ($picks[$i]["winner"] == $picks[$i]["homeTeam"]) || 
                 ($i > 1))
               ? " class=\"mobileRow mpImgTD mpValidSelection\"" 
               : " class=\"mobileRow mpImgTD mpInvalidSelection\"" );
    if( $saveButtonEnabled )
    {
      $saveButtonEnabled = ($style == " class=\"mpInvalidSelection\"");
    }
    $drag = (isset($picks[$i]) && ($picks[$i]["canChange"] != 0)) ? " onMouseDown=\"startDrag(3, " . $i . ");\"" : "";
    $text = ($i==0) 
            ? "My Pick" 
            : (($picks[$i]["winner"] == $picks[$i]["awayTeam"])
              ? ($teamAliases[$picks[$i]["awayTeam"]] . " " . ($i>1 ? $picks[$i]["points"] : "") . "<br><div class=\"imgDiv\">" . 
                 "<img class=\"teamLogo\" src=\"" . getIcon($picks[$i]["awayTeam"], $result["season"]) . "\" draggable=\"false\" " . 
                 "ondragstart=\"return false;\" /></div>" . makeTypeBlock($i))
              : (($picks[$i]["winner"] == $picks[$i]["homeTeam"]) 
                ? ($teamAliases[$picks[$i]["homeTeam"]] . " " . ($i>1 ? $picks[$i]["points"] : "") . "<br><div class=\"imgDiv\">" . 
                   "<img class=\"teamLogo\" src=\"" . getIcon($picks[$i]["homeTeam"], $result["season"]) . "\" draggable=\"false\" " . 
                   "ondragstart=\"return false;\" /></div>")
                : (($picks[$i]["type"] != "winner") 
                  ? ("TIE " . $picks[$i]["points"] . "<br><img style=\"position:absolute; height:0px; width:0px;\" " . 
                    "src=\"" . getIcon("", $result["season"]) . "\">") 
                  : formatTime($picks[$i]))));
    echo "          <td id=\"mp3_" . $i . "\"" . $style . $drag . ">" . $text . "</td>\n";
  }
  echo "        </tr>\n";

  // fill in the fourth row
  echo "        <tr style=\"height:96px\" class=\"montserrat\">\n";
  for($i=0; $i<11; $i++)
  {
    $style = (!isset($picks[$i]) || ($picks[$i]["winner"] == $picks[$i]["homeTeam"])) 
             ? " class=\"mobileRow noBorder\"" 
             : (($picks[$i]["winner"] == $picks[$i]["awayTeam"]) 
               ? " class=\"mobileRow mpGameInfo" . (($picks[$i]["canChange"] == 0) ? "Locked" : "") . "\"" 
               : " class=\"mobileRow mpImgTD mpHomeTeam" . (($picks[$i]["canChange"] == 0) ? "Locked" : "") . "\"");
    $drag = (isset($picks[$i]) && ($picks[$i]["canChange"] != 0)) ? " onMouseDown=\"startDrag(4, " . $i . ");\"" : "";
    $text = (!isset($picks[$i]) || ($picks[$i]["winner"] == $picks[$i]["homeTeam"])) 
            ? ""
            : (($picks[$i]["winner"] == $picks[$i]["awayTeam"]) 
              ? (($picks[$i]["type"] != "winner") 
                 ? ("TIE <br><img style=\"position:absolute; height:0px; width:0px;\"src=\"" . getIcon("", $result["season"]) . "\">") 
                 : formatTime($picks[$i])) 
              : ($teamAliases[$picks[$i]["homeTeam"]] . "<br><div class=\"imgDiv\"><img class=\"teamLogo\" src=\"" . 
                 getIcon($picks[$i]["homeTeam"], $result["season"]) . "\" draggable=\"false\" ondragstart=\"return false;\" /></div>"));
    echo "          <td id=\"mp4_" . $i . "\"" . $style . $drag . ">" . $text . "</td>\n";
  }
  echo "        </tr>\n";

  // fill in the fifth row
  echo "        <tr style=\"height:96px\" class=\"montserrat\">\n";
  for($i=0; $i<11; $i++)
  {
    $style = (!isset($picks[$i]) || ($picks[$i]["winner"] != $picks[$i]["awayTeam"])) 
             ? " class=\"mobileRow noBorder\"" 
             : " class=\"mobileRow mpImgTD mpHomeTeam" . (($picks[$i]["canChange"] == 0) ? "Locked" : "") . "\"";
    $drag = (isset($picks[$i]) && ($picks[$i]["canChange"] != 0)) ? " onMouseDown=\"startDrag(5, " . $i . ");\"" : "";
    $text = (!isset($picks[$i]) || ($picks[$i]["winner"] != $picks[$i]["awayTeam"])) 
            ? ""
            : ($teamAliases[$picks[$i]["homeTeam"]] . " <br><div class=\"imgDiv\"><img class=\"teamLogo\" src=\"" . 
               getIcon($picks[$i]["homeTeam"], $result["season"]) . "\" draggable=\"false\" ondragstart=\"return false;\" /></div>");
    echo "          <td id=\"mp5_" . $i . "\"" . $style . $drag . ">" . $text . "</td>\n";
  }
  echo "        </tr>\n";
?>
        <tr style="height:75px;">
          <td class="noBorder" colspan="11">
            <form action="." method="post" id="makePicksForm">
              <input type="hidden" id="windowScrollPos" name="windowScrollPos" value="0">
<?php
  echo "              <input type=\"hidden\" id=\"picksType\" name=\"picksType\" value=\"superBowl\">\n";
  for( $i=1; $i<11; $i++ )
  {
    if( isset($picks[$i]) && ($picks[$i]["canChange"] != 0) )
    {
      echo "              <input type=\"hidden\" id=\"pickType" . $i . "\" name=\"pickType" . $i . "\" value=\"" . 
          $picks[$i]["type"] . "\">\n";
      echo "              <input type=\"hidden\" id=\"winner" . $i . "\" name=\"winner" . $i . "\" value=\"\">\n";
    }
  }

  // grab the tiebreaker they set
  $TBresult = RunQuery( "select tieBreaker1 from PlayoffResult join Session using (userID) where sessionID=" . $_SESSION["spsID"] . 
                        " and weekNumber=" . $result["weekNumber"] . " and season=" . $result["season"] );
?>
              <br/>
              <span>Combined Super Bowl score</span>
              <input id="tieBreak" name="tieBreak" type="text" maxlength="3" onKeyUp="NumbersOnly('tieBreak'); ToggleSaveButton();" value="<?php
  echo $TBresult[0]["tieBreaker1"];
?>" style="width:35px;" />
              <br/><br/>
              <button id="saveRosterButton" onclick="document.getElementById('windowScrollPos').value = $(window).scrollTop(); showWarning = false; document.getElementById('makePicksForm').submit();"<?php 
  echo $saveButtonEnabled ? "" : " disabled"; ?> style="font-size:20px;">Save Picks</button>
            </form>
          </td>
        </tr>
      </table>
