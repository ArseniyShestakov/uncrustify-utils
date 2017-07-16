#!/usr/bin/env php
<?php
// Removing Uncrustify options that don't affect input file formatting

//Your Uncrustify configuration file
define('INPUT_CONFIG', '/tmp/example.cfg');
//Your file with source code
define('INPUT_SOURCE', '/tmp/example.cpp');
define('TMP_DIR', '/tmp/');
define('SHOW_DIFF', FALSE);
define('BIN_UNCRUSTIFY', 'uncrustify');
define('BIN_DIFF', 'diff');

//Don't change these
define('CONFIG_DEFAULT', TMP_DIR.'/config_default.cfg');
define('CONFIG_PARSED', TMP_DIR.'/config_parsed.cfg');
define('CONFIG_TMP', TMP_DIR.'/config_tmp.cfg');
define('SOURCE_TMP', TMP_DIR.'/tmp.cpp');
define('SOURCE_OLD', SOURCE_TMP.'.origin');

//Get default config file from uncrustify
$ini_default = parseConfig();
//Parse config file with uncrustify and then into array
$ini = parseConfig(INPUT_CONFIG);
//Remove all default options
foreach($ini as $name => $value)
{
	if($value === $ini_default[$name])
		unset($ini[$name]);
}

//Run Uncrustify with non-default options and save output for diff
copy(INPUT_SOURCE, SOURCE_TMP);
runUncrustify($ini);
copy(SOURCE_TMP, SOURCE_OLD);

//Now try to remove each option and see if there change in output
foreach($ini as $name => $value)
{
	echo $name;
	
	copy(INPUT_SOURCE, SOURCE_TMP);
	$ini_tmp = $ini;
	unset($ini_tmp[$name]);
	runUncrustify($ini_tmp);
	
	$diff = shell_exec(BIN_DIFF.' '.SOURCE_OLD.' '.SOURCE_TMP);
	if(!strlen($diff))
	{
		echo ' - diff is empty!'.PHP_EOL;
		unset($ini[$name]);
	}
	else
	{
		echo ' - changes found!'.PHP_EOL;
		if(SHOW_DIFF)
			echo $diff;
	}
}
echo PHP_EOL.PHP_EOL;
echo 'Non-default settings that formatting ('.sizeof($ini).'):'. PHP_EOL;
echo write_ini_file($ini);
echo PHP_EOL.PHP_EOL;

function runUncrustify($array)
{
	write_ini_file($array, CONFIG_TMP);
	shell_exec(BIN_UNCRUSTIFY.' --replace -c '.CONFIG_TMP.' '.SOURCE_TMP.' > /dev/null 2>&1');
}

function parseConfig($path = FALSE)
{
	if(FALSE === $path)
	{
		shell_exec(BIN_UNCRUSTIFY.' --update-config > '.CONFIG_DEFAULT);
		$path = CONFIG_DEFAULT;
	}
	else
	{
		shell_exec(BIN_UNCRUSTIFY.' -c '.$path.' --update-config > '.CONFIG_PARSED);
		$path = CONFIG_PARSED;
	}
	cleanConfig($path);
	return parse_ini_file($path, false, INI_SCANNER_TYPED);
}

function cleanConfig($path)
{
	$contents = file($path);
	foreach($contents as $i => $line)
	{
		if(substr($line, 0, 1) === '#')
			unset($contents[$i]);
	}
	file_put_contents($path, $contents);
}

function write_ini_file($array, $filename = FALSE)
{
    $res = array();
    foreach($array as $key => $val)
    {
		switch(gettype($val))
		{
		case 'boolean':
			$val = $val ? 'true' : 'false';
			break;
		case 'integer':
			break;
		case 'string':
			if(!in_array($val, array('ignore', 'add', 'remove', 'force', 'ignore', 'join', 'lead', 'lead_break', 'lead_force', 'trail', 'trail_break', 'trail_force')))
			{
				$val = '"'.$val.'"';
			}
			break;
		default:
			exit("Wrong type!");
			break;
		}
		$res[] = $key.' = '.$val;
    }

    if(FALSE === $filename)
		return implode(PHP_EOL, $res);
	else
	    file_put_contents($filename, implode(PHP_EOL, $res));
}

// Arseniy Shestakov (arseniyshestakov.com)
