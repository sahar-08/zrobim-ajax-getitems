
<?php
/**
 * @var $modx
 * @var $base_id
 */

if ($modx->event->name == 'OnPageNotFound') {
    if ($_GET['q'] == 'get_docs') {
        include_once MODX_BASE_PATH . 'assets/lib/MODxAPI/modResource.php';

        $headers = getallheaders();
        if (!isset($headers['Authorization']) and $headers['Auth'] !== 'Basic ' . md5($modx->config['site_url'] . '123')) {
            die(json_encode(array('result' => 'Access error! invalid token!')));
        }
        $json = file_get_contents('php://input');
        if (empty($json))
            die(json_encode(array('result' => 'EMPTY DATA!!!')));
        $json = json_decode($json, 1);
        //die(json_encode(array('result' => $json)));
        $parents = [];
        $items = [];
        foreach ($json as $k => $f) {
            $parents[$k] = $f['parents'];
            $items[$k] = $f['items'];
        }
        $pids = [];
        foreach ($parents as $k => $ps) {
            $doc = new modResource($modx,true);
            foreach ($ps as $kp => $p) {
                $ex_base = (int)$modx->db->getValue($modx->db->select('contentid',$modx->getFullTableName('site_tmplvar_contentvalues'),'tmplvarid = '.$base_id.' and value = '.$p['id'],'id ASC',1));
                $pid = $ex_base ? $ex_base : $modx->db->getValue($modx->db->select('id', $modx->getFullTableName('site_content'), 'pagetitle = "' . $modx->db->escape($p['menutitle']) . '" or  menutitle = "' . $modx->db->escape($p['menutitle']) . '"'));

                if(!$pid){

                    $ex_pid = $p['id'];
                    unset($p['id']);
                    $doc->create($p);
                    $doc->set('base_id',$ex_pid);

                }else{
                    if($headers['post_type'] == 'upd'){
                        $doc->edit($pid);
                        unset($p['id']);
                        $document = (array)$modx->getDocument($pid);

                        foreach ($document as $kf => $fields){
                            if($p[$kf] != $fields){
                                $doc->set($kf, $p[$kf]);
                            }
                        }
                    }
                }
                $pid = $doc->save(false,false);

                $p_ids[$p['id']] = $pid;
            }
        }
        $out_p = [];
        foreach ($items as $k => $ps) {
            $doc = new modResource($modx,true);
            foreach ($ps as $kp => $p) {
                $ex_base = $modx->db->getValue($modx->db->select('contentid',$modx->getFullTableName('site_tmplvar_contentvalues'),'tmplvarid = '.$base_id.' and value = '.$p['id'],'id ASC',1));
                $p['menutitle'] = !empty($p['menutitle']) ? $p['menutitle'] : $p['pagetitle'];
                $pid = $ex_base ? $ex_base : $modx->db->getValue($modx->db->select('id', $modx->getFullTableName('site_content'), ' pagetitle ="' . $modx->db->escape($p['pagetitle']) . '" or  menutitle = "' . $modx->db->escape($p['menutitle']) . '"'));

                $ex_base_p = (int)$modx->db->getValue($modx->db->select('contentid',$modx->getFullTableName('site_tmplvar_contentvalues'),'tmplvarid = '.$base_id.' and value = '.$p['parent'],'id ASC',1));
                $p['parent'] = $ex_base_p ? $ex_base_p : $modx->db->getValue($modx->db->select('id', $modx->getFullTableName('site_content'), 'pagetitle ="' . $modx->db->escape($p['parent_title']) . '" or menutitle = "' . $modx->db->escape($p['parent_title']) . '" '));
                $out_p[$pid] = $p['parent'];
                if(!$pid){
                    $ex_pid = $p['id'];
                    unset($p['id']);
                    $doc->create($p);
                    $doc->set('base_id',$ex_pid);
                }else{
                    if($headers['post_type'] == 'upd'){
                        $doc->edit($pid);
                        unset($p['id']);


                        $document = $modx->getDocumentObject('id',$pid);
                        foreach ($document as $kf => $fields){
                            if(!is_array($p[$kf] )){
                                if($p[$kf] != $fields){
                                    $doc->set($kf, $p[$kf]);
                                }
                            }else{
                                if(($p[$kf][0] == $fields[$kf][0]) and ($p[$kf][1] != $fields[$kf][1])){
                                    $doc->set($kf, $p[$kf]);
                                }
                            }

                        }
                    }
                }
                $pid = $doc->save(true,false);
                $modx->clearCache();
                $pids[$p['id']] = $pid;
            }
        }
        http_response_code(200);
        die(json_encode(array('result' => $out_p)));
    }
}