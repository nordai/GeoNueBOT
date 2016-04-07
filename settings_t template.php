<?php
//modulo delle KEYs per funzionamento dei bot (Template)

// Telegram
define('TELEGRAM_BOT','');
define('BOT_WEBHOOK', '');
define('LOG_FILE', 'telegram.log');

// DB BOT Telegram
define('DB_GEO_HOST', "127.0.0.1");
define('DB_GEO_PORT', "5432");
define('DB_GEO_NAME', "geonue_bot");
define('DB_GEO_USER', "");
define('DB_GEO_PASSWORD', "");

define('DB_TABLE_USER',"utenti");
define('DB_TABLE_GEO',"segnalazioni");
define('DB_TABLE_MAPS',"mappe");
define('DB_TABLE_STATE',"stato");
define('DB_ERR', "errore database POSTGIS");

// UMAP
define('UMAP_URL', 'umap.geonue.com');
define('UMAP_ZOOM', '19');

// DB UMAP
define('DB_UMAP_HOST', "127.0.0.1");
define('DB_UMAP_PORT', "5432");
define('DB_UMAP_NAME', "umap");
define('DB_UMAP_USER', "");
define('DB_UMAP_PASSWORD', "");

define('DB_TABLE_UMAP_MAP',"leaflet_storage_map");
define('DB_TABLE_UMAP_LAYER',"leaflet_storage_datalayer");

// GEOSERVER
define('MAPSERVER_URL', 'http://demo.geonue.com/geonueserver/');
define('MAPSERVER_WORKSPACE', 'bot');
define('MAPSERVER_NAMELAYER', 'bot:segnalazioni');

// WebService
define('WS_SHAPEFILE',"");
define('WS_GML',"");
define('WS_CSV',"");
define('WS_GEOJSON',"");
define('WS_KML',"");
define('WS_WMS',"");
define('WS_WFS',"");
define('WS_CSW',"");

?>
