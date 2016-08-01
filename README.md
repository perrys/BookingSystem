# WSRC BookingSystem

The <a href=\"http://sourceforge.net/project/?group_id=5113\" target=\"_blank\">Meeting Room Booking System</a>
is open source software that is distributed under the Gnu Public License(GPL). MRBS was written by Daniel Gardner and John Beranek, and has been converted to a court booking system by <a href=\"http://www.jupiterwill.co.uk\" target=\"_blank\">Jupiterwill.</a></p>

Note, to downgrate to PHP5.6 on recent linux distributions (the code here uses deprecated APIs which have been removed in PHP7), do:

sudo add-apt-repository ppa:ondrej/php
sudo apt-get update
sudo apt-get install php5.6 php5.6-mysql libapache2-mod-php5.6

# to switch to 5.6:
sudo a2dismod php7.0 ; sudo a2enmod php5.6 ; sudo service apache2 restart

# to switch to 7.0:
sudo a2dismod php5.6 ; sudo a2enmod php7.0 ; sudo service apache2 restart
