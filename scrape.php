<?php

  // Errors
  error_reporting(E_ALL);
  ini_set('display_errors', '1');

  // Requirements
  require 'library/colors.php';
  require 'library/helper.php';

  // Allowed config
  $config = json_decode(file_get_contents('config.json'), true);

  // Start color class
  $color = new Colors();

  // Defaults
  $category = $config['categoryDefault'];
  $region   = $config['region'][$config['regionDefault']];
  $count    = $config['countDefault'];

  while ( true ) {
    
    // Ask for a region
    regionChoice($color, $config['region'], $config['regionDefault']);

    // Read user choice
    $region = trim(fgets(STDIN));
    $region = ( $region !== null && array_key_exists($region, $config['region']) ? $config['region'][$region] : $config['region'][$config['regionDefault']] );

    // Ask for category
    categoryChoice($color, $config['category'], $config['categoryDefault']);

    // Read user choice
    $category = trim(fgets(STDIN));
    $category = ( $category !== null && array_key_exists($category, $config['category']) ? $category : $config['categoryDefault'] );

    // Ask for number of products
    countChoice($color, $config['countDefault']);

    // Read user choice
    $count = ( 0 + trim(fgets(STDIN)) );
    $count = ( $count <= 0 ? $config['countDefault'] : $count );

    break;
  }

  // Clear terminal and start
  clear();

  // Search configuration
  $domain   = 'https://www.amazon' . $region;
  $uri      = '/gp/bestsellers/' . $category . '?pg=';
  $complete = 0;
  $perpage  = 20;
  $pages    = 5;

  // Item storage
  $brands     = [];
  $categories = [$config['category'][$category]];
  $currencies = require 'library/currencies.php';
  $products   = [];

  // File storage
  $dir  = 'data/';
  $file = $dir . 'amazon' . $region . '-' . $category . '.json';

  // Ensure data exists
  if ( ! file_exists($dir) ) {
    mkdir($dir, 0777);
  }

  // Define our regex patterns
  $patterns = [
    '<input type="hidden" id="ASIN" name="ASIN" value="(?P<sku>.+?)">',
    'ue_url=(?:.+?)?\/(?P<slug>.+?)\/dp\/',
    '<span id="productTitle" class="a-size-large">(?P<name>.+?)<\/span>',
    '<span id="priceblock_[a-z]+" class=".+?">(?P<price>.+?)<\/span>',
    '(?:<td class="a-span12 a-color-secondary a-size-base">.+?<span class="a-text-strike"> (?P<sale_price>.+?)<\/span>)',
    '<div id="availability" class=".+?">.+?<span class=".+?">(?P<stock_status>.+?)\..+?<\/span>',
    '<span class=\'cat-link\'>(?P<category>.+?)<\/span>',
    '<a id="brand" class=".+?" href=".+?">(?P<brand>[^<>]+)<\/a>',
    '<span class="a-list-item">(?P<description>[^<>]+)<\/span>',
    'src="(?P<images>.+?\._[A-Z]{2}[0-9,]{2,5}_\.[a-z0-9]{3,4})"'
  ];

  // DEBUG
  echo $color->parseString('Scraping {green:' . $config['category'][$category] . '} from {cyan:Amazon' . $region . "}\n");

  // Loop through pages
  for ( $page = 1; $page <= $pages; $page++ ) {

    // Variables
    $matches = [[]];
    $attempt = 0;

    // Keep trying
    do {

      // Get the page
      $content = request($domain . $uri . $page);

      // Match the product listings
      // preg_match_all('/zg_itemImageImmersion"><a  href="[\n]+ (.+?)\n">/mi', $content, $matches);
      preg_match_all('/<div class="zg_title"><a[ ]+href="(.+?)">.+?<\/a>/mis', $content, $matches);
      // preg_match_all('/<a class="a-link-normal" href="(.+?)">/mi', $content, $matches);

      // Increae attempt
      $attempt += 1;

    } while ( $attempt < 10 && empty($matches[0]) );

    // Attempt limit exceeded
    if ( $attempt >= 10 ) {
      exit($color->parseString('Failed to retrieve page {red:' . $page . "}, exiting\n"));
    }

    // DEBUG
    echo $color->parseString('Page {green:' . $page . '} ({red:retrieved in ' . $attempt . ' attempt' . ( $attempt != 1 ? 's' : '' ) . "}):\n");

    // Loop through matches
    for ( $item = 0; $item < ( $perpage < count($matches[1]) ? $perpage : count($matches[1]) ); $item++ ) {

      // Go get the product
      $product = request(trim($matches[1][$item]));

      // Clean up the raw HTML
      $product = trim(preg_replace('/\s\s+/', ' ', $product));

      // Pull out the information
      preg_match_all('/' . implode('|', $patterns) . '/mi', $product, $fields);

      // Clean the results
      $fields = cleanUp($fields);

      // Format the data
      $fields = formatData($fields, $config, $region, $category);

      // Skip empty items
      if ( $fields['name'] === '' ) {
        echo $color->parseString('  Skipping {red:' . trim($matches[1][$item]) . "}\n");
        continue;
      }

      // DEBUG
      echo $color->parseString('  Processed {green:' . htmlspecialchars_decode($fields['name']) . "}\n");

      // Push into products
      $products[] = $fields;

      // Add brand
      if ( $fields['brand'] !== '' and ! in_array($fields['brand'], $brands) ) {
        $brands[] = $fields['brand'];
      }

      // Add category
      if ( $fields['category'] !== '' and ! in_array($fields['category'], $categories) ) {
        $categories[] = $fields['category'];
      }

      // Increment completed items
      $complete++;

      // Keep within limit
      if ( $count < 100 && $complete >= $count ) {
        echo $color->parseString('Specified limit of {cyan:' . $count . "} reached, ending run\n");
        break 2;
      }
    }
  }

  // Create data and fix any utf-8 errors
  $data = array_to_utf8([
    'brands'     => $brands,
    'categories' => $categories,
    'currency'   => $currencies[$region],
    'products'   => $products
  ]);

  // Create json data
  $json = json_encode($data, JSON_PRETTY_PRINT);

  // Write to file
  $write = file_put_contents($file, $json);

  // Write failed
  if ( $write === false || $write <= 0 ) {
    exit($color->parseString('Failed to write {red:' . $file . "}\n"));
  }

  // DEBUG
  echo "\n";
  echo $color->parseString("{green:Complete}, results saved to \"{cyan:{$file}}\"\n");
