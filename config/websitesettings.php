<?php

return [
    'navigationSections' => [
        ["name" => "Dashboard", "href" => 'dashboard', "current" => true],
        ["name" => "Messages", "href" => "#", "current" => false]
    ],
    'userNavigationSections' => [
        ["name" => "Your Profile", "href" => 'dashboard'],
        ["name" => "Dashboard", "href" => 'profile.edit'],
        ["name" => "Settings", "href" => "#"],
        ["name" => "Messages", "href" => "#"],
        ["name" => "Sign out", "href" => "#"]
    ]
];
