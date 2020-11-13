<?php

/*
 * php-news-page, ver 1.5
 * Copyright (C) 2020, Nicholas Christopoulos (nereus@freemail.gr)
 * LICENSE: GPL v3 or newer
 */

// initialize
setlocale(LC_ALL, "el_GR.UTF8");
error_reporting(E_ERROR | E_PARSE);
define("INVALIDATE_CACHE", 60 * 30); // when to refresh cache (every 30mins on user request)

// --- library ---
function valid_cache() {
	if ( file_exists('cache.txt') )
		return (time() - filectime('cache.txt') < INVALIDATE_CACHE);
	return false;
	}

// --- main ---
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
		font-family: Roboto, sans-serif;
		font-size: 90%;
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
		font-family: Roboto, sans-serif;
		font-size: 0.8rem;
		color: #666666;
		}
	a.news-more {
		font-family: Roboto, sans-serif;
		font-weight: 900;
		}
	a:link, a:active, a:visited { color: blue; }
	a.news-more:link, a.news-more:active, a.news-more:visited { color: #cc6600; }
	div.news-content { font-size: 1.0rem; border: 1px solid black; padding: 1rem; }
	button { font-family: Roboto, sans-serif; }
	div.wait-box {
		margin: 5%;
		border: 6px solid black;
		padding: 2rem;
		font-size: 200%;
		text-align: center;
		}
	div.wait-box h5 { font-size: 9pt; }
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
		
	function getcurdir() {
		var location = window.location.href;
		return location.substring(0, location.lastIndexOf("/")+1);
		}
	
	function showExtraContent(id) {
		$('#news-content-'+id).toggle(); 
		}
	</script>	  
</head>
		  
<body lang="el" class="hyp">
<script src="/lib/Hyphenopoly_Loader.js"></script> <!-- load & enable hyphenation -->
<header>
	<h1>NDC RSS READER</h1>
</header>
<div id='wait-box' class='wait-box'>
	Loading. Please wait...
	<h5>Time is depended on building cache or not and how many feeds are defined</h5>
</div>
<a name="top"></a>
<div id='news' class='news'></div>  
<footer>
	<table width="100%">
	<tr><td>Copyleft (c) 2020, <a href="mailto:nereus@freemail.gr">Nicholas Christopoulos</a>
	<td align=right>
		  <a href="#top">[top]</a>
		  <a href="/">[root]</a>
	<tr><td>php-news-page Version 1.5 - License GPL v3+
	<td align=right><a href='https://github.com/nereusx/php-news-page'><b>Source code @github</b></a>
	</table>
</footer>
	  
<script type="text/javascript">
$(document).ready(function() {
	var xrss = $.ajax(getcurdir()+'rss.php')
		.done(function(html) {
			$('#wait-box').hide();
			$('#news').html(html);
			})
		.fail(function() {
			$('#wait-box').text('An error occurred');
			});
	});
</script>
</body>
</html>

EOT;
?>
