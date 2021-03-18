<?php

/**
 * Copyright 2021 Girino Vey!
 * 
 * This software is distributed as is, with no warranties or liabilities. Use at your own risk
 * License: https://license.girino.org
 */

include 'config.php';

// Telegram function which you can call
function getChatMember() {
	global $telegrambot,$telegramchatid,$userid;
	$url='https://api.telegram.org/bot'.$telegrambot.'/getChatMember';
	$data=array('chat_id'=>$telegramchatid,'user_id'=>$userid);
	$options=array('http'=>array('method'=>'POST','header'=>"Content-Type:application/x-www-form-urlencoded\r\n",'content'=>http_build_query($data),),);
	$context=stream_context_create($options);
	$result=file_get_contents($url,false,$context);
	$result=json_decode($result);
	return ($result->ok) ? $result->result : False;
}


// Telegram function which you can call
function telegram($msg) {
        global $telegrambot,$telegramchatid;
        $url='https://api.telegram.org/bot'.$telegrambot.'/sendMessage';
		$data=array('chat_id'=>$telegramchatid,'text'=>$msg,'parse_mode'=>'markdown');
        $options=array('http'=>array('method'=>'POST','header'=>"Content-Type:application/x-www-form-urlencoded\r\n",'content'=>http_build_query($data),),);
        $context=stream_context_create($options);
        $result=file_get_contents($url,false,$context);
        return $result;
}

function ticker() {
	$url = 'https://api.binance.com/api/v1/ticker/price';
	$options=array('http'=>array('method'=>'GET','header'=>"Content-Type:application/x-www-form-urlencoded\r\n"));
	$context=stream_context_create($options);
	$result=file_get_contents($url,false,$context);
	$decoded = json_decode($result);
	$ret = array();
	foreach($decoded as $t) {
		$ret[$t->symbol] = $t->price;
	}
	return $ret;
}

function cotacao($ticker, $curr, $base) {
	if (array_key_exists($curr.$base, $ticker)) {
		$v = $ticker[$curr.$base];
	} else if (array_key_exists($base.$curr, $ticker)) {
		$v = 1.0 / $ticker[$base.$curr];
	} else {
		$v = "Err not found";
	}

	return "Cotação do " . $curr . " = ". $v . " " . $base;
}

$member = getChatMember();
if (property_exists($member, "user") && property_exists($member->user, "username")) {
	$username = $member->user->username;
} else {
	$username = "anonymous";
}
$states = array();
while (true) {

	// reread the include file
	include 'config.php';

	$ticker = ticker();
	foreach ($watch_values as $watch_tuple) {
		$watch_ticker = $watch_tuple[0].$watch_tuple[1];
		$watch_value = $watch_tuple[2];
		$key = $watch_ticker.$watch_value;
		if (!array_key_exists($key, $states)) {
			$states[$key] = ($ticker[$watch_ticker] < $watch_value) ? "UNDER" : "OVER";
		} elseif ($states[$key] == "UNDER" && $ticker[$watch_ticker] >= $watch_value) {
			telegram("[".$username."](tg://user?id=". $userid .") *Cotação $watch_ticker acima de " . $watch_value . " " . $watch_tuple[1] . "*");
			$states[$key] = "OVER";
		} elseif ($states[$key] != "UNDER" && $ticker[$watch_ticker] < $watch_value) {
			telegram("[".$username."](tg://user?id=". $userid .") *Cotação $watch_ticker abaixo de " . $watch_value . " " . $watch_tuple[1] . "*");
			$states[$key] = "UNDER";
		}
	}

	$out = implode("\n", array_map(function ($m) use ($ticker) {	return cotacao($ticker, $m[0], $m[1]);	}, $list_tickers));
	telegram($out);
	print($out . "\n\n");
	sleep($sleep_time);
}
?>
