# Blesta Time4VPS Module
 This is Time4VPS provisioning module for Blesta platform.

## Installation  
  
 1. Download latest module [release](https://github.com/time4vps/blesta/releases);
 2. Upload archive folder contents to your Blesta installation root directory;
 3. Log in to your admin Blesta account and navigate to `Settings -> Modules`;
 4. Find the Time4VPS module in "Available" tab and click the "Install" button to install it;
 5. Click `Add Server` button;
 6. Set following fields:
	- Name: `Time4VPS`;
	- Hostname: `billing.time4vps.com`;
	- Set your Time4VPS username and password accordingly;
	- Tick to use `SSL Mode for Connections`.
 7. Import Time4VPS products by navigating to `http://<your blesta url>/components/modules/time4vps/update.php`;
 8. Delete `/components/modules/time4vps/update.php` file from your server.


## Product import 
Import Time4VPS products by navigating to `http://<your blesta url>/components/modules/time4vps/update.php`.
**Run it once, as every other request will reset any changes you made for existing Time4VPS products.**
  
## License  
[MIT](https://github.com/time4vps/time4vps-lib/blob/master/LICENSE)
