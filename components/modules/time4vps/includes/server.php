<?php

/** @noinspection PhpUndefinedFunctionInspection */

/** @noinspection PhpUndefinedNamespaceInspection */
/** @noinspection PhpUndefinedClassInspection */

use Time4vps\API\Order;
use Time4vps\API\Service;
use Time4vps\Base\Endpoint;

/**
 * Create account
 *
 * @param $params
 * @return string|void
 * @throws Exception
 */
function time4vps_CreateAccount($params)
{
    time4vps_InitAPI($params);
    try {
        if ($server = time4vps_ExtractServer($params)) {
            return 'Service is already created';
        }
    } catch (Exception $e) {
    }
    $product_id = $params['product_id'];
    try {
        $order = new Order();
        $order = $order->create($product_id, $params['domain'] != '' ? $params['domain'] : 'serverhost.name', time4vps_BillingCycle($params['billing_cycle']), []);
        $service_id = (new Service())->fromOrder($order['order_num']);
        $r = new Record();
        $id = $r->insert(TIME4VPS_TABLE, array('external_id' => $service_id, 'service_id' => $params['service_id'], 'details_updated' => null));
    } catch (Exception $e) {
        return 'Cannot create account. ' . $e->getMessage();
    }
    return 'success';
}

function time4vps_UpdateAccount($params)
{
    time4vps_InitAPI($params);
    $product_id = $params['product_id'];
    $resources = $params['resources'];
    try {
        $order = new Order();
        $order = $order->update($product_id, $resources);
    } catch (Exception $e) {
        return 'Cannot update account. ' . $e->getMessage();
    }
    return 'success';
}


/**
 * Terminate account
 *
 * @param $params
 * @return string
 */
function time4vps_TerminateAccount($params)
{
    time4vps_InitAPI($params);
    try {
        $server = time4vps_ExtractServer($params);
    } catch (Exception $e) {
        return $e->getMessage();
    }
    try {
        (new Service($server->id()))->cancel('No need, terminated by API', true);
    } catch (Exception $e) {
        return 'Cannot terminate account. ' . $e->getMessage();
    }

    return 'success';
}

/**
 * Upgrade package or config option
 *
 * @param $params
 * @return string
 * @throws Exception
 */
function time4vps_ChangePackage($params)
{
    time4vps_InitAPI($params);

    try {
        $server = time4vps_ExtractServer($params);
    } catch (Exception $e) {
        return $e->getMessage();
    }

    try {
        $service = new Service($server->id());
    } catch (Exception $e) {
        return $e->getMessage();
    }

    $details = $server->details();

    if ((int) $params['configoption1'] !== (int) $details['package_id']) {
        $service->orderUpgrade(['package' => $params['configoption1']], time4vps_BillingCycle($params['model']['billingcycle']));
    }

    $service->orderUpgrade(['resources' => time4vps_ExtractComponents($params, false)], time4vps_BillingCycle($params['model']['billingcycle']));

    return 'success';
}

/**
 * Changes server password
 *
 * @param $params
 * @return string
 */
function time4vps_ResetPassword($params)
{
    time4vps_InitAPI($params);

    try {
        $server = time4vps_ExtractServer($params);
        $server->resetPassword();
    } catch (Exception $e) {
        return 'Cannot change server password. ' . $e->getMessage();
    }

    return 'success';
}

/**
 * Reboot server
 *
 * @param $params
 * @return string
 */
function time4vps_Reboot($params)
{
    time4vps_InitAPI($params);

    try {
        $server = time4vps_ExtractServer($params);
        $server->reboot();
    } catch (Exception $e) {
        return 'Cannot reboot server. ' . $e->getMessage();
    }

    return 'success';
}

/**
 * Change DNS Servers
 *
 * @param $params
 * @param string $ns1
 * @param string $ns2
 * @param string $ns3
 * @param string $ns4
 * @return string
 */
function time4vps_ChangeDNS($params, $ns1, $ns2 = '', $ns3 = '', $ns4 = '')
{
    time4vps_InitAPI($params);

    try {
        $server = time4vps_extractServer($params);
        $server->setDNS($ns1, $ns2, $ns3, $ns4);
    } catch (Exception $e) {
        return 'Cannot change DNS. ' . $e->getMessage();
    }

    return 'success';
}

/**
 * Change PTR Record
 *
 * @param $params
 * @param $ip
 * @param $ptr
 * @return string
 */
function time4vps_ChangePTR($params, $ip, $ptr)
{
    time4vps_InitAPI($params);

    try {
        $server = time4vps_ExtractServer($params);
        $server->setPTR($ip, $ptr);
    } catch (Exception $e) {
        return 'Cannot change PTR. ' . $e->getMessage();
    }

    return 'success';
}

/**
 * Reinstall server
 *
 * @param $params
 * @param $os
 * @return string
 */
function time4vps_ReinstallServer($params, $os)
{
    time4vps_InitAPI($params);
    try {
        $server = time4vps_ExtractServer($params);
        $server->reinstall($os, null, $params['init']);
    } catch (Exception $e) {
        return 'Cannot reinstall server. ' . $e->getMessage();
    }

    return 'success';
}

/**
 * Change server hostname
 *
 * @param $params
 * @param $hostname
 * @return string
 */
function time4vps_ChangeHostname($params, $hostname)
{
    time4vps_InitAPI($params);

    try {
        $server = time4vps_ExtractServer($params);
        $server->rename($hostname);
    } catch (Exception $e) {
        return 'Cannot rename server. ' . $e->getMessage();
    }

    return 'success';
}

/**
 * Launch emergency console
 *
 * @param $params
 * @param $timeout
 * @return string
 */
function time4vps_EmergencyConsole($params, $timeout)
{
    time4vps_InitAPI($params);

    try {
        $server = time4vps_ExtractServer($params);
        $server->emergencyConsole($timeout);
    } catch (Exception $e) {
        return 'Cannot launch emergency console. ' . $e->getMessage();
    }

    return 'success';
}

/**
 * Reset firewall to default settings
 *
 * @param $params
 * @return string
 */
function time4vps_ResetFirewall($params)
{
    time4vps_InitAPI($params);

    try {
        $server = time4vps_ExtractServer($params);
        $server->flushFirewall();
    } catch (Exception $e) {
        return 'Cannot reset firewall. ' . $e->getMessage();
    }

    return 'success';
}

/**
 * get service Details and send server password
 * @param $params
 * @throws Exception
 */
function time4vps_GetServiceDetails($params)
{
    $r = new Record();
    $row = $r->select()->from(TIME4VPS_TABLE)->where("service_id", "=", $params['service_id'])->fetch();
    if (isset($row->external_id) && $row->external_id != '') {
        time4vps_InitAPI($params);
        $service = (new Service($row->external_id))->details();
    } else {
        $service = new stdClass();
    }
    return $service;
}


/**
 * Update server details table
 *
 * @param $params
 * @param bool $force
 * @return array|false
 * @throws Exception
 */
function time4vps_GetServerDetails($params, $force = false)
{
    $r = new Record();
    $row = $r->select()->from(TIME4VPS_TABLE)->where("service_id", "=", $params['service_id'])->fetch();
    $current_details = isset($row->details) && $row->details ? json_decode($row->details, true) : [];
    $last_update = isset($row->details_updated) && $row->details_updated ? strtotime($row->details_updated) : null;

    if ($force || !$current_details || !$last_update || $current_details['active_task'] || time() - $last_update > 5 * 60) {
        $update = [];

        time4vps_InitAPI($params);

        $server = time4vps_ExtractServer($params);

        // Update service details
        $service = (new Service($server->id()))->details();

        $update['lastupdate'] = date('Y-m-d H:i:s', time());
        $update['domainstatus'] = $service['status'];

        if ($update['domainstatus'] === 'Active') {
            // Update server details

            $details = $server->details();

            if ($details['active_task']) {
                return $details;
            }
            // Cache data
            $r = new Record();
            $r->where("service_id", "=", $params['service_id'])->update(TIME4VPS_TABLE, array("details" => json_encode($details), "details_updated" => $update['lastupdate']));

            $current_details = $details;
        }
    }
    return $current_details;
}

/**
 * Update server details table and mark details as obsolete
 *
 * @param $params
 */
function time4vps_MarkServerDetailsObsolete($params)
{
    /** @noinspection PhpUndefinedClassInspection */
    $r = new Record();
    $r->where("service_id", "=", $params['service_id'])->update(TIME4VPS_TABLE, array("details_updated" => null));
}
