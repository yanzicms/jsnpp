<?php
/**
 * Jsnpp - A full-chain PHP framework
 * Author: A.J <804644245@qq.com>
 * Copyright: Jsnpp [http://www.jsnpp.com] All rights reserved.
 * Licensed: Apache-2.0
 * GitHub: https://github.com/yanzicms/jsnpp
 */
namespace jsnpp;

use jsnpp\exception\TemplateSyntaxException;

class Response
{
    protected $header = [];
    protected $data;
    protected $isJson = false;
    private $app;
    private $request;
    private $assign = [];
    public function __construct(Application $app, Request $request){
        $this->app = $app;
        $this->request = $request;
    }
    public function setHeader($name, $value = null)
    {
        if(is_array($name)){
            $this->header = array_merge($this->header, $name);
        }
        else{
            $this->header[$name] = $value;
        }
        return $this;
    }
    public function setCode($code)
    {
        http_response_code($code);
        return $this;
    }
    public function getCode()
    {
        return http_response_code();
    }
    public function output()
    {
        $this->outheader();
        echo $this->data;
        if($this->isJson){
            exit();
        }
    }
    public function end($data = '')
    {
        $this->receive($data)->outheader();
        ob_clean();
        echo $this->data;
        ob_end_flush();
        exit();
    }
    private function outheader()
    {
        if(!headers_sent() && !empty($this->header)){
            foreach($this->header as $key => $val){
                header($key . (!is_null($val) ? ':' . $val : ''));
            }
        }
    }
    public function setContentType($contentType, $charset = 'utf-8')
    {
        $this->setHeader('Content-Type', $contentType . '; charset=' . $charset);
        return $this;
    }
    public function setJsonHeader()
    {
        $this->setContentType('application/json');
    }
    public function receive($data)
    {
        $this->isJson = false;
        if(is_array($data)){
            $this->data = json_encode($data);
            $this->isJson = true;
        }
        else{
            if(is_string($data) && Tools::isJson($data) && $this->request->isJson()){
                $this->isJson = true;
            }
            $this->data = $data;
        }
        if($this->isJson){
            $this->setJsonHeader();
        }
        return $this;
    }
    public function setAssign($name, $value = '')
    {
        if(is_array($name)){
            $this->assign = array_merge($this->assign, $name);
        }
        else{
            $this->assign[$name] = $value;
        }
    }
    public function appendAssign($name, $value = '')
    {
        if(is_array($name)){
            foreach($name as $key => $val){
                $this->append($key, $val);
            }
        }
        else{
            $this->append($name, $value);
        }
    }
    private function append($name, $value)
    {
        if($this->hasAssign($name)){
            $this->assign[$name] .= $value;
        }
        else{
            $this->assign[$name] = $value;
        }
    }
    public function hasAssign($name)
    {
        return isset($this->assign[$name]) ? true : false;
    }
    public function resetAssign()
    {
        $this->assign = [];
        return $this;
    }
    public function template($content)
    {
        $ostart = $this->app->getConfig('tagstart');
        $oend = $this->app->getConfig('tagsend');
        $start = str_replace('{', '\{', $ostart);
        $end = str_replace('}', '\}', $oend);
        if(is_file($content)){
            $tplfile = $content;
            $content = $this->getcontent($content);
            $content = $this->getcss($content, $tplfile);
            $content = $this->getjs($content, $tplfile);
        }
        $compfile = md5($content);
        $comp = $this->app->rootDir() . DIRECTORY_SEPARATOR . 'assist' . DIRECTORY_SEPARATOR . 'comp' . DIRECTORY_SEPARATOR . $compfile . '.php';
        if(!is_file($comp)){
            $content = preg_replace("/\<\!\-\-[\S\s]*?\-\-\>/s", '', $content);
            $tpl = $this->convert($content, $start, $end);
            file_put_contents($comp, $tpl);
        }
        extract($this->assign);
        ob_implicit_flush(false);
        include $comp;
        $html = ob_get_clean();
        return $html;
    }
    private function getcss($content, $tplfile)
    {
        while(preg_match('/{css( ){1,}((\.\.\/)*[A-Za-z][A-Za-z0-9_\-]*(\/[A-Za-z][A-Za-z0-9_\-]*)*)}/i', $content, $mat)){
            $tplinc = dirname($tplfile) . DIRECTORY_SEPARATOR . $mat[2] . '.css';
            if(is_file($tplinc)){
                $incfile = file_get_contents($tplinc);
                $content = str_replace($mat[0], '<style>' . PHP_EOL . $incfile . PHP_EOL . '</style>', $content);
                continue;
            }
            else{
                $content = str_replace($mat[0], '', $content);
            }
        }
        return $content;
    }
    private function getjs($content, $tplfile)
    {
        while(preg_match('/{js( ){1,}((\.\.\/)*[A-Za-z][A-Za-z0-9_\-]*(\/[A-Za-z][A-Za-z0-9_\-]*)*)}/i', $content, $mat)){
            $tplinc = dirname($tplfile) . DIRECTORY_SEPARATOR . $mat[2] . '.js';
            if(is_file($tplinc)){
                $incfile = file_get_contents($tplinc);
                $content = str_replace($mat[0], '<script>' . PHP_EOL . $incfile . PHP_EOL . '</script>', $content);
                continue;
            }
            else{
                $content = str_replace($mat[0], '', $content);
            }
        }
        return $content;
    }
    private function getcontent($tplfile)
    {
        $content = file_get_contents($tplfile);
        while(preg_match('/{include( ){1,}((\.\.\/)*[A-Za-z][A-Za-z0-9_\-]*(\/[A-Za-z][A-Za-z0-9_\-]*)*)}/i', $content, $mat)){
            $tplinc = dirname($tplfile) . DIRECTORY_SEPARATOR . $mat[2] . '.' . $this->app->getConfig('templatesuffix');
            if(is_file($tplinc)){
                $incfile = file_get_contents($tplinc);
                $content = str_replace($mat[0], $incfile, $content);
                continue;
            }
            else{
                throw new TemplateSyntaxException('Template syntax error: ' . $mat[0]);
                break;
            }
        }
        return $content;
    }
    public function display($tplfile)
    {
        $ostart = $this->app->getConfig('tagstart');
        $oend = $this->app->getConfig('tagsend');
        $start = str_replace('{', '\{', $ostart);
        $end = str_replace('}', '\}', $oend);
        $content = $this->getcontent($tplfile);
        $compfile = md5($content);
        $compPath = $this->app->rootDir() . DIRECTORY_SEPARATOR . 'assist' . DIRECTORY_SEPARATOR . 'comp';
        $comp = $compPath . DIRECTORY_SEPARATOR . $compfile . '.php';
        if(!is_file($comp)){
            $content = preg_replace("/\<\!\-\-[\S\s]*?\-\-\>/s", '', $content);
            $tpl = $this->convert($content, $start, $end);
            if(!is_dir($compPath)){
                @mkdir($compPath, 0777, true);
            }
            file_put_contents($comp, $tpl);
        }
        $this->outheader();
        extract($this->assign);
        include $comp;
        exit();
    }
    private function convert($tpl, $start, $end)
    {
        $jsnpp = '#jsnpp#%jsnppbound%';
        $jsnpps = '#jsnpp#';
        $jsnppConversion = '#\j\s\n\p\p#%\j\s\n\p\p\b\o\u\n\d%';
        $jsnppConversions = '#\j\s\n\p\p#';
        $tpl = preg_replace('/<!--.*-->/is', '', $tpl);
        $tpl = str_replace($jsnpp, $jsnppConversion, $tpl);
        $tpl = str_replace($jsnpps, $jsnppConversions, $tpl);
        $preg = '/(?<!'.$start.')'.$start.'(lang\(.+?\)|[^:;\n'.$start.$end.']*?)'.$end.'(?!'.$end.')/';
        preg_match_all($preg, $tpl, $match);
        $tplshell = preg_replace($preg, $jsnpp, $tpl);
        $tplshellArr = explode($jsnpps, $tplshell);
        $match = $match[1];
        $stack = [];
        $eachorder = 0;
        foreach($match as $key => $val){
            $val = trim($val);
            if(substr($val, 0, 5) == 'lang('){
                $lang = trim(substr($val, 5, -1));
                $match[$key] = '<?php echo lang(' . $lang . '); ?>';
                
            }
            elseif(substr($val, 0, 4) == 'url('){
                $url = trim(substr($val, 4, -1));
                if(count($stack) > 0){
                    $url = $this->toeach($stack, $url);
                }
                $url = $this->ptoa($url);
                $match[$key] = '<?php echo url(' . $url . '); ?>';
            }
            elseif(substr($val, 0, 4) == 'act('){
                $act = trim(substr($val, 4, -1));
                if(count($stack) > 0){
                    $act = $this->toeach($stack, $act);
                }
                $act = $this->ptoa($act);
                if(strpos($act, '[') !== false){
                    if(substr($act, -1) == ']'){
                        $hasparam = true;
                        $hasvar = false;
                    }
                    else{
                        $hasparam = true;
                        $hasvar = true;
                    }
                }
                else{
                    if(strpos($act, ',') !== false){
                        $hasparam = false;
                        $hasvar = true;
                    }
                    else{
                        $hasparam = false;
                        $hasvar = false;
                    }
                }
                if($hasparam == false && $hasvar == false){
                    list($class, $func) = $this->getcontrfunc($act);
                    $actmp = md5($class . '_' . $func . '_' . serialize([]));
                    $phpstr = '<?php $' . $actmp . ' = act(' . $act . '); ?>';
                }
                else{
                    $actmp = preg_replace('/\[.*\]/', '', $act);
                    $actmparr = explode(',', $actmp);
                    $actmp = trim(trim(trim(end($actmparr)), '\'"'));
                    if(!empty($actmp)){
                        $phpstr = '<?php $' . $actmp . ' = act(' . $act . '); ?>';
                    }
                    else{
                        preg_match('/\[.*\]/', $act, $actarr);
                        $actarr = eval('return ' . $actarr[0] . ';');
                        list($class, $func) = $this->getcontrfunc($actmparr[0]);
                        $actmp = md5($class . '_' . $func . '_' . serialize($actarr));
                        $phpstr = '<?php $' . $actmp . ' = act(' . $act . '); ?>';
                    }
                }
                
                $match[$key] = $phpstr;
            }
            elseif(substr($val, 0, 5) == 'each '){
                list($eacharr, $eachi, $eachfrom, $eachto, $eachstep, $act) = $this->breakeach($val);
                $phpstr = '';
                if(!empty($act)){
                    if(count($stack) > 0){
                        $act = $this->toeach($stack, $act);
                    }
                    $act = $this->ptoa($act);
                    if(strpos($act, '[') !== false){
                        if(substr($act, -1) == ']'){
                            $hasparam = true;
                            $hasvar = false;
                        }
                        else{
                            $hasparam = true;
                            $hasvar = true;
                        }
                    }
                    else{
                        if(strpos($act, ',') !== false){
                            $hasparam = false;
                            $hasvar = true;
                        }
                        else{
                            $hasparam = false;
                            $hasvar = false;
                        }
                    }
                    if($hasparam == false && $hasvar == false){
                        list($class, $func) = $this->getcontrfunc($act);
                        $actmp = md5($class . '_' . $func . '_' . serialize([]));
                        $phpstr = '<?php $' . $actmp . ' = act(' . $act . '); ?>';
                    }
                    else{
                        $actmp = preg_replace('/\[.*\]/', '', $act);
                        $actmparr = explode(',', $actmp);
                        $actmp = trim(trim(trim(end($actmparr)), '\'"'));
                        if(!empty($actmp)){
                            $phpstr = '<?php $' . $actmp . ' = act(' . $act . '); ?>';
                        }
                        else{
                            preg_match('/\[.*\]/', $act, $actarr);
                            $actarr = eval('return ' . $actarr[0] . ';');
                            list($class, $func) = $this->getcontrfunc($actmparr[0]);
                            $actmp = md5($class . '_' . $func . '_' . serialize($actarr));
                            $phpstr = '<?php $' . $actmp . ' = act(' . $act . '); ?>';
                        }
                    }
                    $eacharr = '$' . $actmp;
                }
                if(count($stack) > 0){
                    $eacharr = $this->toeach($stack, $eacharr);
                    $eacharr = $this->ptoa($eacharr);
                }
                $orderstr = '';
                if($eachorder > 0){
                    $orderstr = $eachorder;
                }
                $eachorder ++;
                $stack[] = [$eacharr, $eachi, $eachorder];
                $match[$key] = $phpstr . '<?php if(isset('.$eacharr.') && is_array('.$eacharr.') && count('.$eacharr.') > 0){
$_jsnpp_keyarr_'.$eachorder.' = array_keys('.$eacharr.');
}else{
$_jsnpp_keyarr_'.$eachorder.' = [];
}
$_jsnpp_template_'.$eachorder.' = count($_jsnpp_keyarr_'.$eachorder.');
$_jsnpp_template_'.$eachorder.' = ('.$eachto.' < 0) ? $_jsnpp_template_'.$eachorder.' : ('.$eachto.' > $_jsnpp_template_'.$eachorder.' ? $_jsnpp_template_'.$eachorder.' : '.$eachto.');
for('.$eachi.' = '.$eachfrom.', $order' . $orderstr . ' = 1; '.$eachi.' < $_jsnpp_template_'.$eachorder.'; '.$eachi.'+='.$eachstep.', $order' . $orderstr . ' ++) { ?>';
            }
            elseif(substr($val, 0, 2) == 'if'){
                $val = trim(substr($val, 2));
                if(substr($val, 0, 1) != '(' || substr($val, -1) != ')'){
                    $val = '(' . $val . ')';
                }
                if(count($stack) > 0){
                    $val = $this->toeach($stack, $val);
                }
                $val = $this->ptoa($val);
                $match[$key] = '<?php if' . $val . ' { ?>';
            }
            elseif(substr($val, 0, 6) == 'elseif'){
                $val = trim(substr($val, 6));
                if(substr($val, 0, 1) != '(' || substr($val, -1) != ')'){
                    $val = '(' . $val . ')';
                }
                if(count($stack) > 0){
                    $val = $this->toeach($stack, $val);
                }
                $val = $this->ptoa($val);
                $match[$key] = '<?php } elseif' . $val . ' { ?>';
            }
            elseif(substr($val, 0, 6) == 'empty '){
                $val = substr($val, 6);
                if(count($stack) > 0){
                    $val = $this->toeach($stack, $val);
                }
                $val = $this->ptoa($val);
                $match[$key] = '<?php if(!isset(' . $val . ') || empty(' . $val . ')) { ?>';
            }
            elseif(substr($val, 0, 9) == 'notempty '){
                $val = substr($val, 9);
                if(count($stack) > 0){
                    $val = $this->toeach($stack, $val);
                }
                $val = $this->ptoa($val);
                $match[$key] = '<?php if(isset(' . $val . ') && !empty(' . $val . ')) { ?>';
            }
            elseif($val == 'endeach'){
                array_pop($stack);
                $eachorder --;
                $match[$key] = '<?php } ?>';
            }
            elseif($val == 'endif' || $val == 'endempty' || $val == 'endnotempty'){
                $match[$key] = '<?php } ?>';
            }
            elseif($val == 'else'){
                $match[$key] = '<?php } else { ?>';
            }
            else{
                $func = '';
                if(false !== $vpos = strpos($val, '|')){
                    $func = trim(substr($val, $vpos + 1));
                    $val = trim(substr($val, 0, $vpos));
                }
                if(count($stack) > 0){
                    $val = $this->toeach($stack, $val);
                }
                $val = $this->ptoa($val);
                if($func != ''){
                    if(false !== $funcpos = strpos($func, '(')){
                        $funcleft = substr($func, 0, $funcpos + 1);
                        $funcright = substr($func, $funcpos + 1);
                        if(trim($funcright) == ')'){
                            $fval = $funcleft . $val . $funcright;
                        }
                        else{
                            $fval = $funcleft . $val . ', ' . $funcright;
                        }
                    }
                    else{
                        $fval = $func . '(' . $val . ')';
                    }
                    $match[$key] = '<?php echo '.$fval.'; ?>';
                }
                else{
                    $match[$key] = '<?php echo '.$val.'; ?>';
                }
            }
        }
        $matchid = 0;
        foreach($tplshellArr as $key => $val){
            if(substr($val, 0, 12) == '%jsnppbound%'){
                $tplshellArr[$key] = $match[$matchid] . substr($val, 12);
                $matchid ++;
            }
        }
        $tpl = implode('', $tplshellArr);
        $tpl = str_replace($jsnppConversion, $jsnpp, $tpl);
        $tpl = str_replace($jsnppConversions, $jsnpps, $tpl);
        return $tpl;
    }
    private function getcontrfunc($name)
    {
        $name = trim(trim(trim($name), '\'"'));
        $name = str_replace('\\', '/', $name);
        if(strpos($name, '/') !== false){
            $arr = explode('/', $name);
            $class = $arr[0];
            $func = $arr[1];
        }
        else{
            $class = $this->app->getConfig('defaultcontroller');
            if(empty($class)){
                $class = 'index';
            }
            $func = $name;
        }
        return [$class, $func];
    }
    private function breakeach($str)
    {
        $act = '';
        if(stripos($str, ' act(') !== false){
            preg_match('/ act\(.*\)/', $str, $actarr);
            $act = trim($actarr[0]);
            $act = trim(substr($act, 4, -1));
            $str = preg_replace('/ act\(.*\)/', ' $_jsnpp_framework', $str);
        }
        $str = preg_replace('/( )+/', ' ', $str);
        $arr = explode(' ', $str);
        $arrlen = count($arr);
        $reArr = [];
        for($i = 0; $i < $arrlen; $i += 2){
            $reArr[$arr[$i]] = $arr[$i + 1];
        }
        if(!isset($reArr['each']) || !isset($reArr['in'])){
            throw new TemplateSyntaxException('Template syntax error: each');
        }
        if(strpos($reArr['in'], '.') !== false){
            $inarr = explode('.', $reArr['in']);
            $reArr['in'] = array_shift($inarr);
            foreach($inarr as $inval){
                $reArr['in'] .= '[\'' . $inval . '\']';
            }
        }
        if(!isset($reArr['from'])){
            $reArr['from'] = 0;
        }
        if(!isset($reArr['to'])){
            $reArr['to'] = -1;
        }
        if(!isset($reArr['step'])){
            $reArr['step'] = 1;
        }
        return [$reArr['in'], $reArr['each'], $reArr['from'], $reArr['to'], $reArr['step'], $act];
    }
    private function ptoa($val)
    {
        return preg_replace('/\.([A-Za-z][A-Za-z0-9_\-]*)/', '[\'$1\']', $val);
    }
    private function toeach(&$stack, $val)
    {
        foreach($stack as $skey => $sval){
            $val = preg_replace('/\\'.$sval[1].'(\.|\W|$)/', $sval[0].'[$_jsnpp_keyarr_' . $sval[2] . '['.$sval[1].']]$1', $val);
        }
        return $val;
    }
}