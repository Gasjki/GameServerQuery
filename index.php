<?php

use GameServerQuery\Filter\Type\GameSpy3\MinecraftHostnameColorStripFilter;
use GameServerQuery\GameServerQuery;
use GameServerQuery\Protocol\Games\GameSpy3\MinecraftProtocol;
use GameServerQuery\Result;
use GameServerQuery\Server;

include_once './vendor/autoload.php';
include_once './src/GameServerQuery/autoloader.php';

$gameServerQuery = (new GameServerQuery())
    ->server(
        new Server(MinecraftProtocol::class, '168.119.77.154', 2387)
    )
    ->filter(MinecraftHostnameColorStripFilter::class, [
        // Filter sections.
        // If 'sections' is an empty array, then the filter will be skipped.
        'sections'  => [
            // [] = all keys, ['hostname', 'map', ...] - applies only to these keys, null = don't filter this section
            Result::GENERAL_CATEGORY => [Result::GENERAL_HOSTNAME_SUBCATEGORY, Result::GENERAL_MAP_SUBCATEGORY],
            Result::PLAYERS_CATEGORY => [Result::PLAYERS_NAME_SUBCATEGORY],
            Result::RULES_CATEGORY   => [],
        ],
    ]);

dd($gameServerQuery->process());

