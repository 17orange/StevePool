    <span style="font-size:18px; font-weight:bold;">Manage Divisions for <?php echo $thisSeason; ?> Season</span>
    <br/>
    <br/>
    <form action="." method="post">
      <input type="hidden" name="adminTask" value="manageDivisions" />
      <span style="font-size:16px; font-weight:bold;">Create New Conference</span>
      <input type="text" name="newConfName" />
      <input type="submit" value="Add" />
    </form>
<?php
    if( isset($addConfError) && $addConfError != "" )
    {
      echo "    <span style=\"color:#FF0000; font-weight:bold;\">" . $addConfError . "</span>\n";
    }
?>
    <br/>
    <br/>
    <form action="." method="post">
      <input type="hidden" name="adminTask" value="manageDivisions" />
      <span style="font-size:16px; font-weight:bold;">Create New Division</span>
      <input type="text" name="newDivName" />
      <input type="submit" value="Add" />
      <table>
<?php
    // grab all the conferences
    $confResults = runQuery( "select * from Conference order by confID" );

    // show them
    while( ($row = mysqli_fetch_assoc( $confResults )) != null )
    {
      echo "        <tr>\n";
      echo "          <td><input type=\"radio\" value=\"" . $row["confID"] . "\" name=\"useConfID\" /></td>\n";
      echo "          <td>" . $row["name"] . "</td>\n";
      echo "        </tr>\n";      
    }
?>
      </table>
    </form>
<?php
    if( isset($addDivError) && $addDivError != "" )
    {
      echo "    <span style=\"color:#FF0000; font-weight:bold;\">" . $addDivError . "</span>\n";
    }
?>
    <br/>
    <br/>
    <form action="." method="post">
      <input type="hidden" name="adminTask" value="manageDivisions" />
      <span style="font-size:16px; font-weight:bold;">Randomize Division Assignment</span>
      <table>
<?php
    // grab all division info
    $divResults = runQuery( "select divID, Division.name as divName, confID, Conference.name as confName " . 
                            "from Division join Conference using (confID) order by confID, divID" );

    // show the divisions
    $currentConf = -1;
    while( ($row = mysqli_fetch_assoc( $divResults )) != null )
    {
      // show the conference name
      if( $row["confID"] != $currentConf )
      {
        echo "        <tr>\n";
        echo "          <td colspan=2><span style=\"font-size:14px; font-weight:bold;\">" . 
             $row["confName"] . "</span></td>\n";
        echo "        </tr>\n";
        $currentConf = $row["confID"];
      }
      echo "        <tr>\n";
      echo "          <td><input type=\"checkbox\" value=\"doIt\" name=\"useDivision" .
           $row["divID"] . "\" /></td>\n";
      echo "          <td>" . $row["divName"] . "</td>\n";
      echo "        </tr>\n";
    }
?>
      </table>
      <input type="submit" value="Randomize" />
    </form>
<?php
    if( isset($manageDivError) && $manageDivError != "" )
    {
      echo "    <span style=\"color:#FF0000; font-weight:bold;\">" . $manageDivError . "</span>\n";
    }
?>
    <br/>
    <br/>
    <span style="font-size:18px; font-weight:bold;">Current Division Placement</span>
<?php
    // grab all user info
    $userDataResults = runQuery( "select firstName, lastName, Conference.name as confName, " . 
                                 "Division.name as divName from User left join SeasonResult using (userID) " . 
                                 "left join Division using (divID) left join Conference using (confID) " . 
                                 "where season=" . $thisSeason . " order by confID, divID, " . 
                                 "lastName asc, firstName asc" );

    // show the assignments
    $currConf = "";
    $currDiv = "";
    $tableHTML = "";
    $divLists = array();
    $outIndex = -1;
    $inIndex = 0;
    while( ($row = mysqli_fetch_assoc($userDataResults)) != null )
    {
      // new conference
      if( $currConf != $row["confName"] )
      {
        // dump out the just completed conference
        if( count($divLists) > 0 )
        {
          $tableHTML .= "    <br/><br/>\n    <table style=\"width:100%;\">\n";
          $tableHTML .= "      <tr>\n        <td colspan=" . count($divLists) . 
                        " style=\"width: 100%; text-align: center;\"><span " . 
                        "style=\"font-size:16px; font-weight:bold; text-align:center;\">" . 
                        $currConf . "</span></td>\n      </tr>\n";
          for( $j=0; $j<count($divLists[0]); $j+=1 )
          {
            $tableHTML .= "      <tr style=\"width:100%;\">\n";
            for( $i=0; $i<count($divLists); $i+=1 )
            {
              $tableHTML .= "        <td style=\"width:" . (100 / count($divLists)) . "%;\">" . 
                            (($j == 0) ? "<span style=\"font-size:14px; font-weight:bold; " . 
                            "text-decoration:underline;\">" : "" ) . 
                            (isset($divLists[$i][$j]) ? $divLists[$i][$j] : "") . 
                            (($j == 0) ? "</span>" : "") . "</td>\n";
            }
            $tableHTML .= "      </tr>\n";
          }
          $tableHTML .= "    </table>\n";
        }

        // add in the new heading, and wipe the previous list
        $currConf = $row["confName"];
        $divLists = array();
        $numRows = 0;
        $outIndex = -1;
      }
      // new division
      if( $currDiv != $row["divName"] )
      {
        $currDiv = $row["divName"];
        $outIndex += 1;
        $inIndex = 0;
        $divLists[$outIndex] = array();
        $divLists[$outIndex][$inIndex] = $currDiv;
      }
      // add this guy to the current list
      $inIndex += 1;
      $divLists[$outIndex][$inIndex] = $row["firstName"] . " " . $row["lastName"];
      if( count($divLists[$outIndex]) > $numRows )
      {
        $numRows = count($divLists[$outIndex]);
      }
    }

    // dump out the just completed conference
    if( count($divLists) > 0 )
    {
      $tableHTML .= "    <br/><br/>\n    <table style=\"width:100%;\">\n";
      $tableHTML .= "      <tr>\n        <td colspan=" . count($divLists) . 
                    " style=\"width: 100%; text-align: center;\"><span " . 
                    "style=\"font-size:16px; font-weight:bold; text-align:center;\">" . 
                    $currConf . "</span></td>\n      </tr>\n";
      for( $j=0; $j<$numRows; $j+=1 )
      {
        $tableHTML .= "      <tr style=\"width:100%;\">\n";
        for( $i=0; $i<count($divLists); $i+=1 )
        {
          $tableHTML .= "        <td style=\"width:" . (100 / count($divLists)) . "%;\">" . 
                        (($j == 0) ? "<span style=\"font-size:14px; font-weight:bold; " . 
                        "text-decoration:underline;\">" : "" ) . 
                        (isset($divLists[$i][$j]) ? $divLists[$i][$j] : "") . 
                        (($j == 0) ? "</span>" : "") . "</td>\n";
        }
        $tableHTML .= "      </tr>\n";
      }
      $tableHTML .= "    </table>\n";
    }

    // dump it out
    echo $tableHTML;
?>
