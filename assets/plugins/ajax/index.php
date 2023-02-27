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
        // die(json_encode(array('result' => $json)));
        $parents = [];
        $items = [];
        foreach ($json as $k => $f) {
            $parents[$k] = $f['parents'];
            $items[$k] = $f['items'];
        }
        $pids = [];
        foreach ($parents as $k => $ps) {
            $doc = new modResource($modx, true);
            foreach ($ps as $kp => $p) {
                $ex_base = (int)$modx->db->getValue($modx->db->select('contentid', $modx->getFullTableName('site_tmplvar_contentvalues'), 'tmplvarid = ' . $base_id . ' and value = ' . $p['id'], 'id ASC', 1));
                $pid = $ex_base ? $ex_base : $modx->db->getValue($modx->db->select('id', $modx->getFullTableName('site_content'), 'pagetitle = "' . $modx->db->escape($p['menutitle']) . '" or  menutitle = "' . $modx->db->escape($p['menutitle']) . '"'));
                $ex_pid = $p['id'];
                if (!$pid) {
                    unset($p['id']);
                    $doc->create($p);
                    foreach ($p as $kf => $fields) {
                        if (is_array($fields)) {
                            $doc->set($kf, $fields[1]);
                        } else
                            $doc->set($kf, $fields);
                    }
                    $doc->set('base_id', $ex_pid);
                    $pid = $doc->save(true, false);
                    $p_ids[$p['id']] = $pid;
                }
            }
        }
        $out_p = [];
        foreach ($items as $k => $ps) {
            $doc = new modResource($modx, true);
            foreach ($ps as $kp => $p) {
                $ex_base = $modx->db->getValue($modx->db->select('contentid', $modx->getFullTableName('site_tmplvar_contentvalues'), 'tmplvarid = ' . $base_id . ' and value = ' . $p['id'], 'id ASC', 1));
                $ex_base_p = (int)$modx->db->getValue($modx->db->select('contentid', $modx->getFullTableName('site_tmplvar_contentvalues'), 'tmplvarid = ' . $base_id . ' and value = ' . $p['parent'], 'id ASC', 1));
                $p['parent'] = $ex_base_p != 0 ? $ex_base_p : (int)$modx->db->getValue($modx->db->select('id', $modx->getFullTableName('site_content'), 'pagetitle ="' . $p['parent_title'] . '" or menutitle = "' . $p['parent_title'] . '" '));
                $p['menutitle'] = !empty($p['menutitle']) ? $p['menutitle'] : $p['pagetitle'];
                $pid = $ex_base ? $ex_base : $modx->db->getValue($modx->db->select('id', $modx->getFullTableName('site_content'), '( pagetitle ="' . $p['pagetitle'] . '" or  menutitle = "' . $p['menutitle'] . '" ) and parent ='.$p['parent']));

                if (!$pid) {
                    $ex_pid = $p['id'];
                    unset($p['id']);
                    unset($p['relation']);
                    $doc->create($p);
                    $doc->set('base_id', $ex_pid);
                    foreach ($p as $kf => $fields) {
                        if (!is_array($fields)) {
                            $doc->set($kf, $modx->db->escape($fields));
                        } else {
                            $doc->set($kf, $fields[1]);
                        }

                    }
                    $pid = $doc->save(true, false);
                } else {
                    $doc->edit($pid);
                    if($p['template'] == 9 ){
                        $lang_arr = $modx->db->select('sc.id as id,gp.pagetitle as gp_title','`evo_site_content` as sc
left join `evo_site_content` as p on p.id = sc.parent
left join `evo_site_content` as gp on gp.id = p.parent','sc.alias = "'.$p['alias'].'"','gp.id ASC, sc.id ASC');
                    }else{
                        $lang_arr = $modx->db->select('sc.id as id,gp2.pagetitle as gp_title','`evo_site_content` as sc
left join `evo_site_content` as p on p.id = sc.parent
left join `evo_site_content` as gp on gp.id = p.parent
left join `evo_site_content` as gp2 on gp2.id = gp.parent','sc.pagetitle = "'.$p['pagetitle'].'"','gp2.id ASC, sc.id ASC');
                    }
                    $rel = [];
                    while ($lang = $modx->db->getRow($lang_arr)){
                        $rel[] = strtolower($lang['gp_title']).':'.$lang['id'];
                    }
                    $rel = implode('||',$rel);
                    $ex_pid = $p['id'];
                    unset($p['id']);
                    unset($p['relation']);
                    $doc->set('base_id', $ex_pid);
                    $document = $modx->getDocumentObject('id', $pid);
                    foreach ($document as $kf => $fields) {
                        if (!is_array($p[$kf])) {
                            if($p[$kf] != $fields[1])
                                $doc->set($kf, $p[$kf]);
                        } else {
                            if($p[$kf][1] != $fields[1])
                                $doc->set($kf, $p[$kf][1]);
                        }

                    }
                    $doc->set('relation',$rel);
                    $pid = $doc->save();
                }
                $out_p[$pid] = $p['id'];
                $modx->clearCache();
            }
        }
        http_response_code(200);
        die(json_encode(array('result' => $out_p)));
    }
}