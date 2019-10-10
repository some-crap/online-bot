<?php
	ini_set("log_errors", 1);
	ini_set("error_log", "logsbot.txt");
	$link= new mysqli('HOST', 'U_NAME', 'PASS', 'DBNAME');
	$confirmation_token = ''; 
	function vk_msg_send($peer_id,$text,$keyboard=null)
	{
			if(is_null($keyboard))
			{
					$request_params = array(
					'message' => $text, 
					'peer_id' => $peer_id, 
					'access_token' => "",
					'v' => '5.87' 
					);
			}
			else
			{
					$request_params = array(
					'message' => $text, 
			        'peer_id' => $peer_id, 
		        	'keyboard' => $keyboard,
					'access_token' => "",
					'v' => '5.87' 
					);
			}
			$get_params = http_build_query($request_params); 
			file_get_contents('https://api.vk.com/method/messages.send?'. $get_params);
	}
	$data = json_decode(file_get_contents('php://input'));
	switch ($data->type) {  
		case 'confirmation': 
		echo $confirmation_token; 
		break; 
		
		
		case 'message_new': 
		echo 'ok';
		$peer_id = $data->object->peer_id;  
		$from_id = $data->object->from_id;
		$msg = $data->object->text;
		$from_id = mysqli_real_escape_string($link, $from_id);
		$db = mysqli_query($link, "SELECT * FROM `users` WHERE `uid` = '".$from_id."'");
		if(mysqli_num_rows($db) == 0){
		    mysqli_query($link, "INSERT INTO `users` SET `uid` = '".$from_id."', `hidden` = '0'");
		    vk_msg_send($peer_id, "/token - получить ссылку для получения токена \n/offline - скрыть онлайн");
		    exit();
		}
		else{
		    $db = mysqli_fetch_array($db);
		    if($db['user_condition'] == "offline_token"){
		        $token = explode("access_token=",$msg);
		        $token = explode("&", $token[1]);
		        $token = $token[0];
		        $ans = json_decode(file_get_contents("https://api.vk.me/method/account.setPrivacy?v=5.109&key=online&value=only_me&access_token=".$token));
		        mysqli_query($link, "UPDATE `users` SET `user_condition` = 'ready', `hidden` = '1' WHERE `uid` = '".$from_id."'");
		        if($ans -> response -> category == "only_me"){
		        vk_msg_send($peer_id, "Усё. Твой онлайн скрыт. Обратно показать мы, увы, не можем.");
		        exit();
		        }
		        else{
		            vk_msg_send($peer_id, "Чёт пошло не так. Попробуй получить токен снова.");
		            exit();
		        }
		    }
		    if($msg == "/token"){
		        vk_msg_send($peer_id, "Вот тебе ссылка для получения токена: https://oauth.vk.com/authorize?client_id=2685278&scope=1073737727&redirect_uri=https://oauth.vk.com/blank.html&display=page&response_type=token&revoke=1 \n Когда будешь менять свой онлайн - отправь мне содержимое адресной строки..");
		        exit();
		    }
		    if($msg == "/offline"){
		         mysqli_query($link, "UPDATE `users` SET `user_condition` = 'offline_token' WHERE `uid` = '".$from_id."'");
		         vk_msg_send($peer_id, "Ты получил токен этой командой?\n/token\nТеперь отправь мне эту строку!");
		         exit();
		    }
		}
		break; 
	}
?>
