<?php

class WFMTimeConnector extends WFMConnectorBase {

    /**
     * This method creates an php array out of an SimpleXMLElement time object.
     *
     * This method can create a single time record entry from the SimpleXMLElement 
     * object provided and create a simple PHP array object.
     *
     * @param SimpleXMLElement $xpath the xml path pointer to the current location of the time element in the XML document.
     *
     * @throws Exception 
     *
     * @return array a php array representation of the XML elements.
     */
    private function parseItem(SimpleXMLElement $xpath) {

        if ($xpath == null) {
            throw new Exception('Invalid method call. The XML element is empty.');
        }

        $item = array();
        $item['ID'] = (string)$xpath->ID;
        $item['JobID'] = (string)$xpath->Job->ID;
        $item['JobName'] = (string)$xpath->Job->Name;
        $item['TaskID'] = (string)$xpath->Task->ID;
        $item['TaskName'] = (string)$xpath->Task->Name;
        $item['StaffID'] = (string)$xpath->Staff->ID;
        $item['StaffName'] = (string)$xpath->Staff->Name;
        $item['Date'] = (string)$xpath->Date;
        $item['Minutes'] = (string)$xpath->Minutes;
        $item['Billable'] = (string)$xpath->Billable;
        $item['Notes'] = (string)$xpath->Note;
        return $item;
    }

    /**
     * This method returns an array of already submitted time entries for a staff member over a selected period of time.
     *
     * All parameters are mandatory.
     *
     * @param string ID of the staff member (wfm staff-id)
     * @param string from date in the format YYYYMMDD
     * @param string to date in the format YYYYMMDD
     *
     * @throws Exception
     *
     * @return array list of time entries
     */
    function getTimeOFStaff($staffID, $from, $to) {        

        trigger_error("Please implement permission checks.", E_USER_NOTICE);

        if (!$staffID || !$from || !$to) {
            throw new Exception("Madatory parameters missing. Requires a staff-ID, a from and to date.");
        } 

        $url = sprintf("time.api/staff/%d%s&from=%s&to=%s",$staffID,$auth,$from,$to);

        $auth = $this->getAuthString();
        $service = $this->getRESTService();

        $response = $service->request($url,"GET");

        // this method throws an expection and is passed on to its calling method.
        $this->validateResponse($response);

        $list = array();
        $times = $response->xpath("/Response/Times/Time");

        // this loop may throw an exception if the XML is invalid. The exception
        // will not be caught and passed on to its calling method.
        foreach($times as $time) {
            $list[] = $this->parseItem($time);
        }
        return $list;
    }


    /**
     * This method adds a new time record entry.
     *
     * This method creates an xml request and send it to WorkflowMax to add a new
     * time entry. All parameters are mandatory.
     *
     * @param string jobID the ID to the jobs, i.e. 'JOB00003'
     * @param string taskID is the ID of the task to add the time against, i.e. '6487447'
     * @param string staffID is the WFM staff ID
     * @param string date the date of the day to add the time to, the format must be YYYMMDD, i.e. '20121218'
     * @param int minutes the amount of time in minute to add to the task
     * @param string note, added to the record.
     *
     * @throws Expection
     *
     * @return array a task array of the new created record
     *
     * Examples:  
     * - addTimeEntry('JOB00003','6487447','55918','20121218',60,"Test note's");
     *   Will track 60 minuntes for the 18 Dec 2012 against the non completed task 'Development' of the Afterhours mobile job.
     *
     * - addTimeEntry('J000011','5738118','55918','20121218',60,'Test note');
     *   This call with throw an exeption because the task has been closed (completed).
     */
    function addTimeEntry($jobID, $taskID, $staffID, $date, $minutes, $note) {
        
        trigger_error("Please implement permission checks.", E_USER_NOTICE);

        $xml = <<<XML
<Timesheet>
<Job>%s</Job>
<Task>%s</Task>
<Staff>%s</Staff>
<Date>%s</Date>
<Minutes>%d</Minutes>
<Note>%s</Note>
</Timesheet>
XML;
        $xml = sprintf($xml,$jobID, $taskID, $staffID, $date, $minutes, $note);

        $auth = $this->getAuthString();
        $service = $this->getRESTService();

        $url = sprintf("time.api/add/%s",$auth);
        $response = $service->request($url,"POST",$xml);

        // this method throws an expection and is passed on to its calling method.
        $this->validateResponse($response);

        $item = array();
        $times = $response->xpath("/Response/Time");

        // this loop may throw an exception if the XML is invalid. The exception
        // will not be caught and passed on to its calling method.
        foreach($times as $time) {
            $item = $this->parseItem($time);
        }
        return $item;
    }

    function updateTimeEntry($timeID, $jobID, $taskID, $staffID, $date, $minutes, $note) {
        trigger_error("Please implement permission checks.", E_USER_NOTICE);

        $xml = <<<XML
<Timesheet>
<ID>%s</ID>
<Job>%s</Job>
<Task>%s</Task>
<Staff>%s</Staff>
<Date>%s</Date>
<Minutes>%d</Minutes>
<Note>%s</Note>
</Timesheet>
XML;
        $xml = sprintf($xml,$timeID, $jobID, $taskID, $staffID, $date, $minutes, $note);

        $auth = $this->getAuthString();
        $service = $this->getRESTService();

        $url = sprintf("time.api/add/%s",$auth);
        $response = $service->request($url,"PUT",$xml);

        // this method throws an expection and is passed on to its calling method.
        $this->validateResponse($response);

        $item = array();
        $times = $response->xpath("/Response/Time");

        // this loop may throw an exception if the XML is invalid. The exception
        // will not be caught and passed on to its calling method.
        foreach($times as $time) {
            $item = $this->parseItem($time);
        }
        return $item;
    }

    /**
     * This method deletes a time entry from the WorkflowMax timesheets. 
     *
     * The time entry record needs to have the right state so that it can be deleted, i.e.
     * the job is not completed.
     *
     * @param string workflow-max time-id (i.e. retrieval via the get method or in the responses of the create method)
     *
     * @throws Exception
     *
     * @return string static message 'done' if the deletion was successful, otherwise it would have thrown an exception.
     */
    function deleteTimeEntry($timeID) {

        trigger_error("Please implement permission checks.", E_USER_NOTICE);

        $auth = $this->getAuthString();
        $service = $this->getRESTService();

        $url = sprintf("time.api/delete/%s%s",$timeID,$auth);
        $response = $service->request($url,'DELETE');

        // this method throws an expection and is passed on to its calling method.
        $this->validateResponse($response);

        return "Deleted";
    }

}