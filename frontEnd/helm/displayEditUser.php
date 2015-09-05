<?php
    $userSet = isset($_SESSION["editUserID"]);
    if( $userSet )
    {
      // grab their info
      $thisUserData = mysqli_fetch_assoc( runQuery( "select * from User where userID=" . $_SESSION["editUserID"] ) );
    }

    // grab all user info
    $userDataResults = runQuery( "select * from User order by lastName asc, firstName asc" );
?>
    <span style="font-size:18px; font-weight:bold;">Edit User</span>
    <form action="." method="post">
      <span>Selected User</span>
      <input type="hidden" name="adminTask" value="selectEditUser" />
      <select name="selectedUserID">
<?php
    while( ($userRow = mysqli_fetch_assoc( $userDataResults )) != null )
    {
      echo "        <option value=\"" . $userRow["userID"] . "\"" . 
           (($userSet && ($_SESSION["editUserID"] == $userRow["userID"])) ? " selected" : "") . ">" . 
           $userRow["firstName"] . " " . $userRow["lastName"] . "</option>\n";
    }
?>
      </select>
      <input type="submit" value="Select This User" />
    </form>
    <br/><br/>
    <form action="." method="post" id="editForm">
      <input type="hidden" name="adminTask" value="editUser" />
      <input type="hidden" name="editUserID" value="<?php echo ($userSet ? $thisUserData["userID"] : ""); ?>" />
      <table>
        <tr>
          <td><span style="text-align:right;">Email</span></td>
          <td><input type="text" name="editEmail" id="editEmail" value="<?php 
    if( isset($_POST["editEmail"]) )
    {
      echo $_POST["editEmail"];
    }
    else if($userSet)
    {
      echo $thisUserData["email"];
    }
    else
    {
      echo "";
    } 
?>" /></td>
        </tr>
        <tr>
          <td><span style="text-align:right;">Username</span></td>
          <td><input type="text" name="editUsername" id="editUsername" value="<?php 
    if( isset($_POST["editUsername"]) )
    {
      echo $_POST["editUsername"];
    }
    else if($userSet)
    {
      echo $thisUserData["username"];
    }
    else
    {
      echo "";
    } 
?>" /></td>
        </tr>
        <tr>
          <td><span style="text-align:right;">Password</span></td>
          <td><input type="text" name="editPassword" id="editPassword" value="<?php 
    if( isset($_POST["editPassword"]) )
    {
      echo $_POST["editPassword"];
    }
    else
    {
      echo "";
    } 
?>"/></td>
        </tr>
        <tr>
          <td><span style="text-align:right;">First Name</span></td>
          <td><input type="text" name="editFirstName" id="editFirstName" value="<?php 
    if( isset($_POST["editFirstName"]) )
    {
      echo $_POST["editFirstName"];
    }
    else if($userSet)
    {
      echo $thisUserData["firstName"];
    }
    else
    {
      echo "";
    } 
?>" /></td>
        </tr>
        <tr>
          <td><span style="text-align:right;">Last Name</span></td>
          <td><input type="text" name="editLastName" id="editLastName" value="<?php 
    if( isset($_POST["editLastName"]) )
    {
      echo $_POST["editLastName"];
    }
    else if($userSet)
    {
      echo $thisUserData["lastName"];
    }
    else
    {
      echo "";
    } 
?>" /></td>
        </tr>
        <tr>
<?php
    $thisSeason = mysqli_fetch_assoc( runQuery("select value from Constants where name='fetchSeason'"));
    $thisDivision = runQuery("select concat(Conference.name, '/', Division.name) as name " . 
                    "from SeasonResult left join Division using (divID) left join Conference using (confID) where userID=" . 
                    $thisUserData["userID"] . " and season=" . $thisSeason["value"] );
?>
          <td><span style="text-align:right;"><?php echo $thisSeason["value"]; ?> Conference/Division</span></td>
<?php
    if( mysqli_num_rows($thisDivision) == 0 )
    {
?>
          <td><span style="text-align:right;">Not entered in <?php echo $thisSeason["value"]; ?> season</span></td>
<?php
    } else {
?>
          <td><select name="editDivID">
<?php
      $thisDivision = mysqli_fetch_assoc( $thisDivision );
      $allDivisions = runQuery("select distinct(Division.divID) as divID, concat(Conference.name, '/', Division.name) as name " . 
                      "from SeasonResult join Division using (divID) join Conference using (confID) where season=" . 
                      $thisSeason["value"] . " order by Division.divID");
      $hasDivision = 0;
      while( ($thisDiv = mysqli_fetch_assoc( $allDivisions )) != null )
      {
        $hasDivision |= ($thisDiv["name"] == $thisDivision["name"]);
        echo "            <option value=\"" . $thisDiv["divID"] . "\"" . (($thisDiv["name"] == $thisDivision["name"]) ? " selected" : "") . 
             ">" . $thisDiv["name"] . "</option>\n";
      }
      echo "            <option value=\"null\"" . ($hasDivision ? "" : " selected") . ">No Division Yet</option>\n";
?>
          </select></td>
<?php
    }
?>
        </tr>
        <tr>
          <td colspan="2"><input type="button" value="Update This User" onClick="TryEdit();"<?php echo ($userSet ? "" : " disabled"); ?>/></td>
        </tr>
      </table>
    </form>
<?php
    if( isset($editError) && $editError != "" )
    {
      echo "    <span style=\"color:#FF0000; font-weight:bold;\">" . $editError . "</span>\n";
    }
?>
    <script type="text/javascript">
      function TryEdit()
      {
        if( document.getElementById("editEmail").value == "" )
        {
          alert("Email field must be filled in.");
        }
        else if( document.getElementById("editUsername").value == "" )
        {
          alert("Username field must be filled in.");
        }
        else if( document.getElementById("editFirstName").value == "" )
        {
          alert("First name field must be filled in.");
        }
        else if( document.getElementById("editLastName").value == "" )
        {
          alert("Last name field must be filled in.");
        }
        else
        {
          document.getElementById("editForm").submit();
        }
      }
    </script>

