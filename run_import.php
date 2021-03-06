<?php
  //see README for instructions
  error_reporting(E_ALL);
  ini_set("display_startup_errors",1);
  ini_set("display_errors",1);
  ini_set("memory_limit","1500M"); //datasets we are dealing with can be quite large, need enough space in memory
  set_time_limit(0);
  date_default_timezone_set('America/Chicago');
  
  //pulling from Socrata with https://github.com/socrata/socrata-php
  require("source/socrata.php");
  
  //inserting in to Fusion Tables with http://code.google.com/p/fusion-tables-client-php/
  require('source/clientlogin.php');
  require('source/sql.php');
  require('source/file.php'); //not being used, but could be useful to someone automating CSV import in to FT
  
  //my custom libraries
  require('source/connectioninfo.php');
  
  header('Content-type: text/plain');

  //keep track of script execution time
  $bgtime=time();

  $view_uid = ConnectionInfo::$view_uid;
  $data_site = ConnectionInfo::$data_site;
  $app_token = ConnectionInfo::$app_tokenn;
  $fusionTableId = ConnectionInfo::$fusionTableId;
  
  echo "Socrata -> Fusion Tables import by Derek Eder\n\n";
  echo "Downloading from Socrata … \n";
  
  //Fetch data from Socrata
  $response = NULL;
  if($view_uid != NULL && $data_site != NULL) {
    // Create a new unauthenticated client
    $socrata = new Socrata("http://$data_site/api", $app_token);

    $params = array();
    //$params["max_rows"] = 1; //max number of rows to fetch

    // Request rows from Socrata
    $response = $socrata->get("/views/$view_uid/rows.json", $params);
    
    echo "----Fetching data from Socrata----\n";
    echo "Dataset name: " . $response["meta"]["view"]["name"] . "\n";
    
    echo "\n----Columns----\n";
    $colCount = 0;
    foreach($response["meta"]["view"]["columns"] as $column) {
      echo $colCount . ": " . $column["name"] . "\n";
      $colCount++;
    }
    
    //Fetch info from Fusion Tables and do inserts & data manipulation
    echo "\n----Inserting in to Fusion Tables----\n";
    
    //get token
  	$token = ClientLogin::getAuthToken(ConnectionInfo::$google_username, ConnectionInfo::$google_password);
  	$ftclient = new FTClientLogin($token);
  	
  	//check how many are in Fusion Tables already
  	$ftResponse = $ftclient->query("SELECT Count() FROM $fusionTableId");
  	echo "$ftResponse \n";
  	
  	$ftResponse = $ftclient->query(SQLBuilder::select($fusionTableId, "'Inspection Date'", "", "'Inspection Date' DESC", "1"));
  	$ftResponse = trim(str_replace("Inspection Date", "", $ftResponse)); //totally a hack. there's a better way to do this
  	
  	//big assumption: socrata will return the data ordered by date. this may not always be the case
  	if ($ftResponse != "")
  		$latestInsert = new DateTime(str_replace("Inspection Date", "", $ftResponse));   
  	else
  		$latestInsert = new DateTime("1/1/2001"); //if there are no rows, set it to an early date so we import everything
  
    echo "\nLatest FT insert: " . $latestInsert->format('m/d/Y') . "\n";
		
    //$importBefore = new DateTime("10/10/2010");
    //echo "\nImporting before: " . $importBefore->format('m/d/Y') . "\n";
  
    /*
      File format
      8 Inspection ID
  		9 DBA Name
  		10 AKA Name
  		11 License #
  		12 Facility Type
  		13 Risk
  		14 Address
  		15 City
  		16 State
      17 Zip
  		18 Inspection Date
  		19 Inspection Type
  		20 Results
  		21 Violations
  		Geometry [new column]
  		
  		Results decode
  		1 Pass
  		2 Pass w/ Conditions
  		3 Fail
  		4 Out of Business
  		5 Business not Located
    */
  
  	$insertCount = 0;
  	$violationsCount = 0;
  	
  	$violationsParsed = 0;
  	$commentsParsed = 0;
  	$fp = fopen('food_inspections.csv', 'w+');
  	$licenseIds = array();
	
    foreach($response["data"] as $row) {

  		$inspectionDate = new DateTime($row[18]);
  		
  		$today = new DateTime();
  		$daysSinceInspection = $today->getTimestamp() - $inspectionDate->getTimestamp();
  		$daysSinceInspection = intval($daysSinceInspection / (60 * 60 *24));
  		
  		$fullAddress = $row[14] . " " . $row[15] . " " . $row[16] . " " . $row[17];
  		$location = $row[24] . "," . $row[25];
  		
  		$results = $row[20];
  		if ($results == "Pass") $results = "1";
  		else if ($results == "Pass w/ Conditions") $results = "2";
  		else if ($results == "Fail") $results = "3";
  		else if ($results == "Out of Business") $results = "4";
  		else if ($results == "Business not Located") $results = "5";
      else $results = 6;
      
      $violations = "";
      $comments = "";
      if ($row[21] != "")
      {
        $violationsCount++;
        //parse out violations
        $numViolations = preg_match_all('(\n\d+)', $row[21], $matches, PREG_PATTERN_ORDER);
  
        foreach ($matches as $val) {
          for ($j=0;$j<$numViolations;$j++) {
            $v = intval(str_replace("\n", "", $val[$j]));
            
            //set css class to hilight different violation severities
            $class = "violation-critical";
            if ($v >= 30) $class="violation-minor";
            else if ($v >= 15) $class="violation-serious";
            $violations .= "<a class='$class' href='violations.html#violation-$v' target='_blank'>$v</a>, ";
          }
        }
        $violations = trim($violations, " ,");
        if ($violations != "") $violationsParsed++;
        
        //do the same for comments
        $numComments = preg_match_all('/Comments:(.+)\n/', $row[21], $matches, PREG_PATTERN_ORDER);
  
        foreach ($matches as $val) {
          for ($j=0;$j<$numComments;$j++) {
            $v = str_replace("\n", "", $val[$j]);
            $comments .= "$v<br />";
          }
        }
        $comments = str_replace("Inspector Comments:", "", $comments);
        $comments = str_replace("Comments:", "", $comments);
        if ($comments != "") 
        {
          $comments = str_replace("\n", " ", $comments);
          $comments = truncateTxt($comments, 500);
          $comments = "<span class='mute'>$comments</span>";
          $commentsParsed++;
        }
  		}
  		//if ($inspectionDate > $latestInsert) {
	    	$insertArray = array(
	      "Inspection ID" => $row[8],
  		  "DBA Name" => clean_field($row[9]),
  		  "AKA Name" => clean_field($row[10]),
  		  "License #" => $row[11],
  		  "Facility Type" => $row[12],
  		  "Risk" => $row[13],
  		  "Address" => $fullAddress,
  		  "Inspection Date" => $row[18],
  		  "Inspection Type" => $row[19],
  		  "Days Since Inspection" => $daysSinceInspection,
  		  "Results" => $results,
  		  "Violations" => $violations,
  		  "Comments" => $comments,
  		  "Location" => $location
	    	);
	    	
	    	//only insert the most recent inspection
	      if (!in_array($row[11], $licenseIds)) {
	         fputcsv($fp, $insertArray);
	         array_push($licenseIds, $row[11]);
	         //echo $ftclient->query(SQLBuilder::insert($fusionTableId, $insertArray));
	    	   $insertCount++;
	    	   echo "inserted $insertCount so far (" . $inspectionDate->format('m/d/Y') . ")\n";
	      }
	    	
    	//}
    }
  }
  echo "\ninserted $insertCount rows\n";
  echo "$violationsCount violations present\n";
  echo "$violationsParsed violations parsed\n";
  echo "$commentsParsed comments parsed\n";
  
  echo "This script ran in " . (time()-$bgtime) . " seconds\n";
  echo "\nDone.\n";

  
  function clean_field($val) {
    return str_replace("'", "", $val);
  }
  
  function truncateTxt($string, $limit, $break=".", $pad="...")
  {
    // return with no change if string is shorter than $limit
    if(strlen($string) <= $limit) return $string;
  
    // is $break present between $limit and the end of the string?
    if(false !== ($breakpoint = strpos($string, $break, $limit))) {
      if($breakpoint < strlen($string) - 1) {
        $string = substr($string, 0, $breakpoint) . $pad;
      }
    }
  
    return $string;
  }
?>