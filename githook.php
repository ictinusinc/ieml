<?php

ignore_user_abort(true);

if (!empty($_REQUEST['payload']))
{
	$pipes = NULL;
	$p_resource = proc_open('git pull', array(
		array('pipe', 'r'), array('pipe', 'w'), array('pipe', 'w')
	), $pipes);


	if ($p_resource === FALSE)
	{
		echo 'Failed to open \'git pull\' process.' . "\n";
	}
	else
	{
		fclose($pipes[0]);

		$stdout = stream_get_contents($pipes[1]);
		fclose($pipes[1]);

		$stderr = stream_get_contents($pipes[2]);
		fclose($pipes[2]);

		$return_value = proc_close($p_resource);

		if (!empty($stdout))
		{
			echo 'stdout: <pre>' . $stdout . '</pre>' . "\n";
		}

		if ($return_value !== 0)
		{
			echo 'error: <pre>' . $return_value . '</pre>' . "\n";


			if (!empty($stderr))
			{
				echo 'stderr: <pre>' . $stderr . '</pre>' . "\n";
			}
		}
	}
}
