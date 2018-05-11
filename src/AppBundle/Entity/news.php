<?php

namespace AppBundle\Entity;

use AppBundle\AppBundle;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;

/**
 * news
 *
 * @ORM\Table(name="news")
 * @ORM\Entity(repositoryClass="AppBundle\Repository\newsRepository")
 */
class news
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="title", type="string", length=255)
     */
    private $title;

    /**
     * @var string
     *
     * @ORM\Column(name="location", type="string", length=255)
     */
    private $location;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="date", type="datetime")
     */
    private $date;

    /**
     * @var string
     *
     * @ORM\Column(name="post", type="text")
     */
    private $post;

    /**
     * @var string
     *
     * @ORM\Column(name="main_image", type="string", length=255)
     */
    private $mainImage;

    /**
     * @var string
     *
     * @ORM\Column(name="second_image", type="string", length=255, nullable=true)
     */
    private $secondImage;

    /**
     * @var \AppBundle\Entity\tag
     *
     * @ORM\ManyToMany(targetEntity="AppBundle\Entity\tag")
     */
    private $tag;


    /**
     * @var \AppBundle\Entity\type
     *
     * @ORM\ManyToMany(targetEntity="AppBundle\Entity\type")
     */
    private $type;

    /**
     * @var boolean
     *
     * @ORM\Column(type="boolean", options={"default":"0"})
     */
    private $isHot;

    /**
     * @return boolean
     */
    public function getisHot()
    {
        return $this->isHot;
    }

    /**
     * @param boolean $isHot
     */
    public function setIsHot($isHot)
    {
        $this->isHot = $isHot;
    }

    /**
     * Get id
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set title
     *
     * @param string $title
     *
     * @return news
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Get title
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Set location
     *
     * @param string $location
     *
     * @return news
     */
    public function setLocation($location)
    {
        $this->location = $location;

        return $this;
    }

    /**
     * Get location
     *
     * @return string
     */
    public function getLocation()
    {
        return $this->location;
    }

    /**
     * Set date
     *
     * @param \DateTime $date
     *
     * @return news
     */
    public function setDate($date)
    {
        $this->date = $date;

        return $this;
    }

    /**
     * Get date
     *
     * @return \DateTime
     */
    public function getDate()
    {
        return $this->date;
    }

    /**
     * Set post
     *
     * @param string $post
     *
     * @return news
     */
    public function setPost($post)
    {
        $this->post = $post;

        return $this;
    }

    /**
     * Get post
     *
     * @return string
     */
    public function getPost()
    {
        return $this->post;
    }

    /**
     * Set mainImage
     *
     * @param string $mainImage
     *
     * @return news
     */
    public function setMainImage($mainImage)
    {
        $this->mainImage = $mainImage;

        return $this;
    }

    /**
     * Get mainImage
     *
     * @return string
     */
    public function getMainImage()
    {
        return $this->mainImage;
    }

    /**
     * Set secondImage
     *
     * @param string $secondImage
     *
     * @return news
     */
    public function setSecondImage($secondImage)
    {
        $this->secondImage = $secondImage;

        return $this;
    }

    /**
     * Get secondImage
     *
     * @return string
     */
    public function getSecondImage()
    {
        return $this->secondImage;
    }


    /**
     * @return \AppBundle\Entity\tag
     */
    public function getTag()
    {
        return $this->tag;
    }

    /**
     * @param \AppBundle\Entity\tag $tag
     */
    public function setTag($tag)
    {
        $this->tag = $tag;
    }

    /**
     * @return \AppBundle\Entity\type
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param \AppBundle\Entity\type $type
     */
    public function setType($type)
    {
        $this->type = $type;
    }
}

