<?php

require_once realpath(dirname(__FILE__) . '/../../Log_Autoload.php');

function printAssert($script,$line,$message){
  echo "<br/>assert fail-----------------<br/>";
  echo "script:$script<br/>";
  echo "line:$line<br/>";
  echo "message:$message<br/>";
  echo "-----------------------------<br/>";
}
assert_options(ASSERT_ACTIVE,1);
assert_options(ASSERT_CALLBACK,'printAssert');

$endpoint="cn-hangzhou-devcommon-intranet.sls.aliyuncs.com";
$accessKeyId = "94to3z418yupi6ikawqqd370";
$accessKey = "DFk3ONbf81veUFpMg7FtY0BLB2w=";
$project='ali-liulei-projecttest';
$logstore = 'logstore2';

error_reporting(E_ALL^E_NOTICE^E_WARNING);

class SlsFunctionTest {
    public $client;
    public $project;
    public $logstore;
    public $startTime;
    public $topicPrefix;
    
    public function __construct($endpoint, $accessKeyId, $accessKey, $project, $logstore) {
        $this->client = new Aliyun_Log_Client($endpoint, $accessKeyId, $accessKey);
        $this->project = $project;
        $this->logstore = $logstore;
        $this->startTime = time();
        $this->topicPrefix = "sls_topic_php_{$this->startTime}_";
    }
    
    public function testPutData() {
        for($i=0;$i<10;++$i) {
            $logitems = array();
            
            for($j=0;$j<600;++$j) {
                $logitem = new Aliyun_Log_Models_LogItem(time());
                $logitem->pushBack('ID', 'id_' . (string)( $i * 600 + $j ) );
                $logitems[] = $logitem;
            }
            $topic = $this->topicPrefix . (string)$i;
            
            try {
                $request = new Aliyun_Log_Models_PutLogsRequest($this->project, $this->logstore, $topic, null, $logitems);
                $response = $this->client->putLogs($request);
            } catch (Aliyun_Log_Exception $ex) {
                throw $ex;
            } catch (Exception $ex) {
                throw $ex;
            }
        }
    }
    
    public function testGetLogStore() {
        $request = new Aliyun_Log_Models_ListLogstoresRequest($this->project);
        try {
            $response = $this->client->listLogstores($request);
            assert(in_array($this->logstore, $response->getLogstores()));
        } catch (Aliyun_Log_Exception $ex) {
            throw $ex;
        } catch (Exception $ex) {
            throw $ex;
        }
    }
    
    public function testListTopics() {
        try {
            $token = $this->topicPrefix;
            $request = new Aliyun_Log_Models_ListTopicsRequest($this->project, $this->logstore, $token, 5);
            
            $response = $this->client->listTopics($request);
            
            $returnTopics = $response->getTopics();
            assert($response->getNextToken()==$this->topicPrefix.'5');
    
            $request->setToken($response->getNextToken());
            
            $response = $this->client->listTopics($request);
            
            $topics = $response->getTopics();
            foreach ($topics as $topic)
                $returnTopics[] = $topic;
            $token = $response->getNextToken();
            $tmp = strlen($this->topicPrefix);
            
            assert(!$token || strlen($token)<$tmp || 
                strpos($token, $this->topicPrefix)===false);
            assert(count($returnTopics)==10);
            for($i=0;$i<10;++$i)
                assert($returnTopics[$i]==$this->topicPrefix.(string)$i);
        } catch (Aliyun_Log_Exception $ex) {
            throw $ex;
        } catch (Exception $ex) {
            throw $ex;
        }
    }
    
    public function testGetHistogram() {
        try {
            $topic = $this->topicPrefix . '0';
            $query = 'ID';
            $from = $this->startTime;
            $to = $this->startTime + 100;
            $request = new Aliyun_Log_Models_GetHistogramsRequest($this->project, $this->logstore, 
                    $from, $to, $topic, $query);
            $response = $this->client->getHistograms($request);
            
            assert( $response->getTotalCount() == 600 );
            assert( $response->isCompleted() );
            
            $total = 0;
            $histograms = $response->getHistograms();
            foreach ($histograms as $hist) {
                assert($hist->isCompleted());
                $total += $hist->getCount();
            }
            assert( $total == 600 );
        } catch (Aliyun_Log_Exception $ex) {
            throw $ex;
        } catch (Exception $ex) {
            throw $ex;
        }
    }
    
    public function testGetLogs() {
        try {
            $topicIndex = 3;
            $topic = $this->topicPrefix.(string)($topicIndex);
            $from = $this->startTime;
            $to = $this->startTime+100;
            $query = 'ID';
            $request = new Aliyun_Log_Models_GetLogsRequest($this->project, $this->logstore, 
                    $from, $to, $topic, $query, 20, 0, false);
            $response = $this->client->GetLogs($request);
            
            assert('$response->getCount()==20');
            echo "getCount:".$response->getCount();
            $request = new Aliyun_Log_Models_GetLogsRequest($this->project, $this->logstore, 
                    $from, $to, $topic, $query, 100, 50, False);
            
            $response = $this->client->getLogs($request);
            
            assert('$response->getCount()==100');
            //echo "getCount:".$response->getCount();
            assert('$response->isCompleted()');
            $logitems = $response->getLogs();
            $index = 0;
            foreach ($logitems as $item) {
                $content = $item->getContents();
                assert($content['ID']=='id_'.(string)($topicIndex*600+50+$index));
                $index += 1;
            }
        } catch (Aliyun_Log_Exception $ex) {
            throw $ex;
        } catch (Exception $ex) {
            throw $ex;
        }
    }
    
    public function testBatchGetLogs()
    {

    }

    public function testAll() {
        $this->testPutData();
        sleep(50);
        $this->testGetLogStore();
        $this->testListTopics();
        $this->testGetHistogram();
        $this->testGetLogs();
        echo "Function test success." . PHP_EOL;
    }
}

$test = new SlsFunctionTest($endpoint, $accessKeyId, $accessKey, $project, $logstore);
$test->testPutData();
$test->testGetLogs();
