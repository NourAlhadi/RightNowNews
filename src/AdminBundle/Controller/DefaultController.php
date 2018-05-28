<?php

namespace AdminBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

class DefaultController extends Controller
{
    /**
     * @Route("/admin/")
     */
    public function indexAction(){
        $em = $this->getDoctrine()->getManager();

        $newsC = $em->getRepository('AppBundle:news')->findAll();
        $newsC = sizeof($newsC);

        $tagsC = $em->getRepository('AppBundle:tag')->findAll();
        $tagsC = sizeof($tagsC);

        $userC = 0;
        $adminC = 0;
        $users = $em->getRepository('AppBundle:User')->findAll();
        for ($i = 0; $i < sizeof($users); $i++){
            if ($users[$i]->hasRole('ROLE_ADMIN') || $users[$i]->hasRole('ROLE_SUPER_ADMIN')) $adminC++;
            else $userC++;
        }

        return $this->render('AdminBundle:Default:index.html.twig',[
            "news" =>$newsC,
            "tags" =>$tagsC,
            "user" =>$userC,
            "admin"=>$adminC,
        ]);
    }
}
