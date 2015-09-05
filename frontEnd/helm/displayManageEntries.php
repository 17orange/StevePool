    <span style="font-size:18px; font-weight:bold;">Manage Entries for <?php echo $thisSeason; ?> Season</span>
    <form action="." method="post">
      <input type="hidden" name="adminTask" value="manageEntries" />
      <table>
<?php
    // grab all user info
    $userDataResults = runQuery( "select User.userID as userID, firstName, lastName, season " . 
                                 "from User left join SeasonResult on ((User.userID=SeasonResult.userID) " . 
                                 "and ((season=" . $thisSeason . ") or (season is null))) " . 
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
             "%\"><input type=\"checkbox\" name=\"enroll" . $rows[$j]["userID"] . "\"";
        if( $rows[$j]["season"] == $thisSeason )
        {
          echo " checked";
        }
        echo " value=\"doIt\" /></td>\n";
        echo "          <td style=\"width:" . (85/$columnCount) . "%\">" . $rows[$j]["firstName"] . " " . 
             $rows[$j]["lastName"] . "<input type=\"hidden\" name=\"wasEnroll" . $rows[$j]["userID"] . 
             "\" value=\"" . (($rows[$j]["season"] == $thisSeason) ? "Y" : "N") . "\" /></td>\n";
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

