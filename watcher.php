<?php
/*
watcher -- check if a site is up, and run rescue actions otherwise

Copyright 2014 Instituto Nacional de Investigación y Tecnología Agraria y Alimentaria

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program. If not, see <http://www.gnu.org/licenses/>.
*/

/**
 * Return a curl header checker
 *
 * The header checker is a function that calls a function if the header match
 * some value.
 * The header checker built by this function is valid to receive a 
 * CURLOPT_HEADERFUNCTION callback.
 *
 * @param $headerValue	[string] the value of the header ('200', '404', ...)
 * @param $function	[callable] the function triggered by the headerValue
 *
 * @return		[callable] a function with params($ch and $header)
 */
function getChecker($headerValue, $function)
{
    return function($ch, $header) use ($headerValue, $function) {
        if (strpos($header, $headerValue)) {
            call_user_func($function);
        }
    };
}

/**
 * Return an email sender, useful for callback 
 */
function getNotifier($notifyTo, $title, $message)
{
    return function() use ($notifyTo, $title, $message) {
        mail($notifyTo, $title, $message);
    };
}

/**
 * Set a CURL connection to a site and launch a function if the HTTP header
 * matches some value.
 *
 * @param $site		[string] an URL
 * @param $headerValue	[string] an HTTP header value ('200', '400', ...)
 * @param $function	[callable] the function triggered by $headerValue
 */
function checkHeader($site, $headerValue, $function)
{
    $ch = curl_init($site);
    curl_setopt(
        $ch,
        CURLOPT_HEADERFUNCTION,
        getChecker($headerValue, $function)
    );
    curl_exec($ch);
    curl_close($ch);
}

/**
 * Build a 'checkHeader' function with fixed params.
 *
 * Poor-man currying to pass a checkHeader as callback for another checkHeader
 *
 * @param $site		[string] an URL
 * @param $headerValue	[string] an HTTP header value ('200', '400', ...)
 * @param $function	[callable] the function triggered by $headerValue
 */
function factoryCheckHeader($site, $error, $function)
{
    return function() use ($site, $error, $function) {
        checkHeader($site, $error, $function);
    };
}

$site = $argv[1];
$rescueSite = $argv[2];
$notifyTo = $argv[3];
$title = gettext("Site %s is down");
$content = gettext("Site %s seems to be down.\nSITEWATCHER tried to recover it using %s; but running the rescuer didn't solve the issue.");
$messageTitle = sprintf($title, $site);
$messageContent = sprintf($content, $site, $rescueSite);

checkHeader(
    $site, 
    '500',
    factoryCheckHeader(
        $rescueSite,
        '500',
        getNotifier($notifyTo, $messageTitle, $messageContent)
    )
);
?>
