    <script type="text/javascript">
      function TryLogin()
      {
        var user = document.getElementById("loginUser").value;
        var pw = document.getElementById("loginPW").value;
        var error = document.getElementById("loginError");
        if( user == "" )
        {
          error.innerHTML = "You must enter either your username or email address.";
        }
        else if( pw == "" )
        {
          error.innerHTML = "You must enter a password.";
        }
        else
        {
          document.getElementById("loginForm").submit();
        }
      }

      function TryUpdate()
      {
        var user = document.getElementById("acctUser").value;
        var email = document.getElementById("acctEmail").value;
        var pw = document.getElementById("acctPW").value;
        var pw2 = document.getElementById("acctPW2").value;
        var pw3 = document.getElementById("acctPW3").value;
        var error = document.getElementById("acctError");
        if( user == "" )
        {
          error.innerHTML = "You must enter your username.";
        }
        else if( email == "" )
        {
          error.innerHTML = "You must enter an email address.";
        }
        else if( pw == "" )
        {
          error.innerHTML = "You must enter your current password.";
        }
        else if( pw2 != "" && pw2 != pw3 )
        {
          error.innerHTML = "New passwords don't match.";
        }
        else
        {
          document.getElementById("accountForm").submit();
        }
      }

      function ScrollDialogs()
      {
        $('#loginDialog').css( 'top', $(window).scrollTop() + 'px');
        $('#accountDialog').css( 'top', $(window).scrollTop() + 'px');
        $('#pickConfirmDialog').css( 'top', $(window).scrollTop() + 'px');
      }
    </script>
    <div id="loginDialog" style="position:absolute; width:100%; height:100%; display:none;">
      <div style="position:absolute; width:100%; height:100%; background:#000000; opacity:0.5; z-index:100;" onclick="$('#loginDialog').slideToggle('fast');"></div>
      <div style="position:relative; background:#D9DCE3; width:400px; height:275px; margin:100px auto; border:5px solid #314972; z-index:101; text-align:center; border-radius:10px; color:#6E809F;">
        <form action="helpers/login.php" method="post" id="loginForm" target="taskWindow">
          <input type="hidden" name="task" value="login" />
          <table style="border-spacing:10px; width:100%;">
            <tr>
              <td class="noBorder" colspan=2><span style="font-size:200%; font-weight:bold">Login</span></td>
            </tr>
            <tr>
            </tr>
            <tr>
              <td class="noBorder"><span style="font-size:100%; font-weight:bold">Username/Email</span></td>
              <td class="noBorder"><input name="loginUser" id="loginUser" value="<?php echo (isset($_POST["loginUser"]) ? $_POST["loginUser"] : ""); ?>"/></td>
            </tr>
            <tr>
              <td class="noBorder"><span style="font-size:100%; font-weight:bold">Password</span></td>
              <td class="noBorder"><input name="loginPW" id="loginPW" type="password" /></td>
            </tr>
            <tr>
              <td class="noBorder" colspan=2><button onClick="TryLogin(); return false;">Sign In</button></td>
            </tr>
            <tr style="height:40px;">
              <td class="noBorder" colspan=2><span id="loginError" style="font-size:100%; color:#FF0000;"></span></td>
            </tr>
            <tr>
              <td class="noBorder" colspan=2><a href="helpers/forgotPassword.php" target="taskWindow"><span style="font-size:100%;">Reset My Password</span></a></td>
            </tr>
          </table>
        </form>
      </div>
    </div>

<?php
  $acctUser = "";
  $acctEmail = "";
  if( isset($_SESSION["spsID"]) )
  {
    $results = mysqli_fetch_assoc( runQuery( "select username, email from User join Session using (userID) where sessionID=" . 
                                             $_SESSION["spsID"] ) );
    $acctUser = $results["username"];
    $acctEmail = $results["email"];
  }
?>
    <div id="accountDialog" style="position:absolute; width:100%; height:100%; display:none;">
      <div style="position:absolute; width:100%; height:100%; background:#000000; opacity:0.5; z-index:100;" onclick="$('#accountDialog').slideToggle('fast');"></div>
      <div style="position:relative; background:#D9DCE3; width:500px; height:325px; margin:100px auto; border:5px solid #314972; z-index:101; text-align:center; border-radius:10px; color:#6E809F;">
        <form action="helpers/changeAccountDetails.php" method="post" id="accountForm" target="taskWindow">
          <input type="hidden" name="task" value="account" />
          <table style="border-spacing:10px; width:100%;">
            <tr>
              <td class="noBorder" colspan=2><span style="font-size:200%; font-weight:bold">Account Details</span></td>
            </tr>
            <tr>
            </tr>
            <tr>
              <td class="noBorder"><span style="font-size:100%; font-weight:bold">Username</span></td>
              <td class="noBorder"><input name="acctUser" id="acctUser" value="<?php echo (isset($_POST["acctUser"]) ? $_POST["acctUser"] : $acctUser); ?>" style="width:80%;" /></td>
            </tr>
            <tr>
              <td class="noBorder"><span style="font-size:100%; font-weight:bold">Email</span></td>
              <td class="noBorder"><input name="acctEmail" id="acctEmail" value="<?php echo (isset($_POST["acctEmail"]) ? $_POST["acctEmail"] : $acctEmail); ?>" style="width:80%;" /></td>
            </tr>
            <tr>
              <td class="noBorder"><span style="font-size:100%; font-weight:bold">Current Password</span></td>
              <td class="noBorder"><input name="acctPW" id="acctPW" type="password" style="width:80%;" /></td>
            </tr>
            <tr>
              <td class="noBorder"><span style="font-size:100%; font-weight:bold">New Password</span></td>
              <td class="noBorder"><input name="acctPW2" id="acctPW2" type="password" style="width:80%;" /></td>
            </tr>
            <tr>
              <td class="noBorder"><span style="font-size:100%; font-weight:bold">Confirm Password</span></td>
              <td class="noBorder"><input name="acctPW3" id="acctPW3" type="password" style="width:80%;" /></td>
            </tr>
            <tr style="height:40px;">
              <td class="noBorder" colspan=2><span id="acctError" style="font-size:100%; color:#FF0000;"></span></td>
            </tr>
            <tr>
              <td class="noBorder" colspan=2><button onClick="TryUpdate(); return false;">Update Info</button></td>
            </tr>
          </table>
        </form>
      </div>
    </div>

    <div id="pickConfirmDialog" style="position:absolute; width:100%; height:100%; display:none;">
      <div style="position:absolute; width:100%; height:100%; background:#000000; opacity:0.5; z-index:100;" onclick="$('#pickConfirmDialog').slideToggle('fast');"></div>
      <div style="position:relative; background:#D9DCE3; width:500px; height:200px; margin:100px auto; border:5px solid #314972; z-index:101; text-align:center; border-radius:10px; color:#6E809F;"><br><br>
        <span style="font-size:200%; text-align:center; width:100%;">Picks saved successfully!</span><br><br><br>
        <span class="navButton" onclick="$('#pickConfirmDialog').slideToggle('fast');">OK</span>
      </div>
    </div>

