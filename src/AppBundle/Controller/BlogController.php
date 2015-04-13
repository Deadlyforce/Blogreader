<?php

namespace AppBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use AppBundle\Entity\Blog;
use AppBundle\Form\BlogType;
use AppBundle\Form\BlogParamType;

use Symfony\Component\Security\Core\Exception\AccessDeniedException;

use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\GetSetMethodNormalizer;
use Symfony\Component\DomCrawler\Crawler;


/**
 * Blog controller.
 *
 * @Route("/blog")
 */
class BlogController extends Controller
{       
    /**
     * Lists all Blog entities.
     *
     * @Route("/", name="blog")
     * @Method("GET")
     * @Template()
     */
    public function indexAction()
    {
        $em = $this->getDoctrine()->getManager();
        $entities = $em->getRepository('AppBundle:Blog')->findAll();

        return array(
            'entities' => $entities,
        );
    }
    
    /**
     * Creates a new Blog entity.
     *
     * @Route("/", name="blog_create")
     * @Method("POST")
     * @Template("AppBundle:Blog:new.html.twig")
     */
    public function createAction(Request $request)
    {
        $entity = new Blog();
        $form = $this->createCreateForm($entity);
        $form->handleRequest($request);         
               
        if ($form->isValid()) {
//            $entity->upload();
            $em = $this->getDoctrine()->getManager();
            $em->persist($entity);
            $em->flush();

            return $this->redirect($this->generateUrl('blog_show', array('id' => $entity->getId())));
        }

        return array(
            'entity' => $entity,
            'form'   => $form->createView(),
        );
    }

    /**
     * Creates a form to create a Blog entity.
     *
     * @param Blog $entity The entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createCreateForm(Blog $entity)
    {
        $form = $this->createForm(new BlogType(), $entity, array(
            'action' => $this->generateUrl('blog_create'),
            'method' => 'POST',
        ));

        $form->add('submit', 'submit', array('label' => 'Create'));

        return $form;
    }
    
    /**
     * Paramétrage du crawler avec un échantillon d'urls
     * 
     * @Route("/crawl_param/{id}/edit", name="blog_crawl_param_edit")
     * @Method("GET")
     * @Template()
     */
    public function crawlParamEditAction($id)
    {
        $em = $this->getDoctrine()->getManager();
        $blog = $em->getRepository('AppBundle:Blog')->find($id);
        
        if (!$blog) {
            throw $this->createNotFoundException('Unable to find Blog entity.');
        }
        
        // Récupération des mots filtres pour les url en base
        $words = $blog->getUrlExcludedWords();        
        $endwords = $blog->getUrlExcludedEndWords();
        
        // Prévision du cas où la colonne json_array est vide et retourne un array vide
        if(!is_array($words)){
            $wordString = implode(',', json_decode($words));
        }else{
            $wordString = '';
        }
        
        if(!is_array($endwords)){
            $endwordString = implode(',', json_decode($endwords));
        }else{
            $endwordString = '';
        }
        
        $blog->setUrlExcludedWords($wordString);
        $blog->setUrlExcludedEndWords($endwordString);
       
        $paramForm = $this->createParamForm($blog);        
        
        return array(
            'paramForm' => $paramForm->createView(),
            'blog' => $blog
        );
        
    }
    
    /**
     * Update les paramètres de réglage du crawler
     * 
     * @param Request $request
     * @param int $id
     * 
     * @Route("/crawl_param/{id}/update", name="blog_crawl_param_update")
     * @Method("PUT")
     * @Template("AppBundle:Blog:crawlParamEdit.html.twig")
     */
    public function crawlParamUpdateAction(Request $request, $id)
    {
        $em = $this->getDoctrine()->getManager();
        $blog = $em->getRepository('AppBundle:Blog')->find($id);
        
        if (!$blog) {
            throw $this->createNotFoundException('Unable to find Blog entity.');
        }

        $paramForm = $this->createParamForm($blog);

        // Transformation de la chaîne de mots clé en array
        $encoders = array(new JsonEncoder());
        $normalizers = array(new GetSetMethodNormalizer());        
        $serializer = new Serializer($normalizers, $encoders);
      
        $requestArray = $request->request->get('blogParam');
        
        $wordsArray = explode(',', $requestArray['url_excluded_words']);
        $json_wordsArray = $serializer->serialize($wordsArray, 'json');
        
        $endwordsArray = explode(',', $requestArray['url_excluded_endwords']);
        $json_endwordsArray = $serializer->serialize($endwordsArray, 'json');
        // Transformation fin
        
        $requestArray['url_excluded_words'] = $json_wordsArray;
        $requestArray['url_excluded_endwords'] = $json_endwordsArray;

        $request->request->set('blogParam', $requestArray); 

        $paramForm->handleRequest($request);

        if ($paramForm->isValid()) {

            $em->flush();

            return $this->redirect($this->generateUrl('blog_crawl_param_edit', array('id' => $id)));
        }

        return array(
            'blog'      => $blog,
            'param_form'   => $paramForm->createView()
        );
    }
    
    /**
     * Creates a form to add parameters for the crawler settings
     * 
     * @param Blog $entity
     */
    public function createParamForm(Blog $entity)
    {
        $form = $this->createForm(new BlogParamType(), $entity, array(
            'action' => $this->generateUrl('blog_crawl_param_update', array('id' => $entity->getId())),
            'method' => 'PUT'
        ));
            
        $form->add('submit', 'submit', array(
            'label' => 'Save options'
        ));
        
        return $form;
    }
    
    /**
     * Parcours le site avec les réglages finaux du crawler
     * Cette méthode rend le controleur crawlAction dans le template
     * 
     * @Route("/crawl/{id}", name="blog_crawl_results")
     * @Template()
     */
    public function crawlResultsAction($id)
    {
        // Request Limit à 0 pour crawler la totalité des url du site après réglages
        $requestLimit = 0;        
        
        // Essayer: appel de crawlAction puis affichage classique - désactiver le rendu de crawlAction dans le template
        $crawlResults = $this->crawlAction($id, $requestLimit);
        
        $urls = $crawlResults['urls'];
        $process_report = $crawlResults['process_report'];
        $blog = $crawlResults['blog'];
        $em = $crawlResults['em'];
        
        // Date actuelle
        $date = new \DateTime('', new \DateTimeZone('Europe/Paris'));
        
        // Enregistrement des résultats en base
        $encoders = array(new JsonEncoder());
        $normalizers = array(new GetSetMethodNormalizer());        
        $serializer = new Serializer($normalizers, $encoders);
       
        $json_urls = $serializer->serialize($urls, 'json');
               
        $blog->setUrlList($json_urls);
        $blog->setUrlListDate($date);
        
        $em->persist($blog);
        $em->flush();
        
        return array(
            'urls' => $urls,
            'process_report' => $process_report,
            'blog' => $blog
        );
    }
       
    /**
     * Parcours le site concerné
     * 
     * @Route("/crawl/{id}/{requestLimit}", name="blog_crawl")     
     * @Template()
     */
    public function crawlAction($id, $requestLimit)
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
        
        // Sets the target url
        $crawl->setURL($url);
        
        // Analyse la balise content-type du document, autorise les pages de type text/html
        $crawl->addContentTypeReceiveRule("#text/html#"); 
        
        // Filter Rules
        $url_excluded_words = $blog->getUrlExcludedWords();
        $url_excluded_endwords = $blog->getUrlExcludedEndWords();
        $url_excluded_date = $blog->getUrlExcludedDate();
        
        $this->addURLFilterRules($crawl, $url_excluded_words, $url_excluded_endwords, $url_excluded_date);
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
        
        // Update de l'entité en BDD
        $urls = $crawl->result;
        // Dépile la première valeur du résultat qui est l'url de la homepage. Non souhaitée.
        array_shift($urls);
        $process_report = $crawl->getProcessReport();       

        return array(
            'urls' => $urls,
            'process_report' => $process_report,
            'blog' => $blog,
            'em' => $em
        );
    }
    
    /**
     * Etablit les règles de filtrage des url ramassées par le crawler
     * 
     * @param object $crawl
     */
    public function addURLFilterRules($crawl, $url_ex_words, $url_ex_endwords, $url_excluded_date)
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
    }

    /**
     * Displays a form to create a new Blog entity.
     *
     * @Route("/new", name="blog_new")
     * @Method("GET")
     * @Template()
     */
    public function newAction()
    {
        $entity = new Blog();
        $form   = $this->createCreateForm($entity);

        return array(
            'entity' => $entity,
            'form'   => $form->createView(),
        );
    }

    /**
     * Finds and displays a Blog entity.
     *
     * @Route("/{id}", name="blog_show")
     * @Method("GET")
     * @Template()
     */
    public function showAction($id)
    {
        $em = $this->getDoctrine()->getManager();
        $blog = $em->getRepository('AppBundle:Blog')->find($id);

        if (!$blog) {
            throw $this->createNotFoundException('Unable to find Blog entity.');
        }
        
        // Traitement du json array d'urls
        $json_list = $blog->getUrlList();
        if($json_list){
            $urls = json_decode($json_list);
            $urls_count = count($urls);
        }else{
            $urls = NULL;
            $urls_count = 0;
        }       

        return array(
            'blog'      => $blog,
            'urls' => $urls,
            'urls_count' => $urls_count
//            'delete_form' => $deleteForm->createView(),
        );
    }

    /**
     * Displays a form to edit an existing Blog entity.
     *
     * @Route("/{id}/edit", name="blog_edit")
     * @Method("GET")
     * @Template()
     */
    public function editAction($id)
    {
        $em = $this->getDoctrine()->getManager();
        $entity = $em->getRepository('AppBundle:Blog')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Blog entity.');
        }

        $editForm = $this->createEditForm($entity);
        $deleteForm = $this->createDeleteForm($id);

        return array(
            'entity'      => $entity,
            'edit_form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        );
    }

    /**
    * Creates a form to edit a Blog entity.
    *
    * @param Blog $entity The entity
    *
    * @return \Symfony\Component\Form\Form The form
    */
    private function createEditForm(Blog $entity)
    {
        $form = $this->createForm(new BlogType(), $entity, array(
            'action' => $this->generateUrl('blog_update', array('id' => $entity->getId())),
            'method' => 'PUT',
        ));

        $form->add('submit', 'submit', array('label' => 'Update'));

        return $form;
    }
        
    /**
     * Edits an existing Blog entity.
     *
     * @Route("/{id}", name="blog_update")
     * @Method("PUT")
     * @Template("AppBundle:Blog:edit.html.twig")
     */
    public function updateAction(Request $request, $id)
    {
        $em = $this->getDoctrine()->getManager();
        $entity = $em->getRepository('AppBundle:Blog')->find($id);
        
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Blog entity.');
        }

        $deleteForm = $this->createDeleteForm($id);
        $editForm = $this->createEditForm($entity);
        $editForm->handleRequest($request);

        if ($editForm->isValid()) {
//            $entity->upload();
            $em->flush();

            return $this->redirect($this->generateUrl('blog_edit', array('id' => $id)));
        }

        return array(
            'entity'      => $entity,
            'edit_form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        );
    }
    
//    /**
//     * Deletes a Blog entity.
//     *
//     * @Route("/{id}", name="blog_delete")
//     * @Method("DELETE")
//     */
//    public function deleteAction(Request $request, $id)
//    {
//        $form = $this->createDeleteForm($id);
//        $form->handleRequest($request);
//
//        if ($form->isValid()) {
//            $em = $this->getDoctrine()->getManager();
//            $entity = $em->getRepository('AppBundle:Blog')->find($id);
//
//            if (!$entity) {
//                throw $this->createNotFoundException('Unable to find Blog entity.');
//            }
//
//            $em->remove($entity);
//            $em->flush();
//        }
//
//        return $this->redirect($this->generateUrl('blog'));
//    }

    /**
     * Creates a form to delete a Blog entity by id.
     *
     * @param mixed $id The entity id
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createDeleteForm($id)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('blog_delete', array('id' => $id)))
            ->setMethod('DELETE')
            ->add('submit', 'submit', array('label' => 'Supprimer ce blog'))
            ->getForm()
        ;
    }
    
    /**
     * Efface un blog
     * 
     * @Route("/{id}/delete", name="blog_delete")
     */
    public function deleteBlogAction($id)
    {
        $em = $this->getDoctrine()->getManager();
        $blog = $em->getRepository('AppBundle:Blog')->find($id);
        
        if(!$blog){
            throw $this->createNotFoundException('Unable to find Blog entity.');            
        }
        
        $em->remove($blog);
        $em->flush();
        
        return $this->redirect($this->generateUrl('blog'));
    }    
        
    /**
     * Delete an url from the json array and save to database
     * 
     * @Route("/{id}/{key}/delete_url", name="blog_delete_url")
     */
    public function deleteUrlAction($id, $key)
    {
        $em = $this->getDoctrine()->getManager();
        $blog = $em->getRepository('AppBundle:Blog')->find($id);
        
        $json_urls = $blog->getUrlList();
        $urls = json_decode($json_urls);
        
        unset($urls[$key]);
        $urls = array_values($urls);

        // Enregistrement en base
        $encoders = array(new JsonEncoder());
        $normalizers = array(new GetSetMethodNormalizer());        
        $serializer = new Serializer($normalizers, $encoders);
       
        $json_urls = $serializer->serialize($urls, 'json');
        
        $blog->setUrlList($json_urls);
        $em->persist($blog);
        $em->flush();
        
        return $this->redirect($this->generateUrl('blog_show', array('id' => $id)));
    }
    
}
