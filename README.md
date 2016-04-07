You must have PHP, POSTGIS DB, GEOSERVER, UMAP configured and a Telegram Bot.
 	- DB POSTGIS (http://postgis.net/)
 	- GeoServer (http://geoserver.org/)
 	- uMap (https://umap.openstreetmap.fr)

In localhost is possible to launch
php start.php 'sethook' to set start.php as webhook
php start.php 'removehook' to remove start.php as webhook
php start.php 'getupdates' to run getupdates.php

After setup webhook is possible to use telegram managed by webhost

To use the system
- Make a Telegram Bot
- Send Location to it
- Reply to bot with a text description or image, video, audio, document
- All data are sent in the database 
- Data can mapped now

Rename settings_t_template.php in settings_t.php and put inside

- TOKEN of telegram Bot
- Link to webhook if you want use webhook
- Create DB and table with table_postgis.sql
- Username e Password of DB BOT
- Username e Password of DB UMAP
- URL and layer name GEOSERVER


To use the application use "start.php getupdates" for manual execution. "start.php sethook" for Telegram webhook execution.

A simple example is implemented here http://www.geonue.com/tour/geonue-bot/

Good Luck!
