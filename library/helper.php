<?php

  function array_to_utf8($arr) {
    foreach ( $arr as $key => $value ) {
      if ( is_array($value) ) {
        $arr[$key] = array_to_utf8($value);
      } else {
        $arr[$key] = iconv('UTF-8', 'UTF-8//IGNORE', utf8_encode($value));
      }
    }

    return $arr;
  }

  function clear() {
    echo chr(27).chr(91).'H'.chr(27).chr(91).'J';
  }

  function fileChoice($color, $dir) {

    // Variables
    $files = [];

    // Build files list
    if ( $handle = opendir($dir) ) {
      while ( false !== ($entry = readdir($handle)) ) {
        if ( $entry != '.' && $entry != '..' ) {
          $files[] = $entry;
        }
      }
      closedir($handle);
    }

    clear();

    // No files
    if ( empty($files) ) {
      exit($color->parseSring("No files in \"{red:{$dir}}\", please use the scrape tool first\n"));
    }

    // Show select dialog
    echo $color->parseString("Choose a {cyan:file} to import:\n");
    
    foreach ( $files as $key => $file ) {
      echo $color->parseString('  {green:' . ($key + 1 ) . '}. ' . $file . "\n");
    }

    echo $color->parseString('Enter your choice ({green:1}): ');

    return $files;
  }

  function regionChoice($color, $options, $default) {
    
    clear();  
    echo $color->parseString("Choose a {cyan:region}:\n");
    
    foreach ( $options as $key => $value ) {
      echo $color->parseString('  {green:' . $key . '}. amazon' . $value . "\n");
    }
    
    echo $color->parseString('Enter your choice ({green:' . $default . '}): ');
  }

  function categoryChoice($color, $options, $default) {
    
    $length = 0;

    clear();
    echo $color->parseString("Choose a {cyan:category}:\n");

    foreach ( $options as $key => $value ) {
      if ( strlen($key) > $length ) { $length = strlen($key); }
    }
    
    foreach ( $options as $key => $value ) {
      echo $color->parseString('  {green:' . str_pad($key, $length, ' ') . '} ' . $value . "\n");
    }
    
    echo $color->parseString('Enter your choice ({green:' . $default . '}): ');
  }

  function countChoice($color, $default) {
    clear();
    echo $color->parseString("How many products would you like, maximum of {red:100}?\n");
    echo $color->parseString("Enter your choice ({green:{$default}}): ");
  }

  function request($url) {

    // URL filter
    $url = preg_replace('/(http[s]?:\/\/(.+?)http[s]?:\/\/.+?)\//', 'https://$2/', $url);

    // Variables
    $referral = parse_url($url)['host'];
    $agent    = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.12; rv:50.0) Gecko/20100101 Firefox/50.0';
    $headers  = [
      'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
      'Accept-Language: en-GB,en;q=0.5',
      'Accept-Encoding: gzip, deflate'
    ];

    // Start curl
    $ch = curl_init($url);

    // Set headers
    curl_setopt_array($ch, [
      CURLOPT_USERAGENT  => $agent,
      CURLOPT_HTTPHEADER => $headers,
      CURLOPT_REFERER    => $referral,
      CURLOPT_ENCODING   => 'gzip',
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_HEADER         => false,
      CURLOPT_FOLLOWLOCATION => true
    ]);

    // Make a request
    $result = curl_exec($ch);

    // Close curl
    curl_close($ch);
  
    return $result;
  }

  function cleanUp($arr) {
    
    // Variables
    $clean  = [];
    $arrays = ['images'];
   
    // Loop and filter fields
    foreach ( $arr as $key => $values ) {
      if ( ! is_numeric($key) ) {
        $filter = array_unique(array_filter($values));
        $clean[$key] = ( count($filter) > 1 || in_array($key, $arrays) ? array_values($filter) : trim(end($filter)) );
      }
    }

    // Send them back
    return $clean;
  }

  function formatData($data, $config, $region, $category) {

    // Fix slug
    $data['slug'] = ( $data['slug'] === '' ? slugify($data['name']) : strtolower($data['slug']) );

    // Fix pricing
    $data['price']      = preg_replace('/[^0-9]/', '', $data['price']);
    $data['sale_price'] = preg_replace('/[^0-9]/', '', $data['sale_price']);

    // Fix price and sale price
    if ( $data['price'] < $data['sale_price'] ) {
      $price = $data['price'];
      $data['price'] = $data['sale_price'];
      $data['sale_price'] = $price;
    }

    // Fix images
    if ( ! empty($data['images']) ) {
      foreach ( $data['images'] as &$img ) {
        $img = preg_replace('/_([A-Z]{2})[0-9,]{2,5}_/', '_${1}1000_', $img);
      }
    }

    // Fix stock and status
    if ( $data['stock_status'] === 'In stock' ) {
      $data['stock'] = ['level' => rand(10, 1000), 'availability' => 'in-stock'];
      unset($data['stock_status']);
    } else {
      $data['stock'] = ['level' => 0, 'availability' => 'out-stock'];
      unset($data['stock_status']);
    }

    // Assign default category
    if ( $data['category'] === '' ) {
      $data['category'] = $config['category'][$category];
    }

    // Trim all the things
    array_walk_recursive($data, function(&$string) {
      $string = trim($string);
    });

    return $data;
  }

  function slugify($text)
  {
    // replace non letter or digits by -
    $text = preg_replace('~[^\pL\d]+~u', '-', $text);

    // transliterate
    $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);

    // remove unwanted characters
    $text = preg_replace('~[^-\w]+~', '', $text);

    // trim
    $text = trim($text, '-');

    // remove duplicate -
    $text = preg_replace('~-+~', '-', $text);

    // lowercase
    $text = strtolower($text);

    if (empty($text)) {
      return 'n-a';
    }

    return $text;
  }
