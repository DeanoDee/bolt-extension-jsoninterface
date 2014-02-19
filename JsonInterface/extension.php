<?php
// JsonInterface Extension for Bolt, by Bob den Otter

namespace JsonInterface;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class Extension extends \Bolt\BaseExtension
{


    /**
     * Info block for Sitemap Extension.
     */
    function info()
    {

        $data = array(
            'name' => "JsonInterface",
            'description' => "An extension to output JSON data structures of your content for your Bolt website.",
            'author' => "Bob den Otter",
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
     */
    function initialize()
    {
    
        // Set up the routes for the sitemap..        
        $this->app->match('/{contenttypeslug}/json/data.{extension}', array($this, 'json'))
            ->assert('extension', '(js)')
            ->assert('contenttypeslug', $this->app['storage']->getContentTypeAssert())
        ;

    }

    public function json($contenttypeslug)
    {
		
        // Make sure we output no extra 'fluff'..
        $this->app['extensions']->clearSnippetQueue();
        $this->app['extensions']->disableJquery();
        $this->app['debugbar'] = false;

        $notice = "OK";

		//\util::var_dump($contenttype); 
        if (!empty($contenttypeslug)) {
            $contenttype = $this->app['storage']->getContenttype($contenttypeslug);
            $contentname = strtolower($contenttype["name"]);
            $is_allowed = isset($this->config["allowed"]) && ($this->config["allowed"] === true || array_key_exists($contentname, $this->config["allowed"])); 
            if (empty($contenttype) || !$is_allowed) {
                $notice = "Contenttype '". safeString($contenttypeslug) . "' does not exist or is not allowed.";
            }
        } else {
            $notice = "You should pass a valid contenttype slug.";
        }
        
        if(isset($_GET['filter']) && isset($this->config["allowed"][$contentname]["filters"])){
	        $filters = $this->config["allowed"][$contentname]["filters"];
	        $filter = $_GET['filter'];
	        $where = isset($filters[$filter]) ? $filters[$filter] : array_shift(array_values($filters));
        } else {
	        $where = "";
        }

        if (!empty($_GET['limit'])) {
            $limit = intval($_GET['limit']);
        } else {
            $limit = 10;
        }

        if (!empty($_GET['order'])) {
            $order = safeString($_GET['order']);
        } else {
            $order = "id";
        }

        $records = $this->app['storage']->getContent(
                $contenttype['slug'],
                array('limit' => $limit, 'order' => $order),
                $dummy, $where
            );
            
        $content = array('notice' => '');

        if (!empty($records) && $notice == "OK") {

            foreach($records as $record) {
                $content['records'][ $record->id ] = array(
                    "values" => $record->values,
                    "taxonomy" => $record->taxonomy,
                    "relation" => $record->relation
                );
            }

        } else if($notice == "OK") {

            $notice = "No records matched or no records present";

        }

        $content['notice'] = $notice;

        $headers = array('Content-Type' => 'application/javascript; charset=utf-8');

        if (!empty($_GET['callback'])) {
            $body = sprintf("%s(%s);", safeString($_GET['callback']) , json_encode($content));
        } else {
            $body = json_encode($content);

        }


        return new Response($body, 200, $headers);

    }


}

