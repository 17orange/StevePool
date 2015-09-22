    <script type="text/javascript">
      var emptyImg = "includes/nfl.png";
<?php
  // fill in the seed data
  $afcSeed1 = RunQuery( "select homeTeam from Game where season=" . $result["season"] . 
                        " and weekNumber=19 order by gameID limit 0,1" );
  $afcSeed2 = RunQuery( "select homeTeam from Game where season=" . $result["season"] . 
                        " and weekNumber=19 order by gameID limit 1,1" );
  $afcWCGame1 = RunQuery( "select homeTeam, awayTeam from Game where season=" . $result["season"] .
                          " and weekNumber=18 order by gameID limit 0,1" );
  $afcWCGame2 = RunQuery( "select homeTeam, awayTeam from Game where season=" . $result["season"] .
                          " and weekNumber=18 order by gameID limit 1,1" );
  $afcSeed1 = $afcSeed1[0]["homeTeam"];
  $afcSeed2 = $afcSeed2[0]["homeTeam"];
  $afcSeed3 = $afcWCGame1[0]["homeTeam"];
  $afcSeed4 = $afcWCGame2[0]["homeTeam"];
  $afcSeed5 = $afcWCGame2[0]["awayTeam"];
  $afcSeed6 = $afcWCGame1[0]["awayTeam"];
  $nfcSeed1 = RunQuery( "select homeTeam from Game where season=" . $result["season"] . 
                        " and weekNumber=19 order by gameID limit 2,1" );
  $nfcSeed2 = RunQuery( "select homeTeam from Game where season=" . $result["season"] . 
                        " and weekNumber=19 order by gameID limit 3,1" );
  $nfcWCGame1 = RunQuery( "select homeTeam, awayTeam from Game where season=" . $result["season"] .
                          " and weekNumber=18 order by gameID limit 2,1" );
  $nfcWCGame2 = RunQuery( "select homeTeam, awayTeam from Game where season=" . $result["season"] .
                          " and weekNumber=18 order by gameID limit 3,1" );
  $nfcSeed1 = $nfcSeed1[0]["homeTeam"];
  $nfcSeed2 = $nfcSeed2[0]["homeTeam"];
  $nfcSeed3 = $nfcWCGame1[0]["homeTeam"];
  $nfcSeed4 = $nfcWCGame2[0]["homeTeam"];
  $nfcSeed5 = $nfcWCGame2[0]["awayTeam"];
  $nfcSeed6 = $nfcWCGame1[0]["awayTeam"];

  echo "      var AS1 = '" . $afcSeed1 . "';\n";
  echo "      var AS2 = '" . $afcSeed2 . "';\n";
  echo "      var AS3 = '" . $afcSeed3 . "';\n";
  echo "      var AS4 = '" . $afcSeed4 . "';\n";
  echo "      var AS5 = '" . $afcSeed5 . "';\n";
  echo "      var AS6 = '" . $afcSeed6 . "';\n";
  echo "      var NS1 = '" . $nfcSeed1 . "';\n";
  echo "      var NS2 = '" . $nfcSeed2 . "';\n";
  echo "      var NS3 = '" . $nfcSeed3 . "';\n";
  echo "      var NS4 = '" . $nfcSeed4 . "';\n";
  echo "      var NS5 = '" . $nfcSeed5 . "';\n";
  echo "      var NS6 = '" . $nfcSeed6 . "';\n";
?>

      function MakePick(pickID)
      {
        var team = document.getElementById(pickID).innerHTML;
        if( pickID.substring(pickID.length - 1) == "H" && pickID != "SBH" )
        {
          team = team.substring(2);
        }
        if( team == "" )
        {
          return;
        }

        if( pickID.substring(1,6) == 'fcWC1' )
        {
          // swap them if we need to 
          var testSeed = (pickID.substring(0,3) == "afc") ? ((pickID.substring(6) == "A") ? AS1 : AS2)
                                                          : ((pickID.substring(6) == "A") ? NS1 : NS2);
          if( document.getElementById(pickID.substring(0,3) + 'Div1H').innerHTML != ("@ " + testSeed) )
          {
            var temp = document.getElementById(pickID.substring(0,3) + 'Div1H').innerHTML;
            document.getElementById(pickID.substring(0,3) + 'Div1H').innerHTML = document.getElementById(pickID.substring(0,3) + 'Div2H').innerHTML;
            document.getElementById(pickID.substring(0,3) + 'Div2H').innerHTML = temp;
            temp = document.getElementById(pickID.substring(0,3) + 'Div1H_IMG').src;
            document.getElementById(pickID.substring(0,3) + 'Div1H_IMG').src = document.getElementById(pickID.substring(0,3) + 'Div2H_IMG').src;
            document.getElementById(pickID.substring(0,3) + 'Div2H_IMG').src = temp;
            CleanPick(pickID.substring(0,3) + 'Div1', true);
            CleanPick(pickID.substring(0,3) + 'Div2', true);
          }

          // make this the pick
          document.getElementById(pickID.substring(0,3) + 'Div1A').innerHTML = team;
          document.getElementById(pickID).style.color = "#007500";
          document.getElementById(pickID.substring(0,6) + ((pickID.substring(6) == "A") ? "H" : "A")).style.color = "#BF0000";
          document.getElementById(pickID.substring(0,3) + 'Div1A_IMG').src = document.getElementById(pickID + '_IMG').src;
        }
        else if( pickID.substring(1,6) == 'fcWC2' )
        {
          // make this the pick
          var targetID = pickID.substring(0,3) + "Div2A";
          var opponentID = pickID.substring(0,6) + ((pickID.substring(6) == "A") ? "H" : "A");
          document.getElementById(targetID).innerHTML = team;
          document.getElementById(pickID).style.color = "#007500";
          document.getElementById(opponentID).style.color = "#BF0000";
          document.getElementById(targetID + '_IMG').src = document.getElementById(pickID + '_IMG').src;
          CleanPick(pickID.substring(0,3) + 'Div2', false);
        }
        else if( pickID.substring(1,6) == 'fcDiv' )
        {
          // clean our opponent (or ourselves) if they are in the next round already
          var opponentID = pickID.substring(0, 7) + ((pickID.substring(7) == "H") ? "A" : "H");
          var opponentTeam = document.getElementById(opponentID).innerHTML;
          if( opponentTeam.substring(0,2) == "@ " )
          {
            opponentTeam = opponentTeam.substring(2);
          }
          if( (document.getElementById(pickID.substring(0,3) + 'CCA').innerHTML == opponentTeam) || 
              (document.getElementById(pickID.substring(0,3) + 'CCA').innerHTML == team) )
          {
            document.getElementById(pickID.substring(0,3) + 'CCA').innerHTML = "";
            document.getElementById(pickID.substring(0,3) + 'CCA_IMG').src = emptyImg;
          }
          else if( (document.getElementById(pickID.substring(0,3) + 'CCH').innerHTML.substring(2) == opponentTeam) ||
                   (document.getElementById(pickID.substring(0,3) + 'CCH').innerHTML.substring(2) == team) )
          {
            document.getElementById(pickID.substring(0,3) + 'CCH').innerHTML = "@ ";
            document.getElementById(pickID.substring(0,3) + 'CCH_IMG').src = emptyImg;
          }

          // see where we are trying to stick this guy
          var targetSlot = pickID.substring(0,3) + ((team == ((pickID.substring(0,3) == 'afc') ? AS1 : NS1)) ? 'CCH' : 
                                                   ((team == ((pickID.substring(0,3) == 'afc') ? AS6 : NS6)) ? 'CCA' : 
                                                    ('CC' + ((pickID.substring(6,7) == "1") ? "A" : "H"))));
          var opponentSlot = pickID.substring(0,3) + 'CC' + ((targetSlot.substring(5) == "H") ? "A" : "H");

          // see if we need to switch them
          var targetOccupied = (document.getElementById(targetSlot).innerHTML.length > 2);
          if( !targetOccupied && (document.getElementById(targetSlot).innerHTML.length == 2) )
          {
            targetOccupied = (document.getElementById(targetSlot).innerHTML.substring(0,1) != "@");
          }
          var opponentOccupied = (document.getElementById(opponentSlot).innerHTML.length > 2);
          if( !opponentOccupied && (document.getElementById(opponentSlot).innerHTML.length == 2) )
          {
            opponentOccupied = (document.getElementById(opponentSlot).innerHTML.substring(0,1) != "@");
          }
          if( targetOccupied || opponentOccupied )
          {
            // find them
            var them = document.getElementById(targetOccupied ? targetSlot : opponentSlot).innerHTML;
            if( them.substring(0,2) == "@ " )
            {
              them = them.substring(2);
            }

            // find seeds
            var mySeed = (pickID.substring(0,3) == 'afc') 
                         ? ((team == AS1) ? 1 : ((team == AS2) ? 2 : ((team == AS3) ? 3 : ((team == AS4) ? 4 : ((team == AS5) ? 5 : 6)))))
                         : ((team == NS1) ? 1 : ((team == NS2) ? 2 : ((team == NS3) ? 3 : ((team == NS4) ? 4 : ((team == NS5) ? 5 : 6)))));
            var theirSeed = (pickID.substring(0,3) == 'afc') 
                            ? ((them == AS1) ? 1 : ((them == AS2) ? 2 : ((them == AS3) ? 3 : ((them == AS4) ? 4 : ((them == AS5) ? 5 : 6)))))
                            : ((them == NS1) ? 1 : ((them == NS2) ? 2 : ((them == NS3) ? 3 : ((them == NS4) ? 4 : ((them == NS5) ? 5 : 6)))));
            if( ((mySeed < theirSeed) && (targetSlot.substring(5) == "A")) || ((mySeed > theirSeed) && (targetSlot.substring(5) == "H")) )
            {
              targetSlot = opponentSlot;
              opponentSlot = pickID.substring(0,3) + "CC" + ((targetSlot.substring(5) == "H") ? "A": "H");
              targetOccupied = !targetOccupied;
            }

            // if they're in the target, swap them over
            if( targetOccupied )
            {
              document.getElementById(opponentSlot).innerHTML = ((opponentSlot.substring(5) == "H") ? "@ " : "") + them;
              document.getElementById(opponentSlot + "_IMG").src = document.getElementById(targetSlot + "_IMG").src;
            }
          }
          
          document.getElementById(targetSlot).innerHTML = ((targetSlot.substring(5) == "H") ? "@ " : "") + team;
          document.getElementById(pickID).style.color = "#007500";
          document.getElementById(opponentID).style.color = "#BF0000";
          document.getElementById(targetSlot + "_IMG").src = document.getElementById(pickID + "_IMG").src;
          CleanPick(pickID.substring(0,3) + 'CC', true);
        }
        else if( pickID.substring(1,5) == 'fcCC' )
        {
          // make this the pick
          var target = 'SB' + ((pickID.substring(0,1) == "a") ? "A" : "H");
          document.getElementById(target).innerHTML = team;
          document.getElementById(pickID).style.color = "#007500";
          document.getElementById(pickID.substring(0,5) + ((pickID.substring(5) == "A") ? "H" : "A")).style.color = "#BF0000";
          document.getElementById(target + '_IMG').src = document.getElementById(pickID + '_IMG').src;
          CleanPick('SB');
        }
        else if( pickID.substring(0,2) == 'SB' )
        {
          // make this the pick
          document.getElementById('superBowlChampion').innerHTML = "Champion:<br>" + team;
          document.getElementById('superBowlChampion').style.color = "#007500";
          document.getElementById(pickID).style.color = "#007500";
          document.getElementById(pickID.substring(0,2) + ((pickID.substring(2) == "A") ? "H" : "A")).style.color = "#BF0000";
          document.getElementById('superBowlChampion_IMG').src = document.getElementById(pickID + '_IMG').src;
        }

        // fix the hidden inputs
        var inputTarget = pickID.substring(0, pickID.length - 1);
        if( (inputTarget.substring(0, 6) == "afcDiv" && document.getElementById("afcDiv2H").innerHTML.substring(2) == AS1) ||
            (inputTarget.substring(0, 6) == "nfcDiv" && document.getElementById("nfcDiv2H").innerHTML.substring(2) == NS1) )
        {
          inputTarget = inputTarget.substring(0, 6) + ((inputTarget.substring(6) == "1") ? "2" : "1");
        }
        document.getElementById(inputTarget).value = team;

        // clean the visuals
        CombYoBeard();
      }

      function CleanPick(id, wipeWinners)
      {
        document.getElementById(id + "A").style.color = "#D9DCE3";
        document.getElementById(id + "H").style.color = "#D9DCE3";
        var inputTarget = id;
        if( (inputTarget.substring(0, 6) == "afcDiv" && document.getElementById("afcDiv2H").innerHTML.substring(2) == AS1) ||
            (inputTarget.substring(0, 6) == "nfcDiv" && document.getElementById("nfcDiv2H").innerHTML.substring(2) == NS1) )
        {
          inputTarget = inputTarget.substring(0, 6) + ((inputTarget.substring(6) == "1") ? "2" : "1");
        }
        document.getElementById(inputTarget).value = "";

        // check to make sure the ones after this are cleaned
        if( id.substring(1, 6) == "fcDiv" )
        {
          document.getElementById(id.substring(0,3) + "CCA").style.color = "#D9DCE3";
          document.getElementById(id.substring(0,3) + "CCH").style.color = "#D9DCE3";
          document.getElementById(id.substring(0,3) + "CC").value = "";
          if( wipeWinners )
          {
            document.getElementById(id.substring(0,3) + "CCA").innerHTML = "";
            document.getElementById(id.substring(0,3) + "CCH").innerHTML = "@ ";
            document.getElementById(id.substring(0,3) + "CCA_IMG").src = emptyImg;
            document.getElementById(id.substring(0,3) + "CCH_IMG").src = emptyImg;
          }
        }
        if( id.substring(1, 6) == "fcDiv" || id.substring(1, 5) == "fcCC" )
        {
          document.getElementById("SBA").style.color = "#D9DCE3";
          document.getElementById("SBH").style.color = "#D9DCE3";
          document.getElementById("SB").value = "";
          if( wipeWinners )
          {
            document.getElementById("SB" + ((id.substring(0,1) == "a") ? "A" : "H")).innerHTML = "";
            document.getElementById("SB" + ((id.substring(0,1) == "a") ? "A" : "H") + "_IMG").src = emptyImg;
          }
        }
        if( id.substring(1, 6) == "fcDiv" || id.substring(1, 5) == "fcCC" || id.substring(0,2) == "SB" )
        {
          document.getElementById("superBowlChampion").innerHTML = "Champion:<br>";
          document.getElementById("superBowlChampion").style.color = "#D9DCE3";
          document.getElementById("superBowlChampion_IMG").src = emptyImg;
        }
      }

      function CombYoBeard()
      {
        var afcDiv1A = document.getElementById('afcDiv1A');
        var afcDiv1H = document.getElementById('afcDiv1H');
        var afcDiv2A = document.getElementById('afcDiv2A');
        var afcDiv2H = document.getElementById('afcDiv2H');
        var nfcDiv1A = document.getElementById('nfcDiv1A');
        var nfcDiv1H = document.getElementById('nfcDiv1H');
        var nfcDiv2A = document.getElementById('nfcDiv2A');
        var nfcDiv2H = document.getElementById('nfcDiv2H');
        var afcCCA = document.getElementById('afcCCA');
        var afcCCH = document.getElementById('afcCCH');
        var nfcCCA = document.getElementById('nfcCCA');
        var nfcCCH = document.getElementById('nfcCCH');       
        var SBA = document.getElementById('SBA');
        var SBH = document.getElementById('SBH');

        // clean AFC championship
        if( afcCCA.innerHTML != "" && 
            ((afcCCA.innerHTML != afcDiv1A.innerHTML) && (afcDiv1A.style.color != "#007500")) && 
            ((afcCCA.innerHTML != afcDiv1H.innerHTML.substring(2)) && (afcDiv1H.style.color != "#007500")) && 
            ((afcCCA.innerHTML != afcDiv2A.innerHTML) && (afcDiv2A.style.color != "#007500")) && 
            ((afcCCA.innerHTML != afcDiv2H.innerHTML.substring(2)) && (afcDiv2H.style.color != "#007500")) )
        {
          document.getElementById('afcCCA').innerHTML = "";
          document.getElementById('afcCCA_IMG').src = emptyImg;
        }
        if( afcCCH.innerHTML != "@ " && 
            ((afcCCH.innerHTML.substring(2) != afcDiv1A.innerHTML) && (afcDiv1A.style.color != "#007500")) && 
            ((afcCCH.innerHTML.substring(2) != afcDiv1H.innerHTML.substring(2)) && (afcDiv1H.style.color != "#007500")) && 
            ((afcCCH.innerHTML.substring(2) != afcDiv2A.innerHTML) && (afcDiv2A.style.color != "#007500")) && 
            ((afcCCH.innerHTML.substring(2) != afcDiv2H.innerHTML.substring(2)) && (afcDiv2H.style.color != "#007500")) )
        {
          document.getElementById('afcCCH').innerHTML = "@ ";
          document.getElementById('afcCCH_IMG').src = emptyImg;
        }

        // clean NFC championship
        if( nfcCCA.innerHTML != "" && 
            ((nfcCCA.innerHTML != nfcDiv1A.innerHTML) && (nfcDiv1A.style.color != "#007500")) && 
            ((nfcCCA.innerHTML != nfcDiv1H.innerHTML.substring(2)) && (nfcDiv1H.style.color != "#007500")) && 
            ((nfcCCA.innerHTML != nfcDiv2A.innerHTML) && (nfcDiv2A.style.color != "#007500")) && 
            ((nfcCCA.innerHTML != nfcDiv2H.innerHTML.substring(2)) && (nfcDiv2H.style.color != "#007500")) )
        {
          document.getElementById('nfcCCA').innerHTML = "";
          document.getElementById('nfcCCA_IMG').src = emptyImg;
        }
        if( nfcCCH.innerHTML != "@ " && 
            ((nfcCCH.innerHTML.substring(2) != nfcDiv1A.innerHTML) && (nfcDiv1A.style.color != "#007500")) && 
            ((nfcCCH.innerHTML.substring(2) != nfcDiv1H.innerHTML.substring(2)) && (nfcDiv1H.style.color != "#007500")) && 
            ((nfcCCH.innerHTML.substring(2) != nfcDiv2A.innerHTML) && (nfcDiv2A.style.color != "#007500")) && 
            ((nfcCCH.innerHTML.substring(2) != nfcDiv2H.innerHTML.substring(2)) && (nfcDiv2H.style.color != "#007500")) )
        {
          document.getElementById('nfcCCH').innerHTML = "@ ";
          document.getElementById('nfcCCH_IMG').src = emptyImg;
        }

        // clean super bowl
        if( SBA.innerHTML != "" && 
            ((SBA.innerHTML != afcCCA.innerHTML) && (afcCCA.style.color != "#007500")) && 
            ((SBA.innerHTML != afcCCH.innerHTML.substring(2)) && (afcCCH.style.color != "#007500")) )
        {
          document.getElementById('SBA').innerHTML = "";
          document.getElementById('SBA_IMG').src = emptyImg;
        }
        if( SBH.innerHTML != "" && 
            ((SBH.innerHTML != nfcCCA.innerHTML) && (nfcCCA.style.color != "#007500")) && 
            ((SBH.innerHTML != nfcCCH.innerHTML.substring(2)) && (nfcCCH.style.color != "#007500")) )
        {
          document.getElementById('SBH').innerHTML = "";
          document.getElementById('SBH_IMG').src = emptyImg;
        }
      }

      function ToggleSaveButton()
      {
        var canSave = document.getElementById('afcWC1').value != "";
        canSave &= document.getElementById('afcWC2').value != "";
        canSave &= document.getElementById('nfcWC1').value != "";
        canSave &= document.getElementById('nfcWC2').value != "";
        canSave &= document.getElementById('afcDiv1').value != "";
        canSave &= document.getElementById('afcDiv2').value != "";
        canSave &= document.getElementById('nfcDiv1').value != "";
        canSave &= document.getElementById('nfcDiv2').value != "";
        canSave &= document.getElementById('afcCC').value != "";
        canSave &= document.getElementById('nfcCC').value != "";
        canSave &= document.getElementById('SB').value != "";
        canSave &= document.getElementById('tieBreaker').value != "";
        canSave &= document.getElementById('tieBreaker').value != "0";

        document.getElementById("saveRosterButton").disabled = !canSave;
      }

      function NumbersOnly(id)
      {
        var allowed = "0123456789";
        var element = document.getElementById(id);
        var string = element.value;
        for(var i=0; i<string.length; ++i)
        {
          if( allowed.indexOf(string.charAt(i)) == -1 )
          {
            string = string.substr(0,i).concat(string.substr(i+1));
            i--;
          }
        }
        element.value = string;
      }
    </script>