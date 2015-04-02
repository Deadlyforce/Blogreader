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
     * Retourne les résultats du test de paramétrage
     * 
     * @Route("/{id}/crawl_param_results", name="blog_crawl_param_results")
     * @Template()
     */
    public function crawlParamResultsAction($id)
    {
        $result = $this->crawlParamEditAction($id);
        $blog = $result['blog'];
        $paramForm = $result['paramForm'];
        
        $requestLimit = $blog->getRequestLimit();
        
        return array(
            'id' => $id,
            'requestLimit' => $requestLimit,
            'paramForm' => $paramForm
        );
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
        
        return array(
            'id' => $id,
            'requestLimit' => $requestLimit
        );
    }
       
    /**
     * Parcours le site concerné
     *      
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

        $crawl->setURL($url);
        
        // Analyse la balise content-type du document, autorise les pages de type text/html
        $crawl->addContentTypeReceiveRule("#text/html#"); 
        
        $this->addURLFilterRules($crawl);
        
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
        
        $encoders = array(new JsonEncoder());
        $normalizers = array(new GetSetMethodNormalizer());        
        $serializer = new Serializer($normalizers, $encoders);
        
        $json_urls = $serializer->serialize($urls, 'json');
        
        $blog->setUrlList($json_urls);
        $em->persist($blog);
        $em->flush();

        return array(
            'urls' => $urls,
            'process_report' => $crawl->getProcessReport()
        );
    }
    
    /**
     * Etablit les règles de filtrage des url ramassées par le crawler
     * 
     * @param object $crawl
     */
    public function addURLFilterRules($crawl)
    {
        // Filtre les url trouvées dans la page en question - ici on garde les pages html uniquement
        $crawl->addURLFilterRule("#(jpg|gif|png|pdf|jpeg|svg|css|js)$# i"); 
        // Vire les url qui contiennent les chaînes suivantes: /forum/, /affiliates/, /register/, -course, archive?, /excerpts/, /books/
        // /subscribe, /privacy-policy, /terms-and-conditions, /search/, /search?, ?comment
        $crawl->addURLFilterRule("#(\/forum\/|\/affiliates\/|\/register\/|\-course|archive\?|\/excerpts\/|\/books\/|\/subscribe|\/privacy\-policy|\/terms\-and\-conditions|\/search\/|\/search\?|\?comment)# i");        
        // Vire les url qui contiennent les chaînes suivantes en fin de d'url : /contact, /books, /downloads, /archive
        $crawl->addURLFilterRule("#(\/contact|\/books|\/downloads|\/archive|\/about)$# i");
        
        // Règles spécifiques à Château-Heartiste - Wordpress platform (de base)
        $crawl->addURLFilterRule("#(\/about\/|\/category\/|openidserver|replytocom|\/author\/|\?shared|\/page\/|\/alpha-assessment-submissions\/|\/beta-of-the-year-contest-submissions\/|dating\-market\-value\-test\-for)# i");
        
        // Règles spécifiques à The Rational Male - Wordpress (développé spécifiquement)
        $crawl->addURLFilterRule("#(\/tag\/)# i");
        $crawl->addURLFilterRule("#(\/the\-book\/|\/donate\/|\/the\-best\-of\-rational\-male\-year\-one\/)$# i");
        
        // Règles spécifiques à RooshV
        $crawl->addURLFilterRule("#(cf\_action\=|doing\_wp\_cron|\%d|\/attachment\/|\/travel\/)# i");
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
        $entity = $em->getRepository('AppBundle:Blog')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Blog entity.');
        }

//        $deleteForm = $this->createDeleteForm($id);

        return array(
            'entity'      => $entity,
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
    
}
