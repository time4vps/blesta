<?php

/**
 * en_us language for the time4vps module
 */

// Basics
$lang['time4vps.name'] = 'Time4VPS';
$lang['time4vps.description'] = 'Time4VPS modules allows you to resell Time4VPS servers and manage them in your Blesta instance.';
$lang['time4vps.module_row'] = 'Server';
$lang['time4vps.module_row_plural'] = 'Servers';
$lang['time4vps.module_group'] = 'Server Group';
$lang['time4vps.tab_stats'] = 'Statistics';
$lang['time4vps.tab_client_stats'] = 'Statistics';
$lang['time4vps.tab_client_actions'] = 'Actions';
$lang['time4vps.tab_service_stats'] = 'Statistics';
$lang['time4vps.tab_client_reboot'] = 'Reboot';
$lang['time4vps.tab_client_reset_password'] = 'Reset Password';
$lang['time4vps.tab_client_change_hostname'] = 'Change Hostname';
$lang['time4vps.tab_client_reinstall'] = 'Reinstall';
$lang['time4vps.tab_client_emergency_console'] = 'Emergency Console';
$lang['time4vps.tab_client_change_dns'] = 'Change DNS';
$lang['time4vps.tab_client_reset_firewall'] = 'Reset Firewall';
$lang['time4vps.tab_client_change_ptr'] = 'Change PTR';
$lang['time4vps.tab_client_request_cancellation'] = 'Request Cancellation';
$lang['time4vps.tab_usage_graph'] = 'Usage Graph';
$lang['time4vps.tab_usage_history'] = 'Usage History';

// Module management
$lang['time4vps.add_module_row'] = 'Add Server';
$lang['time4vps.manage.module_rows_title'] = 'Servers';
$lang['time4vps.manage.module_rows_heading.name'] = 'Server Label';
$lang['time4vps.manage.module_rows_heading.hostname'] = 'Hostname';
$lang['time4vps.manage.module_rows_heading.options'] = 'Options';
$lang['time4vps.manage.module_rows.edit'] = 'Edit';
$lang['time4vps.manage.module_rows.load_packages'] = 'Load Dummy Packages';
$lang['time4vps.manage.module_rows.delete'] = 'Delete';
$lang['time4vps.manage.module_rows.confirm_delete'] = 'Are you sure you want to delete this server?';
$lang['time4vps.manage.module_rows_no_results'] = 'There are no servers.';

// Add/Edit row
$lang['time4vps.add_row.box_title'] = 'Add Time4VPS Server';
$lang['time4vps.add_row.basic_title'] = 'Basic Settings';
$lang['time4vps.add_row.add_btn'] = 'Save Server';
$lang['time4vps.edit_row.box_title'] = 'Edit time4vps Server';
$lang['time4vps.edit_row.basic_title'] = 'Basic Settings';
$lang['time4vps.edit_row.add_btn'] = 'Update Server';
$lang['time4vps.row_meta.server_name'] = 'Server Label';
$lang['time4vps.row_meta.host_name'] = 'Hostname';
$lang['time4vps.row_meta.user_name'] = 'User Name';
$lang['time4vps.row_meta.password'] = 'password';
$lang['time4vps.row_meta.access_hash'] = 'Access Hash';
$lang['time4vps.row_meta.key'] = 'Token (or Remote Key)';
$lang['time4vps.row_meta.use_ssl'] = 'Tick to use SSL Mode for Connections';

// Product fields
$lang['time4vps.package_fields.product'] = 'Products';
$lang['time4vps.package_fields.init'] = 'init Script';
$lang['time4vps.package_fields.os_list'] = 'OS List';
$lang['time4vps.package_fields.os_list_note'] = 'OS list visible to customer (each in new line)';
$lang['package_fields.Component_map'] = 'Component Map';
$lang['package_fields.Component_map_note'] = 'JSON formated object (Blesta component ID = Time4VPS component ID)';
$lang['package_fields.configoption4'] = 'configoption4';
$lang['time4vps.package_fields.configoption4_note'] = 'Placeholder for future options';
$lang['time4vps.package_fields.advance_mode'] = 'Switch to Advance mode';
$lang['time4vps.package_fields.normal_mode'] = 'Switch to Normal mode';

// Service fields
$lang['time4vps.service_field.domain'] = 'Domain';
$lang['time4vps.service_field.sub_domain'] = 'Sub-Domain';
$lang['time4vps.service_field.username'] = 'Username';
$lang['time4vps.service_field.password'] = 'Password';
$lang['time4vps.service_field.confirm_password'] = 'Confirm Password';


// Client actions request Cancel
$lang['time4vps.tab_request_cancel.title'] = 'Service Cancelation Request';
$lang['time4vps.tab_request_cancel.field_time4vps_reason'] = 'Briefly Describe your reason for Cancellation';
$lang['time4vps.tab_request_cancel.field_time4vps_cancellation_type'] = 'Cancellation Type';
$lang['time4vps.tab_request_cancel.field_reques_cancel_submit'] = 'Request Cancellation';

// Service info
$lang['time4vps.service_info.username'] = 'Username';
$lang['time4vps.service_info.server'] = 'Host Name';
$lang['time4vps.service_info.options'] = 'Options';
//client service info
$lang['time4vps.service_info.hostname'] = 'Hostname';
$lang['time4vps.service_info.os'] = 'Operating System';
$lang['time4vps.service_info.mainpv4'] = 'Main IP';
$lang['time4vps.service_info.password'] = 'Password';


// Tooltips
$lang['time4vps.package_fields.tooltip.domains_list'] = 'Enter a CSV list of domains that will be available to provision sub-domains for, e.g. "domain1.com,domain2.com,domain3.com"';
$lang['time4vps.service_field.tooltip.username'] = 'You may leave the username blank to automatically generate one.';
$lang['time4vps.service_field.tooltip.password'] = 'You may leave the password blank to automatically generate one.';


// Errors
$lang['time4vps.!error.server_name_valid'] = 'You must enter a Server Label.';
$lang['time4vps.!error.host_name_valid'] = 'The Hostname appears to be invalid.';
$lang['time4vps.!error.user_name_valid'] = 'The User Name appears to be invalid.';
$lang['time4vps.!error.password_valid'] = 'The Password appears to be invalid.';
$lang['time4vps.!error.meta[product].empty'] = 'A time4vps product is required.';

$lang['time4vps.!error.meta[dedicated_ip].format'] = 'The dedicated IP must be set to 0 or 1.';
$lang['time4vps.!error.api.internal'] = 'An internal error occurred, or the server did not respond to the request.';
$lang['time4vps.!error.module_row.missing'] = 'An internal error occurred. The module row is unavailable.';

$lang['time4vps.!error.time4vps_domain.format'] = 'Please enter a valid domain name, e.g. domain.com.';
$lang['time4vps.!error.time4vps_username.format'] = 'The username may contain only letters and numbers and may not start with a number.';
$lang['time4vps.!error.time4vps_username.test'] = "The username may not begin with 'test'.";
$lang['time4vps.!error.time4vps_username.length'] = 'The username must be between 1 and 16 characters in length.';
$lang['time4vps.!error.time4vps_password.valid'] = 'Password must be at least 8 characters in length.';
$lang['time4vps.!error.time4vps_password.matches'] = 'Password and Confirm Password do not match.';
