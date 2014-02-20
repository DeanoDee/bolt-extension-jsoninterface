<?php
// JsonInterface Extension for Bolt, by Bob den Otter & Dino DiGiulio

namespace JsonInterface;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class Extension extends \Bolt\BaseExtension
{

    private $limit = 10;
    
    private $order = "id";
    
    private $notice = "OK";
    
    private $records = array();
    
    private $content_name = "";
    
    private $response_data;

    /**
     * Info block
     */
    function info()
    {

        $data = array(
            'name' => "JsonInterface",
            'description' => "An extension to output JSON data structures of your content for your Bolt website.",
            'author' => "Bob den Otter, DeanoDee",
            'link' => "http://bolt.cm",
            'version' => "0.8",
            'required_bolt_version' => "1.2.0",
            'highest_bolt_version' => "1.2.0",
            'type' => "General",
            'first_releasedate' => "2013-07-19",
            'latest_releasedate' => "2013-09-04",
            'dependancies' => "",
            'priority' => 10
        );

        return $data;

    }

    /**
     * Initialize Sitemap. Called during bootstrap phase.
     * Sets up the specific routing
     */
    function initialize()
    {
      
        $this->app->match('/{contenttypeslug}/json/data.{extension}', array($this, 'json'))
            ->assert('extension', '(js)')
            ->assert('contenttypeslug', $this->app['storage']->getContentTypeAssert())
        ;

    }
 
    /** 
     * Callback to return JSON data
     */
    public function json($contenttypeslug)
    {
        
        //\util::var_dump($this->order);
        
        $this->turnOffUnessessaryActions();
        $this->testAndSetContentName($contenttypeslug);
        $this->testIsAllowed($contenttypeslug);
        $this->setWhere();
        $this->setLimit();
        $this->setOrder();
        $this->setResponseData($contenttypeslug);
        return $this->buildJSONResponse();

    }
    
    /**
     * Set's $notice datamember to a sting indicating issues in assebling data
     */
    private function setNotice($notice)
    {
        if ($this->notice == "OK") {
            $this->notice = $notice;
        } else {
            $this->notice .= " ".$notice;
        }
    }
    
    /**
     * Ensures There is no extra noise created by the JSON assembly
     */
    private function turnOffUnessessaryActions()
    {
        $this->app['extensions']->clearSnippetQueue();
        $this->app['extensions']->disableJquery();
        $this->app['debugbar'] = false;
    }
    
    /**
     * Test the integrety of the content type and sets the $content_type datamember if valid
     * sets notice if not
     * returns boolean
     */
    private function testAndSetContentName($contenttypeslug)
    {
        if (empty($contenttypeslug)) {
            $this->setNotice("You should pass a valid contenttype slug.");
            return false;
        } 
        $content_type = $this->app['storage']->getContenttype($contenttypeslug);
        if (isset($content_type["name"])) {
	    	$this->content_name = strtolower($content_type["name"]);;
    	}
        if (!empty($contenttypeslug)) {
	    	return true; 
        } 
        $notice = "Contenttype '". safeString($contenttypeslug) . "' does not exist.";
        $this->setNotice($notice);
        return false;
    }
    
    /**
     * Checks to see that the requested data has been allowed in the config.yml file
     * Sets the $content_name datamember if valid
     * sets notice if not
     * returns boolean
     */
    private function testIsAllowed($contenttypeslug)
    {
    	$has_allowed = isset($this->config["allowed"]);
    	$all_allowed = $has_allowed ? $this->config["allowed"] === true : false;
    	$filter_allowed = $has_allowed ? array_key_exists($this->content_name, $this->config["allowed"]) : false;
    	
    	
        $is_allowed = $all_allowed || $filter_allowed;
        if ($is_allowed) {
	    	return true; 
        } 
        $notice = "Contenttype '". safeString($contenttypeslug) . "' is not allowed.";
        $this->setNotice($notice);
        return false;
    }
    
    /**
     * Makes use of the url var filter to set the $where datamember with a clause from config.yml if valid
     * returns void if not
     */
    private function setWhere()
    {
   
        $filter_is_set = isset($_GET['filter']) && isset($this->config["allowed"][$this->content_name]["filters"]);
        if(!$filter_is_set) {
	        return;
        } 
         
        $filters = $this->config["allowed"][$this->content_name]["filters"];
        $filter = safeString($_GET['filter']); 
        $this->where = isset($filters[$filter]) ? $filters[$filter] : array_shift(array_values($filters));
    }
    
    /**
     * Makes use of the url var limit to set the $limit datamember if valid
     * returns void if not
     */
    private function setLimit()
    {
        if (empty($_GET['limit'])) return;
        $this->limit = intval($_GET['limit']);
    }
    
    /**
     * Makes use of the url var order to set the $order datamember if valid
     * returns void if not
     */
    private function setOrder(){
        if (empty($_GET['order'])) return;
        $this->order = safeString($_GET['order']);
    }
    
    /**
     * Sets the $reponse_data member with the data to return
     */
    private function setResponseData($contenttypeslug)
    {
        if($this->notice != "OK"){
            $this->response_data['notice'] = $this->notice;
            return;
        }
        
        $records = $this->app['storage']->getContent(
                $contenttypeslug,
                array('limit' => $this->limit, 'order' => $this->order),
                $dummy, 
                $this->where
        );
		
        if (!empty($records)) {
        
            foreach($records as $record) {
                $this->response_data['records'][ $record->id ] = array(
                    "values" => $record->values,
                    "taxonomy" => $record->taxonomy,
                    "relation" => $record->relation
                );
            }

        } else {

            $this->setNotice("No records matched or no records present");

        }
        
        $this->response_data['notice'] = $this->notice;
    }
    
    /**
     * Builds the JSON response
     */
    private function buildJSONResponse()
    {
        $headers = array('Content-Type' => 'application/javascript; charset=utf-8');

        if (!empty($_GET['callback'])) {
            $body = sprintf("%s(%s);", safeString($_GET['callback']) , json_encode($this->response_data));
        } else {
            $body = json_encode($this->response_data);

        }

        return new Response($body, 200, $headers);
    }
}

