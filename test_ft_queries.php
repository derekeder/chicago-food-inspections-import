<?php

include('source/clientlogin.php');
include('source/sql.php');
include('source/connectioninfo.php');

//get token
$token = ClientLogin::getAuthToken(ConnectionInfo::$google_username, ConnectionInfo::$google_password);
$ftclient = new FTClientLogin($token);
$fusionTableId = ConnectionInfo::$fusionTableId;

/*
//parse out violations
$testStr = <<< EOF
32. FOOD AND NON-FOOD CONTACT SURFACES PROPERLY DESIGNED, CONSTRUCTED AND MAINTAINED
Comments:Inspector Comments: All food and non-food contact equipment and utensils shall be smooth, easily cleanable, and durable, and shall be in good repair.MUST REPAIR OR REPLACE LOOSE DOOR HANDLE ON 3- DOOR REACH IN FREEZER,WORN DOOR GASKET ON REACH IN COOLER.MUST REMOVE RUST FROM LOWER SHELVES OF SOME PREP TABLES & SERVING LINE.  
33. FOOD AND NON-FOOD CONTACT EQUIPMENT UTENSILS CLEAN, FREE OF ABRASIVE DETERGENTS
Comments:Inspector Comments: All food and non-food contact surfaces of equipment and all food storage utensils shall be thoroughly cleaned and sanitized daily.MUST CLEAN MILK WALK IN COOLER FAN GUARD COVERS,DEEP FRYER,STAFF LOUNGE LOWER CABINET AT SINK,FILTERS AT HOOD,ICE MACHINE 
34. FLOORS: CONSTRUCTED PER CODE, CLEANED, GOOD REPAIR, COVERING INSTALLED, DUST-LESS CLEANING METHODS USED
Comments:Inspector Comments: The floors shall be constructed per code, be smooth and easily cleaned, and be kept clean and in good repair.MUST CLEAN FLOOR ALONG WALL BASES IN PREP AREA,4TH FL. MOP SINK CLOSET.  
35. WALLS, CEILINGS, ATTACHED EQUIPMENT CONSTRUCTED PER CODE: GOOD REPAIR, SURFACES CLEAN AND DUST-LESS CLEANING METHODS
Comments:Inspector Comments: The walls and ceilings shall be in good repair and easily cleaned.MUST SCRAPE & PAINT PEELING PAINT ON WALLS & CEILINGS IN LUNCH ROOM,JUICE & FOOD STORAGE AREA,DISH WASHING AREA.MUST REPLACE STAINED CEILING TILES IN LUNCH ROOM 
40. REFRIGERATION AND METAL STEM THERMOMETERS PROVIDED AND CONSPICUOUS
Comments:Inspector Comments: All food establishments that display, prepare, or store potentially hazardous foods shall have calibrated metal stem thermometers, provided and conspicuous, for refrigerated and hot food units.HOT HOLDING CABINET.  
41. PREMISES MAINTAINED FREE OF LITTER, UNNECESSARY ARTICLES, CLEANING  EQUIPMENT PROPERLY STORED
Comments:Inspector Comments: All parts of the food establishment and all parts of the property used in connection with the operation of the establishment shall be kept neat and clean and should not produce any offensive odors.MUST ELEVATE & ORGANIZE ARTICLES OFF OF FLOOR & AWAY FROM WALLS IN 439E,4TH FL. MOP SINK CLOSET,3RD FL. STORAGE AREA.  
EOF;

$numViolations = preg_match_all('(\n\d+)', $testStr, $matches, PREG_PATTERN_ORDER);

foreach ($matches as $val) {
  for ($j=0;$j<$numViolations;$j++) {
    $v = str_replace("\n", "", $val[$j]);
    $violations .= "<a href='violations.html#violation-$v' target='_blank'>$v</a>, ";
  }
}
//echo $violations = trim($violations, " ,");

$numComments = preg_match_all('/Comments:Inspector Comments:(.+)\n/', $testStr, $matches, PREG_PATTERN_ORDER);

foreach ($matches as $val) {
  for ($j=0;$j<$numComments;$j++) {
    $v = str_replace("\n", "", $val[$j]);
    $comments .= "$v<br />";
  }
}
echo str_replace("Comments:Inspector Comments: ", "", $comments);
*/

//echo $ftResponse = $ftclient->query("SELECT Count() FROM $fusionTableId");
//echo $ftclient->query("DELETE FROM $fusionTableId");

date_default_timezone_set('America/Chicago');
$testDate = new DateTime("4/4/2011");
$today = new DateTime();
$daysSinceInspection = $today->getTimestamp() - $testDate->getTimestamp();
$daysSinceInspection = $daysSinceInspection / (60 * 60 *24);

echo $daysSinceInspection
//show all tables
//echo $ftclient->query(SQLBuilder::showTables());
//echo "<br />";
//describe a table
//echo $ftclient->query(SQLBuilder::describeTable(358077));
//echo "<br />";
//select * from table
//echo $ftclient->query(SQLBuilder::select(358077));
//echo "<br />";
//select * from table where test=1
//echo $ftclient->query(SQLBuilder::select(358077, null, "'test'=1"));
//echo "<br />";
//select test from table where test = 1

//$ftclient->query(SQLBuilder::select(564620, array('rowid'), "'ANY PEOPLE USING PROPERTY? (HOMELESS, CHILDEN, GANGS)'=''"));

//foreach ($ftclient as $key => $value) {
//    echo "RowId: $value<br />\n";
//    }
//echo "<br />";
//select rowid from table
//echo $ftclient->query(SQLBuilder::select(358077, array('rowid')));
//echo "<br />";
//delete row 401
//echo $ftclient->query(SQLBuilder::delete(358077, '401'));
//echo "<br />";
//drop table
//echo $ftclient->query(SQLBuilder::dropTable(358731));
//echo "<br />";
//update table test=1 where rowid=1
//echo $ftclient->query("UPDATE 564620 SET 'ANY PEOPLE USING PROPERTY? (HOMELESS, CHILDEN, GANGS)' = 2 WHERE ROWID = ''");
//echo "<br />";
//insert into table (test, test2, 'another test') values (12, 3.3333, 'bob')
//echo $ftclient->query(SQLBuilder::insert(358077, array('test'=>12, 'test2' => 3.33333, 'another test' => 'bob')));

?>