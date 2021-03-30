<?php

use Blesta\Core\Util\Validate\Server;

require 'defines.php';

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

/**
 * time4vps Module
 *
 * @package blesta
 * @subpackage blesta.components.modules.time4vps
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class time4vps extends Module
{
    /**
     * @var int The length of a time4vps api token
     */
    private static $token_length = 32;

    /**
     * Initializes the module
     */
    public function __construct()
    {
        // Load configuration required by this module
        $this->loadConfig(dirname(__FILE__) . DS . 'config.json');

        // Load components required by this module
        Loader::loadComponents($this, ['Input']);

        // Load the language required by this module
        Language::loadLang('time4vps', null, dirname(__FILE__) . DS . 'language' . DS);
    }

    /**
     * When this module is  intalled following operations will be performed
     */
    public function install()
    {
        // Create TIME4VPS_TABLE table if not exist
        $r = new Record();
        $create_table_query = "CREATE TABLE IF NOT EXISTS " . TIME4VPS_TABLE . " (
                `service_id` int(11) NOT NULL,
                `external_id` int(11) NOT NULL,
                `details` text COLLATE utf8_unicode_ci DEFAULT NULL,
                `details_updated` timestamp NULL DEFAULT NULL,
                INDEX `mod_time4vps_external_id_index` (`external_id`),
                CONSTRAINT `mod_time4vps_service_id_unique` UNIQUE(service_id)
              ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
        $r->query($create_table_query);
        // echo 'If you want to import all products from Time4VPS, run <a href="update.php">update</a>.';
    }

    /**
     * Test API connection
     * to test connection and to verify credentials
     * @param $params
     * @return array
     */
    public function time4vps_TestConnection($params)
    {
        try {
            $this->time4vps_ProductLoaderFunction($params);
            $success = true;
            $errorMsg = '';
        } catch (Exception $e) {
            $success = false;
            $errorMsg = $e->getMessage();
        }

        return [
            'success' => $success,
            'error' => $errorMsg
        ];
    }

    /**
     * Initialize the remote server instance through API to verify credentials
     * $params contains all information required for remote server authentication
     */
    public function time4vps_InitAPI($params)
    {
        $debug = new Time4vps\Base\Debug();
        $endpoint = "{$params['serverhttpprefix']}://{$params['serverhostname']}/api/";
        Endpoint::BaseURL($endpoint);
        Endpoint::Auth($params['serverusername'], $params['serverpassword']);
        Endpoint::DebugFunction(function ($args, $request, $response) use ($debug) {
            $id = hash('crc32', microtime(true));
            $benchmark = $debug->benchmark();
            $this->log($args[1], json_encode(array('Request' => $request,'Response' => (string) $response , 'time taken' => $benchmark), true), 'input', true);
        });
    }

    /**
     * Fetch Products from the remote Server
     * $params contains all information required for remote server authentication
     */
    public function time4vps_ProductLoaderFunction($params)
    {
        $this->time4vps_InitAPI($params);
        $products = new Product();
        $available_products = [];
        foreach ($products->getAvailablevps() as $product) {
            $available_products[$product['id']] = $product['name'];
        }
        return $available_products;
    }

    /**
     * Fetch init Scripts from the remote Server
     * $params contains all information required for remote server authentication
     */
    public function time4vps_InitScriptLoaderFunction($params)
    {
        $this->time4vps_InitAPI($params);
        $script = new Script();
        $available_scripts = ['' => ''];
        foreach ($script->all() as $script) {
            $available_scripts[$script['id']] = "{$script['name']} ({$script['syntax']})";
        }
        return $available_scripts;
    }

    /**
     * Returns all tabs to display to an admin when managing a service whose
     * package uses this module
     *
     * @param stdClass $package A stdClass object representing the selected package
     * @return array An array of tabs in the format of method => title.
     *  Example: array('methodName' => "Title", 'methodName2' => "Title2")
     */
    public function getAdminTabs($package)
    {
        return [];
    }

    /**
     * Returns all tabs to display to a client when managing a service whose
     * package uses this module
     *
     * @param stdClass $package A stdClass object representing the selected package
     * @return array An array of tabs in the format of method => title.
     *  Example: array('methodName' => "Title", 'methodName2' => "Title2")
     */
    public function getClientTabs($package)
    {
        return [
            'tabserviceStats' => array(
                "name" => Language::_('time4vps.tab_service_stats', true),
                "icon" => "fas fa-chart-bar"
            ),
            'tabReboot' => array(
                "name" => Language::_('time4vps.tab_client_reboot', true),
                "icon" => "fas fa-sync-alt"
            ),
            'tabResetpassword' => array(
                "name" => Language::_('time4vps.tab_client_reset_password', true),
                "icon" => "fas fa-key"
            ),
            'tabChangeHostName' => array(
                "name" => Language::_('time4vps.tab_client_change_hostname', true),
                "icon" => "fas fa-edit"
            ),
            'tabReinstallOS' => array(
                "name" => Language::_('time4vps.tab_client_reinstall', true),
                "icon" => "fas fa-wrench"
            ),
            'tabEmergencyConsole' => array(
                "name" => Language::_('time4vps.tab_client_emergency_console', true),
                "icon" => "fas fa-terminal"
            ),
            'tabChangeDNS' => array(
                "name" => Language::_('time4vps.tab_client_change_dns', true),
                "icon" => "fas fa-tags"
            ),
            'tabResetFirewall' => array(
                "name" => Language::_('time4vps.tab_client_reset_firewall', true),
                "icon" => "fas fa-fire"
            ),
            'tabChangePTR' => array(
                "name" => Language::_('time4vps.tab_client_change_ptr', true),
                "icon" => "fas fa-search"
            ),
            'tabCancelService' => array(
                "name" => Language::_('time4vps.tab_client_request_cancellation', true),
                "icon" => "fas fa-ban"
            ),
            'tabUsageGraphs' => array(
                "name" => Language::_('time4vps.tab_usage_graph', true),
                "icon" => "fas fa-chart-area"
            ),
            'tabUsagehistory' => array(
                "name" => Language::_('time4vps.tab_usage_history', true),
                "icon" => "fas fa-chart-bar"
            )
        ];
    }

    /**
     * Client Area Service Stats
     *
     * @param stdClass $package A stdClass object representing the current package
     * @param stdClass $service A stdClass object representing the current service
     * @param array $get Any GET parameters
     * @param array $post Any POST parameters
     * @param array $files Any FILES parameters
     * @return array
     */
    public function tabserviceStats($package, $service, array $get = null, array $post = null, array $files = null)
    {
        $this->view = new View('tab_service_stats', 'default');
        $this->view->base_uri = $this->base_uri;
        //Buiding required params from current available data
        $row = $this->getModuleRow();
        $params = [];
        $params['serverhttpprefix'] = isset($row->meta->use_ssl) && $row->meta->use_ssl == true ? 'https' : 'http';
        $params['serverhostname'] = $row->meta->host_name;
        $params['serverusername'] = $row->meta->user_name;
        $params['serverpassword'] = $row->meta->password;
        $params['service_id'] = $service->id;
        // Load the helpers required for this view
        Loader::loadHelpers($this, ['Form', 'Html']);
        $last_result = null;
        $error = null;
        try {
            time4vps_InitAPI($params);
            $server = time4vps_GetServerDetails($params); // server details
            $service = time4vps_GetServiceDetails($params); // service details
        } catch (InvalidTaskException $e) {
            // No tasks yet
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
        $this->view->set('error', $error);
        $this->view->set('server', $server);
        $this->view->set('service', $service);
        $this->view->setDefaultView('components' . DS . 'modules' . DS . 'time4vps' . DS);
        return $this->view->fetch();
    }
    /**
     * Client Area Server Reboot
     *
     * @param stdClass $package A stdClass object representing the current package
     * @param stdClass $service A stdClass object representing the current service
     * @param array $get Any GET parameters
     * @param array $post Any POST parameters
     * @param array $files Any FILES parameters
     * @return array
     */
    public function tabReboot($package, $service, array $get = null, array $post = null, array $files = null)
    {
        $this->view = new View('tab_reboot', 'default');
        $this->view->base_uri = $this->base_uri;
        //Buiding required params from current available data
        $row = $this->getModuleRow();
        $params = [];
        $params['serverhttpprefix'] = isset($row->meta->use_ssl) && $row->meta->use_ssl == true ? 'https' : 'http';
        $params['serverhostname'] = $row->meta->host_name;
        $params['serverusername'] = $row->meta->user_name;
        $params['serverpassword'] = $row->meta->password;
        $params['service_id'] = $service->id;
        // Load the helpers required for this view
        Loader::loadHelpers($this, ['Form', 'Html']);
        $last_result = null;
        $error = null;
        if (!empty($_POST['confirm'])) {
            $error =  time4vps_Reboot($params);
            if ($error === 'success') {
                time4vps_MarkServerDetailsObsolete($params);
                return;
            }
        }
        try {
            time4vps_InitAPI($params);
            $server = time4vps_ExtractServer($params);
            $last_result = $server->taskResult('server_reboot');
        } catch (InvalidTaskException $e) {
            // No tasks yet
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
        $this->view->set('error', $error);
        $this->view->set('last_result', $last_result);
        $this->view->setDefaultView('components' . DS . 'modules' . DS . 'time4vps' . DS);
        return $this->view->fetch();
    }

    /**
     * Client Area Password Reset
     *
     * @param stdClass $package A stdClass object representing the current package
     * @param stdClass $service A stdClass object representing the current service
     * @param array $get Any GET parameters
     * @param array $post Any POST parameters
     * @param array $files Any FILES parameters
     * @return array
     */
    public function tabResetpassword($package, $service, array $get = null, array $post = null, array $files = null)
    {
        $this->view = new View('tab_resetpassword', 'default');
        $this->view->base_uri = $this->base_uri;
        //Buiding required params from current available data
        $row = $this->getModuleRow();
        $params = [];
        $params['serverhttpprefix'] = isset($row->meta->use_ssl) && $row->meta->use_ssl == true ? 'https' : 'http';
        $params['serverhostname'] = $row->meta->host_name;
        $params['serverusername'] = $row->meta->user_name;
        $params['serverpassword'] = $row->meta->password;
        $params['service_id'] = $service->id;
        // Load the helpers required for this view
        Loader::loadHelpers($this, ['Form', 'Html']);
        $last_result = null;
        $error = null;
        if (!empty($post['confirm'])) {
            $error =  time4vps_ResetPassword($params);
            if ($error === 'success') {
                time4vps_MarkServerDetailsObsolete($params);
                return;
            }
        }
        try {
            time4vps_InitAPI($params);
            $server = time4vps_ExtractServer($params);
            $last_result = $server->taskResult('server_reset_password');
        } catch (InvalidTaskException $e) {
            // No tasks yet
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
        $this->view->set('error', $error);
        $this->view->set('last_result', $last_result);
        $this->view->setDefaultView('components' . DS . 'modules' . DS . 'time4vps' . DS);
        return $this->view->fetch();
    }

    /**
     * Client Area Server Reinstall
     *
     * @param stdClass $package A stdClass object representing the current package
     * @param stdClass $service A stdClass object representing the current service
     * @param array $get Any GET parameters
     * @param array $post Any POST parameters
     * @param array $files Any FILES parameters
     * @return array
     */
    public function tabReinstallOS($package, $service, array $get = null, array $post = null, array $files = null)
    {
        $this->view = new View('tab_reinstallos', 'default');
        $this->view->base_uri = $this->base_uri;
        //Buiding required params from current available data
        $row = $this->getModuleRow();
        $params = [];
        $params['serverhttpprefix'] = isset($row->meta->use_ssl) && $row->meta->use_ssl == true ? 'https' : 'http';
        $params['serverhostname'] = $row->meta->host_name;
        $params['serverusername'] = $row->meta->user_name;
        $params['serverpassword'] = $row->meta->password;
        $params['service_id'] = $service->id;
        $os_list = isset($package->meta->os_list) ? $package->meta->os_list : '';
        $params['init'] = isset($package->meta->init) ? $package->meta->init : '';
        // Load the helpers required for this view
        Loader::loadHelpers($this, ['Form', 'Html']);
        $last_result = null;
        $error = null;
        $oses = [];
        if (!empty($post) && isset($post['confirm']) && isset($post['os'])) {
            $error = time4vps_ReinstallServer($params, $post['os']);
            if ($error === 'success') {
                time4vps_MarkServerDetailsObsolete($params);
                return;
                // time4vps_Redirect(time4vps_ActionLink($params, 'Reinstall'));
            }
        }
        try {
            time4vps_InitAPI($params);

            $server = time4vps_ExtractServer($params);
            $oses = $server->availableOS();

            if ($os_list) {
                $visible_os = explode(PHP_EOL, $os_list);

                foreach ($visible_os as &$o) {
                    $o = trim($o);
                }

                foreach ($oses as $idx => $os) {
                    if (!in_array($os['title'], $visible_os)) {
                        unset($oses[$idx]);
                    }
                }
            }

            $last_result = $server->taskResult('server_recreate');
        } catch (InvalidTaskException $e) {
            // No tasks yet
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
        $this->view->set('error', $error);
        $this->view->set('last_result', $last_result);
        $this->view->set('oses', $oses);
        $this->view->setDefaultView('components' . DS . 'modules' . DS . 'time4vps' . DS);
        return $this->view->fetch();
    }

    /**
     * Client Area Change Server Hostname
     *
     * @param stdClass $package A stdClass object representing the current package
     * @param stdClass $service A stdClass object representing the current service
     * @param array $get Any GET parameters
     * @param array $post Any POST parameters
     * @param array $files Any FILES parameters
     * @return array
     */
    public function tabChangeHostName($package, $service, array $get = null, array $post = null, array $files = null)
    {
        $this->view = new View('tab_changehostname', 'default');
        $this->view->base_uri = $this->base_uri;
        //Buiding required params from current available data
        $row = $this->getModuleRow();
        $params = [];
        $params['serverhttpprefix'] = isset($row->meta->use_ssl) && $row->meta->use_ssl == true ? 'https' : 'http';
        $params['serverhostname'] = $row->meta->host_name;
        $params['serverusername'] = $row->meta->user_name;
        $params['serverpassword'] = $row->meta->password;
        $params['service_id'] = $service->id;
        // Load the helpers required for this view
        Loader::loadHelpers($this, ['Form', 'Html']);
        $last_result = null;
        $error = null;
        if (!empty($post['hostname'])) {
            $error = time4vps_ChangeHostname($params, $post['hostname']);
            if ($error === 'success') {
                time4vps_MarkServerDetailsObsolete($params);
                return;
                // time4vps_Redirect(time4vps_ActionLink($params, 'ChangeHostname'));
            }
        }
        $this->view->set('error', $error);
        $this->view->set('last_result', $last_result);
        $this->view->setDefaultView('components' . DS . 'modules' . DS . 'time4vps' . DS);
        return $this->view->fetch();
    }

    /**
     * Client Area Change PTR
     *
     * @param stdClass $package A stdClass object representing the current package
     * @param stdClass $service A stdClass object representing the current service
     * @param array $get Any GET parameters
     * @param array $post Any POST parameters
     * @param array $files Any FILES parameters
     * @return array
     */
    public function tabChangePTR($package, $service, array $get = null, array $post = null, array $files = null)
    {
        $this->view = new View('tab_changeptr', 'default');
        $this->view->base_uri = $this->base_uri;
        //Buiding required params from current available data
        $row = $this->getModuleRow();
        $details = [];
        $params = [];
        $params['serverhttpprefix'] = isset($row->meta->use_ssl) && $row->meta->use_ssl == true ? 'https' : 'http';
        $params['serverhostname'] = $row->meta->host_name;
        $params['serverusername'] = $row->meta->user_name;
        $params['serverpassword'] = $row->meta->password;
        $params['service_id'] = $service->id;
        // Load the helpers required for this view
        Loader::loadHelpers($this, ['Form', 'Html']);
        $error = null;
        $ips = [];
        if (!empty($post['ip']) && !empty($post['ptr'])) {
            $error = time4vps_ChangePTR($params, $post['ip'], $post['ptr']);
            if ($error === 'success') {
                time4vps_MarkServerDetailsObsolete($params);
                return;
                // time4vps_Redirect(time4vps_ActionLink($params, 'ChangePTR'));
            }
        }
        try {
            time4vps_InitAPI($params);
            $server = time4vps_ExtractServer($params);
            $ips = $server->additionalIPs();
            $ips = array_shift($ips);
            $details = time4vps_GetServerDetails($params); // server details
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
        $this->view->set('ips', $ips);
        $this->view->set('error', $error);
        $this->view->set('details', $details);
        $this->view->setDefaultView('components' . DS . 'modules' . DS . 'time4vps' . DS);
        return $this->view->fetch();
    }

    /**
     * Client Area Change DNS
     *
     * @param stdClass $package A stdClass object representing the current package
     * @param stdClass $service A stdClass object representing the current service
     * @param array $get Any GET parameters
     * @param array $post Any POST parameters
     * @param array $files Any FILES parameters
     * @return array
     */
    public function tabChangeDNS($package, $service, array $get = null, array $post = null, array $files = null)
    {
        $this->view = new View('tab_changedns', 'default');
        $this->view->base_uri = $this->base_uri;
        //Buiding required params from current available data
        $row = $this->getModuleRow();
        $details = [];
        $params = [];
        $params['serverhttpprefix'] = isset($row->meta->use_ssl) && $row->meta->use_ssl == true ? 'https' : 'http';
        $params['serverhostname'] = $row->meta->host_name;
        $params['serverusername'] = $row->meta->user_name;
        $params['serverpassword'] = $row->meta->password;
        $params['service_id'] = $service->id;
        // Load the helpers required for this view
        Loader::loadHelpers($this, ['Form', 'Html']);
        $error = null;
        if (!empty($post)) {
            $error = time4vps_ChangeDNS($params, $post['ns1'], $post['ns2']);
            if ($error === 'success') {
                time4vps_MarkServerDetailsObsolete($params);
                return;
                // time4vps_Redirect(time4vps_ActionLink($params, 'ChangeDNS'));
            }
        }
        try {
            $details = time4vps_GetServerDetails($params); // server details
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
        $this->view->set('ippattern', '((^|\.)((25[0-5])|(2[0-4]\d)|(1\d\d)|([1-9]?\d))){4}$');
        $this->view->set('error', $error);
        $this->view->set('details', $details);
        $this->view->setDefaultView('components' . DS . 'modules' . DS . 'time4vps' . DS);
        return $this->view->fetch();
    }

    /**
     * Client Area Reset Firewall
     *
     * @param stdClass $package A stdClass object representing the current package
     * @param stdClass $service A stdClass object representing the current service
     * @param array $get Any GET parameters
     * @param array $post Any POST parameters
     * @param array $files Any FILES parameters
     * @return array
     */
    public function tabResetFirewall($package, $service, array $get = null, array $post = null, array $files = null)
    {
        $this->view = new View('tab_resetfirewall', 'default');
        $this->view->base_uri = $this->base_uri;
        //Buiding required params from current available data
        $row = $this->getModuleRow();
        $params = [];
        $params['serverhttpprefix'] = isset($row->meta->use_ssl) && $row->meta->use_ssl == true ? 'https' : 'http';
        $params['serverhostname'] = $row->meta->host_name;
        $params['serverusername'] = $row->meta->user_name;
        $params['serverpassword'] = $row->meta->password;
        $params['service_id'] = $service->id;
        // Load the helpers required for this view
        Loader::loadHelpers($this, ['Form', 'Html']);
        $last_result = null;
        $error = null;
        if (!empty($post['confirm'])) {
            $error = time4vps_ResetFirewall($params);
            if ($error === 'success') {
                time4vps_MarkServerDetailsObsolete($params);
                return;
            }
        }
        try {
            time4vps_InitAPI($params);
            $server = time4vps_ExtractServer($params);
            $last_result = $server->taskResult('server_flush_iptables');
        } catch (InvalidTaskException $e) {
            // No tasks yet
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
        $this->view->set('last_result', $last_result);
        $this->view->set('error', $error);
        $this->view->setDefaultView('components' . DS . 'modules' . DS . 'time4vps' . DS);
        return $this->view->fetch();
    }

    /**
     * Client Area Emergency Console
     *
     * @param stdClass $package A stdClass object representing the current package
     * @param stdClass $service A stdClass object representing the current service
     * @param array $get Any GET parameters
     * @param array $post Any POST parameters
     * @param array $files Any FILES parameters
     * @return array
     */
    public function tabEmergencyConsole($package, $service, array $get = null, array $post = null, array $files = null)
    {
        $this->view = new View('tab_emergencyconsole', 'default');
        $this->view->base_uri = $this->base_uri;
        //Buiding required params from current available data
        $row = $this->getModuleRow();
        $params = [];
        $params['serverhttpprefix'] = isset($row->meta->use_ssl) && $row->meta->use_ssl == true ? 'https' : 'http';
        $params['serverhostname'] = $row->meta->host_name;
        $params['serverusername'] = $row->meta->user_name;
        $params['serverpassword'] = $row->meta->password;
        $params['service_id'] = $service->id;
        // Load the helpers required for this view
        Loader::loadHelpers($this, ['Form', 'Html']);
        $service_fields = $this->serviceFieldsToObject($service->fields);
        if(isset($service_fields->time4vps_domain) && $service_fields->time4vps_domain == 'serverhost.name'){
            $row = $this->getModuleRow();
            $params = [];
            $params['serverhttpprefix'] = isset($row->meta->use_ssl) && $row->meta->use_ssl == true ? 'https' : 'http';
            $params['serverhostname'] = $row->meta->host_name;
            $params['serverusername'] = $row->meta->user_name;
            $params['serverpassword'] = $row->meta->password;
            $params['service_id'] = $service->id;
            time4vps_InitAPI($params);
            $remote_service = time4vps_GetServiceDetails($params); // remote service details
            $service_fields->time4vps_domain = is_array($remote_service) && isset($remote_service['domain']) && !empty($remote_service['domain']) ? $remote_service['domain']: 'serverhost.name';
        }
        $last_result = null;
        $error = null;
        if (!empty($post['timeout'])) {
            if ($error === 'success') {
                time4vps_MarkServerDetailsObsolete($params);
            }
        }
        try {
            time4vps_InitAPI($params);
            $server = time4vps_ExtractServer($params);
            $last_result = $server->taskResult('server_web_console');
        } catch (InvalidTaskException $e) {
            // No tasks yet
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
        $this->view->set('service_fields', $service_fields);
        $this->view->set('last_result', $last_result);
        $this->view->set('error', $error);
        $this->view->setDefaultView('components' . DS . 'modules' . DS . 'time4vps' . DS);
        return $this->view->fetch();
    }

    /**
     * Tab Cancel Service
     *
     * @param stdClass $package A stdClass object representing the current package
     * @param stdClass $service A stdClass object representing the current service
     * @param array $get Any GET parameters
     * @param array $post Any POST parameters
     * @param array $files Any FILES parameters
     * @return array
     */
    public function tabCancelService($package, $service, array $get = null, array $post = null, array $files = null)
    {
        $this->view = new View('tab_requestcancel', 'default');
        $this->view->base_uri = $this->base_uri;
        // Load the helpers required for this view
        Loader::loadHelpers($this, ['Form', 'Html']);
        $service_fields = $this->serviceFieldsToObject($service->fields);
        if(isset($service_fields->time4vps_domain) && $service_fields->time4vps_domain == 'serverhost.name'){
            $row = $this->getModuleRow();
            $params = [];
            $params['serverhttpprefix'] = isset($row->meta->use_ssl) && $row->meta->use_ssl == true ? 'https' : 'http';
            $params['serverhostname'] = $row->meta->host_name;
            $params['serverusername'] = $row->meta->user_name;
            $params['serverpassword'] = $row->meta->password;
            $params['service_id'] = $service->id;
            time4vps_InitAPI($params);
            $remote_service = time4vps_GetServiceDetails($params); // remote service details
            $service_fields->time4vps_domain = is_array($remote_service) && isset($remote_service['domain']) && !empty($remote_service['domain']) ? $remote_service['domain']: 'serverhost.name';
        }
        // Perform the password reset
        if (!empty($post)) {
            Loader::loadModels($this, ['Services']);
            $data = [
                'cancellation_reason' => $this->Html->ifSet($post['time4vps_reason']),
                'date_canceled' => isset($post['time4vps_cancellation_type']) && $post['time4vps_cancellation_type'] == 'Immediate' ? date("Y-m-d H:i:s") : 'end_of_term'
            ];
            $this->Services->cancel($service->id, $data);
            if ($this->Services->errors()) {
                $this->Input->setErrors($this->Services->errors());
            }

            $vars = (object)$post;
        }
        $options['Immediate'] = 'Immediate';
        $options['End of Billing Period'] = 'End of Billing Period';
        $this->view->set('service_fields', $service_fields);
        $this->view->set('vars', (isset($vars) ? $vars : new stdClass()));
        $this->view->set('service', (isset($service) ? $service : new stdClass()));
        $this->view->set('options', (isset($options) ? $options : []));
        $this->view->set('service_id', $service->id);
        $this->view->setDefaultView('components' . DS . 'modules' . DS . 'time4vps' . DS);
        return $this->view->fetch();
    }

    /**
     * Usage graphs
     * @param stdClass $package A stdClass object representing the current package
     * @param stdClass $service A stdClass object representing the current service
     * @param array $get Any GET parameters
     * @param array $post Any POST parameters
     * @param array $files Any FILES parameters
     * @return array
     */
    public function tabUsageGraphs($package, $service, array $get = null, array $post = null, array $files = null)
    {
        //Buiding required params from current available data
        $row = $this->getModuleRow();
        $params = [];
        $params['serverhttpprefix'] = isset($row->meta->use_ssl) && $row->meta->use_ssl == true ? 'https' : 'http';
        $params['serverhostname'] = $row->meta->host_name;
        $params['serverusername'] = $row->meta->user_name;
        $params['serverpassword'] = $row->meta->password;
        $params['service_id'] = $service->id;
        // Load the helpers required for this view
        Loader::loadHelpers($this, ['Form', 'Html']);
        $error = null;
        $graphs = [];
        if (empty($_GET['graph'])) {// for all graphs
            $this->view = new View('tab_usagegraphs', 'default');
            $this->view->base_uri = $this->base_uri;
            try {
                time4vps_InitAPI($params);
                $server = time4vps_ExtractServer($params);

                foreach ($server->usageGraphs() as $graph) {
                    $graphs[$graph["type"]] = $graph;
                }
            } catch (Exception $e) {
                $error = $e->getMessage();
            }
            $this->view->set('error', $error);
            $this->view->set('graphs', $graphs);
            $this->view->set('url_graph_detail', "?graph=");
            $this->view->setDefaultView('components' . DS . 'modules' . DS . 'time4vps' . DS);
            return $this->view->fetch();
        } else { // for single detailed Graph
            $this->view = new View('tab_usagegraph', 'default');
            $this->view->base_uri = $this->base_uri;
            $graphType = '';
            $graphTypes = ['traffic', 'io', 'load', 'iops', 'netpps', 'memory', 'cpu', 'storage'];
            if (!empty($_GET['graph']) && in_array($_GET['graph'], $graphTypes)) {
                $graphType = $_GET['graph'];
                try {
                    time4vps_InitAPI($params);
                    $server = time4vps_ExtractServer($params);

                    foreach ($server->usageGraphs(768) as $graph) {
                        preg_match("/^({$graphType})_(.*)$/", $graph["type"], $matches);

                        if ($matches) {
                            $graphs[ucfirst($matches[2])] = $graph['url'];
                        }
                    }
                } catch (Exception $e) {
                    $error = $e->getMessage();
                }
            } else {
                $error = "Invalid graph type.";
            }
            $this->view->set('error', $error);
            $this->view->set('graphs', $graphs);
            $this->view->set('graph_type', ucfirst($graphType));
            $this->view->setDefaultView('components' . DS . 'modules' . DS . 'time4vps' . DS);
            return $this->view->fetch();
        }
    }

    /**
     * Usage history
     * @param stdClass $package A stdClass object representing the current package
     * @param stdClass $service A stdClass object representing the current service
     * @param array $get Any GET parameters
     * @param array $post Any POST parameters
     * @param array $files Any FILES parameters
     * @return array
     */
    public function tabUsagehistory($package, $service, array $get = null, array $post = null, array $files = null)
    {
        $this->view = new View('tab_usagehistory', 'default');
        $this->view->base_uri = $this->base_uri;
        //Buiding required params from current available data
        $row = $this->getModuleRow();
        $params = [];
        $params['serverhttpprefix'] = isset($row->meta->use_ssl) && $row->meta->use_ssl == true ? 'https' : 'http';
        $params['serverhostname'] = $row->meta->host_name;
        $params['serverusername'] = $row->meta->user_name;
        $params['serverpassword'] = $row->meta->password;
        $params['service_id'] = $service->id;
        // Load the helpers required for this view
        Loader::loadHelpers($this, ['Form', 'Html']);
        $error = null;
        $usage_history = [];
        try {
            time4vps_InitAPI($params);
            $server = time4vps_ExtractServer($params);
            $usage_history = $server->usageHistory();
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
        $this->view->set('usage_history', array_reverse($usage_history));
        $this->view->set('error', $error);
        $this->view->setDefaultView('components' . DS . 'modules' . DS . 'time4vps' . DS);
        return $this->view->fetch();
    }


    /**
     * Returns all fields used when adding/editing a package, including any
     * javascript to execute when the page is rendered with these fields.
     *
     * @param $vars stdClass A stdClass object representing a set of post fields
     * @return ModuleFields A ModuleFields object, containing the fields to
     *  render as well as any additional HTML markup to include
     */
    public function getPackageFields($vars = null)
    {
        Loader::loadHelpers($this, ['Html']);
        $fields = new ModuleFields();
        $fields->setHtml("
			<script type=\"text/javascript\">
				$(document).ready(function() {
					// Set whether to show or hide the ACL option
					$('#time4vps_Component_map').hide();
					$('#time4vps_Component_map_note').hide();
					$('#time4vps_configoption4').hide();
					$('#time4vps_configoption4_note').hide();
                    $( '#time4vps_advance_mode' ).click(function() {
                        console.log('Advance mode');
                        $('#time4vps_Component_map').show();
                        $('#time4vps_Component_map_note').show();
                        $('#time4vps_configoption4').show();
                        $('#time4vps_configoption4_note').show();
                        $('#time4vps_normal_mode').show();
                        $('#time4vps_advance_mode').hide();
                    });
                    $( '#time4vps_normal_mode' ).click(function() {
                        $('#time4vps_Component_map').hide();
                        $('#time4vps_Component_map_note').hide();
                        $('#time4vps_configoption4').hide();
                        $('#time4vps_configoption4_note').hide();
                        $('#time4vps_normal_mode').hide();
                        $( '#time4vps_advance_mode').show();
                    });
				});
			</script>
		");
        Loader::loadModels($this, ['ModuleManager']);
        if (isset($vars->module_row) && $vars->module_row != '') {
            $raw_response[] = $this->ModuleManager->getRow($vars->module_row);
        } else {
            $raw_response = $this->ModuleManager->getRows($vars->module_id);
        }
        $available_products = array();
        $available_scripts = array();
        $params = [];
        foreach ($raw_response as $key => $response) {
            $params['serverhostname'] = isset($response->meta) ? $response->meta->host_name : $response->host_name;
            $params['serverusername'] = isset($response->meta) ? $response->meta->user_name : $response->user_name;
            $params['serverpassword'] = isset($response->meta->password) ? $response->meta->password : $response->password;
            $params['serveraccesshash'] = isset($response->meta->access_hash) ? $response->meta->access_hash : $response->access_hash;
            $params['serversecure'] = ((isset($response->meta->use_ssl) && $response->meta->use_ssl == true) || (isset($response->use_ssl) && $response->use_ssl == true) ) ? '1' : '0';
            $params['serverhttpprefix'] = ((isset($response->meta->use_ssl) && $response->meta->use_ssl == true) || (isset($response->use_ssl) && $response->use_ssl == true) ) ? 'https' : 'http';
            $available_products = $available_products + $this->time4vps_ProductLoaderFunction($params); //Append products to existing Array
            $available_scripts = $available_scripts + $this->time4vps_InitScriptLoaderFunction($params); // Append init scripts to existing Array
        }
        // Set the time4vps product as a selectable option
        $product = $fields->label(Language::_('time4vps.package_fields.product', true), 'time4vps_product');
        $product->attach(
            $fields->fieldSelect(
                'meta[product]',
                $available_products,
                $this->Html->ifSet($vars->meta['product']),
                ['id' => 'time4vps_product']
            )
        );
        $fields->setField($product);

        // Set the time4vps init script as a selectable option
        $init = $fields->label(Language::_('time4vps.package_fields.init', true), 'time4vps_init');
        $init->attach(
            $fields->fieldSelect(
                'meta[init]',
                $available_scripts,
                $this->Html->ifSet($vars->meta['init']),
                ['id' => 'time4vps_init']
            )
        );
        $fields->setField($init);

        // Set the OS List
        $os_list = $fields->label(
            Language::_('time4vps.package_fields.os_list', true),
            'time4vps_os_list'
        );
        $os_list->attach(
            $fields->fieldTextarea(
                'meta[os_list]',
                $this->Html->ifSet($vars->meta['os_list']),
                ['id' => 'time4vps_os_list']
            )
        );
        $fields->setField($os_list);

        // Set OS list note
        $os_list_note = $fields->label(
            Language::_('time4vps.package_fields.os_list_note', true),
            'time4vps_os_list_note'
        );
        $fields->setField($os_list_note);

        // Set the configoption4
        $configoption4 = $fields->label(
            Language::_('time4vps.package_fields.configoption4', true),
            'time4vps_configoption4'
        );
        $configoption4->attach(
            $fields->fieldTextarea(
                'meta[configoption4]',
                $this->Html->ifSet($vars->meta['configoption4']),
                ['id' => 'time4vps_configoption4']
            )
        );
        $fields->setField($configoption4);
        // Set configoption4 note (Placeholder for future options)
        $configoption4_note = $fields->label(
            Language::_('time4vps.package_fields.configoption4_note', true),
            'time4vps_configoption4_note',
            array( 'id' => "time4vps_configoption4_note")
        );
        $fields->setField($configoption4_note);

        // Set the Component Map
        $Component_map = $fields->label(
            Language::_('time4vps.package_fields.Component_map', true),
            'time4vps_Component_map'
        );
        $Component_map->attach(
            $fields->fieldTextarea(
                'meta[Component_map]',
                $this->Html->ifSet($vars->meta['Component_map']),
                ['id' => 'time4vps_Component_map']
            )
        );
        $fields->setField($Component_map);
        // Set Component_map note
        $Component_map_note = $fields->label(
            Language::_('time4vps.package_fields.Component_map_note', true),
            'time4vps_Component_map_note',
            array( 'id' => "time4vps_Component_map_note")
        );
        $fields->setField($Component_map_note);
        //Advance mode link
        $advance_mode = $fields->label(
            Language::_('time4vps.package_fields.advance_mode', true),
            'time4vps_advance_mode',
            array( 'id' => "time4vps_advance_mode" , 'style' => "text-decoration: underline;")
        );
        $normal_mode = $fields->label(
            Language::_('time4vps.package_fields.normal_mode', true),
            'time4vps_normal_mode',
            array( 'id' => "time4vps_normal_mode" , 'style' => "text-decoration: underline; display:none;")
        );
        $fields->setField($advance_mode);
        $fields->setField($normal_mode);
        return $fields;
    }

    /**
     * Validates input data when attempting to add a package, returns the meta
     * data to save when adding a package. Performs any action required to add
     * the package on the remote server. Sets Input errors on failure,
     * preventing the package from being added.
     *
     * @param array An array of key/value pairs used to add the package
     * @return array A numerically indexed array of meta fields to be stored for this package containing:
     *  - key The key for this meta field
     *  - value The value for this key
     *  - encrypted Whether or not this field should be encrypted (default 0, not encrypted)
     * @see Module::getModule()
     * @see Module::getModuleRow()
     */
    public function addPackage(array $vars = null)
    {
        // Set rules to validate input data
        $this->Input->setRules($this->getPackageRules($vars));

        // Build meta data to return
        $meta = [];
        if ($this->Input->validates($vars)) {
            // Return all package meta fields
            foreach ($vars['meta'] as $key => $value) {
                $meta[] = [
                    'key' => $key,
                    'value' => $value,
                    'encrypted' => 0
                ];
            }
        }
        return $meta;
    }

    /**
     * Validates input data when attempting to edit a package, returns the meta
     * data to save when editing a package. Performs any action required to edit
     * the package on the remote server. Sets Input errors on failure,
     * preventing the package from being edited.
     *
     * @param stdClass $package A stdClass object representing the selected package
     * @param array An array of key/value pairs used to edit the package
     * @return array A numerically indexed array of meta fields to be stored for this package containing:
     *  - key The key for this meta field
     *  - value The value for this key
     *  - encrypted Whether or not this field should be encrypted (default 0, not encrypted)
     * @see Module::getModule()
     * @see Module::getModuleRow()
     */
    public function editPackage($package, array $vars = null)
    {
        // Set rules to validate input data
        $this->Input->setRules($this->getPackageRules($vars));

        // Build meta data to return
        $meta = [];
        if ($this->Input->validates($vars)) {
            // Return all package meta fields
            foreach ($vars['meta'] as $key => $value) {
                $meta[] = [
                    'key' => $key,
                    'value' => $value,
                    'encrypted' => 0
                ];
            }
        }
        return $meta;
    }

    /**
     * Returns the rendered view of the manage module page
     *
     * @param mixed $module A stdClass object representing the module and its rows
     * @param array $vars An array of post data submitted to or on the manager module
     *  page (used to repopulate fields after an error)
     * @return string HTML content containing information to display when viewing the manager module page
     */
    public function manageModule($module, array &$vars)
    {
        // Load the view into this object, so helpers can be automatically added to the view
        $this->view = new View('manage', 'default');
        $this->view->base_uri = $this->base_uri;
        $this->view->setDefaultView('components' . DS . 'modules' . DS . 'time4vps' . DS);
        if (isset($_GET['load_products'])) {
            $module_credentials = $module->rows[$_GET['load_products']]->meta;
            $module_id = $_GET['module_id'];
            $module_row = $_GET['module_row_id'];
            $this->AddServerPackages($module_credentials, $module_id, $module_row);
            Loader::loadHelpers($this, ['Form', 'Html', 'Widget']);
            $this->view->set('module', $module);
            return $this->view->fetch();
        }
        // Load the helpers required for this view
        Loader::loadHelpers($this, ['Form', 'Html', 'Widget']);

        $this->view->set('module', $module);

        return $this->view->fetch();
    }


    /**
     * Add Dummy Pacakages for the selected server
     *
     * @param mixed $module A stdClass object representing the module and its rows
     * @return string HTML content containing information to display when viewing the manager module page
     */
    public function AddServerPackages($module, $module_id, $module_row)
    {
        // echo 'Data: <pre>' . print_r($module, true) . '</pre>';
        $api = [
            'serverhttpprefix' => $module->use_ssl === true ? 'https' : 'http',
            'serverhostname' => $module->host_name,
            'serverusername' => $module->user_name,
            'serverpassword' => $module->password
        ];
        time4vps_InitAPI($api);
        $products = (new Product())->getAvailableVPS();
        if ($products) {
            $package_count = 0;
            Loader::loadModels($this, ['Packages']);
            foreach ($products as $key => $product) {
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
                    "qty" => 0,
                    "client_qty_unlimited" => true,
                    "client_qty" => 0,
                    "upgrades_use_renewal" => 1,
                    "module_id" => $module_id,
                    "module_row" => $module_row,
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
                    "select_group_type" => "",
                    "groups" => [],
                    "group_names" => [
                        [
                         "lang" => "en_us",
                         "name" => ""
                        ]
                    ],
                    "save" => "Create Package",
                    "company_id" => 1,
                    "taxable" => 0,
                    "single_term" => 0
                ];
                $package_id = $this->Packages->add($vars);
                if ($package_id) {
                    $package_count++;
                }
            }
        }
        echo $package_count . ' Added';
        return;
    }

    /**
     * Returns the rendered view of the add module row page
     *
     * @param array $vars An array of post data submitted to or on the add module
     *  row page (used to repopulate fields after an error)
     * @return string HTML content containing information to display when viewing the add module row page
     */
    public function manageAddRow(array &$vars)
    {
        // Load the view into this object, so helpers can be automatically added to the view
        $this->view = new View('add_row', 'default');
        $this->view->base_uri = $this->base_uri;
        $this->view->setDefaultView('components' . DS . 'modules' . DS . 'time4vps' . DS);

        // Load the helpers required for this view
        Loader::loadHelpers($this, ['Form', 'Html', 'Widget']);

        // Set unspecified checkboxes
        if (!empty($vars)) {
            if (!(isset($vars['use_ssl']) && !empty($vars['use_ssl']))) {
                $vars['use_ssl'] = 'false';
            }
        }
        $this->view->set('vars', (object)$vars);
        return $this->view->fetch();
    }

    /**
     * Returns the rendered view of the edit module row page
     *
     * @param stdClass $module_row The stdClass representation of the existing module row
     * @param array $vars An array of post data submitted to or on the edit
     *  module row page (used to repopulate fields after an error)
     * @return string HTML content containing information to display when viewing the edit module row page
     */
    public function manageEditRow($module_row, array &$vars)
    {
        // Load the view into this object, so helpers can be automatically added to the view
        $this->view = new View('edit_row', 'default');
        $this->view->base_uri = $this->base_uri;
        $this->view->setDefaultView('components' . DS . 'modules' . DS . 'time4vps' . DS);

        // Load the helpers required for this view
        Loader::loadHelpers($this, ['Form', 'Html', 'Widget']);

        if (empty($vars)) {
            $vars = $module_row->meta;
        } else {
            // Set unspecified checkboxes
            if (!(isset($vars['use_ssl']) && !empty($vars['use_ssl']))) {
                $vars['use_ssl'] = 'false';
            }
        }
        $this->view->set('vars', (object)$vars);
        return $this->view->fetch();
    }

    /**
     * Adds the module row on the remote server. Sets Input errors on failure,
     * preventing the row from being added. Returns a set of data, which may be
     * a subset of $vars, that is stored for this module row
     *
     * @param array $vars An array of module info to add
     * @return array A numerically indexed array of meta fields for the module row containing:
     *  - key The key for this meta field
     *  - value The value for this key
     *  - encrypted Whether or not this field should be encrypted (default 0, not encrypted)
     */
    public function addModuleRow(array &$vars)
    {
        // Testing whether given params are correct or not before creating server
        $params['serverhttpprefix'] = isset($vars['use_ssl']) && $vars['use_ssl'] == true ? 'https' : 'http';
        $params['serverhostname'] = $vars['host_name'];
        $params['serverusername'] = $vars['user_name'];
        $params['serverpassword'] = $vars['password'];
        $response = $this->time4vps_TestConnection($params);
        if ($response['success']) {
            $meta_fields = ['server_name', 'host_name', 'user_name', 'password', 'access_hash','use_ssl'];
            $encrypted_fields = ['password'];

            // Set unspecified checkboxes
            if (!(isset($vars['use_ssl']) && !empty($vars['use_ssl']))) {
                $vars['use_ssl'] = 'false';
            }

            $this->Input->setRules($this->getRowRules($vars));

            // Validate module row
            if ($this->Input->validates($vars)) {
                // Build the meta data for this row
                $meta = [];
                foreach ($vars as $key => $value) {
                    if (in_array($key, $meta_fields)) {
                        $meta[] = [
                            'key' => $key,
                            'value' => $value,
                            'encrypted' => in_array($key, $encrypted_fields) ? 1 : 0
                        ];
                    }
                }
                return $meta;
            }
        } else {
            $this->Input->setErrors(['api' => ['internal' => $response['error']]]);
        }
    }

    /**
     * Edits the module row on the remote server. Sets Input errors on failure,
     * preventing the row from being updated. Returns a set of data, which may be
     * a subset of $vars, that is stored for this module row
     *
     * @param stdClass $module_row The stdClass representation of the existing module row
     * @param array $vars An array of module info to update
     * @return array A numerically indexed array of meta fields for the module row containing:
     *  - key The key for this meta field
     *  - value The value for this key
     *  - encrypted Whether or not this field should be encrypted (default 0, not encrypted)
     */
    public function editModuleRow($module_row, array &$vars)
    {
        $meta_fields = ['server_name', 'host_name', 'user_name', 'password', 'access_hash','use_ssl'];
        $encrypted_fields = ['password'];

        // Set unspecified checkboxes
        if (empty($vars['use_ssl'])) {
            $vars['use_ssl'] = 'false';
        }

        $this->Input->setRules($this->getRowRules($vars));

        // Validate module row
        if ($this->Input->validates($vars)) {
            // Build the meta data for this row
            $meta = [];
            foreach ($vars as $key => $value) {
                if (in_array($key, $meta_fields)) {
                    $meta[] = [
                        'key' => $key,
                        'value' => $value,
                        'encrypted' => in_array($key, $encrypted_fields) ? 1 : 0
                    ];
                }
            }

            return $meta;
        }
    }

    /**
     * Returns the value used to identify a particular package service which has
     * not yet been made into a service. This may be used to uniquely identify
     * an uncreated services of the same package (i.e. in an order form checkout)
     *
     * @param stdClass $package A stdClass object representing the selected package
     * @param array $vars An array of user supplied info to satisfy the request
     * @return string The value used to identify this package service
     * @see Module::getServiceName()
     */
    public function getPackageServiceName($package, array $vars = null)
    {
        $domain = $this->getDomainNameFromData($package, $vars);

        return !empty($domain) ? $domain : null;
    }

    /**
     * Returns the value used to identify a particular service
     *
     * @param stdClass $service A stdClass object representing the service
     * @return string A value used to identify this service amongst other similar services
     */
    public function getServiceName($service)
    {
        // Determine the module ID so we can fetch the updated service name from it
        $r = new Record();
        $module_row_id = $r->select('id')
            ->from('module_rows')
            ->where('module_id', '=', $service->package->module_id)
            ->fetch()->id;
        // Attempt to revise the service name from the module
        if ($module_row_id) {
            $row = $this->getModuleRow($module_row_id);
            $params = [];
            $params['serverhttpprefix'] = isset($row->meta->use_ssl) && $row->meta->use_ssl == true ? 'https' : 'http';
            $params['serverhostname'] = $row->meta->host_name;
            $params['serverusername'] = $row->meta->user_name;
            $params['serverpassword'] = $row->meta->password;
            $params['service_id'] = $service->id;
            time4vps_InitAPI($params);
            $remote_service = time4vps_GetServiceDetails($params); // service details
            $service_name = is_array($remote_service) && isset($remote_service['domain']) && !empty($remote_service['domain']) ? $remote_service['domain']: 'serverhost.name';
        } else {
            $service_name = 'serverhost.name';
        }
        return $service_name;
    }

    /**
     * Returns all fields to display to an admin attempting to add a service with the module
     *
     * @param stdClass $package A stdClass object representing the selected package
     * @param $vars stdClass A stdClass object representing a set of post fields
     * @return ModuleFields A ModuleFields object, containg the fields to render
     *  as well as any additional HTML markup to include
     */
    public function getAdminAddFields($package, $vars = null)
    {
        Loader::loadHelpers($this, ['Html']);

        $fields = new ModuleFields();

        // Show the subdomain fields when we are adding a service, but not when managing a pending service
        $show_subdomains = (isset($vars->time4vps_domain) && isset($vars->time4vps_sub_domain))
            || !isset($vars->time4vps_domain);

        if ($this->Html->ifSet($package->meta->sub_domains) == 'enable' && $show_subdomains) {
            $domains = $this->getPackageAvailableDomains($package);

            // Create sub_domain label
            $sub_domain = $fields->label(Language::_('time4vps.service_field.sub_domain', true), 'time4vps_sub_domain');
            // Create sub_domain field and attach to domain label
            $sub_domain->attach(
                $fields->fieldText(
                    'time4vps_sub_domain',
                    $this->Html->ifSet($vars->time4vps_sub_domain),
                    ['id' => 'time4vps_sub_domain']
                )
            );
            // Set the label as a field
            $fields->setField($sub_domain);

            // Create domain label
            $domain = $fields->label(Language::_('time4vps.service_field.domain', true), 'time4vps_domain');
            // Create domain field and attach to domain label
            $domain->attach(
                $fields->fieldSelect(
                    'time4vps_domain',
                    $domains,
                    $this->Html->ifSet($vars->time4vps_domain),
                    ['id' => 'time4vps_domain']
                )
            );
            // Set the label as a field
            $fields->setField($domain);
        } else {
            // Create domain label
            $domain = $fields->label(Language::_('time4vps.service_field.domain', true), 'time4vps_domain');
            // Create domain field and attach to domain label
            $domain->attach(
                $fields->fieldText(
                    'time4vps_domain',
                    $this->Html->ifSet($vars->time4vps_domain),
                    ['id' => 'time4vps_domain']
                )
            );
            // Set the label as a field
            $fields->setField($domain);
        }

        // Create username label
        $username = $fields->label(Language::_('time4vps.service_field.username', true), 'time4vps_username');
        // Create username field and attach to username label
        $username->attach(
            $fields->fieldText('time4vps_username', $this->Html->ifSet($vars->time4vps_username), ['id' => 'time4vps_username'])
        );
        // Add tooltip
        $tooltip = $fields->tooltip(Language::_('time4vps.service_field.tooltip.username', true));
        $username->attach($tooltip);
        // Set the label as a field
        $fields->setField($username);

        // Create password label
        $password = $fields->label(Language::_('time4vps.service_field.password', true), 'time4vps_password');
        // Create password field and attach to password label
        $password->attach(
            $fields->fieldPassword(
                'time4vps_password',
                ['id' => 'time4vps_password', 'value' => $this->Html->ifSet($vars->time4vps_password)]
            )
        );
        // Add tooltip
        $tooltip = $fields->tooltip(Language::_('time4vps.service_field.tooltip.password', true));
        $password->attach($tooltip);
        // Set the label as a field
        $fields->setField($password);

        // Confirm password label
        $confirm_password = $fields->label(
            Language::_('time4vps.service_field.confirm_password', true),
            'time4vps_confirm_password'
        );
        // Create confirm password field and attach to password label
        $confirm_password->attach(
            $fields->fieldPassword(
                'time4vps_confirm_password',
                ['id' => 'time4vps_confirm_password', 'value' => $this->Html->ifSet($vars->time4vps_password)]
            )
        );
        // Add tooltip
        $tooltip = $fields->tooltip(Language::_('time4vps.service_field.tooltip.password', true));
        $confirm_password->attach($tooltip);
        // Set the label as a field
        $fields->setField($confirm_password);

        return $fields;
    }

    /**
     * Returns all fields to display to a client attempting to add a service with the module
     *
     * @param stdClass $package A stdClass object representing the selected package
     * @param $vars stdClass A stdClass object representing a set of post fields
     * @return ModuleFields A ModuleFields object, containg the fields to render as well
     *  as any additional HTML markup to include
     */
    public function getClientAddFields($package, $vars = null)
    {
        Loader::loadHelpers($this, ['Html']);

        $fields = new ModuleFields();

        if ($this->Html->ifSet($package->meta->sub_domains) == 'enable') {
            $domains = $this->getPackageAvailableDomains($package);

            // Create sub_domain label
            $sub_domain = $fields->label(Language::_('time4vps.service_field.sub_domain', true), 'time4vps_sub_domain');
            // Create sub_domain field and attach to domain label
            $sub_domain->attach(
                $fields->fieldText(
                    'time4vps_sub_domain',
                    $this->Html->ifSet($vars->time4vps_sub_domain),
                    ['id' => 'time4vps_sub_domain']
                )
            );

            // Set the label as a field
            $fields->setField($sub_domain);

            // Create domain label
            $domain = $fields->label(Language::_('time4vps.service_field.domain', true), 'time4vps_domain');
            // Create domain field and attach to domain label
            $domain->attach(
                $fields->fieldSelect(
                    'time4vps_domain',
                    $domains,
                    $this->Html->ifSet($vars->time4vps_domain),
                    ['id' => 'time4vps_domain']
                )
            );
            // Set the label as a field
            $fields->setField($domain);
        } else {
            // Create domain label
            $domain = $fields->label(Language::_('time4vps.service_field.domain', true), 'time4vps_domain');
            // Create domain field and attach to domain label
            $domain->attach(
                $fields->fieldText(
                    'time4vps_domain',
                    $this->Html->ifSet($vars->time4vps_domain, $this->Html->ifSet($vars->domain)),
                    ['id' => 'time4vps_domain']
                )
            );
            // Set the label as a field
            $fields->setField($domain);
        }

        return $fields;
    }

    /**
     * Returns all fields to display to an admin attempting to edit a service with the module
     *
     * @param stdClass $package A stdClass object representing the selected package
     * @param $vars stdClass A stdClass object representing a set of post fields
     * @return ModuleFields A ModuleFields object, containg the fields to render as
     *  well as any additional HTML markup to include
     */
    public function getAdminEditFields($package, $vars = null)
    {
        Loader::loadHelpers($this, ['Html']);

        $fields = new ModuleFields();

        // Create domain label
        $domain = $fields->label(Language::_('time4vps.service_field.domain', true), 'time4vps_domain');
        // Create domain field and attach to domain label
        $domain->attach(
            $fields->fieldText('time4vps_domain', $this->Html->ifSet($vars->time4vps_domain), ['id' => 'time4vps_domain'])
        );
        // Set the label as a field
        $fields->setField($domain);

        // Create username label
        $username = $fields->label(Language::_('time4vps.service_field.username', true), 'time4vps_username');
        // Create username field and attach to username label
        $username->attach(
            $fields->fieldText('time4vps_username', $this->Html->ifSet($vars->time4vps_username), ['id' => 'time4vps_username'])
        );
        // Set the label as a field
        $fields->setField($username);

        // Create password label
        $password = $fields->label(Language::_('time4vps.service_field.password', true), 'time4vps_password');
        // Create password field and attach to password label
        $password->attach(
            $fields->fieldPassword(
                'time4vps_password',
                ['id' => 'time4vps_password', 'value' => $this->Html->ifSet($vars->time4vps_password)]
            )
        );
        // Set the label as a field
        $fields->setField($password);

        return $fields;
    }

    /**
     * Attempts to validate service info. This is the top-level error checking method. Sets Input errors on failure.
     *
     * @param stdClass $package A stdClass object representing the selected package
     * @param array $vars An array of user supplied info to satisfy the request
     * @return bool True if the service validates, false otherwise. Sets Input errors when false.
     */
    public function validateService($package, array $vars = null)
    {
        $this->Input->setRules($this->getServiceRules($vars, $package));
        return $this->Input->validates($vars);
    }

    /**
     * Attempts to validate an existing service against a set of service info updates. Sets Input errors on failure.
     *
     * @param stdClass $service A stdClass object representing the service to validate for editing
     * @param array $vars An array of user-supplied info to satisfy the request
     * @return bool True if the service update validates or false otherwise. Sets Input errors when false.
     */
    public function validateServiceEdit($service, array $vars = null)
    {
        $this->Input->setRules($this->getServiceRules($vars, $service->package, true));
        return $this->Input->validates($vars);
    }

    /**
     * Returns the rule set for adding/editing a service
     *
     * @param array $vars A list of input vars
     * @param stdClass $package The service package
     * @param bool $edit True to get the edit rules, false for the add rules
     * @return array Service rules
     */
    private function getServiceRules(array $vars = null, stdClass $package = null, $edit = false)
    {
        $rules = [
            'time4vps_username' => [
                'format' => [
                    'if_set' => true,
                    'rule' => ['matches', '/^[a-z]([a-z0-9])*$/i'],
                    'message' => Language::_('time4vps.!error.time4vps_username.format', true)
                ],
                'test' => [
                    'if_set' => true,
                    'rule' => ['matches', '/^(?!test)/'],
                    'message' => Language::_('time4vps.!error.time4vps_username.test', true)
                ],
                'length' => [
                    'if_set' => true,
                    'rule' => ['betweenLength', 1, 16],
                    'message' => Language::_('time4vps.!error.time4vps_username.length', true)
                ]
            ],
            'time4vps_password' => [
                'valid' => [
                    'if_set' => true,
                    'rule' => ['isPassword', 8],
                    'message' => Language::_('time4vps.!error.time4vps_password.valid', true),
                    'last' => true
                ],
            ],
            'time4vps_confirm_password' => [
                'matches' => [
                    'if_set' => true,
                    'rule' => ['compares', '==', (isset($vars['time4vps_password']) ? $vars['time4vps_password'] : '')],
                    'message' => Language::_('time4vps.!error.time4vps_password.matches', true)
                ]
            ]
        ];

        if (!isset($vars['time4vps_domain']) || strlen($vars['time4vps_domain']) < 4) {
            unset($rules['time4vps_domain']['test']);
        }

        // Set the values that may be empty
        $empty_values = ['time4vps_username', 'time4vps_password'];

        if ($edit) {
            // If this is an edit and no password given then don't evaluate password
            // since it won't be updated
            if (!array_key_exists('time4vps_password', $vars) || $vars['time4vps_password'] == '') {
                unset($rules['time4vps_password']);
            }

            // Validate domain if given
            $rules['time4vps_domain']['format']['if_set'] = true;

            if (isset($rules['time4vps_domain']['test'])) {
                $rules['time4vps_domain']['test']['if_set'] = true;
            }
        }

        // Remove rules on empty fields
        foreach ($empty_values as $value) {
            if (empty($vars[$value])) {
                unset($rules[$value]);
            }
        }

        return $rules;
    }

    /**
     * Retrieves the domain name from the given vars for this package
     *
     * @param stdClass $package An stdClass object representing the package
     * @param array $vars An array of input data including:
     *  - time4vps_domain The time4vps domain name
     *  - time4vps_sub_domain The time4vps sub domain (optional)
     * @return string The name of the domain name
     */
    private function getDomainNameFromData(stdClass $package, array $vars = null)
    {
        Loader::loadHelpers($this, ['Html']);

        $name = $this->formatDomain($this->Html->ifSet($vars['time4vps_domain']));

        return $name;
    }

    /**
     * Adds the service to the remote server. Sets Input errors on failure,
     * preventing the service from being added.
     *
     * @param stdClass $package A stdClass object representing the selected package
     * @param array $vars An array of user supplied info to satisfy the request
     * @param stdClass $parent_package A stdClass object representing the parent
     *  service's selected package (if the current service is an addon service)
     * @param stdClass $parent_service A stdClass object representing the parent
     *  service of the service being added (if the current service is an addon service
     *  service and parent service has already been provisioned)
     * @param string $status The status of the service being added. These include:
     *  - active
     *  - canceled
     *  - pending
     *  - suspended
     * @return array A numerically indexed array of meta fields to be stored for this service containing:
     *  - key The key for this meta field
     *  - value The value for this key
     *  - encrypted Whether or not this field should be encrypted (default 0, not encrypted)
     * @see Module::getModule()
     * @see Module::getModuleRow()
     */
    public function addService($package, array $vars = null, $parent_package = null, $parent_service = null, $status = 'pending')
    {
        $row = $this->getModuleRow();
        if (!$row) {
            $this->Input->setErrors(
                ['module_row' => ['missing' => Language::_('time4vps.!error.module_row.missing', true)]]
            );
            return;
        }
        //setting params for API init
        $vars['time4vps_serverhttpprefix'] = isset($row->meta->use_ssl) && $row->meta->use_ssl == true ? 'https' : 'http';
        $vars['time4vps_serverhostname'] = $row->meta->host_name;
        $vars['time4vps_serverusername'] = $row->meta->user_name;
        $vars['time4vps_serverpassword'] = $row->meta->password;
        $vars['time4vps_product_id'] = $package->meta->product;
        $vars['time4vps_billing_cycle'] = $package->pricing[0]->period;
        // Generate username/password
        if (array_key_exists('time4vps_domain', $vars)) {
            Loader::loadModels($this, ['Clients','Packages']);

            // Strip "www." from beginning of domain if present

            $vars['time4vps_domain'] = $this->formatDomain($vars['time4vps_domain']);
            // Get the formatted domain name
            $domain = $this->getDomainNameFromData($package, $vars);

            // Generate a username
            if (empty($vars['time4vps_username'])) {
                $vars['time4vps_username'] = $this->generateUsername($domain);
            }

            // Generate a password
            if (empty($vars['time4vps_password'])) {
                $vars['time4vps_password'] = $this->generatePassword();
                $vars['time4vps_confirm_password'] = $vars['time4vps_password'];
            }

            // Use client's email address
            if (isset($vars['client_id']) && ($client = $this->Clients->get($vars['client_id'], false))) {
                $vars['time4vps_email'] = $client->email;
            }
        }
        $params = $this->getFieldsFromInput((array)$vars);
        $this->validateService($package, $vars);

        if ($this->Input->errors()) {
            return;
        }

        // Only provision the service if 'use_module' is true
        $result = null;
        if ($vars['use_module'] == 'true') {
            $masked_params = $params;
            $masked_params['password'] = '***';
            $this->log($row->meta->host_name . '|createacct', serialize($masked_params), 'input', true);
            unset($masked_params);
            if (isset($vars['service_id']) && $vars['service_id'] != '') {
                $params['service_id'] = $vars['service_id'];
                $result = time4vps_CreateAccount($params);
            } else {
                /* functionality Explained
                    if service id is not set, it means service is not stored in db yet, so we will wait for service.add trigger
                    see below get_Service_id function definition, which create account on server when service id is returned.
                */
                $eventFactory = new \Blesta\Core\Util\Events\EventFactory();
                $eventListener = $eventFactory->listener();
                $eventListener->register('Services.add', [$this, 'get_Service_id']);
            }

            if ($this->Input->errors()) {
                return;
            }
        }
        // Return service fields
        return [
            [
                'key' => 'time4vps_domain',
                'value' => !empty($params['domain']) ? $params['domain'] : 'serverhost.name',
                'encrypted' => 0
            ],
            [
                'key' => 'time4vps_username',
                'value' => $params['username'],
                'encrypted' => 0
            ],
            [
                'key' => 'time4vps_password',
                'value' => $params['password'],
                'encrypted' => 1
            ],
            [
                'key' => 'time4vps_confirm_password',
                'value' => $params['password'],
                'encrypted' => 1
            ]
        ];
    }

    /**
     * This function is called by event trigger when service is added to return service id
     * Service id is then used in creater server API to create server on remote Blesta server
     * $event contains all event related information
     */
    public function get_Service_id($event)
    {
        $event = $event->getParams();                                           // to get event params
        $service_id = $event['service_id'];                                     // service id of last added service
        $service = $event['vars'];                                              // service object of last added service
        $module = $this->getModuleRow();                                        // return current module, which will be used to fetch remote server details
        $package = $this->Packages->getByPricingId($service['pricing_id']);     // return package on the basis of pricing id of current service
        $vars['time4vps_serverhttpprefix'] = isset($module->meta->use_ssl) && $module->meta->use_ssl == true ? 'https' : 'http';
        $vars['time4vps_serverhostname'] = $module->meta->host_name;
        $vars['time4vps_serverusername'] = $module->meta->user_name;
        $vars['time4vps_serverpassword'] = $module->meta->password;
        $vars['time4vps_product_id'] = $package->meta->product;
        $vars['time4vps_billing_cycle'] = $package->pricing[0]->period;
        $vars['time4vps_domain'] = $this->formatDomain($service['time4vps_domain']);
        $vars['time4vps_username'] = $service['time4vps_username'];
        $vars['time4vps_password'] = $service['time4vps_password'];
        $params = $this->getFieldsFromInput((array)$vars);
        $params['service_id'] = $service_id;
        $result = time4vps_CreateAccount($params); // formed all required params and now we can create account or order a new service on remote server
    }

    /**
     * Edits the service on the remote server. Sets Input errors on failure,
     * preventing the service from being edited.
     *
     * @param stdClass $package A stdClass object representing the current package
     * @param stdClass $service A stdClass object representing the current service
     * @param array $vars An array of user supplied info to satisfy the request
     * @param stdClass $parent_package A stdClass object representing the parent
     *  service's selected package (if the current service is an addon service)
     * @param stdClass $parent_service A stdClass object representing the parent
     *  service of the service being edited (if the current service is an addon service)
     * @return array A numerically indexed array of meta fields to be stored for this service containing:
     *  - key The key for this meta field
     *  - value The value for this key
     *  - encrypted Whether or not this field should be encrypted (default 0, not encrypted)
     * @see Module::getModule()
     * @see Module::getModuleRow()
     */
    public function editService($package, $service, array $vars = null, $parent_package = null, $parent_service = null)
    {
        $row = $this->getModuleRow();

        $this->validateServiceEdit($service, $vars);

        // Strip "www." from beginning of domain if present
        if (isset($vars['time4vps_domain'])) {
            $vars['time4vps_domain'] = $this->formatDomain($vars['time4vps_domain']);
        }

        if ($this->Input->errors()) {
            return;
        }

        $service_fields = $this->serviceFieldsToObject($service->fields);

        // Remove password if not being updated
        if (isset($vars['time4vps_password']) && $vars['time4vps_password'] == '') {
            unset($vars['time4vps_password']);
        }

        // Only update the service if 'use_module' is true
        if ($vars['use_module'] == 'true') {
            // Check for fields that changed
            $delta = [];
            foreach ($vars as $key => $value) {
                if (!array_key_exists($key, $service_fields) || $vars[$key] != $service_fields->$key) {
                    $delta[$key] = $value;
                }
            }

            // Update domain (if changed)
            if (isset($delta['time4vps_domain'])) {
                $params = ['domain' => $delta['time4vps_domain']];

                $this->log($row->meta->host_name . '|modifyacct', serialize($params), 'input', true);
                $result = $this->parseResponse($api->modifyacct($service_fields->time4vps_username, $params));
            }

            // Update password (if changed)
            if (isset($delta['time4vps_password'])) {
                $this->log($row->meta->host_name . '|passwd', '***', 'input', true);
                $result = $this->parseResponse(
                    $api->passwd($service_fields->time4vps_username, $delta['time4vps_password'])
                );
            }

            // Update username (if changed), do last so we can always rely on
            // $service_fields['time4vps_username'] to contain the username
            if (isset($delta['time4vps_username'])) {
                $params = ['newuser' => $delta['time4vps_username']];
                $this->log($row->meta->host_name . '|modifyacct', serialize($params), 'input', true);
                $result = $this->parseResponse($api->modifyacct($service_fields->time4vps_username, $params));
            }
        }

        // Set fields to update locally
        $fields = ['time4vps_domain', 'time4vps_username', 'time4vps_password'];
        foreach ($fields as $field) {
            if (property_exists($service_fields, $field) && isset($vars[$field])) {
                $service_fields->{$field} = $vars[$field];
            }
        }

        // Set the confirm password to the password
        $service_fields->time4vps_confirm_password = $service_fields->time4vps_password;

        // Return all the service fields
        $fields = [];
        $encrypted_fields = ['time4vps_password', 'time4vps_confirm_password'];
        foreach ($service_fields as $key => $value) {
            $fields[] = ['key' => $key, 'value' => $value, 'encrypted' => (in_array($key, $encrypted_fields) ? 1 : 0)];
        }

        return $fields;
    }

    /**
     * Cancels the service on the remote server. Sets Input errors on failure,
     * preventing the service from being canceled.
     *
     * @param stdClass $package A stdClass object representing the current package
     * @param stdClass $service A stdClass object representing the current service
     * @param stdClass $parent_package A stdClass object representing the parent
     *  service's selected package (if the current service is an addon service)
     * @param stdClass $parent_service A stdClass object representing the parent
     *  service of the service being canceled (if the current service is an addon service)
     * @return mixed null to maintain the existing meta fields or a numerically
     *  indexed array of meta fields to be stored for this service containing:
     *  - key The key for this meta field
     *  - value The value for this key
     *  - encrypted Whether or not this field should be encrypted (default 0, not encrypted)
     * @see Module::getModule()
     * @see Module::getModuleRow()
     */
    public function cancelService($package, $service, $parent_package = null, $parent_service = null)
    {
        if (($row = $this->getModuleRow())) {
            $service_fields = $this->serviceFieldsToObject($service->fields);
            // construct params for terminate account API
            $params['serverhttpprefix'] = isset($row->meta->use_ssl) && $row->meta->use_ssl == true ? 'https' : 'http';
            $params['serverhostname'] = $row->meta->host_name;
            $params['serverusername'] = $row->meta->user_name;
            $params['serverpassword'] = $row->meta->password;
            $params = array('accountid' => $service->id_value, 'serviceid' => $service->id,'userid' => $service->client_id, 'domain' => $service_fields->time4vps_domain, 'username' => $service_fields->time4vps_username, 'password' => $service_fields->time4vps_password, 'packageid' => $service->package->id, 'status' => $service->package->status, 'type' =>  'server', 'producttype' => 'server', 'moduletype' => 'time4vps', 'configoption3' => $service->package->meta->os_list);
            return time4vps_TerminateAccount($params);
        }
        return null;
    }

    /**
     * Fetches the HTML content to display when viewing the service info in the
     * admin interface.
     *
     * @param stdClass $service A stdClass object representing the service
     * @param stdClass $package A stdClass object representing the service's package
     * @return string HTML content containing information to display when viewing the service info
     */
    public function getAdminServiceInfo($service, $package)
    {
        $row = $this->getModuleRow();

        // Load the view into this object, so helpers can be automatically added to the view
        $this->view = new View('admin_service_info', 'default');
        $this->view->base_uri = $this->base_uri;
        $this->view->setDefaultView('components' . DS . 'modules' . DS . 'time4vps' . DS);

        $row = $this->getModuleRow();
        $params = [];
        $params['serverhttpprefix'] = isset($row->meta->use_ssl) && $row->meta->use_ssl == true ? 'https' : 'http';
        $params['serverhostname'] = $row->meta->host_name;
        $params['serverusername'] = $row->meta->user_name;
        $params['serverpassword'] = $row->meta->password;
        $params['service_id'] = $service->id;
        // Load the helpers required for this view
        Loader::loadHelpers($this, ['Form', 'Html']);
        $last_result = null;
        $error = null;
        $remote_server = $remote_service = array();
        try {
            time4vps_InitAPI($params);
            $remote_server = time4vps_GetServerDetails($params); // server details
            $remote_service = time4vps_GetServiceDetails($params); // service details
        } catch (InvalidTaskException $e) {
            // No tasks yet
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
        $this->view->set('remote_server', $remote_server);
        $this->view->set('remote_service', $remote_service);
        return $this->view->fetch();
    }

    /**
     * Fetches the HTML content to display when viewing the service info in the
     * client interface.
     *
     * @param stdClass $service A stdClass object representing the service
     * @param stdClass $package A stdClass object representing the service's package
     * @return string HTML content containing information to display when viewing the service info
     */
    public function getClientServiceInfo($service, $package)
    {
        $row = $this->getModuleRow();
        // Load the view into this object, so helpers can be automatically added to the view
        $this->view = new View('client_service_info', 'default');
        $this->view->base_uri = $this->base_uri;
        $this->view->setDefaultView('components' . DS . 'modules' . DS . 'time4vps' . DS);

        // Load the helpers required for this view
        Loader::loadHelpers($this, ['Form', 'Html']);

        $row = $this->getModuleRow();
        $params = [];
        $params['serverhttpprefix'] = isset($row->meta->use_ssl) && $row->meta->use_ssl == true ? 'https' : 'http';
        $params['serverhostname'] = $row->meta->host_name;
        $params['serverusername'] = $row->meta->user_name;
        $params['serverpassword'] = $row->meta->password;
        $params['service_id'] = $service->id;
        // Load the helpers required for this view
        Loader::loadHelpers($this, ['Form', 'Html']);
        $last_result = null;
        $error = null;
        $remote_server = $remote_service = array();
        try {
            time4vps_InitAPI($params);
            $remote_server = time4vps_GetServerDetails($params); // server details
            $remote_service = time4vps_GetServiceDetails($params); // service details
        } catch (InvalidTaskException $e) {
            // No tasks yet
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
        $this->view->set('remote_server', $remote_server);
        $this->view->set('remote_service', $remote_service);
        return $this->view->fetch();
    }

    /**
     * Validates that the given hostname is valid
     *
     * @param string $host_name The host name to validate
     * @return bool True if the hostname is valid, false otherwise
     */
    public function validateHostName($host_name)
    {
        $validator = new Server();
        return $validator->isDomain($host_name) || $validator->isIp($host_name);
    }

    /**
     * Validates that at least 2 name servers are set in the given array of name servers
     *
     * @param array $name_servers An array of name servers
     * @return bool True if the array count is >= 2, false otherwise
     */
    public function validateNameServerCount($name_servers)
    {
        if (is_array($name_servers) && count($name_servers) >= 2) {
            return true;
        }
        return false;
    }

    /**
     * Validates that the nameservers given are formatted correctly
     *
     * @param array $name_servers An array of name servers
     * @return bool True if every name server is formatted correctly, false otherwise
     */
    public function validateNameServers($name_servers)
    {
        if (is_array($name_servers)) {
            foreach ($name_servers as $name_server) {
                if (!$this->validateHostName($name_server)) {
                    return false;
                }
            }
        }
        return true;
    }

    /**
     * Validates whether or not the connection details are valid by attempting to fetch
     * the number of accounts that currently reside on the server
     *
     * @return bool True if the connection is valid, false otherwise
     */
    public function validateConnection($key, $host_name, $user_name, $use_ssl, &$account_count)
    {
        try {
            $api = $this->getApi($host_name, $user_name, $key, $use_ssl);

            $count = $this->getAccountCount($api);
            if ($count !== false) {
                $account_count = $count;
                return true;
            }
        } catch (Exception $e) {
            // Trap any errors encountered, could not validate connection
        }
        return false;
    }

    /**
     * Generates a username from the given host name
     *
     * @param string $host_name The host name to use to generate the username
     * @return string The username generated from the given hostname
     */
    private function generateUsername($host_name)
    {
        // Remove everything except letters and numbers from the domain
        $username = preg_replace('/[^a-z0-9]/i', '', $host_name);

        // Remove the 'test' string if it appears in the beginning
        if (strpos($username, 'test') === 0) {
            $username = substr($username, 4);
        }

        // Ensure no number appears in the beginning
        $username = ltrim($username, '0123456789');

        $length = strlen($username);
        $pool = 'abcdefghijklmnopqrstuvwxyz0123456789';
        $pool_size = strlen($pool);

        if ($length < 5) {
            for ($i = $length; $i < 8; $i++) {
                $username .= substr($pool, mt_rand(0, $pool_size - 1), 1);
            }
            $length = strlen($username);
        }

        $username = substr($username, 0, min($length, 8));

        return $username;
    }

    /**
     * Retrieves all of the available domains for subdomain provisioning for a specific package
     *
     * @param stdClass $package A stdClass object representing the selected package
     * @return mixed A key/value array of available domains
     */
    private function getPackageAvailableDomains(stdClass $package)
    {
        if (!empty($package->meta->domains_list)) {
            return $this->parseElementsFromCsv($package->meta->domains_list);
        }

        return [];
    }

    /**
     * Parses out the given elements from a CSV
     *
     * @param string $csv The CSV list
     * @return array An array of elements from the list
     */
    private function parseElementsFromCsv($csv)
    {
        $items = [];

        foreach (explode(',', $csv) as $item) {
            $item = strtolower(trim($item));

            // Skip any blank items
            if (empty($item)) {
                continue;
            }

            $items[$item] = $item;
        }

        return $items;
    }

    /**
     * Generates a password
     *
     * @param int $min_length The minimum character length for the password (5 or larger)
     * @param int $max_length The maximum character length for the password (14 or fewer)
     * @return string The generated password
     */
    private function generatePassword($min_length = 10, $max_length = 14)
    {
        $pool = 'abcdefghijklmnopqrstuvwxyz0123456789!@#$%^&*()';
        $pool_size = strlen($pool);
        $length = mt_rand(max($min_length, 5), min($max_length, 14));
        $password = '';

        for ($i = 0; $i < $length; $i++) {
            $password .= substr($pool, mt_rand(0, $pool_size - 1), 1);
        }

        return $password;
    }

    /**
     * Returns an array of service field to set for the service using the given input
     *
     * @param array $vars An array of key/value input pairs
     * @param stdClass $package A stdClass object representing the package for the service
     * @return array An array of key/value pairs representing service fields
     */
    private function getFieldsFromInput(array $vars)
    {
        $fields = [
            'domain' => isset($vars['time4vps_domain']) ? $vars['time4vps_domain'] : null,
            'username' => isset($vars['time4vps_username']) ? $vars['time4vps_username'] : null,
            'password' => isset($vars['time4vps_password']) ? $vars['time4vps_password'] : null,
            'contactemail' => isset($vars['time4vps_email']) ? $vars['time4vps_email'] : null,
            'serverhttpprefix' => isset($vars['time4vps_serverhttpprefix']) ? $vars['time4vps_serverhttpprefix'] : null,
            'serverhostname' => isset($vars['time4vps_serverhostname']) ? $vars['time4vps_serverhostname'] : null,
            'serverusername' => isset($vars['time4vps_serverusername']) ? $vars['time4vps_serverusername'] : null,
            'serverpassword' => isset($vars['time4vps_serverpassword']) ? $vars['time4vps_serverpassword'] : null,
            'product_id' => isset($vars['time4vps_product_id']) ? $vars['time4vps_product_id'] : null,
            'billing_cycle' => isset($vars['time4vps_billing_cycle']) ? $vars['time4vps_billing_cycle'] : null,
        ];

        return $fields;
    }

    /**
     * Parses the response from the API into a stdClass object
     *
     * @param string $response The response from the API
     * @return stdClass A stdClass object representing the response, void if the response was an error
     */
    private function parseResponse($response)
    {
        $row = $this->getModuleRow();

        $result = json_decode($response);
        $success = true;

        // Set internal error
        if (!$result) {
            $this->Input->setErrors(['api' => ['internal' => Language::_('time4vps.!error.api.internal', true)]]);
            $success = false;
        }

        // Only some API requests return status, so only use it if its available
        if (isset($result->status) && $result->status == 0) {
            $this->Input->setErrors(['api' => ['result' => $result->statusmsg]]);
            $success = false;
        } elseif (isset($result->result) && is_array($result->result) && isset($result->result[0]->status) && $result->result[0]->status == 0) {
            $this->Input->setErrors(['api' => ['result' => $result->result[0]->statusmsg]]);
            $success = false;
        } elseif (isset($result->passwd) && is_array($result->passwd) && isset($result->passwd[0]->status) && $result->passwd[0]->status == 0) {
            $this->Input->setErrors(['api' => ['result' => $result->passwd[0]->statusmsg]]);
            $success = false;
        } elseif (isset($result->time4vpsresult) && !empty($result->time4vpsresult->error)) {
            $this->Input->setErrors(
                [
                    'api' => [
                        'error' => (isset($result->time4vpsresult->data->reason)
                            ? $result->time4vpsresult->data->reason
                            : $result->time4vpsresult->error
                        )
                    ]
                ]
            );
            $success = false;
        }

        $sensitive_data = ['/PassWord:.*?(\\\\n)/i'];
        $replacements = ['PassWord: *****${1}'];

        // Log the response
        $this->log($row->meta->host_name, preg_replace($sensitive_data, $replacements, $response), 'output', $success);

        // Return if any errors encountered
        if (!$success) {
            return;
        }

        return $result;
    }

    /**
     * Fetches a listing of all packages configured in time4vps for the given server
     *
     * @param stdClass $module_row A stdClass object representing a single server
     * @return array An array of packages in key/value pair
     */
    private function gettime4vpsPackages($module_row)
    {
        if (!isset($this->DataStructure)) {
            Loader::loadHelpers($this, ['DataStructure']);
        }
        if (!isset($this->ArrayHelper)) {
            $this->ArrayHelper = $this->DataStructure->create('Array');
        }

        $packages = [];

        try {
            $this->log($module_row->meta->host_name . '|listpkgs', null, 'input', true);
            $package_list = $api->listpkgs();
            $result = json_decode($package_list);

            $success = false;
            if (isset($result->package)) {
                $success = true;
                $packages = $this->ArrayHelper->numericToKey($result->package, 'name', 'name');
            }

            $this->log($module_row->meta->host_name, $package_list, 'output', $success);
        } catch (Exception $e) {
            // API request failed
        }

        return $packages;
    }

    /**
     * Removes the www. from a domain name
     *
     * @param string $domain A domain name
     * @return string The domain name after the www. has been removed
     */
    private function formatDomain($domain)
    {
        return strtolower(preg_replace('/^\s*www\./i', '', $domain));
    }

    /**
     * Builds and returns the rules required to add/edit a module row (e.g. server)
     *
     * @param array $vars An array of key/value data pairs
     * @return array An array of Input rules suitable for Input::setRules()
     */
    private function getRowRules(&$vars)
    {
        $rules = [
            'server_name' => [
                'valid' => [
                    'rule' => 'isEmpty',
                    'negate' => true,
                    'message' => Language::_('time4vps.!error.server_name_valid', true)
                ]
            ],
            'host_name' => [
                'valid' => [
                    'rule' => [[$this, 'validateHostName']],
                    'message' => Language::_('time4vps.!error.host_name_valid', true)
                ]
            ],
            'user_name' => [
                'valid' => [
                    'rule' => 'isEmpty',
                    'negate' => true,
                    'message' => Language::_('time4vps.!error.user_name_valid', true)
                ]
            ],
            'password' => [
                'valid' => [
                    'rule' => 'isEmpty',
                    'negate' => true,
                    'message' => Language::_('time4vps.!error.password_valid', true)
                ]
            ]
        ];

        return $rules;
    }

    /**
     * Builds and returns rules required to be validated when adding/editing a product
     *
     * @param array $vars An array of key/value data pairs
     * @return array An array of Input rules suitable for Input::setRules()
     */
    private function getPackageRules($vars)
    {
        $rules = [
            'meta[product]' => [
                'empty' => [
                    'rule' => 'isEmpty',
                    'negate' => true,
                    'message' => Language::_('time4vps.!error.meta[product].empty', true) // product must be given
                ]
            ]
        ];

        return $rules;
    }
}
