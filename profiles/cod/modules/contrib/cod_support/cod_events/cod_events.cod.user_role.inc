<?php
/**
 * @file
 * cod_events.features.user_role.inc
 */

/**
 * Implements hook_user_initial_roles().
 */
function cod_events_user_initial_roles() {
  $roles = array();

  // Exported role: attendee manager.
  $roles['attendee manager'] = array(
    'name' => 'attendee manager',
    'weight' => 4,
  );

  return $roles;
}
