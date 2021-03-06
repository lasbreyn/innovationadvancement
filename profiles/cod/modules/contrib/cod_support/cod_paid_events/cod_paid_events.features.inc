<?php
/**
 * @file
 * cod_paid_events.features.inc
 */

/**
 * Implements hook_commerce_product_default_types().
 */
function cod_paid_events_commerce_product_default_types() {
  $items = array(
    'cod_paid_event' => array(
      'type' => 'cod_paid_event',
      'name' => 'Paid Event',
      'description' => 'A paid event product',
      'help' => '',
      'revision' => 1,
    ),
  );
  return $items;
}

/**
 * Implements hook_views_api().
 */
function cod_paid_events_views_api($module = NULL, $api = NULL) {
  return array("api" => "3.0");
}
