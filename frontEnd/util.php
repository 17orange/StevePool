<?php

  include_once "SqlException.php";

  global $link, $realDB; 

  $STAGING = false;

  $username = "StevePoolUser";
  $password = '$t3v3P00lU$3r';
  $realDB   = "StevePool";
  $link     = mysqli_connect( "localhost",$username,$password, $realDB) or die("Connect Unsuccessful!".mysqli_error());

  $memcache = new Memcached();
  $memcache->addServer("localhost", 11211) or die("Could not connect (memcache)");
	
  function RunQuery( $query, $cacheThis=true ){
    global $live, $link, $realDB; 
    global $username, $password, $memcache;

    $db = $realDB;	
    $doACleanThing = false;
    $doAThing = false;

    try {
      $queryHash = "QUERYLENGTH" . strlen($query) . "HASH" . md5($query);
      if( !$cacheThis || $memcache->get($queryHash) === false ) {
        if( !mysqli_ping( $link ) ) {
          mysqli_close( $link );
          $link = mysqli_connect( "localhost",$username,$password, $realDB) or die("Connect Unsuccessful!".mysql_error());
        }
        $freshResultSet=mysqli_query($link, $query);
        if( !$freshResultSet ){
          throw new SqlException( mysqli_error( $link ) );
        }

        $resultSet = array();
        while( ($thisRow = mysqli_fetch_assoc($freshResultSet)) != null ) {
          $resultSet[] = $thisRow;
        }

        if( $cacheThis ) {
          $memcache->set($queryHash, $resultSet, 3600) or die("Failed to save data on server");
        }
      } else {
        $resultSet = $memcache->get($queryHash);
      }
    } catch (Exception $e) {
      if($doACleanThing) {
        echo "There was a problem!  Try logging out and back in.\n";

        exit();
      } else if($doAThing) {
        echo 'Caught exception: ',  mysqli_errno($link), ' -> ', $e->getMessage(), "\n";
      }
    }

    // result set will be closed when page is done.
    return $resultSet;
  }

  // some cookie stuff to keep them logged in
  // they have a cookie and no session
  if( isset($_COOKIE["spsID"]) && !isset($_SESSION["spsID"]) )
  {
    $_SESSION["spsID"] = $_COOKIE["spsID"];
    // extend the cookie
    setcookie("spsID", $_SESSION["spsID"], time() + 3600 * 24 * 30, "/", $_SERVER["SERVER_NAME"]);
  }

  // get the aliases and map them correctly
  $teamAliases = array("TIE" => "TIE");
  $results = RunQuery( "select teamID, alias from Team");
  foreach( $results as $thisTeam ) {
    $teamAliases[$thisTeam["teamID"]] = $thisTeam["alias"];
  }

  function getIcon($team, $season)
  {
    if( $team == "" || $team == "TBD")
    {
      return "icons/" . $season . "/nfl.png";
    }

    $str = "icons/" . $season . "/";

    $teamInfo = RunQuery( "select lower(nickname) as name from Team where teamID='" . $team . "'" );
    $teamInfo = $teamInfo[0];
    $str .= $teamInfo["name"];

    $str .= ".svg";
    return $str;
  }

  function formatTime($pick)
  {
    if( isset($pick["status"]) && $pick["status"] == 3 )
    {
      return "FINAL<br>" . $pick["awayTeam"] . " " . $pick["awayScore"] . "<br>" . $pick["homeTeam"] . " " . $pick["homeScore"];
    }
    else if( isset($pick["status"]) && $pick["status"] == 2 )
    {
      return "LIVE<br>" . $pick["awayTeam"] . " " . $pick["awayScore"] . "<br>" . $pick["homeTeam"] . " " . $pick["homeScore"];
    }
    else
    {
      return strftime("%a<br>%b %e<br>%l:%M%p", strtotime($pick["gameTime"]));
    }
  }

  function formatTimeMobile($pick)
  {
    if( isset($pick["status"]) && $pick["status"] == 3 )
    {
      return "FINAL<br>" . $pick["awayTeam"] . " " . $pick["awayScore"] . " - " . $pick["homeTeam"] . " " . $pick["homeScore"];
    }
    else if( isset($pick["status"]) && $pick["status"] == 2 )
    {
      return "LIVE<br>" . $pick["awayTeam"] . " " . $pick["awayScore"] . " - " . $pick["homeTeam"] . " " . $pick["homeScore"];
    }
    else
    {
      return strftime("%a %b %e<br>%l:%M%p", strtotime($pick["gameTime"]));
    }
  }
?>
