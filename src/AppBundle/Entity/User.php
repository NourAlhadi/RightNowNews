<?php

namespace AppBundle\Entity;

use FOS\UserBundle\Model\User as BaseUser;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="fos_user")
 */
class User extends BaseUser
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    public function __construct()
    {
        parent::__construct();
        $this->log = array_fill(0,10,0);
    }

    /**
     * @var \AppBundle\Entity\type
     *
     * @ORM\ManyToMany(targetEntity="AppBundle\Entity\type")
     */
    private $types;


    /**
     * @ORM\Column(name="avatar", type="string", length=255, nullable=true)
     *
     * @var string
     */
    private $avatar;

    /**
     * @ORM\Column(name="fname", type="string", length=255, nullable=true)
     * @var string
     */
    private $fname;

    /**
     * @ORM\Column(name="lname", type="string", length=255, nullable=true)
     * @var string
     */
    private $lname;


    /**
     * @ORM\Column(name="chart", type="array")
     * @var array
     */
    private $log;

    /**
     * @return mixed
     */
    public function getLog()
    {
        return $this->log;
    }

    /**
     * @param mixed $log
     */
    public function setLog($log)
    {
        $this->log = $log;
    }


    public function incLog($id){
        $this->log[$id]++;
    }

    public function setOneLog($id,$val){
        $this->log[$id] = $val;
    }

    /**
     * @return string
     */
    public function getAvatar()
    {
        return $this->avatar;
    }

    /**
     * @param string $avatar
     */
    public function setAvatar($avatar)
    {
        $this->avatar = $avatar;
    }


    /**
     * @return string
     */
    public function getFname()
    {
        return $this->fname;
    }

    /**
     * @param string $fname
     */
    public function setFname($fname)
    {
        $this->fname = $fname;
    }

    /**
     * @return string
     */
    public function getLname()
    {
        return $this->lname;
    }

    /**
     * @param string $lname
     */
    public function setLname($lname)
    {
        $this->lname = $lname;
    }

    /**
     * @return \AppBundle\Entity\type
     */
    public function getTypes()
    {
        return $this->types;
    }

    /**
     * @param \AppBundle\Entity\type $types
     */
    public function setTypes($types)
    {
        $this->types = $types;
    }


    public function __toString()
    {
        $name = $this->fname . " " . $this->lname;
        if ($name == " ") $name = $this->username;
        return $name;
    }

}