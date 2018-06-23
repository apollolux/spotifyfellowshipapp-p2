<?php
/****
luxcal.php
by Alex Rosario
calendar backend for Spotify Tech Fellowship application
****/

function param($p, $def="") {
	//global $_SERVER, $_SESSION, $_COOKIE, $_REQUEST, $_POST, $_GET;
	if (!empty($_SESSION)&&isset($_SESSION[$p])) return $_SESSION[$p];
	else if (isset($_COOKIE[$p])) return $_COOKIE[$p];
	else if (isset($_REQUEST[$p])) return $_REQUEST[$p];
	else if (isset($_POST[$p])) return $_POST[$p];
	else if (isset($_GET[$p])) return $_GET[$p];
	else return $def;
}


/****
BACK END
Build the backend of the calendar application. The API for the calendar should be the following:

Events (Minimum Required API)
●     POST /events
       ○     Should create an event

●     GET /events
        ○     Should return all events

Events (Optional API. Not required; bonus points available)
●     DELETE /events/:id
       ○     Should delete an event
●     PUT /events/:id
       ○     Should update an existing event
****/
/****
backend checklist
+	POST /events
+	GET /events
	DELETE /events/:id
	PUT /events/:id
****/
/****
event format json

{
	"user":[
		{
			"id":1,
			"name":"alex"
		}
	],
	"events":[
		{
			"id":1,
			"uid":1,
			"date":"2018-06-28",
			"time":{
				"start":"14:30",
				"end":"23:59"
			},
			"description":"This is a sample event!"
		}
	]
}

****/

function lux_find_last_event($id, $v) {
	if ($v->id>$id) $id = $v->id;
	return $id;
}


$fn_cal = "luxcal.json";
$lux_evts = null;

$evt_default = array(
	"user"=>array(),
	"events"=>array()
);

$method = strtolower($_SERVER['REQUEST_METHOD']);
$req = $_SERVER['REQUEST_URI'];
$gate = param('act',null);
$req_end = substr($req, strrpos($req, '/'));
//var_dump(stat($fn_cal)); exit;
if ("post"===$method) {	// Should create an event
	// TODO: create new event, set id to its id
	if ("events"===$gate) {
		$id = param('id', -1)+0;
		$uid = param('uid', -1)+0;
		$evt_date = param('evtdate', null);
		$evt_start = param('evtstart', null);
		$evt_end = param('evtend', null);
		$evt_desc = param('evtdesc', "");
		if (0>$id) die('{"status":1,"message":"Missing id"}');
		if (0>$uid) die('{"status":1,"message":"Missing user"}');
		else if ($evt_date&&$evt_start&&$evt_end) {
			$txt = file_get_contents($fn_cal);
			$lux_evts = json_decode($txt);
			if ($lux_evts) {
				if (1>$id) {
					$last_id = array_reduce($lux_evts->events, "lux_find_last_event", -1);
					if (-1<$last_id) $id = 1+$last_id;
					else $id = count($lux_evts->events);
				}
				//die('{"id":'.$id.'}');
				$lux_evts->events[] = array(
					"id"=>$id,
					"uid"=>$uid,
					"date"=>$evt_date,
					"time"=>array(
						"start"=>$evt_start,
						"end"=>$evt_end
					),
					"description"=>$evt_desc
				);
				// TODO: save json
				$json_out = json_encode($lux_evts);
				if (file_exists($fn_cal)&&!is_writable($fn_cal)) {
					rename($fn_cal, 'luxcal.'.date("Ymd.His").'.json');
				}
				/*$fp = fopen($fn_cal, "w");
				if ($fp) {
					fwrite($fp, $json_out);
					fclose($fp);
					// TODO: return status ok json
					echo '{"status":0}';
					exit;
				}*/
				if (false!==file_put_contents($fn_cal, $json_out)) {
					// TODO: return status ok json
					echo '{"status":0,"message":"ok"}';
					exit;
				}
				else {
					printf(
						'{"status":1,"message":"Error: unable to write event file"}'
					);
					exit;
				}
			}
			else {
				printf(
					"Error: unable to read event file: %d - %s\n",
					json_last_error(),
					json_last_error_msg()
				);
			}
		}
	}
	else {
		//print_r($_POST);
		echo("Unsupported gateway '{$gate}'\n");
	}
}
else if ("get"===$method) {	// Should return all events
	//print_r($_GET);
	$txt = "";
	$txt = file_get_contents($fn_cal);
	$lux_evts = json_decode($txt);
	if ("events"===$gate) {
		if ($lux_evts) {
			$n = count($lux_evts->events);
			echo(json_encode($lux_evts->events));
			exit;
			//echo ("{$n} event(s)\n");
			//var_dump($lux_evts);
			//for ($i=0; $i<$n; ++$i) {
			//	print_r($lux_evts->events[$i]);
			//}
		}
		else {
			printf(
				"Error: unable to read event file: %d - %s\n",
				json_last_error(),
				json_last_error_msg()
			);
		}
		// TODO: get all events
		//die("get");
	}
	else if ("users"===$gate) {
		echo(json_encode($lux_evts->user));
		exit;
		//echo("TODO: get user(s) '{$gate}'\n");
	}
	else {
		echo("Unsupported gateway '{$gate}'\n");
	}
}
else if ("put"===$method) {
	$arr = file_get_contents('php://input');
	print_r($_POST);
	print_r($_GET);
	print_r($arr);
	$id = param("id", -1);
	echo("PUT '{$arr}'!\n");
	if (0>$id) {
		echo("Error: unable to acquire id to put\n");
	}
	else {
		echo("Msg: attempting to put id '{$id}' to update\n");
	}
	//die("put");
}
else if ("delete"===$method) {	// Should delete an event
	print_r($_POST);
	print_r($_GET);
	$id = param("id", -1);
	echo("DELETE '{$id}'!\n");
	if (0>$id) {
		echo("Error: unable to acquire id to delete\n");
	}
	else {
		echo("Msg: attempting to delete id '{$id}'\n");
	}
	//die("delete");
}
else {
	die("unsupported request method '{$method}'");
}
die("{$method} : {$req_end} ({$req})");

?>