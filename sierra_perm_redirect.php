<?php
  //Modified: 10/24 - tr
  $PRIMO_ID = '01OHIOLINK_OSU:BLOCKO';
  //get the initial query
  $query_string = $_GET['q'];
  $debug_on = $_GET['debug'];

  function make_check_digit($num) {
	  //See https://documentation.iii.com/sierrahelp/Default.htm#sril/sril_records_numbers.html
	  //b8484612 without check digit
	  //to generate a check digit, reverse the string, 
	  //then multiple by postion starting at the new beginning
	  // 2, 3, 4, 5, 6, 7, 8
	  // device the sum of the totals by 11.  check digit is kept to 2 digits. 
	  // if the remainder is 10, then x.  if not 10, then its the rounded number.
	  // if that number is 10, then the value is x
	  $tmp_num = strrev($num);
	  $product = 0;
	  for ($x = 0; $x < strlen($num); $x++) {
		  $product += $tmp_num[$x] * (2+$x);		  
	  }
	  $remainder = $product % 11;	  
	  if ($remainder == 10) {
		  $remainder = 'x';
	  }
	  return $remainder;
  }

  if ($debug_on == 'true') {
	  echo "Captured information: <br />";
	  echo "Sever Values: <br />";
	  echo var_dump($_SERVER) . "<br />";
	  echo "<br />";
	  echo "Request values: <br />";
	  echo var_dump($_REQUEST) . "<br />";
	  echo "<br />";
	  $referring_URL = $_SERVER['HTTP_REFERER'];
	  if ($referring_URL == "") {
		  echo "No Referring URL is present - this means the user is coming from a bookmark or direct link. <br />";
	  } else {
		  echo "Referring URL: " . $_SERVER['HTTP_REFERER'] . "<br />";
	  }
	  echo "Query String: " . $query_string . '<br />'; 
  }
  //parse the bibnumber
  if (strpos($query_string, 'record=') === false) {
	  //this isn't a bib perm link -- do something else
	  if ($debug_on == 'true') {
	       echo 'not a bib record -- doing other stuff' . '<br />';
      }
	  //https://library.ohio-state.edu/search/X?SEARCH=building+digital+libraries&SORT=D&searchscope=7&submit=Submit
	  if (strpos($query_string, 'SEARCH=') === false &&
		  strpos($query_string, 'searchtype') === false &&
		  strpos($query_string, '&FF') === false) {
	//	  //not a search -- probably an erm record -- print the tombstone
		  echo 'This link structure cannot be redirected.';
	  } else {		
		  $parts = parse_url($query_string);		
          parse_str($parts['query'], $query);
		  //print_r($query);
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
				  $searchtype= 'sub,contains';
				  break;
			default:
				//echo('inside query: ' . $inside_search);				
				if (strlen($inside_search) == 0) {
				    $searchtype = 'any,contains';
				} else {
					if (substr($inside_search,0,1)=='t') {
						$searchtype='lds04,contains';
						$q_string = substr($query['FF'],1);
					} else {
						$search_type='any,contains';
					}
				}
				break;
		  }
		  
		  //$url = 'https://ohiolink-osu.primo.exlibrisgroup.com/discovery/search?query=any,contains,' . $query['SEARCH'] . '&tab=Everything&search_scope=MyInst_and_CI&vid=01OHIOLINK_OSU:OSU&offset=0';
		  $url = 'https://search.library.osu.edu/discovery/search?query=' . $searchtype . ',' . $q_string . '&tab=LibraryCatalog&search_scope=MyInstitution&vid=' . $PRIMO_ID . '&offset=0';
	  }
  } else {
	  //this is a bib perm record
	  $bib_num = substr($query_string, strpos($query_string, 'record=') + strlen('record='));
	  
      //since record numbers can be of varied length the best way to address this is to 
	  //clean the ~ out of the record argument if its present (and will be if the perm url 
	  //structure was used - then remove the b from the value.  We put it back later. 
	  //the reason for the removal is check digit is a loop where data is a multiplier of 
	  //data * position.  The b in the string is only useful at the end of the process
	  //and will foul the check digit generation if present.
	  if (strpos($bib_num, '~') >=0) {
		  $bib_num = substr($bib_num, 0, strpos($bib_num, '~'));
		  $bib_num = substr($bib_num, 1);
	  }
	  
	  //create the check_digit
	  $bib_num = 'b' . $bib_num . make_check_digit($bib_num);
	  
	  //search string: https://ohiolink-osu.primo.exlibrisgroup.com/discovery/search?query=any,contains,[bib_num]&tab=Everything&search_scope=MyInst_and_CI&vid=01OHIOLINK_OSU:OSU&offset=0
	  //$url = 'https://ohiolink-osu.primo.exlibrisgroup.com/discovery/search?query=any,contains,' . $bib_num . '&tab=Everything&search_scope=MyInst_and_CI&vid=01OHIOLINK_OSU:OSU&offset=0';
	  $url = 'https://search.library.osu.edu/discovery/search?query=any,contains,' . $bib_num . '&tab=LibraryCatalog&search_scope=MyInstitution&vid=' . $PRIMO_ID . '&offset=0';
	  
  }
  if ($debug_on != 'true') {
	  if ($url !='') {
	     header('location: ' . $url);
	  }
  }
?>