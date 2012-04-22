<?php

print "<pre>";  
//$repos = array('php', 'java', 'ruby', 'python', 'c', 'javascript');
$repos = array('python'=>'.py');

$m = new Mongo();
$db = $m->hot_or_not;
$collection = $db->users;

$usernames = array(); 
foreach($repos as $repo=> $ext){
    $users = "https://github.com/api/v2/json/repos/search/" . $repo;
    $users = json_decode(file_get_contents($users));
    $user_repos= $users->repositories;
    foreach($user_repos as $key=>$val){
        $username = $val->username;
        syslog(LOG_ERR,"Getting all files for $username");
        store_all_files($username,$repo,$ext,$collection);
    }
}

print "</pre>";  



function get_random_file($username){
    $repo_url = "https://api.github.com/users/" . $username . "/repos";
    $repos = json_decode(file_get_contents($repo_url));
    print "they have " . count($repos) . " different repos, we are randomly looking at: ";
    $rand_repo = $repos[array_rand($repos)];
    print $rand_repo->name . "\n";

    // $file_path = "https://github.com/api/v2/json/repos/show/$username/" . $rand_repo->name . "/blobs";
    $file_path = "https://api.github.com/repos/$username/" . $rand_repo->name . "/commits";
    $commits = json_decode(file_get_contents($file_path));
    $commit = $commits[0];
    $tree = $commit->commit->tree->sha;
    $blobs = get_blobs_in_tree($tree,$username,$rand_repo->name);
    foreach($blobs as $blob){
        print "Examining file: " . $blob->path . "\n";
    }

}

function store_all_files($username,$lang,$ext,$collection){
    $repo_url = "https://api.github.com/users/" . $username . "/repos";
    $repos = json_decode(file_get_contents($repo_url));
    $rand_repo = $repos[array_rand($repos)];

    syslog(LOG_ERR,"they have " . count($repos) . " different repos, we are randomly looking at: " . $rand_repo->name);

    // $file_path = "https://github.com/api/v2/json/repos/show/$username/" . $rand_repo->name . "/blobs";
    $file_path = "https://api.github.com/repos/$username/" . $rand_repo->name . "/commits";
    syslog(LOG_ERR,$file_path);
    $commits = json_decode(file_get_contents($file_path));
    $commit = $commits[0];
    $tree = $commit->commit->tree->sha;
    $blobs = get_blobs_in_tree($tree,$username,$rand_repo->name,$ext);
    foreach($blobs as $blob){
        try{
            $text = http_blob_get($blob->url);
            $collection->update(array('username'=>$username),array('$push'=>array('files'=>array('name'=>$blob->path,'text'=>$text,'lang'=>$lang))),array("upsert"=>true));
        }catch (Exception $e){
            continue;
        }
    }



}

function endswith($string, $test) {
    $strlen = strlen($string);
    $testlen = strlen($test);
    if ($testlen > $strlen) return false;
    return substr_compare($string, $test, -$testlen) === 0;
}

function get_blobs_in_tree($tree_hash,$username,$repo_name,$ext){
    $to_return = array();

    $file_path = "https://api.github.com/repos/$username/$repo_name/git/trees/$tree_hash";
    syslog(LOG_ERR,$file_path);
    $tree= json_decode(file_get_contents($file_path));

    foreach($tree->tree as $elem){
        if($elem->type === "blob"){
            if(endswith($elem->path,$ext)){            
                print "found file: " . $elem->path . "\n";
                $to_return[] = $elem;
            }
        } else{
            print "Recursing into directory " . $elem->path . "\n";
            $sub_tree = get_blobs_in_tree($elem->sha,$username,$repo_name,$ext);
            foreach($sub_tree as $blob){
                $to_return[] = $blob;
            }
        }
    }
    return $to_return;

}

function http_blob_get($url){
    $opts = array(
            'http'=>array(
                'method'=>"GET",
                'header'=>"Accept-language: en\r\n" .
                "Accept: application/vnd.github-blob.raw\r\n"
                )
            );

    $context= stream_context_create($opts);
    return file_get_contents($url,false,$context);

}

