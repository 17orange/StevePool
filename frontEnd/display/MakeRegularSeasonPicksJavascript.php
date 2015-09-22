    <script type="text/javascript">
      var showWarning = false;
      $(window).on("beforeunload", function() {
        if( showWarning ) {
          return "Are you sure? You didn't finish the form!";
        }
      });

      var currentMousePos = {x:-1, y:-1};
      var currentOffset = {x:0, y:0};
      $(document).mousemove(function(event) {
        // update the position
        currentMousePos.x = event.pageX;
        currentMousePos.y = event.pageY;

        // see if we're dragging
        if( currentDragPos != -1 )
        {
          // move the dragger
          $("#dragger").css( {left: currentMousePos.x + currentOffset.x, top: currentMousePos.y + currentOffset.y} );

          // see which horizontal position it is now in
          var newDragPos = currentDragPos;
          var dragOff = $("#dragger").offset();
          var dragCenter = dragOff.left + ($("#dragger").width() / 2);
          for( var i=1; i<17; i++ )
          {
            var targOff = $("#mp3_" + i).offset();
            if( i != currentDragPos && !($("#mp3_" + i).hasClass("mpLockedSelection")) && 
                targOff.left <= dragCenter && dragCenter <= (targOff.left + $("#mp1_" + i).width()) )
            {
              newDragPos = i;
              i = 17;
            }
          }

          // see if it has changed horizontal position
          if( newDragPos != currentDragPos )
          {
            // swap these positions
            for( var i=1; i<6; i++ )
            {
              var newElem = document.getElementById("mp" + i + "_" + newDragPos);
              var oldElem = document.getElementById("mp" + i + "_" + currentDragPos);
              var dragElem = document.getElementById("drag" + i);

              // innerHTML
              var temp = newElem.innerHTML;
              newElem.innerHTML = oldElem.innerHTML;
              oldElem.innerHTML = temp;

              // swap point values
              if( newElem.innerHTML.indexOf("<img") != -1 )
              {
                var spPos = newElem.innerHTML.indexOf(" ") + 1;
                var brPos = newElem.innerHTML.indexOf("<br>");
                newElem.innerHTML = newElem.innerHTML.substr(0, spPos) + (17 - newDragPos) + newElem.innerHTML.substr(brPos);
              }
              if( oldElem.innerHTML.indexOf("<img") != -1 )
              {
                var spPos = oldElem.innerHTML.indexOf(" ") + 1;
                var brPos = oldElem.innerHTML.indexOf("<br>");
                oldElem.innerHTML = oldElem.innerHTML.substr(0, spPos) + (17 - currentDragPos) + oldElem.innerHTML.substr(brPos);
              }
              if( dragElem.innerHTML.indexOf("<img") != -1 )
              {
                var spPos = dragElem.innerHTML.indexOf(" ") + 1;
                var brPos = dragElem.innerHTML.indexOf("<br>");
                dragElem.innerHTML = dragElem.innerHTML.substr(0, spPos) + (17 - newDragPos) + dragElem.innerHTML.substr(brPos);
              }

              // className
              temp = newElem.className;
              newElem.className = oldElem.className;
              oldElem.className = temp;
            }

            // update the index number
            currentDragPos = newDragPos;
          }

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
              i = 5;
            }
          }

          // see if it has changed vertical position
          if( newDragSelection != currentDragSelection )
          {
            // blank out the old point value
            if( currentDragSelection != 3 )
            {
              var currStr = document.getElementById("drag" + currentDragSelection).innerHTML;
              var spacePos = currStr.indexOf(" ");
              var brPos = currStr.indexOf("<br>");
              document.getElementById("drag" + currentDragSelection).innerHTML = currStr.substr(0, spacePos) + currStr.substr(brPos);
            }

            // update the selection number
            currentDragSelection = newDragSelection;

            // fix these class names
            document.getElementById("drag2").className = (currentDragSelection == 2) ? "mpImgTD mpValidSelection" : "mpImgTD mpAwayTeam";
            document.getElementById("drag3").className = (currentDragSelection == 3) ? "mpInvalidSelection" : "mpGameInfo";
            document.getElementById("drag4").className = (currentDragSelection == 4) ? "mpImgTD mpValidSelection" : "mpImgTD mpHomeTeam";

            // assign the new point value
            if( currentDragSelection != 3 )
            {
              var currStr = document.getElementById("drag" + currentDragSelection).innerHTML;
              var brPos = currStr.indexOf("<br>");
              document.getElementById("drag" + currentDragSelection).innerHTML = currStr.substr(0, brPos) + " " + (17 - currentDragPos) + currStr.substr(brPos);
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

          // put the warning up
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
          srcElem.className = "noBorder";
        }
        $("#dragger").css( {width: topWidth} );
      }

      function ToggleSaveButton()
      {
        var canSave = (document.getElementById("tieBreak").value != "") && (document.getElementById("tieBreak").value > 0);
        for( var i=1; i<17 && canSave; i++ )
        {
          var testElem = document.getElementById("mp3_" + i);
          canSave = (testElem.className.indexOf("mpLockedSelection") != -1) || (testElem.innerHTML.indexOf("<img") != -1);
          // adjust the teams they're saving
          if( testElem.innerHTML.indexOf("<img") != -1 )
          {
            // find where in the array this game is located
            var thisTeam = testElem.innerHTML.substr(0, testElem.innerHTML.indexOf(" "));
            for( var k=1; k<17; k++ )
            {
              var thisHome = document.getElementById("homeTeam" + k)
              var thisAway = document.getElementById("awayTeam" + k)
              if( thisHome != null && thisAway != null && ((thisHome.value == thisTeam) || (thisAway.value == thisTeam)) )
              {
                // set its winner and point value
                document.getElementById("winner" + k).value = thisTeam;
                document.getElementById("pts" + k).value = 17 - i;
                k = 17;
              }
            }
          }
        }

        document.getElementById("saveRosterButton").disabled = !canSave;
      }

      function ToggleSaveButtonMobile()
      {
        var canSave = (document.getElementById("tieBreak").value != "") && (document.getElementById("tieBreak").value > 0);
        for( var i=1; i<17 && canSave; i++ )
        {
          var testElem = document.getElementById("mp3_" + i);
          canSave = (testElem.className.indexOf("mpLockedSelection") != -1) || (testElem.innerHTML.indexOf("<img") != -1);
          // adjust the teams they're saving
          if( testElem.innerHTML.indexOf("<img") != -1 )
          {
            // find where in the array this game is located
            var thisTeam = testElem.innerHTML.substr(0, testElem.innerHTML.indexOf("<br>"));
            for( var k=1; k<17; k++ )
            {
              var thisHome = document.getElementById("homeTeam" + k)
              var thisAway = document.getElementById("awayTeam" + k)
              if( thisHome != null && thisAway != null && ((thisHome.value == thisTeam) || (thisAway.value == thisTeam)) )
              {
                // set its winner and point value
                document.getElementById("winner" + k).value = thisTeam;
                document.getElementById("pts" + k).value = 17 - i;
                k = 17;
              }
            }
          }
        }

        document.getElementById("saveRosterButton").disabled = !canSave;
      }

      function NumbersOnly()
      {
        var allowed = "0123456789";
        var element = document.getElementById("tieBreak");
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
        for( var i=1; i<17; i++ )
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
                destElem.innerHTML = destElem.innerHTML.substr(0,brPos) + " " + (17 - i) + destElem.innerHTML.substr(brPos);
              }
            }
            document.getElementById("mp1_" + i).className = "mpImgTD mpAwayTeam";
            document.getElementById("mp2_" + i).className = "mpGameInfo";
            document.getElementById("mp3_" + i).className = "mpImgTD mpValidSelection";
            document.getElementById("mp4_" + i).className = "noBorder";
            document.getElementById("mp5_" + i).className = "noBorder";
          }
        }

        // put the warning up
        showWarning = true;

        ToggleSaveButton();
      }

      function PickAllAwayTeams()
      {
        for( var i=1; i<17; i++ )
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
                destElem.innerHTML = destElem.innerHTML.substr(0,brPos) + " " + (17 - i) + destElem.innerHTML.substr(brPos);
              }
            }
            document.getElementById("mp5_" + i).className = "mpImgTD mpHomeTeam";
            document.getElementById("mp4_" + i).className = "mpGameInfo";
            document.getElementById("mp3_" + i).className = "mpImgTD mpValidSelection";
            document.getElementById("mp2_" + i).className = "noBorder";
            document.getElementById("mp1_" + i).className = "noBorder";
          }
        }

        // put the warning up
        showWarning = true;

        ToggleSaveButton();
      }

      function PickAllHomeTeamsMobile()
      {
        for( var i=1; i<17; i++ )
        {
          SetWinnerMobile(i, true);
        }
      }

      function PickAllAwayTeamsMobile()
      {
        for( var i=1; i<17; i++ )
        {
          SetWinnerMobile(i, false);
        }
      }

      function SetWinnerMobile(row, homeTeam)
      {
        // find the current winner of this game
        var offset = (document.getElementById("mp1_" + row).className.indexOf("mpImgTD") != -1) 
                     ? 0 
                     : ((document.getElementById("mp2_" + row).className.indexOf("mpImgTD") != -1)
                       ? 1 : 2);
        // set it to be the homeTeam winning
        if( homeTeam && offset > 0)
        {
          // away team
          var destElem = document.getElementById("mp1_" + row);
          var srcElem = document.getElementById("mp" + (offset + 1) + "_" + row);
          destElem.innerHTML = srcElem.innerHTML;
          destElem.className = "mpImgTD mpMobileAwayTeam";
          destElem.onclick = function() { SetWinnerMobile(row, false) };
          destElem.style.textAlign = null;

          // game info
          var destElem = document.getElementById("mp2_" + row);
          var srcElem = document.getElementById("mp" + (offset + 2) + "_" + row);
          destElem.innerHTML = srcElem.innerHTML;
          destElem.className = "mpMobileGameInfo";
          destElem.onclick = null;

          // home team
          var destElem = document.getElementById("mp3_" + row);
          var srcElem = document.getElementById("mp" + (offset + 3) + "_" + row);
          destElem.innerHTML = srcElem.innerHTML;
          destElem.className = "mpImgTD mpValidSelection";

          // arrow
          var destElem = document.getElementById("mp4_" + row);
          destElem.innerHTML = "<---";
          destElem.className = "noBorder mpMobileBGText";
          destElem.onclick = null;

          // points
          var destElem = document.getElementById("mp5_" + row);
          destElem.innerHTML = 17 - row;
          destElem.className = "noBorder mpMobileBGText";
          destElem.style.textAlign = "left";
          destElem.onclick = null;
        }
        // set it to be the awayTeam winning
        else if( !homeTeam && offset < 2)
        {
          // home team
          var destElem = document.getElementById("mp5_" + row);
          var srcElem = document.getElementById("mp" + (offset + 3) + "_" + row);
          destElem.innerHTML = srcElem.innerHTML;
          destElem.className = "mpImgTD mpMobileHomeTeam";
          destElem.onclick = function() { SetWinnerMobile(row, true) };
          destElem.style.textAlign = null;

          // game info
          var destElem = document.getElementById("mp4_" + row);
          var srcElem = document.getElementById("mp" + (offset + 2) + "_" + row);
          destElem.innerHTML = srcElem.innerHTML;
          destElem.className = "mpMobileGameInfo";
          destElem.onclick = null;

          // away team
          var destElem = document.getElementById("mp3_" + row);
          var srcElem = document.getElementById("mp" + (offset + 1) + "_" + row);
          destElem.innerHTML = srcElem.innerHTML;
          destElem.className = "mpImgTD mpValidSelection";

          // arrow
          var destElem = document.getElementById("mp2_" + row);
          destElem.innerHTML = "--->";
          destElem.className = "noBorder mpMobileBGText";
          destElem.onclick = null;

          // points
          var destElem = document.getElementById("mp1_" + row);
          destElem.innerHTML = 17 - row;
          destElem.className = "noBorder mpMobileBGText";
          destElem.style.textAlign = "right";
          destElem.onclick = null;
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
        while( newRow > 0 && newRow < 17 && direction != 0 )
        {
          if( document.getElementById("mp3_" + newRow).className.indexOf("mpLockedSelection") != -1 )
          {
            newRow += direction;
          }
          else
          {
            direction = 0;
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

        // fix the edges
        for( var i=0; i<4; i++ )
        {
          var testElem = document.getElementById("mp" + ((i % 2) ? 1 : 5) + "_" + ((i < 2) ? row : newRow));
          if( testElem.className.indexOf("noBorder") != -1 )
          {
            testElem.innerHTML = 17 - ((i < 2) ? row : newRow);
          }
        }

        // put the warning up
        showWarning = true;

        ToggleSaveButtonMobile();
      }
    </script>
    <table id="dragger" class="dragTable montserrat">
      <tr style="height:75px"><td class="noBorder" id="drag1"></td></tr>
      <tr style="height:75px"><td class="noBorder" id="drag2"></td></tr>
      <tr style="height:75px"><td class="noBorder" id="drag3"></td></tr>
      <tr style="height:75px"><td class="noBorder" id="drag4"></td></tr>
      <tr style="height:75px"><td class="noBorder" id="drag5"></td></tr>
    </table>
