<?php

/*
|--------------------------------------------------------------------------
| Diva lens collections
|--------------------------------------------------------------------------
| Taken from the Diva catalogue card. A product joins a collection when one
| of the "-" separated parts of its SKU matches a name below.
|   DIVA-VAR-WOODY        -> WOODY  -> diva
|   DIVA-VAR-SEOUL-STEEL  -> STEEL  -> chic_diva
|   DIVA-VAR-NONSAN-NAVY  -> NAVY   -> miss_diva
| Order matters: collections are shown in this order in the response.
*/

return [

    'collections' => [
        'her_diva' => [
            'title' => 'Her Diva',
            'title_ar' => 'هير ديفا',
            'names' => ['LILI', 'EITAN', 'BETON', 'GALA'],
        ],
        'chic_diva' => [
            'title' => 'Chic Diva',
            'title_ar' => 'شيك ديفا',
            'names' => ['LAMIR', 'STEEL', 'CEDAR', 'CREAM'],
        ],
        'miss_diva' => [
            'title' => 'Miss Diva',
            'title_ar' => 'ميس ديفا',
            'names' => ['AMBER', 'LAVA', 'SAND', 'NAVY'],
        ],
        'diva' => [
            'title' => 'Diva',
            'title_ar' => 'ديفا',
            'names' => [
                'TRUFFLE', 'AMANDE', 'WOODY', 'NUT',
                'TOFFEE', 'LATTE', 'CLAY', 'MOON',
                'GRIS', 'MIDNIGHT', 'OLIVIAN', 'IVORY',
            ],
        ],
    ],

    // Products whose SKU matches none of the names above (e.g. LIN, SILK)
    'other' => [
        'title' => 'Other',
        'title_ar' => 'أخرى',
    ],

    // "Products of the Month": only discounted products, picked randomly
    // but the same for the whole month.
    // discount_percentage: show only products with this exact discount (e.g. 20).
    // Set DIVA_MONTH_DISCOUNT= (empty) to include any discounted product.
    'products_of_the_month' => [
        'title' => 'Products of the Month',
        'title_ar' => 'منتجات الشهر',
        'limit' => (int) env('DIVA_MONTH_PRODUCTS_LIMIT', 6),
        'discount_percentage' => env('DIVA_MONTH_DISCOUNT', 20),
    ],

    // "Most Popular": ordered by how many users added the product to their wishlist
    'most_popular' => [
        'title' => 'Most Popular',
        'title_ar' => 'الأكثر شعبية',
        'limit' => (int) env('DIVA_POPULAR_PRODUCTS_LIMIT', 8),
    ],
];
