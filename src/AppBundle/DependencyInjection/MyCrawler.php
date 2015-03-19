<?php
namespace AppBundle\DependencyInjection;

set_time_limit(480);

use PHPCrawler;
use PHPCrawlerDocumentInfo;

/**
 * Description of MyCrawler
 *
 * @author Norman
 */
class MyCrawler extends PHPCrawler{          
    
    public $result = array();
    
    /**
     * Récupère les infos d'une url
     * 
     * @param PHPCrawlerDocumentInfo $pageInfo
     */
    public function handleDocumentInfo(PHPCrawlerDocumentInfo $pageInfo)
    {        
        $page_url = $pageInfo->url;        
        $source = $pageInfo->source;
        $status = $pageInfo->http_status_code;
        
        // Si page "OK" (pas de code erreur) et non vide, affiche l'url
        if($status == 200 && $source!='')
        {            
            echo $page_url.'<br/>';            
            $this->result[] = $page_url;
            
            flush();            
        }      
    }    
}
