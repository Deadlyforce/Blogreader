<?php
namespace AppBundle\DependencyInjection;

// Autorisation de tourner 8h sans interruption pour le script
set_time_limit(28800);


use PHPCrawler;
use PHPCrawlerDocumentInfo;
use PHPCrawlerResponseHeader;

use Doctrine\ORM\EntityManager;
use AppBundle\Entity\Article;

/**
 * Description of MyCrawler
 *
 * @author Norman
 */
class MyCrawler extends PHPCrawler{          
    
    public $result = array();
    public $content = array();  
    
    public $blog_id;
    public $status; 
    public $counter = 0;
    
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
            $this->counter++;
            // Ecriture d'un fichier getOutput.php qui contient les résultats actuels (en cours)

//            echo $this->blog_id;
//            echo $this->status;            
            
//            echo $page_url.'<br/>';            
//            echo "Links found: " . count($pageInfo->links_found_url_descriptors) .'<br/>'; 
            
            // $status = 1 pour crawl complet avec sauvegarde / $status = 0 pour les tests
            // le counter est là pour éviter l'enregistrement de la première entrée, qui est l'url de base
            if($this->status == 1 && $this->counter > 1){
                // Crawl complet
                $blog = $this->em->getRepository('AppBundle:Blog')->find($this->blog_id);
                
                $article = new Article();
                
                // Date actuelle
                $date = new \DateTime('', new \DateTimeZone('Europe/Paris'));       
                $article->setSaveDate($date);

                $article->setBlog($blog);            
                $article->setUrl($page_url);
                $article->setSource($pageInfo->content);

                $this->em->persist($article);
                $this->em->flush();
                
                echo $page_url.'<br/>'; 
                flush(); 
                                
            }else{
                // Tests ($status == 0)
                $this->result[] = $page_url;
                $this->content[] = $pageInfo->content;
            }                               
        }      
    }    
}
