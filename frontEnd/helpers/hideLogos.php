<?php
  session_start();

  include "../util.php";
?>
<!DOCTYPE html>
<html xmlns="https://www.w3.org/1999/xhtml" lang="en">
  <head>
    <script type="text/javascript" src="../includes/jquery-1.11.1.js"></script>
  </head>
  <body>
    <script type="text/javascript">
<?php
  if( isset($_POST["doIt"]) && $_POST["doIt"] == "true" )
  {
    $_SESSION["spHideLogos"] = "TRUE";

    setcookie("spHideLogos", $_SESSION["spHideLogos"], time() + 3600 * 24 * 30, "/", $_SERVER["SERVER_NAME"]);
  }
  else if( isset($_POST["doIt"]) && $_POST["doIt"] == "false" )
  {
    // save the session ID for future use
    unset($_SESSION["spHideLogos"]);
    setcookie("spHideLogos", "", time() - 3600 * 24 * 30, "/", $_SERVER["SERVER_NAME"]);
  }
?>
      parent.location.search = "?refresh=" + Math.random();
    </script>
  </body>
</html>
