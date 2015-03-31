<?php

namespace Ens\JobeetBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use Ens\JobeetBundle\Entity\Job;
use Ens\JobeetBundle\Form\JobType;

/**
 * Job controller.
 *
 */
class JobController extends Controller
{

    /**
     * Lists all Job entities.
	 * @return \Symfony\Component\HttpFoundation\Response
	 */
    public function indexAction()
    {
        $em = $this->getDoctrine()->getManager();
        $format = $this->getRequest()->getRequestFormat();
        
		$categories = $em->getRepository('EnsJobeetBundle:Category')->getWithJobs();
		
		//enregistre les offres tant quelles dÃ©passent pas la valeur de 'max_jobs_on_homepage'
		foreach($categories as $category)
		{
			$category->setActiveJobs($em->getRepository('EnsJobeetBundle:Job')
					->getActiveJobs($category->getId(), $this->container->getParameter('max_jobs_on_homepage'))
				);
			$category->setMoreJobs($em->getRepository('EnsJobeetBundle:Job')->countActiveJobs($category->getId()) - $this->container->getParameter('max_jobs_on_homepage'));
		}
		
		return $this->render('EnsJobeetBundle:Job:index.'.$format.'.twig', array(
				'categories' => $categories,
				'lastUpdated' => $em->getRepository('EnsJobeetBundle:Job')->getLatestPost()->getCreatedAt()->format(DATE_ATOM),
				'feedId' => sha1($this->get('router')->generate('ens_job', array('_format'=> 'atom'), true)),
			));
    }
	
    /**
     * Creates a new Job entity.
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function createAction()
    {
        $entity = new Job();
        $request = $this->getRequest();
        $form = $this->createForm(new JobType(), $entity);
        $form->bind($request);

        if ($form->isValid())
        {
            $em = $this->getDoctrine()->getManager();
            
            $em->persist($entity);
            $em->flush();

			 return $this->redirect($this->generateUrl('ens_job_preview', array(
					'company' => $entity->getCompanySlug(),
					'location' => $entity->getLocationSlug(),
					'token' => $entity->getToken(),
					'position' => $entity->getPositionSlug()
				)));
        }

        return $this->render('EnsJobeetBundle:Job:new.html.twig', array(
            'entity' => $entity,
            'form'   => $form->createView(),
        ));
    }

//     /**
//      * Creates a form to create a Job entity.
//      *
//      * @param Job $entity The entity
//      *
//      * @return \Symfony\Component\Form\Form The form
//      */
//     private function createCreateForm(Job $entity)
//     {
//         $form = $this->createForm(new JobType(), $entity, array(
//             'action' => $this->generateUrl('ens_job_create'),
//             'method' => 'POST',
//         ));

//         $form->add('submit', 'submit', array('label' => 'Create'));

//         return $form;
//     }

    /**
     * Displays a form to create a new Job entity.
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function newAction()
    {
        $entity = new Job();
        $entity->setType('full-time');
        $form   = $this->createForm(new JobType(), $entity);

        return $this->render('EnsJobeetBundle:Job:new.html.twig', array(
            'entity' => $entity,
            'form'   => $form->createView(),
        ));
    }

    /**
     * Finds and displays a Job entity.
     * @param int $id
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function showAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('EnsJobeetBundle:Job')->getActiveJob($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Job entity.');
        }
        
        $session = $this->getRequest()->getSession();
        
        // fetch jobs already stored in the job history
        $jobs = $session->get('job_history', array());
        
        // store the job as an array so we can put it in the session and avoid entity serialize errors
        $job = array('id' => $entity->getId(),
        		'position' =>$entity->getPosition(),
        		'company' => $entity->getCompany(),
        		'companyslug' => $entity->getCompanySlug(),
        		'locationslug' => $entity->getLocationSlug(), 
        		'positionslug' => $entity->getPositionSlug()
        	);
        
        if (!in_array($job, $jobs))
        {
        	// add the current job at the beginning of the array
        	array_unshift($jobs, $job);
        	
        	// store the new job history back into the session
        	$session->set('job_history', array_slice($jobs, 0, 3));
        }

        $deleteForm = $this->createDeleteForm($entity->getToken());

        return $this->render('EnsJobeetBundle:Job:show.html.twig', array(
	            'entity'      => $entity,
	            'delete_form' => $deleteForm->createView(),
	        ));
    }

    /**
     * Displays a form to edit an existing Job entity.
     * @param int $token
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function editAction($token)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('EnsJobeetBundle:Job')->findOneByToken($token);

        if (!$entity)
        {
            throw $this->createNotFoundException('Unable to find Job entity.');
        }
        
        $editForm = $this->createForm(new JobType(), $entity);
        $deleteForm = $this->createDeleteForm($token);

        return $this->render('EnsJobeetBundle:Job:edit.html.twig', array(
            'entity'      => $entity,
            'edit_form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ));
    }

//     /**
//     * Creates a form to edit a Job entity.
//     *
//     * @param Job $entity The entity
//     *
//     * @return \Symfony\Component\Form\Form The form
//     */
//     private function createEditForm(Job $entity)
//     {
//         $form = $this->createForm(new JobType(), $entity, array(
//             'action' => $this->generateUrl('ens_job_update', array('id' => $entity->getId())),
//             'method' => 'PUT',
//         ));

//         $form->add('submit', 'submit', array('label' => 'Update'));

//         return $form;
//     }
    
    /**
     * Edits an existing Job entity.
     * @param Request $request
     * @param int $token
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function updateAction(Request $request, $token)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('EnsJobeetBundle:Job')->findOneByToken($token);

        if (!$entity)
        {
            throw $this->createNotFoundException('Unable to find Job entity.');
        }

        $deleteForm = $this->createDeleteForm($token);
        $editForm = $this->createForm(new JobType(), $entity);
        
        $editForm->bind($request);

        if ($editForm->isValid())
        {
            $em->persist($entity);
        	$em->flush();

            return $this->redirect($this->generateUrl('ens_job_preview', array(
            		'company' => $entity->getCompanySlug(),
					'location' => $entity->getLocationSlug(),
					'token' => $entity->getToken(),
					'position' => $entity->getPositionSlug()
            		
            	)));
        }

        return $this->render('EnsJobeetBundle:Job:edit.html.twig', array(
            'entity'      => $entity,
            'edit_form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ));
    }
    
    /**
     * Deletes a Job entity.
     * @param Request $request
     * @param int $token
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function deleteAction(Request $request, $token)
    {
        $form = $this->createDeleteForm($token);
        $form->handleRequest($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $entity = $em->getRepository('EnsJobeetBundle:Job')->findOneByToken($token);

            if (!$entity) {
                throw $this->createNotFoundException('Unable to find Job entity.');
            }

            $em->remove($entity);
            $em->flush();
        }

        return $this->redirect($this->generateUrl('ens_job'));
    }

    /**
     * Creates a form to delete a Job entity by token.
     *
     * @param int $token The entity token
     * @return \Symfony\Component\Form\Form
     */
    private function createDeleteForm($token)
    {
//         return $this->createFormBuilder()
//             ->setAction($this->generateUrl('ens_job_delete', array('token' => $token)))
//             ->setMethod('DELETE')
//             ->add('submit', 'submit', array('label' => 'Delete'))
//             ->getForm()
//         ;

    	return $this->createFormBuilder(array('token' => $token))
    	->add('token', 'hidden')
    	->getForm()
    	;
    }
    
    /**
     * 
     * @param int $token
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function previewAction($token)
    {
    	$em = $this->getDoctrine()->getManager();
    	$entity = $em->getRepository('EnsJobeetBundle:Job')->findOneByToken($token);
    	
    	if (!$entity)
    	{
    		throw $this->createNotFoundException('Unable to find Job entity.');
    	}
    	
    	$deleteForm = $this->createDeleteForm($entity->getToken());
    	$publishForm = $this->createPublishForm($entity->getToken());
    	$extendForm = $this->createExtendForm($entity->getToken());
    	
    	return $this->render('EnsJobeetBundle:Job:show.html.twig', array(
    			'entity' => $entity,
    			'delete_form' => $deleteForm->createView(),
    			'publish_form' => $publishForm->createView(),
    			'extend_form' => $extendForm->createView(),
    		));
    }
    
    /**
     * 
     * @param int $token
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function publishAction($token)
    {
    	$form = $this->createPublishForm($token);
    	$request = $this->getRequest();
    	$form->bind($request);
    	
    	if ($form->isValid()) {
    		$em = $this->getDoctrine()->getManager();
    		$entity = $em->getRepository('EnsJobeetBundle:Job')->findOneByToken($token);
    		
    		if (!$entity)
    		{
    			throw $this->createNotFoundException('Unable to find Job entity.');
    		}
    		
    		$entity->publish();
    		$em->persist($entity);
    		$em->flush();
    		
    		$this->get('session')->getFlashBag()->add('notice', 'Your job is now online for 30 days.');
    	}
    	
    	return $this->redirect($this->generateUrl('ens_job_preview', array(
    			'company' => $entity->getCompanySlug(),
    			'location' => $entity->getLocationSlug(),
    			'token' => $entity->getToken(),
    			'position' => $entity->getPositionSlug()
    		)));
    }
    
    /**
     * 
     * @param int $token
     * @return \Symfony\Component\Form\Form
     */
    private function createPublishForm($token)
    {
    	return $this->createFormBuilder(array('token' => $token))
		    	->add('token', 'hidden')
		    	->getForm()
    		;
    }
    
    /**
     * 
     * @param int $token
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function extendAction($token)
	{
		$form = $this->createExtendForm($token);
		$request = $this->getRequest();
		$form->bindRequest($request);
		
		if ($form->isValid())
		{
			$em = $this->getDoctrine()->getEntityManager();
			$entity = $em->getRepository('EnsJobeetBundle:Job')->findOneByToken($token);
			
			if (!$entity)
			{
				throw $this->createNotFoundException('Unable to find Job entity.');
			}
			
			if (!$entity->extend())
			{
				throw $this->createNotFoundException('Unable to find extend the Job.');
			}
			
			$em->persist($entity);
			$em->flush();
			
			$this->get('session')->getFlashBag()->add('notice', sprintf('Your job validity has been extended until %s.', $entity->getExpiresAt()->format('m/d/Y')));
		}
		
		return $this->redirect($this->generateUrl('ens_job_preview', array(
				'company' => $entity->getCompanySlug(),
				'location' => $entity->getLocationSlug(),
				'token' => $entity->getToken(),
				'position' => $entity->getPositionSlug()
			)));
	}
	
	/**
	 * 
	 * @param int $token
	 * @return \Symfony\Component\Form\Form
	 */
	private function createExtendForm($token)
	{
		return $this->createFormBuilder(array('token' => $token))
				->add('token', 'hidden')
				->getForm()
			;
	}
	
	/**
	 * sénario test formulaire
	 */
	public function testExtendJob()
	{
		// A job validity cannot be extended before the job expires soon
		$client = $this->createJob(array('job[position]' => 'FOO4'), true);
		$crawler = $client->getCrawler();
		$this->assertTrue($crawler->filter('input[type=submit]:contains("Extend")')->count() == 0);
		// A job validity can be extended when the job expires soon
		// Create a new FOO5 job
		$client = $this->createJob(array('job[position]' => 'FOO5'), true);
		// Get the job and change the expire date to today
		$kernel = static::createKernel();
		$kernel->boot();
		$em = $kernel->getContainer()->get('doctrine.orm.entity_manager');
		$job = $em->getRepository('EnsJobeetBundle:Job')->findOneByPosition('FOO5');
		$job->setExpiresAt(new \DateTime());
		$em->flush();
		// Go to the preview page and extend the job
		$crawler = $client->request('GET', sprintf('/job/%s/%s/%s/%s', $job->getCompanySlug(), $job->getLocationSlug(), $job->getToken(), $job->getPositionSlug()));
		$crawler = $client->getCrawler();
		$form = $crawler->selectButton('Extend')->form();
		$client->submit($form);
		// Reload the job from db
		$job = $this->getJobByPosition('FOO5');
		// Check the expiration date
		$this->assertTrue($job->getExpiresAt()->format('y/m/d') == date('y/m/d', time() + 86400 * 30));
	}
}
