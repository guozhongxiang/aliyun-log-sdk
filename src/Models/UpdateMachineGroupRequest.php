<?php
namespace AliLog\Models;

/**
 * Copyright (C) Alibaba Cloud Computing
 * All rights reserved
 */

/**
 * 
 *
 * @author log service dev
 */
class Aliyun_Log_Models_UpdateMachineGroupRequest extends Aliyun_Log_Models_Request {

    private $machineGroup; 
    /**
     * Aliyun_Log_Models_UpdateMachineGroupRequest Constructor
     *
     */
    public function __construct($machineGroup) {
        $this->machineGroup = $machineGroup;
    }

    public function getMachineGroup(){
        return $this->machineGroup;
    }

    public function setMachineGroup($machineGroup){
        $this->machineGroup = $machineGroup;
    }

    
}
