<?php
namespace AppBundle\DependencyInjection;

// Autorisation de tourner 8h sans interruption pour le script
set_time_limit(28800);

use PHPCrawler;
use PHPCrawlerDocumentInfo;
use PHPCrawlerResponseHeader;


/**
 * Description of MyCrawler
 *
 * @author Norman
 */
class MyCrawler extends PHPCrawler{          
    
    public $result = array();
    public $content = array();
//    public $counter = 0;
    
    /**
     * Overridable method that will be called after the header of a document was received and BEFORE the content will be received
     *  
     * @param PHPCrawlerResponseHeader $header
     */
    public function handleHeaderInfo(PHPCrawlerResponseHeader $header) 
    {
//var_dump($header);
    }
    
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
            // Test si cette url est déjà présente en base
            
            
//            $this->counter++;
//var_dump($this->counter);
//            echo $page_url.'<br/>';            
//            echo "Links found: " . count($pageInfo->links_found_url_descriptors) .'<br/>'; 

            $this->result[] = $page_url;
            $this->content[] = $pageInfo->content;
            flush();            
        }      
    }    
}
