<?php

namespace Ens\JobeetBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class DefaultController extends Controller
{
	/**
	 * affiche la page d'accueil
	 * @param $name
	 * @return \Symfony\Component\HttpFoundation\Response
	 */
    public function indexAction($name)
    {
        return $this->render('EnsJobeetBundle:Default:index.html.twig', array('name' => $name));
    }
}
