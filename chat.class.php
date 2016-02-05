<?php
require_once('config.php');
require_once('george_error_handler.php');

class Chat
{

	private $mysqli;

	//constructor open database connection
	function __construct(){
		$this->mysqli = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_DATABASE);
	}

	//destructor closes database connection
	function __destruct(){
		$this->mysqli->close();
	}

	//Truncates (empties) the table containing all messages
	public function deleteAllMessages(){
		$query = 'TRUNCATE TABLE chat';
		$result = $this->mysqli->query($query);

	}

	public function postNewMessage($user_name, $message, $color){
		$user_name = $this->mysqli->real_escape_string($user_name);
		$message = $this->mysqli->real_escape_string($message);
		$color = $this->mysqli->real_escape_string($color);
		$query = 'INSERT INTO chat (posted_on, user_name, message, color)' .
		' VALUES (
		NOW(),
		"' . $user_name . '",
		"' . $message . '",
		"' . $color . '" ) ';
		$result = $this->mysqli->query($query);
	}

	//Get new messages
	public function getNewMessages($id=0){
		$id = $this->mysqli->real_escape_string($id);
		if($id>0){
			$query = 
			'
			SELECT message_id, user_name, message, color, DATE_FORMAT(posted_on, "%H:%i:%s")
			AS posted_on FROM chat WHERE message_id > '
			. $id .

			'ORDER BY message_id ASC ';
		}else{
			$query = 
			'
			SELECT message_id, user_name, message, color, posted_on
			FROM (SELECT message_id, user_name, message, color, DATE_FORMAT(posted_on, "%H:%i:%s")
			AS posted_on FROM chat ORDER BY message_id DESC LIMIT 50) AS Last50
			ORDER BY message_id ASC ';
		}

		$result = $this->mysqli->query($query);

		//XML response
		$response = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>';
		$response .= '<response>';
		$response .= $this->isDatabaseCleared($id);

		if($result->num_rows){
			while($row = $result->fetch_array(MYSQLI_ASSOC)){
				$id = $row['message_id'];
				$color = $row['color'];
				$userName = $row['user_name'];
				$time = $row['posted_on'];
				$message = $row['message'];
				$response .= '<id>' . $id . '</id>'.
							'<color>' . $color . '</color>'.
							'<time>' . $time . '</time>'.
							'<name>' . $userName . '</name>'.
							'<message>' . $message . '</message>';
			}
			$result->close();
		}

		$response .= '</response>';
		return $response;
	}


	private function isDatabaseCleared($id){
		if($id>0){
			$check_clear = 'SELECT count(*) old FROM chat WHERE message_id<=' .$id;
			$result = $this->mysqli->query($check_clear);
			$row = $result->fetch_array(MYSQLI_ASSOC);
			if($row['old']==0)
				return '<clear>true</clear>';
		}
		return '<clear>false</clear>';
	}




}

?>