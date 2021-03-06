<?php

/*
 * php-news-page, ver 1.6
 * Copyright (C) 2020, Nicholas Christopoulos (nereus@freemail.gr)
 * LICENSE: GPL v3 or newer
 */

// initialize
setlocale(LC_ALL, "el_GR.UTF8");
error_reporting(E_ERROR | E_PARSE);
define("SEC_PER_DAY", 86400);	// seconds per day
define("MAX_TIME", SEC_PER_DAY * 2); // older post (max: 2 days old)
define("INVALIDATE_CACHE", 60 * 60); // when to refresh cache (every 60mins on user request)
define("MAX_DESC", 750);	// maximum length of description

// --- library ---
function save_cache($data) {
	$encodedString = json_encode($data);			// encode the array into a JSON string.
	file_put_contents('cache.txt', $encodedString);	// save the JSON string to a text file.
	}

function load_cache() {
	$fileContents = file_get_contents('cache.txt');	// retrieve the data from our text file.
	return json_decode($fileContents, true);		// convert the JSON string back into an array.
	}

function valid_cache() {	// returns true if does not need to refresh cache
	if ( file_exists('cache.txt') )
		return (time() - filectime('cache.txt') < INVALIDATE_CACHE);
	return false;
	}

function load_feeds() {	// loads 'feeds.txt' and returns an array with the feeds
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

function pdate_sortcb($a, $b) {	// used to sort news by date
	return $a[0] < $b[0];
	}

function to_date($d) {	// date format to display
	return strftime("%A %d %b %Y, %T %Z", $d);
	}

function badwolf($atext) {	// filtering news; returns true if strings in $atext array does not contain any bad word
	global $badwords;

	foreach ( $atext as $s ) {
		$words = preg_split("/[\s,]+/", $s);
		foreach ( $badwords as $b) {
			if ( in_array($b, $words) )
				return 1;
			}
		}
	return 0;
	}

// --- main ---
$badwords = array();
if ( file_exists("badwords.txt") )
	$badwords = file("badwords.txt", FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
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
			$opts = array('http'=>array('header' => "User-Agent: NDC_RSS_READER/1.6\r\n")); 
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
					else if ( strlen($descr) > MAX_DESC ) {
						$p = strpos($descr, ".", MAX_DESC); // note: strrpos is buggy (7.4.11)
						if ( $p !== false ) {
							$content = substr($descr, $p + 1);
							$descr = substr($descr, 0, $p) . ". [<font color='#007700'><b>&gt;&gt;</b></font>]";
							}
						}
					$imgsrc = "";
					if ( $media = $item->children("media", true) ) {
						if ( $media->content->thumbnail ) {
							$attributes = $media->content->thumbnail->attributes();
							$imgsrc     = (string) $attributes['url'];
							}
						}
					if ( $pdate > $now - MAX_TIME ) {
						if ( !badwolf(array($title, $descr)) ) {
							array_push($data,
								array($pdate, $title, $descr, $imgsrc, $content, $elink, $servname, $serv));
							}
						}
					}
				}
			}
		}
	
	// sort news by date
	usort($data, "pdate_sortcb");
	
	// store to avoid refreshing every time
	save_cache($data);
	}

// --- print ---
$msg_to_site = "Μεταφορά στο site...";		// text: jump to the news-site
$msg_more_text = "Εμφάνιση όλου του κειμένου";	// text: view additional contents
$source_text = "Πηγή";							// text: the word 'source'
$btn_gotosite = "...";		// button text, go-to-site
$btn_viewcontent = "⇵";		// button text, view the rest description
$id = 1;	// just a unique ID per article
foreach ( $data as $src ) {
	list($pdate, $title, $descr, $imgsrc, $content, $elink, $servname, $serv) = $src;
	echo "<div class='news-item'>\n";
	echo "\t<h2>", $title, "</h2>\n";
	echo "\t<div class='news-pdate'>", to_date($pdate), "</div>\n<p>\n";
	echo "\t<div class='news-text'>\n", $descr, "\n\t</div>\n";
	if ( strlen($imgsrc) )
		echo "\t<div><img src='", $imgsrc, "' alt='' style='width:95%;'/></div>\n";
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
