<?php
/*
 * shorthand the path the the groups addon for later usage
 */
$app->path('groups', __DIR__);

// Auth Api extension
$this->module('cockpit')->extend([

    'getGroups' => function() {

        $groups__all_fields = $this->app->storage->find('cockpit/groups', ['fields' => ['group' => true, '_id' => false]]);

        $groups = [];
        foreach ($groups__all_fields as $i => $row) {
            $groups[] = $row['group'];
        }

        $groups = array_merge(['admin'], $groups);

        return array_unique($groups);

    }
]);

/*
 * extend groups/acls by db data
 */
$groups_data = $app->storage->find('cockpit/groups');
foreach ($groups_data as $i => $row) {
    $isSuperAdmin = isset($row['admin']) ? $row['admin'] : false;
    $vars = isset($row['vars']) ? $row['vars'] : [];

    $group_name = $row['group'];

    // add the group its self
    $app('acl')->addGroup($group_name, $isSuperAdmin, $vars);

    // add the assigned acls for the group
    $acls_filtered = [
        'cockpit' => @$row['cockpit'],
        'collections' => @$row['collections'],
        'regions' => @$row['regions'],
        'singletons' => @$row['singletons'],
        'forms' => @$row['forms']
    ];
    if (!$isSuperAdmin && is_array($acls_filtered)) {
        foreach (array_filter($acls_filtered) as $resource => $actions) {
            foreach ($actions as $action => $allow) {
                if ($allow) {
                    $app('acl')->allow($group_name, $resource, $action);
                }
            }
        }
    }
}
// ACL
$actions = array_merge($app('acl')->getResources()['cockpit'],['groups']);
$app('acl')->addResource('cockpit', $actions);

// ADMIN
if (COCKPIT_ADMIN && !COCKPIT_API_REQUEST) {
    include_once(__DIR__ . '/admin.php');
}