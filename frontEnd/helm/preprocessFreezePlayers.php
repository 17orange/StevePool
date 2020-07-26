<?php
    // get the list of guys that need removed and the guys that need added
    $userQuery = runQuery( "select userID from User" );
    while( ($row = mysqli_fetch_assoc( $userQuery )) != null )
    {
      // see if we need to freeze them
      if( isset( $_POST["wasFrozen" . $row["userID"]]) && $_POST["wasFrozen" . $row["userID"]] == "N" &&
          isset( $_POST["freeze" . $row["userID"]]) && $_POST["freeze" . $row["userID"]] == "doIt")
      {
        runQuery( "call FreezeUser(" . $row["userID"] . ", 'Y')" );

        // flush cache
        $memcache = new Memcached();
        $memcache->addServer("localhost", 11211) or die("Could not connect (memcache)");
        $memcache->flush();
      }
      // see if we need to unfreeze them
      else if( isset( $_POST["wasFrozen" . $row["userID"]]) && $_POST["wasFrozen" . $row["userID"]] == "Y" &&
               (!isset( $_POST["freeze" . $row["userID"]]) || $_POST["freeze" . $row["userID"]] != "doIt") )
      {
        runQuery( "call FreezeUser(" . $row["userID"] . ", 'N')" );

        // flush cache
        $memcache = new Memcached();
        $memcache->addServer("localhost", 11211) or die("Could not connect (memcache)");
        $memcache->flush();
      }
    }

    // check for dupes
    $manageError = "Entries Updated!";
?>
