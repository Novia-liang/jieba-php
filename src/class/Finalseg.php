<?php
/**
 * Finalseg.php
 *
 * PHP version 5
 *
 * @category PHP
 * @package  /class/
 * @author   Fukuball Lin <fukuball@gmail.com>
 * @license  MIT Licence
 * @version  GIT: <fukuball/iloveck101>
 * @link     https://github.com/fukuball/iloveck101
 */

/**
 * Finalseg
 *
 * @category PHP
 * @package  /class/
 * @author   Fukuball Lin <fukuball@gmail.com>
 * @license  MIT Licence
 * @version  Release: <0.0.1>
 * @link     https://github.com/fukuball/iloveck101
 */
class Finalseg
{

    public static $prob_start = array();
    public static $prob_trans = array();
    public static $prob_emit = array();

    /**
     * Static method init
     *
     * @param array $options # other options
     *
     * @return void
     */
    public static function init($options=array())
    {

        $defaults = array(
            'mode'=>'default'
        );

        $options = array_merge($defaults, $options);

        self::$prob_start = self::load_model(dirname(dirname(__FILE__)).'/model/prob_start.json');
        self::$prob_trans = self::load_model(dirname(dirname(__FILE__)).'/model/prob_trans.json');
        self::$prob_emit = self::load_model(dirname(dirname(__FILE__)).'/model/prob_emit.json');

    }// end function init

    /**
     * Static method load_model
     *
     * @param string $f_name # input f_name
     * @param array $options # other options
     *
     * @return void
     */
    public static function load_model($f_name, $options=array())
    {

        $defaults = array(
            'mode'=>'default'
        );

        $options = array_merge($defaults, $options);

        return json_decode(file_get_contents($f_name), true);

    }// end function load_model

    /**
     * Static method viterbi
     *
     * @param string $sentence # input sentence
     * @param array  $options  # other options
     *
     * @return array $viterbi
     */
    public static function viterbi($sentence, $options=array())
    {

        $defaults = array(
            'mode'=>'default'
        );

        $options = array_merge($defaults, $options);

        $obs = $sentence;
        $states = array('B', 'M', 'E', 'S');
        $V = array();
        $V[0] = array();
        $path = array();

        foreach ($states as $key=>$state) {
            $y = $state;
            $c = mb_substr($obs, 0, 1, 'UTF-8');
            $prob_emit = 0.0;
            if (isset(self::$prob_emit[$y][$c])) {
                $prob_emit = self::$prob_emit[$y][$c];
            } else {
                $prob_emit = array_values(self::$prob_emit[$y])[0];
            }
            $V[0][$y] = self::$prob_start[$y]*$prob_emit;
            $path[$y] = $y;
        }

        for ($t=1; $t<mb_strlen($obs, 'UTF-8'); $t++) {
            $c = mb_substr($obs, $t, 1, 'UTF-8');
            $V[$t] = array();
            $newpath = array();
            foreach ($states as $key=>$state) {
                $y = $state;
                $temp_prob_array = array();
                foreach ($states as $key=>$state0) {
                    $y0 = $state0;
                    $prob_trans = 0.0;
                    if (isset(self::$prob_trans[$y0][$y])) {
                        $prob_trans = self::$prob_trans[$y0][$y];
                    } else {
                        $prob_trans = 0;
                    }
                    $prob_emit = 0.0;
                    if (isset(self::$prob_emit[$y][$c])) {
                        $prob_emit = self::$prob_emit[$y][$c];
                    } else {
                        $prob_emit = 0;
                    }
                    $temp_prob_array[$y0] = $V[$t-1][$y0]*$prob_trans*$prob_emit;
                }
                arsort($temp_prob_array);
                $max_prob = reset($temp_prob_array);
                $max_key = key($temp_prob_array);
                $V[$t][$y] = $max_prob;
                if (is_array($path[$max_key])) {
                    $newpath[$y] = array();
                    foreach ($path[$max_key] as $key=>$path_value) {
                        array_push($newpath[$y], $path_value);
                    }
                    array_push($newpath[$y], $y);
                } else {
                    $newpath[$y] = array($path[$max_key], $y);
                }
            }
            $path = $newpath;
        }

        $es_states = array('E','S');
        $temp_prob_array = array();
        $len = mb_strlen($obs, 'UTF-8');
        foreach ($es_states as $key=>$state) {
            $y = $state;
            $temp_prob_array[$y] = $V[$len-1][$y];
        }
        arsort($temp_prob_array);
        $prob = reset($temp_prob_array);
        $state = key($temp_prob_array);

        return array("prob"=>$prob, "pos_list"=>$path[$state]);

    }// end function viterbi

    /**
     * Static method __cut
     *
     * @param string $sentence # input sentence
     * @param array  $options  # other options
     *
     * @return array $words
     */
    public static function __cut($sentence, $options=array())
    {

        $defaults = array(
            'mode'=>'default'
        );

        $options = array_merge($defaults, $options);

        $words = array();

        $viterbi_array = self::viterbi($sentence);
        $prob = $viterbi_array['prob'];
        $pos_list = $viterbi_array['pos_list'];

        $begin = 0;
        $next = 0;
        $len = mb_strlen($sentence, 'UTF-8');

        for ($i=0; $i<$len; $i++) {
            $char = mb_substr($sentence, $i, 1, 'UTF-8');
            $pos = $pos_list[$i];
            if ($pos=='B') {
                $begin = $i;
            } else if ($pos=='E') {
                array_push($words, mb_substr($sentence, $begin, (($i+1)-$begin), 'UTF-8'));
                $next = $i+1;
            } else if ($pos=='S') {
                array_push($words, $char);
                $next = $i+1;
            }
        }

        if ($next<$len) {
            array_push($words, mb_substr($sentence, $next, null, 'UTF-8'));
        }

        return $words;

    }// end function __cut

}// end of class Finalseg

Finalseg::init();
?>