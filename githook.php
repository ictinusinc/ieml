<?php

ignore_user_abort(true);

if (!empty($_REQUEST['payload'])) {
	$result = `git pull`;
}
