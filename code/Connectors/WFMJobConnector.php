<?php

class WFMJobConnector extends WFMConnectorBase {

    /**
     * This method creates an php array out of an SimpleXMLElement job object.
     *
     * This method can create a single job record entry from the SimpleXMLElement 
     * object provided and create a simple PHP array object.
     *
     * @param SimpleXMLElement $xpath the xml path pointer to the current location of the job element in the XML document.
     *
     * @param boolean $onlyTrackableJobs true if only active projects should be shown, to filter completed, set this field to TRUE (default).
     *
     * @throws Exception 
     *
     * @return array a php array representation of the XML elements.
     */
     private function parseItem(SimpleXMLElement $xpath, $onlyTrackableJobs = true) {
        if ($xpath == null) {
            throw new Exception('Invalid method call. The XML element is empty.');
        }

        $item = array();
        $item['ID'] = (string)$job->ID;
        $item['Name'] = (string)$job->Name;
        $item['Description'] = (string)$job->Description;

        $item['ClientName'] = (string)$job->Client[0]->Name;

        $item['Budget'] = (float)$job->Budget;
        $item['ProjectType'] = (string)$job->Type;

        $item['State'] = (string)$job->State;
        $item['StartDate'] = (string)$job->StartDate;
        $item['DueDate'] = (string)$job->DueDate;

        $tasks = $job->xpath("Tasks/Task");
        $tasklists = array();
        foreach($tasks as $task) {
            $task_item = array();
            if (!$onlyTrackableJobs || $task->Completed == 'false') {
                $task_item['ID'] = (string)$task->ID;
                $task_item['Name'] = (string)$task->Name;
                $task_item['Description'] = (string)$task->Description;
                $task_item['EstimatedMinutes'] = (int)$task->EstimatedMinutes;
                $task_item['ActualMinutes'] = (int)$task->ActualMinutes;
                $task_item['Completed'] = (boolean)$task->Completed;
                $task_item['Billable'] = (boolean)$task->Billable;
                $tasklists[] = $task_item;
            }
        }
        $item['Tasks'] = $tasklists;
        return $item;        
    }
    
    /**
     * This method returns all actual jobs for a staff member.
     *
     * Staff members can track their time against projects they have been assigned to.
     * If a project is completed, time records can not be added to that project anymore.
     *
     * @param string $staffID WorkflowMax Staff ID
     *
     * @param boolean $onlyTrackableJobs true if only active projects should be shown, to filter completed, set this field to TRUE (default).
     *
     * @throws Exceptions
     *
     * @return array list of jobs
     */
    function getJobsOfStaff($staffID, $onlyTrackableJobs = true) {

        if (!$staffID) {
            throw new Exception("Madatory parameters missing. Requires a staff-ID, a from and to date.");
        } 

        $auth = $this->getAuthString();
        $service = $this->getRESTService();

        $response = $service->request("job.api/staff/".$staffID.$auth,"GET");

        // this method throws an expection and is passed on to its calling method.
        $this->validateResponse($response);

        $jobs = $response->xpath("/Response/Jobs/Job");
        $resultArray = array();
        
        foreach($jobs as $job) {
            $resultArray[] = parseItem($job, $onlyTrackableJobs);
        }
        return $resultArray;
    }

}