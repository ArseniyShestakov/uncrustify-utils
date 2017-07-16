#!/usr/bin/env bash
# Uncrustify configuration helper. Ctrl+C to exit
# Open config in editor on one side and run this script on other side
#
# Warning! Script only copy input source once on startup.
# After the first uncrustify run it's will use same processed file file.
# Some options might have no effect with already formatted code.
#
# Requires following packages: inotify-tools, colordiff
# diff --color option only available in diffutils 3.4 (2016-08-08)
# for older version uncomment line with "colordiff" instead
# Path to uncrustify configuration
INPUT_CONFIG="/tmp/example.cfg"
# Path to source code file
INPUT_SRC="/tmp/example.cpp"

# Don't change unless needed
BIN_UNCRUSTIFY="uncrustify"
TMPFILE="/tmp/tmp.cpp"

cp $INPUT_SRC $TMPFILE
while true;
do
	# Watch for changes of configuration file
	inotifywait -e close_write $INPUT_CONFIG > /dev/null 2>&1
	clear

	# Check if there will be changes to code with updated configuration file
	$BIN_UNCRUSTIFY -q -c $INPUT_CONFIG --check $TMPFILE
	ERROR_CODE=$?
	if [ ${ERROR_CODE} != 0 ]; then
		# Pipe Uncrustify preview into diff stdin for visualization
		$BIN_UNCRUSTIFY -q -c $INPUT_CONFIG -f $TMPFILE | diff --color -u $TMPFILE --to-file=/dev/stdin
#		$BIN_UNCRUSTIFY -q -c $INPUT_CONFIG -f $TMPFILE | diff -u $TMPFILE --to-file=/dev/stdin | colordiff

		# Now actually update file
		$BIN_UNCRUSTIFY -q -c $INPUT_CONFIG --replace $TMPFILE
	fi
done;

# Arseniy Shestakov (arseniyshestakov.com)
