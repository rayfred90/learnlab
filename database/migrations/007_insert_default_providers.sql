-- Migration: 007_insert_default_providers.sql
-- Insert default provider configurations

INSERT IGNORE INTO `{prefix}sixlab_providers` 
(`name`, `type`, `display_name`, `config`, `is_active`, `is_default`, `supported_features`) 
VALUES 
(
  'gns3', 
  'gns3', 
  'GNS3 Network Simulator',
  '{"server_url":"http://localhost:3080","web_gui_url":"http://localhost:3080","auth_username":"","auth_password":"","templates_directory":"/opt/gns3/projects/templates","auto_cleanup_minutes":120,"max_concurrent_sessions":50,"enable_snapshots":true}',
  1,
  1,
  '["network_topology","console_access","real_time_validation","configuration_backup","snapshot_support","multi_vendor_devices"]'
),
(
  'guacamole',
  'guacamole', 
  'Apache Guacamole Remote Desktop',
  '{"server_url":"http://localhost:8080/guacamole","admin_username":"guacadmin","admin_password":"","connection_group":"sixlab-sessions","vm_hypervisor":"manual","vm_template_linux":"","vm_template_windows":"","enable_recording":false,"recording_path":""}',
  0,
  0,
  '["remote_desktop","ssh_access","vnc_access","rdp_access","file_transfer","session_recording","clipboard_sharing"]'
),
(
  'eveng',
  'eveng',
  'EVE-NG Network Emulator', 
  '{"server_url":"https://your-eve-server","username":"admin","password":"","lab_template_path":"/opt/unetlab/labs/templates","enable_wireshark":true,"max_lab_size":20}',
  0,
  0,
  '["network_topology","console_access","multi_vendor_support","wireshark_integration","configuration_backup","collaborative_labs"]'
);

-- Insert default lab templates
INSERT IGNORE INTO `{prefix}sixlab_lab_templates`
(`name`, `slug`, `description`, `provider_type`, `difficulty_level`, `estimated_duration`, `template_data`, `validation_rules`, `instructions`, `prerequisites`, `learning_objectives`, `tags`, `is_active`, `is_featured`, `author_id`)
VALUES
(
  'Basic Router Configuration',
  'basic-router-configuration',
  'Learn fundamental router configuration commands and basic routing concepts.',
  'gns3',
  'beginner',
  60,
  '{"topology":{"devices":[{"name":"R1","type":"cisco_router","x":100,"y":100}],"connections":[]},"steps":[{"id":1,"title":"Connect to Router Console","description":"Access the router console via Telnet","validation_type":"console_access"},{"id":2,"title":"Enter Privileged Mode","description":"Use the enable command","validation_type":"privilege_mode"},{"id":3,"title":"Enter Configuration Mode","description":"Use configure terminal command","validation_type":"config_mode"},{"id":4,"title":"Configure Interface","description":"Configure GigabitEthernet0/0 with IP 192.168.1.1/24","validation_type":"interface_config"},{"id":5,"title":"Enable Interface","description":"Bring interface up with no shutdown","validation_type":"interface_status"}]}',
  '{"step_1":{"expected_output":"Router>","validation_command":"show privilege"},"step_2":{"expected_output":"Router#","validation_command":"show privilege"},"step_3":{"expected_output":"Router(config)#","validation_command":"show privilege"},"step_4":{"expected_config":"interface GigabitEthernet0/0\\n ip address 192.168.1.1 255.255.255.0","validation_command":"show running-config interface GigabitEthernet0/0"},"step_5":{"expected_status":"up","validation_command":"show ip interface brief"}}',
  'Step-by-step instructions for basic router configuration including console access, privilege modes, and interface configuration.',
  'Basic networking knowledge, familiarity with command line interfaces',
  'Understand router CLI navigation, Configure basic interface settings, Apply IP addressing concepts, Use Cisco IOS commands effectively',
  'cisco,routing,beginner,configuration,interfaces',
  1,
  1,
  1
),
(
  'VLAN Configuration',
  'vlan-configuration', 
  'Configure VLANs, trunk ports, and inter-VLAN routing on switches.',
  'gns3',
  'intermediate',
  90,
  '{"topology":{"devices":[{"name":"SW1","type":"cisco_switch","x":100,"y":100},{"name":"SW2","type":"cisco_switch","x":300,"y":100}],"connections":[{"from":"SW1","to":"SW2","interface_from":"GigabitEthernet0/1","interface_to":"GigabitEthernet0/1"}]},"steps":[{"id":1,"title":"Create VLANs","description":"Create VLAN 10 and VLAN 20","validation_type":"vlan_creation"},{"id":2,"title":"Assign Access Ports","description":"Configure access ports for each VLAN","validation_type":"access_ports"},{"id":3,"title":"Configure Trunk","description":"Set up trunk port between switches","validation_type":"trunk_config"},{"id":4,"title":"Verify VLAN Configuration","description":"Confirm VLAN and trunk status","validation_type":"vlan_verification"}]}',
  '{"step_1":{"expected_vlans":["10","20"],"validation_command":"show vlan brief"},"step_2":{"expected_access_ports":{"GigabitEthernet0/2":"10","GigabitEthernet0/3":"20"},"validation_command":"show interfaces switchport"},"step_3":{"expected_trunk_vlans":"1,10,20","validation_command":"show interfaces trunk"},"step_4":{"expected_status":"operational","validation_command":"show spanning-tree"}}',
  'Comprehensive VLAN configuration lab covering VLAN creation, access ports, trunk configuration, and verification procedures.',
  'Basic switch operation knowledge, understanding of Layer 2 concepts',
  'Create and manage VLANs, Configure access and trunk ports, Understand VLAN traffic flow, Troubleshoot VLAN connectivity',
  'cisco,switching,vlan,trunking,intermediate',
  1,
  1,
  1
);
