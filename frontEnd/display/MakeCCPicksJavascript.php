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
      var tries = 0;
      $(document).mousemove(function(event) {
        // update the position
        currentMousePos.x = event.pageX;
        currentMousePos.y = event.pageY;

        // see if we're dragging
        if( currentDragPos != -1 )
        {
          // move the dragger
          $("#dragger").css( {left: currentMousePos.x + currentOffset.x} );

          var dragOff = $("#dragger").offset();
          // see if theyve adjusted it horizontally
          var newDragSelection = currentDragSelection;
          dragCenter = dragOff.left + ($("#dragger").width() / 2);
          for( var i=2; i<5; i++ )
          {
            var targOff = $("#mp" + (6 - i) + "_" + currentDragPos).offset();
            if( i != currentDragSelection && 
                targOff.left <= dragCenter && dragCenter <= (targOff.left + $("#mp" + (6 - i) + "_" + currentDragPos).width()) )
            {
              newDragSelection = i;
              i = 5;
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
              document.getElementById("drag" + currentDragSelection).innerHTML = currStr.substr(0, spacePos) + ((brPos > 0) ? currStr.substr(brPos) : "");
            }

            // update the selection number
            currentDragSelection = newDragSelection;

            // fix these class names
            document.getElementById("drag2").className = (currentDragSelection == 2) ? "mpImgTD mpValidSelection<?php echo ($_SESSION["cbm"] ? " CBM" : ""); ?>" : "mpImgTD mpWCDivAwayTeam";
            if( document.getElementById("drag3").innerHTML.substr(0,3) == "TIE" )
            {
              document.getElementById("drag3").className = (currentDragSelection == 3) ? "mpValidSelection<?php echo ($_SESSION["cbm"] ? " CBM" : ""); ?>" : "mpWCDivGameInfo";
            }
            else
            {
              document.getElementById("drag3").className = (currentDragSelection == 3) ? "mpInvalidSelection<?php echo ($_SESSION["cbm"] ? " CBM" : ""); ?>" : "mpWCDivGameInfo";
            }
            document.getElementById("drag4").className = (currentDragSelection == 4) ? "mpImgTD mpValidSelection<?php echo ($_SESSION["cbm"] ? " CBM" : ""); ?>" : "mpImgTD mpWCDivHomeTeam";

            // update the slider handle
            adjustSliders(null,null);
          }
        }
      });      
      var currentDragPos = -1;
      var currentDragSelection = -1;
      $(document).mouseup(function(event) {
        // see if we're dragging something
        if( currentDragPos != -1 )
        {
          var fontSize = parseFloat($("#sliderHandle" + currentDragPos).parents("a").css("font-size"));
          var upperLimit = (fontSize * -1.25) - 5;
          var standardLimit = (fontSize * -6.25) - 7.5;
          var lowerLimit = (fontSize * -11.25) - 10;

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
        $("#dragger").css( {top: srcOff.top, left: srcOff.left + ((currentDragSelection==4) 
                                                                  ? (-$("#mp1_" + column).width()) 
                                                                  : ((currentDragSelection==2)
                                                                    ? $("#mp1_" + column).width()
                                                                    : 0))} );
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
          srcElem.className = "noBorder";
        }
        $("#dragger").css( {width: topWidth} );
      }

      function ToggleSaveButton()
      {
        var canSave = (document.getElementById("pointsLeft").innerHTML == "0 points remaining");
        for( var i=1; i<5 && canSave; i++ )
        {
          var testElem = document.getElementById("mp3_" + i);
          var thisTeam = testElem.innerHTML.substr(0, testElem.innerHTML.indexOf(" "));
          canSave = (testElem.className.indexOf("mpLockedSelection") != -1) || (((thisTeam == "TIE") || (testElem.innerHTML.indexOf("<img") != -1)) && 
                     (testElem.className.indexOf("mpInvalidSelection") == -1));

          // adjust the teams they're saving
          if( testElem.innerHTML.indexOf("<img") != -1 || thisTeam == "TIE" )
          {
            // find where in the array this game is located
            var lookTeam = thisTeam;
            if( thisTeam == "TIE" )
            {
              var lookTeam = "";
              for( var k=1; k<4 && lookTeam == ""; k++ )
              {
                var teamHTML = document.getElementById("mp" + k + "_" + i).innerHTML;
                if( teamHTML.indexOf("<img") != -1 )
                {
                  lookTeam = teamHTML.substr(0, teamHTML.indexOf("<br>") - 1);
                }
              }
            }

            // check to see whether this column holds a winner or halftime pick
            var lookType = "";
            for( var k=1; k<4 && lookType == ""; k++ )
            {
              var typeHTML = document.getElementById("typeLabel" + i).innerHTML;
              if( typeHTML.indexOf("Final") != -1 )
              {
                lookType = "winner";
              }
              else if( typeHTML.indexOf("Half") != -1 )
              {
                lookType = "winner2Q";
              }
            }

            // run through the list of values until we find this one
            for( var k=1; k<5; k++ )
            {
              var thisHome = document.getElementById("homeTeam" + k);
              var thisAway = document.getElementById("awayTeam" + k);
              var thisType = document.getElementById("pickType" + k).value;
              if( thisHome != null && thisAway != null && thisType == lookType &&
                  ((thisHome.value == lookTeam) || (thisAway.value == lookTeam)) )
              {
                // set its winner
                document.getElementById("winner" + k).value = thisTeam;
                k = 5;
              }
            }
          }

          // grab the tiebreaker
          document.getElementById("tb" + i).value = document.getElementById("tieBreak" + i).value;
          canSave &= (document.getElementById("tieBreak" + i).value != "");
          canSave &= (document.getElementById("tieBreak" + i).value != "0");
        }

        document.getElementById("saveRosterButton").disabled = !canSave;

        // test the warning system
        if( showWarning ) {
          $(".warningZone").html(showWarning ? "Picks not saved!" : "&nbsp;");
          $("#mainTable").css("background", showWarning ? "#af0000" : "none");
        }
      }

      function ToggleSaveButtonMobile()
      {
        var canSave = true;
        for( var i=1; i<5 && canSave; i++ )
        {
          var testElem = document.getElementById("mp3_" + i);
          canSave = (testElem.className.indexOf("mpLockedSelection") != -1) || ((testElem.className.indexOf("mpInvalidSelection") == -1) &&
                    ((testElem.innerHTML.indexOf("<img") != -1) || (testElem.innerHTML.indexOf("TIE") != -1)));

          // adjust the teams they're saving
          if( testElem.innerHTML.indexOf("<img") != -1 || testElem.innerHTML.indexOf("TIE") != -1)
          {
            // find where in the array this game is located
            var thisTeam = testElem.innerHTML.substr(0, testElem.innerHTML.indexOf("<br>"));
            var lookTeam = thisTeam;
            if( testElem.innerHTML.indexOf("TIE") != -1 )
            {
              var thisTeam = "TIE";
              var lookTeam = "";
              for( var k=1; k<4 && lookTeam == ""; k++ )
              {
                var teamHTML = document.getElementById("mp" + k + "_" + i).innerHTML;
                if( teamHTML.indexOf("<img") != -1 )
                {
                  lookTeam = teamHTML.substr(0, teamHTML.indexOf("<br>"));
                }
              }
            }

            // check to see whether this column holds a winner or halftime pick
            var lookType = "";
            for( var k=1; k<4 && lookType == ""; k++ )
            {
              var typeHTML = document.getElementById("mpH" + k + "_" + i).innerHTML;
              if( typeHTML.indexOf("Final") != -1 )
              {
                lookType = "winner";
              }
              else if( typeHTML.indexOf("Halftime") != -1 )
              {
                lookType = "winner2Q";
              }
            }

            // run through the list of values until we find this one
            for( var k=1; k<5; k++ )
            {
              var thisHome = document.getElementById("homeTeam" + k);
              var thisAway = document.getElementById("awayTeam" + k);
              var thisType = document.getElementById("pickType" + k).value;
              if( thisHome != null && thisAway != null && thisType == lookType &&
                  ((thisHome.value == lookTeam) || (thisAway.value == lookTeam)) )
              {
                // set its winner and point value
                document.getElementById("winner" + k).value = thisTeam;
                document.getElementById("pts" + k).value = 5 - i;
                k = 5;
              }
            }
          }

          // grab the tiebreaker
          document.getElementById("tb" + i).value = document.getElementById("tieBreak" + i).value;
          canSave &= (document.getElementById("tieBreak" + i).value != "");
          canSave &= (document.getElementById("tieBreak" + i).value != "0");
        }

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

        // put the warning up
        showWarning = true;
      }

      function PickAllHomeTeams()
      {
        for( var i=1; i<5; i++ )
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
              if( j == 3 )
              {
                var brPos = destElem.innerHTML.indexOf("<br>");
                destElem.innerHTML = destElem.innerHTML.substr(0,brPos) + " " + (5 - i) + destElem.innerHTML.substr(brPos);
              }
            }
            document.getElementById("mp1_" + i).className = "mpImgTD mpAwayTeam";
            document.getElementById("mp2_" + i).className = "mpGameInfo";
            document.getElementById("mp3_" + i).className = "mpImgTD mpValidSelection<?php echo ($_SESSION["cbm"] ? " CBM" : ""); ?>";
            document.getElementById("mp4_" + i).className = "noBorder";
            document.getElementById("mp5_" + i).className = "noBorder";
          }
        }

        ToggleSaveButton();
      }

      function PickAllAwayTeams()
      {
        for( var i=1; i<5; i++ )
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
              if( j == 3 )
              {
                var brPos = destElem.innerHTML.indexOf("<br>");
                destElem.innerHTML = destElem.innerHTML.substr(0,brPos) + " " + (5 - i) + destElem.innerHTML.substr(brPos);
              }
            }
            document.getElementById("mp5_" + i).className = "mpImgTD mpHomeTeam";
            document.getElementById("mp4_" + i).className = "mpGameInfo";
            document.getElementById("mp3_" + i).className = "mpImgTD mpValidSelection<?php echo ($_SESSION["cbm"] ? " CBM" : ""); ?>";
            document.getElementById("mp2_" + i).className = "noBorder";
            document.getElementById("mp1_" + i).className = "noBorder";
          }
        }

        ToggleSaveButton();
      }
      function PickAllHomeTeamsMobile()
      {
        for( var i=1; i<5; i++ )
        {
          SetWinnerMobile(i, true);
        }
      }

      function PickAllAwayTeamsMobile()
      {
        for( var i=1; i<5; i++ )
        {
          SetWinnerMobile(i, false);
        }
      }

      var pickHibernating = false;
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
          destElem.className = "mpValidSelection<?php echo ($_SESSION["cbm"] ? " CBM" : ""); ?>";
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
          destElem.innerHTML = 5 - row;
          destElem.className = "noBorder mpMobileBGText";
          destElem.style.textAlign = "right";
          destElem.onclick = null;

          // points
          destElem = document.getElementById("mp5_" + row);
          destElem.innerHTML = 5 - row;
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
          destElem.className = "mpImgTD mpValidSelection<?php echo ($_SESSION["cbm"] ? " CBM" : ""); ?>";
          destElem.onclick = null;

          // arrow
          destElem = document.getElementById("mp4_" + row);
          destElem.innerHTML = "<---";
          destElem.className = "noBorder mpMobileBGText";
          destElem.onclick = null;

          // points
          destElem = document.getElementById("mp5_" + row);
          destElem.innerHTML = 5 - row;
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
          destElem.className = "mpImgTD mpValidSelection<?php echo ($_SESSION["cbm"] ? " CBM" : ""); ?>";
          destElem.onclick = null;

          // arrow
          var destElem = document.getElementById("mp2_" + row);
          destElem.innerHTML = "--->";
          destElem.className = "noBorder mpMobileBGText";
          destElem.onclick = null;

          // points
          var destElem = document.getElementById("mp1_" + row);
          destElem.innerHTML = 5 - row;
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

      function MoveRowMobile(row, direction)
      {
        // find the row it wants to go to
        row = parseInt(row);
        var newRow = row + direction;
        while( newRow > 0 && newRow < 5 && direction != 0 )
        {
          if( (($("#mp3_" + newRow).hasClass("mpValidSelection")) || ($("#mp3_" + newRow).hasClass("mpInvalidSelection"))) && 
                illegalPointValues.indexOf(5 - newRow) < 0 )
          {
            direction = 0;
          }
          else
          {
            newRow += direction;
          }
        }

        // no valid move in that direction
        if( direction != 0 )
        {
          return;
        }

        // swap them
        for( var i=1; i<6; i++ )
        {
          var destElem = document.getElementById("mp" + i + "_" + newRow);
          var origElem = document.getElementById("mp" + i + "_" + row);
          var temp = destElem.innerHTML;
          destElem.innerHTML = origElem.innerHTML;
          origElem.innerHTML = temp;
          temp = destElem.className;
          destElem.className = origElem.className;
          origElem.className = temp;
          temp = destElem.onclick;
          destElem.onclick = origElem.onclick;
          origElem.onclick = temp;
          temp = destElem.style.textAlign;
          destElem.style.textAlign = origElem.style.textAlign;
          origElem.style.textAlign = temp;
        }
        for( var i=1; i<4; i++ )
        {
          var destElem = document.getElementById("mpH" + i + "_" + newRow);
          var origElem = document.getElementById("mpH" + i + "_" + row);
          var temp = destElem.innerHTML;
          destElem.innerHTML = origElem.innerHTML;
          origElem.innerHTML = temp;
          temp = destElem.className;
          destElem.className = origElem.className;
          origElem.className = temp;
          temp = destElem.colSpan;
          destElem.colSpan = origElem.colSpan;
          origElem.colSpan = temp;
        }

        // fix the edges
        for( var i=0; i<4; i++ )
        {
          var testElem = document.getElementById("mp" + ((i % 2) ? 1 : 5) + "_" + ((i < 2) ? row : newRow));
          if( testElem.className.indexOf("noBorder") != -1 )
          {
            testElem.innerHTML = 5 - ((i < 2) ? row : newRow);
          }
        }

        // put the warning up
        showWarning = true;

        ToggleSaveButtonMobile();
      }

      function adjustSliders(event, ui) {
        var pointsLeft = 20;
        var total = 20;
        $(".pointSlider").each( function() {
          total -= ($(this).slider("value") ? $(this).slider("value") : 1);
          pointsLeft -= $(this).slider("value");
        } );
        $(".pointSlider").each( function() {
          var max = ($(this).slider("value") ? $(this).slider("value") : 1) + total;
          if( max > 17 ) {
            max = 17;
          }
          if( max < 1 ) {
            max = 1;
          }
          $(this).slider("option", "max", max);
          $(this).slider("value", $(this).slider("value"));
          fixCaption({"target":$(this)}, {"value":$(this).slider("value")});
          $(this).css({"width":((max * 100 / 17) + "%")});
        } );
        $('#pointsLeft').html(pointsLeft + " point" + ((pointsLeft == 1) ? "" : "s") + " remaining");

        showWarning = (event != null);

        ToggleSaveButton();
      }

      function fixHandles() {
        $(".pointSlider").each( function() {
          fixCaption({"target":$(this)}, {"value":$(this).slider("value")});
        } );    
      }

      function fixCaption(event, ui) {
        var index = $(event.target).attr("id").charAt(6);
        $("#pts" + index).attr("value", ui.value);
        $(event.target).find(".sliderGood").css({"width":((ui.value * 100 / $(event.target).slider("option", "max")) + "%")});
        var targetPick = ((typeof currentDragPos == "undefined") || (currentDragPos != index)) ? document.getElementById("mp3_" + index) : document.getElementById("drag" + currentDragSelection);
        var selection = $(targetPick);
        if( selection.hasClass("mpValidSelection") ) {
          var spPos = selection.html().indexOf(" ") + 1;
          var brPos = selection.html().indexOf("<br>");
          selection.html(selection.html().substr(0, spPos) + ui.value + ((brPos > 0) ? selection.html().substr(brPos) : ""));
        }
        $(event.target).find(".handleGuts").removeClass("mpValidSelection mpInvalidSelection mpImgTD noBorder").addClass((ui.value == 0) ? "mpInvalidSelection" : targetPick.className).html(targetPick.innerHTML);
        if( $(event.target).find(".handleGuts").find("img").length == 0 ) {
          $(event.target).find(".handleGuts").html("TIE " + ui.value + "<br><div class=\"imgDiv\"><img class=\"teamLogo\" src=\"icons/2016/nfl.png\" draggable=\"false\" ondragstart=\"return false;\" ontouchstart=\"return false;\"></div>");
        }
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
        <?php for($i=1; $i<5; $i++ ) { ?>
          $("#slider<?php echo $i; ?>").slider({ 
            value:$("#pts<?php echo $i; ?>").attr("value"), min:0, max:17, slide:fixCaption, stop:adjustSliders });
          $("#sliderHandle<?php echo $i; ?>").find("div").css({"min-width":"68px","height":"65px"});
          $("#slider<?php echo $i; ?>").find(".ui-slider-handle").append($("#sliderHandle<?php echo $i; ?>"));
        <?php } ?>
        adjustSliders(null, null);
        document.getElementById("saveRosterButton").disabled = true;
      }

      function TPdragStart(event, ui) {
      }

      function TPdragDrag(event, ui) {
      }

      function TPdragStop(event, ui) {
      }
    </script>
    <table id="dragger" class="dragTable montserrat">
      <tr style="height:75px">
        <td style="min-width:68px" class="noBorder" id="drag1"></td>
        <td style="min-width:68px" class="noBorder" id="drag2"></td>
        <td style="min-width:68px" class="noBorder" id="drag3"></td>
        <td style="min-width:68px" class="noBorder" id="drag4"></td>
        <td style="min-width:68px" class="noBorder" id="drag5"></td>
      </tr>
    </table>
