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
                           $result["weekNumber"] . " and season=" . $result["season"], false );
  $picks = array();
  foreach( $pickResults as $thisPick )
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
  $tieBreakers = RunQuery( "select tieBreaker1, tieBreaker2, tieBreaker3, tieBreaker4 from PlayoffResult " . 
                           "join Session using (userID) where sessionID=" . $_SESSION["spsID"] . 
                           " and weekNumber=" . $result["weekNumber"] . " and season=" . $result["season"] );
  $count = 1;
  $halftimeTBs = "";
  foreach( $games as $thisGame )
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
        "onKeyUp=\"NumbersOnly('tieBreak" . $count . "'); ToggleSaveButton();\" value=\"" . $tieBreakers[0]["tieBreaker" . $count] . 
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
                    $tieBreakers[0]["tieBreaker" . ($count + 2)] . "\" style=\"width:35px;\" />\n";  
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

  <!-- MOBILE CHECK -->
  <script type="text/javascript">
    var check = false;
    (function(a){if(/(android|bb\d+|meego).+mobile|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od)|iris|kindle|lge |maemo|midp|mmp|mobile.+firefox|netfront|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|series(4|6)0|symbian|treo|up\.(browser|link)|vodafone|wap|windows ce|xda|xiino|android|ipad|playbook|silk/i.test(a)||/1207|6310|6590|3gso|4thp|50[1-6]i|770s|802s|a wa|abac|ac(er|oo|s\-)|ai(ko|rn)|al(av|ca|co)|amoi|an(ex|ny|yw)|aptu|ar(ch|go)|as(te|us)|attw|au(di|\-m|r |s )|avan|be(ck|ll|nq)|bi(lb|rd)|bl(ac|az)|br(e|v)w|bumb|bw\-(n|u)|c55\/|capi|ccwa|cdm\-|cell|chtm|cldc|cmd\-|co(mp|nd)|craw|da(it|ll|ng)|dbte|dc\-s|devi|dica|dmob|do(c|p)o|ds(12|\-d)|el(49|ai)|em(l2|ul)|er(ic|k0)|esl8|ez([4-7]0|os|wa|ze)|fetc|fly(\-|_)|g1 u|g560|gene|gf\-5|g\-mo|go(\.w|od)|gr(ad|un)|haie|hcit|hd\-(m|p|t)|hei\-|hi(pt|ta)|hp( i|ip)|hs\-c|ht(c(\-| |_|a|g|p|s|t)|tp)|hu(aw|tc)|i\-(20|go|ma)|i230|iac( |\-|\/)|ibro|idea|ig01|ikom|im1k|inno|ipaq|iris|ja(t|v)a|jbro|jemu|jigs|kddi|keji|kgt( |\/)|klon|kpt |kwc\-|kyo(c|k)|le(no|xi)|lg( g|\/(k|l|u)|50|54|\-[a-w])|libw|lynx|m1\-w|m3ga|m50\/|ma(te|ui|xo)|mc(01|21|ca)|m\-cr|me(rc|ri)|mi(o8|oa|ts)|mmef|mo(01|02|bi|de|do|t(\-| |o|v)|zz)|mt(50|p1|v )|mwbp|mywa|n10[0-2]|n20[2-3]|n30(0|2)|n50(0|2|5)|n7(0(0|1)|10)|ne((c|m)\-|on|tf|wf|wg|wt)|nok(6|i)|nzph|o2im|op(ti|wv)|oran|owg1|p800|pan(a|d|t)|pdxg|pg(13|\-([1-8]|c))|phil|pire|pl(ay|uc)|pn\-2|po(ck|rt|se)|prox|psio|pt\-g|qa\-a|qc(07|12|21|32|60|\-[2-7]|i\-)|qtek|r380|r600|raks|rim9|ro(ve|zo)|s55\/|sa(ge|ma|mm|ms|ny|va)|sc(01|h\-|oo|p\-)|sdk\/|se(c(\-|0|1)|47|mc|nd|ri)|sgh\-|shar|sie(\-|m)|sk\-0|sl(45|id)|sm(al|ar|b3|it|t5)|so(ft|ny)|sp(01|h\-|v\-|v )|sy(01|mb)|t2(18|50)|t6(00|10|18)|ta(gt|lk)|tcl\-|tdg\-|tel(i|m)|tim\-|t\-mo|to(pl|sh)|ts(70|m\-|m3|m5)|tx\-9|up(\.b|g1|si)|utst|v400|v750|veri|vi(rg|te)|vk(40|5[0-3]|\-v)|vm40|voda|vulc|vx(52|53|60|61|70|80|81|83|85|98)|w3c(\-| )|webc|whit|wi(g |nc|nw)|wmlb|wonu|x700|yas\-|your|zeto|zte\-/i.test(a.substr(0,4)))check = true})(navigator.userAgent||navigator.vendor||window.opera);
    if( check ) 
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
          document.getElementById("mainTable").innerHTML = xmlhttp.responseText;
        }
      }

      xmlhttp.open("GET", "display/MakeCCPicksMobile.php", true);
      xmlhttp.send();
    }
  </script>
