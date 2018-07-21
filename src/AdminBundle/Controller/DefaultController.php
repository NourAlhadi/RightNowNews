<?php

namespace AdminBundle\Controller;

use AdminBundle\Service\Helper;
use AppBundle\Entity\news;
use AppBundle\Entity\tag;
use AppBundle\Entity\type;
use AppBundle\Form\newsType;
use AppBundle\Service\FilterNews;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\JsonResponse;
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

        $msg = $em->getRepository('AppBundle:Message')->findAll();
        $msgC = sizeof($msg);

        return $this->render('AdminBundle:Default:index.html.twig',[
            "news" =>$newsC,
            "tags" =>$tagsC,
            "user" =>$userC,
            "admin"=>$adminC,
            "msg"=>$msgC,
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



    /**
     * @Route("/admin/tags/",name="admin_tags")
     */
    public function tagsAction(){
        $em = $this->getDoctrine()->getManager();
        $tags = $em->getRepository('AppBundle:tag')->findBy([],['name'=>'ASC']);
        $tags = array_slice($tags,0, 25);

        return $this->render('@Admin/Default/tags.html.twig',[
            "tags"=>$tags,
        ]);
    }

    /**
     * @Route("/admin/tags/more/{num}",name="admin_tags_more")
     */
    public function tagsMoreAction($num){
        $em = $this->getDoctrine()->getManager();

        $tags = $em->getRepository('AppBundle:tag')->findBy([],['name'=>'ASC']);
        $tags = array_slice($tags,0,$num);

        $encoder = new JsonEncoder();
        $normalizer = new ObjectNormalizer();

        $normalizer->setCircularReferenceHandler(function ($object) {
            return $object->getName();
        });

        $serializer = new Serializer(array($normalizer), array($encoder));

        $data = $serializer->serialize($tags, 'json');

        $response = new Response();
        $response->setContent(json_encode(array(
            'data' => $data,
        )));
        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }


    /**
     * @Route("/admin/tags/delete/{id}", name="admin_tags_delete")
     */
    public function deleteTagsAction($id){
        $em = $this->getDoctrine()->getManager();
        $news = $em->getRepository('AppBundle:tag')->findOneBy(['id'=>$id]);
        if ($news == null) return $this->redirectToRoute("admin_tags");

        $connection = $this->getDoctrine()->getConnection();
        $sql = $connection->prepare("delete from tag where id = :id;");
        $sql->bindValue("id",$id);
        $sql->execute();


        $connection = $this->getDoctrine()->getConnection();
        $sql = $connection->prepare("delete from news_tag where tag_id = :id;");
        $sql->bindValue("id",$id);
        $sql->execute();


        return $this->redirectToRoute("admin_tags");
    }


    /**
     * @Route("/admin/tags/update/{id}", name="admin_tags_update")
     */
    public function updateTagsAction(Request $request,$id){

        $em = $this->getDoctrine()->getManager();

        $tag = $em->getRepository('AppBundle:tag')->findOneBy(['id'=>$id]);
        if ($tag == null) return $this->redirectToRoute('admin_news');

        $last = $tag;

        $form = $this->createFormBuilder($tag)
            ->add('name', TextType::class,[
                'label' => 'اسم الوسم: '
            ])
            ->getForm();
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $tag = $form->getData();

            $em->persist($tag);
            $em->flush();
            return $this->redirectToRoute('admin_tags');
        }


        return $this->render('@Admin/Default/tags_form_update.html.twig',[
            "form"=>$form->createView(),
            "last"=>$last,
        ]);
    }


    /**
     * @Route("/admin/users/",name="admin_users")
     */
    public function usersAction(){
        $em = $this->getDoctrine()->getManager();
        $users = $em->getRepository('AppBundle:User')->findBy([],['username'=>'ASC']);
        $vUsers = [];
        for ($i = 0; $i < sizeof($users); $i++){
            if (!$users[$i]->hasRole('ROLE_SUPER_ADMIN') && !$users[$i]->hasRole('ROLE_ADMIN')){
                array_push($vUsers,$users[$i]);
            }
        }
        $users = $vUsers;
        $users = array_slice($users,0, 25);

        return $this->render('@Admin/Default/users.html.twig',[
            "users"=>$users,
        ]);
    }

    /**
     * @Route("/admin/users/more/{num}",name="admin_users_more")
     */
    public function usersMoreAction($num){
        $em = $this->getDoctrine()->getManager();

        $users = $em->getRepository('AppBundle:User')->findBy([],['username'=>'ASC']);
        $vUsers = [];
        for ($i = 0; $i < sizeof($users); $i++){
            if (!$users[$i]->hasRole('ROLE_SUPER_ADMIN') && !$users[$i]->hasRole('ROLE_ADMIN')){
                array_push($vUsers,$users[$i]);
            }
        }
        $users = $vUsers;
        $users = array_slice($users,0,$num);

        $encoder = new JsonEncoder();
        $normalizer = new ObjectNormalizer();

        $normalizer->setCircularReferenceHandler(function ($object) {
            return $object->getName();
        });

        $serializer = new Serializer(array($normalizer), array($encoder));

        $data = $serializer->serialize($users, 'json');

        $response = new Response();
        $response->setContent(json_encode(array(
            'data' => $data,
        )));
        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }

    /**
     * @Route("/admin/user/update/{id}", name="admin_user_update")
     */
    public function updateUserAction($id){

        $em = $this->getDoctrine()->getManager();
        $user = $em->getRepository('AppBundle:User')->findOneBy(['id'=>$id]);
        if ($user != null){
            $user->addRole('ROLE_ADMIN');
            $userManager = $this->get('fos_user.user_manager');
            $userManager->updateUser($user);
        }
        return $this->redirectToRoute("admin_users");
    }

    /**
     * @Route("/admin/user/delete/{id}", name="admin_user_delete")
     */
    public function deleteUserAction($id){

        $em = $this->getDoctrine()->getManager();
        $user = $em->getRepository('AppBundle:User')->findOneBy(['id'=>$id]);
        if ($user != null){
            $userManager = $this->get('fos_user.user_manager');
            $userManager->deleteUser($user);
        }
        return $this->redirectToRoute("admin_users");
    }




    /**
     * @Route("/admin/admins/",name="admin_admins")
     */
    public function adminsAction(){
        $em = $this->getDoctrine()->getManager();
        $users = $em->getRepository('AppBundle:User')->findBy([],['username'=>'ASC']);
        $vUsers = [];
        for ($i = 0; $i < sizeof($users); $i++){
            if ($users[$i]->hasRole('ROLE_SUPER_ADMIN') || $users[$i]->hasRole('ROLE_ADMIN')){
                array_push($vUsers,$users[$i]);
            }
        }
        $users = $vUsers;
        $users = array_slice($users,0, 25);

        return $this->render('@Admin/Default/admins.html.twig',[
            "users"=>$users,
        ]);
    }

    /**
     * @Route("/admin/admins/more/{num}",name="admin_admins_more")
     */
    public function adminsMoreAction($num){
        $em = $this->getDoctrine()->getManager();

        $users = $em->getRepository('AppBundle:User')->findBy([],['username'=>'ASC']);
        $vUsers = [];
        for ($i = 0; $i < sizeof($users); $i++){
            if ($users[$i]->hasRole('ROLE_SUPER_ADMIN') || $users[$i]->hasRole('ROLE_ADMIN')){
                array_push($vUsers,$users[$i]);
            }
        }
        $users = $vUsers;
        $users = array_slice($users,0,$num);

        $encoder = new JsonEncoder();
        $normalizer = new ObjectNormalizer();

        $normalizer->setCircularReferenceHandler(function ($object) {
            return $object->getName();
        });

        $serializer = new Serializer(array($normalizer), array($encoder));

        $data = $serializer->serialize($users, 'json');

        $response = new Response();
        $response->setContent(json_encode(array(
            'data' => $data,
        )));
        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }

    /**
     * @Route("/admin/admins/update/{id}", name="admin_admins_update")
     */
    public function updateAdminAction($id){

        $em = $this->getDoctrine()->getManager();
        $user = $em->getRepository('AppBundle:User')->findOneBy(['id'=>$id]);
        if ($user != null){
            $user->addRole('ROLE_SUPER_ADMIN');
            $userManager = $this->get('fos_user.user_manager');
            $userManager->updateUser($user);
        }
        return $this->redirectToRoute("admin_admins");
    }

    /**
     * @Route("/admin/admins/delete/{id}", name="admin_admins_delete")
     */
    public function deleteAdminAction($id){

        $em = $this->getDoctrine()->getManager();
        $user = $em->getRepository('AppBundle:User')->findOneBy(['id'=>$id]);
        if ($user != null){
            $userManager = $this->get('fos_user.user_manager');
            $userManager->deleteUser($user);
        }
        return $this->redirectToRoute("admin_admins");
    }





    /**
     * @Route("/admin/messages/",name="admin_messages")
     */
    public function messagesAction(){
        $em = $this->getDoctrine()->getManager();
        $msg = $em->getRepository('AppBundle:Message')->findBy([],['id'=>'DESC']);
        $msg = array_slice($msg,0, 25);

        return $this->render('@Admin/Default/messages.html.twig',[
            "messages"=>$msg,
        ]);
    }

    /**
     * @Route("/admin/messages/more/{num}",name="admin_messages_more")
     */
    public function messagesMoreAction($num){
        $em = $this->getDoctrine()->getManager();

        $msg = $em->getRepository('AppBundle:Message')->findBy([],['id'=>'DESC']);
        $msg = array_slice($msg,0,$num);

        $encoder = new JsonEncoder();
        $normalizer = new ObjectNormalizer();

        $normalizer->setCircularReferenceHandler(function ($object) {
            return $object->getName();
        });

        $serializer = new Serializer(array($normalizer), array($encoder));

        $data = $serializer->serialize($msg, 'json');

        $response = new Response();
        $response->setContent(json_encode(array(
            'data' => $data,
        )));
        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }




    /**
     * @Route("/admin/messages/delete/{id}", name="admin_messages_delete")
     */
    public function deleteMessageAction($id){

        $em = $this->getDoctrine()->getManager();
        $msg = $em->getRepository('AppBundle:Message')->findOneBy(['id'=>$id]);
        if ($msg != null){
            $em->remove($msg);
            $em->flush();
        }
        return $this->redirectToRoute("admin_messages");
    }



    /**
     * @Route("/profile/developer/",name="get_developer")
     */
    public function getDeveloperAction(Request $request){

        $sub = $request->request->get('key');
        $st = $request->request->get('start');
        if ($sub != null){
            $new_id = uniqid();
            $this->getUser()->setApi($new_id);
            $this->get('fos_user.user_manager')->updateUser($this->getUser());
        }

        if ($st != null){
            return $this->redirectToRoute('api_start');
        }
        return $this->render('default/get_developer.html.twig');
    }

    /**
     * @Route("/api/start/",name="api_start")
     */
    public function apiStartAction(Request $request){
        return $this->render('default/api.html.twig');
    }


    /**
     * @Route("/api/query",name="api")
     * @Method("GET")
     */
    public function apiAction(Request $request){
        $em = $this->getDoctrine()->getManager();

        $filter = new FilterNews();

        $api = $request->get("api");
        if ($api == null){
            $data = [
                "result" => "ERROR",
                "message" => "لم يتم توفير رمز مطور صالح!!"
            ];
            return new JsonResponse($data);
        }

        $users = $em->getRepository('AppBundle:User')->findAll();
        $valid = false;
        for ($i =0; $i < sizeof($users); $i++){
            if ($users[$i]->getApi() == $api){
                $valid = true;
            }
        }
        if (!$valid){
            $data = [
                "result" => "ERROR",
                "message" => "لم يتم توفير رمز مطور صالح!!"
            ];
            return new JsonResponse($data);
        }

        $qnews = $request->get('news');
        $qtype = $request->get('type');

        if ($qnews == null && $qtype == null){
            $data = [
                "result" => "ERROR",
                "message" => "لم يتم طلب أي شيء من المنصة!!"
            ];
            return new JsonResponse($data);
        }

        $type = $em->getRepository('AppBundle:type')->findOneBy(['name'=>$qtype]);
        if ($qnews == null && $type == null){
            $data = [
                "result" => "ERROR",
                "message" => "لم يتم توفير نوع صحيح من الأخبار للمنصة!!"
            ];
            return new JsonResponse($data);
        }

        $news = $em->getRepository('AppBundle:news')->findBy([],['date'=>'DESC']);
        $filter->setNewsSet($news);
        if ($qnews != null && $qnews != "all"){
            $news = $filter->filter_by_location($qnews);
        }
        $filter->setNewsSet($news);
        if ($type != null){
            $news = $filter->filter_by_type($type);
        }


        $encoder = new JsonEncoder();
        $normalizer = new ObjectNormalizer();
        $normalizer->setCircularReferenceHandler(function ($object) {
            return $object->getName();
        });
        $serializer = new Serializer(array($normalizer), array($encoder));
        $news_set = $serializer->serialize($news, 'json');

        $data = [
            "result" => "ACCEPTED",
            "cnt" => sizeof($news),
            "news_set" => json_decode($news_set,true)

        ];
        return new JsonResponse($data);

    }
}
