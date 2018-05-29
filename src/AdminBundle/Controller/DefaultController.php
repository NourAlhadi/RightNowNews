<?php

namespace AdminBundle\Controller;

use AppBundle\Entity\news;
use AppBundle\Form\newsType;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;

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


    /**
     * @Route("/admin/news/",name="admin_news")
     */
    public function newsAction(){
        $em = $this->getDoctrine()->getManager();
        $news = $em->getRepository('AppBundle:news')->findBy([],['date'=>'DESC']);
        $news = array_slice($news,0, 1);

        return $this->render('@Admin/Default/news.html.twig',[
           "news"=>$news,
        ]);
    }

    /**
     * @Route("/admin/news/more/{num}",name="admin_news_more")
     */
    public function newsMoreAction($num){
        $em = $this->getDoctrine()->getManager();

        $news = $em->getRepository('AppBundle:news')->findBy([],['date'=>'DESC']);
        $news = array_slice($news,0,$num);

        $encoder = new JsonEncoder();
        $normalizer = new ObjectNormalizer();

        $normalizer->setCircularReferenceHandler(function ($object) {
            return $object->getName();
        });

        $serializer = new Serializer(array($normalizer), array($encoder));

        $data = $serializer->serialize($news, 'json');

        $response = new Response();
        $response->setContent(json_encode(array(
            'data' => $data,
        )));
        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }

    /**
     * @Route("/admin/news/add/", name="admin_news_add")
     */
    public function addNewsAction(Request $request){

        $uploader = $this->get('AppBundle\Service\FileUploader');

        $news = new news();
        $form = $this->createForm(newsType::class,$news);
        $form->handleRequest($request);

        $em = $this->getDoctrine()->getManager();
        if ($form->isSubmitted() && $form->isValid()){

            /*$news->setMainImage($uploader->upload($news->getMainImage()));

            if ($news->getSecondImage() != null){
                $news->setSecondImage($uploader->upload($news->getSecondImage()));
            }
            $news->setDate(new \DateTime('now'));
            $news->setTag(null);
            $em = $this->getDoctrine()->getManager();


            /*$tags = explode(" ",$news->getTag()[0]);
            $news->clearTag();


            for ($i = 0; $i < sizeof($tags); $i++){
                if ($tags[$i] == "" || $tags[$i] == " ") continue;
                $tag = new Tag();
                $tag->setName($tags[$i]);
                $news->addTag($tag);
                $em->persist($tag);
            }

            dump($news);*/

            $em->persist($news);
            $em->flush();

            return $this->redirectToRoute($this->generateUrl('admin_news'));
        }


        return $this->render('@Admin/Default/news_form.html.twig',[
            "form"=>$form->createView()
        ]);
    }
}
