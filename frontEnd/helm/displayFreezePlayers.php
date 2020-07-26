    <span style="font-size:18px; font-weight:bold;">Freeze Players for <?php echo $thisSeason; ?> Season</span>
    <form action="." method="post">
      <input type="hidden" name="adminTask" value="freezePlayer" />
      <table>
<?php
    // grab frozen user info
    $frozenResults = runQuery( "select User.userID as userID, firstName, lastName, season " . 
                               "from User join SeasonResult on ((User.userID=SeasonResult.userID) " . 
                               "and (season=" . $thisSeason . ")) join FrozenUser on (User.userID=FrozenUser.userID) " . 
                               "order by lastName asc, firstName asc" );
    $frozenIDs = array();
    $i = 0;
    while( ($row = mysqli_fetch_assoc( $frozenResults )) != null )
    {
      $frozenIDs[$i] = $row["userID"];
      $i += 1;
    }

    // grab all user info
    $userDataResults = runQuery( "select User.userID as userID, firstName, lastName, season " . 
                                 "from User join SeasonResult on ((User.userID=SeasonResult.userID) " . 
                                 "and (season=" . $thisSeason . ")) " . 
                                 "order by lastName asc, firstName asc" );

    // see how many rows there are
    $columnCount = 6;
    $count = ceil(mysqli_num_rows( $userDataResults ) / $columnCount);

    // grab them all
    $rows = array();
    $i = 0;
    while( ($row = mysqli_fetch_assoc( $userDataResults )) != null )
    {
      $rows[$i] = $row;
      $i += 1;
    }

    // show them all
    for( $i=0; $i<$count; $i+=1 )
    {
      echo "        <tr style=\"width:100%;\">\n";
      for( $j=$i; $j<count($rows); $j+=$count)
      {

        echo "          <td style=\"width:" . (15/$columnCount) . 
             "%\"><input type=\"checkbox\" name=\"freeze" . $rows[$j]["userID"] . "\"";
        if( in_array($rows[$j]["userID"], $frozenIDs) )
        {
          echo " checked";
        }
        echo " value=\"doIt\" /></td>\n";
        echo "          <td style=\"width:" . (85/$columnCount) . "%\">" . $rows[$j]["firstName"] . " " . 
             $rows[$j]["lastName"] . "<input type=\"hidden\" name=\"wasFrozen" . $rows[$j]["userID"] . 
             "\" value=\"" . (in_array($rows[$j]["userID"], $frozenIDs) ? "Y" : "N") . "\" /></td>\n";
      }
      echo "        </tr>\n";
    }
?>
        <tr>
          <td colspan="2"><input type="submit" value="Update The Entries" /></td>
        </tr>
      </table>
    </form>
<?php
    if( isset($manageError) && $manageError != "" )
    {
      echo "    <span style=\"color:#FF0000; font-weight:bold;\">" . $manageError . "</span>\n";
    }
?>

