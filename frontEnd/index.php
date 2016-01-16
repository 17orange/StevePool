<?php
  session_start();

  include "util.php";

  // preprocessing logic
  include "preprocess/allPages.php";

  // logic for making picks
  if( $_SESSION["pageName"] == "makePicks" )
  {
    include "preprocess/pMakePicks.php";
  } 
  // logic for showing picks
  else if( $_SESSION["pageName"] == "showPicks" )
  {
    include "preprocess/pShowPicks.php";
  }
  // logic for showing picks
  else if( $_SESSION["pageName"] == "possibleOutcomes" )
  {
    include "preprocess/pOutcomes.php";
  }
  // logic for showing season standings
  else if( $_SESSION["pageName"] == "standings" )
  {
    include "preprocess/pStandings.php";
  }
?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" lang="en">
  <head>
    <title>Steve's NFL Pool</title>
    <link href="includes/favicon.ico" rel="icon" type="image/x-icon">
    <link href="http://fonts.googleapis.com/css?family=Fjalla+One" rel="stylesheet" type="text/css">
    <link href="http://fonts.googleapis.com/css?family=Montserrat" rel="stylesheet" type="text/css">
    <link href="includes/stevePool.css" rel="stylesheet" type="text/css">
    <script type="text/javascript" src="includes/jquery-1.11.1.js"></script>
    <script type="text/javascript" src="includes/smartSort.js"></script>
    <script type="text/javascript">
      history.replaceState({}, null, "/stevePool<?php echo ($STAGING ? "Staging" : ""); ?>/");
    </script>
  </head>
  <body class="fjalla" onscroll="ScrollDialogs();">
<?php
  // modal dialogs
  include "display/dialogs.php";

  // nav menu
  include "display/navMenu.php";

  // display for making picks
  if( $_SESSION["pageName"] == "makePicks" )
  {
    if( isset( $_SESSION["spsID"] ) )
    {
      include "display/dMakePicks.php";
    }
    else
    {
      include "display/mustBeLoggedIn.php";
    }
  } 
  // display for showing picks
  else if( $_SESSION["pageName"] == "showPicks" )
  {
    include "display/dShowPicks.php";
  }
  // display for possible outcomes
  else if( $_SESSION["pageName"] == "possibleOutcomes" )
  {
    include "display/dOutcomes.php";
  }
  // display for showing season standings
  else if( $_SESSION["pageName"] == "standings" )
  {
    include "display/dStandings.php";
  }
?>
    <iframe name="taskWindow" id="taskWindow" frameborder="0" style="width:0px;height:0px"></iframe>
  </body>
</html>

