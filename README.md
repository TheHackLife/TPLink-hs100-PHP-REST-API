# TPLink hs100 PHP api
PHP rest api for tplink hs100 smart plugs

**NOTE** You need have an account on "Kasa" app, and register your plugs on it

## Usage

```php
define("TPLINK_USER", "XXXXX"); //Login email
define("TPLINK_PASS", "XXXXX"); //Login password

require 'tplink.class.php';

$tplink = new TpLink();
/* Get an array of all smart plugs */
$plugs = $tplink->getDeviceList();

/* Set on first plug */
$tplink->plugSwitch(true, array(    // bool, default false
	"deviceId" => $plugs[0]['deviceId'], // deviceId, default none
	"useUrl" => $plugs[0]['useUrl']      // optional specify appServerUrl (Previously provided)
));	
```

### Commands
#### myTerminal _(null)_
Return your uid
#### returnToken _(null)_
Login and return your tplink token
#### getDeviceList _(null)_
Get an complete list of your plugs, can take some time
```php
/* Example response */
Array
(
    [0] => Array
        (
            [deviceId] => XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX
            [name] => Riego
            [deviceName] => Wi-Fi Smart Plug HS100(EU)
            [mac] => XXXXXXXXXXX
            [useUrl] => https://use1-wap.tplinkcloud.com
            [working] => 1
            [relay_status] => off
            [geo] => Array
                (
                    [lat] => -XX.XXXXX
                    [lon] => -XX.XXXXX
                )

            [Ids] => Array
                (
                    [fwId] => XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX
                    [hwId] => XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX
                    [oemId] => XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX
                )

        )
        ...
```
#### plugSwitch _(bool, Array)_
Send command to plug, true / false, and deviceId in array

## License

MIT License

2017
