<?php

print "<pre>";  
//$repos = array('php', 'java', 'ruby', 'python', 'c', 'javascript');
$repos = array('php');

$usernames = array(); 
foreach($repos as $repo){
    $users = "https://github.com/api/v2/json/repos/search/" . $repo;
    $users = json_decode(file_get_contents($users));
    $user_repos= $users->repositories;
    foreach($user_repos as $key=>$val){
        $username = $val->username;
        array_push($usernames, $username);
    }
}

print " we have a total of " . count($usernames) . " users\n";
$rand_user = $usernames[array_rand($usernames)];
print "We randomly are looking at the usr: $rand_user\n";
get_random_file($rand_user);

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

function get_blobs_in_tree($tree_hash,$username,$repo_name){
    $to_return = array();

    $file_path = "https://api.github.com/repos/$username/$repo_name/git/trees/$tree_hash";
    $tree= json_decode(file_get_contents($file_path));

    foreach($tree->tree as $elem){
        if($elem->type === "blob"){
            print "found file: " . $elem->path . "\n";
            $to_return[] = $elem;
        } else{
            print "Recursing into directory " . $elem->path . "\n";
            $sub_tree = get_blobs_in_tree($elem->sha,$username,$repo_name);
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

