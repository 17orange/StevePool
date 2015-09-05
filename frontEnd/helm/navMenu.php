    <form action="." method="post">
      <input type="hidden" name="doLogout" value="yep"/>
      <input type="submit" value="Logout"/>
    </form>
    <br/>
    <span style="font-size:18px; font-weight:bold;">Menu</span>
    <form action="." method="post">
      <input type="hidden" name="newPage" value="managePicks"/>
      <input type="submit" value="Manage Picks"<?php echo ($_SESSION["pageName"] == "managePicks") ? " disabled" : ""; ?>/>
    </form>
    <form action="." method="post">
      <input type="hidden" name="newPage" value="manageGames"/>
      <input type="submit" value="Manage Games"<?php echo ($_SESSION["pageName"] == "manageGames") ? " disabled" : ""; ?>/>
    </form>
    <form action="." method="post">
      <input type="hidden" name="newPage" value="manageDivisions"/>
      <input type="submit" value="Manage Divisions"<?php echo ($_SESSION["pageName"] == "manageDivisions") ? " disabled" : ""; ?>/>
    </form>
    <form action="." method="post">
      <input type="hidden" name="newPage" value="manageEntries"/>
      <input type="submit" value="Manage Entries"<?php echo ($_SESSION["pageName"] == "manageEntries") ? " disabled" : ""; ?>/>
    </form>
    <form action="." method="post">
      <input type="hidden" name="newPage" value="addUser"/>
      <input type="submit" value="Add New User"<?php echo ($_SESSION["pageName"] == "addUser") ? " disabled" : ""; ?>/>
    </form>
    <form action="." method="post">
      <input type="hidden" name="newPage" value="editUser"/>
      <input type="submit" value="Edit Existing User"<?php echo ($_SESSION["pageName"] == "editUser") ? " disabled" : ""; ?>/>
    </form>
    <form action="." method="post">
      <input type="hidden" name="newPage" value="dataDump"/>
      <input type="submit" value="Data Dumps"<?php echo ($_SESSION["pageName"] == "dataDump") ? " disabled" : ""; ?>/>
    </form>
    <br/><br/>

