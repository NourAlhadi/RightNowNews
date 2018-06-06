<?php

namespace AppBundle\Service;


use DateTime;
use Doctrine\Common\Persistence\ObjectManager;

class FilterNews{

    /**
     * The News Set To Be Filtered
     * @var \AppBundle\Entity\news[]
     */
    private $news_set;


    /**
     * News set Getter
     * @return \AppBundle\Entity\news[] $news_set
     */
    public function getNewsSet()
    {
        return $this->news_set;
    }

    /**
     * News Set Setter
     * @param \AppBundle\Entity\news[] $news_set
     */
    public function setNewsSet($news_set)
    {
        $this->news_set = $news_set;
    }

    /**
     * Get the news related only to the given location
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
     * Get all news NOT related to the given location
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
     * Get all news of given type
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




    function edit_distance($str1, $str2){
        $length1 = mb_strlen( $str1, 'UTF-8');
        $length2 = mb_strlen( $str2, 'UTF-8');
        if( $length1 < $length2) return $this->edit_distance($str2, $str1);
        if( $length1 == 0 ) return $length2;
        if( $str1 === $str2) return 0;
        $prevRow = range( 0, $length2);
        for ( $i = 0; $i < $length1; $i++ ) {
            $currentRow=array();
            $currentRow[0] = $i + 1;
            $c1 = mb_substr( $str1, $i, 1, 'UTF-8') ;
            for ( $j = 0; $j < $length2; $j++ ) {
                $c2 = mb_substr( $str2, $j, 1, 'UTF-8' );
                $insertions = $prevRow[$j+1] + 1;
                $deletions = $currentRow[$j] + 1;
                $substitutions = $prevRow[$j] + (($c1 != $c2)?1:0);
                $currentRow[] = min($insertions, $deletions, $substitutions);
            }
            $prevRow = $currentRow;
        }
        return $prevRow[$length2];
    }


    private function getDelta($head, $title){
        $src = mb_split(" ", $head);
        $tar = mb_split(" ", $title);

        $ret = 0;
        for ($i = 0; $i < sizeof($tar); $i++){
            $tmp = mb_strlen($tar[$i]);
            $len1 = mb_strlen($tar[$i]);
            for ($j = 0; $j < sizeof($src); $j++){
                $len2 = mb_strlen($src[$j]);
                $tmp = min($tmp,$this->edit_distance($tar[$i],$src[$j]) + abs($len2 - $len1));
            }
            $ret += $tmp;
        }
        return $ret;
    }

    /**
     *
     * @param string $title
     * @return \AppBundle\Entity\news[]
     */
    public function filter_by_title($title){
        $ret = [];
        dump($title);
        for ($i = 0;$i < sizeof($this->news_set); $i++){
            $head = $this->news_set[$i]->getTitle();
            //dump($head);
            //dump($this->getDelta($head,$title));
            if ($this->getDelta($head,$title) <= 2){
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

    /**
     * @param $tag \AppBundle\Entity\tag[]
     * @param bool $lock
     * @return \AppBundle\Entity\tag[]
     */
    public function filter_by_tags($tag, $lock = false){
        $arr = [];
        for ($i = 0; $i < sizeof($tag); $i++){
            if ($tag[$i] == null) continue;
            $name = $tag[$i]->getName();
            array_push($arr,$name);
        }
        $ret = [];
        $used = [];
        for ($i = 0; $i < sizeof($this->news_set); $i++){
            $news = $this->news_set[$i];
            $tags = $news->getTag();
            for ($j = 0; $j < sizeof($tags); $j++){
                if ($tags[$j] == null) continue;
                $name = $tags[$j]->getName();
                if (in_array($name,$arr)){
                    array_push($ret,$news);
                    array_push($used,$news->getId());
                    break;
                }
            }
        }
        if ($lock == false && sizeof($ret) < 4){
            $cnt = 0;
            for ($i = 0; $i < sizeof($this->news_set); $i++){
                if ($cnt >= 4) break;
                $news = $this->news_set[$i];
                $id = $news->getId();
                if (in_array($id,$used)) continue;
                $cnt++;
                array_push($ret,$news);
            }
        }
        return $ret;
    }

    public function exclude($id){
        $news = $this->news_set;
        $ret = [];
        for ($i = 0; $i < sizeof($news); $i++){
            $post = $news[$i];
            if ($post->getId() == $id) continue;
            array_push($ret,$post);
        }
        return $ret;
    }

    /**
     * @param $data string
     * @param $em ObjectManager
     * @return \AppBundle\Entity\type
     */
    public function getNearestType($data,$em){
        $ret = null;
        $all = $em->getRepository('AppBundle:type')->findAll();
        $res = mb_strlen($data);
        for ($i = 0; $i < sizeof($all); $i++){
            $type = $all[$i]->getName();
            $curr = $this->edit_distance($data,$type);
            if ($curr < $res){
                $res = $curr;
                $ret = $all[$i];
            }
        }
        if ($res > 4) return null;
        return $ret;
    }

    /**
     * @param $data string
     * @param $em ObjectManager
     * @return \AppBundle\Entity\tag[]
     */
    public function getNearestTags($data,$em){
        $ret = [];
        $all = $em->getRepository('AppBundle:tag')->findAll();
        for ($i = 0; $i < sizeof($all); $i++){
            $curr = $all[$i]->getName();
            $curr = $this->mb_str_replace(" "," ",$curr);
            //dump($curr);
            //dump($this->edit_distance($curr,$data));
            if ($this->edit_distance($curr,$data) < 3){
                array_push($ret,$all[$i]);
            }
        }
        return $ret;
    }

    public function search_engine($data, $classifier, $em){
        if ($classifier == "title"){
            return $this->filter_by_title($data);
        }else if ($classifier == "type"){
            $type = $this->getNearestType($data,$em);
            return $this->filter_by_type($type);
        }else{
            $tags = $this->getNearestTags($data,$em);
            return $this->filter_by_tags($tags,true);
        }
    }



    function mb_str_replace($search, $replace, $subject, &$count = 0) {
        if (!is_array($subject)) {
            // Normalize $search and $replace so they are both arrays of the same length
            $searches = is_array($search) ? array_values($search) : array($search);
            $replacements = is_array($replace) ? array_values($replace) : array($replace);
            $replacements = array_pad($replacements, count($searches), '');
            foreach ($searches as $key => $search) {
                $parts = mb_split(preg_quote($search), $subject);
                $count += count($parts) - 1;
                $subject = implode($replacements[$key], $parts);
            }
        } else {
            // Call mb_str_replace for each subject in array, recursively
            foreach ($subject as $key => $value) {
                $subject[$key] = $this->mb_str_replace($search, $replace, $value, $count);
            }
        }
        return $subject;
    }

    public function rangeDate($st, $en){
        $ret = [];
        $st = ($st == null ? null: new DateTime($st));
        $en = ($en == null ? null: new DateTime($en));
        if ($en != null){
            $en->modify("+1 day");
        }
        for ($i = 0 ; $i < sizeof($this->news_set); $i++){
            $post = $this->news_set[$i];
            $dt = $post->getDate();
            //dump($st);
            //dump($en);
            //dump($dt);
            if ( ($st == null && $dt <= $en) || ($en == null && $dt >= $st) || ($dt >= $st && $dt <= $en) ){
                array_push($ret,$post);
            }
        }
        return $ret;
    }

}