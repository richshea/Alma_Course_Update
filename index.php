<?php

require 'config.php';

echo '<html><head><style> 

table {
    border-collapse: collapse;
}

table, th, td {
    border: 1px solid rgba(0,0,0,0.4);
    padding:5px;
   
}

textarea{width:100%; height:200px;}
div{border: 1px solid black; padding:10px; margin:10px}

.check_if_dest_exists_and_update_or_add {
  background-color: rgba(0, 100, 100, 0.1);
} 
.check_if_dest_needs_update {
  background-color: rgba( 0, 0, 100, 0.1);
}  
.check_if_dest_needs_update {
  background-color: rgba( 0, 0, 100, 0.1);
}  
.check_all_dest_array_and_update_or_add {
  background-color: rgba( 0, 100, 100, 0.1);
}  
.create_dest_array {
  background-color: rgba( 0, 100, 100, 0.1);
}  
.create_source_array {
  background-color: rgba( 0, 100, 100, 0.1);
}  
.filter_source_array {
  background-color: rgba( 0, 100, 100, 0.1);
} 

.curl_error {
  background-color:rgb(100,0,0);
  color:white;
}

</style>
</head><body>';
//https://pageconfig.com/post/remove-undesired-characters-with-trim_all-php
function trim_all( $str )
{
    //replace all white-spaces and control chars with a single space
    return trim( preg_replace( "/[\\x00-\\x20]+/" , ' ' , $str ) );
}

class AlmaCourse
{
    public $code;
    public $name;
    public $section;
    public $academic_department;
    public $processing_department;
    public $term;
    public $start_date;
    public $end_date;
    public $weekly_hours;
    public $participants;
    public $year;
    public $instructor;
    public $searchable_id;
    public $note;
    public $reading_lists;
    public $status;

  
 
  public function __construct( $code ="", $name ="", $section ="", $academic_department ="", $processing_department ="", $term ="", $start_date ="", $end_date ="", $weekly_hours ="", $participants ="", $year ="", $instructor ="", $searchable_id ="", $note ="", $reading_lists ="", $status ="INACTIVE" )
      {
            $this->code = $code;
            $this->name = $name;
            $this->section = $section;
            $this->academic_department = $academic_department;
            $this->processing_department = $processing_department;
            $this->term = $term;
            $this->start_date = $start_date;
            $this->end_date = $end_date;
            $this->weekly_hours = $weekly_hours;
            $this->participants = $participants;
            $this->year = $year;
            $this->instructor = $instructor;
            $this->searchable_id = $searchable_id;
            $this->note = $note;
            $this->reading_lists = $reading_lists;
            $this->status = $status;
      }
}

class BrandeisCourse
{
    public $ClassNumber;
    public $Subject;
    public $CourseNumber;
    public $CourseTitle;
    public $Limit;
    public $Actual;
    public $WaitList;
    public $Consent;
    public $Instructor;
    public $Status;
    public $CourseCode;
    public $CourseSection;
    
    public function __construct($classnumber, $subject, $coursenumber, $coursetitle, $limit, $actual, $waitlist, $consent, $instructor, $status)
      {
        $this->ClassNumber =  $classnumber;
        $this->Subject =  $subject;
        $this->CourseNumber =  $coursenumber;
        $this->CourseTitle =  $coursetitle;
        $this->Limit =  $limit;
        $this->Actual =  $actual;
        $this->WaitList =  $waitlist;
        $this->Consent =  $consent;
        $this->Instructor =  $instructor;
        $this->Status =  $status;
        
        $CourseCodeSectionArray = explode(' ', $coursenumber);
        
        $this->CourseCode = $CourseCodeSectionArray[0];
        $this->CourseSection = $CourseCodeSectionArray[1];
      }
    
}
    
class CourseCreator
{
    public $SourceArray;
    public $FilteredSourceArray;
    public $DestArray;
    public $AlmaBaseUrl;
    public $AlmaApiKey;
    public $Semester;
    public $StartDate;
    public $EndDate;
    public $SourceCourseUrl;
    public $Debug;
    public $Year;
    public $YearSuffix;
    public $ProcessingDept;
    public $LastApiCall;
    public $reasonsList;
    
    public function wait_for_api_limit() {
        if ($this->Debug) {
            echo "<div class='wait_for_api_limit'>";
        }
        $currentTime = microtime(true);
        $timeSinceLastApiCall = $currentTime - $this->LastApiCall;
        while ($timeSinceLastApiCall < 0.06) {
            if ($this->Debug) {
                echo "<table><tr><th>CurrentTime</th><th>LastApiCall</th><th>Difference</th></tr>
                <tr><td>$currentTime</td><td>$this->LastApiCall</td><td>$timeSinceLastApiCall</td></tr></table>";
            }
            usleep(10000);
            $timeSinceLastApiCall = microtime(true) - $this->LastApiCall;
        }
        if ($this->Debug) {
            echo "</div>";
        }
    }
    
    public function send_api_request( $CURLOPT_URL, $xmlorjson, $GETPUTPOSTorDELETE, $POSTFIELDS, $try = 0) {
        
        if ($this->Debug) {
            echo "<div class='send_api_request'>";
            $requestArray = explode('apikey',$CURLOPT_URL);
            echo "sending api request:" . $requestArray[0] . ", $xmlorjson, $GETPUTPOSTorDELETE, <br />POSTFIELDS: <textarea>";
            echo print_r($POSTFIELDS);
            echo "</textarea><br>";
        }
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $CURLOPT_URL);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_HEADER, FALSE);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $GETPUTPOSTorDELETE);
        if ($POSTFIELDS != null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $POSTFIELDS);
        }
        if ($xmlorjson == 'xml' || $xmlorjson == 'json') {
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/'.$xmlorjson));
        }
        curl_setopt($ch, CURLOPT_FAILONERROR, true);
        
        $this->wait_for_api_limit();
        $response = curl_exec($ch);
        $this->LastApiCall = microtime(true);
        
        if(curl_error($ch)) {
            echo "<div class='curl_error'>error on try $try: <br />" . curl_error($ch) . "</div>";
            echo '<textarea>';
            echo print_r($response);
            echo '</textarea><br></div>';
            curl_close($ch);
            $response = "error";
            if ($try < 5) {
                $response = $this->send_api_request( $CURLOPT_URL, $xmlorjson, $GETPUTPOSTorDELETE, $POSTFIELDS, ++$try);
            } else {
                echo 'Failed 5 times. Giving up';
            }
            
        } else {
            if ($this->Debug) {
                /*
                echo "Update Request: <br><textarea>";
                print_r($almaCourseApiXML->asXML());
                echo "</textarea><br>";
                */
                echo "Request Response: <br><textarea>";
                print_r($response);
                echo "</textarea><br>";
            }
        }
        if ($this->Debug) {
            echo "</div>";
        }
        return $response;
    }
    
    public function create_source_array() {
        if ($this->Debug) {
            echo "<div class='create_source_array'>";
        }
        
        function rows($elements)
        {
            $str = "";
            foreach ($elements as $element) {
                $str .= $element->textContent."; ";
            }

            return $str;
        }
        
        if ($this->Debug) echo "create_source_array<br>";
        
        //pull HTML of registrar courses page into a string
        $mySourceHTMLString = file_get_contents($this->SourceCourseUrl);
        //if ($this->Debug) echo "<textarea>".$mySourceHTMLString."</textarea><br>";
        
        //create a DOMDocument object
        $mySourceHTML = new DOMDocument();
        
        //https://stackoverflow.com/questions/1148928/disable-warnings-when-loading-non-well-formed-html-by-domdocument-php
        libxml_use_internal_errors(true);
        
        //load string with HTML into the DOMDocument object.
        $mySourceHTML->loadHTML($mySourceHTMLString);
        libxml_clear_errors();
        $coursesList = $mySourceHTML->getElementById("classes-list");
        
        $coursesArray = $coursesList->getElementsByTagName('tr');
        
        //if ($this->Debug) echo "<textarea>".$mySourceHTML->saveHTML($coursesList)."</textarea><br>";
        
        $this->SourceArray = array();
        
        foreach ($coursesArray as $courseRecord) {
            $courseValues = $courseRecord->getElementsByTagName('td');
            array_push($this->SourceArray, new BrandeisCourse(
                trim_all($courseValues->item(0)->textContent),
                trim_all($courseValues->item(1)->textContent),
                trim_all($courseValues->item(2)->textContent),
                trim_all($courseValues->item(3)->textContent),
                trim_all($courseValues->item(4)->textContent),
                trim_all($courseValues->item(5)->textContent),
                trim_all($courseValues->item(6)->textContent),
                trim_all($courseValues->item(7)->textContent),
                trim_all($courseValues->item(8)->textContent),
                trim_all($courseValues->item(9)->textContent)
            ));
        }
        
        if ($this->Debug) {
            echo '<textarea>';
            foreach ($this->SourceArray as $myCourse) {
                echo print_r($myCourse)."<br>";
            }
            echo '</textarea><br>';
            echo "</div>";
        }
        
    }
    
    public function filter_source_array() {
        
        if ($this->Debug) {
            echo "<div class='filter_source_array'>";
            echo "filter_source_array<br>";
        }
        $this->FilteredSourceArray = array();
            
        foreach ($this->SourceArray as $brandeisCourse) {
            if ($brandeisCourse->ClassNumber != "" && $brandeisCourse->Subject != "PE") {
                    $duplicate = false;
                
                    foreach ($this->FilteredSourceArray as $brandeisCourseFiltered) {
                        if ($brandeisCourseFiltered->Instructor == $brandeisCourse->Instructor &&
                            $brandeisCourseFiltered->CourseCode == $brandeisCourse->CourseCode &&
                            $brandeisCourseFiltered->CourseSection != $brandeisCourse->CourseSection) {
                                $duplicate = true;
                                 break;
                        } 
                    }
                    if ($duplicate) {
                        if ($this->Debug) echo "skipping duplicate".$brandeisCourse->ClassNumber." ".$brandeisCourse->CourseTitle."<br>";
                    } else {
                        array_push($this->FilteredSourceArray, $brandeisCourse);                        
                    }
                
            }
        }
        
        if ($this->Debug) {
            echo '<textarea>';
            foreach ($this->FilteredSourceArray as $myCourse) {
                echo print_r($myCourse)."<br>";
            }
            echo '</textarea><br>';
            echo "</div>";
        }
        
    }
    
    public function create_dest_array() {
        
        if ($this->Debug) {
            echo "<div class='create_dest_array'>";
            echo "create_dest_array<br>";
        }
        
        $this->DestArray = array();
        
        if ($this->Semester == "SPRING") $semesterCode = 1;
        if ($this->Semester == "SUMMER") $semesterCode = 2;
        if ($this->Semester == "FALL") $semesterCode = 3;
        
        
        
        foreach ($this->FilteredSourceArray as $brandeisCourse) {
            
            $code = $this->YearSuffix . $semesterCode . $brandeisCourse->Subject . "-" 
                . $brandeisCourse->CourseCode . "-" . $brandeisCourse->CourseSection;
            $name = $brandeisCourse->CourseTitle;
            $section = "";
            $academic_department = new StdClass();
            $processing_department = (object) array("value" => $this->ProcessingDept);
            $term = array((object) array("value" => $this->Semester));
            $start_date = $this->StartDate;
            $end_date = $this->EndDate;
            $weekly_hours = 0;
            $participants = (int)$brandeisCourse->Actual;
            $year = $this->Year;
            $instructor = $this->get_instructors_array($brandeisCourse->Instructor, "json");
            $searchable_id = array($brandeisCourse->Instructor);
            $note = array();
            //$reading_lists = (object) ["reading_list" => [(object) ["code" => $code, "name" => $name, "status" => (object) ["value" => "BeingPrepared"], "due_back_date" => $end_date]]];
            
            array_push($this->DestArray, new AlmaCourse($code, $name, $section, $academic_department, $processing_department, 
                                                        $term, $start_date, $end_date, $weekly_hours, $participants, $year, 
                                                        $instructor, $searchable_id, $note, $reading_lists));
        } 
        
        if ($this->Debug) {
            echo '<textarea>';
            foreach ($this->DestArray as $myCourse) {
                echo print_r($myCourse)."<br>";
            }
            echo '</textarea><br>';
            echo "</div>";
        }
        
    }
    
    public function get_instructors_array( $Instructor, $jsonorxml ) {
        
        if ($jsonorxml == "json") {
            return array("instructor"=>(object) array("primary_id"=>"julie.bister", "first_name"=>"julie", "last_name"=>"bister"));
        }
        
        if ($jsonorxml == "xml") {
            return "<instructor><primary_id>julie.bister</primary_id><first_name>Julie</first_name><last_name>Bister</last_name></instructor>";   
        }
    }
    
    
    
    public function check_if_dest_exists_and_update_or_add( $almaCourse ) {
        $returnValue = "nocheck";
        
        
        if ($this->Debug) {
            echo "<div class='check_if_dest_exists_and_update_or_add'>";
            echo "Checking if exists: ".$almaCourse->code."<br>";
        }
        
        //$ch = curl_init();
        $queryParams = '?' . urlencode('q') . '=' . urlencode('code') . '~' . urlencode($almaCourse->code) . '&' . urlencode('limit') . '=' . urlencode('10') . '&' . urlencode('offset') . '=' . urlencode('0') . '&' . urlencode('order_by') . '=' . urlencode('code') . '&' . urlencode('direction') . '=' . urlencode('ASC') . '&' . urlencode('apikey') . '=' . urlencode($this->AlmaApiKey);
        
        
        $CURLOPT_URL = $this->AlmaBaseUrl . "/almaws/v1/courses" . $queryParams;
        $xmlorjson = null;
        $GETPUTPOSTorDELETE = 'GET';
        $POSTFIELDS = null;
        $response = $this->send_api_request( $CURLOPT_URL, $xmlorjson, $GETPUTPOSTorDELETE, $POSTFIELDS);
        
        if($response == 'error') {
            $returnValue = "error";
        } else {
            $responseXML = new SimpleXMLElement($response);
            $attributesArray = $responseXML->attributes();
            if ($attributesArray["total_record_count"] == 1) {
            
                if ($this->Debug) {
                    echo '<br>' . $almaCourse->code . ' exists as Course ID: ' . $responseXML->course->id . '<br>';
                    echo "Latest course information from Alma: <br><textarea>";
                    print_r(json_encode($responseXML->course));
                    echo "</textarea><br>";
                }
                //$returnValue = $this->delete_dest( $responseXML->course );
                
                if ($this->check_if_dest_needs_update( $almaCourse, $responseXML->course )) {
                    $returnValue = $this->update_dest( $responseXML->course );
                } else {
                    $returnValue = "nochange";
                }
                
            } else {
                    if ($this->Debug) {
                        echo '<br>' . $almaCourse->code . ' not in Alma <br>';
                    }
                    $returnValue = $this->add_dest( $almaCourse );
                    //$returnValue = 'nochange';
                     
            }
        }
        if ($this->Debug) {
            echo "</div>";
        }
        //curl_close($ch);
        return $returnValue;
        
        /*
        if ($this->Debug) {
            echo '<textarea>';
            echo print_r($response);
            echo '</textarea><br>';
        } 
        */ 
        
    }
    
    //This function checks and also changes the XML just obtained from the API
    public function check_if_dest_needs_update( $almaCourseRegistrar, &$almaCourseApiXML) {
        
        if ($this->Debug) {
            echo "<div class='check_if_dest_needs_update'>";
            echo "Checking if needs update: ".print_r($almaCourseRegistrar)."<br>";
        }
        
        $needsUpdate = false;
        $reason = "none";
        
        if ($almaCourseRegistrar->code != $almaCourseApiXML->code) {
            $needsUpdate = true;
            $reason = "Codes do not match<br>";
        }
        if ($almaCourseRegistrar->name != $almaCourseApiXML->name) {
            $needsUpdate = true;
            $reason = "Names do not match<br>";
        }
        if ($almaCourseRegistrar->term["value"] != $almaCourseApiXML->terms["term"]) {
            $needsUpdate = true;
            $reason = "Terms do not match<br>";
        }
        if ($almaCourseRegistrar->start_date != $almaCourseApiXML->start_date) {
            $needsUpdate = true;
            $reason = "Start Dates do not match<br>";
        }
        if ($almaCourseRegistrar->end_date != $almaCourseApiXML->end_date) {
            $needsUpdate = true;
            $reason = "End Dates do not match<br>";
        }
        if ($almaCourseRegistrar->participants != $almaCourseApiXML->participants) {
            $needsUpdate = true;
            $reason = "Participants do not match. Alma has: $almaCourseApiXML->participants Registrar has: $almaCourseRegistrar->participants<br>";
            $almaCourseApiXML->participants = $almaCourseRegistrar->participants;
        }
        if ($almaCourseRegistrar->year != $almaCourseApiXML->year) {
            $needsUpdate = true;
            $reason = "Years do not match<br>";
        }
        
        $this->reasonsList["$almaCourseApiXML->code"] = $reason;
        
        
        if ($almaCourseRegistrar->instructor != $almaCourseApiXML->instructors) {
            $needsUpdate = true;
            $reason = "Instructors do not match<br>";
            $almaCourseApiXML->instructors = $this->get_instructors_array($almaCourseRegistrar->instructor, "xml");
        }
        
        
        if ($this->Debug) {
            echo '<br>'. $almaCourseApiXML->id . ' needs update: ' ; 
            if ($needsUpdate) echo "true"; else echo "false"; 
            echo "<br>reason: $reason<br>instructors:". $almaCourseApiXML->instructors;
            echo "</div>";
        }
        
        return $needsUpdate;

        
    }
    
    public function update_dest( $almaCourseApiXML ) {
        
        if ($this->Debug) {
            echo "<div class='update_dest'>";
            echo "Updating: ".$almaCourseApiXML->id."<br>";
        }
        
        //$ch = curl_init();
        $queryParams = '/' . $almaCourseApiXML->id . '?' . urlencode('apikey') . '=' . urlencode($this->AlmaApiKey);
        
        
        $CURLOPT_URL = $this->AlmaBaseUrl . "/almaws/v1/courses" . $queryParams;
        $xmlorjson = 'xml';
        $GETPUTPOSTorDELETE = 'PUT';
        $POSTFIELDS = $almaCourseApiXML->asXML();
        $response = $this->send_api_request( $CURLOPT_URL, $xmlorjson, $GETPUTPOSTorDELETE, $POSTFIELDS);
        
        if($response == 'error') {
            return "error";
        } else {
            if ($this->Debug) {
                /*
                echo "Update Request: <br><textarea>";
                print_r($almaCourseApiXML->asXML());
                echo "</textarea><br>";
                */
                echo "Update Request Response: <br><textarea>";
                print_r($response);
                echo "</textarea><br>";
            }
        }
        if ($this->Debug) {
            echo "</div>";
        }
        return "updated";
    }
    
    public function add_dest( $almaCourse ) {
        
        if ($this->Debug) {
            echo "<div class='add_dest'>";
            echo "Adding Course: <br><textarea>";
            print_r(json_encode($almaCourse));
            echo "</textarea><br>";
        }
        
        $returnValue = 'impossible';
        //$ch = curl_init();
        $queryParams = '?' . urlencode('apikey') . '=' . urlencode($this->AlmaApiKey);
        
        
        $CURLOPT_URL = $this->AlmaBaseUrl . "/almaws/v1/courses" . $queryParams;
        $xmlorjson = 'json';
        $GETPUTPOSTorDELETE = 'POST';
        $POSTFIELDS = json_encode($almaCourse);
        $response = $this->send_api_request( $CURLOPT_URL, $xmlorjson, $GETPUTPOSTorDELETE, $POSTFIELDS);
        
        if($response == 'error') {
            $returnValue = "error";
        } else {
            $responseXML = new SimpleXMLElement($response);
            if ($this->Debug) {
                echo "Sending XML to add_reading_list: <br><textarea>";
                print_r($responseXML);
                echo "</textarea><br>";
            }
            $this->add_reading_list($responseXML);
            $returnValue = 'added';
        }
        //curl_close($ch);
        
        if ($this->Debug) {
            echo "</div>";
        }
        return $returnValue;
    }
    
    public function add_reading_list ( $almaCourseApiXML ) {
        $returnValue = 'impossible';
        if ($this->Debug) {
            echo "<div class='add_reading_list'>";
            echo "Adding reading list for: ".$almaCourseApiXML->id."<br>";
        }
        
        $reading_list = (object) array("code" => $almaCourseApiXML->code->__toString(), "name" => $almaCourseApiXML->name->__toString(), "status" => (object) array("value" => "BeingPrepared"), "due_back_date" => $this->EndDate);
        
        if ($this->Debug) {
                echo "Reading List object: <br><textarea>";
                print_r(json_encode($reading_list));
                echo "</textarea><br>";
        }
        
        //$ch = curl_init();
        $queryParams = '/' . $almaCourseApiXML->id . '/reading-lists?' . urlencode('apikey') . '=' . urlencode($this->AlmaApiKey);
        
        
        $CURLOPT_URL = $this->AlmaBaseUrl . "/almaws/v1/courses" . $queryParams;
        $xmlorjson = 'json';
        $GETPUTPOSTorDELETE = 'POST';
        $POSTFIELDS = json_encode($reading_list);
        $response = $this->send_api_request( $CURLOPT_URL, $xmlorjson, $GETPUTPOSTorDELETE, $POSTFIELDS);
        
        if($response == 'error') {
            $returnValue = "error";
        } else {
            if ($this->Debug) {
                echo "Reading List Add Request Response: <br><textarea>";
                print_r($response);
                echo "</textarea><br>";
            }
            
            $responseXML = new SimpleXMLElement($response);
            $returnValue = "reading list added";
        }
        if ($this->Debug) {
            echo "</div>";
        }
        return $returnValue;
    }
    
    public function delete_dest( $almaCourseApiXML ) {
        $returnValue = 'impossible';
        
        if ($this->Debug) {
            echo "<div class='delete_dest'>";
            echo "Deleting: ".$almaCourseApiXML->id."<br>";
        }
        
        
        //$ch = curl_init();
        $queryParams = '/' . $almaCourseApiXML->id . '?' . urlencode('apikey') . '=' . urlencode($this->AlmaApiKey);
        
        
        $CURLOPT_URL = $this->AlmaBaseUrl . "/almaws/v1/courses" . $queryParams;
        $xmlorjson = 'json';
        $GETPUTPOSTorDELETE = 'DELETE';
        $POSTFIELDS = null;
        $response = $this->send_api_request( $CURLOPT_URL, $xmlorjson, $GETPUTPOSTorDELETE, $POSTFIELDS);
        
        if($response == 'error') {
            $returnValue = "error";
        } else {
            if ($this->Debug) {
                echo "Delete Request Response: <br><textarea>";
                print_r($response);
                echo "</textarea><br>";
            }
            $returnValue = 'deleted';
        }
        if ($this->Debug) {
            echo "</div>";
        }
        return $returnValue;
    }
    
    public function check_all_dest_array_and_update_or_add() {
        echo "<div class='check_all_dest_array_and_update_or_add'>";
        if ($this->Debug) echo "check_all_dest_array_and_update_or_add<br>";
        $checked = 0;
        $updated = 0;
        $added = 0;
        $nochange = 0;
        $error = 0;
        $impossible = 0;
        $deleted = 0;
        $total = count($this->DestArray);
        
        $this->reasonsList = array();
        
        echo "<table><tr><th>checked</th><th>updated</th><th>added</th><th>deleted</th><th>nochange</th><th>error</th><th>impossible</th><th>code</th><th>action</th><th>reason</th></tr>";
        foreach ( $this->DestArray as $almaCourse ) {
            $this->reasonsList["$almaCourse->code"] = "none";
            $checkResponse = $this->check_if_dest_exists_and_update_or_add($almaCourse);
            switch ($checkResponse) {
                case "nocheck":
                    $impossible++;
                    break;
                case "added":
                    $checked++;
                    $added++;
                    break;
                case "updated":
                    $checked++;
                    $updated++;
                    break;
                case "error":
                    $error++;
                    break;
                case "nochange":
                    $checked++;
                    $nochange++;
                    break;
                case "deleted":
                    $checked++;
                    $deleted++;
                    break;
            }
            echo "<tr><td>$checked / $total</td><td>$updated</td><td>$added</td><td>$deleted</td><td>$nochange</td><td>$error</td><td>$impossible</td><td>$almaCourse->code</td><td>$checkResponse</td><td>" . $this->reasonsList["$almaCourse->code"] . "</td></tr>";
            flush();
            
        }
        echo "</table>";
        flush();
        
        echo "</div>";
    }
    
    public function __construct( $almabaseurl, $almaapikey, $semester, $startdate, $enddate, $sourcecourseurl, $year, $processingdept, $debug ) {
        $this->AlmaBaseUrl = $almabaseurl;
        $this->AlmaApiKey = $almaapikey;
        $this->Semester = $semester;
        $this->StartDate = $startdate;
        $this->EndDate = $enddate;
        $this->SourceCourseUrl = $sourcecourseurl;       
        $this->Year = $year;
        $this->YearSuffix = substr( $year, -2);
        $this->ProcessingDept = $processingdept;
        $this->Debug = $debug;
    }
}



if ($_GET["function"] == "updateAllCourses" && 
    (($_GET["cipher"] == $SANDCIPHER) && ($_GET["environment"] == "SANDBOX")) ||
    (($_GET["cipher"] == $PRODCIPHER) && ($_GET["environment"] == "PRODUCTION"))) {
    
    $environment = "";
    if ($_GET["environment"] == "SANDBOX") {
        $apiKey = $SANDBOX_API_KEY;
        $processingUnit = $PROD_PROC_UNIT;
    } 
    else if ($_GET["environment"] == "PRODUCTION") {
        $apiKey = $PRODUCTION_API_KEY;
        $processingUnit = $SAND_PROC_UNIT;
    } 

    $apiBase = htmlspecialchars($_GET["apiBase"]);
    
    $season = "";
    if ($_GET["season"] == "SPRING") {
        $season = "SPRING"; 
    } 
    else if ($_GET["season"] == "FALL") {
        $season = "FALL"; 
    }

    $enrollmentPage = "";
    if ($season == "SPRING") {
        $enrollmentPage = "http://www.brandeis.edu/registrar/SpringEnrollment.html";
    }
    else if ($season == "FALL") {
        $enrollmentPage = "http://www.brandeis.edu/registrar/FallEnrollment.html";
    }
    
     
    $startDate = htmlspecialchars($_GET["startDate"]);
    $endDate = htmlspecialchars($_GET["endDate"]);
    $year = substr($startDate,0,4);

    $debug = true;

    if ( empty($apiBase) || 
        empty($apiKey) ||
        empty($season) || 
        empty($startDate) ||
        empty($endDate) ||
        empty($enrollmentPage) || 
        empty($year) ||
        empty($processingUnit)) {
        
        echo '<h1>Job failed to start: Bad Parameters</h1>
                <table>
                    <tr><th>Parameter</th><th>Value</th></tr>
                    <tr><td>$apiBase</td><td>'.$apiBase.'</td></tr>
                    <tr><td>$apiKey</td><td>'.$apiKey.'</td></tr>
                    <tr><td>$season</td><td>'.$season.'</td></tr>
                    <tr><td>$startDate</td><td>'.$startDate.'</td></tr>
                    <tr><td>$endDate</td><td>'.$endDate.'</td></tr>
                    <tr><td>$enrollmentPage</td><td>'.$enrollmentPage.'</td></tr>
                    <tr><td>$year</td><td>'.$year.'</td></tr>
                    <tr><td>$processingUnit</td><td>'.$processingUnit.'</td></tr>
                    <tr><td>$debug</td><td>'.$debug.'</td></tr>
                </table>';
    }

    else {
        $myCourseCreator = new CourseCreator( $apiBase,$apiKey, $season, $startDate,$endDate,$enrollmentPage, $year, $processingUnit, $debug);

        $myCourseCreator->create_source_array();
        $myCourseCreator->filter_source_array();
        $myCourseCreator->create_dest_array();
        $myCourseCreator->check_all_dest_array_and_update_or_add();
    }

} else {
    
    $apiBase = "https://api-na.hosted.exlibrisgroup.com";
    $startDate = "2018-01-01Z";
    $endDate = "2018-05-09Z";
    
    
echo '
<form method="get" name="myForm">
<select name="function">
<option value="updateAllCourses">Update All Courses</option>
</select>
<select name="environment">
<option value="SANDBOX">Sandbox</option>
<option value="PRODUCTION">Production</option>
</select>
<select name="season">
<option value="SPRING">Spring</option>
<option value="FALL">Fall</option>
</select>
<label>API Base URL <input type="text" name="apiBase" value="'.$apiBase.'"></label>
<label>Semester Start Date<input type="text" name="startDate" value="'.$startDate.'"></label>
<label>Semester End Date<input type="text" name="endDate" value="'.$endDate.'"></label>
<label>Password<input type="text" name="cipher" value=""></label>
<input type="submit" value="Go">
</form>';
    
echo '
<form method="get" name="myFormDelete">
<select name="function">
<option value="DeleteCourses">Delete Courses</option>
</select>
<select name="environment">
<option value="SANDBOX">Sandbox</option>
<option value="PRODUCTION">Production</option>
</select>
<label>API Base URL <input type="text" name="apiBase" value="'.$apiBase.'"></label>
<label>Start Date<input type="text" name="startDate" value="'.$startDate.'"></label>
<label>End Date<input type="text" name="endDate" value="'.$endDate.'"></label>
<label>Code Prefix<input type="text" name="codePrefix" value=""></label>
<label>Password<input type="text" name="cipher" value=""></label>
<input type="submit" value="Go">
</form>';
    
}

echo '</body></html>';
    
    
?>