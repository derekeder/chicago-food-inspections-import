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
	
	//for clearing out table
	//$ftclient->query("DELETE FROM $fusionTableId");
	
	//check how many are in Fusion Tables already
	$ftResponse = $ftclient->query("SELECT Count() FROM $fusionTableId");
	echo "$ftResponse \n";
	
	//this part is very custom to this particular dataset. If you are using this, here's where the bulk of your work would be: data mapping!
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
	$fp = fopen('food_inspections.csv', 'w+');
	
    foreach($response["data"] as $row) {

  		$inspectionDate = new DateTime($row[18]);
  		$fullAddress = $row[14] . " " . $row[15] . " " . $row[16] . " " . $row[17];
  		$location = $row[24] . "," . $row[25];
  		
  		$results = $row[20];
  		if ($results == "Pass") $results = "1";
  		else if ($results == "Pass w/ Conditions") $results = "2";
  		else if ($results == "Fail") $results = "3";
  		else if ($results == "Out of Business") $results = "4";
  		else if ($results == "Business not Located") $results = "5";
      else $results = 6;
  		
  		if ($inspectionDate > $latestInsert) {
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
  		  "Results" => $results,
  		  "Violations" => clean_field($row[21]),
  		  "Location" => $location
	    	);
	    
	      //fputcsv($fp, $insertArray);
	    	echo $ftclient->query(SQLBuilder::insert($fusionTableId, $insertArray));
	    	$insertCount++;
	    	echo "inserted $insertCount so far (" . $inspectionDate->format('m/d/Y') . ")\n";
    	}
    }
  }
  echo "\ninserted $insertCount rows\n";
  echo "This script ran in " . (time()-$bgtime) . " seconds\n";
  echo "\nDone.\n";

  
  function clean_field($val) {
    return str_replace("'", "", $val);
  }
?>