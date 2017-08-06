    <script type="text/javascript">
      var showWarning = false;
      $(window).on("beforeunload", function() {
        if( showWarning ) {
          return "Are you sure? You didn't finish the form!";
        }
      });

      var illegalPointValues = [];
      var currentMousePos = {x:-1, y:-1};
      var currentOffset = {x:0, y:0};
      var pointVals = {2:9, 3:8, 4:7, 5:5, 6:3, 7:5, 8:3, 9:2, 10:1};
      $(document).mousemove(function(event) {
        // update the position
        currentMousePos.x = event.pageX;
        currentMousePos.y = event.pageY;

        // see if we're dragging
        if( currentDragPos != -1 )
        {
          // move the dragger
          $("#dragger").css( {top: currentMousePos.y + currentOffset.y} );
          // see which horizontal position it is now in
          var newDragPos = currentDragPos;
          var dragOff = $("#dragger").offset();
          var dragCenter = dragOff.left + ($("#dragger").width() / 2);

          // see if theyve adjusted it vertically
          var newDragSelection = currentDragSelection;
          dragCenter = dragOff.top + ($("#dragger").height() / 2);
          for( var i=2; i<5; i++ )
          {
            var targOff = $("#mp" + (6 - i) + "_0").offset();
            if( i != currentDragSelection && 
                targOff.top <= dragCenter && dragCenter <= (targOff.top + $("#mp" + i + "_0").height()) )
            {
              newDragSelection = i;
              i = 11;
            }
          }

          // see if it has changed vertical position
          if( newDragSelection != currentDragSelection )
          {
            // blank out the old point value
            if( currentDragSelection != 3 || document.getElementById("drag" + currentDragSelection).innerHTML.substr(0,3) == "TIE")
            {
              var currStr = document.getElementById("drag" + currentDragSelection).innerHTML;
              var spacePos = currStr.indexOf(" ") + 1;
              var brPos = currStr.indexOf("<br>");
              document.getElementById("drag" + currentDragSelection).innerHTML = currStr.substr(0, spacePos) + currStr.substr(brPos);
            }

            // update the selection number
            currentDragSelection = newDragSelection;

            // fix these class names
            document.getElementById("drag2").className = (currentDragSelection == 2) ? "mobileRow mpImgTD mpValidSelection" : "mobileRow mpImgTD mpAwayTeam";
            if( document.getElementById("drag3").innerHTML.substr(0,3) == "TIE" )
            {
              document.getElementById("drag3").className = (currentDragSelection == 3) ? "mobileRow mpValidSelection" : "mobileRow mpGameInfo";
            }
            else
            {
              document.getElementById("drag3").className = (currentDragSelection == 3) ? "mobileRow mpInvalidSelection" : "mobileRow mpGameInfo";
            }
            document.getElementById("drag4").className = (currentDragSelection == 4) ? "mobileRow mpImgTD mpValidSelection" : "mobileRow mpImgTD mpHomeTeam";

            // assign the new point value
            if( currentDragPos > 1 && (currentDragSelection != 3 || document.getElementById("drag" + currentDragSelection).innerHTML.substr(0,3) == "TIE"))
            {
              var currStr = document.getElementById("drag" + currentDragSelection).innerHTML;
              var brPos = currStr.indexOf("<br>");
              document.getElementById("drag" + currentDragSelection).innerHTML = currStr.substr(0, brPos) + " " + pointVals[currentDragPos] + currStr.substr(brPos);
            }
          }
        }
      });      
      var currentDragPos = -1;
      var currentDragSelection = -1;
      $(document).mouseup(function(event) {
        // see if we're dragging something
        if( currentDragPos != -1 )
        {
          // save it to the current position
          for( var i=1; i<6; i++ )
          {
            var srcElem = document.getElementById("drag" + i);
            var targIndex = ((i + 7 - currentDragSelection) % 5) + 1;
            var targetElem = document.getElementById("mp" + targIndex + "_" + currentDragPos);

            // innerHTML
            targetElem.innerHTML = srcElem.innerHTML;
            srcElem.innerHTML = "&nbsp;";

            // className
            targetElem.className = srcElem.className;
            srcElem.className = "";
          }

          // reset to no drag
          currentDragPos = -1;
          currentDragSelection = -1;
          $("#dragger").css( {width: 0, height: 0, top:-1000} );

          // show warning
          showWarning = true;

          // see whether it's able to be saved yet
          ToggleSaveButton();
        }
      });      

      function startDrag(row, column)
      {
        // see whether there's anything in this cell
        if( $("#mp" + row + "_" + column).html() == "" )
        {
          // if not, ignore it
          return;
        }

        // reposition the table to this spot
        currentDragPos = column;
        currentDragSelection = ($("#mp1_" + column).html() != "") 
                               ? 4
                               : (($("#mp2_" + column).html() != "")
                                 ? 3 
                                 : 2); 
        var srcOff = $("#mp1_" + column).offset();
        $("#dragger").css( {left: srcOff.left, top: (srcOff.top + ((currentDragSelection==4) 
                                                                  ? (-$("#mp1_" + column).height()) 
                                                                  : ((currentDragSelection==2)
                                                                    ? $("#mp1_" + column).height()
                                                                    : 0)) )} );
        currentOffset.x = $("#dragger").offset().left - currentMousePos.x;
        currentOffset.y = $("#dragger").offset().top - currentMousePos.y;

        // copy then blank these
        var topWidth = 0;
        for( var i=1; i<6; i++ )
        {
          var targIndex = ((i + 7 - currentDragSelection) % 5) + 1;
          var srcElem = document.getElementById("mp" + targIndex + "_" + column);
          var targetElem = document.getElementById("drag" + i);

          // width
          if( srcElem.offsetWidth > topWidth )
          {
            topWidth = srcElem.offsetWidth;
          }

          // innerHTML
          targetElem.innerHTML = srcElem.innerHTML;
          srcElem.innerHTML = "&nbsp;";
          if( targetElem.innerHTML != "" )
          {
            $("#drag" + i).css( {"background-color": "#D9DCE3"} );
          }
          else
          {
            $("#drag" + i).css( {"background-color": "transparent", "height": "75px"} );
          }

          // className
          targetElem.className = srcElem.className;
          srcElem.className = "mobileRow noBorder";
        }
        $("#dragger").css( {width: topWidth} );
      }

      function ToggleSaveButton()
      {
        var teamAliases = {<?php
          foreach($teamAliases as $thisID => $thisAlias) {
            echo $thisAlias . ":\"" . $thisID . "\",";
          }
          echo "19:19";
        ?>};
        var canSave = true;
        for( var i=1; i<11 && canSave; i++ )
        {
          var testElem = document.getElementById("mp3_" + i);
          canSave = (testElem.className.indexOf("mpLockedSelection") != -1) || ((testElem.innerHTML.indexOf("<img") != -1) && 
                     (testElem.className.indexOf("mpInvalidSelection") == -1));

          // adjust the teams they're saving
          if( testElem.innerHTML.indexOf("<img") != -1 )
          {
            // find where in the array this game is located
            var thisTeam = teamAliases[testElem.innerHTML.substr(0, testElem.innerHTML.indexOf(" "))];

            // set its winner and point value
            document.getElementById("winner" + i).value = thisTeam;
          }

          // grab the tiebreaker
          canSave &= (document.getElementById("tieBreak").value != "");
          canSave &= (document.getElementById("tieBreak").value != "0");
        }

        $(".mobileRow").draggable("enable");
        $(".mobileRow.noBorder").draggable("disable");
        document.getElementById("saveRosterButton").disabled = !canSave;

        // test the warning system
        $(".warningZone").html(showWarning ? "Picks not saved!" : "&nbsp;");
        $("#mainTable").css("background", showWarning ? "#af0000" : "none");
      }

      function ToggleSaveButtonMobile()
      {
        var teamAliases = {<?php
          foreach($teamAliases as $thisID => $thisAlias) {
            echo $thisAlias . ":\"" . $thisID . "\",";
          }
          echo "19:19";
        ?>};
        var canSave = true;
        for( var i=1; i<11 && canSave; i++ )
        {
          var testElem = document.getElementById("mp3_" + i);
          canSave = (testElem.className.indexOf("mpLockedSelection") != -1) || ((testElem.className.indexOf("mpInvalidSelection") == -1) &&
                    ((testElem.innerHTML.indexOf("<img") != -1) || (testElem.innerHTML.indexOf("TIE") != -1)));

          // adjust the teams they're saving
          if( testElem.innerHTML.indexOf("<img") != -1 || testElem.innerHTML.indexOf("TIE") != -1 )
          {
            // find where in the array this game is located
            var thisTeam = teamAliases[testElem.innerHTML.substr(0, testElem.innerHTML.indexOf("<br>"))];
            if( testElem.innerHTML.indexOf("TIE") != -1 )
            {
              var thisTeam = "TIE";
            }

            // set its winner and point value
            document.getElementById("winner" + i).value = thisTeam;
          }

          // grab the tiebreaker
          canSave &= (document.getElementById("tieBreak").value != "");
          canSave &= (document.getElementById("tieBreak").value != "0");
        }

        document.getElementById("tieBreakReal").value = document.getElementById("tieBreak").value;
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
        showWarning = true;
      }

      function PickAllHomeTeams()
      {
        for( var i=1; i<11; i++ )
        {
          if( document.getElementById("mp3_" + i).className.indexOf("mpLockedSelection") == -1 )
          {
            var swap = (document.getElementById("mp2_" + i).innerHTML == "") ? 2 : 
                        ((document.getElementById("mp1_" + i).innerHTML == "") ? 1 : 0);
            for( var j=1; j<4 && swap > 0; j++ )
            {
              var destElem = document.getElementById("mp" + j + "_" + i);
              var srcElem = document.getElementById("mp" + (j + swap) + "_" + i);
              destElem.innerHTML = srcElem.innerHTML;
              srcElem.innerHTML = "";

              // blank the old value if it was away team
              if( destElem.innerHTML.indexOf("<img") != -1 )
              {
                var spPos = destElem.innerHTML.indexOf(" ");
                var brPos = destElem.innerHTML.indexOf("<br>");
                destElem.innerHTML = destElem.innerHTML.substr(0, spPos) + destElem.innerHTML.substr(brPos);
              }
              // add the point value if we're on the pick row
              if( j == 3 && i > 1)
              {
                var brPos = destElem.innerHTML.indexOf("<br>");
                destElem.innerHTML = destElem.innerHTML.substr(0,brPos) + " " + pointVals[i] + destElem.innerHTML.substr(brPos);
              }
            }
            document.getElementById("mp1_" + i).className = "mpImgTD mpAwayTeam";
            document.getElementById("mp2_" + i).className = "mpGameInfo";
            document.getElementById("mp3_" + i).className = "mpImgTD mpValidSelection";
            document.getElementById("mp4_" + i).className = "noBorder";
            document.getElementById("mp5_" + i).className = "noBorder";
          }
        }

        ToggleSaveButton();
      }

      function PickAllAwayTeams()
      {
        for( var i=1; i<11; i++ )
        {
          if( document.getElementById("mp3_" + i).className.indexOf("mpLockedSelection") == -1 )
          {
            var swap = (document.getElementById("mp4_" + i).innerHTML == "") ? 2 : 
                        ((document.getElementById("mp5_" + i).innerHTML == "") ? 1 : 0);
            for( var j=5; j>2 && swap > 0; j-- )
            {
              var destElem = document.getElementById("mp" + j + "_" + i);
              var srcElem = document.getElementById("mp" + (j - swap) + "_" + i);
              destElem.innerHTML = srcElem.innerHTML;
              srcElem.innerHTML = "";

              // blank the old value if it was home team
              if( destElem.innerHTML.indexOf("<img") != -1 )
              {
                var spPos = destElem.innerHTML.indexOf(" ");
                var brPos = destElem.innerHTML.indexOf("<br>");
                destElem.innerHTML = destElem.innerHTML.substr(0, spPos) + destElem.innerHTML.substr(brPos);
              }
              // add the point value if we're on the pick row
              if( j == 3 && i > 1 )
              {
                var brPos = destElem.innerHTML.indexOf("<br>");
                destElem.innerHTML = destElem.innerHTML.substr(0,brPos) + " " + pointVals[i] + destElem.innerHTML.substr(brPos);
              }
            }
            document.getElementById("mp5_" + i).className = "mpImgTD mpHomeTeam";
            document.getElementById("mp4_" + i).className = "mpGameInfo";
            document.getElementById("mp3_" + i).className = "mpImgTD mpValidSelection";
            document.getElementById("mp2_" + i).className = "noBorder";
            document.getElementById("mp1_" + i).className = "noBorder";
          }
        }

        ToggleSaveButton();
      }
      function PickAllHomeTeamsMobile()
      {
        for( var i=1; i<11; i++ )
        {
          SetWinnerMobile(i, true);
        }
      }

      function PickAllAwayTeamsMobile()
      {
        for( var i=1; i<11; i++ )
        {
          SetWinnerMobile(i, false);
        }
      }

      var pickHibernating = false;
      var captions = []
      function SetWinnerMobile(row, homeTeam)
      {
        // find the current winner of this game
        var offset = (document.getElementById("mp1_" + row).className.indexOf("mpImgTD") != -1) 
                     ? 0 
                     : ((document.getElementById("mp2_" + row).className.indexOf("mpImgTD") != -1)
                       ? 1 : 2);

        // ignore it if we need to
        if(pickHibernating || ((homeTeam == "TIE") && (offset == 1))) {
          return;
        }

        // set it to be a tie
        if( homeTeam == "TIE" && offset != 1)
        {
          // pause this to eat the double taps
          pickHibernating = true;
          setTimeout(function() { pickHibernating = false; }, 1000);

          // gap team
          var destElem = document.getElementById("mp" + (4 - offset) + "_" + row);
          var srcElem = document.getElementById("mp3_" + row);
          destElem.innerHTML = srcElem.innerHTML;
          destElem.className = "mpImgTD mpMobile" + ((offset == 0) ? "Home" : "Away") + "Team mpMobileWipeTop";
          destElem.onclick = function() { SetWinnerMobile(this.id.slice(this.id.indexOf('_') + 1), (offset == 0)) };
          destElem.style.textAlign = null;

          // game info
          destElem = document.getElementById("mp3_" + row);
          srcElem = document.getElementById("mp" + (offset + 2) + "_" + row);
          destElem.innerHTML = srcElem.innerHTML;
          destElem.className = "mpValidSelection";
          destElem.onclick = null;

          // wall team
          destElem = document.getElementById("mp" + (2 + offset) + "_" + row);
          srcElem = document.getElementById("mp" + ((offset * 2) + 1) + "_" + row);
          destElem.innerHTML = srcElem.innerHTML;
          destElem.className = "mpImgTD mpMobile" + ((offset == 0) ? "Away" : "Home") + "Team mpMobileWipeTop";
          destElem.onclick = function() { SetWinnerMobile(this.id.slice(this.id.indexOf('_') + 1), (offset != 0)) };
          destElem.style.textAlign = null;

          // points
          destElem = document.getElementById("mp1_" + row);
          destElem.innerHTML = pointVals[row];
          destElem.className = "noBorder mpMobileBGText";
          destElem.style.textAlign = "right";
          destElem.onclick = null;

          // points
          destElem = document.getElementById("mp5_" + row);
          destElem.innerHTML = pointVals[row];
          destElem.className = "noBorder mpMobileBGText";
          destElem.style.textAlign = "left";
          destElem.onclick = null;

          // header
          srcElem = document.getElementById("mpH" + (offset + 1) + "_" + row);
          destElem = document.getElementById("mpH2_" + row);
          destElem.innerHTML = srcElem.innerHTML;
          destElem.className = srcElem.className;
          destElem.colSpan = srcElem.colSpan;
          destElem = document.getElementById("mpH3_" + row);
          destElem.innerHTML = "";
          destElem.className = "noBorder";
          destElem.colSpan = 1;
          destElem = document.getElementById("mpH1_" + row);
          destElem.innerHTML = "";
          destElem.className = "noBorder";
          destElem.colSpan = 1;
        }
        // set it to be the homeTeam winning
        else if( homeTeam && offset > 0)
        {
          // away team
          var destElem = document.getElementById("mp1_" + row);
          var srcElem = document.getElementById("mp" + (offset + 1) + "_" + row);
          destElem.innerHTML = srcElem.innerHTML;
          destElem.className = "mpImgTD mpMobileAwayTeam mpMobileWipeTop";
          destElem.onclick = function() { SetWinnerMobile(this.id.slice(this.id.indexOf('_') + 1), false) };
          destElem.style.textAlign = null;

          // game info
          destElem = document.getElementById("mp2_" + row);
          srcElem = document.getElementById("mp" + (offset + 2) + "_" + row);
          destElem.innerHTML = srcElem.innerHTML;
          destElem.className = "mpMobileGameInfo mpMobileWipeTop";
          destElem.onclick = null;

          // home team
          destElem = document.getElementById("mp3_" + row);
          srcElem = document.getElementById("mp" + (offset + 3) + "_" + row);
          destElem.innerHTML = srcElem.innerHTML;
          destElem.className = "mpImgTD mpValidSelection";
          destElem.onclick = null;

          // arrow
          destElem = document.getElementById("mp4_" + row);
          destElem.innerHTML = (row == 1) ? "" : "<---";
          destElem.className = "noBorder mpMobileBGText";
          destElem.onclick = null;

          // points
          destElem = document.getElementById("mp5_" + row);
          destElem.innerHTML = (row == 1) ? "" : pointVals[row];
          destElem.className = "noBorder mpMobileBGText";
          destElem.style.textAlign = "left";
          destElem.onclick = null;

          // header
          srcElem = document.getElementById("mpH" + (offset + 1) + "_" + row);
          destElem = document.getElementById("mpH1_" + row);
          destElem.innerHTML = srcElem.innerHTML;
          destElem.className = srcElem.className;
          destElem.colSpan = srcElem.colSpan;
          destElem = document.getElementById("mpH2_" + row);
          destElem.innerHTML = "";
          destElem.className = "noBorder";
          destElem.colSpan = 1;
          destElem = document.getElementById("mpH3_" + row);
          destElem.innerHTML = "";
          destElem.className = "noBorder";
          destElem.colSpan = 1;
        }
        // set it to be the awayTeam winning
        else if( !homeTeam && offset < 2)
        {
          // home team
          var destElem = document.getElementById("mp5_" + row);
          var srcElem = document.getElementById("mp" + (offset + 3) + "_" + row);
          destElem.innerHTML = srcElem.innerHTML;
          destElem.className = "mpImgTD mpMobileHomeTeam mpMobileWipeTop";
          destElem.onclick = function() { SetWinnerMobile(this.id.slice(this.id.indexOf('_') + 1), true) };
          destElem.style.textAlign = null;

          // game info
          var destElem = document.getElementById("mp4_" + row);
          var srcElem = document.getElementById("mp" + (offset + 2) + "_" + row);
          destElem.innerHTML = srcElem.innerHTML;
          destElem.className = "mpMobileGameInfo mpMobileWipeTop";
          destElem.onclick = null;

          // away team
          var destElem = document.getElementById("mp3_" + row);
          var srcElem = document.getElementById("mp" + (offset + 1) + "_" + row);
          destElem.innerHTML = srcElem.innerHTML;
          destElem.className = "mpImgTD mpValidSelection";
          destElem.onclick = null;

          // arrow
          var destElem = document.getElementById("mp2_" + row);
          destElem.innerHTML = (row == 1) ? "" : "--->";
          destElem.className = "noBorder mpMobileBGText";
          destElem.onclick = null;

          // points
          var destElem = document.getElementById("mp1_" + row);
          destElem.innerHTML = (row == 1) ? "" : pointVals[row];
          destElem.className = "noBorder mpMobileBGText";
          destElem.style.textAlign = "right";
          destElem.onclick = null;

          // header
          srcElem = document.getElementById("mpH" + (offset + 1) + "_" + row);
          destElem = document.getElementById("mpH3_" + row);
          destElem.innerHTML = srcElem.innerHTML;
          destElem.className = srcElem.className;
          destElem.colSpan = srcElem.colSpan;
          destElem = document.getElementById("mpH2_" + row);
          destElem.innerHTML = "";
          destElem.className = "noBorder";
          destElem.colSpan = 1;
          destElem = document.getElementById("mpH1_" + row);
          destElem.innerHTML = "";
          destElem.className = "noBorder";
          destElem.colSpan = 1;
        }

        // put the warning up
        showWarning = true;

        ToggleSaveButtonMobile();
      }
      //this will hold reference to the tr we have dragged and its helper
      var dropZone = {};
      function MobileContentLoaded() {
        $(".mobileRow").draggable({
          helper:"clone",
          start: TPdragStart,
          drag: TPdragDrag,
          stop: TPdragStop,
          cancel: 'td.noBorder'
        });
        showWarning = false;
        $(window).on("beforeunload", function() {
          if( showWarning ) {
            return "Are you sure? You didn't finish the form!";
          }
        });
        document.getElementById("saveRosterButton").disabled = true;
        $(".mobileRow").draggable("enable");
        $(".mobileRow.noBorder").draggable("disable");
      }

      function TPdragStart(event, ui) {
      }

      function TPdragDrag(event, ui) {
      }

      function TPdragStop(event, ui) {
      }
    </script>
    <table id="dragger" class="dragTable montserrat">
      <tr style="height:96px"><td class="mobileRow noBorder" id="drag1"></td></tr>
      <tr style="height:96px"><td class="mobileRow noBorder" id="drag2"></td></tr>
      <tr style="height:96px"><td class="mobileRow noBorder" id="drag3"></td></tr>
      <tr style="height:96px"><td class="mobileRow noBorder" id="drag4"></td></tr>
      <tr style="height:96px"><td class="mobileRow noBorder" id="drag5"></td></tr>
    </table>
