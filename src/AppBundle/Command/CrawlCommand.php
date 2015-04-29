<?php

namespace AppBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use PHPCrawlerUrlCacheTypes;

class CrawlCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('article:crawl')
            ->setDescription('Lancement du crawler sur la totalité du site')
            ->addArgument('blog_id', InputArgument::REQUIRED)
            ->addArgument('status', InputArgument::REQUIRED)    
            ->addArgument('requestLimit', InputArgument::REQUIRED)    
        ;
    }
    
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $logger = $this->getContainer()->get('logger');
        $logger->info('Début de l\'exécution de la commande');
        
        // Traitement : appel de l'action crawler avec arguments, dans le controller ArticleController 
        $blog_id = $input->getArgument('blog_id');
        $status = $input->getArgument('status');
        $requestLimit = $input->getArgument('requestLimit');
       
        // Récupérer l'url de l'entité avec l'id de l'entité blog
        $em = $this->getContainer()->get('Doctrine')->getManager();
        $blog = $em->getRepository('AppBundle:Blog')->find($blog_id);        
               
        if(!$blog){
            throw $this->createNotFoundException('Impossible de trouver l\'entité blog demandée');            
        }
        
        $url = $blog->getUrl();
        
        // Au lieu de créer une instance de la classe MyCrawler, je l'appelle en tant que service (config.yml)
        $crawl = $this->getContainer()->get('my_crawler');
        
        // Passe l'id du blog au crawler et le statut de la requête (test ou final)
        $crawl->blog_id = $blog_id;
        // Détermine si il s'agit d'un crawl final (toutes url + sauvegarde et donc valeur 1) ou d'un test (valeur 0)
        $crawl->status = $status;
        // Passe l'entity manager au service
        $crawl->em = $em;       
       
        // Sets the target url
        $crawl->setURL($url);
        
        // Spidering huge websites : activates the SQLite-cache 
        $crawl->setUrlCacheType(PHPCrawlerUrlCacheTypes::URLCACHE_SQLITE);
        
        // Analyse la balise content-type du document, autorise les pages de type text/html
        $crawl->addContentTypeReceiveRule("#text/html#"); 
        
        // Filter Rules
        $url_excluded_words = $blog->getUrlExcludedWords();
        $url_excluded_endwords = $blog->getUrlExcludedEndWords();
        $url_excluded_date = $blog->getUrlExcludedDate();
        $url_excluded_year = $blog->getUrlExcludedYear();
        
        $this->addURLFilterRules($crawl, $url_excluded_words, $url_excluded_endwords, $url_excluded_date, $url_excluded_year);
        // Filter Rules End
        
        $crawl->enableCookieHandling(TRUE);
        
        // Sets a limit to the number of bytes the crawler should receive alltogether during crawling-process.
        $crawl->setTrafficLimit(0);
        
        // Sets a limit to the total number of requests the crawler should execute.
        $crawl->setRequestLimit($requestLimit);
        
        // Sets the content-size-limit for content the crawler should receive from documents.
        $crawl->setContentSizeLimit(0);
        
        // 2 - The crawler will only follow links that lead to the same host like the one in the root-url.
        // E.g. if the root-url (setURL()) is "http://www.foo.com", the crawler will ONLY follow links to "http://www.foo.com/...", but not
        // to "http://bar.foo.com/..." and "http://www.another-domain.com/...". This is the default mode.
        $crawl->setFollowMode(2);
        
        // Sets the timeout in seconds for waiting for data on an established server-connection.
        $crawl->setStreamTimeout(20);
        
        // Sets the timeout in seconds for connection tries to hosting webservers.
        $crawl->setConnectionTimeout(20);
        
        // For instance: If the maximum depth is set to 1, the crawler only will follow links found in the entry-page
        // of the crawling-process, but won't follow any further links found in underlying documents.
//        $crawl->setCrawlingDepthLimit(3);
        
        $crawl->obeyRobotsTxt(TRUE);
        $crawl->setUserAgentString("Mozilla/5.0 (Windows NT 6.3; rv:36.0) Gecko/20100101 Firefox/36.0");
      
        $crawl->go();

        // TESTCRAWL : Récupération des urls (variables vides lors du crawl complet)
        $urls = $crawl->result;
        $contents = $crawl->content;
        
        // TESTCRAWL : Dépile la première valeur du résultat qui est l'url de la homepage. Non souhaitée.
        array_shift($urls);
        array_shift($contents);
        
        $process_report = $crawl->getProcessReport();  
        
        // Sauvegarde du rapport de crawl
        $this->saveReportAction($blog_id, $process_report);

        return array(
            'urls' => $urls,
            'contents' => $contents,
            'process_report' => $process_report
        );                
        
    }
    
    /**
     * Saves the crawler report
     * 
     * @param int $blog_id Blog id
     * @param array $process_report Crawler Stats
     */
    public function saveReportAction($blog_id, $process_report)
    {
        $em = $this->getContainer()->get('Doctrine')->getManager();
        $blog = $em->getRepository('AppBundle:Blog')->find($blog_id);
        
        // Date actuelle
        $date = new \DateTime('', new \DateTimeZone('Europe/Paris')); 
                        
        // Enregistrement du process_report dans Blog
        $blog->setLinksFollowed($process_report->links_followed);
        $blog->setDocsReceived($process_report->files_received);
        $blog->setProcessRuntime($process_report->process_runtime);
        $blog->setBytesReceived($process_report->bytes_received);
        
        $blog->setLastCrawlDate($date);
          
        $em->persist($blog);
        $em->flush();
    }
    
    /**
     * Etablit les règles de filtrage des url ramassées par le crawler
     * 
     * @param object $crawl
     */
    public function addURLFilterRules($crawl, $url_ex_words, $url_ex_endwords, $url_excluded_date, $url_excluded_year)
    {
        // Conditions au cas ou il n'y a encore aucune règle dans la base
        if(!is_array($url_ex_words)){
            $url_excluded_words = json_decode($url_ex_words);
        }else{
            $url_excluded_words = array();
        }
        
        if(!is_array($url_ex_endwords)){
            $url_excluded_endwords = json_decode($url_ex_endwords);
        }else{
            $url_excluded_endwords = array();
        }
                
        // Echappement des caractères spéciaux
        foreach($url_excluded_words as $key => $value){
            $url_excluded_words[$key] = preg_quote($value, '/');
        }
        foreach($url_excluded_endwords as $key => $value){
            $url_excluded_endwords[$key] = preg_quote($value, '/');
        }

        $string_excluded_words = implode("|", $url_excluded_words);
        $string_excluded_endwords = implode("|", $url_excluded_endwords);
      
        // Filtre les url trouvées dans la page en question - ici on garde les pages html uniquement
        $crawl->addURLFilterRule("#(jpg|gif|png|pdf|jpeg|svg|css|js)$# i"); 
       
        // Règles définies par l'utilisateur, spécifiques à chaque blog
        // Vire les url qui contiennent ce type de chaînes : /affiliates/, /register/, -course, archive? etc... 
        if($string_excluded_words != ''){
            $crawl->addURLFilterRule("#($string_excluded_words)# i");        
        }
        // Vire les url qui contiennent les chaînes suivantes en fin de d'url
        if($string_excluded_endwords != ''){
            $crawl->addURLFilterRule("#($string_excluded_endwords)$# i");        
        }
        
        // Règle pour supprimer les url contenant des dates comme /2014/10/ en fin de chaîne
        if($url_excluded_date){
            $crawl->addURLFilterRule("#(\/[0-9]{4}\/(0[1-9]|1[0-2])\/)$# i");
        }
        // Règle pour supprimer les url contenant des dates comme /2014/ en fin de chaîne
        if($url_excluded_year){
            $crawl->addURLFilterRule("#(\/[0-9]{4}\/)$# i");
        }
    }
}
