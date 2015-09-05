<?php
    $editedUserID = mysqli_real_escape_string($link, $_POST["editUserID"]);
    $email = mysqli_real_escape_string($link, $_POST["editEmail"]);
    $username = mysqli_real_escape_string($link, $_POST["editUsername"]);
    $password = mysqli_real_escape_string($link, $_POST["editPassword"]);
    $firstName = mysqli_real_escape_string($link, $_POST["editFirstName"]);
    $lastName = mysqli_real_escape_string($link, $_POST["editLastName"]);
    $divID = mysqli_real_escape_string($link, $_POST["editDivID"]);

    // check for dupes
    $editError = "";
    $results = mysqli_fetch_assoc( runQuery( "select count(*) as num from User where email='" . 
                                             $email . "' and userID!=" . $editedUserID ) );
    $results2 = mysqli_fetch_assoc( runQuery( "select count(*) as num from User where username='" . 
                                              $username . "' and userID!=" . $editedUserID ) );
    if( $results["num"] > 0 )
    {
      $editError = "That email address is in use by another player.";
    }
    else if( $results2["num"] > 0 )
    {
      $editError = "That username is in use by another player.";
    }
    else
    {
      runQuery( "call EditUser(" . $editedUserID . ",'" . $username . "','" . $email .
                "'," . (($password == "") ? "null" : ("md5('" . $password . "')")) . ",'" . 
                $firstName . "','" . $lastName . "'," . $divID . ")" );
      $editError = "User edited successfully!";
    }
?>
