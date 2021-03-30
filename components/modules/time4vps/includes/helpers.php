<?php

/** @noinspection PhpUndefinedClassInspection */

/** @noinspection PhpUndefinedNamespaceInspection */
/** @noinspection PhpUndefinedFunctionInspection */

use Time4vps\API\Server;
use Time4vps\Base\Endpoint;

/**
 * Time4vps API Initialisation function
 *
 * @param $params
 */
function time4vps_InitAPI($params)
{
    $debug = new Time4vps\Base\Debug();
    $endpoint = "{$params['serverhttpprefix']}://{$params['serverhostname']}/api/";
    Endpoint::BaseURL($endpoint);
    Endpoint::Auth($params['serverusername'], $params['serverpassword']);
    Endpoint::DebugFunction(function ($args, $request, $response) use ($debug) {
        $id = hash('crc32', microtime(true));
        $benchmark = $debug->benchmark();
        // $this->log($args[1], array('Request' => $request,'Response' => (string) $response , 'time taken' => $benchmark), 'input', true);
    });
}

/**
 * Get Time4vps server ID from params
 *
 * @param $params
 * @return Server|false External server ID or false
 * @throws Exception
 */
function time4vps_ExtractServer($params)
{
    if (isset($params['service_id'])) {
        $r = new Record();
        $server = $r->query("SELECT " . TIME4VPS_TABLE . ".* FROM " . TIME4VPS_TABLE . " WHERE `service_id` = ? ", $params['service_id'])->fetch();
    } else {
        $server = false;
    }
    if ($server) {
        /** @var Server $s */
        return new Server($server->external_id);
    }
    throw new Exception('Unable to find related server');
}

/**
 * Redirect user to URL
 *
 * @param $url
 */
function time4vps_Redirect($url)
{
    header("Location: {$url}");
    exit();
}

/**
 * Extract billing cycle
 *
 * @param $cycle
 * @return string
 */
function time4vps_BillingCycle($cycle)
{
    switch ($cycle) {
        case 'month':
        case 'Month':
            return 'm';
        case 'year':
        case 'Year':
            return 'a';
    }
    return null;
}

/**
 * Extract package options from params
 *
 * @param $params
 * @param bool $skip_disabled
 * @return array
 */
function time4vps_ExtractComponents($params, $skip_disabled = true)
{
    $custom = [];
    $map = json_decode($params['configoption5'], true);
    if ($params['configoptions'] && $map['components']) {
        foreach ($params['configoptions'] as $configoption => $enabled) {
            if (!$enabled && $skip_disabled) {
                continue;
            }
            $r = new Record();
            $option = $r->query("select * from 'tblproductconfigoptions'
            join tblproductconfiggroups on tblproductconfiggroups.id = tblproductconfiggroups.gid
            join tblproductconfiglinks on tblproductconfiglinks.gid = tblproductconfiggroups.id
            where 'tblproductconfiglinks.pid' = ?
            and tblproductconfigoptions.optionname = ?", $params['pid'], $configoption)->fetch();

            if (!$option || empty($map['components'][$option->id])) {
                continue;
            }
            $component = $map['components'][$option->id];
            $custom[$component['category_id']][$component['item_id']] = $enabled;
        }
    }

    return $custom;
}
