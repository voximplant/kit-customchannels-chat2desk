An example of integrating Chat2Desc with Voximplant KIT using a custom channel
=============================================================================


Configure your credentials in [./env](.env) file:

* KIT_API_URL: KIT Api url by your region. https://kitapi-eu.voximplant.com/api/v3 or https://kitapi-us.voximplant.com/api/v3);
* KIT_ACCOUNT_NAME: Your account name in Voximplant KIT;
* KIT_API_TOKEN: Your api token in Voximplant KIT;
* KIT_CHANNEL_UUID: Your custom channel uuid in Voximplant KIT;
* CHAT2DESC_API_TOKEN: Api token for chat2desc Api;


Run server:
```shell script
> php app.php
```


#### Set callback url in chat2desc:

```shell script
> curl -XPOST https://api.chat2desk.com/v1/webhooks \
  -H "Authorization: {{YOUR_API_TOKEN}}" \
  -H "Content-Type: application/json" \
  --data '{"url": "{{your_server_host}}/chat2desc-incoming", "name": "CustomKitChannelAliTest", "events": ["inbox"]}'
```

####  \* This solution is for example only, how to integrate chat2desc with voximplant KIT. Dont`t use it for production
