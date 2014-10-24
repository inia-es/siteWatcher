<?php
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
        call_user_func('checkHeader', $site, $error, $function);
    };
}

var_dump ($argv);
$site = $argv[1];
$rescueSite = $argv[2];
$notifyTo = $argv[3];
$messageTitle = 'Sitio de revistas caído'; 
$messageContent = 'El sitio de revistas INIA parece estar caído. 
            He intentado limpiar la caché pero la web no se recupera.';
checkHeader(
    $site, 
    '500',
    factoryCheckHeader(
        $rescueSite,
        '500',
        getSiteDownNotifier($notiFyTo, $messageTitle, $messageContent)
    )
);
?>