<?php
if (isset($_REQUEST['error']) && $_REQUEST['error'] == 'fail') {
?>
	<strong>Alert:</strong> Login Failed.</p>
<?php
}
?>
<form method="post" id="formLogin">
	<input type="hidden" name="a" value="auth" />
	<label for="loginEmail"><?php echo trans_phrase('user', $lang); ?>:</label>
	<input type="text" name="loginEmail" id="loginEmail" /><br />
	<label for="loginPassword"><?php echo trans_phrase('password', $lang); ?>:</label>
	<input type="password" name="loginPassword" id="loginPassword" /><br />
	<input class="btn" type="submit" name="submit" value="Login" />
</form>
	