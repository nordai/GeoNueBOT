<?php
/**
 * Telegram Bot for mapping by Nordai s.r.l.
 * @author originale motore Telegram: Gabriele Grillo <gabry.grillo@alice.it> con riadattamento da parte di Matteo Tempestini e Piersoft 
 
 	Componenti base BOT
 	- DB POSTGIS (http://postgis.net/)
 	- GeoServer (http://geoserver.org/)
 	- uMap (https://umap.openstreetmap.fr)
	
	Funzionamento base
	- scelta mappa
	- invio location
	- invio segnalazione come risposta
	
	Funzionalità avanzate (manager)
	- profilo utente
	- cancella segnalazione
	- web service geografici BOT
	- attiva/disattiva notifiche
	- crea mappa personale
	- crea nuova mappa (solo per profili avanzati)
	- gestione segnalazioni (approva, respingi, sospendi e cancella)
	- attivazione/disattivazione procedura di approvazione segnalazioni su mappa
	- lista segnalazioni da approvare
	- lista segnalazioni in sospeso
	- abilita/disabilita mappa
	- rendi la mappa privata/pubblica
	
	
	Funzionalità avanzate (amministratore)
	- lista di tutte le mappe (private e pubbliche)
	- imposta mappa di default
	
 */
 
include("Telegram.php");

class mainloop{
 
 function start($telegram,$update)
	{

		date_default_timezone_set('Europe/Rome');
		$today = date("Y-m-d H:i:s");
		
		/* If you need to manually take some parameters
		*  $result = $telegram->getData();
		*  $text = $result["message"] ["text"];
		*  $chat_id = $result["message"] ["chat"]["id"];
		*/
		
		$text = $update["message"] ["text"];
		$chat_id = $update["message"] ["chat"]["id"];
		$user_id=$update["message"]["from"]["id"];
		$location=$update["message"]["location"];
		$reply_to_msg=$update["message"]["reply_to_message"];
		
		$this->shell($telegram,$text,$chat_id,$user_id,$location,$reply_to_msg);

	}

	//gestisce l'interfaccia utente
	function shell($telegram,$text,$chat_id,$user_id,$location,$reply_to_msg)
	{
		date_default_timezone_set('Europe/Rome');
		$today = date("Y-m-d H:i:s");
		
		$db = $this->getdb();
		
		//CHECK umap utente
		$id_map = $this->check_setmap($telegram,$chat_id);
		
		$sql =  "SELECT id_map, umap_id, name_map FROM ".DB_TABLE_MAPS ." WHERE id_map=".$id_map;
				
		$ret = pg_query($db, $sql);
		   if(!$ret){
		      echo pg_last_error($db);
		      exit;
		   } 
				  
		$row = array();
		
		while($res = pg_fetch_row($ret)){
		  	if(!isset($res[0])) continue;
		  		$umap_id = $res[1];
		   		$name_map = $res[2];
		}
		
		$shortUrl= UMAP_URL ."/m/". $umap_id;
          		

			if ($text == "/start") {
				$log=$today. ";new chat started;" .$chat_id. "\n";
				$reply = "Benvenuto. Per inviare una segnalazione, clicca [Invia posizione] dall'icona a forma di graffetta e aspetta una decina di secondi. Quando ricevi la risposta automatica, puoi scrivere un testo descrittivo o allegare un contenuto video foto audio ect.
				
Con GeoNueBOT puoi usare più mappe, per vedere la lista di quelle disponibili digita /maplist e attiva quella che preferisci.

Mappa in uso: ".$id_map.". ".$name_map."
Per vedere le segnalazioni in mappa: ".$shortUrl."
Per cambiare mappa: /setmap
								
In qualsiasi momento scrivendo /start ti ripeterò questo messaggio di benvenuto. Per le funzionalità avanzate scrivi /help.

Decliniamo ogni responsabilità dall'uso improprio di questo strumento e dei contenuti degli utenti. 
				
Tutte le info sono sui server Telegram, mentre in un database locale c'è traccia dei links degli allegati da te inviati";
				$content = array('chat_id' => $chat_id, 'text' => $reply);
				$telegram->sendMessage($content);
			}
			//gestione segnalazioni georiferite
			elseif($location!=null)
			{
				// in modalità manutenzione decommentare sendMessage e commentare location_manager.
				$reply = "GeoNueBOT work in progress. Stay tuned! :)";
				$content = array('chat_id' => $chat_id, 'text' => $reply);
				//$telegram->sendMessage($content);

				$this->location_manager($telegram,$user_id,$chat_id,$location);
				exit;	
			}
			// inserimento contenuto segnalazione
			elseif($reply_to_msg["text"] == "[Cosa vuoi comunicare qui?]")
			{
			    $response=$telegram->getData();
				
			    $text =$response["message"]["text"];
			    $risposta="";
			    
			    $file_name="";
			    $file_path="";
			    $file_name="";
				$file_type="";
				
				$file_id = null;
			    
			    $type=$response["message"]["video"]["file_id"];
			    if ($type != NULL) {
				    $file_id=$type;
					$file_type = "video";
					$file_path=$response["message"]["video"]["thumb"]["file_path"];
				    $caption=$response["message"]["caption"];
					if ($caption != NULL) $text=$caption;
				    $risposta="ID dell'allegato:".$type;
			    }
			    
				$numbphoto = count($response["message"]["photo"]);
			    $typep=$response["message"]["photo"][$numbphoto-1]["file_id"];
			    if ($typep !=NULL) {
					$file_id=$typep;
					$file_type = "photo";
				    $telegramtk=TELEGRAM_BOT; // inserire il token
				    $rawData = file_get_contents("https://api.telegram.org/bot".$telegramtk."/getFile?file_id=".$file_id);
				    $obj=json_decode($rawData, true);
				    $file_path=$obj["result"]["file_path"];
				    $caption=$response["message"]["caption"];
			    	if ($caption != NULL) $text=$caption;
			    	$risposta="ID dell'allegato: ".$typep;
			    }
			    
			    $typed=$response["message"]["document"]["file_id"];
			    if ($typed !=NULL){
			    	$file_id=$typed;
					$file_type = "document";
				    $file_name=$response["message"]["document"]["file_name"];
				    $text= $file_name;
				    $risposta="ID dell'allegato:".$typed;
			    }
			    
			    $typev=$response["message"]["voice"]["file_id"];
			    if ($typev !=NULL){
			    	$file_id=$typev;
					$file_type = "voice";
			    	$text="ascolta audio";
			    	$risposta="ID dell'allegato:".$typev;
			    }
			    
			    $sql =  "SELECT lat,lng,map,state, umap_id, name_map FROM ".DB_TABLE_GEO ." s JOIN ". DB_TABLE_MAPS ." m ON s.map = m.id_map WHERE bot_request_message='".$reply_to_msg['message_id']."'";
				
				$ret = pg_query($db, $sql);
				   if(!$ret){
				      echo pg_last_error($db);
				      exit;
				   } 
				  
			    $row = array();
			    $i=0;
			    
			    while($res = pg_fetch_row($ret)){
			    	if(!isset($res[0])) continue;
			    		$row[$i]['lat'] = $res[0];
			    		$row[$i]['lng'] = $res[1];
			    		$row[$i]['map'] = $res[2];
			    		$row[$i]['state'] = $res[3];
			    		$row[$i]['umap_id'] = $res[4];
			    		$row[$i]['name_map'] = $res[5]; 
			    		$i++;
			    }	
			    
			    if ($row[0]['state'] > 1) {
			    	$reply = "Non puoi più modificare questa segnalazione [".$reply_to_msg['message_id']."].";
			    	$content = array('chat_id' => $chat_id, 'text' => $reply);
					$telegram->sendMessage($content);
					exit;
			    }
			    
			    $state = $this->check_approved()?1:2;
			    
			    $sql = "UPDATE ".DB_TABLE_GEO ." SET text_msg='".str_replace("'"," ",$text)."',file_id='". $file_id ."',file_type='". $file_type ."',file_path='". $file_path ."', state = ".$state." WHERE bot_request_message ='".$reply_to_msg['message_id']."'";
			    
			    file_put_contents(LOG_FILE, $sql, FILE_APPEND | LOCK_EX);
			    
				$ret = pg_query($db, $sql);
				   
				if(!$ret){
				   echo pg_last_error($db);
				   $reply = pg_last_error($db);
				   exit;
				} else {
					$reply = "Segnalazione [".$reply_to_msg['message_id']."] registrata. Grazie!";
					
					$umap = $this->get_umap($row[0]['umap_id']);
					$shortUrl = UMAP_URL ."/it/map/".$umap[2]."_".$umap[0]."#".UMAP_ZOOM ."/".$row[0]['lat']."/".$row[0]['lng'];
				//	$shortUrl= UMAP_URL ."/m/". $row[0]['umap_id']."#". UMAP_ZOOM ."/".$row[0]['lat']."/".$row[0]['lng'];
          			$reply .="\nPuoi visualizzarla su :\n".$shortUrl;
				}
				   
			    $content = array('chat_id' => $chat_id, 'text' => $reply);
				$telegram->sendMessage($content);
				$log=$today. ";information for maps recorded;" .$chat_id. "\n";	
				file_put_contents(LOG_FILE, $log, FILE_APPEND | LOCK_EX);
				
				
				if ($this->check_approved()) { 
				
					//avviso il manager della mappa per l'approvazione della nuova segnalazione
				   // $sql = "SELECT m.author FROM ".DB_TABLE_USER ." u JOIN ". DB_TABLE_MAPS ." m ON u.user_id = m.author and m.approve = true and (u.type_role = 'manager' or u.type_role = 'admin') and m.id_map = ".$row[0]['map']." GROUP BY m.author";
				   // avviso il proprietario della mappa
				    $sql = "SELECT m.author FROM ".DB_TABLE_USER ." u JOIN ". DB_TABLE_MAPS ." m ON u.user_id = m.author and m.approve = true and m.id_map = ".$row[0]['map']." GROUP BY m.author";
				    
				    file_put_contents(LOG_FILE, $sql, FILE_APPEND | LOCK_EX);
				    
				   $ret = pg_query($db, $sql);
					   if(!$ret){
					      echo pg_last_error($db);
					      exit;
					   } 
					  
					$user = $this->get_user($chat_id);
					  
				   	$bot_request_message_id=$response["message"]["message_id"];
					$reply = "rif. ".$row[0]['map'].". ".$row[0]['name_map']." [".$reply_to_msg['message_id']."] / ".$user[5]." [".$user[7]."] - APPROVI? Y/N";
					$reply .="\nPuoi visualizzarla su :\n".$shortUrl;
		            $forcehide=$telegram->buildForceReply(true);
				    while($res = pg_fetch_row($ret)){
				    	$content = array('chat_id' => $res[0], 'reply_markup' => $forcehide, 'text' => $reply);
						$telegram->sendMessage($content);
				    }	
				    
				}
					
			}	
			// approvazione segnalazione
			elseif(strpos($reply_to_msg["text"],"APPROVI? Y/N") != 0 
				//&& ($this->check_admin($user_id) || $this->check_manager($user_id)) 
				&& $this->check_approved())
			{
				$response=$telegram->getData();
				$text =$response["message"]["text"];
				
				$id_bot_msg = $this->get_string_between($reply_to_msg["text"], "[", "]");
			    
				$this->mod_state($telegram,$chat_id,$text,$id_bot_msg);
			    
					
			}	
			// modifica stato segnalazioni
			elseif ((substr($text, 0, 2) == "/A" || substr($text, 0, 2) == "/R" || substr($text, 0, 2) == "/C" || substr($text, 0, 2) == "/S")
				&& is_numeric(substr($text, 2)) && is_int(intval(substr($text, 2))) ) {
					
				
				$this->mod_state($telegram,$chat_id,substr($text, 0, 2),substr($text, 2));
				
			
			}
			// lista con segnalazioni da approvare su mappa attiva...
			elseif ($text == "/Alist" && ($this->check_admin($user_id) || $this->check_manager($user_id) || $this->check_user_map($user_id,$id_map) )) {
					
				$sql =  "SELECT bot_request_message, lat, lng FROM ". DB_TABLE_GEO ." WHERE state=1 AND map=".$id_map;
				
				$ret = pg_query($db, $sql);
				   if(!$ret){
				      echo pg_last_error($db);
				      exit;
				   } 
			    
			    if (pg_num_rows($ret)) {
			   		$row = array();
			    	$reply = "Segnalazioni da approvare\n\n";
			    	while($res = pg_fetch_row($ret)){		   
			    		$urlmap = $shortUrl ."#". UMAP_ZOOM ."/".$res[1]."/".$res[2]; 	
			    		$reply .= "/A".$res[0]." | /R".$res[0]." | /S".$res[0]."\n[".$urlmap."]\n\n"; 
			    	}	
			    }
			    else {
			    	$reply = "Non ci sono segnalazioni da approvare\n\n";
			    }
			    
			    $content = array('chat_id' => $chat_id, 'text' => $reply);
				$telegram->sendMessage($content);
								
			
			}
			// lista con segnalazioni da approvare su mappa
			elseif (((substr($text, 0, 6) == "/Alist") 
				&& is_numeric(substr($text, 6)) && is_int(intval(substr($text, 6))))
				&& ($this->check_admin($user_id) || $this->check_manager($user_id,substr($text, 6)) || $this->check_user_map($user_id,substr($text, 6)) )) {
					
					
				if ($this->check_map(substr($text, 6), false)) {
					
					$sql =  "SELECT bot_request_message, lat, lng FROM ". DB_TABLE_GEO ." WHERE state=1 AND map=".substr($text, 6);
					
					$ret = pg_query($db, $sql);
					   if(!$ret){
					      echo pg_last_error($db);
					      exit;
					   } 
				    
				    if (pg_num_rows($ret)) {
				   		$row = array();
				    	$reply = "Segnalazioni da approvare\n\n";
				    	while($res = pg_fetch_row($ret)){		   
				    		$urlmap = $shortUrl ."#". UMAP_ZOOM ."/".$res[1]."/".$res[2]; 	
				    		$reply .= "/A".$res[0]." | /R".$res[0]." | /S".$res[0]."\n[".$urlmap."]\n\n"; 
				    	}	
				    }
				    else {
				    	$reply = "Non ci sono segnalazioni da approvare\n\n";
				    }
				}
				else 
					$reply = "La mappa non esiste\n\n";
			    
			    $content = array('chat_id' => $chat_id, 'text' => $reply);
				$telegram->sendMessage($content);
								
			
			}
			// lista con segnalazioni in sospeso su mappa attiva...
			elseif ($text == "/Slist" && ($this->check_admin($user_id) || $this->check_manager($user_id) || $this->check_user_map($user_id,$id_map) )) {
					
				$sql =  "SELECT bot_request_message, lat, lng FROM ". DB_TABLE_GEO ." WHERE state=4 AND map=".$id_map;
				
				$ret = pg_query($db, $sql);
				   if(!$ret){
				      echo pg_last_error($db);
				      exit;
				   } 
				  
				if (pg_num_rows($ret)) {
				    $row = array();
				    $reply = "Segnalazioni in sospeso\n\n";
				    while($res = pg_fetch_row($ret)){		
				    	$urlmap = $shortUrl ."#". UMAP_ZOOM ."/".$res[1]."/".$res[2];    	
				    	$reply .= "/A".$res[0]." | /R".$res[0]." | /C".$res[0]."\n[".$urlmap."]\n\n"; 
				    }	
				}
				else {
					$reply = "Non ci sono segnalazioni in sospeso\n\n";
				}
			    
			    $content = array('chat_id' => $chat_id, 'text' => $reply);
				$telegram->sendMessage($content);
								
			
			}
			// lista con segnalazioni in sospeso su mappa
			elseif (((substr($text, 0, 6) == "/Slist") 
				&& is_numeric(substr($text, 6)) && is_int(intval(substr($text, 6))))
				&& ($this->check_admin($user_id) || $this->check_manager($user_id,substr($text, 6)) || $this->check_user_map($user_id,substr($text, 6)) )) {
					
					
				if ($this->check_map(substr($text, 6), false)) {
					
					$sql =  "SELECT bot_request_message, lat, lng FROM ". DB_TABLE_GEO ." WHERE state=4 AND map=".substr($text, 6);
					
					$ret = pg_query($db, $sql);
					   if(!$ret){
					      echo pg_last_error($db);
					      exit;
					   } 
				    
				    if (pg_num_rows($ret)) {
				   		$row = array();
				    	$reply = "Segnalazioni in sospeso\n\n";
				    	while($res = pg_fetch_row($ret)){		   
				    		$urlmap = $shortUrl ."#". UMAP_ZOOM ."/".$res[1]."/".$res[2]; 	
				    		$reply .= "/A".$res[0]." | /R".$res[0]." | /C".$res[0]."\n[".$urlmap."]\n\n"; 
				    	}	
				    }
				    else {
				    	$reply = "Non ci sono segnalazioni in sospeso\n\n";
				    }
				}
				else 
					$reply = "La mappa non esiste\n\n";
			    
			    $content = array('chat_id' => $chat_id, 'text' => $reply);
				$telegram->sendMessage($content);
								
			
			}
			// lista mappe
			elseif ($text == "/maplist") {
					
				$sql =  "SELECT * FROM ". DB_TABLE_MAPS ." WHERE enabled=true AND private=false ORDER BY id_map";
				
				$ret = pg_query($db, $sql);
				   if(!$ret){
				      echo pg_last_error($db);
				      exit;
				   } 
				  
			    $row = array();
			    
			    $reply = "Mappe disponibili\n\n";
			    while($res = pg_fetch_row($ret)){
			    	$shortUrl= UMAP_URL ."/m/". $res[4];
			    	if ($res[5] == "t")
			    		$def = " (default)";
			        else
			        	$def = "";
			    	$reply .= $res[0].". ".$res[1]."".$def." - /infomap".$res[0]."\n";
			    //	$reply .= "[".$shortUrl."]\n\n"; 
			    }	
			    
			    $content = array('chat_id' => $chat_id, 'text' => $reply);
				$telegram->sendMessage($content);
			   						
			
			}
			// lista mappe sistema (admin e manager)
			elseif ($text == "/listallmap" && ($this->check_admin($user_id) || $this->check_manager($user_id))) {
					
				$sql =  "SELECT * FROM ". DB_TABLE_MAPS ." ORDER BY id_map";
				
				$ret = pg_query($db, $sql);
				   if(!$ret){
				      echo pg_last_error($db);
				      exit;
				   } 
				  
			    $row = array();
			    
			    $reply = "Mappe del sistema\n\n";
			    while($res = pg_fetch_row($ret)){
			    	$shortUrl= UMAP_URL ."/m/". $res[4];
			    	if ($res[5] == "t")
			    		$def = " (default)";
			    	else if ($res[3] == "f")
			    		$def = " (non attiva)";
			        else
			        	$def = "";
			        
			        if ($res[6] == "t")
			    		$def .= " [P]";

			    	$reply .= $res[0].". ".$res[1]."".$def." - /infomap".$res[0]."\n";
			    //	$reply .= "[".$shortUrl."]\n\n"; 
			    }	
			    
			    $content = array('chat_id' => $chat_id, 'text' => $reply);
				$telegram->sendMessage($content);
			   						
			
			}
			// info mappa
			elseif ((substr($text, 0, 8) == "/infomap") 
				&& is_numeric(substr($text, 8)) && is_int(intval(substr($text, 8)))) {
				
				$response=$telegram->getData();
				
				$username=$response["message"]["from"]["username"];
			    $first_name=$response["message"]["from"]["first_name"];
			    $last_name=$response["message"]["from"]["last_name"];
			    
			    $id_map = substr($text, 8);
			    
			    if ($this->check_map($id_map, false)) {
				
					$sql =  "SELECT * FROM ". DB_TABLE_MAPS ." WHERE id_map = ". $id_map;
				  	$ret = pg_query($db, $sql);
					   
					if(!$ret){
					   echo pg_last_error($db);
					   $reply = pg_last_error($db);
					   exit;
					} else {
						
						$row = array();
						$i = 0;
						while($res = pg_fetch_row($ret)){
					    	if(!isset($res[0])) continue;
					    		$row[$i]['name_map'] = $res[1];
					    		$row[$i]['approve'] = $res[2];
					    		$row[$i]['enabled'] = $res[3];
					    		$row[$i]['umap_id'] = $res[4];
					    		$row[$i]['def'] = $res[5];
					    		$row[$i]['private'] = $res[6];
					    		$row[$i]['author'] = $res[7];
					    		$row[$i]['password'] = $res[8];
					    		$i++;
					    }	
					    
					    if ($row[0]['enabled'] == 't' || $this->check_admin($user_id) || $this->check_manager($user_id,$id_map)) {
							
							$shortUrl= UMAP_URL ."/m/". $row[0]['umap_id'];
							
							$reply = "MAPPA n.".$id_map." ".$row[0]['name_map']."\n";
							$reply .= "[".$shortUrl."]\n\n";
							
							$reply .= "Per usarla: /map_".$row[0]['name_map']."\n\n";
							
							if ($row[0]['approve'] == 't') {
								$reply .= "APPROVAZIONE SEGNALAZIONI ABILITATA\n";
								if ($this->check_admin($user_id) || $this->check_manager($user_id,$id_map))
									$reply .= "per disabilitarla: /offapproved".$id_map."\n";
							}
							else {
								$reply .= "APPROVAZIONE SEGNALAZIONI NON ABILITATA\n";
								if ($this->check_admin($user_id) || $this->check_manager($user_id,$id_map))
									$reply .= "per abilitarla: /onapproved".$id_map."\n";
							}
							if ($row[0]['private'] == 't') {
								$reply .= "\nMAPPA PRIVATA\n";
								if ($this->check_admin($user_id) || $this->check_manager($user_id,$id_map))
									$reply .= "per renderla pubblica: /publicmap".$id_map."\n";
							}
							else {
								$reply .= "\nMAPPA PUBBLICA\n";
								if ($this->check_admin($user_id) || $this->check_manager($user_id,$id_map))
									$reply .= "per renderla privata: /privatemap".$id_map."\n";
							}
							if ($this->check_admin($user_id) || $this->check_manager($user_id,$id_map)) {
								if ($row[0]['enabled'] == 't') {
									$reply .= "\nMAPPA ATTIVA\n";
									$reply .= "per disabilitarla: /disabledmap".$id_map."\n";
								}
								else {
									$reply .= "\nMAPPA NON ATTIVA\n";
									$reply .= "per attivarla: /enablemap".$id_map."\n";
								}
							}
							
							if ($this->check_admin($user_id))
								$reply .= "\nper impostarla come default: /defaultmap".$id_map."\n\n";
							
						
							//check stati segnalazioni mappa
							$sql =  "SELECT se.state, st.stato, count(se.state) FROM ".DB_TABLE_GEO." se JOIN ".DB_TABLE_STATE." st ON se.state = st.id WHERE map = ".$id_map." GROUP BY se.state, st.stato ORDER BY se.state";
							
							$ret = pg_query($db, $sql);
							   if(!$ret){
							      echo pg_last_error($db);
							      exit;
							   } 
							   
							if (pg_num_rows($ret)) {
								$reply .= "\nRIEPILOGO SEGNALAZIONI:\n";
								$row = array();
								
							    while($res = pg_fetch_row($ret)){
							    	$reply .= $res[1].": ".$res[2]."\n";
						    	}	
							}
							else {
								$reply .= "\nNon ci sono ancora SEGNALAZIONI su questa mappa\n";
							}
							
						   
					    }
					    else {
					    	$reply = "Mappa non attiva.";
					    }
						
						
					}
				}
				else
					$reply = "Mappa non presente nel sistema.";
					    
				$content = array('chat_id' => $chat_id, 'text' => $reply,'disable_web_page_preview'=>true);
				$telegram->sendMessage($content);
				$log=$today. ",me sent," .$sql. "\n";
					 
			}
			// fa scegliere la mappa da utilizzare
			elseif ($text == "/setmap") {
				
				$sql =  "SELECT name_map FROM ". DB_TABLE_MAPS ." WHERE enabled=true AND private=false ORDER BY name_map";
				
				$ret = pg_query($db, $sql);
				   if(!$ret){
				      echo pg_last_error($db);
				      exit;
				   } 
				
				$row = array();  
			    $option = array();
			    $i=1;
			    while($res = pg_fetch_row($ret)){
			    	if (($i % 3) == 0) {
			    		array_push($option, $row);
			    		$row = array();
			    		$i++;
			    	}
			    	array_push($row, "/map_". $res[0]);
			    	$i++;
			    }	
			    if (count($row))
			    	array_push($option, $row);
			    
			    $keyb = $telegram->buildKeyBoard($option,$onetime=true);
          		$content = array('chat_id' => $chat_id, 'reply_markup' => $keyb, 'text' => "[Seleziona la mappa]");
          		$telegram->sendMessage($content);
		
			}
			// imposta la mappa attiva dal nome
			elseif (substr($text, 0, 5) == "/map_") {
				
				$name_map = substr($text, 5);
				
				$map_id = $this->check_name_map($name_map, true);
				
				if ($map_id) {
				
					$sql = "UPDATE ". DB_TABLE_USER ." SET map = ".$map_id." WHERE user_id = '".$chat_id."'";
				  	$ret = pg_query($db, $sql);
					   
					if(!$ret){
					   echo pg_last_error($db);
					   $reply = pg_last_error($db);
					   exit;
					} else {	
						$reply = "Mappa [".$name_map."] impostata.";
					}
				}
				else
					$reply = "Mappa non presente nel sistema o non attiva.";
				   
				$reply_markup = $telegram->buildKeyBoardHide();
				$content = array('chat_id' => $chat_id, 'text' => $reply, 'reply_markup' => $reply_markup);
				$telegram->sendMessage($content);
				$log=$today. ";information for activate map;" .$map_id. "\n";	
				file_put_contents(LOG_FILE, $log, FILE_APPEND | LOCK_EX);	
											
			
			} 
			// imposta la mappa attiva da id
			elseif ((substr($text, 0, 7) == "/setmap") 
				&& is_numeric(substr($text, 7)) && is_int(intval(substr($text, 7)))) {
				
				$map_id = substr($text, 7);
				
				if ($this->check_map($map_id, true)) {
				
					$sql = "UPDATE ". DB_TABLE_USER ." SET map = ".$map_id." WHERE user_id = '".$chat_id."'";
				  	$ret = pg_query($db, $sql);
					   
					if(!$ret){
					   echo pg_last_error($db);
					   $reply = pg_last_error($db);
					   exit;
					} else {	
						$reply = "Mappa [".$map_id."] impostata.";
					}
				}
				else
					$reply = "Mappa non presente nel sistema o non attiva.";
				   
			    $content = array('chat_id' => $chat_id, 'text' => $reply);
				$telegram->sendMessage($content);
				$log=$today. ";information for activate map;" .$map_id. "\n";	
				file_put_contents(LOG_FILE, $log, FILE_APPEND | LOCK_EX);	
											
			
			}
			// imposta la mappa di default
			elseif ((substr($text, 0, 14) == "/setdefaultmap") 
				&& is_numeric(substr($text, 14)) && is_int(intval(substr($text, 14))) && $this->check_admin($user_id)) {
				
				$map_id = substr($text, 14);
					
				if ($this->check_map($map_id, true)) {
				
					$sql = "UPDATE ". DB_TABLE_MAPS ." SET def=false";
				  	$ret = pg_query($db, $sql);
					   
					if(!$ret){
					   echo pg_last_error($db);
					   $reply = pg_last_error($db);
					   exit;
					} else {
						$sql2 = "UPDATE ". DB_TABLE_MAPS ." SET def=true WHERE id_map=".$map_id;
				  		$ret2 = pg_query($db, $sql2);
						if(!$ret2){
						   echo pg_last_error($db);
						   $reply = pg_last_error($db);
						   exit;
						} else {
							$sql3 = "UPDATE ". DB_TABLE_USER ." SET map=".$map_id;
					  		$ret3 = pg_query($db, $sql3);
							if(!$ret3){
							   echo pg_last_error($db);
							   $reply = pg_last_error($db);
							   exit;
							} else {
							    $map = $this->info_map($map_id);
							    $reply = "Mappa [".$map_id.". ".$map[1]."] impostata come default.";
							    $msg = "[INFO] La mappa [".$map_id.". ".$map[1]."] è stata impostata come default.";
							    $msg .= "\n[".UMAP_URL."/m/".$map[4]."]";
							    $msg .= "\n\nLa puoi cambiare in qualsiasi momento con il comando /setmap";
							    $this->alert_usermap($telegram,$msg, $map_id, true);
							}

						}
					}
				}
				else
					$reply = "Mappa non presente nel sistema o non attiva.";
				   
			    $content = array('chat_id' => $chat_id, 'text' => $reply);
				$telegram->sendMessage($content);
				$log=$today. ";information for maps default;" .$map_id. "\n";	
				file_put_contents(LOG_FILE, $log, FILE_APPEND | LOCK_EX);	
											
			
			}
			// rendi la mappa privata/pubblica su mappa in uso
			elseif (($text == "/privatemap" || $text == "/publicmap") && ($this->check_admin($user_id) || $this->check_manager($user_id,$id_map) || $this->check_user_map($user_id,$id_map) )) {
					
				if ($this->check_map($id_map, true)) {
					
					if ($text == "/privatemap")
						$private = "true";
					else
						$private = "false";
				
					$sql = "UPDATE ". DB_TABLE_MAPS ." SET private=".$private." WHERE id_map=".$id_map;
				  	$ret = pg_query($db, $sql);
					   
					if(!$ret){
					   echo pg_last_error($db);
					   $reply = pg_last_error($db);
					   exit;
					} else {
						$map = $this->info_map($id_map);
						if ($text == "/privatemap") {
							$reply = "Mappa [".$id_map.". ".$map[1]."] impostata come privata.";
							$msg = "[INFO] La mappa [".$id_map.". ".$map[1]."] è stata resa privata.";
							$msg .= "\n[".UMAP_URL."/m/".$map[4]."]";
							$this->alert_usermap($telegram,$msg, $id_map, true);
						}
						else {
							$reply = "Mappa [".$id_map.". ".$map[1]."] impostata come pubblica.";
							$msg = "[INFO] La mappa [".$id_map.". ".$map[1]."] è stata resa pubblica.";
							$msg .= "\n[".UMAP_URL."/m/".$map[4]."]";
							$this->alert_usermap($telegram,$msg, $id_map, true);
						}
					}
				}
				else
					$reply = "Mappa non presente nel sistema o non attiva.";
				   
			    $content = array('chat_id' => $chat_id, 'text' => $reply);
				$telegram->sendMessage($content);
				$log=$today. ";information for maps default;" .$id_map. "\n";	
				file_put_contents(LOG_FILE, $log, FILE_APPEND | LOCK_EX);	
											
			
			}
			// rendi la mappa privata
			elseif ((substr($text, 0, 11) == "/privatemap") 
				&& is_numeric(substr($text, 11)) && is_int(intval(substr($text, 11))) 
				&& ($this->check_admin($user_id) || $this->check_manager($user_id,substr($text, 11)) || $this->check_user_map($user_id,substr($text, 11)) )) {
				
				$map_id = substr($text, 11);
					
				if ($this->check_map($map_id, true)) {
				
					$sql = "UPDATE ". DB_TABLE_MAPS ." SET private=true WHERE id_map=".$map_id;
				  	$ret = pg_query($db, $sql);
					   
					if(!$ret){
					   echo pg_last_error($db);
					   $reply = pg_last_error($db);
					   exit;
					} else {
						$map = $this->info_map($map_id);
						$reply = "Mappa [".$map_id.". ".$map[1]."] impostata come privata.";
						$msg = "[INFO] La mappa [".$map_id.". ".$map[1]."] è stata resa privata.";
						$msg .= "\n[".UMAP_URL."/m/".$map[4]."]";
						$this->alert_usermap($telegram,$msg, $map_id, true);
					}
				}
				else
					$reply = "Mappa non presente nel sistema o non attiva.";
				   
			    $content = array('chat_id' => $chat_id, 'text' => $reply);
				$telegram->sendMessage($content);
				$log=$today. ";information for maps default;" .$map_id. "\n";	
				file_put_contents(LOG_FILE, $log, FILE_APPEND | LOCK_EX);	
											
			
			}
			// rendi la mappa pubblica
			elseif ((substr($text, 0, 10) == "/publicmap") 
				&& is_numeric(substr($text, 10)) && is_int(intval(substr($text, 10))) 
				&& ($this->check_admin($user_id) || $this->check_manager($user_id,substr($text, 10)) || $this->check_user_map($user_id,substr($text, 10))) ) {
				
				$map_id = substr($text, 10);
					
				if ($this->check_map($map_id, true)) {
				
					$sql = "UPDATE ". DB_TABLE_MAPS ." SET private=false WHERE id_map=".$map_id;
				  	$ret = pg_query($db, $sql);
					   
					if(!$ret){
					   echo pg_last_error($db);
					   $reply = pg_last_error($db);
					   exit;
					} else {
						$map = $this->info_map($map_id);
						$reply = "Mappa [".$map_id.". ".$map[1]."] impostata come pubblica.";
						$msg = "[INFO] La mappa [".$map_id.". ".$map[1]."] è stata resa pubblica.";
						$msg .= "\n[".UMAP_URL."/m/".$map[4]."]";
						$this->alert_usermap($telegram,$msg, $map_id, true);
					}
				}
				else
					$reply = "Mappa non presente nel sistema o non attiva.";
				   
			    $content = array('chat_id' => $chat_id, 'text' => $reply);
				$telegram->sendMessage($content);
				$log=$today. ";information for maps default;" .$map_id. "\n";	
				file_put_contents(LOG_FILE, $log, FILE_APPEND | LOCK_EX);	
											
			
			}
			// attiva/disattiva mappa in uso
			elseif (($text == "/enablemap" || $text == "/disabledmap") && ($this->check_admin($user_id) || $this->check_manager($user_id,$id_map) || $this->check_user_map($user_id,$id_map) )) {
				
				if ($this->check_map($id_map, false)) {
					
						if ($text == "/enablemap")
							$enabled = "true";
						else
							$enabled = "false";
				
						$sql = "UPDATE ". DB_TABLE_MAPS ." SET enabled=".$enabled." WHERE id_map=".$id_map;
				  		$ret = pg_query($db, $sql);
						if(!$ret){
						   echo pg_last_error($db);
						   $reply = pg_last_error($db);
						   exit;
						} else {
							$map = $this->info_map($id_map);
							    
							if ($text == "/enablemap") {
						    	if ($map[6] == "f") {
								    $msg = "[INFO] E' stata attivata la mappa ".$id_map.". ".$map[1];
								    $msg .= "\n[".UMAP_URL."/m/".$map[4]."]";
								    $msg .= "\n\nSe la vuoi utilizzare: /map_".$map[1];
								    $this->alert_usermap($telegram, $msg, $id_map, true);
							    }
							    
							    $reply = "Mappa [".$id_map.". ".$map[1]."] attivata.";
							}
						    else {
						    	$reply = "Mappa [".$id_map.". ".$map[1]."] disattivata.";
								$msg = "[INFO] La mappa [".$id_map.". ".$map[1]."] è stata disattivata.";
								$this->alert_usermap($telegram, $msg, $id_map);		
								$default_id = $this->check_defaultmap();
								$sql2 = "UPDATE ". DB_TABLE_USER ." SET map=".$default_id. " WHERE map=".$id_map;
						  		$ret2 = pg_query($db, $sql2);
						    }
						}
						
				}
				else
					$reply = "Mappa non presente nel sistema";
				   
			    $content = array('chat_id' => $chat_id, 'text' => $reply);
				$telegram->sendMessage($content);
				$log=$today. ";information for activate map;" .$id_map. "\n";	
				file_put_contents(LOG_FILE, $log, FILE_APPEND | LOCK_EX);	
											
			
			}
			// attiva mappa
			elseif ((substr($text, 0, 10) == "/enablemap") 
				&& is_numeric(substr($text, 10)) && is_int(intval(substr($text, 10))) 
				&& ($this->check_admin($user_id) || $this->check_manager($user_id,substr($text, 10)) || $this->check_user_map($user_id,substr($text, 10)) )) {
				
				$map_id = substr($text, 10);
					
				if ($this->check_map($map_id, false)) {
				
						$sql = "UPDATE ". DB_TABLE_MAPS ." SET enabled=true WHERE id_map=".$map_id;
				  		$ret = pg_query($db, $sql);
						if(!$ret){
						   echo pg_last_error($db);
						   $reply = pg_last_error($db);
						   exit;
						} else {
						    $map = $this->info_map($map_id);
						    if ($map[6] == "f") {
							    $msg = "[INFO] E' stata attivata la mappa ".$map_id.". ".$map[1];
							    $msg .= "\n[".UMAP_URL."/m/".$map[4]."]";
							    $msg .= "\n\nSe la vuoi utilizzare: /map_".$map[1];
							    $this->alert_usermap($telegram, $msg, $map_id, true);
						    }
						    
						    $reply = "Mappa [".$map_id.". ".$map[1]."] attivata.";
						}
						
				}
				else
					$reply = "Mappa non presente nel sistema";
				   
			    $content = array('chat_id' => $chat_id, 'text' => $reply);
				$telegram->sendMessage($content);
				$log=$today. ";information for activate map;" .$map_id. "\n";	
				file_put_contents(LOG_FILE, $log, FILE_APPEND | LOCK_EX);	
											
			
			}
			// disattiva mappa
			elseif ((substr($text, 0, 12) == "/disabledmap") 
				&& is_numeric(substr($text, 12)) && is_int(intval(substr($text, 12))) 
				&& ($this->check_admin($user_id) || $this->check_manager($user_id,substr($text, 12)) || $this->check_user_map($user_id,substr($text, 12)) )) {
				
				$map_id = substr($text, 12);
					
				if ($this->check_map($map_id, false)) {
				
						$sql = "UPDATE ". DB_TABLE_MAPS ." SET enabled=false WHERE id_map=".$map_id;
				  		$ret = pg_query($db, $sql);
						if(!$ret){
						   echo pg_last_error($db);
						   $reply = pg_last_error($db);
						   exit;
						} else {
							$map = $this->info_map($map_id);
							$reply = "Mappa [".$map_id.". ".$map[1]."] disattivata.";
							$msg = "[INFO] La mappa [".$map_id.". ".$map[1]."] è stata disattivata.";
							$this->alert_usermap($telegram, $msg, $map_id);		
							$default_id = $this->check_defaultmap();
							$sql2 = "UPDATE ". DB_TABLE_USER ." SET map=".$default_id. " WHERE map=".$map_id;
					  		$ret2 = pg_query($db, $sql2);
						/*	if(!$ret2){
							   echo pg_last_error($db);
							   $reply = pg_last_error($db);
							   exit;
							} 	*/		    
						}
						
				}
				else
					$reply = "Mappa non presente nel sistema";
				   
			    $content = array('chat_id' => $chat_id, 'text' => $reply);
				$telegram->sendMessage($content);
				$log=$today. ";information for activate map;" .$map_id. "\n";	
				file_put_contents(LOG_FILE, $log, FILE_APPEND | LOCK_EX);	
											
			
			}
			// attiva notifiche
			elseif ($text == "/alerton") {
				
				$sql = "UPDATE ". DB_TABLE_USER ." SET alert=true WHERE user_id='".$chat_id."'";
				$ret = pg_query($db, $sql);
				if(!$ret){
					echo pg_last_error($db);
					$reply = pg_last_error($db);
					exit;
				} else {
					$reply = "Notifiche attivate.";						    
				}		
				   
			    $content = array('chat_id' => $chat_id, 'text' => $reply);
				$telegram->sendMessage($content);
				$log=$today. ";information for activate alert;" .$chat_id. "\n";	
				file_put_contents(LOG_FILE, $log, FILE_APPEND | LOCK_EX);	
											
			
			}
			// disattiva notifiche
			elseif ($text == "/alertoff") {
				
				$sql = "UPDATE ". DB_TABLE_USER ." SET alert=false WHERE user_id='".$chat_id."'";
				$ret = pg_query($db, $sql);
				if(!$ret){
					echo pg_last_error($db);
					$reply = pg_last_error($db);
					exit;
				} else {
					$reply = "Notifiche disattivate.";						    
				}		
				   
			    $content = array('chat_id' => $chat_id, 'text' => $reply);
				$telegram->sendMessage($content);
				$log=$today. ";information for activate alert;" .$chat_id. "\n";	
				file_put_contents(LOG_FILE, $log, FILE_APPEND | LOCK_EX);	
											
			
			}
			//profilo utente
			elseif ($text == "/me") {
				
				$response=$telegram->getData();
				$username=$response["message"]["from"]["username"];
			    $first_name=$response["message"]["from"]["first_name"];
			    $last_name=$response["message"]["from"]["last_name"];
			    
			    $user = $this->check_user($chat_id);
				
				//check stati segnalazioni utente
				$sql =  "SELECT se.state, st.stato, count(se.state) FROM ".DB_TABLE_GEO." se JOIN ".DB_TABLE_STATE." st ON se.state = st.id WHERE iduser='".$chat_id."' AND map = ".$id_map." GROUP BY se.state, st.stato ORDER BY se.state";
				
				$ret = pg_query($db, $sql);
				   if(!$ret){
				      echo pg_last_error($db);
				      exit;
				   } 
						  
				$reply = "Ciao ".$first_name.", 
ecco il tuo profilo:\n\n";

if ($username)
	$reply .= "username: @".$username."\n\n";
	
$reply .= "MAPPA ATTIVA NEL BOT
 
".$id_map.". ".$name_map." [".$shortUrl."]";

				if (pg_num_rows($ret)) {
					$reply .= "\n\nSegnalazioni su ".$name_map."\n";
	
					$row = array();
					
				    while($res = pg_fetch_row($ret)){
				    	$reply .= $res[1].": ".$res[2]."\n";
				    }	
				}
				else {
					$reply .= "\n\nNon ci sono ancora segnalazioni su ".$name_map."\n\n";
				}
			    
			    
			    $reply .= "\nNotifiche: ".(($user[4]=="t")?"SI":"NO");
			    $reply .= "\n[ ".(($user[4]=="t")?"disattiva: /alertoff":"attiva: /alerton")." ]";
			
				
			//	if ($this->check_admin($chat_id) || $this->check_manager($chat_id)) {
					
					$sql =  "SELECT * FROM ". DB_TABLE_MAPS ." WHERE author = '".$chat_id."' ORDER BY id_map";
				
					$ret = pg_query($db, $sql);
					   if(!$ret){
					      echo pg_last_error($db);
					      exit;
					   } 
					  
				    $row = array();
				    
				    $reply .= "\n\n\nLE TUE MAPPE\n\n";
				    while($res = pg_fetch_row($ret)){
				    	$shortUrl= UMAP_URL ."/m/". $res[4];
				    	if ($res[5] == "t")
				    		$def = " (default)";
				    	else if ($res[3] == "f")
				    		$def = " (non attiva)";
				        else
				        	$def = "";
				        
				        if ($res[6] == "t")
				    		$def .= " [P]";
	
				    	$reply .= $res[0].". ".$res[1]."".$def." - /infomap".$res[0]."\n";
				    	//$reply .= "[".$shortUrl."]\n\n"; 
				//    }	

				}


				$content = array('chat_id' => $chat_id, 'text' => $reply,'disable_web_page_preview'=>true);
				$telegram->sendMessage($content);
				$log=$today. ",me sent," .$sql. "\n";
					 
			}
			// lista dei web service geografici del BOT
			elseif ($text == "/webservice" || $text == "webservice") {
				
				$log=$today. ",webservice requested," .$chat_id. "\n";				
      			$this->create_keyboard_webservice($telegram,$chat_id);
     			exit;
			    				 
			}
			// visualizza web service selezionato
			elseif ($text == "/shapefile" || $text == "shapefile"
				|| $text == "/gml" || $text == "gml"
				|| $text == "/csv" || $text == "csv"
				|| $text == "/geojson" || $text == "geojson"
				|| $text == "/kml" || $text == "kml"
				|| $text == "/wms" || $text == "wms"
				|| $text == "/wfs" || $text == "wfs"
				|| $text == "/csw" || $text == "csw") {
					
				$log=$today. ",".$text." requested," .$chat_id. "\n";
				$url_ws = "";
				switch ($text) {
				    case "/shapefile" :
				    case "shapefile":
				        $url_ws = WS_SHAPEFILE;
				        break;
				    case "/gml" :
				    case "gml":
				        $url_ws = WS_GML;
				        break;
				    case "/csv" :
				    case "csv":
				        $url_ws = WS_CSV;
				        break;
				    case "/geojson" :
				    case "geojson":
				        $url_ws = WS_GEOJSON;
				        break;
				    case "/kml" :
				    case "kml":
				        $url_ws = WS_KML;
				        break;
				    case "/wms" :
				    case "wms":
				        $url_ws = WS_WMS;
				        break;
				    case "/wfs" :
				    case "wfs":
				        $url_ws = WS_WFS;
				        break;
				    case "/csw" :
				    case "csw":
				        $url_ws = WS_CSW;
				        break;
				}
				$reply = "[ Link servizio: ".$url_ws." ]";
				$reply_markup = $telegram->buildKeyBoardHide();
				$content = array('chat_id' => $chat_id, 'text' => $reply, 'reply_markup' => $reply_markup);
			
				$telegram->sendMessage($content);		
				
			}
			// attiva/disattiva la funzionalità di approvazione delle segnalazioni della mappa in uso
			elseif (($text == "/offapproved" || $text == "/onapproved") && ($this->check_admin($user_id) || $this->check_manager($user_id,$id_map) || $this->check_user_map($user_id,$id_map) )) {
				
				if ($text == "/onapproved") {
					$sql = "UPDATE ".DB_TABLE_MAPS ." SET approve = true WHERE enabled = true and id_map = ".$id_map;
					$reply = "\nProcedura di approvazione attivata per la mappa ".$id_map;
				}
				else {
					$sql = "UPDATE ".DB_TABLE_MAPS ." SET approve = false WHERE enabled = true and id_map = ".$id_map;
					$reply = "\nProcedura di approvazione disattivata per la mappa ".$id_map;
				}
			    
				$ret = pg_query($db, $sql);
				  
   				if(!$ret){
   				   echo pg_last_error($db);
   				   $reply = pg_last_error($db);
   				   exit;
   				} 
				   
			    $content = array('chat_id' => $chat_id, 'text' => $reply);
				$telegram->sendMessage($content);
				$log=$today. ";change approved;" .$text. "\n";	
				file_put_contents(LOG_FILE, $log, FILE_APPEND | LOCK_EX);	
			    				 
			}
			// attiva la funzionalità di approvazione delle segnalazioni di una mappa tramite id
			elseif	((substr($text, 0, 11) == "/onapproved") 
				&& is_numeric(substr($text, 11)) && is_int(intval(substr($text, 11))) 
				&& ($this->check_admin($user_id) || $this->check_manager($user_id,substr($text, 11)) || $this->check_user_map($user_id,substr($text, 11)) )) {
				
				$map_id = substr($text, 11);
				$sql = "UPDATE ".DB_TABLE_MAPS ." SET approve = true WHERE enabled = true and id_map = ".$map_id;
				$reply = "\nProcedura di approvazione attivata per la mappa ".$map_id;
			    
				$ret = pg_query($db, $sql);
				  
   				if(!$ret){
   				   echo pg_last_error($db);
   				   $reply = pg_last_error($db);
   				   exit;
   				} 
				   
			    $content = array('chat_id' => $chat_id, 'text' => $reply);
				$telegram->sendMessage($content);
				$log=$today. ";change approved;" .$text. "\n";	
				file_put_contents(LOG_FILE, $log, FILE_APPEND | LOCK_EX);	
			    				 
			}
			// disattiva la funzionalità di approvazione delle segnalazioni di una mappa tramite id
			elseif	((substr($text, 0, 12) == "/offapproved") 
				&& is_numeric(substr($text, 12)) && is_int(intval(substr($text, 12))) 
				&& ($this->check_admin($user_id) || $this->check_manager($user_id,substr($text, 12)) || $this->check_user_map($user_id,substr($text, 12)) )) {
				
				$map_id = substr($text, 12);
				$sql = "UPDATE ".DB_TABLE_MAPS ." SET approve = false WHERE enabled = true and id_map = ".$map_id;
				$reply = "\nProcedura di approvazione disattivata per la mappa ".$map_id;
			    
				$ret = pg_query($db, $sql);
				  
   				if(!$ret){
   				   echo pg_last_error($db);
   				   $reply = pg_last_error($db);
   				   exit;
   				} 
				   
			    $content = array('chat_id' => $chat_id, 'text' => $reply);
				$telegram->sendMessage($content);
				$log=$today. ";change approved;" .$text. "\n";	
				file_put_contents(LOG_FILE, $log, FILE_APPEND | LOCK_EX);	
			    				 
			}
			// creazione mappa
			elseif ($text == "/newmap" && ($this->check_admin($user_id) || $this->check_manager($user_id)) ) {
				
				//nascondo la tastiera e forzo l'utente a darmi una risposta
				$forcehide=$telegram->buildForceReply(true);

				//chiedo cosa sta accadendo nel luogo
				$content = array('chat_id' => $chat_id, 'text' => "[Dai un nome alla mappa]", 'reply_markup' =>$forcehide, 'reply_to_message_id' =>$bot_request_message_id);
				$bot_request_message=$telegram->sendMessage($content);
				
				
				
			}
			// mappa personale (info o creazione mappa)
			elseif ($text == "/mymap") {
				
				$map = $this->check_user_map($chat_id);
				
				if ($map) {
					
					/*
					['id_map'] = $map[0];
					['name_map'] = $map[1];
					['approve'] = $map[2];
					['enabled'] = $map[3];
					['umap_id'] = $map[4];
					['def'] = $map[5];
					['private'] = $map[6];
					['author'] = $map[7];
					['password'] = $map[8];
					*/
					    		
					$shortUrl= UMAP_URL ."/m/". $map[4];
							
					$reply = "MAPPA n.".$map[0]." ".$map[1]."\n";
					$reply .= "[".$shortUrl."]\n\n";
							
					$reply .= "Per usarla: /setmap".$map[0]."\n\n";
					
					if ($map[2] == 't') {
						$reply .= "APPROVAZIONE SEGNALAZIONI ABILITATA\n";
						$reply .= "per disabilitarla: /offapproved".$map[0]."\n";
					}	
					else {
						$reply .= "APPROVAZIONE SEGNALAZIONI NON ABILITATA\n";
						$reply .= "per abilitarla: /onapproved".$map[0]."\n";
					}	
					if ($map[6] == 't') {
						$reply .= "\nMAPPA PRIVATA\n";
						$reply .= "per renderla pubblica: /publicmap".$map[0]."\n";
					}
					else {
						$reply .= "\nMAPPA PUBBLICA\n";
						$reply .= "per renderla privata: /privatemap".$map[0]."\n";
					}
					
					if ($map[3] == 't') {
						$reply .= "\nMAPPA ATTIVA\n";
						$reply .= "per disabilitarla: /disabledmap".$map[0]."\n";
					}
					else {
						$reply .= "\nMAPPA NON ATTIVA\n";
						$reply .= "per attivarla: /enablemap".$map[0]."\n";
					}
							
					//check stati segnalazioni mappa
					$sql =  "SELECT se.state, st.stato, count(se.state) FROM ".DB_TABLE_GEO." se JOIN ".DB_TABLE_STATE." st ON se.state = st.id WHERE map = ".$map[0]." GROUP BY se.state, st.stato ORDER BY se.state";
							
					$ret = pg_query($db, $sql);
					if(!$ret){
					    echo pg_last_error($db);
					    exit;
					} 
							   
					if (pg_num_rows($ret)) {
						$reply .= "\nRIEPILOGO SEGNALAZIONI:\n";
						$row = array();
								
					    while($res = pg_fetch_row($ret)){
					    	$reply .= $res[1].": ".$res[2]."\n";
					   	}	
					}
					else {
						$reply .= "\nNon ci sono ancora SEGNALAZIONI su questa mappa\n";
					}
					
					$content = array('chat_id' => $chat_id, 'text' => $reply);
					$telegram->sendMessage($content);
				
				} 
				else {
					//nascondo la tastiera e forzo l'utente a darmi una risposta
					$forcehide=$telegram->buildForceReply(true);
	
					//chiedo cosa sta accadendo nel luogo
					$content = array('chat_id' => $chat_id, 'text' => "[Dai un nome alla mappa]", 'reply_markup' =>$forcehide, 'reply_to_message_id' =>$bot_request_message_id);
					$bot_request_message=$telegram->sendMessage($content);
				}				
				
			}
			// inserimento effettivo della nuova mappa
			elseif($reply_to_msg["text"] == "[Dai un nome alla mappa]" 
				&& ($this->check_admin($user_id) || $this->check_manager($user_id) || $this->check_user_map($user_id) == false))
			{
			    $response=$telegram->getData();
			    $text = strtolower($this->clean($response["message"]["text"]));
			    
			    if ($this->check_name_map($text,false)) {
			    	$reply = "Il nome [".$text."] è già presente, usane un'altro";
			    	$content = array('chat_id' => $chat_id, 'text' => $reply,'disable_web_page_preview'=>true);
					$telegram->sendMessage($content);
			    	exit;
			    }
			    else if (strlen($text) > 10 || strlen($text) < 3) {
			    	$reply = "Il nome deve essere da 3 a 10 caratteri";
			    	$content = array('chat_id' => $chat_id, 'text' => $reply,'disable_web_page_preview'=>true);
					$telegram->sendMessage($content);
			    	exit;
			    }
			    
			    $time=$response["message"]["date"]; //registro nel DB anche il tempo unix
				
				$h = "1";// Hour for time zone goes here e.g. +7 or -4, just remove the + or -
  				$hm = $h * 60;
  				$ms = $hm * 60;
  				$timec=gmdate("Y-m-d\TH:i:s\Z", $time+($ms));
  				$timec=str_replace("T"," ",$timec);
  				$timec=str_replace("Z"," ",$timec);
			    
			    $db2 = $this->getdb_umap();
			    
			    $sql = "INSERT INTO ".DB_TABLE_UMAP_MAP." (name, slug, center, zoom, locate, modified_at,edit_status, share_status, settings, licence_id) VALUES ('".$text."', '".$text."', '0101000020E610000001000000009C2840ED1B870D6C7A4540', 7, FALSE, '".$timec."',1,2,'{\"geometry\": {\"type\": \"Point\", \"coordinates\": [12.304687500000002, 42.956422511073335]}, \"type\": \"Feature\", \"properties\": {\"miniMap\": false, \"zoomControl\": true, \"description\": \"Se vuoi contribuire con delle segnalazioni in mappa, usa il BOT Telegram [[https://telegram.me/geonuebot|@GeoNueBOT]] - [[https://telegram.me/geonuebot|Accedi a @GeoNueBOT]]\", \"scrollWheelZoom\": true, \"scaleControl\": true, \"wmslayer\": {\"wms_url\": \"http://dev-geonue.nordai.it/geonueserver/wms\"}, \"displayPopupFooter\": false, \"zoom\": 5, \"slideshow\": {}, \"limitBounds\": {}, \"tilelayer\": {}, \"datalayersControl\": true, \"moreControl\": true, \"licence\": \"\", \"tilelayersControl\": true, \"captionBar\": false, \"shortCredit\": \"GeoNueBOT Telegram\", \"name\": \"".$text."\"}}',2) RETURNING id;";
    
    			file_put_contents(LOG_FILE, $sql."\n", FILE_APPEND | LOCK_EX);
				$ret = pg_query($db2, $sql);
				
				if(!$ret){
				   echo pg_last_error($db2);
				   $log = $timec.";query;errore inserimento mappa:".$text."\n";
				   file_put_contents(LOG_FILE, $log, FILE_APPEND | LOCK_EX);
				} else {
					
					$new_umap_id = pg_fetch_array($ret);
					
					$sql = "INSERT INTO ". DB_TABLE_UMAP_LAYER ."(name, geojson, display_on_load, map_id) VALUES ('Registrate', 'datalayer/0/1/".$text."_registrate.geojson', TRUE, ".$new_umap_id[0].");";
					    
					$sql .= "INSERT INTO ". DB_TABLE_UMAP_LAYER ."(name, geojson, display_on_load, map_id) VALUES ('Approvate', 'datalayer/0/1/".$text."_approvate.geojson', TRUE, ".$new_umap_id[0].");";
					
					$sql .= "INSERT INTO ". DB_TABLE_UMAP_LAYER ."(name, geojson, display_on_load, map_id) VALUES ('Respinte', 'datalayer/0/1/".$text."_respinte.geojson', TRUE, ".$new_umap_id[0].");";
				
					
					file_put_contents(LOG_FILE, $sql."\n", FILE_APPEND | LOCK_EX);
					$ret2 = pg_query($db2, $sql);
					if(!$ret2){
					   echo pg_last_error($db2);
					   $log = $timec.";query;errore inserimento layers:".$text."\n";
					   file_put_contents(LOG_FILE, $log, FILE_APPEND | LOCK_EX);
					} else {
						
					   pg_close($db2);

					   $db = $this->getdb();
					   
					   
					   if ($this->check_user_map($user_id))
					   		$mymap = 'FALSE';
					   else
					    	$mymap = 'TRUE';
					   
					   $sql = "INSERT INTO ". DB_TABLE_MAPS ." (name_map, approve, enabled, umap_id, def, private, author, mymap) VALUES ('".$text."', TRUE, TRUE, ".$new_umap_id[0].", FALSE, TRUE, '".$chat_id."',".$mymap.") RETURNING id_map;";
					   
					   file_put_contents(LOG_FILE, $sql."\n", FILE_APPEND | LOCK_EX);
					   
					   $ret3 = pg_query($db, $sql);
					   					   
					   if(!$ret3){
					   		echo pg_last_error($db);
					   		$log = $timec.";query;errore inserimento mappa su BOT:".$text."\n";
					  		 file_put_contents(LOG_FILE, $log, FILE_APPEND | LOCK_EX);
						} else {
							
							$new_id = pg_fetch_array($ret3);
							
							$file_01 = UMAP_PATH_ORI.'approvate.geojson';
							$newfile_01 = UMAP_PATH_DEST.''.$text.'_approvate.geojson';
							if (!copy($file_01, $newfile_01)) {
								    $log = "failed to copy $file_01...\n";
								    file_put_contents(LOG_FILE, $log, FILE_APPEND | LOCK_EX);
							}
							$str=file_get_contents($newfile_01);
							$str=str_replace("[name_map]", $text,$str);
							file_put_contents($newfile_01, $str);
							
							
							$file_02 = UMAP_PATH_ORI.'registrate.geojson';
							$newfile_02 = UMAP_PATH_DEST.''.$text.'_registrate.geojson';
							if (!copy($file_02, $newfile_02)) {
								    $log = "failed to copy $file_02...\n";
								    file_put_contents(LOG_FILE, $log, FILE_APPEND | LOCK_EX);
							}
							$str=file_get_contents($newfile_02);
							$str=str_replace("[name_map]", $text,$str);
							file_put_contents($newfile_02, $str);
							
							$file_03 = UMAP_PATH_ORI.'respinte.geojson';
							$newfile_03 = UMAP_PATH_DEST.''.$text.'_respinte.geojson';
							if (!copy($file_03, $newfile_03)) {
								    $log = "failed to copy $file_03...\n";
								    file_put_contents(LOG_FILE, $log, FILE_APPEND | LOCK_EX);
							}
							$str=file_get_contents($newfile_03);
							$str=str_replace("[name_map]", $text,$str);
							file_put_contents($newfile_03, $str);
							
						
						   $reply = "La mappa [".$new_id[0].". ".$text."] è stata creata.";
						   $reply .= "\n[".UMAP_URL."/m/".$new_umap_id[0]."]\n\n";
						   
						   $reply .= "Per usarla: /setmap".$new_id[0]."\n\n";
						   
						   $reply .= "La mappa è privata. Per renderla pubblica: /publicmap".$new_id[0]."\n\n";
  
						   $reply .= "Per diventare proprietario della mappa su uMap seguire i seguenti passi:\n";
						   $reply .= "1. modifica mappa (icona penna)\n";
						   $reply .= "2. copiare link segreto su permessi mappa (icona chiave)\n";
						   $reply .= "3. tornare alla home (". UMAP_URL.") e autenticarsi\n";
						   $reply .= "4. andare sul link segreto\n\n";
						   
						   $reply .= "GESTIONE MAPPA
							
/enablemap".$new_id[0]." - attiva mappa
/disabledmap".$new_id[0]." - disattiva mappa
/privatemap".$new_id[0]." - la rende privata
/publicmap".$new_id[0]." - la rende pubblica
/onapproved".$new_id[0]." - attiva procedura approvazione 
/offapproved".$new_id[0]." - disattiva procedura approvazione
";
					    
					       $content = array('chat_id' => $chat_id, 'text' => $reply,'disable_web_page_preview'=>true);
						   $telegram->sendMessage($content);
						
						   $log = $timec.";query;mappa inserita:".$text."\n";
						   file_put_contents(LOG_FILE, $log, FILE_APPEND | LOCK_EX);
						}
						
						pg_close($db);
						
					}
   				}
							    
			}
			// aiuto per gli utenti
			elseif ($text == "/help" || $text == "help") {
					 
					 $reply = ("GeoNueBot e' un servizio per georiferire informazioni in mappa.

Per inviare una segnalazione, clicca [Invia posizione] dall'icona a forma di graffetta e aspetta una decina di secondi. Quando ricevi la risposta automatica, puoi scrivere un testo descrittivo o allegare un contenuto video foto audio ect.

Mappa in uso: ".$id_map.". ".$name_map."
Per vedere le segnalazioni in mappa: ".$shortUrl."

Lista mappe disponibili: /maplist
Per cambiare mappa: /setmap

Il tuo profilo: /me


OPZIONI AVANZATE

/alerton - attiva avvisi
/alertoff - disattiva avvisi
/webservice - servizi di interoperabilità per consultare e scaricare i dati

/C+[numero segnalazione] - cancella segnalazione (es: /C001), solo se in stato registrata o sospesa 

/manager - funzionalità dedicate per gestione mappe"	
	          );
					 $content = array('chat_id' => $chat_id, 'text' => $reply,'disable_web_page_preview'=>true);
					 $telegram->sendMessage($content);
					 $log=$today. ",crediti sent," .$chat_id. "\n";
					 
			}
			// guida per amministratori
			elseif ($text == "/manager") {
			
			$reply = ("GESTIONE SEGNALAZIONI 

Alla domanda [APPROVI? Y/N] rispondere Y per si, N per no. 

Per approvazione diretta: 
/A+numrequest - approva (es: /A001)
/R+numrequest - respingi (es: /R001)
/S+numrequest - sospendi (es: /S001)
/C+numrequest - cancella (es: /C001)


CREAZIONE E GESTIONE MAPPE

/mymap - crea mappa personale (una per utente)
/newmap - crea nuova mappa (solo per profili avanzati)

[ Prima di utilizzare queste funzionalità impostare la mappa con /setmap ]
/enablemap - attiva mappa
/disabledmap - disattiva mappa
/privatemap - rende mappa privata
/publicmap - rende mappa pubblica
/onapproved - abilita procedura approvazione  
/offapproved - disabilita procedura approvazione
/Alist - lista segnalazioni da approvare
/Slist - lista segnalazioni in sospeso

"			
			          );
			          
			           $content = array('chat_id' => $chat_id, 'text' => $reply,'disable_web_page_preview'=>true);
					 $telegram->sendMessage($content);
					 $log=$today. ",crediti sent," .$chat_id. "\n";


			} 
			// guida per amministratori
			elseif (($text == "/admin" || $text == "admin") && $this->check_admin($user_id)) {
					 
					 $reply = ("[Funzionalità dedicate agli amministratori]
					 
/listallmap - lista di tutte le mappe del sistema

/setdefaultmap+[idmappa] - imposta la mappa del bot di default (es: /setdefaultmap1)

"			
	          );
					 $content = array('chat_id' => $chat_id, 'text' => $reply,'disable_web_page_preview'=>true);
					 $telegram->sendMessage($content);
					 $log=$today. ",crediti sent," .$chat_id. "\n";
					 
			}   				 
			//comando errato
			else{
				 $reply = "Hai selezionato un comando non previsto o non hai le autorizzazioni per poterlo utilizzare";
				 $content = array('chat_id' => $chat_id, 'text' => $reply);
				 $telegram->sendMessage($content);
				 $log=$today. ";wrong command sent;" .$chat_id. "\n";
			 }
						
			//aggiorna tastiera
			//$this->create_keyboard($telegram,$chat_id);
			//log			
			file_put_contents(LOG_FILE, $log, FILE_APPEND | LOCK_EX);
			
			pg_close($db);
			
	}


	// Crea la tastiera
 	function create_keyboard($telegram, $chat_id)
	{
		$forcehide=$telegram->buildKeyBoardHide(true);
		$content = array('chat_id' => $chat_id, 'text' => "", 'reply_markup' =>$forcehide, 'reply_to_message_id' =>$bot_request_message_id);
		$bot_request_message=$telegram->sendMessage($content);
	}

     //crea la tastiera per download
     function create_keyboard_webservice($telegram, $chat_id)
      {
          $option = array(["kml","gml","csv"],["geojson","shapefile"],["wms","wfs","csw"]);
          $keyb = $telegram->buildKeyBoard($option,$onetime=true);
          $content = array('chat_id' => $chat_id, 'reply_markup' => $keyb, 'text' => "[Seleziona il servizio.]");
          $telegram->sendMessage($content);
      }
	  
      //salva la posizione
		function location_manager($telegram,$user_id,$chat_id,$location)
		{
				$lng=$location["longitude"];
				$lat=$location["latitude"];
				
				//rispondo
				$response=$telegram->getData();
				$bot_request_message_id=$response["message"]["message_id"];
				$time=$response["message"]["date"]; //registro nel DB anche il tempo unix
				
				$h = "1";// Hour for time zone goes here e.g. +7 or -4, just remove the + or -
  				$hm = $h * 60;
  				$ms = $hm * 60;
  				$timec=gmdate("Y-m-d\TH:i:s\Z", $time+($ms));
  				$timec=str_replace("T"," ",$timec);
  				$timec=str_replace("Z"," ",$timec);

				
				//nascondo la tastiera e forzo l'utente a darmi una risposta
				$forcehide=$telegram->buildForceReply(true);

				//chiedo cosa sta accadendo nel luogo
				$content = array('chat_id' => $chat_id, 'text' => "[Cosa vuoi comunicare qui?]", 'reply_markup' =>$forcehide, 'reply_to_message_id' =>$bot_request_message_id);
				$bot_request_message=$telegram->sendMessage($content);
				
				//memorizzare nel DB
				$obj=json_decode($bot_request_message);
				$id=$obj->result;
				$id=$id->message_id;
				
				$db = $this->getdb();
				
				//CHECK mappa utente
				$id_map = $this->check_setmap($telegram,$user_id);
					    
								
				$sql = "INSERT INTO ". DB_TABLE_GEO. "(lat,lng, iduser,text_msg,bot_request_message,data_time,file_id,file_path,file_type,geom,state,map) VALUES (".$lat.",".$lng.",'".$user_id."',' ','".$id."','".$timec."',' ',' ',' ',ST_GeomFromText('POINT(".$lng." ".$lat.")', 4326),0, ".$id_map.")";
				
				file_put_contents(LOG_FILE, $sql."\n", FILE_APPEND | LOCK_EX);
				$ret = pg_query($db, $sql);
				if(!$ret){
				   echo pg_last_error($db);
				   $log = $timec.";query;errore inserimento posizione user_id:".$user_id."\n";
				   file_put_contents(LOG_FILE, $log, FILE_APPEND | LOCK_EX);
				} else {
				   $log = $timec.";query;posizione inserita user_id:".$user_id."\n";
				   file_put_contents(LOG_FILE, $log, FILE_APPEND | LOCK_EX);
   				}
				
				pg_close($db);

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
		
		//connessione al DB UMAP
		function getdb_umap() {
			// Instances the class		
			$host        = "host=".DB_UMAP_HOST;
			$port        = "port=".DB_UMAP_PORT;
			$dbname      = "dbname=". DB_UMAP_NAME;
			$credentials = "user=".DB_UMAP_USER." password=".DB_UMAP_PASSWORD;
				
			$db = pg_connect("$host $port $dbname $credentials");
		    return $db;
		}
		
		// estrapolazione stringa per procedura approvazione segnalazioni
		function get_string_between($string, $start, $end){
		    $string = ' ' . $string;
		    $ini = strpos($string, $start);
		    if ($ini == 0) return '';
		    $ini += strlen($start);
		    $len = strpos($string, $end, $ini) - $ini;
		    return substr($string, $ini, $len);
		}
		
		// verifica se amministratore
		function check_admin($id_user){
			
			$db = $this->getdb();
			
		    $sql =  "SELECT * FROM ".DB_TABLE_USER ." WHERE type_role = 'admin' and user_id = '".$id_user."'";
			
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

		// verifica se manager (opzionale della mappa)
		function check_manager($id_user, $id_map = false){
			
			$db = $this->getdb();
			
			if ($id_map)
		    	$sql = "SELECT * FROM ".DB_TABLE_USER ." u JOIN ". DB_TABLE_MAPS ." m ON u.user_id = m.author and u.type_role = 'manager' and u.user_id = '".$id_user."'and m.id_map = ".$id_map;
		    else
				$sql =  "SELECT * FROM ".DB_TABLE_USER ." WHERE type_role = 'manager' and user_id = '".$id_user."'";

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
		
		//verifica esistenza utente
		function check_user($id_user){
			
			$db = $this->getdb();
			
		    $sql =  "SELECT * FROM ".DB_TABLE_USER ." WHERE user_id = '".$id_user."'";
			
			$ret = pg_query($db, $sql);
			   if(!$ret){
			      echo pg_last_error($db);
			      return false;
			   }
			   
			if (pg_num_rows($ret)) {
				while($res = pg_fetch_row($ret)){
			    	if(!isset($res[0])) continue;
			    		return $res;
			    }
			}
		    else
		    	return false;
		
		}
		
		//per impostare nuovo utente e sapere l'id della mappa attiva
		function check_setmap($telegram, $id_user, $def_map) {
			
			$db = $this->getdb();
			
			$response=$telegram->getData();
				
			$username=$response["message"]["from"]["username"];
			$first_name=$response["message"]["from"]["first_name"];
			$last_name=$response["message"]["from"]["last_name"];
			
			if (!$this->check_user($id_user)) {
				
				//check mappa di default
				$map_id = $this->check_defaultmap();				
				$sql = "INSERT INTO ". DB_TABLE_USER. "(user_id,type_role,approved,map,alert,first_name,last_name,username) VALUES ('".$id_user."','user',false,".$map_id.",true,'".$first_name."','".$last_name."','".$username."')";
				
				$ret = pg_query($db, $sql);
				if(!$ret){
				   echo pg_last_error($db);
				   $log = $timec.";query;errore inserimento utente user_id:".$id_user."\n";
				   file_put_contents(LOG_FILE, $log, FILE_APPEND | LOCK_EX);
				} else {
				   $log = $timec.";query;utente inserito user_id:".$id_user."\n";
				   file_put_contents(LOG_FILE, $log, FILE_APPEND | LOCK_EX);
   				}
   				
   				return $map_id;
			
			}
			else {
				
				$sql = "UPDATE ". DB_TABLE_USER ." SET first_name = '".$first_name."', last_name = '".$last_name."', username = '".$username."'  WHERE user_id ='".$id_user."'";
				
				$ret = pg_query($db, $sql);
				   if(!$ret){
				      echo pg_last_error($db);
				      exit;
				   } 

				$sql =  "SELECT map FROM ". DB_TABLE_USER ." WHERE user_id ='".$id_user."'";
			
				$ret = pg_query($db, $sql);
				   if(!$ret){
				      echo pg_last_error($db);
				      exit;
				   } 
				  
			    $row = array();
			    $i=0;
			    
			    while($res = pg_fetch_row($ret)){
			    	if(!isset($res[0])) continue;
			    		return $res[0];
			    }
		
			}			
			
		}
		
		// controlla quale è la mappa di default
		function check_defaultmap() {
			$db = $this->getdb();
			
			//check mappa di default
				$sql =  "SELECT id_map, umap_id, name_map FROM ".DB_TABLE_MAPS ." WHERE def=true";
						
				$ret = pg_query($db, $sql);
				   if(!$ret){
				      echo pg_last_error($db);
				      exit;
				   } 
						  
				$row = array();
				
				while($res = pg_fetch_row($ret)){
				  	if(!isset($res[0])) continue;
				  		return $res[0];
				}
				
				pg_close($db);

		}
		
		//check per verificare se è attiva nella mappa corrente la procedura di approvazione
		function check_approved(){
			
			$db = $this->getdb();
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
		    
		   pg_close($db);
	
		}
		
		//check per verificare se esiste la mappa tramite l'id
		function check_map($id_map, $enabled){
			
			$db = $this->getdb();
			if ($enabled)
				$enabled = "enabled = true and ";
			else
				$enabled = "";
				
		    $sql =  "SELECT * FROM ". DB_TABLE_MAPS ." WHERE ".$enabled." id_map=".$id_map;
			$ret = pg_query($db, $sql);
			
		   if(!$ret){
		      echo pg_last_error($db);
		      return false;
		   }
		   
		   if (pg_num_rows($ret))
		   	return true;
		   else
		    return false;
	
		   pg_close($db);

		}
		
		//check per verificare se esiste la mappa tramite il nome
		function check_name_map($name_map, $enabled){
			
			$db = $this->getdb();
			if ($enabled)
				$enabled = "enabled = true and ";
			else
				$enabled = "";
				
		    $sql =  "SELECT * FROM ". DB_TABLE_MAPS ." WHERE ".$enabled." name_map='".$name_map."'";
			$ret = pg_query($db, $sql);
			
		   if(!$ret){
		      echo pg_last_error($db);
		      return false;
		   }
		   
		   if (pg_num_rows($ret)) {
		   	while($res = pg_fetch_row($ret)){
			 	if(!isset($res[0])) continue;
			   		return $res[0];
			}	
		//   	return true;
		   }
		   else
		    return false;
	
		   pg_close($db);

		}
		
		// verifica mappa utente
		function check_user_map($id_user, $id_map = false) {
			
			$db = $this->getdb();
			
			if ($id_map) $filter = " AND id_map=".$id_map." ";
			else $filter = "";
			
			$sql =  "SELECT * FROM ". DB_TABLE_MAPS ." WHERE mymap = true ".$filter." AND author ='".$id_user."'";
			
			$ret = pg_query($db, $sql);
			   if(!$ret){
			      echo pg_last_error($db);
			      exit;
			   } 
				  
			$row = array();
			$i=0;
			    
			while($res = pg_fetch_row($ret)){
			 	if(!isset($res[0])) continue;
			   		return $res;
			}			
			
		}
		
		// recupera le info della mappa di umap
		function get_umap($id_umap) {
			
			$db = $this-> getdb_umap();
			
			$sql =  "SELECT * FROM leaflet_storage_map WHERE id = ".$id_umap;
			
			$ret = pg_query($db, $sql);
			   if(!$ret){
			      echo pg_last_error($db);
			      exit;
			   } 
				  
			$row = array();
			$i=0;
			    
			while($res = pg_fetch_row($ret)){
			 	if(!isset($res[0])) continue;
			   		return $res;
			}			
			
		}
		
		//Info di una mappa
		function info_map($id_map){
			
			$db = $this->getdb();
				
		    $sql =  "SELECT * FROM ". DB_TABLE_MAPS ." WHERE id_map=".$id_map;
			$ret = pg_query($db, $sql);
			
		   if(!$ret){
		      echo pg_last_error($db);
		      return false;
		   }
		   
		   while($res = pg_fetch_row($ret)){
			   	if(!isset($res[0])) continue;
			    	return $res;
			}
	
		   pg_close($db);

		}
		
		// avvisa gli utenti di una mappa
		function alert_usermap($telegram, $msg, $map_id, $all = false) {
			
			$db = $this->getdb();
			
			$where = "";
			if (!$all) $where = " AND map=".$map_id;
					
			$sql = "SELECT * FROM ".DB_TABLE_USER ." WHERE alert=true ".$where;
				
			$ret = pg_query($db, $sql);
			if(!$ret){
			   echo pg_last_error($db);
			     exit;
			} 
					  
			$bot_request_message_id=$response["message"]["message_id"];
			$forcehide=$telegram->buildForceReply(true);
			while($res = pg_fetch_row($ret)){
			   	$content = array('chat_id' => $res[0], 'reply_markup' => $forcehide, 'text' => $msg);
				$telegram->sendMessage($content);
			}	
			
			pg_close($db);
				    
		}
		
		// avvisa gli utenti di una mappa
		function get_user($user_id) {
			
			$db = $this->getdb();
			
			$sql = "SELECT * FROM ".DB_TABLE_USER ." WHERE user_id= '".$user_id."'";
				
			$ret = pg_query($db, $sql);
			if(!$ret){
			   echo pg_last_error($db);
			     exit;
			} 
				
			while($res = pg_fetch_row($ret)){
			   	if(!isset($res[0])) continue;
			    	return $res;
			}	  
			
			pg_close($db);
				    
		}

		
		// per modificare lo stato della segnalazione
		function mod_state($telegram, $chat_id, $text, $id_bot_msg) {
			
			$db = $this->getdb();
				
			 $sql =  "SELECT iduser,lat,lng,state, map, umap_id FROM ".DB_TABLE_GEO ." s JOIN ". DB_TABLE_MAPS ." m ON s.map = m.id_map WHERE bot_request_message='".$id_bot_msg."'";
			
				$ret = pg_query($db, $sql);
				   if(!$ret){
				      echo pg_last_error($db);
				      exit;
				   } 
				   
				if (!pg_num_rows($ret)) {
					$reply = "Segnalazione [".$id_bot_msg."] non presente nel sistema.";
				 	$content = array('chat_id' => $chat_id, 'text' => $reply);
					$telegram->sendMessage($content);
					exit;
				}
				  
			    $row = array();
			    $i=0;
			    
			    while($res = pg_fetch_row($ret)){
			    	if(!isset($res[0])) continue;
			    		$row[$i]['iduser'] = $res[0];
			    		$row[$i]['lat'] = $res[1];
			    		$row[$i]['lng'] = $res[2];
			    		$row[$i]['state'] = $res[3];
			    		$row[$i]['map'] = $res[4];
			    		$row[$i]['umap_id'] = $res[5];
			    		
			    		$i++;
			    }
			    
			    $check_temp = true;
			    if (strtoupper($text) == '/C' &&
			        $row[0]['iduser'] != $chat_id &&
			    	($row[0]['state'] == 2 || $row[0]['state'] == 3 || $row[0]['state'] == 5)) {
			    	$check_temp = false;
				}
				else if (($this->check_admin($user_id) || $this->check_user_map($user_id, $row[0]['map']))) {
					$check_temp = false;
				}
				
				if ($check_temp) {
					$reply = "Operazione su segnalazione [".$id_bot_msg."] non consentita.";
				 	$content = array('chat_id' => $chat_id, 'text' => $reply);
					$telegram->sendMessage($content);
					exit;
				}
				
				switch (strtoupper($text)) {
					case 'S':
					case 'SI':
					case 'Y':
					case 'YES':
					case '/A':
						$reply = "Segnalazione [".$id_bot_msg."] approvata.";
						$sql = "UPDATE ".DB_TABLE_GEO ." SET state = 2 WHERE bot_request_message ='".$id_bot_msg."'";
						$umap = $this->get_umap($row[0]['umap_id']);
						$shortUrl = UMAP_URL ."/it/map/".$umap[2]."_".$umap[0]."#".UMAP_ZOOM ."/".$row[0]['lat']."/".$row[0]['lng'];
						//$shortUrl= UMAP_URL ."/m/". $row[0]['umap_id'] ."#". UMAP_ZOOM ."/".$row[0]['lat']."/".$row[0]['lng'];
      					$reply .="\nPuoi visualizzarla su :\n".$shortUrl;
			    
					break;
					
					case 'N':
					case 'NO':
					case '/R':
						$reply = "Segnalazione [".$id_bot_msg."] respinta.";
						$sql = "UPDATE ".DB_TABLE_GEO ." SET state = 3 WHERE bot_request_message ='".$id_bot_msg."'";	
						$umap = $this->get_umap($row[0]['umap_id']);
						$shortUrl = UMAP_URL ."/it/map/".$umap[2]."_".$umap[0]."#".UMAP_ZOOM ."/".$row[0]['lat']."/".$row[0]['lng'];
//						$shortUrl= UMAP_URL ."/m/". $row[0]['umap_id'] ."#". UMAP_ZOOM ."/".$row[0]['lat']."/".$row[0]['lng'];
      					$reply .="\nPuoi visualizzarla su :\n".$shortUrl;
			    			
					break;
					
					case '/S':
						$reply = "Segnalazione [".$id_bot_msg."] sospesa.";
						$sql = "UPDATE ".DB_TABLE_GEO ." SET state = 4 WHERE bot_request_message ='".$id_bot_msg."'";				
					break;
					
					case '/C':
						$reply = "Segnalazione [".$id_bot_msg."] cancellata.";
						$sql = "UPDATE ".DB_TABLE_GEO ." SET state = 5 WHERE bot_request_message ='".$id_bot_msg."'";				
					break;
					
				    
			    }
			    
			    
				
				$ret = pg_query($db, $sql);
				  
   				if(!$ret){
   				   echo pg_last_error($db);
   				   $reply = pg_last_error($db);
   				   exit;
   				} 
				   
			    $content = array('chat_id' => $chat_id, 'text' => $reply);
				$telegram->sendMessage($content);
				$log=$today. ";information for maps change state (".$reply.");" .$chat_id. "\n";	
				file_put_contents(LOG_FILE, $log, FILE_APPEND | LOCK_EX);	
				
			    
				// invio risposta a utente
			    $content = array('chat_id' => $row[0]['iduser'], 'text' => $reply);
				$telegram->sendMessage($content);
				
				
    			pg_close($db);
			
		}
		
		// Remove all special characters from a string
		function clean($string) {
		   $string = str_replace(' ', '-', $string); // Replaces all spaces with hyphens.
		   return preg_replace('/[^A-Za-z0-9\-]/', '', $string); // Removes special chars.
		}
		
		
		
}

?>
