<?php
include('settings_t.php');
// POST HANDLER -->
$telegramtk=TELEGRAM_BOT; // inserire il token

if(isset($_POST['bot_msg']) || isset($_GET['bot_msg'])) {
	$bot_msg = $_POST['bot_msg']?$_POST['bot_msg']:$_GET['bot_msg'];
	
    $db = getdb();
    
    $sql =  "SELECT s.iduser, s.bot_request_message, s.text_msg, s.file_id, s.file_type, s.file_path, s.lat, s.lng, s.state, s.map, s.data_time, u.first_name, u.last_name, u.username FROM ".DB_TABLE_GEO ." s JOIN ". DB_TABLE_USER ." u ON s.iduser = u.user_id WHERE s.bot_request_message='".$bot_msg."' AND s.state > 0";
	
	$ret = pg_query($db, $sql);
	   if(!$ret){
	      echo pg_last_error($db);
	      exit;
	   } 
	  
    $row = array();
    $i=0;
	
	while($res = pg_fetch_row($ret)){
    	if(!isset($res[0])) continue;
		$row[$i]['iduser'] = $res[0];
    	$row[$i]['bot_request_message'] = $res[1];
		$row[$i]['text_msg'] = $res[2];
		$row[$i]['file_id'] = $res[3];
		$row[$i]['file_type'] = $res[4];
		$row[$i]['file_path'] = $res[5];
		$row[$i]['lat'] = $res[6];
		$row[$i]['lng'] = $res[7];
		$row[$i]['state'] = $res[8];
		$row[$i]['map'] = $res[9];
    	$row[$i]['data_time'] = $res[10];
    	$row[$i]['first_name'] = $res[11];
    	$row[$i]['last_name'] = $res[12];
    	$row[$i]['username'] = $res[13];
		
		
    	$i++;
    }		
	
	$file_id = $row[0]['file_id'];

	$rawData = file_get_contents("https://api.telegram.org/bot".$telegramtk."/getFile?file_id=".$file_id);
	$obj=json_decode($rawData, true);
	$path=$obj["result"]["file_path"];
	$pathc="https://api.telegram.org/file/bot".$telegramtk."/".$path;
	
	/*$author = '';
	if ($row[0]['first_name'])
		$author .= $row[0]['first_name'].' ';
	if ($row[0]['last_name'])
		$author .= $row[0]['last_name'].' ';
	if ($row[0]['username'])
		$author .= '['.$row[0]['username'].']';*/
	
	if ($row[0]['iduser'] == "157129073")
		$author = "redazione";
	else if ($row[0]['username'])
		$author = $row[0]['username'];
	
	switch ($row[0]['file_type']) {
		case "video":
			$thumb="https://api.telegram.org/file/bot".$telegramtk."/".$row[0]['file_path'];
			$tagfile = '<video preload="(none,auto,metadata)" poster="'.$thumb.'" controls="controls"><source src="'.$pathc.'" type="video/mp4" /></video>';
		break;
		case "photo":
			$tagfile = '<img src="'.$pathc.'" id="x-aspect-1" />';
		break;
		case "document":
			$tagfile= '<a href="'.$pathc.'" target="_blank">'.$row[0]['text_msg'].'</a>';
		break;
		case "voice":
			$thumb="https://api.telegram.org/file/bot".$telegramtk."/".$row[0]['file_path'];
			$tagfile = '<audio controls><source src="'.$pathc.'" type="audio/ogg"></audio>';
		  
		break;		
	}
	
	if (check_approved()) {
		
		
	$tagstate = "";
	
		switch ($row[0]['state']) {
			case 0:
				$tagstate = '<span style="color: gray;">In inserimento</span>';
			break;
			case 1:
				$tagstate = '<span style="color: orange;">Registrata</span>';
			break;
			case 2:
				$tagstate = '<span style="color: green;">Approvata</span>';
			break;
			case 3:
				$tagstate = '<span style="color: red;">Respinta</span>';
			break;
			case 4:
				$tagstate = '<span style="color: blue;">Sospesa</span>';
			break;
			case 5:
				$tagstate = '<span style="color: black;">Cancellata</span>';
			  
			break;		
		}
	
	}
	
}
//connessione al DB
function getdb() {
	// Instances the class		
	$host        = "host=".DB_GEO_HOST;
	$port        = "port=".DB_GEO_PORT;
	$dbname      = "dbname=". DB_GEO_NAME;
	$credentials = "user=".DB_GEO_USER." password=".DB_GEO_PASSWORD;
		
	$db = pg_connect("$host $port $dbname $credentials");
    return $db;
}

//check per verificare se è attiva nella mappa corrente la procedura di approvazione
function check_approved(){
			
			$db = getdb();
		    $sql =  "SELECT * FROM ". DB_TABLE_MAPS ." WHERE approve = true and enabled = true ";
			$ret = pg_query($db, $sql);
			
		   if(!$ret){
		      echo pg_last_error($db);
		      return false;
		   }
		   
		   if (pg_num_rows($ret))
		   	return true;
		   else
		    return false;
	
		}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
                      "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns='http://www.w3.org/1999/xhtml' xml:lang='en'>
    <head>
        <title>File <?php print $file_id ?></title>
<style type='text/css'>
body {
	text-align: left;
    margin: 0px;
    padding: 0px;
    font-family:   "Trebuchet MS", "Verdana", Arial, sans-serif, Helvetica;
}
h1.title {
    font-size: 1.3em;
}
p.author {
    font-size: 0.8em;
}
p.text_msg {
    font-size: 1.1em;
}
p.data_time {
    font-size: 0.7em;
}
img {
    border: 0px solid black;
    padding: 0px;
}
img#x-aspect-1 {
    width: 100%;
    height: auto;
}
video {
    width: 100%;
}
audio {
    width: 100%;
}
</style>


    </head>
    <body>
        <div>
            <?php
            
            $url = '~(?:(https?)://([^\s<]+)|(www\.[^\s<]+?\.[^\s<]+))(?<![\.,:])~i'; 
			$string = preg_replace($url, '<a href="$0" target="_blank" title="$0">$0</a>', $row[0]['text_msg']);

			if ($i) {
			print '<h1 class="title">Segnalazione n.'.$row[0]['bot_request_message'].'</h1>';
			print '<p class="author">di '.($author?$author:'anonimo').'</p>';
			if($row[0]['file_type'] != "document") print '<p class="text_msg">'.$string.'</p>';
			print '<div class="tagfile">'.$tagfile.'</div>';
			print '<p class="data_time">data: '.$row[0]['data_time'].'</p>';
			print '<p class="state">'.$tagstate.'</p>';
			}
			?>
        </div>
    </body>
</html>