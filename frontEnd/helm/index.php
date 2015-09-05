<?php
session_start();

include "util.php";

// session protection stuff
if (isset($_SESSION["LAST_ACTIVITY"]) && (time() - $_SESSION["LAST_ACTIVITY"] > 1800)) {
  // last request was more than 30 minutes ago
  session_unset();     // unset $_SESSION variable for the run-time 
  session_destroy();   // destroy session data in storage
}
$_SESSION["LAST_ACTIVITY"] = time(); // update last activity time stamp

// see if they punched in the right password
if( isset($_POST["SPusername"]) && isset($_POST["SPpassword"]) && 
    $_POST["SPusername"] == "4dm!n" && $_POST["SPpassword"] == '$P3!CH3R' )
{
  session_regenerate_id();
  $_SESSION["SPgoodLogin"] = session_id();
}
// they logged out
else if( isset($_POST["doLogout"]) )
{
  session_unset();     // unset $_SESSION variable for the run-time 
  session_destroy();   // destroy session data in storage
}

// if they dont have a valid session, show the login screen
if (!isset($_SESSION["SPgoodLogin"]) || $_SESSION["SPgoodLogin"] != session_id() )
{
?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" lang="en">
  <head>
    <title>This isn't the page you are looking for.</title>
  </head>
  <body style="background:url(obiwan.png) no-repeat center center fixed; background-size:cover;">
    <form action="." method="post">
      <input name="SPusername" type="text" /><br/>
      <input name="SPpassword" type="password" /><br/>
      <input type="submit" value="Submit"/>
    </form>
  </body>
</html>
<?php
} 
// they have a login session, so show the tools
else
{
  // see which page they are on
  if( !isset($_SESSION["pageName"]) )
  {
    $_SESSION["pageName"] = "managePicks";
  }
  else if( isset($_POST["newPage"]) ) 
  {
    $_SESSION["pageName"] = $_POST["newPage"];
  }

  // do the preprocessing

  // see which season this is
  $thisSeason = mysqli_fetch_assoc( runQuery( "select value from Constants where name='fetchSeason'" ) );
  $thisSeason = $thisSeason["value"];
  $thisWeek = mysqli_fetch_assoc( runQuery( "select value from Constants where name='fetchWeek'" ) );
  $thisWeek = $thisWeek["value"];

  // add user
  if( isset($_POST["adminTask"]) && $_POST["adminTask"] == "addUser" )
  {
    include "preprocessAddUser.php";
  }
  // select an editable user
  else if( isset($_POST["adminTask"]) && $_POST["adminTask"] == "selectEditUser" )
  {
    $_SESSION["editUserID"] = $_POST["selectedUserID"];
  }
  // edit user
  else if( isset($_POST["adminTask"]) && $_POST["adminTask"] == "editUser" )
  {
    include "preprocessEditUser.php";
  }
  // manage season entrants
  else if( isset($_POST["adminTask"]) && $_POST["adminTask"] == "manageEntries" )
  {
    include "preprocessManageEntries.php";
  }
  // manage divisions
  else if( isset($_POST["adminTask"]) && $_POST["adminTask"] == "manageDivisions" )
  {
    include "preprocessManageDivisions.php";
  }
  // manage games
  else if( isset($_POST["adminTask"]) && $_POST["adminTask"] == "manageGames" )
  {
    include "preprocessManageGames.php";
  }
  // manage picks
  else if( isset($_POST["adminTask"]) && $_POST["adminTask"] == "managePicks" )
  {
    include "preprocessManagePicks.php";
  }
?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" lang="en">
  <head>
    <title>Steve's Pool Admin Page</title>
  </head>
  <body>
<?php
  // nav menu
  include "navMenu.php";

  // logic for adding a user page
  if( $_SESSION["pageName"] == "addUser" )
  {
    include "displayAddUser.php";
  } 
  // logic for editing a user
  else if( $_SESSION["pageName"] == "editUser" )
  {
    include "displayEditUser.php";
  }
  // logic for managing entries
  else if( $_SESSION["pageName"] == "manageEntries" )
  {
    include "displayManageEntries.php";
  }
  // logic for managing divisions
  else if( $_SESSION["pageName"] == "manageDivisions" )
  {
    include "displayManageDivisions.php";
  }
  // logic for managing games
  else if( $_SESSION["pageName"] == "manageGames" )
  {
    include "displayManageGames.php";
  }
  // logic for managing picks
  else if( $_SESSION["pageName"] == "managePicks" )
  {
    include "displayManagePicks.php";
  }
  // logic for dumping data
  else if( $_SESSION["pageName"] == "dataDump" )
  {
    include "displayDataDumps.php";
  }
?>
  </body>
</html>
<?php
} 
?>
