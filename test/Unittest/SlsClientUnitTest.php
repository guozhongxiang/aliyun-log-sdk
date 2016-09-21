<?php

require_once realpath(dirname(__FILE__) . '/../../Log_Autoload.php');

if(!defined('API_VERSION'))
    define('API_VERSION', '0.4.0');

class Aliyun_Log_Client_Mock extends Aliyun_Log_Client{
    
    public $gSendUrl;
    public $gSendbody;
    public $gSendHeader;
    public $gSendParam;
    
    public $gReturnJson;
    public $gReturnStatus;
   
    
    public function __construct($endpoint, $accessKeyId='', $accessKey='') {
        parent::__construct($endpoint, $accessKeyId,  $accessKey);
    }
    
    public function getEndpoint() {
        return $this->endpoint;
    }
    
    public function getSlsHost() {
        return $this->slsHost;
    }
    
    public function getaccessKeyId() {
        return $this->accessKeyId;
    }
    
    public function getaccessKey() {
        return $this->accessKey;
    }
    
    protected function GetGMT() {
        return 'Mon, 3 Jan 2010 08:33:47 GMT';
    }
    
    protected function GetHttpResponse($method, $url, $body, $headers) {
        $this->gSendUrl = $url;
        $this->gSendbody = $body;
        $this->gSendHeader = $headers;

        $headers['x-log-requestid'] = 'requestid';
        $response = array();
        $response[] = $this->gReturnStatus;
        $response[] = $headers;
        $response[] = $this->gReturnJson;
        return $response;
    }
}

function ReadIP($cmd) {
    system($cmd);
    $file_handle = fopen("ip.txt", "r");
    list ($line) = fscanf($file_handle, "%s");
    fclose($file_handle);
    return $line;
}

function LocalIP() {
    $cmd = "/sbin/ifconfig eth0 |grep \"inet addr\"| cut -f 2 -d \":\"|cut -f 1 -d \" \">ip.txt";
    $localIp = ReadIP($cmd);
    if ( ! $localIp ) {
        $cmd = "/sbin/ifconfig bond0 |grep \"inet addr\"| cut -f 2 -d \":\"|cut -f 1 -d \" \">ip.txt";
        $localIp = ReadIP($cmd);
    }
    return $localIp;
}

class Aliyun_Log_ClientUnittest extends PHPUnit_Framework_TestCase{

    function testConstruct() {
        $endpoint = 'http://cn-hangzhou.sls.aliyuncs.com/';
        $accessID = 'accessID';
        $accessKey = 'accessKey';
        $client = new Aliyun_Log_Client_Mock($endpoint, $accessID, $accessKey);
        
        $this->assertTrue( $client->getEndpoint()=='cn-hangzhou.sls.aliyuncs.com:80' );
        $this->assertTrue( $client->getSlsHost()=='cn-hangzhou.sls.aliyuncs.com' );
        $this->assertTrue( $client->getaccessKeyId()==$accessID );
        $this->assertTrue( $client->getaccessKey()==$accessKey );
    }
    
    function testSource() {
        $endpoint = 'cn-hangzhou.sls.aliyuncs.com';
        $AccessKey = '4fdO2fTDDnZPU/L7CHNdemB2Nsk=';
        $AccessKeyId = 'bq2sjzesjmo86kq35behupbq';
        $client = new Aliyun_Log_Client_Mock($endpoint, $AccessKeyId, $AccessKey);

        $client->gReturnJson = "{\"Code\" : \"ok\"}";
        $client->gReturnStatus = 200;
        
        $contents = array(
                'TestKey'=>'TestContent',
        );
        $logItem = new Aliyun_Log_Models_LogItem();
        $logItem->setTime(1405409656);
        $logItem->setContents($contents);
        $logitems = array($logItem);

        $project = 'big-game';
        $logstore = 'app_log';
        $topic = '';
        
        $request = new Aliyun_Log_Models_PutLogsRequest($project, $logstore, $topic, null, $logitems);
        $client->PutLogs($request);
        
        $expectHeaders = $client->gSendHeader;
        
        $request->setSource(LocalIP());
        $client->putLogs($request);
        
        $this->assertTrue($client->gSendHeader==$expectHeaders);
    }
    
    function testPutLogsExample() {
        $endpoint = 'cn-hangzhou.sls.aliyuncs.com';
        $AccessKey = '4fdO2fTDDnZPU/L7CHNdemB2Nsk=';
        $AccessKeyId = 'bq2sjzesjmo86kq35behupbq';
        $client = new Aliyun_Log_Client_Mock($endpoint, $AccessKeyId, $AccessKey);

        $client->gReturnJson = "{\"Code\" : \"ok\"}";
        $client->gReturnStatus = 200;
        
        $content = array( # a pair list, the key can be same
            'TestKey'=>'TestContent',
        );
        $logItem = new Aliyun_Log_Models_LogItem();
        $logItem->setTime(1405409656);
        $logItem->setContents($content);
        $logitems = array($logItem);

        $project = 'big-game';
        $logstore = 'app_log';
        $topic = '';
        $source = '10.230.201.117';
        $request = new Aliyun_Log_Models_PutLogsRequest($project, $logstore, $topic, $source, $logitems);
        $client->putLogs($request);
        
        $expectUrl = "http://big-game.cn-hangzhou.sls.aliyuncs.com:80/logstores/app_log";
        $expectBody = "x\x9c\xe3\x92\xe3\xf8\xb1i\xf2<V!1.\xf6\x90\xd4\xe2\x12\xef\xd4J!n\x10\xc39?\xaf$5\xafD\x8aA\x89\xcf\xd0@\xcf\xc8\x18\x88\r\x0c\xf5\x0c\r\xcd\x01}\xfe\r\xa1";
        $expectHeader = array(
            'x-log-apiversion' => API_VERSION, 
            'x-log-compresstype' => 'deflate', 
            'x-log-bodyrawsize' => 50, 
            'Content-Length' => 54, 
            'Content-MD5' => 'A6DD19A0A63FFAA37D8DC5F3C0244C05', 
            'Host' => 'big-game.cn-hangzhou.sls.aliyuncs.com', 
            'x-log-signaturemethod' => 'hmac-sha1', 
            'Date' => 'Mon, 3 Jan 2010 08:33:47 GMT', 
            'Content-Type' => 'application/x-protobuf', 
            'Authorization' => 'LOG bq2sjzesjmo86kq35behupbq:7p7vYln0GK06sBkBW+VI3xVmbo4='
        );
        
        $this->assertTrue( $client->gSendUrl==$expectUrl);
        $this->assertTrue( $client->gSendbody==$expectBody);
        $this->assertTrue( $client->gSendHeader==$expectHeader);
    }
    
    function testPutLogs() {
        $endpoint = 'cn-hangzhou.sls.aliyuncs.com';
        $AccessKeyId = 'mockKeyId';
        $AccessKey = 'mockKey';
        $client = new Aliyun_Log_Client_Mock($endpoint, $AccessKeyId, $AccessKey);

        $client->gReturnJson = "{\"Code\" : \"ok\"}";
        $client->gReturnStatus = 200;

        $contents = array(
                'value1' => '12',
                'value2' => '24',
                'value3' => '36',
                'value4' => '48',
                'avg' => '30'
        );
        $logItem = new Aliyun_Log_Models_LogItem();
        $logItem->setTime(1405409656);
        $logItem->setContents($contents);
        $logitems = array($logItem);
        
        $source = '10.230.201.117';
        $project = "mock_project";
        $logstore = "mock_logstore";
        $topic = "mock_topic";
        $request = new Aliyun_Log_Models_PutLogsRequest($project, $logstore, $topic, $source, $logitems);
        $client->putLogs($request);

        $expectUrl = 'http://mock_project.cn-hangzhou.sls.aliyuncs.com:80/logstores/mock_logstore';
        $expectBody = "x\x9c\xe3\xf2\xe4\xf8\xb1i\xf2<V!\x1e.\xb6\xb2\xc4\x9c\xd2TC!&C#8\xcfH\x88\xc9\xc8\x04\xce3\x16b26\x83\xf3L\x84\x98L,\x848\xb9\x98\x13\xcb\xd2\x81\x12\x06R\\\xb9\xf9\xc9\xd9\xf1%\xf9\x05\x99\xc9J|\x86\x06zF\xc6@l`\xa8ghh\x0e\x00\x0f\xf0\x18%";
        $expectHeader = array(
            'x-log-apiversion' => API_VERSION,
            'x-log-compresstype' => 'deflate',
            'x-log-bodyrawsize' => 103, 
            'Content-Length' => 87, 
            'Content-MD5' => '052AA1D39F84BD08578A499D839D8546', 
            'Host' => 'mock_project.cn-hangzhou.sls.aliyuncs.com', 
            'x-log-signaturemethod' => 'hmac-sha1', 
            'Date' => 'Mon, 3 Jan 2010 08:33:47 GMT', 
            'Content-Type' => 'application/x-protobuf', 
            'Authorization' => 'LOG mockKeyId:3u8AoqWMbAzeH1XuhUKMeNC9zBQ='
        );
        
        $this->assertTrue( $client->gSendUrl==$expectUrl);
        $this->assertTrue( $client->gSendbody==$expectBody);
        $this->assertTrue( $client->gSendHeader==$expectHeader);
    }
    
    function testPutLogsWithRawIP() {
        $expectUrl = 'http://100.230.201.117:9000/logstores/mock_logstore';
        $expectBody = "x\x9c\xe3\xf2\xe4\xf8\xb1i\xf2<V!\x1e.\xb6\xb2\xc4\x9c\xd2TC!&C#8\xcfH\x88\xc9\xc8\x04\xce3\x16b26\x83\xf3L\x84\x98L,\x848\xb9\x98\x13\xcb\xd2\x81\x12\x06R\\\xb9\xf9\xc9\xd9\xf1%\xf9\x05\x99\xc9J|\x86\x06zF\xc6@l`\xa8ghh\x0e\x00\x0f\xf0\x18%";
        $expectHeader = array(
                'x-log-apiversion' => API_VERSION,
                'x-log-compresstype' => 'deflate',
                'x-log-bodyrawsize' => 103,
                'Content-Length' => 87,
                'Content-MD5' => '052AA1D39F84BD08578A499D839D8546',
                'Host' => 'mock_project.100.230.201.117',
                'x-log-signaturemethod' => 'hmac-sha1',
                'Date' => 'Mon, 3 Jan 2010 08:33:47 GMT',
                'Content-Type' => 'application/x-protobuf',
                'Authorization' => 'LOG mockKeyId:3u8AoqWMbAzeH1XuhUKMeNC9zBQ='
        );
        
        $endpoint = '100.230.201.117:9000';
        $AccessKeyId = 'mockKeyId';
        $AccessKey = 'mockKey';
        $client = new Aliyun_Log_Client_Mock($endpoint, $AccessKeyId, $AccessKey);

        $client->gReturnJson = "{\"Code\" : \"ok\"}";
        $client->gReturnStatus = 200;
        
        $contents = array(
                'value1' => '12',
                'value2' => '24',
                'value3' => '36',
                'value4' => '48',
                'avg' => '30'
        );
        $logItem = new Aliyun_Log_Models_LogItem();
        $logItem->setTime(1405409656);
        $logItem->setContents($contents);
        $logitems = array($logItem);

        $project = "mock_project";
        $logstore = "mock_logstore";
        $topic = "mock_topic";
        $source = '10.230.201.117';
        $request = new Aliyun_Log_Models_PutLogsRequest($project, $logstore, $topic, $source, $logitems);
        $client->putLogs($request);
        $this->assertTrue( $client->gSendUrl==$expectUrl);
        $this->assertTrue( $client->gSendbody==$expectBody);
        $this->assertTrue( $client->gSendHeader==$expectHeader);
    }
    
    function testListLogstores() {
        $endpoint = 'cn-hangzhou.sls.aliyuncs.com';
        $AccessKeyId = 'mockKeyId';
        $AccessKey = 'mockKey';
        $client = new Aliyun_Log_Client_Mock($endpoint, $AccessKeyId, $AccessKey);
        
        $client->gReturnJson = '{"count":1,"logstores":["access_log"]}';
        $client->gReturnStatus = 200;
        
        $project = "mock_project";
        $request = new Aliyun_Log_Models_ListLogstoresRequest($project);
        $logstore = $client->listLogstores($request);
        
        $expectUrl = "http://mock_project.cn-hangzhou.sls.aliyuncs.com:80/logstores";
        $expectHeader = array(
            'x-log-apiversion' => API_VERSION,
            'Content-Length' => 0,
            'x-log-bodyrawsize' => 0,
            'Host' => 'mock_project.cn-hangzhou.sls.aliyuncs.com',
            'x-log-signaturemethod' => 'hmac-sha1',
            'Date' => 'Mon, 3 Jan 2010 08:33:47 GMT',
            'Content-Type' => '', 
            'Authorization' => "LOG mockKeyId:denlH7KcBdbkr2h1//xKm9Mdt/s="
        );
        $expectBody = null;

        $this->assertTrue( $client->gSendUrl==$expectUrl);
        $this->assertTrue( $client->gSendbody==$expectBody);
        $this->assertTrue( $client->gSendHeader==$expectHeader);
        $this->assertTrue($logstore->getCount()==1);
        $this->assertTrue(count($logstore->getLogstores())==1);
        $this->assertTrue($logstore->getLogstores()[0]=="access_log");
    }
    
    function testListTopics() {
        $endpoint = 'cn-hangzhou.sls.aliyuncs.com';
        $AccessKeyId = 'mockKeyId';
        $AccessKey = 'mockKey';
        $client = new Aliyun_Log_Client_Mock($endpoint, $AccessKeyId, $AccessKey);
        
        $client->gReturnJson = '{"count":1,"topics":["TestTopic"]}';
        $client->gReturnStatus = 200;
        
        $project = "mock_project";
        $logstore = "mock_logstore";
        $request = new Aliyun_Log_Models_ListTopicsRequest($project, $logstore);
        $topic = $client->listTopics($request);
        
        $expectUrl = "http://mock_project.cn-hangzhou.sls.aliyuncs.com:80/logstores/mock_logstore?type=topic";
        $expectHeader = array(
            'x-log-apiversion' => API_VERSION, 
            'Content-Length' => 0, 
            'x-log-bodyrawsize' => 0, 
            'Host' => 'mock_project.cn-hangzhou.sls.aliyuncs.com', 
            'x-log-signaturemethod' => 'hmac-sha1', 
            'Date' => 'Mon, 3 Jan 2010 08:33:47 GMT', 
            'Content-Type' => '', 
            'Authorization' => 'LOG mockKeyId:RS010USmMLo4uwPcEDNFVpXOiyI='
        );
        $expectBody = null;
        
        $this->assertTrue( $client->gSendUrl==$expectUrl);
        $this->assertTrue( $client->gSendbody==$expectBody);
        $this->assertTrue( $client->gSendHeader==$expectHeader);
        
        $this->assertTrue( $topic->getCount()==1);
        $this->assertTrue( count($topic->getTopics())==1);
        $this->assertTrue( $topic->getTopics()[0]=='TestTopic');
    }
    
    function testGetLogs() {
        $endpoint = 'cn-hangzhou.sls.aliyuncs.com';
        $AccessKeyId = 'mockKeyId';
        $AccessKey = 'mockKey';
        $client = new Aliyun_Log_Client_Mock($endpoint, $AccessKeyId, $AccessKey);
        
        $client->gReturnJson = '{"count":2,"logs":[{"Key1":"Value1","__source__":"10.101.166.89","__time__":"1412906016"},{"Key2":"Value2","__source__":"192.168.179.1","__time__":"1412909285"}],"progress":"Complete"}';
        $client->gReturnStatus = 200;
        
        $project = "mock_project";
        $logstore = "mock_logstore";
        $topic = "mock_topic";
        $from = 1351052600;
        $to = 1351052630;
        $reverse = True;
        $lines = 23;
        $offset = 3;
        $query = "mock_query";
        
        $request = new Aliyun_Log_Models_GetLogsRequest($project, $logstore, $from, $to, $topic);
        $logs = $client->getLogs($request);
        
        $expectUrl = "http://mock_project.cn-hangzhou.sls.aliyuncs.com:80/logstores/mock_logstore?from=1351052600&to=1351052630&topic=mock_topic&type=log";
        $expectHeader = array(
            'x-log-apiversion' => API_VERSION, 
            'Content-Length' => 0, 
            'x-log-bodyrawsize' => 0, 
            'Host' => 'mock_project.cn-hangzhou.sls.aliyuncs.com', 
            'x-log-signaturemethod' => 'hmac-sha1', 
            'Date' => 'Mon, 3 Jan 2010 08:33:47 GMT', 
            'Content-Type' => '', 
            'Authorization' => 'LOG mockKeyId:ZrPVOBM1Gm6k8+ZjETfQ0VB2YyU='
        );
        $expectBody = null;
        
        $this->assertTrue($client->gSendUrl==$expectUrl);
        $this->assertTrue($client->gSendbody==$expectBody);
        $this->assertTrue($client->gSendHeader==$expectHeader);
        
        $this->assertTrue($logs->getCount()==2);
        $this->assertTrue($logs->isCompleted()==True);
        $this->assertTrue(count($logs->getLogs())==2);
        
        $log0 = $logs->getLogs()[0];
        $this->assertTrue($log0->getContents()==array('Key1' => 'Value1'));
        $this->assertTrue($log0->getTime()==1412906016);
        $this->assertTrue($log0->getSource()=='10.101.166.89');
        
        $log1 = $logs->getLogs()[1];
        $this->assertTrue($log1->getContents()==array('Key2' => 'Value2'));
        $this->assertTrue($log1->getTime()==1412909285);
        $this->assertTrue($log1->getSource()=='192.168.179.1');
        
        
        $request = new Aliyun_Log_Models_GetLogsRequest($project, $logstore, $from, $to, $topic, 
                $query, $lines, $offset, $reverse);
        $logs = $client->getLogs($request);
        
        $expectUrl = "http://mock_project.cn-hangzhou.sls.aliyuncs.com:80/logstores/mock_logstore?from=1351052600&line=23&offset=3&query=mock_query&reverse=true&to=1351052630&topic=mock_topic&type=log";
        $expectHeader = array(
            'x-log-apiversion' => API_VERSION, 
            'Content-Length' => 0, 
            'x-log-bodyrawsize' => 0, 
            'Host' => 'mock_project.cn-hangzhou.sls.aliyuncs.com', 
            'x-log-signaturemethod' => 'hmac-sha1', 
            'Date' => 'Mon, 3 Jan 2010 08:33:47 GMT', 
            'Content-Type' => '', 
            'Authorization' => 'LOG mockKeyId:ZYKtnYYr6w0uCwY/yUZHm/6jXzQ='
        );
        $expectBody = null;
        
        $this->assertTrue($client->gSendUrl==$expectUrl);
        $this->assertTrue($client->gSendbody==$expectBody);
        $this->assertTrue($client->gSendHeader==$expectHeader);
        
        $this->assertTrue($logs->getCount()==2);
        $this->assertTrue($logs->isCompleted()==True);
        $this->assertTrue(count($logs->getLogs())==2);
        
        $log0 = $logs->getLogs()[0];
        $this->assertTrue($log0->getContents()==array('Key1' => 'Value1'));
        $this->assertTrue($log0->getTime()==1412906016);
        $this->assertTrue($log0->getSource()=='10.101.166.89');
        
        $log1 = $logs->getLogs()[1];
        $this->assertTrue($log1->getContents()==array('Key2' => 'Value2'));
        $this->assertTrue($log1->getTime()==1412909285);
        $this->assertTrue($log1->getSource()=='192.168.179.1');
    }
    
    function testGetHistograms() {
        $endpoint = 'http://cn-hangzhou.sls.aliyuncs.com/';
        $AccessKeyId = 'mockKeyId';
        $AccessKey = 'mockKey';
        $client = new Aliyun_Log_Client_Mock($endpoint, $AccessKeyId, $AccessKey);

        $client->gReturnJson = '{"count":2,"histograms":[{"count":0,"from":1412900280,"progress":"Complete","to":1412901000},{"count":0,"from":1412901000,"progress":"Complete","to":1412902800}],"progress":"Complete"}';
        $client->gReturnStatus = 200;
        
        $project = "mock_project";
        $logstore = "mock_logstore";
        $topic = "mock_topic";
        $from = 1351052600;
        $to = 1351052630;
        $query = "mock_query";
        
        $request = new Aliyun_Log_Models_GetHistogramsRequest($project, $logstore, $from, $to, $topic);
        $status = $client->getHistograms($request);
        
        $expectUrl = "http://mock_project.cn-hangzhou.sls.aliyuncs.com:80/logstores/mock_logstore?from=1351052600&to=1351052630&topic=mock_topic&type=histogram";
        $expectHeader = array(
                'x-log-apiversion' => API_VERSION,
                'Content-Length' => 0,
                'x-log-bodyrawsize' => 0,
                'Host' => 'mock_project.cn-hangzhou.sls.aliyuncs.com',
                'x-log-signaturemethod' => 'hmac-sha1',
                'Date' => 'Mon, 3 Jan 2010 08:33:47 GMT',
                'Content-Type' => '', 
                'Authorization' => 'LOG mockKeyId:k7hGL4KRiKNbdRp0/7U2CR3P5Oc='
        );
        $expectBody = null;

        $this->assertTrue($client->gSendUrl==$expectUrl);
        $this->assertTrue($client->gSendbody==$expectBody);
        $this->assertTrue($client->gSendHeader==$expectHeader);
        
        $this->assertTrue($status->isCompleted()==True);
        $this->assertTrue($status->getTotalCount()==2);
        
        $histograms0 = $status->getHistograms()[0];
        $this->assertTrue($histograms0->getFrom()==1412900280);
        $this->assertTrue($histograms0->getTo()==1412901000);
        $this->assertTrue($histograms0->getCount()==0);
        $this->assertTrue($histograms0->isCompleted()==True);

        $histograms1 = $status->getHistograms()[1];
        $this->assertTrue($histograms1->getFrom()==1412901000);
        $this->assertTrue($histograms1->getTo()==1412902800);
        $this->assertTrue($histograms1->getCount()==0);
        $this->assertTrue($histograms1->isCompleted()==True);
        
        
        $request->setQuery($query);
        $status = $client->getHistograms($request);
         
        $expectUrl = "http://mock_project.cn-hangzhou.sls.aliyuncs.com:80/logstores/mock_logstore?from=1351052600&query=mock_query&to=1351052630&topic=mock_topic&type=histogram";
        $expectHeader = array(
                'x-log-apiversion' => API_VERSION,
                'Content-Length' => 0,
                'x-log-bodyrawsize' => 0,
                'Host' => 'mock_project.cn-hangzhou.sls.aliyuncs.com',
                'x-log-signaturemethod' => 'hmac-sha1',
                'Date' => 'Mon, 3 Jan 2010 08:33:47 GMT',
                'Content-Type' => '',
                'Authorization' => 'LOG mockKeyId:HgizTLWNsIavlTj3sncDt7DynmI='
        );
        $expectBody = null;
        
        $this->assertTrue($client->gSendUrl==$expectUrl);
        $this->assertTrue($client->gSendbody==$expectBody);
        $this->assertTrue($client->gSendHeader==$expectHeader);
         
        $this->assertTrue($status->isCompleted()==True);
        $this->assertTrue($status->getTotalCount()==2);
         
        $histograms0 = $status->getHistograms()[0];
        $this->assertTrue($histograms0->getFrom()==1412900280);
        $this->assertTrue($histograms0->getTo()==1412901000);
        $this->assertTrue($histograms0->getCount()==0);
        $this->assertTrue($histograms0->isCompleted()==True);
        
        $histograms1 = $status->getHistograms()[1];
        $this->assertTrue($histograms1->getFrom()==1412901000);
        $this->assertTrue($histograms1->getTo()==1412902800);
        $this->assertTrue($histograms1->getCount()==0);
        $this->assertTrue($histograms1->isCompleted()==True);
    }


    //0.5.0 add
    function testBatchGetLogs() {
        $endpoint = 'http://cn-hangzhou.sls.aliyuncs.com/';
        $AccessKeyId = 'mockKeyId';
        $AccessKey = 'mockKey';
        $client = new Aliyun_Log_Client_Mock($endpoint, $AccessKeyId, $AccessKey);
        
        $project = "mock_project";
        $logstore = "mock_logstore";
        $shardId = "mock_shardId";
        $cursor = "mock_cursor";
        $count = "mock_count";
        $topic = "mock_topic";
        $topic2 = "mock_topic2";
        $time1 = 1451052630;
        $time2 = 1451052635;
      
        $log_content = new Log_Content();
        $log = new Log();
        $logGroup = new LogGroup();
        $logPackageList = new LogPackageList();
        
        $logGroup->setTopic($topic);
        $logGroup->setSource(LocalIP());
        $logGroup->setReserved($logstore);
        
        $log->setTime($time1);
        $log_content->setKey('key1');$log_content->setValue('value1');
        $log->setContents(0, $log_content);
        $log_content->setKey('key2');$log_content->setValue('value2');
        $log->setContents(1, $log_content);
        $logGroup->setLogs(0, $log);
        
        $log->setTime($time2);
        $log_content->setKey('key21');$log_content->setValue('value21');
        $log->setContents(0, $log_content);
        $log_content->setKey('key22');$log_content->setValue('value22');
        $log->setContents(1, $log_content);
        $logGroup->setLogs(1, $log);
        
        $logPackageData = Aliyun_Log_Util::toBytes($logGroup);
        $logPackageData = gzcompress($logGroup);
        $logPackage = new LogPackage();
        $logPackage->setData($logPackageData);
        $logPackageList->addPackages($logPackage);

        $logGroup->setTopic($topic2);
        $logPackageData = Aliyun_Log_Util::toBytes($logGroup);
        $logPackage = new LogPackage();
        $logPackage->setData($logPackageData);
        $logPackageList->addPackages($logPackage);
        
        $client->gReturnStatus = 200;
        $client->gReturnJson = Aliyun_Log_Util::toBytes($logPackageList); 
 

        $request = new Aliyun_Log_Models_BatchGetLogsRequest($project, $logstore, $shardId, $count, $cursor);
        $response = $client->batchGetLogs($request);
        
        $headers = array(
            'Accept-Encoding'=>'gzip',
            'accept'=>'application/x-protobuf'
        );
        $expectUrl = "http://mock_project.cn-hangzhou.sls.aliyuncs.com:80/logstores/mock_logstore/shards/mock_shardId?count=mock_count&cursor=mock_cursor&type=log";
        $expectHeader = array(
            'x-log-apiversion' => API_VERSION,
            'Content-Length' => 0,
            'x-log-bodyrawsize' => 0,
            'Host' => 'mock_project.cn-hangzhou.sls.aliyuncs.com',
            'x-log-signaturemethod' => 'hmac-sha1',
            'Date' => 'Mon, 3 Jan 2010 08:33:47 GMT',
            'Content-Type' => '',
            'Authorization' => 'LOG mockKeyId:HNXvYzxa9XHIn86LfVwJsXPTC2E=',
            'Accept-Encoding'=>'gzip',
            'accept'=>'application/x-protobuf'
        );
        $expectBody = null;
        $this->assertTrue($client->gSendUrl==$expectUrl);
        $this->assertTrue($client->gSendbody==$expectBody);
        $this->assertTrue($client->gSendHeader==$expectHeader);
        
        $this->assertTrue($response->getCount()==2);
        $list =  array();
        $list = $response->getLogGroupList();
        $log = $logGroup->getLogs(0);
        $log_time = $log->getTime();
        $this->assertTrue($log_time==$time2);
        
    }
    
    function testListShards() {
        $endpoint = 'http://cn-hangzhou.sls.aliyuncs.com/';
        $AccessKeyId = 'mockKeyId';
        $AccessKey = 'mockKey';
        $client = new Aliyun_Log_Client_Mock($endpoint, $AccessKeyId, $AccessKey);
        $client->gReturnStatus = 200;      
        $project = "mock_project";
        $logstore = "mock_logstore";
        $shardId = "3";
        $cursor = "mock_cursor";
        $count = 100;
        $topic = "mock_topic";
        $topic2 = "mock_topic2";
        $time1 = 1451052630;
        $time2 = 1451052635;        
        $client->gReturnJson = "[{\"shardID\":0},{\"shardID\":1}]";
        
        $request = new Aliyun_Log_Models_ListShardsRequest($project, $logstore);
        $response = $client->listShards($request);
        
        $expectUrl = "http://mock_project.cn-hangzhou.sls.aliyuncs.com:80/logstores/mock_logstore/shards";
        $expectHeader = array(
            'x-log-apiversion' => API_VERSION,
            'Content-Length' => 0,
            'x-log-bodyrawsize' => 0,
            'Host' => 'mock_project.cn-hangzhou.sls.aliyuncs.com',
            'x-log-signaturemethod' => 'hmac-sha1',
            'Date' => 'Mon, 3 Jan 2010 08:33:47 GMT',
            'Content-Type' => '',
            'Authorization' => 'LOG mockKeyId:O2kd2QSut/hGlijnJT1Jjlvj04E='
        );
        $expectBody = null;
        
        $this->assertTrue($client->gSendUrl==$expectUrl);
        $this->assertTrue($client->gSendbody==$expectBody);
        $this->assertTrue($client->gSendHeader==$expectHeader);
        
        $this->assertTrue(count($response->getShardIds())==2);
       
    }
    
    function testGetCursor() {
        $endpoint = 'http://cn-hangzhou.sls.aliyuncs.com/';
        $AccessKeyId = 'mockKeyId';
        $AccessKey = 'mockKey';
        $client = new Aliyun_Log_Client_Mock($endpoint, $AccessKeyId, $AccessKey);
        $client->gReturnStatus = 200;
        $project = "mock_project";
        $logstore = "mock_logstore";
        $shardId = "3";
        $cursor = "mock_cursor";
        $time1 = 1451052630;
        
        $client->gReturnJson = "{\"cursor\":\"MTQzNjQyNTUwNDcxMzAyMQ\"}";
        
        $request = new Aliyun_Log_Models_GetCursorRequest($project, $logstore,$shardId,'begin');
        $response = $client->getCursor($request);
        $expectUrl = "http://mock_project.cn-hangzhou.sls.aliyuncs.com:80/logstores/mock_logstore/shards/3?mode=begin&type=cursor";
        $expectHeader = array(
            'x-log-apiversion' => API_VERSION,
            'Content-Length' => 0,
            'x-log-bodyrawsize' => 0,
            'Host' => 'mock_project.cn-hangzhou.sls.aliyuncs.com',
            'x-log-signaturemethod' => 'hmac-sha1',
            'Date' => 'Mon, 3 Jan 2010 08:33:47 GMT',
            'Content-Type' => '',
            'Authorization' => 'LOG mockKeyId:VhVwDaP21zq+gHCG/gTMw+DAI5Y='
        );
        $expectBody = null;
        $this->assertTrue($client->gSendUrl==$expectUrl);
        $this->assertTrue($client->gSendbody==$expectBody);
        $this->assertTrue($client->gSendHeader==$expectHeader);
        
        $request = new Aliyun_Log_Models_GetCursorRequest($project, $logstore,$shardId,null,$time1);
        $response = $client->getCursor($request); 
        $expectUrl = "http://mock_project.cn-hangzhou.sls.aliyuncs.com:80/logstores/mock_logstore/shards/3?from=1451052630&type=cursor";
        $expectHeader = array(
            'x-log-apiversion' => API_VERSION,
            'Content-Length' => 0,
            'x-log-bodyrawsize' => 0,
            'Host' => 'mock_project.cn-hangzhou.sls.aliyuncs.com',
            'x-log-signaturemethod' => 'hmac-sha1',
            'Date' => 'Mon, 3 Jan 2010 08:33:47 GMT',
            'Content-Type' => '',
            'Authorization' => 'LOG mockKeyId:4qtlrig8fJqK4ft+nrKocamEIAM='
        );
        $expectBody = null;
        
        $this->assertTrue($client->gSendUrl==$expectUrl);
        $this->assertTrue($client->gSendbody==$expectBody);
        $this->assertTrue($client->gSendHeader==$expectHeader);
        $this->assertTrue($response->getCursor()=='MTQzNjQyNTUwNDcxMzAyMQ');
    }

    function testCreateConfig() {
        $endpoint = 'http://cn-hangzhou.sls.aliyuncs.com/';
        $AccessKeyId = 'mockKeyId';
        $AccessKey = 'mockKey';
        $client = new Aliyun_Log_Client_Mock($endpoint, $AccessKeyId, $AccessKey);
        $client->gReturnStatus = 200;
        $client->gReturnJson = null;


        $configName='conf_ll2';
        $inputDetail = new Aliyun_Log_Models_Config_InputDetail('access2.log',array('ip','time'),true,"\\d+\\.\\d",'/apsarapangu/abc','common_reg_log',"([\\d\\.]+)","%d/%b/%Y:%H:%M:%S",array(),array(),'none');
        $outputDetail = new Aliyun_Log_Models_Config_OutputDetail('ay42','perfcounter');
        $config = new Aliyun_Log_Models_Config($configName,'file',$inputDetail,'LogService',$outputDetail);
        $outputDetail = new Aliyun_Log_Models_Config_OutputDetail('ay42','perfcounter');
        $config = new Aliyun_Log_Models_Config($configName,'file',$inputDetail,'LogService',$outputDetail);
        $request = new Aliyun_Log_Models_CreateConfigRequest($config);

        $response = $client->createConfig($request);
        $expectUrl = "http://cn-hangzhou.sls.aliyuncs.com:80/configs";
        $expectHeader = array(
            'x-log-apiversion' => API_VERSION,
            'Content-Length' => 411,
            'x-log-bodyrawsize' => 0,
            'Content-MD5' => '79BDAB8611BC1885A67B8E1A948582F8',
            'Host' => 'cn-hangzhou.sls.aliyuncs.com',
            'x-log-signaturemethod' => 'hmac-sha1',
            'Date' => 'Mon, 3 Jan 2010 08:33:47 GMT',
            'Content-Type' => 'application/json',
            'Authorization' => 'LOG mockKeyId:gTKz77J0KydYot6wCXEws12uGi8='
        );
        $expectBody ="{\"configName\":\"conf_ll2\",\"inputType\":\"file\",\"inputDetail\":{\"filePattern\":\"access2.log\",\"key\":[\"ip\",\"time\"],\"localStorage\":true,\"logBeginRegex\":\"\\\\d+\\\\.\\\\d\",\"logPath\":\"\/apsarapangu\/abc\",\"logType\":\"common_reg_log\",\"regex\":\"([\\\\d\\\\.]+)\",\"timeFormat\":\"%d\/%b\/%Y:%H:%M:%S\",\"filterRegex\":[],\"filterKey\":[],\"topicFormat\":\"none\"},\"outputType\":\"LogService\",\"outputDetail\":{\"projectName\":\"ay42\",\"logstoreName\":\"perfcounter\"}}";

        $this->assertTrue($client->gSendUrl==$expectUrl);
        $this->assertTrue($client->gSendbody==$expectBody);
        $this->assertTrue($client->gSendHeader==$expectHeader);

    }
    function testUpdateConfig() {
        $endpoint = 'http://cn-hangzhou.sls.aliyuncs.com/';
        $AccessKeyId = 'mockKeyId';
        $AccessKey = 'mockKey';
        $client = new Aliyun_Log_Client_Mock($endpoint, $AccessKeyId, $AccessKey);
        $client->gReturnStatus = 200;
        $client->gReturnJson = null;


        $configName='conf_ll2';
        $inputDetail = new Aliyun_Log_Models_Config_InputDetail('access2.log',array('ip','time'),true,"\\d+\\.\\d",'/apsarapangu/abc','common_reg_log',"([\\d\\.]+)","%d/%b/%Y:%H:%M:%S",array(),array(),'none');
        $outputDetail = new Aliyun_Log_Models_Config_OutputDetail('ay42','perfcounter');
        $config = new Aliyun_Log_Models_Config($configName,'file',$inputDetail,'LogService',$outputDetail);
        $outputDetail = new Aliyun_Log_Models_Config_OutputDetail('ay42','perfcounter');
        $config = new Aliyun_Log_Models_Config($configName,'file',$inputDetail,'LogService',$outputDetail);
        $request = new Aliyun_Log_Models_UpdateConfigRequest($config);

        $response = $client->updateConfig($request);
        $expectUrl = "http://cn-hangzhou.sls.aliyuncs.com:80/configs/conf_ll2";
        $expectHeader = array(
            'x-log-apiversion' => API_VERSION,
            'Content-Length' => 411,
            'x-log-bodyrawsize' => 0,
            'Content-MD5' => '79BDAB8611BC1885A67B8E1A948582F8',
            'Host' => 'cn-hangzhou.sls.aliyuncs.com',
            'x-log-signaturemethod' => 'hmac-sha1',
            'Date' => 'Mon, 3 Jan 2010 08:33:47 GMT',
            'Content-Type' => 'application/json',
            'Authorization' => 'LOG mockKeyId:X1ztmPM8tBoDTxA19fdCCo9jHt4='
        );
        $expectBody ="{\"configName\":\"conf_ll2\",\"inputType\":\"file\",\"inputDetail\":{\"filePattern\":\"access2.log\",\"key\":[\"ip\",\"time\"],\"localStorage\":true,\"logBeginRegex\":\"\\\\d+\\\\.\\\\d\",\"logPath\":\"\/apsarapangu\/abc\",\"logType\":\"common_reg_log\",\"regex\":\"([\\\\d\\\\.]+)\",\"timeFormat\":\"%d\/%b\/%Y:%H:%M:%S\",\"filterRegex\":[],\"filterKey\":[],\"topicFormat\":\"none\"},\"outputType\":\"LogService\",\"outputDetail\":{\"projectName\":\"ay42\",\"logstoreName\":\"perfcounter\"}}";

        $this->assertTrue($client->gSendUrl==$expectUrl);
        $this->assertTrue($client->gSendbody==$expectBody);
        $this->assertTrue($client->gSendHeader==$expectHeader);


    }
    function testGetConfig() {
        $endpoint = 'http://cn-hangzhou.sls.aliyuncs.com/';
        $AccessKeyId = 'mockKeyId';
        $AccessKey = 'mockKey';
        $client = new Aliyun_Log_Client_Mock($endpoint, $AccessKeyId, $AccessKey);
        $client->gReturnStatus = 200;
        $configName = 'mock_configName';
 
        $client->gReturnJson = "{\"configName\":\"mock_configName\",\"inputDetail\":{\"filePattern\":\"access.log\",\"filterKey\":[],\"filterRegex\":[],\"key\":[\"ip\",\"time\"],\"localStorage\":true,\"logBeginRegex\":\"\\\\d+\\\\.\\\\d\",\"logPath\":\"/apsarapangu/abc\",\"logType\":\"common_reg_log\",\"regex\":\"([\\\\d\\\\.]+)\",\"timeFormat\":\"%d/%b/%Y:%H:%M:%S\",\"topicFormat\":\"none\"},\"inputType\":\"file\",\"outputDetail\":{\"logstoreName\":\"perfcounter\",\"projectName\":\"liuleiProj\"},\"outputType\":\"LogService\"}";
        
        $request = new Aliyun_Log_Models_GetConfigRequest($configName);
        $response = $client->getConfig($request);
        $expectUrl = "http://cn-hangzhou.sls.aliyuncs.com:80/configs/mock_configName";
        $expectHeader = array(
            'x-log-apiversion' => API_VERSION,
            'Content-Length' => 0,
            'x-log-bodyrawsize' => 0,
            'Host' => 'cn-hangzhou.sls.aliyuncs.com',
            'x-log-signaturemethod' => 'hmac-sha1',
            'Date' => 'Mon, 3 Jan 2010 08:33:47 GMT',
            'Content-Type' => '',
            'Authorization' => 'LOG mockKeyId:oTqqusB43qtxK9BFm6nrbm6Pdaw='
        );
        $expectBody = null;
        $this->assertTrue($client->gSendUrl==$expectUrl);
        $this->assertTrue($client->gSendbody==$expectBody);
        $this->assertTrue($client->gSendHeader==$expectHeader);

        $this->assertTrue($response->getConfig()->getConfigName()==$configName);
    }



    function testDeleteConfig() {
        $endpoint = 'http://cn-hangzhou.sls.aliyuncs.com/';
        $AccessKeyId = 'mockKeyId';
        $AccessKey = 'mockKey';
        $client = new Aliyun_Log_Client_Mock($endpoint, $AccessKeyId, $AccessKey);
        $configName = 'mock_configName';
        $client->gReturnStatus = 200;
        $client->gReturnJson = null;
        
        $request = new Aliyun_Log_Models_DeleteConfigRequest($configName);
        $response = $client->deleteConfig($request);
        $expectUrl = "http://cn-hangzhou.sls.aliyuncs.com:80/configs/mock_configName";
        $expectHeader = array(
            'x-log-apiversion' => API_VERSION,
            'Content-Length' => 0,
            'x-log-bodyrawsize' => 0,
            'Host' => 'cn-hangzhou.sls.aliyuncs.com',
            'x-log-signaturemethod' => 'hmac-sha1',
            'Date' => 'Mon, 3 Jan 2010 08:33:47 GMT',
            'Content-Type' => '',
            'Authorization' => 'LOG mockKeyId:ehqJvyKjjnrLUJ1HEZd6czjDuOw='
        );
        $expectBody = null;
        $this->assertTrue($client->gSendUrl==$expectUrl);
        $this->assertTrue($client->gSendbody==$expectBody);
        $this->assertTrue($client->gSendHeader==$expectHeader);

    }
    function testListConfigs() {
        $endpoint = 'http://cn-hangzhou.sls.aliyuncs.com/';
        $AccessKeyId = 'mockKeyId';
        $AccessKey = 'mockKey';
        $client = new Aliyun_Log_Client_Mock($endpoint, $AccessKeyId, $AccessKey);
        $client->gReturnStatus = 200;
        $client->gReturnJson = "{\"configs\":[\"mock_conf\",\"mydev\",\"sdk-sample-config1436169372\",\"sdkftconfig1435805276\",\"sdkftconfig1436359042\"],\"offset\":0,\"size\":5,\"total\":5}";
        
        $request = new Aliyun_Log_Models_ListConfigsRequest(null,0,100);
        $response = $client->listConfigs($request);
        $expectUrl = "http://cn-hangzhou.sls.aliyuncs.com:80/configs?offset=0&size=100";
        $expectHeader = array(
            'x-log-apiversion' => API_VERSION,
            'Content-Length' => 0,
            'x-log-bodyrawsize' => 0,
            'Host' => 'cn-hangzhou.sls.aliyuncs.com',
            'x-log-signaturemethod' => 'hmac-sha1',
            'Date' => 'Mon, 3 Jan 2010 08:33:47 GMT',
            'Content-Type' => '',
            'Authorization' => 'LOG mockKeyId:swH2m824MHAfj4LTTeA+8OuB1G8='
        );
        $expectBody = null;
        $this->assertTrue($client->gSendUrl==$expectUrl);
        $this->assertTrue($client->gSendbody==$expectBody);
        $this->assertTrue($client->gSendHeader==$expectHeader);

    }
    function testCreateMachineGroup() {
        $endpoint = 'http://cn-hangzhou.sls.aliyuncs.com/';
        $AccessKeyId = 'mockKeyId';
        $AccessKey = 'mockKey';
        $client = new Aliyun_Log_Client_Mock($endpoint, $AccessKeyId, $AccessKey);
        $client->gReturnStatus = 200;
        $client->gReturnJson = null;

        $groupName='mg_ll2'; 
        $groupAttribute = new Aliyun_Log_Models_MachineGroup_GroupAttribute('en_ll','gt_ll');
        $machineList = array();
        $machine1 = new Aliyun_Log_Models_Machine('UUID1',321);
        $machine2 = new Aliyun_Log_Models_Machine('UUID2',321123);
        $machineList[] = $machine1;
        $machineList[] = $machine2;
        $machineGroup = new Aliyun_Log_Models_MachineGroup($groupName,'gt_ll',$groupAttribute,$machineList);
        $request = new Aliyun_Log_Models_CreateMachineGroupRequest($machineGroup);
        $response = $client->createMachineGroup($request);
      
        $expectUrl = "http://cn-hangzhou.sls.aliyuncs.com:80/machinegroups";
        $expectHeader = array(
            'x-log-apiversion' => API_VERSION,
            'Content-Length' => 206,
            'x-log-bodyrawsize' => 0,
            'Content-MD5' => '5553899CE45C220E28AEA1CE866C89FE',
            'Host' => 'cn-hangzhou.sls.aliyuncs.com',
            'x-log-signaturemethod' => 'hmac-sha1',
            'Date' => 'Mon, 3 Jan 2010 08:33:47 GMT',
            'Content-Type' => 'application/json',
            'Authorization' => 'LOG mockKeyId:4odgNTEu/5uYxZ+ymAaTu79al2Y='
        );
        $expectBody="{\"groupName\":\"mg_ll2\",\"groupType\":\"gt_ll\",\"groupAttribute\":{\"externalName\":\"en_ll\",\"groupTopic\":\"gt_ll\"},\"machineList\":[{\"uuid\":\"UUID1\",\"lastHeartbeatTime\":321},{\"uuid\":\"UUID2\",\"lastHeartbeatTime\":321123}]}";

        $this->assertTrue($client->gSendUrl==$expectUrl);
        $this->assertTrue($client->gSendbody==$expectBody);
        $this->assertTrue($client->gSendHeader==$expectHeader);

    }
    function testUpdateMachineGroup() {
        $endpoint = 'http://cn-hangzhou.sls.aliyuncs.com/';
        $AccessKeyId = 'mockKeyId';
        $AccessKey = 'mockKey';
        $client = new Aliyun_Log_Client_Mock($endpoint, $AccessKeyId, $AccessKey);
        $client->gReturnStatus = 200;
        $client->gReturnJson = null;

        $groupName='mg_ll2'; 
        $groupAttribute = new Aliyun_Log_Models_MachineGroup_GroupAttribute('en_ll','gt_ll');
        $machineList = array();
        $machine1 = new Aliyun_Log_Models_Machine('UUID1',321);
        $machine2 = new Aliyun_Log_Models_Machine('UUID2',321123);
        $machineList[] = $machine1;
        $machineList[] = $machine2;
        $machineGroup = new Aliyun_Log_Models_MachineGroup($groupName,'gt_ll',$groupAttribute,$machineList);
        $request = new Aliyun_Log_Models_UpdateMachineGroupRequest($machineGroup);
        $response = $client->updateMachineGroup($request);
      
        $expectUrl = "http://cn-hangzhou.sls.aliyuncs.com:80/machinegroups/mg_ll2";
        $expectHeader = array(
            'x-log-apiversion' => API_VERSION,
            'Content-Length' => 206,
            'x-log-bodyrawsize' => 0,
            'Content-MD5' => '5553899CE45C220E28AEA1CE866C89FE',
            'Host' => 'cn-hangzhou.sls.aliyuncs.com',
            'x-log-signaturemethod' => 'hmac-sha1',
            'Date' => 'Mon, 3 Jan 2010 08:33:47 GMT',
            'Content-Type' => 'application/json',
            'Authorization' => 'LOG mockKeyId:pR7pzbMXzbf0Rjqa6ix2pH26cQw='
        );
        $expectBody="{\"groupName\":\"mg_ll2\",\"groupType\":\"gt_ll\",\"groupAttribute\":{\"externalName\":\"en_ll\",\"groupTopic\":\"gt_ll\"},\"machineList\":[{\"uuid\":\"UUID1\",\"lastHeartbeatTime\":321},{\"uuid\":\"UUID2\",\"lastHeartbeatTime\":321123}]}";

        $this->assertTrue($client->gSendUrl==$expectUrl);
        $this->assertTrue($client->gSendbody==$expectBody);
        $this->assertTrue($client->gSendHeader==$expectHeader);
    
    }
    function testGetMachineGroup() {
        $endpoint = 'http://cn-hangzhou.sls.aliyuncs.com/';
        $AccessKeyId = 'mockKeyId';
        $AccessKey = 'mockKey';
        $client = new Aliyun_Log_Client_Mock($endpoint, $AccessKeyId, $AccessKey);
        $client->gReturnStatus = 200;
        $groupName = 'mg_ll';
 
        $client->gReturnJson = "{\"groupName\":\"mg_ll\",\"groupType\":\"gt_ll\",\"groupAttribute\":{\"externalName\":\"en_ll\",\"groupTopic\":\"gt_ll\"},\"machineList\":[{\"uuid\":\"UUID1\",\"lastHeartbeatTime\":0,\"info\":{\"ip\":\"10.101.172.20\",\"os\":\"Linux\",\"hostName\":\"\"},\"status\":{\"binaryCurVersion\":\"0.9.0\",\"binaryDeployVersion\":\"0\"}},{\"uuid\":\"UUID2\",\"lastHeartbeatTime\":1431932535,\"info\":{\"ip\":\"10.101.166.89\",\"os\":\"\",\"hostName\":\"\"},\"status\":{\"binaryCurVersion\":\"Undefined\",\"binaryDeployVersion\":\"\"}}],\"createTime\":1436252161,\"lastModifyTime\":1436252161}";
  
        $request = new Aliyun_Log_Models_GetMachineGroupRequest($groupName);
        $response = $client->getMachineGroup($request);
        $expectUrl = "http://cn-hangzhou.sls.aliyuncs.com:80/machinegroups/mg_ll";
        $expectHeader = array(
            'x-log-apiversion' => API_VERSION,
            'Content-Length' => 0,
            'x-log-bodyrawsize' => 0,
            'Host' => 'cn-hangzhou.sls.aliyuncs.com',
            'x-log-signaturemethod' => 'hmac-sha1',
            'Date' => 'Mon, 3 Jan 2010 08:33:47 GMT',
            'Content-Type' => '',
            'Authorization' => 'LOG mockKeyId:/WXwHSO7zDOVgbaIaZnR8Bg/ftM='
        );
        $expectBody = null;
        $this->assertTrue($client->gSendUrl==$expectUrl);
        $this->assertTrue($client->gSendbody==$expectBody);
        $this->assertTrue($client->gSendHeader==$expectHeader);

        $this->assertTrue($response->getMachineGroup()->getGroupName()==$groupName);
    }

    function testDeleteMachineGroup() {
        $endpoint = 'http://cn-hangzhou.sls.aliyuncs.com/';
        $AccessKeyId = 'mockKeyId';
        $AccessKey = 'mockKey';
        $client = new Aliyun_Log_Client_Mock($endpoint, $AccessKeyId, $AccessKey);
        $groupName = 'mg_ll';
        $client->gReturnStatus = 200;
        $client->gReturnJson = null;
        
        $request = new Aliyun_Log_Models_DeleteMachineGroupRequest($groupName);
        $response = $client->deleteMachineGroup($request);
        $expectUrl = "http://cn-hangzhou.sls.aliyuncs.com:80/machinegroups/mg_ll";
        $expectHeader = array(
            'x-log-apiversion' => API_VERSION,
            'Content-Length' => 0,
            'x-log-bodyrawsize' => 0,
            'Host' => 'cn-hangzhou.sls.aliyuncs.com',
            'x-log-signaturemethod' => 'hmac-sha1',
            'Date' => 'Mon, 3 Jan 2010 08:33:47 GMT',
            'Content-Type' => '',
            'Authorization' => 'LOG mockKeyId:u0gNkbOD8xWTyUyrALI0eY4t3pg='
        );
        $expectBody = null;
        $this->assertTrue($client->gSendUrl==$expectUrl);
        $this->assertTrue($client->gSendbody==$expectBody);
        $this->assertTrue($client->gSendHeader==$expectHeader);
    }

    function testListMachineGroups() {
        $endpoint = 'http://cn-hangzhou.sls.aliyuncs.com/';
        $AccessKeyId = 'mockKeyId';
        $AccessKey = 'mockKey';
        $client = new Aliyun_Log_Client_Mock($endpoint, $AccessKeyId, $AccessKey);
        $client->gReturnStatus = 200;
        $client->gReturnJson = "{\"machinegroups\":[\"mg_ll\",\"sdkftgroup1435890381\",\"sdkftgroup1435891611\",\"sdkftgroup1435893201\",\"sdkftgroup1435908199\",\"sdkftgroup1435908972\",\"sdkftgroup1435909607\",\"sdkftgroup1435909809\",\"sdkftgroup1435910307\",\"sdkftgroup1435913721\",\"tangkai_test\"],\"offset\":0,\"size\":11,\"total\":11}";
          
        $request = new Aliyun_Log_Models_ListMachineGroupsRequest(null,0,50);

        $response = $client->listMachineGroups($request);
        $expectUrl = "http://cn-hangzhou.sls.aliyuncs.com:80/machinegroups?offset=0&size=50";
        $expectHeader = array(
            'x-log-apiversion' => API_VERSION,
            'Content-Length' => 0,
            'x-log-bodyrawsize' => 0,
            'Host' => 'cn-hangzhou.sls.aliyuncs.com',
            'x-log-signaturemethod' => 'hmac-sha1',
            'Date' => 'Mon, 3 Jan 2010 08:33:47 GMT',
            'Content-Type' => '',
            'Authorization' => 'LOG mockKeyId:iAlrxEDBCoHhkXOFqTqKrJ1TeFE='
        );
        $expectBody = null;
        $this->assertTrue($client->gSendUrl==$expectUrl);
        $this->assertTrue($client->gSendbody==$expectBody);
        $this->assertTrue($client->gSendHeader==$expectHeader);
        $this->assertTrue($response->getMachineGroups()[0]=='mg_ll');

    }

    function testCreateACL() {
        $endpoint = 'http://cn-hangzhou.sls.aliyuncs.com/';
        $AccessKeyId = 'mockKeyId';
        $AccessKey = 'mockKey';
        $client = new Aliyun_Log_Client_Mock($endpoint, $AccessKeyId, $AccessKey);
        $client->gReturnStatus = 200;
        $client->gReturnJson = null;

        $array = array();
        $array[]='WRITE';
        $array[]='READ';
        $acl = new Aliyun_Log_Models_ACL('account','ANONYMOUS','/projects/ali-liulei-abc',$array);
        $request = new Aliyun_Log_Models_CreateACLRequest($acl);
        $response = $client->createACL($request);

        $expectUrl = "http://cn-hangzhou.sls.aliyuncs.com:80/acls";
        $expectHeader = array(
            'x-log-apiversion' => API_VERSION,
            'Content-Length' => 120,
            'x-log-bodyrawsize' => 0,
            'Content-MD5' => 'B1090B4480B1CC5149D76A5EB06E9FE1',
            'Host' => 'cn-hangzhou.sls.aliyuncs.com',
            'x-log-signaturemethod' => 'hmac-sha1',
            'Date' => 'Mon, 3 Jan 2010 08:33:47 GMT',
            'Content-Type' => 'application/json',
            'Authorization' => 'LOG mockKeyId:egvXSNKBPdcOwEUWOuQo4bpN96E='
        );
        $expectBody="{\"principleType\":\"account\",\"principleId\":\"ANONYMOUS\",\"object\":\"\\/projects\\/ali-liulei-abc\",\"privilege\":[\"WRITE\",\"READ\"]}";
  
        $this->assertTrue($client->gSendUrl==$expectUrl);
        $this->assertTrue($client->gSendbody==$expectBody);
        $this->assertTrue($client->gSendHeader==$expectHeader);
    }
    function testUpdateACL() {
        $endpoint = 'http://cn-hangzhou.sls.aliyuncs.com/';
        $AccessKeyId = 'mockKeyId';
        $AccessKey = 'mockKey';
        $client = new Aliyun_Log_Client_Mock($endpoint, $AccessKeyId, $AccessKey);
        $client->gReturnStatus = 200;
        $client->gReturnJson = null;

        $array = array();
        $array[]='WRITE';
        $array[]='READ';
        $acl = new Aliyun_Log_Models_ACL('account','ANONYMOUS','/projects/ali-liulei-abc',$array,'6798F36BCED3CE34A87B18894851BB55');
        $request = new Aliyun_Log_Models_UpdateACLRequest($acl);
        $response = $client->updateACL($request);

        $expectUrl = "http://cn-hangzhou.sls.aliyuncs.com:80/acls/6798F36BCED3CE34A87B18894851BB55";
        $expectHeader = array(
            'x-log-apiversion' => API_VERSION,
            'Content-Length' => 163,
            'x-log-bodyrawsize' => 0,
            'Content-MD5' => 'F65550C89799A1119FD4F0824C7C82F6',
            'Host' => 'cn-hangzhou.sls.aliyuncs.com',
            'x-log-signaturemethod' => 'hmac-sha1',
            'Date' => 'Mon, 3 Jan 2010 08:33:47 GMT',
            'Content-Type' => 'application/json',
            'Authorization' => 'LOG mockKeyId:VE51Wb0H8WY0KWptloWR5TRGIjc='
        );
        $expectBody="{\"principleType\":\"account\",\"principleId\":\"ANONYMOUS\",\"object\":\"\\/projects\\/ali-liulei-abc\",\"privilege\":[\"WRITE\",\"READ\"],\"aclId\":\"6798F36BCED3CE34A87B18894851BB55\"}";
  
        $this->assertTrue($client->gSendUrl==$expectUrl);
        $this->assertTrue($client->gSendbody==$expectBody);
        $this->assertTrue($client->gSendHeader==$expectHeader);
    }
    function testGetACL() {
        $endpoint = 'http://cn-hangzhou.sls.aliyuncs.com/';
        $AccessKeyId = 'mockKeyId';
        $AccessKey = 'mockKey';
        $client = new Aliyun_Log_Client_Mock($endpoint, $AccessKeyId, $AccessKey);
        $client->gReturnStatus = 200;
        $aclId = '05B2E6D1B626EFFA0EBA2C078FA60B32';
 
        $client->gReturnJson = "{\"aclId\":\"05B2E6D1B626EFFA0EBA2C078FA60B32\",\"principleType\":\"account\",\"principleId\":\"ANONYMOUS\",\"object\":\"/projects/ali-sls-baiying\",\"privilege\":[\"WRITE\",\"READ\"],\"createTime\":1435668319,\"lastModifyTime\":1436359103}" ;
  
        $request = new Aliyun_Log_Models_GetACLRequest($aclId);
        $response = $client->getAcl($request);
        $expectUrl = "http://cn-hangzhou.sls.aliyuncs.com:80/acls/05B2E6D1B626EFFA0EBA2C078FA60B32";
        $expectHeader = array(
            'x-log-apiversion' => API_VERSION,
            'Content-Length' => 0,
            'x-log-bodyrawsize' => 0,
            'Host' => 'cn-hangzhou.sls.aliyuncs.com',
            'x-log-signaturemethod' => 'hmac-sha1',
            'Date' => 'Mon, 3 Jan 2010 08:33:47 GMT',
            'Content-Type' => '',
            'Authorization' => 'LOG mockKeyId:EndQUwavHfHvWYU54ArRN0nRLaM='
        );
        $expectBody = null;
        $this->assertTrue($client->gSendUrl==$expectUrl);
        $this->assertTrue($client->gSendbody==$expectBody);
        $this->assertTrue($client->gSendHeader==$expectHeader);
        $this->assertTrue($response->getAcl()->getAclId()==$aclId);

    }
    function testDeleteACL() {
        $endpoint = 'http://cn-hangzhou.sls.aliyuncs.com/';
        $AccessKeyId = 'mockKeyId';
        $AccessKey = 'mockKey';
        $client = new Aliyun_Log_Client_Mock($endpoint, $AccessKeyId, $AccessKey);
        $aclId = '05B2E6D1B626EFFA0EBA2C078FA60B32';
        $client->gReturnStatus = 200;
        $client->gReturnJson = null;
        
        $request = new Aliyun_Log_Models_DeleteACLRequest($aclId);
        $response = $client->deleteAcl($request);
        $expectUrl = "http://cn-hangzhou.sls.aliyuncs.com:80/acls/05B2E6D1B626EFFA0EBA2C078FA60B32";
        $expectHeader = array(
            'x-log-apiversion' => API_VERSION,
            'Content-Length' => 0,
            'x-log-bodyrawsize' => 0,
            'Host' => 'cn-hangzhou.sls.aliyuncs.com',
            'x-log-signaturemethod' => 'hmac-sha1',
            'Date' => 'Mon, 3 Jan 2010 08:33:47 GMT',
            'Content-Type' => '',
            'Authorization' => 'LOG mockKeyId:KIK/r7ksMcC54y9YnVQHR+h2q7Y='
        );
        $expectBody = null;
        $this->assertTrue($client->gSendUrl==$expectUrl);
        $this->assertTrue($client->gSendbody==$expectBody);
        $this->assertTrue($client->gSendHeader==$expectHeader);

    }
    function testListACLs() {
        $endpoint = 'http://cn-hangzhou.sls.aliyuncs.com/';
        $AccessKeyId = 'mockKeyId';
        $AccessKey = 'mockKey';
        $client = new Aliyun_Log_Client_Mock($endpoint, $AccessKeyId, $AccessKey);
        $client->gReturnStatus = 200;
        $client->gReturnJson = "{\"offset\":0,\"size\":1,\"total\":1,\"acls\":[{\"aclId\":\"05B2E6D1B626EFFA0EBA2C078FA60B32\",\"principleType\":\"account\",\"principleId\":\"ANONYMOUS\",\"object\":\"/projects/ali-sls-baiying\",\"privilege\":[\"WRITE\",\"READ\"],\"createTime\":1435668319,\"lastModifyTime\":1436359103}]}";
        $request = new Aliyun_Log_Models_ListACLsRequest(null,0,50);

        $response = $client->listACLs($request);
        $expectUrl = "http://cn-hangzhou.sls.aliyuncs.com:80/acls?offset=0&size=50";
        $expectHeader = array(
            'x-log-apiversion' => API_VERSION,
            'Content-Length' => 0,
            'x-log-bodyrawsize' => 0,
            'Host' => 'cn-hangzhou.sls.aliyuncs.com',
            'x-log-signaturemethod' => 'hmac-sha1',
            'Date' => 'Mon, 3 Jan 2010 08:33:47 GMT',
            'Content-Type' => '',
            'Authorization' => 'LOG mockKeyId:O2/2j+qmtiZtYkbrPtoPVx6CU+w='
        );
        $expectBody = null;
        $this->assertTrue($client->gSendUrl==$expectUrl);
        $this->assertTrue($client->gSendbody==$expectBody);
        $this->assertTrue($client->gSendHeader==$expectHeader);
        $this->assertTrue($response->getAcls()[0]->getAclId() == '05B2E6D1B626EFFA0EBA2C078FA60B32');


    }
    function testGetMachine() {
         $endpoint = 'http://cn-hangzhou.sls.aliyuncs.com/';
        $AccessKeyId = 'mockKeyId';
        $AccessKey = 'mockKey';
        $client = new Aliyun_Log_Client_Mock($endpoint, $AccessKeyId, $AccessKey);
        $client->gReturnStatus = 200;
        $uuid = 'UUID2';
 
        $client->gReturnJson = "{\"uuid\":\"UUID2\",\"createTime\":0,\"lastModifyTime\":1432196602,\"lastHeartbeatTime\":1431932535,\"info\":{\"ip\":\"10.101.166.89\",\"os\":\"\",\"hostName\":\"\"},\"status\":{\"binaryCurVersion\":\"Undefined\",\"binaryDeployVersion\":\"\"}}";
  
        $request = new Aliyun_Log_Models_GetMachineRequest($uuid);
        $response = $client->getMachine($request);
        $expectUrl = "http://cn-hangzhou.sls.aliyuncs.com:80/machines/UUID2";
        $expectHeader = array(
            'x-log-apiversion' => API_VERSION,
            'Content-Length' => 0,
            'x-log-bodyrawsize' => 0,
            'Host' => 'cn-hangzhou.sls.aliyuncs.com',
            'x-log-signaturemethod' => 'hmac-sha1',
            'Date' => 'Mon, 3 Jan 2010 08:33:47 GMT',
            'Content-Type' => '',
            'Authorization' => 'LOG mockKeyId:CHWpMcKbEVd+qP9OZH7GYIkBcLM='
        );
        $expectBody = null;
        $this->assertTrue($client->gSendUrl==$expectUrl);
        $this->assertTrue($client->gSendbody==$expectBody);
        $this->assertTrue($client->gSendHeader==$expectHeader);
        $this->assertTrue($response->getMachine()->getLastModifyTime()==1432196602);

    }
    function testApplyConfigToMachineGroup() {
        $endpoint = 'http://cn-hangzhou.sls.aliyuncs.com/';
        $AccessKeyId = 'mockKeyId';
        $AccessKey = 'mockKey';
        $client = new Aliyun_Log_Client_Mock($endpoint, $AccessKeyId, $AccessKey);
        $groupName = 'mg_ll';
        $configName = 'conf_ll';
        $client->gReturnStatus = 200;
        $client->gReturnJson = null;
        
        $request = new Aliyun_Log_Models_ApplyConfigToMachineGroupRequest($groupName,$configName);
        $response = $client->applyConfigToMachineGroup($request);
        $expectUrl = "http://cn-hangzhou.sls.aliyuncs.com:80/machinegroups/mg_ll/configs/conf_ll";
        $expectHeader = array(
            'x-log-apiversion' => API_VERSION,
            'Content-Length' => 0,
            'x-log-bodyrawsize' => 0,
            'Host' => 'cn-hangzhou.sls.aliyuncs.com',
            'x-log-signaturemethod' => 'hmac-sha1',
            'Date' => 'Mon, 3 Jan 2010 08:33:47 GMT',
            'Content-Type' => '',
            'Authorization' => 'LOG mockKeyId:Bt/Ibk/0DElbu9BIQePankxd6o4='
        );
        $expectBody = null;
        $this->assertTrue($client->gSendUrl==$expectUrl);
        $this->assertTrue($client->gSendbody==$expectBody);
        $this->assertTrue($client->gSendHeader==$expectHeader);
    }
    
    function testRemoveConfigFromMachineGroup() {
        $endpoint = 'http://cn-hangzhou.sls.aliyuncs.com/';
        $AccessKeyId = 'mockKeyId';
        $AccessKey = 'mockKey';
        $client = new Aliyun_Log_Client_Mock($endpoint, $AccessKeyId, $AccessKey);
        $groupName = 'mg_ll';
        $configName = 'conf_ll';
        $client->gReturnStatus = 200;
        $client->gReturnJson = null;
        
        $request = new Aliyun_Log_Models_RemoveConfigFromMachineGroupRequest($groupName,$configName);
        $response = $client->removeConfigFromMachineGroup($request);
        $expectUrl = "http://cn-hangzhou.sls.aliyuncs.com:80/machinegroups/mg_ll/configs/conf_ll";
        $expectHeader = array(
            'x-log-apiversion' => API_VERSION,
            'Content-Length' => 0,
            'x-log-bodyrawsize' => 0,
            'Host' => 'cn-hangzhou.sls.aliyuncs.com',
            'x-log-signaturemethod' => 'hmac-sha1',
            'Date' => 'Mon, 3 Jan 2010 08:33:47 GMT',
            'Content-Type' => '',
            'Authorization' => 'LOG mockKeyId:qbKgvwkQ6hPcDyKKWBnoIzPG3ZA='
        );
        $expectBody = null;
        $this->assertTrue($client->gSendUrl==$expectUrl);
        $this->assertTrue($client->gSendbody==$expectBody);
        $this->assertTrue($client->gSendHeader==$expectHeader);
    }
    
    
    
    
    
    
    
    
    
    
    function testException() {
        $endpoint = 'cn-hangzhou.sls.aliyuncs.com';
        $AccessKeyId = 'mockKeyId';
        $AccessKey = 'mockKey';
        $client = new Aliyun_Log_Client_Mock($endpoint, $AccessKeyId, $AccessKey);
        
        $logGroups = array();

        $project = "mock_project";
        $logstore = "mock_logstore";
        $topic = "mock" ;
        $request = new Aliyun_Log_Models_PutLogsRequest($project, $logstore, $topic, null);
        
        $content = array(
            'value1' => '12',
            'value2' => '24',
            'value3' => '36',
            'value4' => '48',
            'avg' => '30'
        );
        $logItem = new Aliyun_Log_Models_LogItem();
        $logItem->setTime(1);
        $logItem->setContents($content);
        $logGroups[] = $logItem;
        $request->setLogItems($logGroups);
        
        try {
            $client->gReturnStatus = 400;
            $client->gReturnJson = "{\"error_code\":\"InvalidParameter\",  \"error_message\": \"The request must contain the parameter Category\" }";
            $client->putLogs($request);
            $this->fail("Returned error should cause an exception.");
        } catch (Aliyun_Log_Exception $ex) {
            $this->assertEquals($ex->getErrorCode(), "InvalidParameter");
        } catch (Exception $ex) {
            $this->fail("Unknown exception. ".$ex->__toString());
        }
        
        try {
            $client->gReturnStatus = 200;
            $client->gReturnJson = "{null";
            $client->putLogs($request);
            $this->fail("Returned error should cause an exception.");
        } catch (Aliyun_Log_Exception $ex) {
            $this->assertEquals($ex->getErrorCode(), "BadResponse");
        } catch (Exception $ex) {
            $this->fail("Unknown exception. ".$ex->__toString());
        }
        
    }
}

