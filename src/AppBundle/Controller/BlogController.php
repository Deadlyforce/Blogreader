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

use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\GetSetMethodNormalizer;


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
     * @Route("/{id}/show", name="blog_show")
     * @Method("GET")
     * @param int $id Blog id
     * @Template()
     */
    public function showAction($id)
    {           
        // Efface le log du précédent crawl s'il existe dans le dossier log
        $logPath = dirname(dirname(dirname(__DIR__))).'/web/logs';
        $resultArray = glob($logPath ."/crawl_log_for_blogId_". $id ."_*.txt");
        
        if(!empty($resultArray)){
            foreach($resultArray as $result){
                unlink($result);
            }
        }
        // Efface le log FIN
        
        $em = $this->getDoctrine()->getManager();
        $blog = $em->getRepository('AppBundle:Blog')->find($id);
        $article = $em->getRepository('AppBundle:Article')->findBy(array('blog' => $id));
        
        if (!$blog) {
            throw $this->createNotFoundException('Unable to find Blog entity.');
        }
        if (!$article) {
            $article_count = 0;
        }else{
            $article_count = count($article);
        }
        
        if($blog->getLinksFollowed()){
            $process_report = array();
            
            $process_report['date'] = $blog->getLastCrawlDate();
            $process_report['links'] = $blog->getLinksFollowed();
            $process_report['docs'] = $blog->getDocsReceived() - 1;  // -1 pour l'url de base qui est retirée à la sauvegarde
            
            $data = $this->formatBytes($blog->getBytesReceived());
            $process_report['bytes'] = $data;
            
            $timing = $this->convertSeconds($blog->getProcessRuntime());            
            $process_report['time'] = $timing;
        }else{
            $process_report = NULL;
        }

        return array(
            'blog'      => $blog,
//            'urls' => $urls,
//            'urls_count' => $urls_count,
            'process_report' => $process_report,
            'article_count' => $article_count   
//            'delete_form' => $deleteForm->createView(),
        );
    }
    
    /**
     * Retourne une chaîne représentant la durée du process (secondes -> heures min sec)
     * 
     * @param int $seconds
     * @return string
     */
    public function convertSeconds($seconds)
    {
        return sprintf('%02dh:%02dmn:%02ds', ($seconds/3600), ($seconds/60%60), ($seconds%60));
    }
    
    /**
     * Convertit les bytes en KB, MB, GB etc
     * 
     * @param int $bytes
     * @param int $precision
     * @return string
     */
    public function formatBytes($bytes, $precision = 2)
    {
        $units = array('B', 'KB', 'MB', 'GB', 'TB'); 

        $bytes = max($bytes, 0); 
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024)); 
        $pow = min($pow, count($units) - 1); 
        
        $bytes /= pow(1024, $pow);
        
        return round($bytes, $precision) . ' ' . $units[$pow]; 
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
           
}
