<?php

namespace AppBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use AppBundle\Entity\Blog;
use AppBundle\Form\BlogType;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;


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
     * Parcours le site concerné
     * 
     * @Route("/crawl", name="blog_crawl")
     * @Template()
     */
    public function crawlAction($url = 'http://markmanson.net')
    {               
        // Au lieu de créer une instance de la classe MyCrawler, je l'appelle en tant que service (config.yml)
        $crawl = $this->get('my_crawler');

        $crawl->setURL($url);
        
        // Analyse la balise content-type du document, autorise les pages de type text/html
        $crawl->addContentTypeReceiveRule("#text/html#"); 
        
        // Filtre les url trouvées dans la page en question - ici on garde les pages html uniquement
        $crawl->addURLFilterRule("#(jpg|gif|png|pdf|jpeg|svg|css|js)$# i"); 
        
        // Vire les url qui contiennent les chaînes suivantes: /forum/ ou /affiliates/ ou /register/ ou -course ou archive? ou /excerpts/ ou /books/
        // ou /subscribe ou /privacy-policy ou /terms-and-conditions
        $crawl->addURLFilterRule("#(\/forum\/|\/affiliates\/|\/register\/|\-course|archive\?|\/excerpts\/|\/books\/|\/subscribe|\/privacy\-policy|\/terms\-and\-conditions)# i"); 
        
        $crawl->enableCookieHandling(TRUE);
        
        // Sets a limit to the number of bytes the crawler should receive alltogether during crawling-process.
        $crawl->setTrafficLimit(0);
        
        // Sets a limit to the total number of requests the crawler should execute.
//        $crawl->setRequestLimit(50);
        
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
        $crawl->setCrawlingDepthLimit(1);
        
        $crawl->obeyRobotsTxt(TRUE);
        $crawl->setUserAgentString("Mozilla/5.0 (Windows NT 6.3; rv:36.0) Gecko/20100101 Firefox/36.0");
      
        $crawl->go();
        
        // At the end, after the process is finished, we print a short 
        // report (see method getProcessReport() for more information) 
        $report = $crawl->getProcessReport(); 
        
        echo "Summary:".'<br/>'; 
        echo "Links followed: ".$report->links_followed.'<br/>'; 
        echo "Documents received: ".$report->files_received.'<br/>'; 
        echo "Bytes received: ".$report->bytes_received." bytes".'<br/>'; 
        echo "Process runtime: ".$report->process_runtime." sec".'<br/>';
        echo "Abort reason: ".$report->abort_reason.'<br/>';

        return array(
            'urls' => $crawl->result
        );
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

        $deleteForm = $this->createDeleteForm($id);

        return array(
            'entity'      => $entity,
            'delete_form' => $deleteForm->createView(),
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
    
    /**
     * Deletes a Blog entity.
     *
     * @Route("/{id}", name="blog_delete")
     * @Method("DELETE")
     */
    public function deleteAction(Request $request, $id)
    {
        $form = $this->createDeleteForm($id);
        $form->handleRequest($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $entity = $em->getRepository('AppBundle:Blog')->find($id);

            if (!$entity) {
                throw $this->createNotFoundException('Unable to find Blog entity.');
            }

            $em->remove($entity);
            $em->flush();
        }

        return $this->redirect($this->generateUrl('blog'));
    }

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
    
}
