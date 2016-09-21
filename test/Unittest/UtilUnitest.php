<?php

require_once realpath(dirname(__FILE__) . '/../../Log_Autoload.php');

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

class Aliyun_Log_UtilUnitest extends PHPUnit_Framework_TestCase {
    
    function testGetLocalIp() {
        $ip1 = LocalIP();
        $ip2 = Aliyun_Log_Util::getLocalIp();
        $this->assertTrue($ip1===$ip2);
    }
    
    function testCalMD5() {
        $ma = array(
                " "  =>  "7215EE9C7D9DC229D2921A40E899EC5F",
                "sls_sdk" => "1DF56158B3C11039A2B68B5B06AA91E1",
                "md5" => "1BC29B36F623BA82AAF6724FD3B16718",
                "kl38ys\123\001kd\"\n\tdsi8" => "802EAEB3D5AC8CBE65934997C0B83E4B",
                "kl38ys\123\001kd\"\n\t".chr(8)."dsi8" => "04568016387F3BF053211F01CF55E52E",
                "kl38ys\123\001\211kd\"\n\t".chr(8)."dsi8" => "F2A4958FB6C4CCB82B129512BBB98205",
                "kl38ys\123\000\001\211kd\"\n\t".chr(8)."dsi8" => "344676963785D56724F5B8228F9B2B69"
        ); // be careful of '\b'
        foreach ($ma as $key=>$value)
            $this->assertTrue( Aliyun_Log_Util::calMD5($key)==$value);
    }

    function testHmacSHA1() {
        $key = "OtxrzxIsfpFjA7SwPzILwy8Bw21TLhquhboDYROV";
        $testSet =array(
                "PUT\nc8fdb181845a4ca6b8fec737b3581d76\ntext/html\nThu, 17 Nov 2005 18:49:58 GMT\nx-oss-magic:abracadabra\nx-oss-meta-author:foo@bar.com\n/oss-example/nelson" => "dZpCvvKgxiFw6wvMHHj5g3W6STM=",
                " " => "32DRF+WaJBYZmrkbDdAVS0M0lRI=",
                "sls_sdk" => "PoLgxEZ90bth25IQen06dyWms4Q="
        );
        foreach ($testSet as $a=>$b)
            $this->assertTrue( Aliyun_Log_Util::hmacSHA1($a, $key)==$b );
    }
    
    function testIsIp() {
        $testSet = array(
            '0.0.0.0' => true,
            '255.255.255.255' => true,
            '256.255.255.255' => false,
            '12.23.25' => false,
            '12.23.25.' => false,
            '.123.12.12' => false,
            'dsa.re.fde.f' => false,
            '-12.132.123.5' => false
        );
        foreach ($testSet as $a=>$b)
            $this->assertTrue( Aliyun_Log_Util::isIp($a)==$b );
    }
    
    function testUrlEncode() {
    	$params = array(
    			"" => "",
    			"dfj 2354" => "dfj+2354",
    			"dfj  234" => "dfj++234",
    			"dfj++er" => "dfj%2B%2Ber",
    			"dfj%ber#sdrf@0~&jd(" => "dfj%25ber%23sdrf%400%7E%26jd%28",
    			"sdfj =&34" => "sdfj+%3D%2634",
    			"Tue, 23 Oct 2012 17:41:01 GMT" => "Tue%2C+23+Oct+2012+17%3A41%3A01+GMT",
    			"mock_category" => "mock_category",
    			"DFk3ONbf81veUFpMg7FtY0BLB2w=" => "DFk3ONbf81veUFpMg7FtY0BLB2w%3D"
    	);
    	foreach ( $params as $key=>$value )
    		$this->assertTrue( Aliyun_Log_Util::urlEncodeValue($key)==$value );
    }
}
