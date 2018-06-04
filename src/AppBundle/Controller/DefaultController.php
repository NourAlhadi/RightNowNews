<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Comment;
use AppBundle\Entity\Message;
use AppBundle\Entity\tag;
use AppBundle\Form\CommentType;
use AppBundle\Form\MessageType;
use AppBundle\Form\UserType;
use AppBundle\Service\FileUploader;
use AppBundle\Service\FilterNews;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;

class DefaultController extends Controller
{

    /**
     * @Route("/post/{id}/",name="post")
     * @param integer $id
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function postAction(Request $request, $id, FilterNews $filter){
        $user = $this->getUser();
        $em = $this->getDoctrine()->getManager();
        $post = $em->getRepository('AppBundle:news')->findOneBy(['id'=>$id]);
        if (is_null($post)){
            return $this->render('default/error404.html.twig');
        }
        if ($user != null){
            $id = $post->getType()[0]->getId();
            $user->incLog($id);
            $userManger = $this->get('fos_user.user_manager');
            $userManger->updateUser($user);
        }

        $news = $em->getRepository('AppBundle:news')->findBy([],['date'=>'DESC']);
        $filter->setNewsSet($news);
        $hot = $filter->filter_hot_news();
        $type = $post->getType()[0]->getName();
        $type = $em->getRepository('AppBundle:type')->findOneBy(['name'=>$type]);
        $related = $filter->filter_by_type($type);
        $filter->setNewsSet($related);
        $related = $filter->filter_by_tags($post->getTag());
        $filter-> setNewsSet($related);
        $related = $filter->exclude($post->getId());

        $comment  = new Comment();
        $form = $this->createForm(CommentType::class,$comment);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $comment->setDate(new \DateTime('now'));
            $comment->setWriter($user);
            $post->setComments($comment);
            $em->persist($comment);
            $em->persist($post);
            $em->flush();
            return $this->redirectToRoute('post',["id"=>$post->getId()]);
        }

        return $this->render('default/post.html.twig',[
            "post" => $post,
            "hot"=>$hot,
            "related" => $related,
            "form" => $form->createView()
        ]);
    }

    /**
     * @Route("/post/{post}/comment/{comment}/delete",name="delete_comment")
     */
    public function deleteAction($post,$comment){

        $em = $this->getDoctrine()->getManager();

        $id = $post;
        $post = $em->getRepository('AppBundle:news');
        $post = $post->findOneBy(['id'=>$id]);

        $cid = $comment;
        $comment = $em->getRepository('AppBundle:Comment');
        $comment = $comment->findOneBy(['id'=>$cid]);


        $post->removeComment($comment);
        $em->persist($post);
        $em->flush();

        return $this->redirectToRoute('post',["id"=>$id]);
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
     * @Route("/tag/{id}",name="news_tag")
     */
    public function tagAction($id,FilterNews $filter){
        $em = $this->getDoctrine()->getManager();

        $tag = $em->getRepository('AppBundle:tag')->findOneBy(['id'=>$id]);
        if ($tag == null) return $this->render('default/error404.html.twig');

        $news = $em->getRepository('AppBundle:news')->findBy([],['date'=>'DESC']);
        $filter->setNewsSet($news);

        $hot = $filter->filter_hot_news();

        $tags = [];
        array_push($tags,$tag);
        $news = $filter->filter_by_tags($tags,true);

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
     * @Method("GET")
     * @param Request $request
     * @param FilterNews $filter
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function searcherAction(Request $request, FilterNews $filter){
        $data = $request->get('query');
        $classifier = $request->get('srs');
        if ($classifier == null) $classifier = "title";

        $em = $this->getDoctrine()->getManager();

        $news = $em->getRepository('AppBundle:news')->findBy([],['date'=>'DESC']);
        $filter->setNewsSet($news);
        $hot = $filter->filter_hot_news();

        $news = $filter->filter_by_title($data);

        return $this->render('default/search.html.twig',[
            "news" => $news,
            "hot" => $hot,
            "data" => $data,
        ]);
    }

    /**
     * @Route("/", name="index")
     * @Route("/", name="fos_user_registration_confirmed")
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


    /**
     * @Route("/profile/info/edit/",name="edit_profile")
     * @Route("/profile/info/edit/",name="fos_user_profile_edit")
     */
    public function editAction(Request $request,FileUploader $uploader){

        $user = $this->getUser();


        if ($user == null){
            return $this->redirect($this->generateUrl('fos_user_security_login'));
        }

        $form = $this->createForm(UserType::class,$user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {


            if ($user->getAvatar() != null){
                $user->setAvatar($uploader->upload($user->getAvatar()));
            }


            $this->get('fos_user.user_manager')->updateUser($user);

            return $this->redirect($this->generateUrl('fos_user_profile_show'));
        }


        return $this->render('default/edit.html.twig',[
            "form" => $form->createView(),
        ]);
    }
    /**
     * @Route("/profile/info/change-password/",name="change_password")
     * @Route("/profile/info/change-password/",name="fos_user_change_password")
     */
    public function passwordAction(Request $request){

        $user = $this->getUser();

        if ($user == null){
            return $this->redirect($this->generateUrl('fos_user_security_login'));
        }

        $defaultData = array('id' => 1);
        $form = $this->createFormBuilder($defaultData)
            ->add('pass', PasswordType::class,[
                "label" => 'كلمة المرور الحالية: '
            ])
            ->add('npass', PasswordType::class,[
                "label" => 'كلمة المرور الجديدة: '
            ])
            ->add('npassrep', PasswordType::class,[
                "label" => 'تأكيد كلمة المرور: '
            ])
            ->getForm();

        $form->handleRequest($request);

        $mismatch = false;
        $incorrect = false;

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $pass = $data["pass"];
            $npass1 = $data["npass"];
            $npass2 = $data["npassrep"];
            if ($npass1 !== $npass2){
                $mismatch = true;
            }


            $factory = $this->get('security.encoder_factory');

            $user = $this->getUser();

            $encoder = $factory->getEncoder($user);
            $incorrect = ($encoder->isPasswordValid($user->getPassword(),$pass,$user->getSalt())) ? $incorrect : true;


            if (!($incorrect || $mismatch)){
                $newpass = $encoder->encodePassword($npass1, $user->getSalt());
                $user->setPassword($newpass);
                $manager = $this->get('fos_user.user_manager');
                $manager->updateUser($user);
                return $this->redirect($this->generateUrl('fos_user_profile_show'));
            }


        }

        if ($mismatch){
            $form->get('npass')->addError(new FormError('كلمتا السر غير متطابقتان'));
        }

        if ($incorrect){
            $form->get('npass')->addError(new FormError('كلمة السر التي أدخلتها ليست كلمة السر الخاصة بهذا الحساب'));
        }

        return $this->render('default/change_password.html.twig',[
            "form" => $form->createView(),
        ]);
    }
}
