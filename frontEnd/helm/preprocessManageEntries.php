<?php
    // get the list of guys that need removed and the guys that need added
    $userQuery = runQuery( "select userID from User" );
    while( ($row = mysqli_fetch_assoc( $userQuery )) != null )
    {
      // see if we need to add them
      if( isset( $_POST["wasEnroll" . $row["userID"]]) && $_POST["wasEnroll" . $row["userID"]] == "N" &&
          isset( $_POST["enroll" . $row["userID"]]) && $_POST["enroll" . $row["userID"]] == "doIt")
      {
        runQuery( "call AddUserToSeason(" . $row["userID"] . ")" );
      }
      // see if we need to remove them
      else if( isset( $_POST["wasEnroll" . $row["userID"]]) && $_POST["wasEnroll" . $row["userID"]] == "Y" &&
               (!isset( $_POST["enroll" . $row["userID"]]) || $_POST["enroll" . $row["userID"]] != "doIt") )
      {
        runQuery( "call RemoveUserFromSeason(" . $row["userID"] . ")" );
      }
    }

    // check for dupes
    $manageError = "Entries Updated!";
?>
