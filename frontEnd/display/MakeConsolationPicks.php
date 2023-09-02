      <span>Making Picks for Consolation Pool</span>
      <form action="helpers/changeAccountDetails.php" method="post" id="cbmForm" target="taskWindow" style="display:inline-block;margin-left:200px">
        <input type="hidden" name="task" value="cbm" />
        <input type="hidden" name="acctCBM" id="cbmVal" value="<?php echo (($_SESSION["cbm"] ?? "N") == "Y") ? "N" : "Y"; ?>" />
        <button onClick="$('#cbmForm').submit();">Alternate Colors</button>
      </form>
      <br/>
      <br/>
      <span style="font-size:20px;">Click your selection for the winner of each game to move them to the next round!</span>
      <br/>
      <table style="width:100%; border-spacing:0px; text-align:center; font-size: 14px;">
        <tr><td class="noBorder" colspan=9>&nbsp;</td></tr>
        <tr>
          <td style="width:8%; font-size:18px;" class="noBorder">Wild Card</td>
          <td style="width:2%;" class="noBorder">&nbsp;</td>
          <td style="width:13%; font-size:18px;" class="noBorder">Divisional</td>
          <td style="width:2%;" class="noBorder">&nbsp;</td>
          <td style="width:18%; font-size:18px;" class="noBorder">Conference Championship</td>
          <td style="width:2%;" class="noBorder">&nbsp;</td>
          <td style="width:23%; font-size:18px;" class="noBorder">Super Bowl</td>
          <td style="width:2%;" class="noBorder">&nbsp;</td>
          <td style="width:30%; font-size:18px;" class="noBorder">Champion</td>
        </tr>
        <tr style="height:15px"><td class="noBorder" colspan="9">&nbsp;</td></tr>
<?php
  // set the palette
  $palette = ($_SESSION["cbm"] ?? false) ? ["#0072B2","#000000","#D55E00"] : ["#007500","#888800","#BF0000"];
  $palette2 = ($_SESSION["cbm"] ?? false) ? ["#42BBFF","#888888","#FF913B"] : ["#00AA00","#FFFF00","#FF0000"];

  $playoffGames = RunQuery( "select * from Game where weekNumber>18 and season=" . $result["season"] .
                            " order by weekNumber, gameID" );
  $myPicks = RunQuery( "select * from ConsolationResult join Session using (userID) where season=" .
                       $result["season"] . " and sessionID=" . $_SESSION["spsID"] );

  // get the listing of games
  $afcWC1 = $playoffGames[0];
  $afcWC2 = $playoffGames[1];
  $afcWC3 = $playoffGames[2];
  $nfcWC1 = $playoffGames[3];
  $nfcWC2 = $playoffGames[4];
  $nfcWC3 = $playoffGames[5];

  // we need to worry about the seeding here
  $afcDiv1 = $playoffGames[6];
  $afcDiv2 = $playoffGames[7];
  // swap if necessary
  $WCswapAFC = ($myPicks[0]["wc1AFC"] == "" || $myPicks[0]["wc1AFC"] == $afcWC1["homeTeam"]);
  if( $WCswapAFC )
  {
    $afcDiv2["homeTeam"] = $myPicks[0]["wc1AFC"];
    if( $myPicks[0]["wc2AFC"] == "" || $myPicks[0]["wc2AFC"] == $afcWC2["homeTeam"] ) {
      $afcDiv2["awayTeam"] = $myPicks[0]["wc2AFC"];
      $afcDiv1["awayTeam"] = $myPicks[0]["wc3AFC"];
    } else {
      $afcDiv2["awayTeam"] = $myPicks[0]["wc3AFC"];
      $afcDiv1["awayTeam"] = $myPicks[0]["wc2AFC"];
    }
  
    $temp = $afcDiv1;
    $afcDiv1 = $afcDiv2;
    $afcDiv2 = $temp;
  } else {
    $afcDiv1["awayTeam"] = $myPicks[0]["wc1AFC"];
    if( $myPicks[0]["wc2AFC"] == "" || $myPicks[0]["wc2AFC"] == $afcWC2["homeTeam"] ) {
      $afcDiv2["homeTeam"] = $myPicks[0]["wc2AFC"];
      $afcDiv2["awayTeam"] = $myPicks[0]["wc3AFC"];
    } else {
      $afcDiv2["homeTeam"] = $myPicks[0]["wc3AFC"];
      $afcDiv2["awayTeam"] = $myPicks[0]["wc2AFC"];
    }
  }
  $nfcDiv1 = $playoffGames[8];
  $nfcDiv2 = $playoffGames[9];
  // swap if necessary
  $WCswapNFC = ($myPicks[0]["wc1NFC"] == "" || $myPicks[0]["wc1NFC"] == $nfcWC1["homeTeam"]);
  if( $WCswapNFC )
  {
    $nfcDiv2["homeTeam"] = $myPicks[0]["wc1NFC"];
    if( $myPicks[0]["wc2NFC"] == "" || $myPicks[0]["wc2NFC"] == $nfcWC2["homeTeam"] ) {
      $nfcDiv2["awayTeam"] = $myPicks[0]["wc2NFC"];
      $nfcDiv1["awayTeam"] = $myPicks[0]["wc3NFC"];
    } else {
      $nfcDiv2["awayTeam"] = $myPicks[0]["wc3NFC"];
      $nfcDiv1["awayTeam"] = $myPicks[0]["wc2NFC"];
    }
  
    $temp = $nfcDiv1;
    $nfcDiv1 = $nfcDiv2;
    $nfcDiv2 = $temp;
  } else {
    $nfcDiv1["awayTeam"] = $myPicks[0]["wc1NFC"];
    if( $myPicks[0]["wc2NFC"] == "" || $myPicks[0]["wc2NFC"] == $nfcWC2["homeTeam"] ) {
      $nfcDiv2["homeTeam"] = $myPicks[0]["wc2NFC"];
      $nfcDiv2["awayTeam"] = $myPicks[0]["wc3NFC"];
    } else {
      $nfcDiv2["homeTeam"] = $myPicks[0]["wc3NFC"];
      $nfcDiv2["awayTeam"] = $myPicks[0]["wc2NFC"];
    }
  }

  // grab conference championships
  $afcCC = $playoffGames[10];
  $nfcCC = $playoffGames[11];

  // push their picked winners along
  if( $myPicks[0]["div1AFC"] == ($WCswapAFC ? $afcDiv2["homeTeam"] : $afcDiv1["homeTeam"]) )
  {
    $afcCC["homeTeam"] = $myPicks[0]["div1AFC"];
    $afcCC["awayTeam"] = $myPicks[0]["div2AFC"];
  }
  else
  {
    $afcCC["homeTeam"] = $myPicks[0]["div2AFC"];
    $afcCC["awayTeam"] = $myPicks[0]["div1AFC"];
  }
  if( $myPicks[0]["div1NFC"] == ($WCswapNFC ? $nfcDiv2["homeTeam"] : $nfcDiv1["homeTeam"]) )
  {
    $nfcCC["homeTeam"] = $myPicks[0]["div1NFC"];
    $nfcCC["awayTeam"] = $myPicks[0]["div2NFC"];
  }
  else
  {
    $nfcCC["homeTeam"] = $myPicks[0]["div2NFC"];
    $nfcCC["awayTeam"] = $myPicks[0]["div1NFC"];
  }

  // grab super bowl
  $superBowl = $playoffGames[12];

  // push their picked winners along
  $superBowl["homeTeam"] = $myPicks[0]["confNFC"];
  $superBowl["awayTeam"] = $myPicks[0]["confAFC"];
?>
        <tr>
          <td class="noBorder" rowspan=2>
            <span id="afcWC1A" style="font-size:24px;color:<?php
  echo (($myPicks[0]["wc1AFC"] == "") ? "#D9DCE3" : (($myPicks[0]["wc1AFC"] == $afcWC1["awayTeam"]) ? $palette[0] :
      (($myPicks[0]["wc1AFC"] == $afcWC1["homeTeam"]) ? $palette[2] : "#D9DCE3"))) . ";\">" . $teamAliases[$afcWC1["awayTeam"]] .
      "</span><br>\n            <div class=\"imgDiv mouseEnabled\" style=\"cursor:pointer;\" onClick=\"MakePick('afcWC1A');" .
      " ToggleSaveButton();\"><img class=\"teamLogo\" id=\"afcWC1A_IMG\" src=\"" . getIcon($afcWC1["awayTeam"], $result["season"]); 
  ?>" draggable="false" ondragstart="return false;" /></div>
            <span id="afcWC1H" style="font-size:24px;color:<?php
  echo (($myPicks[0]["wc1AFC"] == "") ? "#D9DCE3" : (($myPicks[0]["wc1AFC"] == $afcWC1["homeTeam"]) ? $palette[0] :
      (($myPicks[0]["wc1AFC"] == $afcWC1["awayTeam"]) ? $palette[2] : "#D9DCE3"))) . ";\">@ " . $teamAliases[$afcWC1["homeTeam"]] .
      "</span><br>\n            <div class=\"imgDiv mouseEnabled\" style=\"cursor:pointer;\" onClick=\"MakePick('afcWC1H');" .
      " ToggleSaveButton();\"><img class=\"teamLogo\" id=\"afcWC1H_IMG\" src=\"" . getIcon($afcWC1["homeTeam"], $result["season"]);
  ?>" draggable="false" ondragstart="return false;" /></div><br><br>
          </td>
          <td class="noBorder">&nbsp;</td>
          <td class="noBorder" rowspan=3>
            <span id="afcDiv1A" style="font-size:36px;color:<?php
  $pickToCompare = ($WCswapAFC ? $myPicks[0]["div2AFC"] : $myPicks[0]["div1AFC"]);
  echo (($pickToCompare == "") ? "#D9DCE3" : (($pickToCompare == $afcDiv1["awayTeam"]) ? $palette[0] :
      (($pickToCompare == $afcDiv1["homeTeam"]) ? $palette[2] : "#D9DCE3"))) . ";\">" . $teamAliases[$afcDiv1["awayTeam"]] .
      "</span><br>\n            <div class=\"imgDiv mouseEnabled\" style=\"cursor:pointer;\" onClick=\"MakePick('afcDiv1A');" .
      " ToggleSaveButton();\"><img class=\"teamLogo\" id=\"afcDiv1A_IMG\" src=\"" . getIcon($afcDiv1["awayTeam"], $result["season"]); 
  ?>" draggable="false" ondragstart="return false;" /></div>
            <span id="afcDiv1H" style="font-size:36px;color:<?php
  echo (($pickToCompare == "") ? "#D9DCE3" : (($pickToCompare == $afcDiv1["homeTeam"]) ? $palette[0] :
      (($pickToCompare == $afcDiv1["awayTeam"]) ? $palette[2] : "#D9DCE3"))) . ";\">@ " . $teamAliases[$afcDiv1["homeTeam"]] .
      "</span><br>\n            <div class=\"imgDiv mouseEnabled\" style=\"cursor:pointer;\" onClick=\"MakePick('afcDiv1H');" .
      " ToggleSaveButton();\"><img class=\"teamLogo\" id=\"afcDiv1H_IMG\" src=\"" . getIcon($afcDiv1["homeTeam"], $result["season"]);
  ?>" draggable="false" ondragstart="return false;" /></div><br><br>
          </td>
          <td class="noBorder">&nbsp;</td>
          <td class="noBorder" rowspan=6>
            <span id="afcCCA" style="font-size:48px;color:<?php
  echo (($myPicks[0]["confAFC"] == "") ? "#D9DCE3" : (($myPicks[0]["confAFC"] == $afcCC["awayTeam"]) ? $palette[0] :
      (($myPicks[0]["confAFC"] == $afcCC["homeTeam"]) ? $palette[2] : "#D9DCE3"))) . ";\">" . $teamAliases[$afcCC["awayTeam"]] .
      "</span><br>\n            <div class=\"imgDiv mouseEnabled\" style=\"cursor:pointer;\" onClick=\"MakePick('afcCCA');" .
      " ToggleSaveButton();\"><img class=\"teamLogo\" id=\"afcCCA_IMG\" src=\"" . getIcon($afcCC["awayTeam"], $result["season"]); 
  ?>" draggable="false" ondragstart="return false;" /></div>
            <span id="afcCCH" style="font-size:48px;color:<?php
  echo (($myPicks[0]["confAFC"] == "") ? "#D9DCE3" : (($myPicks[0]["confAFC"] == $afcCC["homeTeam"]) ? $palette[0] :
      (($myPicks[0]["confAFC"] == $afcCC["awayTeam"]) ? $palette[2] : "#D9DCE3"))) . ";\">@ " . $teamAliases[$afcCC["homeTeam"]] .
      "</span><br>\n            <div class=\"imgDiv mouseEnabled\" style=\"cursor:pointer;\" onClick=\"MakePick('afcCCH');" .
      " ToggleSaveButton();\"><img class=\"teamLogo\" id=\"afcCCH_IMG\" src=\"" . getIcon($afcCC["homeTeam"], $result["season"]);
  ?>" draggable="false" ondragstart="return false;" /></div><br><br>
          </td>
          <td class="noBorder">&nbsp;</td>
          <td class="noBorder" rowspan=12>
            <span id="SBA" style="font-size:60px;color:<?php
  echo (($myPicks[0]["superBowl"] == "") ? "#D9DCE3" : (($myPicks[0]["superBowl"] == $superBowl["awayTeam"]) ? $palette[0] :
      (($myPicks[0]["superBowl"] == $superBowl["homeTeam"]) ? $palette[2] : "#D9DCE3"))) . ";\">" . $teamAliases[$superBowl["awayTeam"]] .
      "</span><br>\n            <div class=\"imgDiv mouseEnabled\" style=\"cursor:pointer;\" onClick=\"MakePick('SBA');" .
      " ToggleSaveButton();\"><img class=\"teamLogo\" id=\"SBA_IMG\" src=\"" . getIcon($superBowl["awayTeam"], $result["season"]); 
  ?>" draggable="false" ondragstart="return false;" /></div>
            <span id="SBH" style="font-size:60px;color:<?php
  echo (($myPicks[0]["superBowl"] == "") ? "#D9DCE3" : (($myPicks[0]["superBowl"] == $superBowl["homeTeam"]) ? $palette[0] :
      (($myPicks[0]["superBowl"] == $superBowl["awayTeam"]) ? $palette[2] : "#D9DCE3"))) . ";\">" . $teamAliases[$superBowl["homeTeam"]] .
      "</span><br>\n            <div class=\"imgDiv mouseEnabled\" style=\"cursor:pointer;\" onClick=\"MakePick('SBH');" .
      " ToggleSaveButton();\"><img class=\"teamLogo\" id=\"SBH_IMG\" src=\"" . getIcon($superBowl["homeTeam"], $result["season"]);
  ?>" draggable="false" ondragstart="return false;" /></div><br><br>
          </td>
          <td class="noBorder">&nbsp;</td>
          <td class="noBorder" rowspan=12>
            <span id="superBowlChampion" style="font-size:72px;color:<?php
  echo (($myPicks[0]["superBowl"] != "") ? $palette[0] : "#D9DCE3") . ";\">Champion:<br>" . $teamAliases[$myPicks[0]["superBowl"]];
  ?></span><br>
            <div class="imgDiv mouseEnabled"><img class="teamLogo" id="superBowlChampion_IMG" src="<?php 
  echo getIcon($myPicks[0]["superBowl"], $result["season"]); 
  ?>" draggable="false" ondragstart="return false;" /></div>
            <form action="." method="post" id="makePicksForm">
              <input type="hidden" id="picksType" name="picksType" value="consolation">
              <input type="hidden" id="windowScrollPos" name="windowScrollPos" value="0">
              <input type="hidden" id="afcWC1" name="afcWC1" value="<?php echo $myPicks[0]["wc1AFC"]; ?>">
              <input type="hidden" id="afcWC2" name="afcWC2" value="<?php echo $myPicks[0]["wc2AFC"]; ?>">
              <input type="hidden" id="afcWC3" name="afcWC3" value="<?php echo $myPicks[0]["wc3AFC"]; ?>">
              <input type="hidden" id="nfcWC1" name="nfcWC1" value="<?php echo $myPicks[0]["wc1NFC"]; ?>">
              <input type="hidden" id="nfcWC2" name="nfcWC2" value="<?php echo $myPicks[0]["wc2NFC"]; ?>">
              <input type="hidden" id="nfcWC3" name="nfcWC3" value="<?php echo $myPicks[0]["wc3NFC"]; ?>">
              <input type="hidden" id="afcDiv1" name="afcDiv1" value="<?php echo $myPicks[0]["div1AFC"]; ?>">
              <input type="hidden" id="afcDiv2" name="afcDiv2" value="<?php echo $myPicks[0]["div2AFC"]; ?>">
              <input type="hidden" id="nfcDiv1" name="nfcDiv1" value="<?php echo $myPicks[0]["div1NFC"]; ?>">
              <input type="hidden" id="nfcDiv2" name="nfcDiv2" value="<?php echo $myPicks[0]["div2NFC"]; ?>">
              <input type="hidden" id="afcCC" name="afcCC" value="<?php echo $myPicks[0]["confAFC"]; ?>">
              <input type="hidden" id="nfcCC" name="nfcCC" value="<?php echo $myPicks[0]["confNFC"]; ?>">
              <input type="hidden" id="SB" name="SB" value="<?php echo $myPicks[0]["superBowl"]; ?>">
              <br><span style="font-size:20px;">Combined Super Bowl Score</span>
              <input id="tieBreaker" name="tieBreaker" type="text" maxlength="3" onKeyUp="NumbersOnly('tieBreaker'); ToggleSaveButton();" value="<?php echo $myPicks[0]["tieBreaker"]; ?>" style="width:35px;" /><br>
              <button id="saveRosterButton" onclick="document.getElementById('windowScrollPos').value = $(window).scrollTop(); document.getElementById('makePicksForm').submit();" disabled style="font-size:20px;">Save Picks</button>
            </form>
          </td>
        </tr>
        <tr></tr>
        <tr>
          <td class="noBorder" rowspan=2>
            <span id="afcWC2A" style="font-size:24px;color:<?php
  echo (($myPicks[0]["wc2AFC"] == "") ? "#D9DCE3" : (($myPicks[0]["wc2AFC"] == $afcWC2["awayTeam"]) ? $palette[0] :
      (($myPicks[0]["wc2AFC"] == $afcWC2["homeTeam"]) ? $palette[2] : "#D9DCE3"))) . ";\">" . $teamAliases[$afcWC2["awayTeam"]] .
      "</span><br>\n            <div class=\"imgDiv mouseEnabled\" style=\"cursor:pointer;\" onClick=\"MakePick('afcWC2A');" .
      " ToggleSaveButton();\"><img class=\"teamLogo\" id=\"afcWC2A_IMG\" src=\"" . getIcon($afcWC2["awayTeam"], $result["season"]); 
  ?>" draggable="false" ondragstart="return false;" /></div>
            <span id="afcWC2H" style="font-size:24px;color:<?php
  echo (($myPicks[0]["wc2AFC"] == "") ? "#D9DCE3" : (($myPicks[0]["wc2AFC"] == $afcWC2["homeTeam"]) ? $palette[0] :
      (($myPicks[0]["wc2AFC"] == $afcWC2["awayTeam"]) ? $palette[2] : "#D9DCE3"))) . ";\">@ " . $teamAliases[$afcWC2["homeTeam"]] .
      "</span><br>\n            <div class=\"imgDiv mouseEnabled\" style=\"cursor:pointer;\" onClick=\"MakePick('afcWC2H');" .
      " ToggleSaveButton();\"><img class=\"teamLogo\" id=\"afcWC2H_IMG\" src=\"" . getIcon($afcWC2["homeTeam"], $result["season"]);
  ?>" draggable="false" ondragstart="return false;" /></div><br><br>
          </td>
          <td class="noBorder" colspan=8>&nbsp;</td>
        </tr>
        <tr>
          <td class="noBorder">&nbsp;</td>
          <td class="noBorder" rowspan=3>
            <span id="afcDiv2A" style="font-size:36px;color:<?php
  $pickToCompare = ($WCswapAFC ? $myPicks[0]["div1AFC"] : $myPicks[0]["div2AFC"]);
  echo (($pickToCompare == "") ? "#D9DCE3" : (($pickToCompare == $afcDiv2["awayTeam"]) ? $palette[0] :
      (($pickToCompare == $afcDiv2["homeTeam"]) ? $palette[2] : "#D9DCE3"))) . ";\">" . $teamAliases[$afcDiv2["awayTeam"]] .
      "</span><br>\n            <div class=\"imgDiv mouseEnabled\" style=\"cursor:pointer;\" onClick=\"MakePick('afcDiv2A');" .
      " ToggleSaveButton();\"><img class=\"teamLogo\" id=\"afcDiv2A_IMG\" src=\"" . getIcon($afcDiv2["awayTeam"], $result["season"]); 
  ?>" draggable="false" ondragstart="return false;" /></div>
            <span id="afcDiv2H" style="font-size:36px;color:<?php
  echo (($pickToCompare == "") ? "#D9DCE3" : (($pickToCompare == $afcDiv2["homeTeam"]) ? $palette[0] :
      (($pickToCompare == $afcDiv2["awayTeam"]) ? $palette[2] : "#D9DCE3"))) . ";\">@ " . $teamAliases[$afcDiv2["homeTeam"]] .
      "</span><br>\n            <div class=\"imgDiv mouseEnabled\" style=\"cursor:pointer;\" onClick=\"MakePick('afcDiv2H');" .
      " ToggleSaveButton();\"><img class=\"teamLogo\" id=\"afcDiv2H_IMG\" src=\"" . getIcon($afcDiv2["homeTeam"], $result["season"]);
  ?>" draggable="false" ondragstart="return false;" /></div><br><br>
          </td>
          <td class="noBorder" colspan=6>&nbsp;</td>
        </tr>
        <tr>
          <td class="noBorder" rowspan=2>
            <span id="afcWC3A" style="font-size:24px;color:<?php
  echo (($myPicks[0]["wc3AFC"] == "") ? "#D9DCE3" : (($myPicks[0]["wc3AFC"] == $afcWC3["awayTeam"]) ? $palette[0] :
      (($myPicks[0]["wc3AFC"] == $afcWC3["homeTeam"]) ? $palette[2] : "#D9DCE3"))) . ";\">" . $teamAliases[$afcWC3["awayTeam"]] .
      "</span><br>\n            <div class=\"imgDiv mouseEnabled\" style=\"cursor:pointer;\" onClick=\"MakePick('afcWC3A');" .
      " ToggleSaveButton();\"><img class=\"teamLogo\" id=\"afcWC3A_IMG\" src=\"" . getIcon($afcWC3["awayTeam"], $result["season"]); 
  ?>" draggable="false" ondragstart="return false;" /></div>
            <span id="afcWC3H" style="font-size:24px;color:<?php
  echo (($myPicks[0]["wc3AFC"] == "") ? "#D9DCE3" : (($myPicks[0]["wc3AFC"] == $afcWC3["homeTeam"]) ? $palette[0] :
      (($myPicks[0]["wc3AFC"] == $afcWC3["awayTeam"]) ? $palette[2] : "#D9DCE3"))) . ";\">@ " . $teamAliases[$afcWC3["homeTeam"]] .
      "</span><br>\n            <div class=\"imgDiv mouseEnabled\" style=\"cursor:pointer;\" onClick=\"MakePick('afcWC3H');" .
      " ToggleSaveButton();\"><img class=\"teamLogo\" id=\"afcWC3H_IMG\" src=\"" . getIcon($afcWC3["homeTeam"], $result["season"]);
  ?>" draggable="false" ondragstart="return false;" /></div><br><br>
          </td>
          <td class="noBorder" colspan=8>&nbsp;</td>
        </tr>
        <tr></tr>
        <tr>
          <td class="noBorder" rowspan=2>
            <span id="nfcWC1A" style="font-size:24px;color:<?php
  echo (($myPicks[0]["wc1NFC"] == "") ? "#D9DCE3" : (($myPicks[0]["wc1NFC"] == $nfcWC1["awayTeam"]) ? $palette[0] :
      (($myPicks[0]["wc1NFC"] == $nfcWC1["homeTeam"]) ? $palette[2] : "#D9DCE3"))) . ";\">" . $teamAliases[$nfcWC1["awayTeam"]] .
      "</span><br>\n            <div class=\"imgDiv mouseEnabled\" style=\"cursor:pointer;\" onClick=\"MakePick('nfcWC1A');" .
      " ToggleSaveButton();\"><img class=\"teamLogo\" id=\"nfcWC1A_IMG\" src=\"" . getIcon($nfcWC1["awayTeam"], $result["season"]); 
  ?>" draggable="false" ondragstart="return false;" /></div>
            <span id="nfcWC1H" style="font-size:24px;color:<?php
  echo (($myPicks[0]["wc1NFC"] == "") ? "#D9DCE3" : (($myPicks[0]["wc1NFC"] == $nfcWC1["homeTeam"]) ? $palette[0] :
      (($myPicks[0]["wc1NFC"] == $nfcWC1["awayTeam"]) ? $palette[2] : "#D9DCE3"))) . ";\">@ " . $teamAliases[$nfcWC1["homeTeam"]] .
      "</span><br>\n            <div class=\"imgDiv mouseEnabled\" style=\"cursor:pointer;\" onClick=\"MakePick('nfcWC1H');" .
      " ToggleSaveButton();\"><img class=\"teamLogo\" id=\"nfcWC1H_IMG\" src=\"" . getIcon($nfcWC1["homeTeam"], $result["season"]);
  ?>" draggable="false" ondragstart="return false;" /></div><br><br>
          </td>
          <td class="noBorder">&nbsp;</td>
          <td class="noBorder" rowspan=3>
            <span id="nfcDiv1A" style="font-size:36px;color:<?php
  $pickToCompare = ($WCswapNFC ? $myPicks[0]["div2NFC"] : $myPicks[0]["div1NFC"]);
  echo (($pickToCompare == "") ? "#D9DCE3" : (($pickToCompare == $nfcDiv1["awayTeam"]) ? $palette[0] :
      (($pickToCompare == $nfcDiv1["homeTeam"]) ? $palette[2] : "#D9DCE3"))) . ";\">" . $teamAliases[$nfcDiv1["awayTeam"]] .
      "</span><br>\n            <div class=\"imgDiv mouseEnabled\" style=\"cursor:pointer;\" onClick=\"MakePick('nfcDiv1A');" .
      " ToggleSaveButton();\"><img class=\"teamLogo\" id=\"nfcDiv1A_IMG\" src=\"" . getIcon($nfcDiv1["awayTeam"], $result["season"]); 
  ?>" draggable="false" ondragstart="return false;" /></div>
            <span id="nfcDiv1H" style="font-size:36px;color:<?php
  echo (($pickToCompare == "") ? "#D9DCE3" : (($pickToCompare == $nfcDiv1["homeTeam"]) ? $palette[0] :
      (($pickToCompare == $nfcDiv1["awayTeam"]) ? $palette[2] : "#D9DCE3"))) . ";\">@ " . $teamAliases[$nfcDiv1["homeTeam"]] .
      "</span><br>\n            <div class=\"imgDiv mouseEnabled\" style=\"cursor:pointer;\" onClick=\"MakePick('nfcDiv1H');" .
      " ToggleSaveButton();\"><img class=\"teamLogo\" id=\"nfcDiv1H_IMG\" src=\"" . getIcon($nfcDiv1["homeTeam"], $result["season"]);
  ?>" draggable="false" ondragstart="return false;" /></div><br><br>
          </td>
          <td class="noBorder">&nbsp;</td>
          <td class="noBorder" rowspan=6>
            <span id="nfcCCA" style="font-size:48px;color:<?php
  echo (($myPicks[0]["confNFC"] == "") ? "#D9DCE3" : (($myPicks[0]["confNFC"] == $nfcCC["awayTeam"]) ? $palette[0] :
      (($myPicks[0]["confNFC"] == $nfcCC["homeTeam"]) ? $palette[2] : "#D9DCE3"))) . ";\">" . $teamAliases[$nfcCC["awayTeam"]] .
      "</span><br>\n            <div class=\"imgDiv mouseEnabled\" style=\"cursor:pointer;\" onClick=\"MakePick('nfcCCA');" .
      " ToggleSaveButton();\"><img class=\"teamLogo\" id=\"nfcCCA_IMG\" src=\"" . getIcon($nfcCC["awayTeam"], $result["season"]); 
  ?>" draggable="false" ondragstart="return false;" /></div>
            <span id="nfcCCH" style="font-size:48px;color:<?php
  echo (($myPicks[0]["confNFC"] == "") ? "#D9DCE3" : (($myPicks[0]["confNFC"] == $nfcCC["homeTeam"]) ? $palette[0] :
      (($myPicks[0]["confNFC"] == $nfcCC["awayTeam"]) ? $palette[2] : "#D9DCE3"))) . ";\">@ " . $teamAliases[$nfcCC["homeTeam"]] .
      "</span><br>\n            <div class=\"imgDiv mouseEnabled\" style=\"cursor:pointer;\" onClick=\"MakePick('nfcCCH');" .
      " ToggleSaveButton();\"><img class=\"teamLogo\" id=\"nfcCCH_IMG\" src=\"" . getIcon($nfcCC["homeTeam"], $result["season"]);
  ?>" draggable="false" ondragstart="return false;" /></div><br><br>
          </td>
          <td class="noBorder" colspan=4>&nbsp;</td>
        </tr>
        <tr></tr>
        <tr>
          <td class="noBorder" rowspan=2>
            <span id="nfcWC2A" style="font-size:24px;color:<?php
  echo (($myPicks[0]["wc2NFC"] == "") ? "#D9DCE3" : (($myPicks[0]["wc2NFC"] == $nfcWC2["awayTeam"]) ? $palette[0] :
      (($myPicks[0]["wc2NFC"] == $nfcWC2["homeTeam"]) ? $palette[2] : "#D9DCE3"))) . ";\">" . $teamAliases[$nfcWC2["awayTeam"]] .
      "</span><br>\n            <div class=\"imgDiv mouseEnabled\" style=\"cursor:pointer;\" onClick=\"MakePick('nfcWC2A');" .
      " ToggleSaveButton();\"><img class=\"teamLogo\" id=\"nfcWC2A_IMG\" src=\"" . getIcon($nfcWC2["awayTeam"], $result["season"]); 
  ?>" draggable="false" ondragstart="return false;" /></div>
            <span id="nfcWC2H" style="font-size:24px;color:<?php
  echo (($myPicks[0]["wc2NFC"] == "") ? "#D9DCE3" : (($myPicks[0]["wc2NFC"] == $nfcWC2["homeTeam"]) ? $palette[0] :
      (($myPicks[0]["wc2NFC"] == $nfcWC2["awayTeam"]) ? $palette[2] : "#D9DCE3"))) . ";\">@ " . $teamAliases[$nfcWC2["homeTeam"]] .
      "</span><br>\n            <div class=\"imgDiv mouseEnabled\" style=\"cursor:pointer;\" onClick=\"MakePick('nfcWC2H');" .
      " ToggleSaveButton();\"><img class=\"teamLogo\" id=\"nfcWC2H_IMG\" src=\"" . getIcon($nfcWC2["homeTeam"], $result["season"]);
  ?>" draggable="false" ondragstart="return false;" /></div><br><br>
          </td>
          <td class="noBorder" colspan=8>&nbsp;</td>
        </tr>
        <tr>
          <td class="noBorder">&nbsp;</td>
          <td class="noBorder" rowspan=3>
            <span id="nfcDiv2A" style="font-size:36px;color:<?php
  $pickToCompare = ($WCswapNFC ? $myPicks[0]["div1NFC"] : $myPicks[0]["div2NFC"]);
  echo (($pickToCompare == "") ? "#D9DCE3" : (($pickToCompare == $nfcDiv2["awayTeam"]) ? $palette[0] :
      (($pickToCompare == $nfcDiv2["homeTeam"]) ? $palette[2] : "#D9DCE3"))) . ";\">" . $teamAliases[$nfcDiv2["awayTeam"]] .
      "</span><br>\n            <div class=\"imgDiv mouseEnabled\" style=\"cursor:pointer;\" onClick=\"MakePick('nfcDiv2A');" .
      " ToggleSaveButton();\"><img class=\"teamLogo\" id=\"nfcDiv2A_IMG\" src=\"" . getIcon($nfcDiv2["awayTeam"], $result["season"]); 
  ?>" draggable="false" ondragstart="return false;" /></div>
            <span id="nfcDiv2H" style="font-size:36px;color:<?php
  echo (($pickToCompare == "") ? "#D9DCE3" : (($pickToCompare == $nfcDiv2["homeTeam"]) ? $palette[0] :
      (($pickToCompare == $nfcDiv2["awayTeam"]) ? $palette[2] : "#D9DCE3"))) . ";\">@ " . $teamAliases[$nfcDiv2["homeTeam"]] .
      "</span><br>\n            <div class=\"imgDiv mouseEnabled\" style=\"cursor:pointer;\" onClick=\"MakePick('nfcDiv2H');" .
      " ToggleSaveButton();\"><img class=\"teamLogo\" id=\"nfcDiv2H_IMG\" src=\"" . getIcon($nfcDiv2["homeTeam"], $result["season"]);
  ?>" draggable="false" ondragstart="return false;" /></div><br><br>
          </td>
          <td class="noBorder" colspan=6>&nbsp;</td>
        </tr>
        <tr>
          <td class="noBorder" rowspan=2>
            <span id="nfcWC3A" style="font-size:24px;color:<?php
  echo (($myPicks[0]["wc3NFC"] == "") ? "#D9DCE3" : (($myPicks[0]["wc3NFC"] == $nfcWC3["awayTeam"]) ? $palette[0] :
      (($myPicks[0]["wc3NFC"] == $nfcWC3["homeTeam"]) ? $palette[2] : "#D9DCE3"))) . ";\">" . $teamAliases[$nfcWC3["awayTeam"]] .
      "</span><br>\n            <div class=\"imgDiv mouseEnabled\" style=\"cursor:pointer;\" onClick=\"MakePick('nfcWC3A');" .
      " ToggleSaveButton();\"><img class=\"teamLogo\" id=\"nfcWC3A_IMG\" src=\"" . getIcon($nfcWC3["awayTeam"], $result["season"]); 
  ?>" draggable="false" ondragstart="return false;" /></div>
            <span id="nfcWC3H" style="font-size:24px;color:<?php
  echo (($myPicks[0]["wc3NFC"] == "") ? "#D9DCE3" : (($myPicks[0]["wc3NFC"] == $nfcWC3["homeTeam"]) ? $palette[0] :
      (($myPicks[0]["wc3NFC"] == $nfcWC3["awayTeam"]) ? $palette[2] : "#D9DCE3"))) . ";\">@ " . $teamAliases[$nfcWC3["homeTeam"]] .
      "</span><br>\n            <div class=\"imgDiv mouseEnabled\" style=\"cursor:pointer;\" onClick=\"MakePick('nfcWC3H');" .
      " ToggleSaveButton();\"><img class=\"teamLogo\" id=\"nfcWC3H_IMG\" src=\"" . getIcon($nfcWC3["homeTeam"], $result["season"]);
  ?>" draggable="false" ondragstart="return false;" /></div><br><br>
          </td>
          <td class="noBorder" colspan=8>&nbsp;</td>
        </tr>
        <tr></tr>
      </table>
