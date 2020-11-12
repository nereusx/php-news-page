<?php

// initialize
setlocale(LC_ALL, "el_GR.UTF8");
error_reporting(E_ERROR | E_PARSE);
define("SEC_PER_DAY", 86400);	// seconds per day
define("MAX_TIME", SEC_PER_DAY * 2); // older post (max: 2 days old)
define("INVALIDATE_CACHE", 60 * 30); // when to refresh cache (every 30mins on user request)
//$cookie_name = "sources";
//$cookie_value = "1";
//setcookie($cookie_name, $cookie_value, time() + (SEC_PER_DAY * 365), "/news");
$feeds = load_feeds();
//if(!isset($_COOKIE[$cookie_name])) {
//	  echo "Cookie named '" . $cookie_name . "' is not set!";
//} else {
//	  echo "Cookie '" . $cookie_name . "' is set!<br>";
//	  echo "Value is: " . $_COOKIE[$cookie_name];
//}

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
$msg_wait = "Loading, please wait...";
if ( !valid_cache() )
	$msg_wait = "Rebuilding cache, please wait...";

//
//	start writting HTML
//
echo <<<'EOT'
<!DOCTYPE html>
<html>
	<head>
  	<meta charset="UTF-8">
  	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	<meta name="keywords" content="Ειδήσεις, RSS, php-news-page">
  	<meta name="author" content="Νικόλας Χριστόπουλος">
  	<meta name="description" content="RSS Reader">
  	<meta name="copyright" content="Copyright© 2000-2020, Nicholas Christopoulos.">
  	<meta name="license" content="GNU FDL (https://www.gnu.org/licenses/fdl.html)">
	<meta name="viewport" content="width=device-width" />

  	<title>NDC RSS READER</title>
  
	<link href="https://fonts.googleapis.com/css2?family=Roboto+Slab:wght@400;700;900&family=Roboto:ital,wght@0,400;0,900;1,700&display=swap" rel="stylesheet">
	<link href="/fonts/fonts.css" rel="stylesheet">
  
  	<style>
  	header {
		font-family: Roboto Slab, serif;
		font-weight: 900;
		text-align: center;
		border-top: 6px solid black;
		border-bottom: 6px solid black;
		margin-bottom: 1rem;
		}
  	header h1 {
		font-family: Roboto Slab, serif;
		font-weight: 900;
		}
	@media screen and (min-width: 40rem) { header { font-size: 120%; } }

	footer {
		font-family: Verdana, Roboto, sans-serif;
		margin-top: 1rem;
		border-top: 6px solid black;
		}
	div.news {
		font-family: Roboto, sans-serif;
		column-count: 1;
		column-gap: 40px;
		column-rule: 4px double #ff00ff;
		}
	@media screen and (min-width:   40rem) { div.news { column-count: 2; } }
	@media screen and (min-width:   80rem) { div.news { column-count: 3; } }
	div.news-pdate {
		font-family: Verdana, sans-serif;
		font-size: 0.8rem;
		color: #666666;
		}
	a.news-more {
		font-family: Verdana, sans-serif;
		font-weight: 900;
		}
	a.news-more:link, a.news-more:active, a.news-more:visited { color: #cc6600; }
	div.news-content { font-size: 1.0rem; border: 1px solid black; padding: 1rem; }
	button { font-family: Verdana, Roboto, sans-serif; }
	div.wait-msg {
		border: 6px solid black;
		padding: 2rem;
		text-align: center;
		}
	</style>

	<script src="/lib/jquery.min.js"></script>

	<script type="text/javascript">
	  // hyphenation
	  var Hyphenopoly = {
		  require: {
			  "el": "FORCEHYPHENOPOLY",
				"en": "manipulations",
				"en-us": "manipulations"
		  },
		  paths: {
			  patterndir: "/lib/patterns/",
				maindir:    "/lib/"
		  },
		  
		  setup: {
			  timeout: 5000,
				classnames: {
					"hyp": {}
				}
		  }
	  };
	
	function showExtraContent(id) {
		$('#news-content-'+id).toggle();
		}
		
	</script>
		  
	</head>
		  
<body lang="el" class="hyp">

<!-- Message: Please wait -->

EOT;

echo "<div class=\"wait-msg\" id=\"wait-msg\"><h1>", $msg_wait, "</h1></div>\n";
		  
echo <<<'EOT'
<!-- load & enable hyphenation -->
<script src="/lib/Hyphenopoly_Loader.js"></script>

<!-- the real body begins here -->
<div id="body" style="display:none;">
<header>
<h1>NDC RSS READER</h1>
</header>

<a name="top"></a>

EOT;

//
//	build the news array
//
$data = array();
if ( valid_cache() ) // too soon ?
	$data = load_cache();
else {
	// read feeds and store to $data
	$now = time();
	foreach ( $feeds as $src ) {
		list($servname, $serv) = $src;
		if ( strlen($serv) ) {
			$feed = simplexml_load_file($serv);
		
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
echo "<div class='news'>\n";
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
echo "</div>\n";

//
//	close html
//
echo <<<'EOT'
	<footer>
	<table width="100%">
	<tr><td>Copyleft (c) 2020, <a href="mailto:nereus@freemail.gr">Nicholas Christopoulos</a>
	<td align=right>
		  <a href="#top">[top]</a>
		  <a href="/">[root]</a>
	<tr><td>php-news-page Version 1.2 - License GPL v3+
	<td align=right><a href='https://github.com/nereusx/php-news-page'><b>Source code @github</b></a>
	</table>
	</footer>

</div> <!-- body -->
<script type="text/javascript">
	$(document).ready(function() {
		$('#body').show();
		$('#wait-msg').hide();
		});
</script>
</body>
</html>

EOT;
?>

