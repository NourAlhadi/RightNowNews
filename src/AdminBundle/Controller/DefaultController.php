<?php

namespace AdminBundle\Controller;

use AdminBundle\Service\Helper;
use AppBundle\Entity\news;
use AppBundle\Entity\tag;
use AppBundle\Form\newsType;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use Symfony\Component\Serializer\Serializer;
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
        $news = array_slice($news,0, 25);

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

            $news->setMainImage($uploader->upload($news->getMainImage()));
            if ($news->getSecondImage() != null){
                $news->setSecondImage($uploader->upload($news->getSecondImage()));
            }


            // title - location - date - post - main_image - second_image - is_hot
            // news_tag / news_type

            $em = $this->getDoctrine();
            $connection = $em->getConnection();
            $statement = $connection->prepare("insert into news values(null,:title,:location,CURRENT_TIMESTAMP,:post,:main_image,:second_image,:is_hot);");
            if ($news->getisHot() !== true) $news->setIsHot(0);
            $statement->bindValue('title', $news->getTitle());
            $statement->bindValue('location', $news->getLocation());
            $statement->bindValue('post', $news->getPost());
            $statement->bindValue('main_image', $news->getMainImage());
            $statement->bindValue('second_image', $news->getSecondImage());
            $statement->bindValue('is_hot', $news->getisHot());
            $statement->execute();

            $id = $em->getRepository('AppBundle:news')->findOneBy([],["id"=>"DESC"])->getId();
            $statement = $connection->prepare("insert into news_type values(:news_id,:type_id);");
            $statement->bindValue('news_id',$id);
            $statement->bindValue('type_id',$news->getType()->getId());
            $statement->execute();


            $tags = explode(" ",$news->getTag()[0]);

            $tid = [];

            $checker = new Helper();
            $em = $this->getDoctrine()->getManager();
            $checker->setEntityManager($em);
            for ($i = 0; $i < sizeof($tags); $i++){
                if ($tags[$i] == "" || $tags[$i] == " ") continue;
                array_push($tid,$tags[$i]);
                if ($checker->check($tags[$i]) == true) continue;
                $tag = new Tag();
                $tag->setName($tags[$i]);
                $em->persist($tag);
                $em->flush();
            }

            for ($i = 0; $i < sizeof($tid); $i++){
                $name = $tid[$i];
                $tgid = $checker->getTagId($name);
                $statement = $connection->prepare("insert into news_tag values(:news_id,:tag_id);");
                $statement->bindValue('news_id',$id);
                $statement->bindValue('tag_id',$tgid);
                $statement->execute();
            }


            return $this->redirectToRoute('admin_news');
        }


        return $this->render('@Admin/Default/news_form.html.twig',[
            "form"=>$form->createView()
        ]);
    }


    /**
     * @Route("/admin/news/update/{id}", name="admin_news_update")
     */
    public function updateNewsAction(Request $request,$id){

        $uploader = $this->get('AppBundle\Service\FileUploader');
        $em = $this->getDoctrine()->getManager();

        $news = $em->getRepository('AppBundle:news')->findOneBy(['id'=>$id]);
        if ($news == null) return $this->redirectToRoute('admin_news');

        $post = new news();
        $form = $this->createForm(newsType::class,$post);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()){


            $post->setMainImage($uploader->upload($post->getMainImage()));
            if ($post->getSecondImage() != null){
                $post->setSecondImage($uploader->upload($post->getSecondImage()));
            }

            $news = $post;

            // title - location - date - post - main_image - second_image - is_hot
            // news_tag / news_type

            $em = $this->getDoctrine();
            $connection = $em->getConnection();
            $statement = $connection->prepare("update news set  title = :title, location = :location, post = :post,main_image = :main_image,second_image = :second_image,is_hot = :is_hot where id = :id;");
            if ($news->getisHot() !== true) $news->setIsHot(0);
            $statement->bindValue('title', $news->getTitle());
            $statement->bindValue('location', $news->getLocation());
            $statement->bindValue('post', $news->getPost());
            $statement->bindValue('main_image', $news->getMainImage());
            $statement->bindValue('second_image', $news->getSecondImage());
            $statement->bindValue('is_hot', $news->getisHot());
            $statement->bindValue('id', $id);
            $statement->execute();


            $statement = $connection->prepare("update news_type set type_id = :type_id where news_id = :news_id;");
            $statement->bindValue('news_id',$id);
            $statement->bindValue('type_id',$news->getType()->getId());
            $statement->execute();


            $tags = explode(" ",$news->getTag()[0]);

            $tid = [];

            $checker = new Helper();
            $em = $this->getDoctrine()->getManager();
            $checker->setEntityManager($em);
            for ($i = 0; $i < sizeof($tags); $i++){
                if ($tags[$i] == "" || $tags[$i] == " ") continue;
                array_push($tid,$tags[$i]);
                if ($checker->check($tags[$i]) == true) continue;
                $tag = new Tag();
                $tag->setName($tags[$i]);
                $em->persist($tag);
                $em->flush();
            }

            $statement = $connection->prepare("delete from news_tag where news_id = :news_id;");
            $statement->bindValue('news_id',$id);
            $statement->execute();

            for ($i = 0; $i < sizeof($tid); $i++){
                $name = $tid[$i];
                $tgid = $checker->getTagId($name);
                $statement = $connection->prepare("insert into news_tag values(:news_id,:tag_id);");
                $statement->bindValue('news_id',$id);
                $statement->bindValue('tag_id',$tgid);
                $statement->execute();
            }


            return $this->redirectToRoute('admin_news');
        }


        return $this->render('@Admin/Default/news_form_update.html.twig',[
            "form"=>$form->createView(),
            "last"=>$news,
        ]);
    }

    /**
     * @Route("/admin/news/delete/{id}", name="admin_news_delete")
     */
    public function deleteNewsAction($id){
        $em = $this->getDoctrine()->getManager();
        $news = $em->getRepository('AppBundle:news')->findOneBy(['id'=>$id]);
        if ($news == null) return $this->redirectToRoute("admin_news");

        $connection = $this->getDoctrine()->getConnection();
        $sql = $connection->prepare("delete from news where id = :id;");
        $sql->bindValue("id",$id);
        $sql->execute();

        $connection = $this->getDoctrine()->getConnection();
        $sql = $connection->prepare("delete from news_type where news_id = :id;");
        $sql->bindValue("id",$id);
        $sql->execute();

        $connection = $this->getDoctrine()->getConnection();
        $sql = $connection->prepare("delete from news_tag where news_id = :id;");
        $sql->bindValue("id",$id);
        $sql->execute();


        return $this->redirectToRoute("admin_news");
    }
}
