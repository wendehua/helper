<?php
/**
 * +----------------------------------------------------------
 * 把返回的数据集转换成Tree
 * +----------------------------------------------------------
 * @access public
 * +----------------------------------------------------------
 * @param array $list 要转换的数据集
 * @param string $pid parent标记字段
 * @param string $level level标记字段
 * +----------------------------------------------------------
 * @return array
 * +----------------------------------------------------------
 */
if (!function_exists("list_to_tree")) {
    function list_to_tree($list, $pk = 'id', $pid = 'pid', $child = '_child', $root = 0)
    {
        // 创建Tree
        $tree = array();
        if (is_array($list)) {
            // 创建基于主键的数组引用
            $refer = array();
            foreach ($list as $key => $data) {
                $refer[$data[$pk]] =& $list[$key];
            }
            foreach ($list as $key => $data) {
                // 判断是否存在parent
                $parentId = $data[$pid];
                
                if ($root == $parentId) {
                    $tree[] =& $list[$key];
                } else {
                    if (isset($refer[$parentId])) {
                        $parent =& $refer[$parentId];
                        $parent[$child][] =& $list[$key];
                    }
                }
            }
        }
        return $tree;
    }
}
if (!function_exists("parse_name")) {
    function parse_name($name, $type = 0)
    {
        if ($type) {
            return ucfirst(preg_replace_callback('/_([a-zA-Z])/', function ($match) {
                return strtoupper($match[1]);
            }, $name));
        } else {
            return strtolower(trim(preg_replace("/[A-Z]/", "_\\0", $name), "_"));
        }
    }
}
if (!function_exists("msubstr_short")) {
    function msubstr_short($str, $length = 40, $suffix = false){
        $str = htmlspecialchars($str);
        $str = strip_tags($str);
        $str = htmlspecialchars_decode($str);
        $strlenth = 0;
        $is_suffix = false;
        $output = '';
        preg_match_all("/[\x01-\x7f]|[\xc2-\xdf][\x80-\xbf]|[\xe0-\xef][\x80-\xbf]{2}|[\xf0-\xff][\x80-\xbf]{3}/", $str, $match);
        foreach ($match[0] as $v){
            preg_match("/[\xe0-\xef][\x80-\xbf]{2}/", $v, $matchs);
            if (!empty($matchs[0])){
                $strlenth += 1;
            }elseif (is_numeric($v)){
                $strlenth += 0.545;
            }else{
                $strlenth += 0.475;
            }
            
            if ($strlenth > $length){
                $is_suffix = true;
                break;
            }
            $output .= $v;
        }
        $output = $suffix ? ($is_suffix ? $output .'...' : $output): $output;
        return $output;
    }
}

if (!function_exists("h")){
    //输出安全的html
    function h($text, $tags = null) {
        $text	=	trim($text);
        //完全过滤注释
        $text	=	preg_replace('/<!--?.*-->/','',$text);
        //完全过滤动态代码
        $text	=	preg_replace('/<\?|\?'.'>/','',$text);
        //完全过滤js
        $text	=	preg_replace('/<script?.*\/script>/','',$text);
        
        $text	=	str_replace('[','&#091;',$text);
        $text	=	str_replace(']','&#093;',$text);
        $text	=	str_replace('|','&#124;',$text);
        //过滤换行符
        $text	=	preg_replace('/\r?\n/','',$text);
        //br
        $text	=	preg_replace('/<br(\s\/)?'.'>/i','[br]',$text);
        $text	=	preg_replace('/(\[br\]\s*){10,}/i','[br]',$text);
        //过滤危险的属性，如：过滤on事件lang js
        while(preg_match('/(<[^><]+)( lang|on|action|background|codebase|dynsrc|lowsrc)[^><]+/i',$text,$mat)){
            $text=str_replace($mat[0],$mat[1],$text);
        }
        while(preg_match('/(<[^><]+)(window\.|javascript:|js:|about:|file:|document\.|vbs:|cookie)([^><]*)/i',$text,$mat)){
            $text=str_replace($mat[0],$mat[1].$mat[3],$text);
        }
        if(empty($tags)) {
            $tags = 'table|td|th|tr|i|b|u|strong|img|p|br|div|strong|em|ul|ol|li|dl|dd|dt|a';
        }
        //允许的HTML标签
        $text	=	preg_replace('/<('.$tags.')( [^><\[\]]*)>/i','[\1\2]',$text);
        //过滤多余html
        $text	=	preg_replace('/<\/?(html|head|meta|link|base|basefont|body|bgsound|title|style|script|form|iframe|frame|frameset|applet|id|ilayer|layer|name|script|style|xml)[^><]*>/i','',$text);
        //过滤合法的html标签
        while(preg_match('/<([a-z]+)[^><\[\]]*>[^><]*<\/\1>/i',$text,$mat)){
            $text=str_replace($mat[0],str_replace('>',']',str_replace('<','[',$mat[0])),$text);
        }
        //转换引号
        while(preg_match('/(\[[^\[\]]*=\s*)(\"|\')([^\2=\[\]]+)\2([^\[\]]*\])/i',$text,$mat)){
            $text=str_replace($mat[0],$mat[1].'|'.$mat[3].'|'.$mat[4],$text);
        }
        //过滤错误的单个引号
        while(preg_match('/\[[^\[\]]*(\"|\')[^\[\]]*\]/i',$text,$mat)){
            $text=str_replace($mat[0],str_replace($mat[1],'',$mat[0]),$text);
        }
        //转换其它所有不合法的 < >
        $text	=	str_replace('<','&lt;',$text);
        $text	=	str_replace('>','&gt;',$text);
        $text	=	str_replace('"','&quot;',$text);
        //反转换
        $text	=	str_replace('[','<',$text);
        $text	=	str_replace(']','>',$text);
        $text	=	str_replace('|','"',$text);
        //过滤多余空格
        $text	=	str_replace('  ',' ',$text);
        return $text;
    }
}

if (!function_exists("xml_to_array")){
    //将 xml数据转换为数组格式。
    function xml_to_array($xml){
        $reg = "/<(\w+)[^>]*>([\\x00-\\xFF]*)<\\/\\1>/";
        if(preg_match_all($reg, $xml, $matches)){
            $count = count($matches[0]);
            for($i = 0; $i < $count; $i++){
                $subxml= $matches[2][$i];
                $key = $matches[1][$i];
                if(preg_match( $reg, $subxml )){
                    $arr[$key] = xml_to_array( $subxml );
                }else{
                    $arr[$key] = $subxml;
                }
            }
        }
        return $arr;
    }
}
