#!/bin/bash
#
# Background starter for Fast (linux)
#
# You must have php5-cli package installed (ie php5 binary !)
# In some case the binary can be php and not php5
#
# simply start it like that :   ./fast3.sh dedfile
#                                          (no endind .cfg !!!!)
# where dedfile.cfg is the dedicated.cfg file used by your dedicated.
#
dedcfg=$1
shift
rm -f $dedcfg.log
#old version: php5 fast.php $dedcfg.cfg $* </dev/null >$dedcfg.log 2>&1 &

# Auto update
# If the script is run with 'update_stop10' as 2nd argument then
# after autoupdate the script will stop with a errorlevel 10 to
# indicate it should be restarted.
(until (php5 fast.php $dedcfg.cfg update_stop10 $*) ; do
  if [ $? -ne 10 ] ; then exit ; fi
  echo "Restart fast..."
done) </dev/null >$dedcfg.log 2>&1 &

