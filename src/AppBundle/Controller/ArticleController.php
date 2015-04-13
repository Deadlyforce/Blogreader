<?php

namespace AppBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use AppBundle\Entity\Article;
use AppBundle\Form\ArticleType;
use Symfony\Component\HttpFoundation\JsonResponse;


/**
 * Article controller.
 *
 * @Route("/article")
 */
class ArticleController extends Controller
{

    /**
     * Lists all Article entities.
     *
     * @Route("/", name="article")
     * @Method("GET")
     * @Template()
     */
    public function indexAction()
    {
        $em = $this->getDoctrine()->getManager();
        $entities = $em->getRepository('AppBundle:Article')->findAll();

        return array(
            'entities' => $entities,
        );
    }
    
    /**
     * Index de tous les articles liés à un blog
     * 
     * @param int $blog_id
     * @Route("/{blog_id}/article_index", name="article_index") 
     * @Template()
     */
    public function articlesIndexAction($blog_id)
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
     * Recherche tous les documents associés aux urls stockées dans le blog
     * 
     * @Route("/{blog_id}/fetch", name="articles_fetch")
     * @Template()
     * @param int $blog_id
     */
    public function fetchAction($blog_id)
    {
        $em = $this->getDoctrine()->getManager();
        $blog = $em->getRepository('AppBundle:Blog')->find($blog_id);
        
        $json_urls = $blog->getUrlList();
        $urls = json_decode($json_urls);

        // Récup des 10 premières url pour test
        for($i=0; $i<13; $i++){
            $urlstest[] = $urls[$i];
        }
         
        $chunkArray = array_chunk($urlstest, 10);
        
        $options = array(
            CURLOPT_RETURNTRANSFER => TRUE,
            CURLOPT_FOLLOWLOCATION => TRUE,
            CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 6.3; rv:36.0) Gecko/20100101 Firefox/36.0',
            CURLOPT_HEADER => FALSE           
        );
        
        foreach($chunkArray as $chunk){
            $tab = $this->multicurl($chunk, $options);
            foreach($tab as $subTab){
                foreach($subTab as $url){
                   $articles_source[] = $url;
               }
            }
        }
       
        $counter = 0;
        
        // Enregistrement des sources en base
        foreach($articles_source as $article_source){
            $article = new Article();
            
            $article->setSource($article_source);
            $article->setBlog($blog);
            
            $em->persist($article);
            $em->flush();
            
            $counter++;
        }        
        
        $return  = array(
            'articles_sources' => $articles_source,
            'counter' => $counter,
            'urls' => $urls,
            'blog' => $blog
        );        
        
        return new JsonResponse($return);
       
//        return array(
//            'article_sources' => $articles_source,
//            'counter' => $counter,
//            'urls' => $urls,
//            'blog' => $blog
//        );
    }
    
    /**
     * Multi Curl process
     * 
     * @param array $urls     
     */
    private function multicurl($urls, $options = array())
    {
        $mh = curl_multi_init(); // Multiple Handles
        $results = array();
        $ch = array(); // Curl Handle
        
        foreach($urls as $key => $url){
            $ch[$key] = curl_init();
            
            // Set options
            if($options){
                curl_setopt_array($ch[$key], $options);
            }
            // Set url
            curl_setopt($ch[$key], CURLOPT_URL, $url);
            curl_multi_add_handle($mh, $ch[$key]);
        }
        
        $running = NULL;
        
        do{
            curl_multi_exec($mh, $running); // passe les url au navigateur
        }while($running > 0);

        // Get content and remove handles.
        foreach($ch as $key => $resource){
            $results[$key] = curl_multi_getcontent($resource);
            curl_multi_remove_handle($mh, $resource);
        }
        
        curl_multi_close($mh); // Fermeture de Session Curl
              
        return array(
            'results' => $results
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
     * Finds and displays a Article entity.
     *
     * @Route("/{id}", name="article_show")
     * @Method("GET")
     * @Template()
     */
    public function showAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('AppBundle:Article')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Article entity.');
        }

        $deleteForm = $this->createDeleteForm($id);

        return array(
            'entity'      => $entity,
            'delete_form' => $deleteForm->createView(),
        );
    }

    /**
     * Displays a form to edit an existing Article entity.
     *
     * @Route("/{id}/edit", name="article_edit")
     * @Method("GET")
     * @Template()
     */
    public function editAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('AppBundle:Article')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Article entity.');
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
    /**
     * Deletes a Article entity.
     *
     * @Route("/{id}", name="article_delete")
     * @Method("DELETE")
     */
    public function deleteAction(Request $request, $id)
    {
        $form = $this->createDeleteForm($id);
        $form->handleRequest($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $entity = $em->getRepository('AppBundle:Article')->find($id);

            if (!$entity) {
                throw $this->createNotFoundException('Unable to find Article entity.');
            }

            $em->remove($entity);
            $em->flush();
        }

        return $this->redirect($this->generateUrl('article'));
    }

    /**
     * Creates a form to delete a Article entity by id.
     *
     * @param mixed $id The entity id
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createDeleteForm($id)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('article_delete', array('id' => $id)))
            ->setMethod('DELETE')
            ->add('submit', 'submit', array('label' => 'Delete'))
            ->getForm()
        ;
    }
}
