<?php

/*
 * php-news-page, ver 1.4
 * Copyright (C) 2020, Nicholas Christopoulos (nereus@freemail.gr)
 * LICENSE: GPL v3 or newer
 */

// initialize
setlocale(LC_ALL, "el_GR.UTF8");
error_reporting(E_ERROR | E_PARSE);
define("SEC_PER_DAY", 86400);	// seconds per day
define("MAX_TIME", SEC_PER_DAY * 2); // older post (max: 2 days old)
define("INVALIDATE_CACHE", 60 * 30); // when to refresh cache (every 30mins on user request)

// --- library ---
function save_cache($data) {
	$encodedString = json_encode($data);			// encode the array into a JSON string.
	file_put_contents('cache.txt', $encodedString);	// save the JSON string to a text file.
	}

function load_cache() {
	$fileContents = file_get_contents('cache.txt');	// retrieve the data from our text file.
	return json_decode($fileContents, true);		// convert the JSON string back into an array.
	}

function valid_cache() {
	if ( file_exists('cache.txt') )
		return (time() - filectime('cache.txt') < INVALIDATE_CACHE);
	return false;
	}

function load_feeds() {
	$feeds = array();
	$f = fopen("feeds.txt", "r") or die("Unable to open feeds file!");
	while ( !feof($f) ) {
		$s = trim(fgets($f));
		if ( strlen($s) && $s[0] != '#' ) {
			list($n, $u) = explode(";", $s);
			$n = trim($n); $u = trim($u);
			array_push($feeds, array($n, $u));
			}
		}
	fclose($f);
	return $feeds;
	}

function pdate_sortcb($a, $b) {
	return $a[0] < $b[0];
	}

function to_date($d) {
	return strftime("%A %d %b %Y, %T %Z", $d);
	}

// --- main ---

$feeds = load_feeds();
$data = array();
if ( valid_cache() ) // too soon ?
	$data = load_cache();
else {
	// read feeds and store to $data
	$now = time();
	foreach ( $feeds as $src ) {
		list($servname, $serv) = $src;
		if ( strlen($serv) ) {
			$opts = array('http'=>array('header' => "User-Agent: NDC_RSS_READER/1.4\r\n")); 
			$context = stream_context_create($opts);
			$html = file_get_contents($serv, false, $context);
			$feed = simplexml_load_string($html);
		
			if ( $feed === FALSE ) {
				// handle error here
				continue;
				}
			else {
				foreach ( $feed->channel->item as $item ) {
					$title = trim((string) $item->title);
					$descr = trim((string) $item->description);
					$elink = (string) $item->link;
					$pdate = strtotime((string) $item->pubDate);
					$guid  = (string) $item->guid;
					$content = "";
					if ( $e_content = $item->children("content", true) )
						$content = (string) $e_content->encoded; 
					$imgsrc = "";
					if ( $media = $item->children("media", true) ) {
						if ( $media->content->thumbnail ) {
							$attributes = $media->content->thumbnail->attributes();
							$imgsrc     = (string) $attributes['url'];
							}
						}
					if ( $pdate > $now - MAX_TIME )
						array_push($data,
							array($pdate, $title, $descr, $imgsrc, $content, $elink, $servname, $serv));
					}
				}
			}
		}
	
	// sort news by date
	usort($data, "pdate_sortcb");
	
	// store to avoid refreshing every time
	save_cache($data);
	}

//
//	print the news
//
$msg_to_site = "Μεταφορά στο site...";		// jump to the site
$msg_more_text = "Εμφάνιση όλου του κειμένου";	// view additional contents
$source_text = "Πηγή";							// the word 'source'
$btn_gotosite = "...";
$btn_viewcontent = "⇵";
$id = 1;
foreach ( $data as $src ) {
	list($pdate, $title, $descr, $imgsrc, $content, $elink, $servname, $serv) = $src;
	echo "<div class='news-item'>\n";
	echo "\t<h2>", $title, "</h2>\n";
	echo "\t<div class='news-pdate'>", to_date($pdate), "</div>\n<p>\n";
	echo "\t<div class='news-text'>\n", $descr, "\n\t</div>\n";
	if ( strlen($imgsrc) )
		echo "\t<div><img src='", $imgsrc, "' alt=''/></div>\n";
	if ( strlen($content) )
		echo "\t<div hidden id='news-content-", $id, "' class='news-content'>\n", $content, "\n\t</div>\n";
	echo "\t<div class='news-footer'>\n";
	echo "\t<table width='100%'>\n";
	echo "\t\t<tr><td><b>", $source_text, ":</b> ", $servname, "\n";
	echo "\t\t<td align='right'>\n";
	if ( strlen($content) )
		echo "\t\t<button title='", $msg_more_text, "' onclick='showExtraContent(", $id ,")'>", $btn_viewcontent, "</button>\n";
	echo "\t\t<button title='", $msg_to_site, "' onclick=\"window.open('", $elink, "','_blank');\">", $btn_gotosite, "</button>\n";
	echo "\t</table>\n\t</div>\n";
	echo "</div>\n";
	$id = $id + 1;
	}
?>
