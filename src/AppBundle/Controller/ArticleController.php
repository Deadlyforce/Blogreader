<?php

namespace AppBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use AppBundle\Entity\Article;
use AppBundle\Form\ArticleType;

use Symfony\Component\Process\Process;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;

use PHPCrawlerUrlCacheTypes;
use Symfony\Component\DomCrawler\Crawler;
//use Symfony\Component\HttpFoundation\JsonResponse;


/**
 * Article controller.
 *
 * @Route("/article")
 */
class ArticleController extends Controller
{
    /**
     * Find and sort dates from all the articles for a Blog
     * 
     * @Route("/{blog_id}/sort_dates", name="sort_dates")
     * @param int $blog_id Blog id
     * @template()
     */
    public function sortDatesAction($blog_id)
    {
        $em = $this->getDoctrine()->getManager();
        $articles = $em->getRepository("AppBundle:Article")->findBy(array("blog" => $blog_id));
       
        foreach($articles as $article){
            
            $crawler = new Crawler($article->getSource());       

            // recherche de la balise <meta property="article:published_time" content="2007-09-19T09:12:23+00:00" />
            $crawler_meta = $crawler->filter('meta')->reduce(function(Crawler $node, $i){
                return $node->attr('property') === "article:published_time"; // Tout les noeuds qui retournent false sont éliminés
            });
            
            // Si la balise meta spécifique est trouvée
            if($crawler_meta->count() != 0){
                $time = $crawler_meta->attr('content');  
                $date = new \DateTime($time);
            }else{
                // Recherche de la balise html5 <time>
                $crawler_time = $crawler->filter('time')->reduce(function(Crawler $node, $i){
                    return ($node->attr('class') == "entry-date" || $node->attr('class') == "entry-date published");
                }); 
                        
                if($crawler_time->count() != 0){
                    $crawler_time->first()->attr('datetime');
                    $date = new \DateTime($time);
                }else{
                    // Recherche de la balise <abbr>
                    $crawler_abbr = $crawler->filter('abbr');
                    if($crawler_abbr->count() != 0){
                        $time = $crawler_abbr->attr('title');
                        $date = new \DateTime($time);
                    }
                }                                
            }                        
            
            if($date){
                $article->setDate($date); 
                $em->flush();
            } 
        }
        
        return $this->redirectToRoute("article", array('blog_id' => $blog_id));
        
    }
    
    
    /**
     * Extrait le contenu de l'article pour chaque url du blog
     * 
     * @param int $blog_id Blog id
     */
    public function sortContentAction($blog_id)
    {
        $em = $this->getDoctrine()->getManager();
        $em->getRepository("AppBundle:Article")->findBy(array("blog" => $blog_id));
    }
    
    
    /**
     * Index de tous les articles liés à un blog
     * 
     * @param int $blog_id
     * @Route("/{blog_id}/article", name="article") 
     * @Template()
     */
    public function articleAction($blog_id)
    {
        $em = $this->getDoctrine()->getManager();
        $articles = $em->getRepository('AppBundle:Article')->findBy(array('blog' => $blog_id), array('date' => 'DESC'));
       
        $blog = $em->getRepository('AppBundle:Blog')->find($blog_id);
       
        if(!$articles){
            $articles = array();
        }
        
        return array(
            'articles' => $articles,
            'blog' => $blog
        );
    }
            
    /**
     * Creates a new Article entity.
     *
     * @Route("/", name="article_create")
     * @Method("POST")
     * @Template("AppBundle:Article:new.html.twig")
     */
    public function createAction(Request $request)
    {
        $entity = new Article();
        $form = $this->createCreateForm($entity);
        $form->handleRequest($request);

        if ($form->isValid()) {
            $entity->upload();
            $em = $this->getDoctrine()->getManager();
            $em->persist($entity);
            $em->flush();

            return $this->redirect($this->generateUrl('article_show', array('id' => $entity->getId())));
        }

        return array(
            'entity' => $entity,
            'form'   => $form->createView(),
        );
    }

    /**
     * Creates a form to create a Article entity.
     *
     * @param Article $entity The entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createCreateForm(Article $entity)
    {
        $form = $this->createForm(new ArticleType(), $entity, array(
            'action' => $this->generateUrl('article_create'),
            'method' => 'POST',
        ));

        $form->add('submit', 'submit', array('label' => 'Create'));

        return $form;
    }

    /**
     * Displays a form to create a new Article entity.
     *
     * @Route("/new", name="article_new")
     * @Method("GET")
     * @Template()
     */
    public function newAction()
    {
        $entity = new Article();
        $form   = $this->createCreateForm($entity);

        return array(
            'entity' => $entity,
            'form'   => $form->createView(),
        );
    }

    /**
     * Finds and displays an Article entity.
     *
     * @Route("/{id}/{blog_id}/show", name="article_show")
     * @Method("GET")
     * @param int $id Article id
     * @param int $blog_id Blog id
     * @Template()
     */
    public function showAction($id, $blog_id)
    {
        $em = $this->getDoctrine()->getManager();
        $article = $em->getRepository('AppBundle:Article')->find($id);

        if (!$article) {
            throw $this->createNotFoundException('Unable to find Article entity.');
        }

        $deleteForm = $this->createDeleteForm($id, $blog_id);

        return array(
            'article'      => $article,
            'delete_form' => $deleteForm->createView(),
            'blog_id' => $blog_id
        );
    }

    /**
     * Displays a form to edit an existing Article entity.
     *
     * @Route("/{id}/{blog_id}/edit", name="article_edit")
     * @Method("GET")
     * @Template()
     */
    public function editAction($id, $blog_id)
    {
        $em = $this->getDoctrine()->getManager();
        $article = $em->getRepository('AppBundle:Article')->find($id);

        if (!$article) {
            throw $this->createNotFoundException('Unable to find Article entity.');
        }

        $editForm = $this->createEditForm($article);
        $deleteForm = $this->createDeleteForm($id, $blog_id);

        return array(
            'article'      => $article,
            'edit_form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
            'blog_id' => $blog_id
        );
    }

    /**
    * Creates a form to edit a Article entity.
    *
    * @param Article $entity The entity
    *
    * @return \Symfony\Component\Form\Form The form
    */
    private function createEditForm(Article $entity)
    {
        $form = $this->createForm(new ArticleType(), $entity, array(
            'action' => $this->generateUrl('article_update', array('id' => $entity->getId())),
            'method' => 'PUT',
        ));

        $form->add('submit', 'submit', array('label' => 'Update'));

        return $form;
    }
    /**
     * Edits an existing Article entity.
     *
     * @Route("/{id}", name="article_update")
     * @Method("PUT")
     * @Template("AppBundle:Article:edit.html.twig")
     */
    public function updateAction(Request $request, $id)
    {
        $em = $this->getDoctrine()->getManager();
        $entity = $em->getRepository('AppBundle:Article')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Article entity.');
        }

        $deleteForm = $this->createDeleteForm($id);
        $editForm = $this->createEditForm($entity);
        $editForm->handleRequest($request);

        if ($editForm->isValid()) {
            $entity->upload();
            $em->flush();

            return $this->redirect($this->generateUrl('article_edit', array('id' => $id)));
        }

        return array(
            'entity'      => $entity,
            'edit_form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        );
    }
    
//    /**
//     * Deletes an Article entity.
//     *
//     * @param int $id Article Id
//     * @Route("/{id}/delete", name="article_delete")
//     * @Method("DELETE")
//     */
//    public function deleteAction(Request $request, $id)
//    {
//        $form = $this->createDeleteForm($id);
//        $form->handleRequest($request);
//
//        if ($form->isValid()) {
//            $em = $this->getDoctrine()->getManager();
//            $entity = $em->getRepository('AppBundle:Article')->find($id);
//
//            if (!$entity) {
//                throw $this->createNotFoundException('Unable to find Article entity.');
//            }
//
//            $em->remove($entity);
//            $em->flush();
//        }
//
//        return $this->redirect($this->generateUrl('article'));
//    }
    
    /**
     * @Route("/{id}/{blog_id}/delete", name="article_delete")
     */
    public function deleteArticleAction($id, $blog_id)
    {
        $em = $this->getDoctrine()->getManager();
        $article = $em->getRepository("AppBundle:Article")->find($id);
        
        if(!$article){
            throw $this->createNotFoundException("Unable to find Article entity.");
        }
        
        $em->remove($article);
        $em->flush();
        
        return $this->redirectToRoute("article", array('blog_id' => $blog_id));
    }

    /**
     * Creates a form to delete a Article entity by id.
     *
     * @param mixed $id The entity id
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createDeleteForm($id, $blog_id)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('article_delete', array('id' => $id, 'blog_id' => $blog_id)))
            ->setMethod('DELETE')
            ->add('submit', 'submit', array('label' => 'Delete'))
            ->getForm()
        ;
    }
    
    /**
     * Parcours le site avec les réglages finaux du crawler
     * Cette méthode rend le controleur crawlAction dans le template
     * 
     * @param int $blog_id Blog id
     * 
     * @Route("/crawl/{blog_id}", name="article_crawl_results")
     * @Template()
     */
    public function crawlResultsAction($blog_id)
    {
        
        $em = $this->getDoctrine()->getManager();
        $blog = $em->getRepository('AppBundle:Blog')->find($blog_id);
        
        // Efface les articles existants pour ce blog
        $this->deleteBlogArticlesAction($blog_id);
        
        // Request Limit à 0 pour crawler la totalité des url du site après réglages
        $requestLimit = 0;        
        
        ini_set('memory_limit', '256M');
        
        // crawl report is now saved in the command
                        
        $status = 1;        
        $cmd = 'php ../app/console article:crawl'.' '.$blog_id.' '.$status.' '.$requestLimit;
        
        // CONDITION AVEC INSTRUCTION EQUIVALENTE POUR LINUX
        if(strtoupper(substr(PHP_OS, 0, 3)) === 'WIN'){
            $command = "start /B ".$cmd." > NUL 2>&1";
        }else{
            $command = "nohup " . $cmd." > /dev/null 2>&1 &";
        }
        
        $process =  new Process($command);
        $process->disableOutput();
        $process->run();          
        sleep(1);
        
        return array(
            "blog" => $blog
        );
        
//        return $this->redirectToRoute('article', array('blog_id' => $blog_id));  
    } 
    
    /**
     * Deletes all the articles from a specific blog
     * 
     * @param int $blog_id Blog id
     * @Route("/{blog_id}/articles_delete", name="articles_delete")
     * @Template()
     */
    public function deleteBlogArticlesAction($blog_id)
    {
        // Vérif si déjà des articles en base
        $em = $this->getDoctrine()->getManager();
        $articles = $em->getRepository('AppBundle:Article')->findBy(array('blog' => $blog_id));        
        
        if($articles){
            // Vidange de tous les articles avant de récupérer à nouveau un stock
            foreach($articles as $article){
                $em->remove($article);
                $em->flush();
            }
        }
        
        return array(
            'message' => 'Articles delete success'
        );
    }
   
    /**
     * Gets the log file (within logs folder) and returns its results
     * 
     * @param int $blog_id Blog id
     * @Route("/{blog_id}/ajax_polling", name="ajax_polling")
     * @Template()
     */
    public function ajaxPollingAction($blog_id)
    {               
        $logPath = dirname(dirname(dirname(__DIR__))).'/web/logs';        
        
        // Attend 1s de façon répétée que le fichier log soit trouvé
        while(empty(glob($logPath ."/crawl_log_for_blogId_". $blog_id ."_*.txt"))){
            sleep(1);
        }
        
        $resultArray = glob($logPath ."/crawl_log_for_blogId_". $blog_id ."_*.txt");        
        $log_txt = $resultArray[0];
        
        while(true){
            // if ajax request has sent a timestamp, then $last_ajax_call = timestamp, else $last_ajax_call = null
            $last_ajax_call = isset($_GET['timestamp']) ? (int)$_GET['timestamp'] : null;
            
            // PHP caches file data, like requesting the size of a file, by default. clearstatcache() clears that cache
            clearstatcache();
            
            // get timestamp of when file has been changed the last time
            $last_change_in_data_file = filemtime($log_txt);
            
            // if no timestamp delivered via ajax or $log_txt has been changed SINCE last ajax timestamp
            if($last_ajax_call == null || $last_change_in_data_file > $last_ajax_call){

                // get content of data.txt
                $data = file_get_contents($log_txt);

                // return log_txt's content and timestamp of last log_txt change into array

                return new JsonResponse(array(
                    'data_from_file' => $data,
                    'timestamp' => $last_change_in_data_file
                )); 
                
                // leave this loop step
                break;

            }else{
                // wait for 1 sec (not very sexy as this blocks the PHP/Apache process, but that's how it goes)
                sleep(1);
                continue;
            }
        }
    }
        
    /**
     * Parcours le site concerné
     * 
     * @Route("/crawl/{blog_id}/{status}/{requestLimit}", name="crawl_test")     
     * @Template()
     * @param int $blog_id Blog id
     * @param int $requestLimit number of urls to crawl (fullcrawl = 0, user defined = testcrawl)
     * @param int $status Used in the crawl process status = 1 default, status = 0 testcrawl
     */
    public function crawlTestAction($blog_id, $requestLimit, $status = 1)
    {                 
        set_time_limit(14400); // 4h de délai d'éxecution du script
        $cmd = 'php ../app/console article:crawl'.' '.$blog_id.' '.$status.' '.$requestLimit;
        
        $process =  new Process($cmd);
        $process->setTimeout(14400);
        $process->run(); 
        
        $output = $process->getOutput();
        $tab = str_replace("\r\n"," ",$output); // Suppression des sauts de ligne
        $urls = explode(" ", $tab); // Fabrication du tableau d'après un "string"

        return array(
            'urls' => $urls,
//            'process_report' => $tab['process_report']
        );
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
