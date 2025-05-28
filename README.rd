# Sierra to Primo Redirect Script

This PHP script is designed to redirect users from legacy Sierra permalinks and search URLs to the Kent State University Libraries' Primo discovery service.

## Overview

The script analyzes the incoming URL parameters, specifically looking for Sierra record identifiers (`record=`) or search queries. Based on the detected format, it constructs a corresponding search URL for Primo and redirects the user.

## Functionality

* **Bib Record Redirection:**
    * Identifies Sierra permalinks containing `record=`.
    * Extracts the bib number.
    * Processes the bib number by:
        * Removing a leading 'b' (if present).
        * Removing a trailing check digit (if the length is greater than 7).
        * Generating the correct Sierra check digit using the `make_check_digit` function.
        * Re-appending the 'b' and the generated check digit.
    * Constructs a Primo search URL to find the specific bibliographic record.
    * Redirects the user to the generated Primo URL.

* **Search Query Redirection:**
    * Identifies Sierra search URLs containing `SEARCH=`, `searchtype`, or `&FF`.
    * Parses the search parameters.
    * Maps Sierra search types (e.g., 't' for title, 'a' for author) to their Primo equivalents.
    * Constructs a Primo search URL based on the parsed query and search type.
    * Redirects the user to the generated Primo URL.

* **Handling Other Links:**
    * If the incoming URL does not contain a recognizable Sierra record or search pattern, and debugging is off, it redirects the user to the main Kent State University Libraries' Primo search page.
    * If debugging is on, it prints a message indicating that the link structure cannot be redirected.

* **Debugging Mode:**
    * A `$debug_on` variable controls debugging output.
    * When `$PRODUCTION` is set to `TRUE` (the default), `$debug_on` is automatically set to `false`.
    * When `$PRODUCTION` is `FALSE`, the script checks for a `debug` parameter in the URL (`?debug=true`).
    * In debug mode, the script outputs:
        * Server environment variables (`$_SERVER`).
        * Request parameters (`$_REQUEST`).
        * The referring URL (`$_SERVER['HTTP_REFERER']`).
        * The raw query string.
        * Messages indicating the type of link detected and the actions taken.

## Configuration

The following variables at the beginning of the script need to be configured:

* `$PRIMO_ID = '01OHIOLINK_KSU:KENT';`: The View ID (VID) for Kent State University's Primo instance.
* `$PRIMO_BASE_URL = 'https://ohiolink-ksu.primo.exlibrisgroup.com/discovery/search';`: The base URL for the Primo discovery search page.
* `$PRODUCTION = TRUE;`: A boolean indicating whether the script is running in a production environment. Set to `FALSE` to enable debugging via the `debug` URL parameter.

## Functions

* `make_check_digit($num)`:
    * Takes a numeric string `$num` as input.
    * Reverses the string.
    * Multiplies each digit by its position (starting from 2).
    * Calculates the sum of these products.
    * Determines the remainder when the sum is divided by 11.
    * Returns the check digit: the remainder itself, or 'x' if the remainder is 10.

* `processBibString($bib)`:
    * Takes a bib number string `$bib` as input.
    * Removes a leading 'b' if present (and if the string is longer than just 'b').
    * Removes the last character if the string length is greater than 7.
    * Returns the processed bib number string.

## Usage

1.  Place this PHP script on a web server accessible by the old Sierra permalinks and search URLs.
2.  Update the `$PRIMO_ID` and `$PRIMO_BASE_URL` variables to match your institution's Primo configuration.
3.  Ensure that the web server is configured to process PHP files.
4.  When a user clicks on an old Sierra permalink or uses a Sierra search URL that points to this script, they will be automatically redirected to the corresponding resource or search in the Primo discovery service.

## Modifications

* `10/24 - tr`: Indicates a modification made on October 24th by someone with the initials "tr". The specific changes are not detailed in the code.
* `5/21/2025 - dru`: Indicates a modification made on May 21st, 2025, by someone with the initials "dru". The comment suggests this modification might have involved changes to how bib numbers were processed, potentially related to handling the tilde (`~`) character, and notes that it might have worked differently for Ohio State University.
