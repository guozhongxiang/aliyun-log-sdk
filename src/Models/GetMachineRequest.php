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
class Aliyun_Log_Models_GetMachineRequest extends Aliyun_Log_Models_Request {
    
    private $uuid;

    /**
     * Aliyun_Log_Models_GetMachineRequest Constructor
     *
     */
    public function __construct($uuid=null) {
        $this->uuid = $uuid;
    }

    public function getUuid(){
        return $this->uuid;
    }

    public function setUuid($uuid){
        $this->uuid = $uuid;
    }
    
}
