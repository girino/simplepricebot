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
function discord($msg) {
	global $webhookurl;
	$url=$webhookurl;
	$data=array('content'=>$msg);
	$options=array('http'=>array('method'=>'POST','header'=>"Content-Type:application/x-www-form-urlencoded\r\n",'content'=>http_build_query($data),),);
	$context=stream_context_create($options);
	$result=file_get_contents($url,false,$context);
	// prints to stdout too
	print($msg . "\n");
	return $result;
}

// send message to discord
function telegram($msg) {
	global $telegrambot,$telegramchatid;
	$url='https://api.telegram.org/bot'.$telegrambot.'/sendMessage';
	$data=array('chat_id'=>$telegramchatid,'text'=>$msg,'parse_mode'=>'markdown');
	$options=array('http'=>array('method'=>'POST','header'=>"Content-Type:application/x-www-form-urlencoded\r\n",'content'=>http_build_query($data),),);
	$context=stream_context_create($options);
	$result=file_get_contents($url,false,$context);
	// prints to stdout too
	print($msg . "\n");
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

function getTicker($ticker, $curr, $base) {
	if (array_key_exists($curr.$base, $ticker)) {
		return $ticker[$curr.$base];
	} else if (array_key_exists($base.$curr, $ticker)) {
		return 1.0 / $ticker[$base.$curr];
	}
	return false;
}

function checkdelta($new, $old, $delta) {
	if ( abs($new - $old) > ($old*$delta) ) {
		return true;
	}
	return false;
}

$states = array();
$oldprices = array();
while (true) {

	// reread the include file
	include 'config.php';

	$ticker = ticker();
	// getting usernames not implemented for discord yet
	if (!$usediscord) {
		$member = getChatMember();
		if (property_exists($member, "user") && property_exists($member->user, "username")) {
			$username = $member->user->username;
		} else {
			$username = "anonymous";
		}
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
	}


	$keys = array_map(function ($m) { return $m[0].$m[1]; }, $list_tickers);
	$prices = array_map(function ($m) use ($ticker) { return getTicker($ticker, $m[0], $m[1]); }, $list_tickers);
	$oldprices_a = array_map(function ($key, $price) use ($oldprices, $tickers_delta) { return array_key_exists($key, $oldprices) ? $oldprices[$key] : $price * (2.0 + 2.0*$tickers_delta); }, $keys, $prices);
	$oldprices = array_combine($keys, $oldprices_a);
	$changes = array_map(function ($new, $old) use ($tickers_delta) { return checkdelta($new, $old, $tickers_delta); }, $prices, $oldprices_a);
	$has_changes = in_array(true, $changes);
	if ($has_changes) {
		$out_a = array_map(function ($m, $p) { return ($p) 
													? "Cotação do " . $m[0] . " = ". $p . " " . $m[1] 
													: "Markets for " . $m[0] . " and " . $m[1] . " not found"; 
												}, $list_tickers, $prices);
		$out = implode("\n", $out_a);
		$oldprices = array_combine($keys, $prices);
		if ($usediscord) {
			discord($out);
		} else {
			telegram($out);
		}
	}
	print("==============================\n");
	sleep($sleep_time);
}
?>
