# Fast3
Controler for Trackmania Forever dedicated server

FAST3 is a controler for use with a Trackmania Forever dedicated server, allowing to add various fonctionalities.

It does not replace the dedicated server, but is connected to it and use its API to extend things. 

FAST3 is for TM FOREVER only !!! (United and Nations)




REPOSITORY
----------
github :  https://github.com/Slig06/Fast3

( Original repository :  http://slig.info/fast3.2/ )
 

FORUM : updates/bugs/ideas
-----

You can acces directly to forum there :

	http://dedimania.com/SITE/forum/viewforum.php?id=5
	

You must connect first into the site to be able to post in
the forum.

	http://dedimania.net/


INSTALLATION
------------

You must have php5 working.
The easiest is to extract fast in the dedicated directory,
so having fast.php in the same directory as the dedicated binary.

You will make or modify your start script yourself...


CONFIGURATION
-------------

The login/pass used for the dedicated is now also the one
used for the Dedimania database. The account is automatically
created on Dedimania after le login/pass has been controlled
on Nadeo masters servers.


You should use a command like that :

	php5 fast.php dedicated.cfg


NOTE: your server HAVE TO really use the ports indicated in its
used dedicated.cfg file, so those configured ports must not be used
by another program !!! I suggest to use for the dedicated the ports
2352,3452 and 5002, and make router-NAT redirections for them if
needed (2352 tcp and udp, 3452 tcp, and only if you want external
remote control 5002 tcp).

PS: You can get and use the tool TCPView (search on google) to see what
program use what port easily.


LOGS
----

Logs are now in the fastlog/ directory, their name contains
the game and tm login to differenciate logs of several fast
started from the same directory.


ADMIN
-----

Fast admins are now un an xml file similar to guestlist.txt etc.
It will be created automatically at first start, and if empty
the first player using an adm or help chat command will be promoted
as admin. When admins are added using /admin the new list is
automatically saved. You can edit the file by hand and restart
fast too. Admin file contains the game and tm login.


GENERALITIES
------------

There is no more problem to start several fast from the same
directory, like for the dedicated. You just have to setup
various dedxxxx.cfg files with different tm accounts and
different ports to launch several dedicated and associated fast.


USING REMOTE FAST
-----------------

If you use fast *remotely* *from* the *dedicated*, the best is to
get a copy of the dedicated.cfg file used by the dedicated, and to
specify in it the server IP address, in an added field <server_ip>


STATISTICS
----------

The statistics site is there :
    http://dedimania.net/tmstats/


CUSTOMISATIONS
--------------

If you want to make changes in texts you should do a copy of
locale.colors.txt, locale.en.txt or locale.fr.txt with the name
locale.custom.txt (in plugins/) and put there all modified or
translated strings, using the same file structure of existing
files.


So long, make good tests  :)
Gilles
