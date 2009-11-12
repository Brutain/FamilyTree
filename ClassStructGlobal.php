<?php
class Person
{
  // member declaration
    public $myID   = "";
  	public $father = "";
	public $mother = "";
	public $spouse = "";
	public $children = Array();
	public $weight = -1;    // this variable might be eliminated when modes are combined and this is just DOS(9999)
    public $dWeight = -1; // DOS weight. different because not all children are included in DOS.
    public $topWeight = 0; // needed for DOS weights because mother and father adds to weight
	public $visited = 0;
	public $rightWeight = -1;
	public $topHeight = -1;
	public $bottomHeight = -1;
	public $x = -1;
	public $y = -1;
	
    function Person($ID){
        $this->myID = $ID;
    }
   



    // caclulate our weight and all ones below us
    public function getWeightSingleChildren() { //new ones give concurrent single childs a single weight
    	global $gEveryone;
    	if($this->weight != -1) return $this->weight;
        if (count($this->children) == 0){
        	if($this->spouse != "") $this->weight = 4;
            else $this->weight = 2;
		} else  {
			$sumWeight = 0;
            $singleChildsWeight = 0;
			foreach($this->children as $child){
                if((count($gEveryone[(string)$child]->children)) == 0){
                    $singleChildsWeight = max($singleChildsWeight,$gEveryone[$child]->getWeight()); // normally 2 else 4 if wife
                }
                else{
                    $sumWeight += $singleChildsWeight;
                    $singleChildsWeight = 0;
                    $sumWeight += $gEveryone[(string)$child]->getWeight();
                }
			}
            $sumWeight += $singleChildsWeight; // if the last child is single, it won't be added till now
			if ($sumWeight<4) $sumWeight = 4;
			$this->weight = $sumWeight;
		}
		return $this->weight;
    }

    public function getWeight() {
    	global $gEveryone;
    	if($this->weight != -1) return $this->weight;
        if (count($this->children) == 0){
        	if($this->spouse != "") $this->weight = 4;
            else $this->weight = 2;
		} else  {
			$sumWeight = 0;
			foreach($this->children as $child){
				$sumWeight += $gEveryone[(string)$child]->getWeight();
			}
			if ($sumWeight<4) $sumWeight = 4;
			$this->weight = $sumWeight;
		}
		return $this->weight;
    }



    public function getDOSWeight() {
    	global $gDOSEveryone;
    	if($this->dWeight != -1) return $this->dWeight;
        
        if (count($this->children) == 0){
        	if($this->spouse != "") $this->dWeight = 4;
            else $this->dWeight = 2;
		} else  {
			$sumWeight = 0;
			foreach($this->children as $child){
				$sumWeight += $gDOSEveryone[(string)$child]->getDOSWeight($DOS-1);
			}
			if ($sumWeight<4) $sumWeight = 4;
			$this->dWeight = $sumWeight;
		}
		return $this->dWeight;
    }


	// calculate our top height
	public function buildDOSTree($DOS,$currentGen){
		global $gDOSEveryone;
        global $xml; // for debug purposes only

        if($DOS == 0) $this->handleDOSCondition();
        $addMother = ($this->mother != "" && $gDOSEveryone[(String)$this->mother]->testAndVisit());
        $addFather = ($this->father != "" && $gDOSEveryone[(String)$this->father]->testAndVisit());
        $addSpouse = ($this->spouse != "" && $gDOSEveryone[(String)$this->spouse]->testAndVisit());
        $fromChildIndex = -1;
        $hasEmptyChild = false;
        reset($this->children);
        while($child = current($this->children)){
            //echo "child".key($this->children)."has ".$gDOSEveryone[(String)$child]->visited;
            if(!$gDOSEveryone[(String)$child]->testAndVisit()){ 
            	$fromChildIndex = key($this->children);
            }else{
                //$results2 = $xml->xpath('/database/people/person[@handle=\''.$child."']");
                //echo $firstName." adds ".$results2[0]->name->first;
                $hasEmptyChild = true;
            }
            next($this->children);
        }




        $results2 = $xml->xpath('/database/people/person[@handle=\''.$this->myID."']");
        if($results2[0]->name->first == "Nasdf"){
            echo ($addMother)?"true":"false";
            echo (($addFather)?"true":"false").(($addSpouse)?"true":"false").(($hasEmptyChild)?"true":"false").$fromChildIndex."<br>";
        }
        $motherMaxHeight = -1;
        $fatherMaxHeight = -1;
        $childrenMaxHeght = -1;
        $spouseMaxHeight = -1;
        $motherMinHeight = -1;
        $fatherMinHeight = -1;
        $childrenMinHeght = -1;
        $spouseMinHeight = -1;

		if($this->father == "" && $this->mother == ""){
			$this->topHeight = 0;
            $this->topWeight = 2;
            $this->dWeight = $this->getDOSWeight();
		}
        if(count($this->children) == 0){
            $this->bottomHeight = 0;
        }
        if(!$addSpouse) $this->topHeight = 0;
        //if($currentGen < 0) $this->topWeight = 0;
        if($currentGen > 0) $this->bottomHeight = 0;

        if($addMother){
            $weights = $gDOSEveryone[$this->mother]->buildDOSTree($DOS-1,$currentGen+1);
            $motherMaxHeight = $weights[0]+1;
            $this->topWeight = $this->topWeight + $weights[1];
            //$this->topWeight = max($this->topWeight,$gDOSEveryone[$this->mother]->topWeight+1);

        }
        if($addFather){
            $weights = $gDOSEveryone[$this->father]->buildDOSTree($DOS-1,$currentGen+1);
            $fatherMaxHeight = $weights[0]+1;
            $this->topWeight = $this->topWeight + $weights[1];
            //$this->topWeight = max($this->topWeight,$gDOSEveryone[$this->father]->topWeight+1);
        }
        if($hasEmptyChild){
            for($i= 0; $i< count($this->children); $i++){
                $child = $this->children[$i];
                if($fromChildIndex == 0){
                    $fromChildIndex--;
                    continue;
                }
                $fromChildIndex--;

                // if $currentGen < 0, top weight still applies, but maxHeight doesn't.
                if($currentGen > 0){
                    $weights = $gDOSEveryone[$child]->buildDOSTree($DOS-1,$currentGen-1);
                    $childrenMaxHeght = max($childrenMaxHeght,$weights[0]);
                }
                //$this->topWeight = max($this->topWeight,$gDOSEveryone[$child]->topWeight);
            }
        }
        if($addSpouse){
            $weights = $gDOSEveryone[$this->spouse]->buildDOSTree($DOS,0);
            $spouseMaxHeight = $weights[0];
            $this->topHeight = max($this->topHeight,$spouseMaxHeight);
        }
        $max = max($this->topHeight,$motherMaxHeight,$fatherMaxHeight,$childrenMaxHeght,$spouseMaxHeight);
        //if($results2[0]->name->first == "N") echo $this->topHeight." ".$motherMaxHeight." ".$fatherMaxHeight." ".$childrenMaxHeght." ".$spouseMaxHeight."<br>";

        if(!$addSpouse) $this->rightWeight = max($this->dWeight/2,$gDOSEveryone[$this->mother]->dWeight-$this->dWeight/2,$this->topWeight/2);
        //if(!$addMother || !$addFather)

        echo $results2[0]->name->first." TopHeight:".$this->topHeight." Max: ".$max." Gen: ".$currentGen." TopWeight: ".$this->topWeight." dWeight: ".$this->dWeight." RightWeight: ".$this->rightWeight."\n<br>";


        return Array($max,$this->topWeight);
	}

    public function testAndVisit(){
        global $gVisitedCounter;
        if($this->visited < $gVisitedCounter){
            $this->visited++;
            return true;
        }
        else
            return false;
    }

    function handleDOSCondition(){
        global $gDOSEveryone;

        $addMother = ($gDOSEveryone[(String)$this->mother]->testAndVisit());
        $addFather = ($gDOSEveryone[(String)$this->father]->testAndVisit());
        $addSpouse = ($gDOSEveryone[(String)$this->spouse]->testAndVisit());

        if(!$addMother || !$addFather){
            $this->spouse = "";
            $this->children = array();
        }
        else if (!$addSpouse){
            $this->mother = "";
            $this->father = "";
        }
        else{
            $tempChildren = array();
            foreach($this->children as $child){
				if(!$gEveryone[(string)$child]->testAndVisit()){
                    $tempChilden[] = $child;
                    break;
                }
			}
            $this->children = $tempChilden;
            $this->father = "";
            $this->mother = "";
        }
    }
    
	public function printme(){
		echo "child of ".$this->father." and ".$this->mother.", spouse of ".$this->spouse." with".count($this->children)."children weighing".$this->weight."<br>";
	}
}

class Childless{
    public $ppl = array();
    public $size = 0;
    public $parentChildless = 1;

    public function __toString(){
        return implode(",",$this->ppl);
    }

    public function getWeight(){
        return $this->size;
    }
}
?>
