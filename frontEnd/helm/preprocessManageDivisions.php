<?php
    // see if they added a new conference
    if( isset($_POST["newConfName"]) )
    {
      runQuery( "call AddConference('" . $_POST["newConfName"] . "')" );
      $addConfError = "Conference Added! (" . $_POST["newConfName"] . ")";
    }
    // see if they added a new division
    else if( isset($_POST["newDivName"]) )
    {
      if( isset($_POST["useConfID"]) )
      {
        runQuery( "call AddDivision('" . $_POST["newDivName"] . "'," . $_POST["useConfID"] . ")" );
        $addDivError = "Division Added! (" . $_POST["newDivName"] . ")";
      }
      else
      {
        $addDivError = "You must pick a conference for that division.";
      }
    }
    // they must have tried to randomize the divisions
    else
    {
      // see which divisions theyre using
      $usedDivisions = array();
      $divResults = runQuery( "select divID from Division order by rand()" );
      while( ($row = mysqli_fetch_assoc( $divResults ) ) != null )
      {
        if( isset( $_POST["useDivision" . $row["divID"]] ) && $_POST["useDivision" . $row["divID"]] == "doIt" )
        {
          $usedDivisions[count($usedDivisions)] = $row["divID"];
        }
      }

      // get the players in a random order
      $randomOrder = runQuery( "select userID from User join SeasonResult using (userID) " . 
                               "where season=" . $thisSeason . " order by rand()" );

      // now assign them
      $playersPerDiv = ceil(mysqli_num_rows( $randomOrder ) / count($usedDivisions));
      $breakPoint = ($playersPerDiv * count($usedDivisions)) - mysqli_num_rows( $randomOrder );
      $divIndex = 0;
      $playerCount = 0;
      while( ($row = mysqli_fetch_assoc( $randomOrder ) ) != null )
      {
        runQuery( "call AssignToDivision(" . $row["userID"] . "," . $usedDivisions[$divIndex] . ")" );
        $playerCount += 1;
        if( $playerCount == $playersPerDiv )
        {
          $playerCount = 0;
          $divIndex += 1;
          $breakPoint -= 1;
          if( $breakPoint == 0 )
          {
            $playersPerDiv -= 1;
          }
        }
      }

      // tell them its done
      $manageDivError = "Divisions assigned!";
    }
?>
