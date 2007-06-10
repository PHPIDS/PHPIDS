<?php

/**
 * PHP IDS
 * 
 * Requirements: PHP5, SimpleXML, MultiByte Extension (optional)
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
 */

class IdsComponent extends Object {

    # define the threshold for the ids reactions
    var $threshold = array( 'log' => 3,						
                            'mail' => 9,
                            'warn' => 27, 
                            'kick' => 81
    );
    
    /*
    * 	defines the email addresses for the 
    * 	idsmail function
    */							
    var $email = array(	'address1@what.ever', 
                        'address2@what.ever'
    );
    
    /**
     * 	detect
     * 
     * 	This function includes the IDS vendor parts and runs the 
     * 	detection routines on the request array.
     * 
     * 	@author .mario
     * 	@param void
     *  @return boolean
     */
    function detect(&$controller){
        
        $this->controller = &$controller;
        $this->name = Inflector::singularize($this->controller->name);
        
        	
        # require the needed files
        vendor('IDS/Monitor');
        vendor('IDS/Filter/Storage');
        
        # instaciate the needed stuff
        $storage = new Filter_Storage();
        $storage->getFilterFromXML(VENDORS . 'lib/default_filter.xml');
        $ids = new IDS_Monitor($_REQUEST, $storage);
        $results = $ids->run();
        
        
        # well - the IDS found something - now let's react!
        if(!empty($results)){
            $this->react($results);
        }
        return true;
    }
    
    /**
     * 	react
     * 
     * 	This function rects on the values in 
     * 	the incoming results array.
     * 
     * 	Depending on the impact vale certain actions are 
     * 	performed.
     * 
     * 	@author .mario
     * 	@param array $results
     * 	@return boolean
     */
    private function react($results){
    
        # loop thorugh the results
        foreach($results as $result){
            foreach($result['filter'] as $filter){
                $new = $this->controller->Session->read('IDS.Impact')+$filter['impact'];
                $this->controller->Session->write('IDS.Impact', $new);
            }
        }
        
        # get the current impact of the possible attacker
        $impact = $this->controller->Session->read('IDS.Impact');
        
        # react on the attack depending on the impact
        if($impact >= $this->threshold['kick']){
            $this->idslog($results);
            $this->idsmail($results);
            $this->idskick($results);
            return true;
        } else if($impact >= $this->threshold['warn']){
            $this->idslog($results);
            $this->idsmail($results);
            $this->idswarn($results);
            return true;
        } else if($impact >= $this->threshold['mail']){
            $this->idslog($results);
            $this->idsmail($results);
            return true;
        } else {	
            return true;
        }
    }	
    
    /**
     * 	idslog
     * 
     * 	This function writes an entry about the intrusion 
     * 	to the intrusion database
     * 
     * 	@author .mario
     * 	@param array $results
     *  @return boolean 	
     */
    private function idslog($results){
    
        foreach($results as $result){
            $data = array('Intrusion' => array(	'name' => $result['name'],
            'value' => stripslashes($result['value']),
            'page' => $this->name, 
            'userid' => $this->controller->Session->read('User.id')?$this->controller->Session->read('User.id'):0, 
            'session' => session_id()?session_id():'0',
            'ip' => $_SERVER['REMOTE_ADDR']?$_SERVER['REMOTE_ADDR']:0));
        }
        
        loadModel('Intrusion');
        $intrusion = new Intrusion;
        $saveable = array('name', 'value', 'page', 'userid', 'session', 'ip');
        $intrusion->save($data, false, $saveable);		
    	
    return true; 	
    }
     
    /**
     *  idsmail
     * 
     * 	This function sends out a mail 
     * 	about the intrusion including the intrusion details.
     *
     * 	@author .mario	
     * 	@param array $results
     * 	@return boolean
     */
    private function idsmail($results){
    
        $cur = null;
        $old = null;
        $body = null;
        
        foreach($results as $result){
            foreach($result['filter'] as $filter){
            
                $cur .= "\nPage: ".$this->name;
                $cur .= "\nImpact: ".$this->controller->Session->read('IDS.Impact');
                $cur .= "\nField: ".$result['name'];
                $cur .= "\nValue: ".stripslashes($result['value']);
                $cur .= "\nFilter: ".$filter['rule'];
                $cur .= "\nUser ID: ".$this->controller->Session->read('User.id')?$this->controller->Session->read('User.id'):0;
                $cur .= "\nSession: ".session_id();
                $cur .= "\nIP: ".$_SERVER['REMOTE_ADDR']."\n\n\n";
                $cur .= "---------------------------------------\n\n";				
                if(strpos($old, $cur) === false){
                    $body .= $cur;
                    $old = $cur;
                    $cur = null;
                }
            }
        }
        $head = "From: PHPIDS\rReply-To: admin@what.ever\r";
        
        if(!empty($this->email)){
            foreach($this->email as $email){
                mail($email, 'Intrusion detected ('.date('d.m.Y H:i').')', $body, $head );		
            }
        }
        
        return true;
    }
    
    /**
     * 	idswarn
     * 
     * 	This function redirects the user to a warning page
     * 
     * 	@author .mario
     * 	@param array $results
     * 	@return boolean 
     */
    private function idswarn($results){
    	
        # do something to warn the user - maybe a redirect?
        return true;
    }
    
    /**
     * 	idswarn
     * 
     * 	This function kicks or bans the malicious user or does 
     * 	other cruel stuff with him.
     * 
     * 	@author .mario
     * 	@param array $results
     * 	@return boolean 
     */		
    private function idskick($results){
    
        # do something very very evil - maybe log the user out?
        return true;	
    }
}
