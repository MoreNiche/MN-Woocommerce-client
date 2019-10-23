# moreniche-tracking
###Installation Instructions

Upload the MN-Woocommerce-client directory (directory included) to the /wp-content/plugins/ directory.
Activate the plugin through the ‘Plugins’ menu in the wordpress admin area.

###Configuration

The configuration of the plugin falls under woocommerce settings. In the Wordpress Admin section, hover over woocommerce on the left hand side and select settings on the menu that appears.

The settings page for woocommerce is split into a tabular form, select the integration tab and then in the sub tabs once loaded select Affiliate Tracking Pixel.

In order to configure the plugin, several options must be completed. Firstly is the cake related options being the Security Saltkey, endpoint and pixel mask, all of which are required to open the communication with our services. The credentials for these fields will be supplied via the Moreniche Attribution team. 


Following these fields further setting that are required are:

| Option                          | Defaults |
| ------------------------------- | :-------------------------:|
| **Default Currency:**           | This should be set to USD as default |
| **Cake Currencies Accepted:**   | This should be a comma separated list with no spaces i.e. GBP,USD,EUR |
| **Sub Site Exists:**            | Yes or No |
| **Report Upsells:**             | If you are trying to upsell additional items during a purchase please tick this option. Please consult with Moreniche to ensure configuration is correct. |
| **USD, GBP and EUR ID:**        | This are internal ID’s and will be supplied by Moreniche Ltd.|


The other fields are intended for testing by Moreniche and require no information to be added to them and should be left blank.

Save the changes and inform Moreniche when complete so they can monitor to ensure all is working ok.
