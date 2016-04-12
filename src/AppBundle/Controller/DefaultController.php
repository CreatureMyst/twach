<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

class DefaultController extends Controller
{
    /**
     * @Route("/", name="homepage")
     */
    public function indexAction(Request $request)
    {
        return $this->render('AppBundle:default:index.html.twig');
    }

    /**
     * @Route("/app", name="app")
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function appAction()
    {
        return $this->render('AppBundle:default:app.html.twig');
    }
}
