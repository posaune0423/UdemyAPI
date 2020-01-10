<?php
require_once(__DIR__ . '/config.php');
require __DIR__ . '/vendor/autoload.php';



use \GuzzleHttp\Client;
use GuzzleHttp\Pool;
use GuzzleHttp\Psr7\Request;

$client = new Client();

$base_url = "https://www.udemy.com/api-2.0/courses/?page_size=100&ordering=highest-rated&ratings=4.2";
session_start();

$sub_category = array(
  '3D+%26+Animation',
  'Accounting+%26+Bookkeeping',
  'Advertising',
  'Affiliate%20Marketing',
  'Analytics+%26+Automation',
  'Apple',
  'Architectural%20Design',
  'Arts+%26+Crafts',
  'Beauty+%26+Makeup',
  'Branding',
  'Business%20Law',
  'Career%20Development',
  'Commercial+%26+Photography',
  'Communications',
  'Compliance',
  'Content%20Marketing',
  'Creativity',
  'Cryptocurrency+%26+Blockchain',
  'Dance',
  'Data+%26+Analytics',
  'Databases',
  'Design%20Thinking',
  'Design%20Tools',
  'Development%20Tools',
  'Dieting',
  'Digital%20Marketing',
  'Digital%20Photography',
  'E-Commerce',
  'Economics',
  'Engineering',
  'Entrepreneurship',
  'Fashion',
  'Finance',
  'Finance%20Cert+%26+Exam%20Prep',
  'Financial%20Modeling+%26+Analysis',
  'Fitness',
  'Food+%26+Beverage',
  'Game%20Design',
  'Game%20Development',
  'Gaming',
  'General%20Health',
  'Google',
  'Graphic%20Design',
  'Growth%20Hacking',
  'Happiness',
  'Hardware',
  'Home%20Business',
  'Home%20Improvement',
  'Human%20Resources',
  'Humanities',
  'Industry',
  'Influence',
  'Instruments',
  'Interior%20Design',
  'Investing+%26+Trading',
  'IT%20Certification',
  'Language',
  'Leadership',
  'Management',
  'Marketing%20Fundamentals',
  'Math',
  'Media',
  'Meditation',
  'Memory+%26+Study%20Skills',
  'Mental%20Health',
  'Microsoft',
  'Mobile%20Apps',
  'Money%20Management%20Tools',
  'Motivation',
  'Music%20Fundamentals', '
  Music%20Software',
  'Music%20Techniques',
  'Network+%26+Security',
  'Nutrition',
  'Online%20Education',
  'Operating%20Systems',
  'Operations',
  'Oracle',
  'Other',
  'Other%20Finance+%26+Economics',
  'Other%20Teaching+%26+Academics',
  'Parenting+%26+Relationships',
  'Personal%20Brand%20Building',
  'Personal%20Finance',
  'Personal%20Transformation',
  'Pet%20Care+%26+Training',
  'Photography%20Fundamentals',
  'Photography%20Tools',
  'Portraits',
  'Product%20Marketing',
  'Production%20Productivity',
  'Programming%20Languages',
  'Project%20Management',
  'Public%20Relations',
  'Real%20Estate',
  'Religion+%26+Spirituality',
  'Safety+%26+First%20Aid',
  'Sales',
  'SAP',
  'Science',
  'Search%20Engine%20Optimization',
  'Self%20Defense',
  'Self%20Esteem',
  'Social%20Media%20Marketing',
  'Social%20Science',
  'Software%20Engineering',
  'Software%20Testing',
  'Sports',
  'Strategy',
  'Stress%20Management',
  'Taxes',
  'Teacher%20Training',
  'Test%20Prep',
  'Travel',
  'User%20Experience',
  'Video+%26+Mobile%20Marketing',
  'Video%20Design',
  'Vocal',
  'Web%20Design',
  'Web%20Development',
  'Yoga'
);

foreach ($sub_category as $v) {
  $urlList[] = $base_url . '&subcategory=' . $v;
}

// var_dump($urlList);

$requests = function ($urlList) use ($client) {
  foreach ($urlList as $url) {
    yield function() use ($client, $url) {
      return $client->requestAsync('GET', $url, ['auth' => [CLIENT_ID, CLIENT_SECRET]]);
    };
  }
};

$contents = [];
$decode_res = [];

$pool = new Pool($client, $requests($urlList), [
  'concurrency' => 100,
  'fulfilled' => function ($response, $index) use ($urlList, &$contents) {
    $contents[$urlList[$index]] = [
      'html'             => $response->getBody()->getContents(),
      'status_code'      => $response->getStatusCode(),
    ];


  },
  'rejected' => function ($reason, $index) use ($urlList, &$contents) {
    // this is delivered each failed request
    $contents[$urlList[$index]] = [
      'html'   => '',
      'reason' => $reason
    ];
  },
]);

$promise = $pool->promise();
$promise->wait();

$id_list = [];

// var_dump($contents);
foreach($contents as $v) {
  $decode_res[] = json_decode($v['html'], true);
}

$results = [];

foreach($decode_res as $v) {
  $results[] = $v['results'];
}

foreach($results as $v) {
  foreach($v as $j) {
    // var_dump($j['title']);
    $id_list[] = $j['id'];
  }
}

$_SESSION['id_list'] = $id_list;
// var_dump($decode_res);



?>

<!DOCTYPE html>
<html lang="ja" dir="ltr">
<head>
  <meta charset="utf-8">
  <link rel="stylesheet" href="/style.css">
  <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">

  <title>UdemyAPI</title>
</head>
<body>
  <div class="container">
    <div class="button text-center">
      <a class="btn-info btn-lg btn my-4" href="/putcsv.php" role="button">Download</a>
    </div>
    <div class="courselist my-3">
      <h2 class="my-3">Course List</h2>
      <table class="table table-striped">
        <tr>
          <th>Course</th>
          <th>Course ID</th>
        </tr>
        <?php foreach ($results as $v) : ?>
          <?php foreach ($v as $j) : ?>
            <tr>
              <td><?php echo $j['title']; ?></td>
              <td><?php echo $j['id']; ?></td>
            </tr>
          <?php endforeach ?>
        <?php endforeach ?>
      </table>
    </div>

  </div>
</body>
</html>
