<?php
    $addedNewUser = mysqli_real_escape_string($link, $_POST["addEmail"]);
    $username = mysqli_real_escape_string($link, $_POST["addUsername"]);
    if( $username == "" )
    {
      $username = $addedNewUser;
    }
    $password = mysqli_real_escape_string($link, $_POST["addPassword"]);
    $firstName = mysqli_real_escape_string($link, $_POST["addFirstName"]);
    $lastName = mysqli_real_escape_string($link, $_POST["addLastName"]);

    // check for dupes
    $addError = "";
    $results = mysqli_fetch_assoc( runQuery( "select count(*) as num from User where email='" . 
                                             $addedNewUser . "'" ) );
    $results2 = mysqli_fetch_assoc( runQuery( "select count(*) as num from User where username='" . 
                                              $username . "'" ) );
    if( $results["num"] > 0 )
    {
      $addError = "That email address is already in use.";
    }
    else if( $results2["num"] > 0 )
    {
      $addError = "That username is already in use.";
    }
    else
    {
      runQuery( "call AddUser('" . (($username == "") ? $addedNewUser : $username) . "','" . $addedNewUser .
                "','" . $password . "','" . $firstName . "','" . $lastName . "')" );
    }
?>
