<?php
    if( isset($addedNewUser) )
    {
      echo "    <span style=\"font-size:18px; font-weight:bold; color:#FF0000\">Added user " .
           $addedNewUser . (($username != "") ? (" (" . $username . ")") : "") . "</span><br/><br/>\n";
    }
?>
    <span style="font-size:18px; font-weight:bold;">Add New User</span>
    <form action="." method="post" id="addForm">
      <input type="hidden" name="adminTask" value="addUser" />
      <table>
        <tr>
          <td><span style="text-align:right;">Email</span></td>
          <td><input type="text" name="addEmail" id="addEmail" /></td>
        </tr>
        <tr>
          <td><span style="text-align:right;">Username</span></td>
          <td><input type="text" name="addUsername" /></td>
        </tr>
        <tr>
          <td><span style="text-align:right;">Password</span></td>
          <td><input type="text" name="addPassword" id="addPassword" /></td>
        </tr>
        <tr>
          <td><span style="text-align:right;">First Name</span></td>
          <td><input type="text" name="addFirstName" id="addFirstName" /></td>
        </tr>
        <tr>
          <td><span style="text-align:right;">Last Name</span></td>
          <td><input type="text" name="addLastName" id="addLastName" /></td>
        </tr>
        <tr>
          <td colspan="2"><input type="button" value="Add This User" onClick="TryAdd();"/></td>
        </tr>
      </table>
    </form>
<?php
    if( isset($addError) && $addError != "" )
    {
      echo "    <span style=\"color:#FF0000; font-weight:bold;\">" . $addError . "</span>\n";
    }
?>
    <script type="text/javascript">
      function TryAdd()
      {
        if( document.getElementById("addEmail").value == "" )
        {
          alert("Email field must be filled in.");
        }
        else if( document.getElementById("addPassword").value == "" )
        {
          alert("Password field must be filled in.");
        }
        else if( document.getElementById("addFirstName").value == "" )
        {
          alert("First name field must be filled in.");
        }
        else if( document.getElementById("addLastName").value == "" )
        {
          alert("Last name field must be filled in.");
        }
        else
        {
          document.getElementById("addForm").submit();
        }
      }
    </script>

