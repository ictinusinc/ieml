<?php

ignore_user_abort(true);

if (!empty($_REQUEST['payload']))
{
        $full_result = NULL;
        $return_value = NULL;
        $short_result = exec('git pull', $full_result, $return_value);

        //TODO: check and report errors in a meaningful way
}
