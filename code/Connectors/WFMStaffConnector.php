<?php

class WFMStaffConnector extends WFMConnectorBase {
 
    /**
     * This method returns all staff member stored in WorkflowMax.
     *
     * @throws Exceptions
     *
     * @return array list of staff members
     */
    function getStaffList() {
        $auth = $this->getAuthString();
        $service = $this->getRESTService();

        $response = $service->request("staff.api/list".$auth,"GET");

        $this->validateResponse($response);

        $staffs = $response->xpath("/Response/StaffList/Staff");
        $staffList = array();

        foreach($staffs as $staff) {
            $staff_item = array();
            $staff_item['ID'] = (string)$staff->ID;
            $staff_item['Name'] = (string)$staff->Name;
            $staff_item['Email'] = (string)$staff->Email;

            $staffList[] = $staff_item;
        }
        return $staffList;
    }

}