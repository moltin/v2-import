<?php

  return [

    '.co.uk' => [
      'type'               => 'currency',
      'code'               => 'GBP',
      'exchange_rate'      => 1,
      'format'             => '£{price}',
      'decimal_point'      => '.',
      'thousand_separator' => ',',
      'decimal_places'     => 2,
      'default'            => true,
      'enabled'            => true
    ],

    '.com' => [
      'type'               => 'currency',
      'code'               => 'USD',
      'exchange_rate'      => 1,
      'format'             => '${price}',
      'decimal_point'      => '.',
      'thousand_separator' => ',',
      'decimal_places'     => 2,
      'default'            => true,
      'enabled'            => true
    ],

    '.de' => [
      'type'               => 'currency',
      'code'               => 'EUR',
      'exchange_rate'      => 1,
      'format'             => 'EUR {price}',
      'decimal_point'      => ',',
      'thousand_separator' => ' ',
      'decimal_places'     => 2,
      'default'            => true,
      'enabled'            => true
    ]

  ];
