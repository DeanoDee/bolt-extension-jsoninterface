JsonInterface
=============

This extension allows you to get JSON(p) from Bolt by using use as `/{contenttype}/json/data.js?limit=1&callback=c`.

Allows for optional url variables limit, order, callback and filter.

TO GET STARTED
==============

1. Add the JsonInterface folder to the app/extensions folder

2. Enable your extenision thought the Bolt interface or add it to your app/config/config.yml fill like so: `enabled_extensions: [ JSON, your_other_extensions... ]`

3. Set up the JsonInterface/config.yml so that you allow for json to be displayed for all or specific content types (see config.yml file comments)

4. Optionally ad different filters to the JsonInterface to make different requests on content types (see config.yml file comments)



This was based off of https://gist.github.com/bobdenotter/6732168


***

[![Flattr this extension](http://api.flattr.com/button/flattr-badge-large.png)](https://flattr.com/submit/auto?user_id=bacbos&url=https://github.com/DeanoDee/bolt-extension-jsoninterface&title=JsonInterface for bolt cms&language=php&tags=github&category=software)


