<?php

return [
    'menu_name'                  => 'Rezgo Connector',
    'settings_title'             => 'Rezgo Connector Settings',
    'api_credentials'            => 'API Credentials',
    'cid_label'                  => 'Rezgo CID (Transcode)',
    'cid_help'                   => 'Your Rezgo Client ID (transcode), e.g. 1446. Found in Rezgo Settings → API Access.',
    'api_key_label'              => 'API Key',
    'api_key_placeholder_new'    => 'Enter your Rezgo API key',
    'api_key_placeholder_set'    => 'API key is saved — enter a new key to update',
    'api_key_help'               => 'Leave blank to keep the existing API key. The key is stored encrypted.',
    'enabled_label'              => 'Enable Rezgo Sync',
    'enabled_help'               => 'When enabled, every completed order will be transmitted to Rezgo in real-time.',
    'save_settings'              => 'Save Settings',
    'settings_saved'             => 'Rezgo Connector settings have been saved successfully.',
    'test_connection'            => 'Test Connection',
    'info_text'                  => 'To connect to Rezgo you need:',
    'info_cid'                   => 'Your Account CID (Transcode) from Rezgo Settings',
    'info_key'                   => 'An API Key with booking permissions',
    'info_log'                   => 'All sync events are logged to storage/logs/rezgo-sync.log',
];
