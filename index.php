<?php

// Modified: 10/24 - tr
// Modified: 5/21/2025 - dru
$PRIMO_ID = '01OHIOLINK_KSU:KENT';
$PRIMO_BASE_URL = 'https://ohiolink-ksu.primo.exlibrisgroup.com/discovery/search';
$PRODUCTION = TRUE;

// get the initial query
$query_string = isset($_GET['q']) ? $_GET['q'] : '';
// get the server name
$server = isset($_GET['q']) ? $_SERVER['HTTP_REFERER'] : '';

// When on production, set debug_on to false.
if ($PRODUCTION) {
    $debug_on = 'false';
} else {
    $debug_on = isset($_GET['debug']) ? $_GET['debug'] : '';
}

/**
 * Function to generate a check digit for a given number.
 */
function make_check_digit($num)
{
    $tmp_num = strrev($num);
    $product = 0;
    for ($x = 0; $x < strlen($num); $x++) {
        $product += $tmp_num[$x] * (2 + $x);
    }
    $remainder = $product % 11;
    if ($remainder == 10) {
        $remainder = 'x';
    }
    return $remainder;
}


/**
 * Function to process the bib number string.
 * It removes the leading 'b' and the trailing character if the length is greater than 7.
 *
 * @param string $bib The bib number string to process.
 * @return string The processed bib number string.
 */
function processBibString($bib)
{
    // Check if the string starts with 'b' and is not just 'b' itself
    $bib = (substr($bib, 0, 1) === 'b' && strlen($bib) > 1) ? substr($bib, 1) : $bib;
    // If the length of the remaining string is greater than 7, remove the last character
    $bib = strlen($bib) > 7 ? substr($bib, 0, -1) : $bib;
    return $bib;
}


//=========================================================================================

/**
 * This script is used to redirect a user from a Sierra perm link to the Primo discovery service.
 * It captures the bib number from the Sierra perm link, generates a check digit,
 * and constructs a new URL for the Primo discovery service.
 */
if ($debug_on == 'true') {
    echo "Captured information: <br />";
    echo "Sever Values: <br />";
    echo "<pre>";
    var_dump($_SERVER);
    echo "</pre><br />";
    echo "<br />";
    echo "Request values: <br />";
    echo "<pre>";
    var_dump($_REQUEST);
    echo "</pre><br />";
    echo "<br />";
    $referring_URL = $server;
    if ($referring_URL == "") {
        echo "No Referring URL is present - this means the user is coming from a bookmark or direct link. <br />";
    } else {
        echo "Referring URL: " . htmlspecialchars($_SERVER['HTTP_REFERER'], ENT_QUOTES, 'UTF-8') . "<br />";
    }
    echo "Query String: " . htmlspecialchars($query_string, ENT_QUOTES, 'UTF-8') . '<br />';
}

// Parse the bibnumber.
if (strpos($query_string, 'record=') === false) {
    // This isn't a bib perm link -- do something else.
    if ($debug_on == 'true') {
         echo 'not a bib record -- doing other stuff' . '<br />';
    }
    //https://library.ohio-state.edu/search/X?SEARCH=building+digital+libraries&SORT=D&searchscope=7&submit=Submit
    //https://kentlink.kent.edu/search~S1/X?SEARCH=(building%20digital%20libraries)&searchscope=1&SORT=D&m=

    // Is this NOT a search link?
    if (
          strpos($query_string, 'SEARCH=') === false &&
          strpos($query_string, 'searchtype') === false &&
          strpos($query_string, '&FF') === false
    ) {
        // Not a search -- probably an erm record -- print the tombstone.
        if ($debug_on == 'true') {
            echo 'This link structure cannot be redirected.';
        }
    } else {
        // This is a search link, so we need to parse it.
        $parts = parse_url($query_string);
        if (isset($parts['query'])) {
            parse_str($parts['query'], $query);
            $searchtype = $query['searchtype'];
            $searcharg = $query['searcharg'];
            $inside_search = $query['FF'];
            $q_string = $query['SEARCH'];

            if (strlen($q_string) == 0) {
                  $q_string = $searcharg;
            }

            switch ($searchtype) {
                case "t":
                    $searchtype = 'title,contains';
                    break;
                case "a":
                    $searchtype = 'creator,contains';
                    break;
                case "d":
                case "j":
                    $searchtype = 'sub,contains';
                    break;
                default:
                    if (strlen($inside_search) == 0) {
                        $searchtype = 'any,contains';
                    } else {
                        if (substr($inside_search, 0, 1) == 't') {
                            $searchtype = 'lds04,contains';
                            $q_string = substr($query['FF'], 1);
                        } else {
                            $search_type = 'any,contains';
                        }
                    }
                    break;
            }

            // Properly encode the query parameters for the URL
            $url = $PRIMO_BASE_URL . '?query=' . urlencode($searchtype) . ',' . urlencode($q_string) . '&tab=LibraryCatalog&search_scope=MyInstitution&vid=' . urlencode($PRIMO_ID) . '&offset=0';
        } else {
            // Handle cases where the query string is malformed
            $url = $PRIMO_BASE_URL . '/discovery/search?vid=' . urlencode($PRIMO_ID);
        }
    }
} else {
    // This is a bib perm record.
    $bib_num = substr($query_string, strpos($query_string, 'record=') + strlen('record='));

    //since record numbers can be of varied length the best way to address this is to
    //clean the ~ out of the record argument if its present (and will be if the perm url
    //structure was used - then remove the b from the value.  We put it back later.
    //the reason for the removal is check digit is a loop where data is a multiplier of
    //data * position.  The b in the string is only useful at the end of the process
    //and will foul the check digit generation if present.
        if (strpos($bib_num, '~') !== false) {
            $bib_num = substr($bib_num, 0, strpos($bib_num, '~'));
            $bib_num = substr($bib_num, 1);
        }

    // Remove leading 'b' if any and trailing check digit if present.
    $bib_num = processBibString($bib_num);
    // Add leading 'b' back and create the check_digit.
    $bib_num = 'b' . $bib_num . make_check_digit($bib_num);

    // Properly encode the bib number for the URL
    $url = $PRIMO_BASE_URL . '?query=any,contains,' . urlencode($bib_num) . '&tab=LibraryCatalog&search_scope=MyInstitution&vid=' . urlencode($PRIMO_ID) . '&offset=0';
}

// If not debugging, redirect to the new URL.
if ($debug_on != 'true') {
    if ($url != '') {
        header('Location: ' . $url);
        exit; // Ensure script execution stops after redirection
    } else {
        // Else redirect to the main Primo page.
        $url =  $PRIMO_BASE_URL . '/discovery/search?vid=' . urlencode($PRIMO_ID);
        header('Location: ' . $url);
        exit; // Ensure script execution stops after redirection
    }
}