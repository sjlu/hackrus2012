<?php
  
    print "<pre>";  
  $repos = array('php', 'java', 'ruby', 'python', 'c', 'javascript');
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

    print " we have a total of " . count($usernames) . " users";
foreach($usernames as $user){
    print "looking at $user\n";
    get_random_file($user);
}

    print "</pre>";  

function get_random_file($username){
    $repo_url = "https://api.github.com/users/" . $username . "/repos";
    $repos = json_decode(file_get_contents($repo_url));
    foreach($repos as $key=>$val){
        print "looking at $key=>";
        print_r($val);
        print "\n";
    }
}

?>
