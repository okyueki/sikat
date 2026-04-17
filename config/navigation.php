<?php

/**
 * Sumber tunggal menu Inventaris (sidebar + modal menu).
 *
 * - modal_category: label grup di modal pencarian
 * - group: ops = operasional, master = referensi (untuk sidebar)
 * - icon: nama view di resources/views/navigation/icons/{icon}.blade.php
 * - access_ability: null = semua user login; string = nama ability di config/access.php (selaras Gate & modal)
 */
return [
    'inventaris' => [
        'sidebar_parent_route_patterns' => [
            'inventaris-barang.*',
            'inventaris.*',
            'jenis-inventaris.*',
            'kategori-inventaris.*',
            'merk-inventaris.*',
            'produsen-inventaris.*',
            'perbaikan.*',
        ],
        'items' => [
            [
                'title' => 'Daftar inventaris',
                'route' => 'inventaris.index',
                'group' => 'ops',
                'modal_category' => 'Inventaris',
                'icon' => 'inv-list',
                'sidebar_active_routes' => [
                    'inventaris.index',
                    'inventaris.create',
                    'inventaris.edit',
                    'inventaris.show',
                    'inventaris.update',
                    'inventaris.destroy',
                    'inventaris.barcode',
                    'inventaris.detail',
                ],
                'access_ability' => 'inventaris.access',
            ],
            [
                'title' => 'Visualisasi data',
                'route' => 'inventaris.visualisasi',
                'group' => 'ops',
                'modal_category' => 'Inventaris',
                'icon' => 'inv-chart',
                'sidebar_active_routes' => ['inventaris.visualisasi*'],
                'access_ability' => 'inventaris.access',
            ],
            [
                'title' => 'Perbaikan inventaris',
                'route' => 'perbaikan.index',
                'group' => 'ops',
                'modal_category' => 'Inventaris',
                'icon' => 'inv-repair',
                'sidebar_active_routes' => [
                    'perbaikan.index',
                    'perbaikan.create',
                    'perbaikan.store',
                    'perbaikan.edit',
                    'perbaikan.update',
                    'perbaikan.destroy',
                    'perbaikan.start',
                    'perbaikan.start.store',
                ],
                'access_ability' => 'inventaris.access',
            ],
            [
                'title' => 'Master barang',
                'route' => 'inventaris-barang.index',
                'group' => 'master',
                'modal_category' => 'Inventaris · Master data',
                'icon' => 'inv-box',
                'sidebar_active_routes' => ['inventaris-barang.*'],
                'access_ability' => 'inventaris.access',
            ],
            [
                'title' => 'Jenis barang',
                'route' => 'jenis-inventaris.index',
                'group' => 'master',
                'modal_category' => 'Inventaris · Master data',
                'icon' => 'inv-tag',
                'sidebar_active_routes' => ['jenis-inventaris.*'],
                'access_ability' => 'inventaris.access',
            ],
            [
                'title' => 'Kategori barang',
                'route' => 'kategori-inventaris.index',
                'group' => 'master',
                'modal_category' => 'Inventaris · Master data',
                'icon' => 'inv-layers',
                'sidebar_active_routes' => ['kategori-inventaris.*'],
                'access_ability' => 'inventaris.access',
            ],
            [
                'title' => 'Merk',
                'route' => 'merk-inventaris.index',
                'group' => 'master',
                'modal_category' => 'Inventaris · Master data',
                'icon' => 'inv-bookmark',
                'sidebar_active_routes' => ['merk-inventaris.*'],
                'access_ability' => 'inventaris.access',
            ],
            [
                'title' => 'Produsen',
                'route' => 'produsen-inventaris.index',
                'group' => 'master',
                'modal_category' => 'Inventaris · Master data',
                'icon' => 'inv-factory',
                'sidebar_active_routes' => ['produsen-inventaris.*'],
                'access_ability' => 'inventaris.access',
            ],
        ],
    ],
];
