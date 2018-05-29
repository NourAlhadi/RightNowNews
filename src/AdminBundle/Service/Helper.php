<?php
/**
 * Created by PhpStorm.
 * User: nouralhadi
 * Date: 5/30/18
 * Time: 12:29 AM
 */

namespace AdminBundle\Service;


use Doctrine\Common\Persistence\ObjectManager;

class Helper
{
    /**
     * @var ObjectManager
     */
    private $em;

    /**
     * @param $em ObjectManager
     */
    public function setEntityManager($em){
        $this->em = $em;
    }

    /**
     * @param $name string
     * @param $em ObjectManager
     * @return bool
     */
    public function check($name){
        $isHere = $this->em->getRepository('AppBundle:tag')->findOneBy(['name'=>$name]);
        if ($isHere == null) return false;
        return true;
    }

    /**
     * @param $name string
     * @return integer
     */
    public function getTagId($name){
        $em = $this->em->getRepository('AppBundle:tag');
        $tag = $em->findOneBy(['name'=>$name]);
        if ($tag == null) return null;
        return $tag->getId();
    }


}