<?php require('../phptest/ClassStructGlobal.php');
require('../phptest/buildFunctions.php');

// in php variables here are automatically global
$gEveryone = array(); // custom class array to hold ppl and their links
$gPeople = array();   // xml path containing all ppl
$gObjects = array();  // all Object names
$gPObjects = array();  // simple array that leads to handle and filename
$gVisitedCounter = 0;  // #times the name changes. Needed to mark people who's name we changed
$gTotalWidth = 0;
$gDOSEveryone = array();
$border = 20;


/* load XML FILE*/
$xml = simplexml_load_file('../phptest/DOS2.xml');

// Setup people and their objects
$gPeople = $xml->xpath('/database/people/person');



foreach ($gPeople as $person){
	$gEveryone[(string)$person['handle']] = new Person((String)$person['handle']);
	$gPObjects[(string)$person['handle']] = $person->objref[hlink];
}
// Setup objects and their names
$objects = $xml->xpath('/database/objects/object');
foreach ($objects as $object){
	$gObjects[(string)$object['handle']] = $object->file['src'];
}

/* create family links */
$families = $xml->xpath('/database/families/family');
foreach($families as $family){
	$f = (String)$family->father['hlink'];
	$m = (String)$family->mother['hlink'];
	$gEveryone[(string)$f]->spouse = $m;
	$gEveryone[(string)$m]->spouse = $f;
	foreach($family->childref as $child){
		$c = $child['hlink'];
		$gEveryone[(string)$f]->children[] = (String)$c;
		$gEveryone[(string)$m]->children[] = (String)$c;
		$gEveryone[(string)$c]->father = $f;
		$gEveryone[(string)$c]->mother = $m;
	}
}




/* look for the head honchos and build tree based on which head honcho
 * NOte: will fail in the futture when recursion gets too big. php limit=256? levels*/
foreach($gPeople as $person){
	$p = $gEveryone[(string)$person['handle']];
	if($p->father == "" and $p->mother == ""){
		$p->getWeight();
		//echo "head node found".$person['handle']."<br>";
        $gTotalWidth = 2*$border + $p->getWeight() + 2; //Note: dunno where the 2 comes from.
		break;
	}
}

function printAll(){
	global $gPeople;
	global $gEveryone;
    global $xml;
	/* print everyone just for kicks*/
	foreach ($gPeople as $person){
        //$results = $xml->xpath('/database/people/person[@handle=\''.$child."']");
		echo "id is ".$person['handle']." ".$person->name->first." ";
		$gEveryone[(string)$person['handle']]->printme();
	}
}

function getTotalWidth(){
	global $gTotalWidth;
	echo $gTotalWidth;
}



?>
<?php
/* function builds the actual structure of the tree */
function buildBodyFromXML(){
	global $gEveryone;
	global $gPeople;
	global $smallT;
	global $pole;
	global $bar;
	global $lpole; 
	global $rpole;
	global $bigT;
	global $link;
	global $gTotalWidth;
    global $border;
	
	function makeEmptyCells($size,$string){
		$result = "";
		for($i9=0; $i9<$size; $i9++)
			$result .= "<td id=emptyCell>$string</td>";	
		return $result;
	}
	
/*/ Note: I'm not sure of the purpose of not using the commented out line...Ah, the point is to make the area under the name for those
	         with no picture to be draggable. If it was all person, there would be non-draggable whitespace.*/
	function makePersonCell($string){
		global $personHeight;
		$result = "<td><table height=$personHeight cellpadding=0 cellspacing=0><tr><td id=".$string." class='person'></td></tr>".
			"<tr>".makeEmptyCells(1,"")."</tr></table></td>";
		//$result = "<td id=".$string." class=person></td>";
		return $result;
	}
	

	foreach($gPeople as $person){
		$peque = Array();							//Person queue, pronounced "peck"
		$p = $person['handle']; 					// p is for person
		$pObj = $gEveryone[(string)$p];
		$s = $pObj->spouse;							// s is for spouse
	
		// $sObj = $gEveryone[$s]; Not needed
		if(($pObj->father == "" and $pObj->mother == "")){
			$peque[] = $p;
			$peque[] = "-1";
			
			
			while(count(array_unique($peque)) > 1){ // array_unique is used so that all the "-1" empty cells are counted multiple times
			$numInRow = count($peque);
			$pRow = "<tr class=personRow>"; 		// personRow html
			$ctRow = "<tr class=connectorRow>";		// conenctorTopRow html
			$cbRow = "<tr class=connectorRow>";		// connectorBotRow html
			

			

			
			// empty cells just for border
			$pRow .= makeEmptyCells($border,"");
			$ctRow .= makeEmptyCells($border,"");
			$cbRow .= makeEmptyCells($border,"");
			
			for($ni=0; $ni<$numInRow; $ni++){
				$p2 = array_shift($peque);			// array_shift means pop the first thing off the list.
				// if empty cell, print empty cells this row add add it to the list of what to do next row
				if($p2 == "-1"){
					$pRow .= makeEmptyCells(2,"");
					$ctRow .= makeEmptyCells(2,"");
					$cbRow .= makeEmptyCells(2,"");
					$peque[] = $p2;
					continue;
				}
				
				$p2Obj = $gEveryone[(string)$p2];	// get the person object from the global list of ppl
				
				
				/* //DEBUG
				reset($p2Obj->children);
				for($o=0;$o<count($p2Obj->children);$o++){
					list($key,$c) = each($p2Obj->children);
					echo  $gEveryone[(string)$c]->getWeight().":";
				}
				echo ";";*/
				
				/* Single child with no spouse case is special...can't get around it..*/
				if(count($gEveryone[(string)$p2Obj->father]->children) == 1 and $p2Obj->spouse == ""){
					$pRow .= makeEmptyCells(1,"");					
					$pRow .= makePersonCell($p2);
					$pRow .= makeEmptyCells(2,"");
					$ctRow .= makeEmptyCells(4,"");
					$cbRow .= makeEmptyCells(4,"");
					$peque[] = "-1";
					$peque[] = "-1";
				} else if($p2Obj->spouse == ""){
				/* case for a single child in a big family */
					$pRow .= makePersonCell($p2);
					$pRow .= "<td id=emptyCell></td>"; // assuming same thing as empty cell
					$ctRow .= makeEmptyCells(2,"");
					$cbRow .= makeEmptyCells(2,"");
					$peque[] = "-1";
				} else { // has a spouse
                    // TODO single child with a spouse condition just fails
					// For future reference for multiple spouses	$link =   "<table width=100% cellpadding=0 cellspacing=0><tr height=10><td class=link><img src=spacer.gif height=8></td></tr><tr height=200><td>&nbsp;</td></tr></table>";
					$s2 = (string)$p2Obj->spouse;
					$sObj2 = $gEveryone[$s2];
                    // Note: this may fail
                    /*if (count($gEveryone[(string)$p2Obj->father]->children) == 1)
                        $pRow .= makeEmptyCells(1,"");*/
					$pRow .= makeEmptyCells($p2Obj->getWeight()/2-2,"");
					$pRow .= makePersonCell($p2);
					if(count($p2Obj->children) > 0)
						$pRow .= "<td id=emptyCell>$smallT</td>";
					else
						$pRow .= "<td id=emptyCell>$link</td>";
					$pRow .= makePersonCell($s2);
					$pRow .= makeEmptyCells($p2Obj->getWeight()/2-1,"");
					
					//deal with ct and cb row from here on
					if(count($p2Obj->children)==0){ 
						// spouse but no children. It ends here with two blank spaces
						$peque[] = "-1";
						$peque[] = "-1";
						$ctRow .= makeEmptyCells(4,"");
						$cbRow .= makeEmptyCells(4,"");
					}
					else if(count($p2Obj->children) == 1){
						$ctRow .= makeEmptyCells($p2Obj->getWeight()/2-1,"");
						$ctRow .= "<td id=emptyCell>$pole</td>";
						$ctRow .= makeEmptyCells($p2Obj->getWeight()/2,"");
						$cbRow .= makeEmptyCells($p2Obj->getWeight()/2-1,"");
						$cbRow .= "<td id=emptyCell>$pole</td>";
						$cbRow .= makeEmptyCells($p2Obj->getWeight()/2,"");
							
					}else{ // two or more children to draw lines for
                        //Note: this may fail...and it did
                        /*if (count($gEveryone[(string)$p2Obj->father]->children) == 1)
                            $ctRow .= makeEmptyCells(1,"");*/
						//Draw ctRow
						for($i=0; $i<$p2Obj->getWeight()/2-1; $i++)
							$ctRow .= "<td id=emptyCell></td>";
						$ctRow .= "<td id=emptyCell>$pole</td>";
						for($i=0; $i<$p2Obj->getWeight()/2; $i++)
							$ctRow .= "<td id=emptyCell></td>";
						

						//Draw cbRow
						// c is for child
						// code decides where to create left rpole		
						reset($p2Obj->children);		// set's pointer to the beginning of the object. needed for each to work
						list($key,$c) = each($p2Obj->children); // get the first child. didn't figure out other way besides each()
						$cObj = $gEveryone[(string)$c];
						if($cObj->spouse == ""){
							$cbRow .= makeEmptyCells(1,$rpole);
							$cbRow .= makeEmptyCells(1,$bar);
						}else{
							$cbRow .= makeEmptyCells($cObj->getWeight()/2-2,"");
							$cbRow .= makeEmptyCells(1,$rpole);
							$cbRow .= makeEmptyCells($cObj->getWeight()/2+1,$bar);
						}
										
						// for children in the middle
						for($j=0; $j< count($p2Obj->children)-2; $j++){
							list($key,$c) = each($p2Obj->children);
							$cObj = $gEveryone[(string)$c];
							if($cObj->spouse == ""){
								$cbRow .= makeEmptyCells(1,$bigT);
								$cbRow .= makeEmptyCells(1,$bar);
							}else{
								$cbRow .= makeEmptyCells($cObj->getWeight()/2-2,$bar);
								$cbRow .= makeEmptyCells(1,$bigT);
								$cbRow .= makeEmptyCells($cObj->getWeight()/2+1,$bar);			
							}
						}
						
						//for child at the end
						list($key,$c) = each($p2Obj->children);
						$cObj = $gEveryone[(string)$c];
						if($cObj->spouse == ""){
							$cbRow .= makeEmptyCells(1,$lpole);
							$cbRow .= makeEmptyCells(1,"");
						}else{
							$cbRow .= makeEmptyCells($cObj->getWeight()/2-2,$bar);
							$cbRow .= makeEmptyCells(1,$lpole);
							$cbRow .= makeEmptyCells($cObj->getWeight()/2+1,"");
						}
						
					}
					// Person in the current row has a spouse...maybe no children, either way, add children to queue. other cases already handled
					foreach($p2Obj->children as $child)
						$peque[] = $child;
				}
			}
			// make border
			$pRow .= makeEmptyCells($border,"");
			$ctRow .= makeEmptyCells($border,"");
			$cbRow .= makeEmptyCells($border,"");
			echo $pRow."</tr>";
			echo $ctRow."</tr>";
			echo $cbRow."</tr>";
			}
			
			break; //uh...can only print one head family for now
		}
	}
}



?>

<?php 

$upload_dir = "/var/www/workspace/phptest/upload/";
global $xml;
global $gEveryone;
global $gVisitedCounter;
//print_r($_FILES);

// only upload panel has name=object, so we're handling info update 
if ($_POST['object']){

// if there's a picture with no transfer error, or there's no picture at all
// we don't perform any changes if the picture upload has an error.
if ($_FILES['picture']['error'] == 0 || $_FILES['picture']['error'] == 4){
	// set the id we have to edit to a new one or old one depending on if we're adding ppl or not.
	if($_POST['object'] == "self")
		$editID = $_POST['id'];
	else
		$editID = "I".time();
	
	echo $editID."- edit id\n";
	echo $_POST['id']."-post id\n";
	
	// we handle new entries here.
	// All changes to the family structure happens here.
    $newPerson = NULL;
	switch ($_POST['object']){
	case "father":
		$result = $xml->xpath("/database/families/family[childref[@hlink='".$_POST['id']."']]");
		
		// add to list of persons in xml
		$newPerson = $xml->people->addChild("person");
		$newPerson->addAttribute("id",$editID);
		$newPerson->addAttribute("handle",$editID);
		
		// add to or create family structure
		// if there is no family for the father we want to add, then we add.
		if($result[0] == false){
		/* due to the way we have set things, anytime we want to add a parent, only one child will exist
		 * so we don't have to find people who are potential children for this father
		 */
			$newFamily = $xml->families->addChild("family");
			$newFamilyID = "F".time();
			$newFamily->addAttribute("id",$newFamilyID); 
			$newFamily->addAttribute("handle",$newFamilyID); 
			$newFamily->addChild("father");
			$newFamily->father->addAttribute("hlink", $editID);
			$newFamily->addChild("childref");
			$newFamily->childref->addAttribute("hlink",$_POST['id']);
			$newFamily['change'] = time();
		} else  {
			$family = $result[0];
			$family->addChild("father");
			$family->father->addAttribute("hlink", $editID);
			$family['change'] = time();
		}
		echo "family add complete\n";
		break;
	
	case "mother":
		$result = $xml->xpath("/database/families/family[childref[@hlink='".$_POST['id']."']]");
		
		// add to list of persons in xml
		$newPerson = $xml->people->addChild("person");
		$newPerson->addAttribute("id",$editID);
		$newPerson->addAttribute("handle",$editID);
		
		// add to or create family structure
		// if there is no family for the father we want to add, then we add.
		if($result[0] == false){
		/* due to the way we have set things, anytime we want to add a parent, only one child will exist
		 * so we don't have to find people who are potential children for this father
		 */
			$newFamily = $xml->families->addChild("family");
			$newFamilyID = "F".time();
			$newFamily->addAttribute("id",$newFamilyID); 
			$newFamily->addAttribute("handle",$newFamilyID); 
			$newFamily->addChild("mother");
			$newFamily->mother->addAttribute("hlink", $editID);
			$newFamily->addChild("childref");
			$newFamily->childref->addAttribute("hlink",$_POST['id']);
			$newFamily['change'] = time();
		} else  {
			$family = $result[0];
			$family->addChild("mother");
			$family->father->addAttribute("hlink", $editID);
			$family['change'] = time();
		}
		echo "family add complete\n";
		break;

    //TODO: figureout if this is all right
    case "spouse":
		// find all families where the given id is a mother or father
		$result = $xml->xpath("/database/families/family[father[@hlink='".$_POST['id']."']] | /database/families/family[mother[@hlink='".$_POST['id']."']]");

		// add to list of persons in xml
		$newPerson = $xml->people->addChild("person");
		$newPerson->addAttribute("id",$editID);
		$newPerson->addAttribute("handle",$editID);

		// add to or create family structure
		// if there is no family for the father we want to add, then we add.
		if($result[0] == false){
		/* due to the way we have set things, anytime we want to add a parent, only one child will exist
		 * so we don't have to find people who are potential children for this father
		 */
			$person = $xml->xpath("/database/people/person[@id='".$_POST['id']."']");
			$newFamily = $xml->families->addChild("family");
			$newFamilyID = "F".time();
			$newFamily->addAttribute("id",$newFamilyID);
			$newFamily->addAttribute("handle",$newFamilyID);

			if($person[0]->gender == "M"){
				$newFamily->addChild("father");
				$newFamily->father->addAttribute("hlink", $_POST['id']);
                $newFamily->addChild("mother");
                $newFamily->mother->addAttribute("hlink", $editID);
			}else{
				$newFamily->addChild("father");
				$newFamily->father->addAttribute("hlink", $editID);
                $newFamily->addChild("mother");
                $newFamily->mother->addAttribute("hlink", $_POST['id']);
			}
		} else  {
            echo "not supposed to get here. adding spouse to a family that exists";
		}
		echo "family add complete\n";
		break;
	
	case "child":
		// find all families where the given id is a mother or father
        //$result = $xml->xpath("//family/father[@hlink='".$_POST['id']."'] || //family/mother[@hlink='".$_POST['id']."']");
		$result = $xml->xpath("/database/families/family[father[@hlink='".$_POST['id']."']] | /database/families/family[mother[@hlink='".$_POST['id']."']]");
        echo $result."\n";

		// add to list of persons in xml
		$newPerson = $xml->people->addChild("person");
		$newPerson->addAttribute("id",$editID);
		$newPerson->addAttribute("handle",$editID);
		
		// add to or create family structure
		// if there is no family for the father we want to add, then we add.
		if($result[0] == false){
		/* due to the way we have set things, anytime we want to add a parent, only one child will exist
		 * so we don't have to find people who are potential children for this father
		 */
			$person = $xml->xpath("/database/people/person[@id='".$_POST['id']."']");
			$newFamily = $xml->families->addChild("family");
			$newFamilyID = "F".time();
			$newFamily->addAttribute("id",$newFamilyID); 
			$newFamily->addAttribute("handle",$newFamilyID); 
			
			if($person[0]->gender == "M"){
				$newFamily->addChild("father");
				$newFamily->father->addAttribute("hlink", $_POST['id']);
			}else{
				$newFamily->addChild("mother");
				$newFamily->mother->addAttribute("hlink", $_POST['id']);
			}
			$newFamily->addChild("childref");
			$newFamily->childref->addAttribute("hlink",$editID);
			$newFamily['change'] = time();
		} else  { // else we don't have to make a new family, just a new child
			$family = $result[0];
			$newChild = $family->addChild("childref");
			$newChild->addAttribute("hlink", $editID);
			$family['change'] = time();
		}
		echo "family add complete\n";
		break;
		
		case "self":
		//we do nothing because no family structure changes except we setup newPerson as an old person to give them a  picture.
        $s = "/database/people/person[@id='".$editID."']";
        $result = $xml->xpath($s);
        $newPerson = $result[0];
        echo "settingNewPerson\n";
		break;
	}

	// If there is a picture of the right type
	if ((($_FILES["picture"]["type"] == "image/gif")
	|| ($_FILES["picture"]["type"] == "image/jpeg")
	|| ($_FILES["picture"]["type"] == "image/tiff")
	|| ($_FILES["picture"]["type"] == "image/bmp")
	|| ($_FILES["picture"]["type"] == "image/png")
	|| ($_FILES["picture"]["type"] == "image/pjpeg"))
	&& ($_FILES["picture"]["size"] < 10240000)){/*
	    echo "Upload: " . $_FILES["picture"]["name"] . "<br />";
	    echo "Type: " . $_FILES["picture"]["type"] . "<br />";
	    echo "Size: " . ($_FILES["picture"]["size"] / 1024) . " Kb<br />";
	    echo "Stored in: " . $_FILES["picture"]["tmp_name"];*/
		if ($_FILES["picture"]["size"] != 0){
			// upload picture to our directory	
			move_uploaded_file($_FILES["picture"]["tmp_name"], "../phptest/upload/".$_POST["id"].picture.$_FILES["picture"]["name"]);
			chmod("../phptest/upload/".$_POST["id"].picture.$_FILES["picture"]["name"],0777);
			echo "uploaded file";
			
			// make new object reference
			$result = $xml->xpath("/database/objects/object[@id='"."O".$_POST["id"]."']");
			// if false, we need to create a new object element
			if ($result[0] == false){
                echo "adding the references\n";
                $newObjRef = $newPerson->addchild("objref");
                $newObjRef->addAttribute("hlink","O".$_POST["id"]);
				$newObject = $xml->objects->addChild("object");
				$newObject->addAttribute("id","O".$_POST["id"]);
				$newObject->addAttribute("handle","O".$_POST["id"]);
				$newObject->addAttribute("change",time());
				$newFile = $newObject->addChild("file");
				$newFile->addAttribute("src",$_POST["id"].picture.$_FILES["picture"]["name"]);
				$newFile->addAttribute("mime",$_FILES["picture"]["type"]);
			}else{ // else, all the links exist, we just need to update the src
				$file = $result[0]->file;
				$file['change'] = time();
				$file['src'] = $_POST["id"].picture.$_FILES["picture"]["name"];
				$file['mime'] = $_FILES["picture"]["type"];
			}
		}		
	} // else there is a picture of the wrong type
	elseif ($_FILES["picture"]["size"] <> 0) {
	  echo "Invalid file";
	  echo "\n".$_FILES["picture"]["size"];
	  return;
	}
	
	// Perform data entry
	
	echo"modifying data\n";
	$s = "/database/people/person[@id='".$editID."']";
	$result = $xml->xpath($s);
	$person = $result[0];
	$person->gender = $_POST['gender'];
	$person->name->first = $_POST['first'];
    $person->name->middle = $_POST['middle'];
	$person->name->last = $_POST['last'];
	$person->address->street = $_POST['street'];
	$person->address->city = $_POST['city'];
	$person->address->state = $_POST['state'];
	$person->address->country = $_POST['country'];
	$person->address->postal = $_POST['postal'];
	$person->address->phone = $_POST['phone'];
	$person->address->email = $_POST['email'];
	$person['change'] = time();
	// write out file
	file_put_contents('FamilyTree3.xml',$xml->asXML());
	chmod('FamilyTree3.xml',0777);
	
	
	//echo $test[0]->asXML();
	/*
	foreach ($xml->people->person as $person){
		echo $person['id']."\n";
		//echo $person->name->first."\n";
	}*/
	
} else {
	echo "seems there is an error";
	echo "Error: " . $_FILES["picture"]["error"] . "<br />";
}
}
if (isset($_POST['myName'])){// handle requests from myNameForm
	
	$gVisitedCounter++;
	$gEveryone[$_POST['myNameID']]->testAndVisit();
	echo generateNewNames($_POST['myNameID'],0,0,false,0);
	if(true){ //isset($_POST['isDOS'])){
        print buildDOSBody($_POST['myNameID'],10);
        //echo buildDOSbody($_POST['myNameID'],$_POST['degreesOfSeperation']);
	}
} else if ($_POST['changeName']){
	
}

//TODO buildDOSbody
function buildDOSBody($myNameID,$degreesOfSeperation){
	global $gEveryone;
	global $gPeople;
	global $smallT;
	global $pole;
	global $bar;
	global $lpole; 
	global $rpole;
	global $bigT;
	global $link;
	global $gTotalWidth;
    global $gDOSEveryone;

    $gDOSEveryone = $gEveryone;
    $gDOSEveryone[$myNameID]->buildDOSTree($degreesOfSeperation,0);
	
	
}



function generateNewNames($currentID, $rank, $gen, $isPaternal, $inFamily){
	global $xml;
	global $gEveryone;
	
	$pObject = $gEveryone[(String)$currentID];
	if($inFamily < -1) return;
	
	
	$results = $xml->xpath('/database/people/person[@handle=\''.$currentID."']");
	$gender = $results[0]->gender;
	$firstName = $results[0]->name->first;
	echo ",".$currentID.",";
	
	// This section prints out the new names
	if($gen < 0 || ($rank==-1 && $gen==0)){
		echo (($gender=="M")?"thằng":"con")."<br>".$firstName;
	}else if($rank == 0){
		switch($gen){
		case 0:
			if($inFamily == 0) echo specialFormat("Tôi đây");
			else echo ($gender=="M")?"Anh":"Em"."<br>".$firstName;
			break;
		case 1:
			echo specialFormat(($gender=="M")?"Ba":"Má");
			break;
		case 2:
			if($isPaternal)
				echo ($gender=="M")?"Ông<br>Nội":"Bà<br> Nội";
			else
				echo ($gender=="M")?"Ông<br>ngoại":"Bà<br>Ngoại";
			break;
		case 3:
			echo specialFormat(($gender=="M")?"Ông Cố":"Bà Cố<br>");
			break;
		default:
			if ($gender=="M"){
				echo "Ông<br>".$results[0]->name->first;
			}
			else {
				$results2 = $xml->xpath('/database/people/person[@handle=\''.$pObject->spouse."']");
				echo "bà<br>".$results2[0]->name->first;
			}
		}
	}else if ($gen > 1){
		if ($gender=="M"){
			echo "Ông<br>".$results[0]->name->first;
		}
		else {
			if($pObject->spouse != "")
                $results = $xml->xpath('/database/people/person[@handle=\''.$pObject->spouse."']");
			echo "Bà<br>".$results[0]->name->first;
		}	
	}else if($rank ==1){
		switch($gen){
		case 0:
			echo (($gender=="M")?"Anh":"Chi")."<br>".$firstName;
			break;
		case 1:
			echo "Bác<br>".$firstName;
			break;					
		}
	} else if($rank == -1){
		if ($isPaternal){
			if ($inFamily == 0) 
				echo (($gender=="M")?"Chú":"Co")."<br>".$firstName;
			else
				echo (($gender=="M")?"Chú":"Thím")."<br>".$firstName;
		}else{
			if ($inFamily == 0) 
				echo (($gender=="M")?"Cậu":"Dì")."<br>".$firstName;
			else
				echo (($gender=="M")?"Chú":"Mợ")."<br>".$firstName;
		}
	}
	
	
	// This section recursively calls new people
	// Note: $inFamily == 0 condition prevents formatting other people. We need to know age before we can apply to ppl outside
    
    $addMother = ($pObject->mother != "" && $gEveryone[(String)$pObject->mother]->testAndVisit());
    $addFather = ($pObject->father != "" && $gEveryone[(String)$pObject->father]->testAndVisit());
    $addSpouse = ($pObject->spouse != "" && $gEveryone[(String)$pObject->spouse]->testAndVisit());
	$fromChildIndex = -1;
	$hasEmptyChild = false;
	reset($pObject->children);
	while($child = current($pObject->children)){
		//echo "child".key($pObject->children)."has ".$gEveryone[(String)$child]->visited;
		if(!$gEveryone[(String)$child]->testAndVisit()) $fromChildIndex = key($pObject->children);
		else{
			//$results2 = $xml->xpath('/database/people/person[@handle=\''.$child."']");
			//echo $firstName." adds ".$results2[0]->name->first;
			$hasEmptyChild = true;
		}
		next($pObject->children);
	}
		
		//echo "Index: ".$fromChildIndex." Empty:".$hasEmptyChild;
		
	if ($addMother){
		if($rank==0 && $gen ==0){
			//echo $firstName."->mother";
			generateNewNames($pObject->mother,$rank,$gen + 1,false,$inFamily);
		}else{
			//echo $firstName."->mother";
			generateNewNames($pObject->mother,$rank,$gen + 1,$isPaternal,$inFamily);
		}
	}
	if ($addFather){
		if($rank==0 && $gen ==0){
			//echo $firstName."->father";
			generateNewNames($pObject->father,$rank,$gen + 1,true,$inFamily);
		}else{
			//echo $firstName."->father";
			generateNewNames($pObject->father,$rank,$gen + 1,$isPaternal,$inFamily);
		}
	}
	if($hasEmptyChild == true){
		$newRank = 1;
		for($i= 0; $i< count($pObject->children); $i++){
			$child = $pObject->children[$i];
			if($fromChildIndex == 0){
				$results2 = $xml->xpath('/database/people/person[@handle=\''.$child."']");
				//echo $firstName." is index".$fromChildIndex."skipping ".$results2[0]->name->first;
				$newRank = -1;
				$fromChildIndex--;
				continue;
			}
			$fromChildIndex--;			

			if($rank == 0){				
				//echo $firstName."->child0";
				generateNewNames($child,$newRank,$gen - 1,$isPaternal,$inFamily);
			}else{				
				//echo $firstName."->child1";
				generateNewNames($child,$rank,$gen - 1,$isPaternal,$inFamily);
			}
		}
	}
	if ($addSpouse){	
		//echo $firstName."->spouse";
		generateNewNames($pObject->spouse,$rank,$gen,$isPaternal,$inFamily - 1); // Note: Your mom will be added as a spouse of your husband as well as a mother, so $inFamily won't ruin it.
	}
}
function specialFormat($str){
    return "<div style=\"padding: 8px;line-height:13px;white-space=normal;font-size:small;\">".$str."</div>";
}

?>