<?php

  // Error handling
  error_reporting(E_ALL);
  ini_set('display_errors', '1');

  // Session
  session_start();

  // Requirements
  require 'library/moltin/storage.php';
  require 'library/moltin/request.php';
  require 'library/moltin/authenticate.php';
  require 'library/moltin/moltin.php';
  require 'library/colors.php';
  require 'library/helper.php';

  // Allowed config
  $config = json_decode(file_get_contents('config.json'), true);

  // Start color class
  $color = new Colors();

  // Variables
  $dataDir = 'data/';
  $tmpDir  = 'tmp/';
  $cache   = [
    'brands'     => [],
    'categories' => [],
    'products'   => []
  ];

  // Options
  $confirm   = 'n';
  $accessKey = '';
  $secretKey = '';
  $file      = '';

  while ( true ) {

    // Confirm deletion
    clear();
    echo $color->parseString("{red:#############\n## WARNING ##\n#############}\n\n");
    echo $color->parseString("If you continue this script will {cyan:DELETE} any existing:\n  - {red:products}\n  - {red:files}\n  - {red:categories}\n  - {red:brands}\n");
    echo $color->parseString("\nPlease confirm you want to continue ({cyan:y} or {cyan:n}): ");

    // Read user choice
    $confirm = trim(fgets(STDIN));

    // Check confirmation
    if ( strtolower($confirm) !== 'y' ) {
      exit($color->parseString("Exiting...\n"));
    }
    
    // Ask for an import file
    $files = fileChoice($color, $dataDir);

    // Read user choice
    $file = ( 0 + trim(fgets(STDIN)) );
    $file = ( isset($files[( $file - 1 )]) ? $files[( $file - 1 )] : $files[0] );

    // Ask for the users public token
    clear();
    echo $color->parseString("Enter your moltin {cyan:access key}: ");

    // Read user input
    $accessKey = trim(fgets(STDIN));

    // Ask for the users public token
    clear();
    echo $color->parseString("Enter your moltin {cyan:secret key}: ");

    // Read user input
    $secretKey = trim(fgets(STDIN));

    break;
  }

  // File
  $data = json_decode(file_get_contents($dataDir . $file), true);

  // Ensure tmp exists
  if ( ! file_exists($tmpDir) ) {
  	mkdir($tmpDir, 0777);
  }

  // Variables
  $start = microtime(true);

  // Create moltin instance
  $moltin = new Moltin\Moltin(new Moltin\Storage, new Moltin\Request);

  // Authenticate
  try { 

    $auth = $moltin->authenticate(new Moltin\Authenticate, [
      'client_id'     => $accessKey,
      'client_secret' => $secretKey
    ]);

    // Check for auth
    if ( ! $auth ) {
      exit($color->parseString("Authentication failed, please try again.\n"));
    }

  } catch ( Exception $e ) {
    exit($color->parseString("Authentication failed, please try again.\n"));
  }

  ##################
  ## CLEAR IT OUT ##
  ##################

  // Files
  /*clear();
  echo $color->parseString("Deleting {cyan:files}...\n");
  $files = $moltin->get('files');
  if ( count($files['data']) > 0 ) {
    foreach ( $files['data'] as $entry ) {
      
      echo $color->parseString("  Deleting {cyan:{$entry['file_name']}}");
      $response = $moltin->delete('files/' . $entry['id']);

      echo "\033[" . strlen($entry['file_name']) . "D";
      echo $color->parseString('{' . ( ! isset($response['errors']) ? 'green' : 'red' ) . ':' . $entry['file_name'] . "}\n");
    }
  }*/

  // Brands
  clear();
  echo $color->parseString("Deleting {cyan:brands}...\n");
  $brands = $moltin->get('brands');
  if ( $brands['meta']['counts']['matching_resource_count'] > 0 ) {
    foreach ( $brands['data'] as $entry ) {
      
      echo $color->parseString("  Deleting {cyan:{$entry['name']}}");
      $response = $moltin->delete('brands/' . $entry['id']);

      echo "\033[" . strlen($entry['name']) . "D";
      echo $color->parseString('{' . ( ! isset($response['errors']) ? 'green' : 'red' ) . ':' . $entry['name'] . "}\n");
    }
  }

  // Categories
  clear();
  echo $color->parseString("Deleting {cyan:categories}...\n");
  $categories = $moltin->get('categories');
  if ( $categories['meta']['counts']['matching_resource_count'] > 0 ) {
    foreach ( $categories['data'] as $entry ) {
      
      echo $color->parseString("  Deleting {green:{$entry['name']}}");
      $response = $moltin->delete('categories/' . $entry['id']);

      echo "\033[" . strlen($entry['name']) . "D";
      echo $color->parseString('{' . ( ! isset($response['errors']) ? 'green' : 'red' ) . ':' . $entry['name'] . "}\n");
    }
  }

  // Products
  clear();
  echo $color->parseString("Deleting {cyan:products}...\n");
  $products = $moltin->get('products');
  if ( $products['meta']['counts']['matching_resource_count'] > 0 ) {
    foreach ( $products['data'] as $entry ) {
      
      echo $color->parseString("  Deleting {green:{$entry['name']}}");
      $response = $moltin->delete('products/' . $entry['id']);

      echo "\033[" . strlen($entry['name']) . "D";
      echo $color->parseString('{' . ( ! isset($response['errors']) ? 'green' : 'red' ) . ':' . $entry['name'] . "}\n");
    }
  }

  ############
  ## BRANDS ##
  ############

  clear();
  echo $color->parseString("Importing {cyan:brands}...\n");

  // Loop the brands we need to create
  foreach ( $data['brands'] as $name ) {

    // Try to find brand
    $response = $moltin->get('brands', ['name' => $name]);

    // Check for existing brands
    if ( $response['meta']['counts']['matching_resource_count'] > 0 ) {

      // Loop returned resources because we can't filter yet
      foreach ( $response['data'] as $entry ) {
        if ( $entry['name'] === $name ) {

          echo $color->parseString("  Found {green:{$entry['name']}}, adding to cache\n");

          // Add to cache
          $cache['brands'][$entry['name']] = $entry['id'];

          // Skip creation
          continue 2;
        }
      }
    }

    // Output
    echo $color->parseString("  Creating {green:{$name}}");

    // Doesn't exist, create it
    $response = $moltin->post('brands', [
      'type' => 'brand',
      'name' => $name,
      'slug' => slugify($name)
    ]);

    // Check result
    echo "\033[" . strlen($name) . "D";
    echo $color->parseString('{' . ( ! isset($response['errors']) ? 'green' : 'red' ) . ':' . $name . "}\n");

    // Add to cache
    if ( ! isset($response['errors']) ) {
      $cache['brands'][$response['data']['name']] = $response['data']['id'];
    }
  }

  ################
  ## CATEGORIES ##
  ################

  clear();
  echo $color->parseString("Importing {cyan:categories}...\n");

  // Variables
  $categoryParent = null;

  // Loop the categories we need to create
  foreach ( $data['categories'] as $name ) {

    // Try to find brand
    $response = $moltin->get('categories', ['name' => $name]);

    // Check for existing categories
    if ( $response['meta']['counts']['matching_resource_count'] > 0 ) {

      // Loop returned resources because we can't filter yet
      foreach ( $response['data'] as $entry ) {
        if ( $entry['name'] === $name ) {

          echo $color->parseString("  Found {green:{$entry['name']}}, adding to cache\n");

          // Add to cache
          $cache['categories'][$entry['name']] = $entry['id'];

          // Set parent if required
          if ( $categoryParent === null ) {
            $categoryParent = $entry['id'];
          }

          // Skip creation
          continue 2;
        }
      }
    }

    // Output
    echo $color->parseString("  Creating {green:{$name}}");

    // Doesn't exist, create it
    $response = $moltin->post('categories', [
      'type' => 'category',
      'name' => $name,
      'slug' => slugify($name)
    ]);

    // Check result
    echo "\033[" . strlen($name) . "D";
    echo $color->parseString('{' . ( ! isset($response['errors']) ? 'green' : 'red' ) . ':' . $name . "}\n");

    // Skip rest of loop
    if ( isset($response['errors']) ) {
      continue;
    }

    // Does this category have a parent
    if ( $categoryParent !== null ) {

      // Output
      echo $color->parseString("  Adding {cyan:{$name}} to it's parent {green:{$categoryParent}}.\n");

      // Add to parent
      $response = $moltin->post('categories/' . $response['data']['id'] . '/relationships/categories', [[
        'type' => 'category',
        'id'   => $categoryParent
      ]]);

    // Set parent
    } else {
      $categoryParent = $response['data']['id'];
    }

    // Add to cache
    $cache['categories'][$response['data']['name']] = $response['data']['id'];
  }

  ##############
  ## PRODUCTS ##
  ##############

  clear();
  echo $color->parseString("Importing {cyan:products}...\n");

  // Loop the products we need to create
  foreach ( $data['products'] as $item ) {

    // Output
    echo $color->parseString("  Creating {green:{$item['name']}}");

    // Create product
    $response = $moltin->post('products', [
      'type'           => 'product',
      'sku'            => $item['sku'],
      'name'           => $item['name'],
      'slug'           => $item['slug'],
      'manage_stock'   => true,
      'description'    => str_replace('<li></li>', '', '<ul><li>' . implode('</li><li>', $item['description']) . '</li></ul>'),
      'status'         => 'live',
      'commodity_type' => 'physical',
      'stock'          => $item['stock']['level'],
      'price'          => [
        [
          'amount'       => $item['price'],
          'currency'     => $data['currency']['code'],
          'includes_tax' => true
        ]
      ]
    ]);

    // Check result
    echo "\033[" . strlen($item['name']) . "D";
    echo $color->parseString('{' . ( ! isset($response['errors']) ? 'green' : 'red' ) . ':' . $item['name'] . "}\n");

    // Skip rest of loop
    if ( isset($response['errors']) ) {
      continue;
    }

    // Assign id
    $id = $response['data']['id'];

    // Add brand relationship
    if ( $item['brand'] !== '' and array_key_exists($item['brand'], $cache['brands']) ) {
        
      // Output
      echo $color->parseString("  Adding {cyan:{$item['brand']}} as a relationship to {green:{$id}}\n");

      $moltin->post('products/' . $id . '/relationships/brands', [[
        'type' => 'brand',
        'id'   => $cache['brands'][$item['brand']]
      ]]);
    }

    // Add category relationship
    if ( $item['category'] !== '' and array_key_exists($item['category'], $cache['categories']) ) {
      
      echo $color->parseString("  Adding {cyan:{$item['category']}} as a relationship to {green:{$id}}\n");

      $moltin->post('products/' . $id . '/relationships/categories', [[
        'type' => 'category',
        'id'   => $cache['categories'][$item['category']]
      ]]);
    }

    // Handle images
    if ( ! empty($item['images']) ) {
      foreach ( $item['images'] as $image ) {
    
        // Variables
        $parts = explode('/', $image);
        $file  = end($parts);

        // Output
        echo $color->parseString("  Uploading {cyan:{$file}}\n");

        // Download it
        file_put_contents($tmpDir . $file, file_get_contents($image));
    
        // Upload it
        $response = $moltin->post('files', ['public' => 'true'], ['file' => ['tmp_name' => $tmpDir . $file, 'type' => 'image/jpeg', 'name' => $file]]);

        // Delete it
        unlink($tmpDir . $file);
    
        // Link file to product
        $moltin->post('products/' . $id . '/relationships/files', [[
          'type' => 'file',
          'id'   => $response['data']['id']
        ]]);

        echo $color->parseString("  Adding {cyan:{$file}} as a relationship to {green:{$id}}\n");
      }
    }
  }

  // DEBUG
  $time = (microtime(true) - $start);
  echo $color->parseString("Completed in {green:{$time}\n");
