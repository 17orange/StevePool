<?php

include_once "SqlException.php";

	global $link, $realDB; 

	$STAGING = true;

	$username = "StevePoolUser";
	$password = '$t3v3P00lU$3r';
	$realDB   = "StevePool";
	$link     = mysqli_connect( "localhost",$username,$password, $realDB) or die("Connect Unsuccessful!".mysqli_error());
	
function runQuery( $query, $db=null, $echo=false ){
	global $live, $link, $realDB; 
	global $username, $password;         
	if( null == $db ){
		$db = $realDB;
	}
	if( !$live and $echo){
		echo( "<p>$query</p>\n" );
	}
	
	$doACleanThing = false;
	$doAThing = false;
	try {
		if( !mysqli_ping( $link ) ) {
			mysqli_close( $link );
			$link = mysqli_connect( "localhost",$username,$password, $realDB) or die("Connect Unsuccessful!".mysql_error());
		}
		$resultSet=mysqli_query($link, $query);
		if( !$resultSet ){
			throw new SqlException( mysqli_error( $link ) );
		}
	} catch (Exception $e) {
		if($doACleanThing) {
			echo "There was a problem!  Try logging out and back in.\n";

			exit();
		} else if($doAThing)
			echo 'Caught exception: ',  mysqli_errno($link), ' -> ', $e->getMessage(), "\n";
	}

	// result set will be closed when page is done.
	return $resultSet;
}

function getArrayFromResultSet( $resultSet ){
	$result = array();
	while( $row = mysqli_fetch_assoc( $resultSet ) ){
		array_push( $result, $row );
	}
	return $result;
}

function list_files($dir, $prefix="", $suffix=""){
	$result = array();
	if(is_dir($dir)){
		if($handle = opendir($dir)){
			while(($file = readdir($handle)) !== false){
				if( $file != "." && 
				    $file != ".." && 
				    strpos( $file, $prefix ) == 0 &&
				    strpos( $file, $suffix ) ){
					array_push( $result, $file );
				}
			}
			closedir($handle);
		}
	}
	return $result;
}
	
function getAList( $query, $colName=null, $dbName=null ){
	if( $colName == null ){
		// most of these queries are 'select name from ...'
		// so if it's a typical case, grab the word "name" from this.
		$split = split( " ", $query );
		$colName = $split[1];
	}
	
	// we assume db is $realDB (Skytopia) but you can override that
	// to get at a test db or Auth or something.
	global $realDB;
	if( $dbName == null ){
		$dbName = $realDB;
	}
	
	$resultSet = runQuery( $query, $dbName, false );
	$result = array();
	while( $row = mysqli_fetch_assoc( $resultSet ) ){
		array_push( $result, $row[$colName] );
	}
	
	return $result;
}

function getAScalar( $query, $colName=null, $dbName=null ){
	$result = getAList( $query, $colName, $dbName );
	return $result[0];
}



######################################################################
#
# HTML & HTTP STUFF
#
######################################################################
# a simple dictionary to convert from compact abbreviations to their
# expanded forms.

function abbreviate( $strings, $type="sql" ){
	global $sqlAbbrevs, $urlAbbrevs, $dispAbbrevs;
	$abbrevs = array();
	switch( $type ){
		case "url":
			$abbrevs = $urlAbbrevs;
			break;
		case "disp":
			$abbrevs = $dispAbbrevs;
			break;
		case "sql":
		default:
			$abbrevs = $sqlAbbrevs;
			break;
	}
	
	if( is_array( $strings ) ){
		$result = array();
		foreach( $strings as $s ){
			array_push( $result, $abbrevs[$s] );
		}
		return $result;
	} else {
		return $abbrevs[$strings];
	}
}

function makeTable( $resultSet,
										$headers=null,
										$abbreviations=null,
										$ranked=true,
										$tableWidth=null,
										$highlightWord=null ){
	$result = "<table border=1 ";
	if( $tableWidth != null ){
		$result = "$result width=\"$tableWidth\"";
	}
	$result = "$result>\n";
	if( $ranked ){
		$result= "$result<th>&nbsp;</th>\n";
	}
	
	// the headers to display atop the table
	if( $headers != null ){
		foreach( $headers as $header ){
			$result = $result . "<th>$header</th>\n";
		}
	} else {
		$headers = array();
		for( $i = 0; $i < mysqli_num_fields( $resultSet ); $i++ ) {
			$meta = mysqli_fetch_field( $resultSet, $i );		
			$result = $result . "<th>$meta->name</th>\n";
			array_push( $headers, $meta->name );
		}
	}
	// the actual data
	$i = 0;
	while( $row = mysqli_fetch_assoc($resultSet) ){
		$i += 1;
		$result = "$result</tr><tr>";
		if( $ranked ){
			$result = "$result<td>$i</td>\n";
		}
		foreach( $headers as $headerName ){
			$alias = $headerName;
			if( $abbreviations == "url" ){
				$alias = abbreviate( $headerName, "url" );
			}
			$result = $result . "<td>";
			$highlighted = false;
			if( $highlightWord ){
				// if the magic word is contained in the value, apply a highlight.
				$highlighted = strpos( $row[$alias], $highlightWord );
				echo( $highlighted );
				if( $highlighted === 0 ){
					echo( "append that shit <br />\n" );
					$result = "$result<div id=\"highlight\"><b>\n";
				}
			}
			$result = $result . $row[$alias];
			if( $highlighted === 0 ){
				$result = "$result</b></div>";
			}
			$result = $result . "</td>\n";
		}
	}
	
	$result = $result . "</tr></table>\n";
	return $result;
}


function isColumnAbbrev( $abbr ){
	global $dispAbbrevs;
	if( $dispAbbrevs[$abbr] ){
		switch( $abbr ){
			default:
				return true;
		}
	} else{
		return false;
	}
}

function isColumnDescending( $colAbbr ){
	global $dispAbbrevs;
	if( $dispAbbrevs[$colAbbr] ){
		switch( $colAbbr ){
			default:
				return true;
		}
	} else{
		return false;
	}
}

$sqlAbbrevs = array( 
);

$dispAbbrevs = array( 
);

# now, the ones for conversion from form dropdowns to URL params.
$urlAbbrevs = array( 
);

  // some cookie stuff to keep them logged in
  // they have a cookie and no session
  if( isset($_COOKIE["spsID"]) && !isset($_SESSION["spsID"]) )
  {
    $_SESSION["spsID"] = $_COOKIE["spsID"];
    // extend the cookie
    setcookie("spsID", $_SESSION["spsID"], time() + 3600 * 24 * 30, "/", $_SERVER["SERVER_NAME"]);
  }

  function getIcon($team, $season)
  {
    if( $team == "" || $team == "TBD")
    {
      return "icons/" . $season . "/nfl.png";
    }

    $str = "icons/" . $season . "/";

    $teamInfo = mysqli_fetch_assoc( RunQuery( "select lower(nickname) as name from Team where teamID='" . $team . "'" ) );
    $str .= $teamInfo["name"];

    $str .= ".svg";
    return $str;
  }

  function formatTime($pick)
  {
    if( $pick["status"] == 3 )
    {
      return "FINAL<br>" . $pick["awayTeam"] . " " . $pick["awayScore"] . "<br>" . $pick["homeTeam"] . " " . $pick["homeScore"];
    }
    else if( $pick["status"] == 2 )
    {
      return "LIVE<br>" . $pick["awayTeam"] . " " . $pick["awayScore"] . "<br>" . $pick["homeTeam"] . " " . $pick["homeScore"];
    }
    else
    {
      return strftime("%a<br>%b %e<br>%l:%M%p", strtotime($pick["gameTime"]));
    }
  }
?>
