<?php
namespace Ens\JobeetBundle\Controller;

use Sonata\AdminBundle\Controller\CRUDController as Controller;
use Sonata\DoctrineORMAdminBundle\Datagrid\ProxyQuery as ProxyQueryInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\File\Exception\AccessDeniedException;

class JobAdminController extends Controller
{
	/**
	 * 
	 * @param Request $request
	 * @param ProxyQueryInterface $selectedModelQuery
	 * @throws AccessDeniedException
	 * @return \Symfony\Component\HttpFoundation\RedirectResponse
	 */
	public function batchActionExtend(Request $request, ProxyQueryInterface $selectedModelQuery)
	{
		if ($this->admin->isGranted('EDIT') === false || $this->admin->isGranted('DELETE') === false)
		{
			throw new AccessDeniedException();
		}
		
		$modelManager = $this->admin->getModelManager();
		$selectedModels = $selectedModelQuery->execute();
		
		try
		{
			foreach ($selectedModels as $selectedModel)
			{
				$selectedModel->extend();
				$modelManager->update($selectedModel);
			}
		}
		
		catch (\Exception $e)
		{
			$this->get('session')->setFlash('sonata_flash_error', $e->getMessage());
			
			return new RedirectResponse($this->admin->generateUrl('list',$this->admin->getFilterParameters()));
		}
		
		$this->get('session')->setFlash('sonata_flash_success', sprintf('The selected jobs validity has been extended until %s.', date('m/d/Y', time() + 86400 * 30)));
		
		return new RedirectResponse($this->admin->generateUrl('list',$this->admin->getFilterParameters()));
	}
	
	/**
	 * 
	 * @return boolean
	 */
	public function batchActionDeleteNeverActivatedIsRelevant()
	{
		return true;
	}
	
	/**
	 * 
	 * @throws AccessDeniedException
	 * @return \Symfony\Component\HttpFoundation\RedirectResponse
	 */
	public function batchActionDeleteNeverActivated()
	{
		if ($this->admin->isGranted('EDIT') === false || $this->admin->isGranted('DELETE') === false)
		{
			throw new AccessDeniedException();
		}
		
		$em = $this->getDoctrine()->getEntityManager();
		$nb = $em->getRepository('EnsJobeetBundle:Job')->cleanup(60);
		
		if ($nb)
		{
			$this->get('session')->setFlash('sonata_flash_success', sprintf('%d never activated jobs have been deleted successfully.', $nb));
		}
		
		else
		{
			$this->get('session')->setFlash('sonata_flash_info', 'No job to delete.');
		}
		
		return new RedirectResponse($this->admin->generateUrl('list',$this->admin->getFilterParameters()));
	}
}