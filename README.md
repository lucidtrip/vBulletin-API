# vBulletin API

API Class to post threads and replys in your vBulletin Board

## exampels
```php
include "vbulletin.api.php";
$vb = new vbulletin;
$vb->login(URL, USER, PASS);
$vb->new_thread(ID, TITEL, TEXT);
$vb->postreply(ID, TITEL, TEXT);
```

## what need
- php5
- phpcurl

## infos
```php
$vb->login(URL, USER, PASS);
```
if login ok than returns `True`. If login fails than return an array with `code` => `false` and `text` with a little error description

```php
$vb->new_thread(ID, TITEL, TEXT);
```
thats return an array with `code` and `text`. `code` is `true` than is `text` the URL to the thread. If `code` a `false` than is `text` an error description
and make a `vbulletin_error_log.html` file with the returns html code from the vBulletin.

```php
$vb->postreply(ID, TITEL, TEXT);
```
works as previously mentioned

have fun!

## support
* autor: bebop
* date: 25.11.2013
* last update: 03.12.2013
* bitcoin: 1H7YZLC1TzeA6qQVFHLtMMmDx8GsxLucid

