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

}

?>
