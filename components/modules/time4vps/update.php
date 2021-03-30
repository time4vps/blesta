<?php

require 'defines.php';
$init = ROOTPATH . '/lib/init.php';
include $init;
use Blesta\Core\Util\Validate\Server;
use Time4vps\API\Product;
use Time4vps\API\Script;
use Time4vps\Exceptions\APIException;
use Time4vps\Exceptions\InvalidTaskException;
use Time4vps\Exceptions\AuthException;
use Time4vps\Base\Endpoint;

require_once dirname(__FILE__) . '/vendor/autoload.php';
require_once dirname(__FILE__) . '/includes/helpers.php';
require_once dirname(__FILE__) . '/includes/server.php';
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
// fetch time4vps module if any exists
$r = new Record();
// $time4vps_module = $r->query('SELECT * FROM modules where modules.`name` = "time4vps"')->fetch();
$time4vps_module = $r->query('SELECT * FROM modules LEFT JOIN module_rows on modules.id = module_rows.module_id where modules.`name` = "time4vps"')->fetch();
if ($time4vps_module && isset($time4vps_module->id)) {
    // check if Package Group Exists
    $package_group_names = $r->query('SELECT * FROM package_group_names WHERE package_group_names.`name` = "time4vps_Test_Products"')->fetch();
    // if exists fetch its package_id
    $max_group_id = isset($package_group_names->package_group_id) && $package_group_names->package_group_id > 0 ? $package_group_names->package_group_id : 0;
    // fetch current company
    $company_id = isset($time4vps_module->company->id) && $time4vps_module->company->id ? $time4vps_module->company->id : 1;
    $group_vars = [
        'names' => [
            [
                'lang' => "en_us",
                'name' => "time4vps_Test_Products"
            ]
        ],
        'descriptions' => [
            [
                'lang' => 'en_us',
                'description' => 'This group is created to hold test products for Time4vps'
            ]
        ],
        'allow_upgrades' => 1,
        'type' => "standard",
        'save' => "Create Group",
        'company_id' => $company_id
    ];
    $group_flag = "existing";
    if ($max_group_id == 0) {
        // if there is no pkg group for time4vps then create a new one
        $parent = new stdClass();
        $group_flag = "new";
        $models = ['PackageGroups'];
        Loader::loadModels($parent, $models);
        $package_group_id = $parent->PackageGroups->add($group_vars);
        $max_group_id = $package_group_id;
    }
    $parent = new stdClass();
    $models = ['ModuleManager'];
    Loader::loadModels($parent, $models);
    $row = $parent->ModuleManager->getRow($time4vps_module->id);
    // load module credentials to fetch products from remote server
        $module_meta = $row->meta;
        $api = [
            'serverhttpprefix' => $module_meta->use_ssl === true ? 'https' : 'http',
            'serverhostname' => $module_meta->host_name,
            'serverusername' => $module_meta->user_name,
            'serverpassword' => $module_meta->password
        ];
        time4vps_InitAPI($api); // initialize time4vps server
        $products = (new Product())->getAvailableVPS(); // fetch all available products to store on local database for listing
        if ($products) {
            $package_count = 0;
            Loader::loadModels($parent, ['Packages']);
            foreach ($products as $key => $product) {
                //  vars contain complete Package/product Object that blesta construct to store into database
                $vars = [
                    "names" => [
                        [
                            "lang" => 'en_us',
                            "name" => $product['name']
                        ]
                    ],
                    "descriptions" => [
                        [
                            "lang" => "en_us",
                            "html" => $product['description'],
                            "text" => ""
                        ]
                    ],
                    "status" => "active",
                    "qty_unlimited" => true,
                    "qty" => null,
                    "client_qty_unlimited" => true,
                    "client_qty" => null,
                    "upgrades_use_renewal" => 1,
                    "module_id" => $time4vps_module->module_id,
                    "module_row" => $row->id,
                    "meta" => [
                        "product" => $product['id'],
                        "init" => "",
                        "os_list" => "",
                        "configoption4" => "",
                        "Component_map" => ""
                    ],
                    "pricing" => [
                        [
                            "term" => 1,
                            "period" => isset($product['prices']['a']) && !empty($product['prices']['a']) ? "year" : "month",
                            "currency" => "USD",
                            "price" => isset($product['prices']['a']) && !empty($product['prices']['a']) ? $product['prices']['a'] : $product['prices']['m'],
                            "price_enable_renews" => 1,
                            "price_renews" => 50,
                            "setup_fee" => 0,
                            "cancel_fee" => 0
                        ]
                    ],
                    "email_content" => [
                        [
                            "lang" => "en_us",
                            "html" => "",
                            "text" => ""
                        ]
                    ],
                    "select_group_type" => $group_flag,
                    "group_names" => [
                        [
                            "lang" => "en_us",
                            "name" => "Time4vps_Test_Products"
                        ]
                    ],
                    "groups" => [
                        $max_group_id
                    ],
                    "save" => "Create Package",
                    "company_id" => $company_id,
                    "taxable" => 0,
                    "single_term" => 0
                ];
                // once package/product object is finalized we will call blesta Add Pacakge function to store Package into our server
                $package_id = $parent->Packages->add($vars);
                if ($package_id) {
                    $package_count++;
                }
            }
        }
        echo $package_count . ' products/packages Added Successfully';
        echo '<br><b> Do not run this URL again & again, it will create duplicate entries otherwise </b><br>';
} else {
    echo 'No module configurations found for Time4vps. Please Configure it first to load products';
}
return;
