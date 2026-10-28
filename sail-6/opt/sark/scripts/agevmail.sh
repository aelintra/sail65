#!/bin/bash
#
# Delete opened voicemail older than globals['VMAILAGE'] days
#
ZERO=0
VMAILAGE=`/usr/bin/sqlite3 /opt/sark/db/sark.db "SELECT VMAILAGE FROM globals WHERE pkey='global'"`
#echo deleting vmail older than $VMAILAGE days
if [ ! -z "$VMAILAGE" ]; then
	if [ "$VMAILAGE" -gt $ZERO ]; then
		logger "srkagevmail - Deleting vmail older than $VMAILAGE days"
		# Path-based find: avoids literal '*/Old' when no Old dirs exist yet
		# (unquoted glob left find looking for a path that contains '*').
		VMROOT=/var/spool/asterisk/voicemail/default
		if [ -d "$VMROOT" ]; then
			find "$VMROOT" -type f -path '*/Old/msg*' -mtime +"$VMAILAGE" -delete
		fi
	else
		logger "srkagevmail - no vmail ageing (variable is zero)"
	fi
else
	logger "srkagevmail - no vmail ageing (variable is null)"
fi