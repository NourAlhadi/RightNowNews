<?php

namespace AppBundle\Service;


use DateTime;

class FilterNews{

    /**
     * @var \AppBundle\Entity\news[]
     */
    private $news_set;


    /**
     * @return \AppBundle\Entity\news[] $news_set
     */
    public function getNewsSet()
    {
        return $this->news_set;
    }

    /**
     * @param \AppBundle\Entity\news[] $news_set
     */
    public function setNewsSet($news_set)
    {
        $this->news_set = $news_set;
    }

    /**
     * @param string $location
     * @return \AppBundle\Entity\news[]
     */
    public function filter_by_location($location){
        $ret = [];
        for ($i = 0;$i < sizeof($this->news_set);$i++){
            $str = $this->news_set[$i]->getLocation();
            if (strpos($str, $location) !== false){
                array_push($ret, $this->news_set[$i]);
            }
        }
        return $ret;
    }


    /**
     * @param string $location
     * @return \AppBundle\Entity\news[]
     */
    public function filter_by_reversed_location($location){
        $ret = [];
        for ($i = 0;$i < sizeof($this->news_set);$i++){
            $str = $this->news_set[$i]->getLocation();
            if (strpos($str, $location) === false){
                array_push($ret, $this->news_set[$i]);
            }
        }
        return $ret;
    }

    /**
     * @param \AppBundle\Entity\type $type
     * @return \AppBundle\Entity\news[]
     */
    public function filter_by_type($type){
        $ret = [];
        for ($i = 0;$i < sizeof($this->news_set);$i++){
            $types = $this->news_set[$i]->getType();
            for ($j = 0; $j < sizeof($types); $j++){
                if ($type == $types[$j]){
                    array_push($ret,$this->news_set[$i]);
                }
            }
        }
        return $ret;
    }

    /**
     * @param string $title
     * @return \AppBundle\Entity\news[]
     */
    public function filter_by_title($title){
        $ret = [];
        for ($i = 0;$i < sizeof($this->news_set); $i++){
            $head = $this->news_set[$i]->getTitle();
            if (levenshtein($head,$title) <= abs(strlen($head) - strlen($title) )){
                array_push($ret,$this->news_set[$i]);
            }
        }
        return $ret;
    }

    /**
     * @return array
     */
    public function filter_hot_news(){
        $ret = [];
        for ($i = 0; $i < sizeof($this->news_set); $i++){
            $curr = $this->news_set[$i];
            if ($curr->getisHot()){
                $diff = date_diff(new DateTime(), $curr->getDate());
                $check = $diff->d + $diff->m + $diff->y;
                $hour = $diff->h ;
                $minutes = $diff->i + (60 * $hour);
                if ($check == 0 && $minutes <= 15){
                    array_push($ret,$this->news_set[$i]);
                }
            }
        }
        return $ret;
    }

}