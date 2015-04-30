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
//use Symfony\Component\HttpFoundation\JsonResponse;


/**
 * Article controller.
 *
 * @Route("/article")
 */
class ArticleController extends Controller
{
    
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
        $articles = $em->getRepository('AppBundle:Article')->findBy(array('blog' => $blog_id));
       
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
        // Vérif si déjà des articles en base
        $em = $this->getDoctrine()->getManager();
        $articles = $em->getRepository('AppBundle:Article')->findBy(array('blog' => $blog_id));
        $blog = $em->getRepository('AppBundle:Blog')->find($blog_id);
        
        if($articles){
            // Vidange de tous les articles avant de récupérer à nouveau un stock
            foreach($articles as $article){
                $em->remove($article);
                $em->flush();
            }
        }
        
        // Request Limit à 0 pour crawler la totalité des url du site après réglages
        $requestLimit = 50;        
        
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
        $process->run();          
        sleep(1);
        
        return array(
            "blog" => $blog
        );
        
//        return $this->redirectToRoute('article', array('blog_id' => $blog_id));  
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
        
//        return array(
//            json_urls => $json_urls
//        );
    }
        
    /**
     * Parcours le site concerné
     * 
     * @Route("/crawl/{id}/{status}/{requestLimit}", name="crawl_test")     
     * @Template()
     */
    public function crawlAction($id, $status = 1, $requestLimit)
    {   
        // Récupérer l'url de l'entité avec l'id de l'entité blog
        $em = $this->getDoctrine()->getManager();
        $blog = $em->getRepository('AppBundle:Blog')->find($id);        
               
        if(!$blog){
            throw $this->createNotFoundException('Impossible de trouver l\'entité blog demandée');            
        }
        
        $url = $blog->getUrl();
        
        // Au lieu de créer une instance de la classe MyCrawler, je l'appelle en tant que service (config.yml)
        $crawl = $this->get('my_crawler');
        
        // Passe l'id du blog au crawler et le statut de la requête (test ou final)
        $crawl->blog_id = $id;
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

        // Récupération des urls
        $urls = $crawl->result;
        $contents = $crawl->content;
        
        // Dépile la première valeur du résultat qui est l'url de la homepage. Non souhaitée.
        array_shift($urls);
        array_shift($contents);
        
        $process_report = $crawl->getProcessReport();       

        return array(
            'urls' => $urls,
            'contents' => $contents,
            'process_report' => $process_report
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
