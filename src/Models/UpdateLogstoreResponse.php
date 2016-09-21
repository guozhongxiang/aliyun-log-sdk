<?php
namespace AliLog\Models;

/**
 * Copyright (C) Alibaba Cloud Computing
 * All rights reserved
 */

/**
 * The response of the UpdateLogstore API from log service.
 *
 * @author log service dev
 */
class Aliyun_Log_Models_UpdateLogstoreResponse extends Aliyun_Log_Models_Response {
    
    /**
     * Aliyun_Log_Models_UpdateLogstoreResponse constructor
     *
     * @param array $resp
     *            UpdateLogstore HTTP response body
     * @param array $header
     *            UpdateLogstore HTTP response header
     */
    public function __construct($resp, $header) {
        parent::__construct ( $header );
    }
    
}
