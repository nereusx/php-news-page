# php-news-page
RSS Reader in PHP. This is a 300 lines (including HTML and CSS) small PHP web page that shows the contents of the RSS feeds as described in `feeds.txt` text file.
This is a web-page without per user options.

* Tiny size
* Feeds in plain text file
* Filters on words of plain text file
* Cache

## Feeds
The feeds are stored in `feeds.txt` file, in the form:
```
# comment
title1 ; url1
...
titleN ; urlN
```

## Filters
The bad-words are stored in `badwords.txt` file, one word per line.
If title or description has any bad word then it is removed from the page.
This is how I avoid sports and life-style news.

# demo
https://nicholas-christopoulos.dev/news/

# screenshot
![#1](https://raw.githubusercontent.com/nereusx/php-news-page/main/screenshots/ss-news-1.png)
