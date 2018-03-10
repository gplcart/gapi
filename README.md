[![Build Status](https://scrutinizer-ci.com/g/gplcart/gapi/badges/build.png?b=master)](https://scrutinizer-ci.com/g/gplcart/gapi/build-status/master)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/gplcart/gapi/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/gplcart/gapi/?branch=master)

Google API is a [GPL Cart](https://github.com/gplcart/gplcart) module that integrates [PHP Google API client](https://github.com/google/google-api-php-client) into your site

**Installation**

This module requires 3-d party library which should be downloaded separately. You have to use [Composer](https://getcomposer.org) to download all the dependencies.

1. From your web root directory: `composer require gplcart/gapi`. If the module was downloaded and placed into `system/modules` manually, run `composer update` to make sure that all 3-d party files are presented in the `vendor` directory.
2. Go to `admin/module/list` end enable the module