/**
* rem_domain_imgs
*
* Проверка сущестования картинки
*
* @category    plugin
* @version     0.1
* @author      sahar-08
* @internal    @events OnWebPagePrerender
* @internal    @properties &url=Основной сайт;text;;https://zrobim.by/;
* @internal    @modx_category Manager and Admin
* @internal    @installset base
*/


$content = $modx->event->params['documentOutput'];

$imgs = array();
preg_match_all('/<img[^>]+>/i',$content, $result);
if (count($result))
{

foreach($result[0] as $img_tag)
{
preg_match('/(src)=("[^"]*")/i',$img_tag, $img[$img_tag]);
$img_real = str_replace('"','',$img[$img_tag][2]);
$img_real = str_replace('./','',$img_real);
if ((strpos($img_real, '.jpg')!==false) or (strpos($img_real, '.jpeg')!==false) or (strpos($img_real, '.png')!==false)) $imgs[] = $img_real;
}
$imgs = array_unique($imgs);
foreach($imgs as $img_real)
{
if(stripos($img_real,'http://') === false or stripos($img_real,'https://') ===false){
if (!file_exists($modx->config['base_path'].$img_real))
{
if(stripos($img_real,'//') === false)
$image = $params['url'].$img_real;
else{
$image = $img_real;
}
$i = $image;
}
else $i = $img_real;
$content = str_replace($img_real, $i, $content);
}

}
}

$modx->event->output($content);