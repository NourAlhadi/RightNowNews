<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Message;
use AppBundle\Form\MessageType;
use AppBundle\Service\FilterNews;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

class DefaultController extends Controller
{

    /**
     * @Route("/post/{id}/",name="post")
     * @param integer $id
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function postAction($id, FilterNews $filter){
        $em = $this->getDoctrine()->getManager();
        $post = $em->getRepository('AppBundle:news')->findOneBy(['id'=>$id]);
        if (is_null($post)){
            return $this->render('default/error404.html.twig');
        }

        $news = $em->getRepository('AppBundle:news')->findBy([],['date'=>'DESC']);
        $filter->setNewsSet($news);
        $hot = $filter->filter_hot_news();

        return $this->render('default/post.html.twig',[
            "id" => $id,
            "hot"=>$hot,
        ]);
    }

    /**
     * @Route("/location/{location}",name="location")
     */
    public function locationAction($location, FilterNews $filter){
        $em = $this->getDoctrine()->getManager();
        $news = $em->getRepository('AppBundle:news')->findBy([],['date'=>'DESC']);
        $filter->setNewsSet($news);
        $news = $filter->filter_by_location($location);

        if (sizeof($news) == 0){
            return $this->render('default/error404.html.twig');
        }

        $allnews = $em->getRepository('AppBundle:news')->findBy([],['date'=>'DESC']);
        $filter->setNewsSet($allnews);
        $hot = $filter->filter_hot_news();

        return $this->render('default/list.html.twig',[
            "news"=>$news,
            "hot"=>$hot,
        ]);
    }

    /**
     * @Route("/news/{type}",name="type")
     */
    public function typeAction($type, FilterNews $filter){
        $em = $this->getDoctrine()->getManager();
        $news = $em->getRepository('AppBundle:news')->findBy([],['date'=>'DESC']);
        $stype = $em->getRepository('AppBundle:type')->findOneBy(['name'=>$type]);

        if (is_null($stype)){
            return $this->render('default/error404.html.twig');
        }

        $news = $em->getRepository('AppBundle:news')->findBy([],['date'=>'DESC']);
        $filter->setNewsSet($news);
        $hot = $filter->filter_hot_news();


        $filter->setNewsSet($news);
        $news = $filter->filter_by_type($stype);
        return $this->render('default/list.html.twig',[
            "news"=>$news,
            "hot"=>$hot,
        ]);
    }

    /**
     * @Route("/news/{type}/location/{location}", name="newslocation")
     */
    public function newslocationAction($type,$location,FilterNews $filter){
        $em = $this->getDoctrine()->getManager();
        $news = $em->getRepository('AppBundle:news')->findBy([],['date'=>'DESC']);
        $stype = $em->getRepository('AppBundle:type')->findOneBy(['name'=>$type]);

        if (is_null($stype)){
            return $this->render('default/error404.html.twig');
        }


        $filter->setNewsSet($news);

        $hot = $filter->filter_hot_news();


        if ($location == 'سوريا') {
            $news = $filter->filter_by_location($location);
        }else{
            $news = $filter->filter_by_reversed_location('سوريا');
        }

        if (sizeof($news) == 0){
            return $this->render('default/error404.html.twig');
        }

        $filter->setNewsSet($news);
        $news = $filter->filter_by_type($stype);

        if (sizeof($news) == 0){
            return $this->render('default/error404.html.twig');
        }

        return $this->render('default/list.html.twig',[
            "news"=>$news,
            "hot"=>$hot,
        ]);
    }

    /**
     * @Route("/contact/",name="contact")
     *
     */
    public function contactAction(Request $request,FilterNews $filter){

        $message = new Message();
        $form = $this->createForm(MessageType::class,$message);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $em = $this->getDoctrine()->getManager();
            $em->persist($message);
            $em->flush();

            return $this->redirect($this->generateUrl('index'));
        }

        $em = $this->getDoctrine()->getManager();
        $allnews = $em->getRepository('AppBundle:news')->findBy([],['date'=>'DESC']);
        $filter->setNewsSet($allnews);
        $hot = $filter->filter_hot_news();


        return $this->render('default/contact.html.twig',[
            "form"=>$form->createView(),
            "hot"=>$hot,
        ]);
    }

    /**
     * @Route("/world/",name="world")
     * @param FilterNews $filter
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function worldAction(FilterNews $filter){
        $em = $this->getDoctrine()->getManager();

        $types = $em->getRepository('AppBundle:type');
        $politics = $types->findOneBy(['name'=>'سياسة']);
        $sport = $types->findOneBy(['name'=>'رياضة']);
        $economy = $types->findOneBy(['name'=>'اقتصاد']);
        $misc = $types->findOneBy(['name'=>'منوع']);

        $news = $em->getRepository('AppBundle:news')->findBy([],['date'=>'DESC']);
        $filter->setNewsSet($news);

        $hot = $filter->filter_hot_news();


        $news = $filter->filter_by_reversed_location('سوريا');
        $filter->setNewsSet($news);

        $politics = $filter->filter_by_type($politics);
        $sport = $filter->filter_by_type($sport);
        $economy = $filter->filter_by_type($economy);
        $misc = $filter->filter_by_type($misc);


        return $this->render('default/world.html.twig',[
            "news" => $news,
            "politics"=>$politics,
            "sport"=>$sport,
            "economy"=>$economy,
            "misc"=>$misc,
            "hot"=>$hot,
        ]);
    }


    /**
     * @Route("/syria/",name="syria")
     * @param FilterNews $filter
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function syriaAction(FilterNews $filter){
        $em = $this->getDoctrine()->getManager();

        $types = $em->getRepository('AppBundle:type');
        $politics = $types->findOneBy(['name'=>'سياسة']);
        $sport = $types->findOneBy(['name'=>'رياضة']);
        $economy = $types->findOneBy(['name'=>'اقتصاد']);
        $misc = $types->findOneBy(['name'=>'منوع']);

        $news = $em->getRepository('AppBundle:news')->findBy([],['date'=>'DESC']);
        $filter->setNewsSet($news);

        $hot = $filter->filter_hot_news();

        $news = $filter->filter_by_location('سوريا');
        $filter->setNewsSet($news);

        $politics = $filter->filter_by_type($politics);
        $sport = $filter->filter_by_type($sport);
        $economy = $filter->filter_by_type($economy);
        $misc = $filter->filter_by_type($misc);


        return $this->render('default/syria.html.twig',[
            "news" => $news,
            "politics"=>$politics,
            "sport"=>$sport,
            "economy"=>$economy,
            "misc"=>$misc,
            "hot"=>$hot,
        ]);
    }


    /**
     * @Route("/searcher/",name="searcher")
     *
     * @Method("POST")
     * @param Request $request
     * @param FilterNews $filter
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function searcherAction(Request $request, FilterNews $filter){
        $data = $request->get('query');
        $em = $this->getDoctrine()->getManager();

        $news = $em->getRepository('AppBundle:news')->findBy([],['date'=>'DESC']);
        $filter->setNewsSet($news);
        $hot = $filter->filter_hot_news();



        return $this->render('default/list.html.twig',[
            "news" => $news,
            "hot" => $hot,
        ]);
    }

    /**
     * @Route("/", name="index")
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function indexAction(FilterNews $filter){
        $em = $this->getDoctrine()->getManager();

        $news = $em->getRepository("AppBundle:news")->findBy([],['date' => 'DESC']);
        $last_news = array_slice($news,0,11);

        $politics = $em->getRepository('AppBundle:type')->findOneBy(['name'=>'سياسة']);
        $sport = $em->getRepository('AppBundle:type')->findOneBy(['name'=>'رياضة']);
        $tech = $em->getRepository('AppBundle:type')->findOneBy(['name'=>'تكنولوجيا']);

        $filter->setNewsSet($news);

        $hot = $filter->filter_hot_news();

        $politics = $filter->filter_by_type($politics);

        $sport = $filter->filter_by_type($sport);

        $tech = $filter->filter_by_type($tech);

        return $this->render('default/index.html.twig', [
            "hot" => $hot,
            "hot_news" => $last_news,
            "politics" => $politics,
            "sport" => $sport,
            "tech" => $tech,
        ]);
    }



}
