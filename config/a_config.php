<?php
/*
 * My config file, named with "a" prefix to ensure it's loaded first before others
 */

define('DEV', env('APP_DEBUG', true));

define('SITE_TITLE', 'Exam Scholars');
define('APP_DIR', __DIR__.'/../app/');
define('IMG_OUTPUT_PUBLIC_PATH', '/img/content/');

define('PAYSTACK_SECRET_KEY', DEV ? 'sk_test_36dbc48fe7824285df543ab662d2965ddc9b9d78' : 'sk_live_0e3fec67c86134c9ce5b3c5d7fdc14f9ede58f1a');
define('PAYSTACK_PUBLIC_KEY', DEV ? 'pk_test_b44a0591b842fdbd04e24a4f7cd621a24baae7cd' : 'pk_live_58ae87cc37384f06669bd90534ea4aa5a4dff714');

define('RAVE_SECRET_KEY', DEV?'FLWSECK-203e53126c5e37dc2a71fce19b81f482-X':'');
define('RAVE_PUBLIC_KEY', DEV?'FLWPUBK-4d9a4de3c8400a577588326b6fa18943-X':'');


define('MONNIFY_PUBLIC_KEY', DEV ? 'MK_TEST_GTBD5YQQF4' : 'MK_PROD_DQT4QJFJUH');
define('MONNIFY_SECRET_KEY', DEV ? 'QRVYP99PZLFHWTF8796RKYGWXSQ3H9EP' : 'K3QXVWG5MVK94BZJW6YVLJSGQMV8B4EP');
define('MONNIFY_CONTRACT_CODE', DEV ? '9758898268' : '575913382413');

define('CHEETAHPAY_PUBLIC_KEY', DEV?'9013358413':'2899200933');
define('CHEETAHPAY_PRIVATE_KEY', DEV?'QKXWfhpxayrsdsNhhNmQ':'eEiNDjFKOaadcIjQXsSV');


define('ECD_DB', DEV ? 'ecd' : 'inkthxda_ecd');


define("K_NEWLINE", PHP_EOL);
define("CSRF_TOKEN", 'csrf_token');
define("ADMIN_SESSION_DATA", SITE_TITLE . '_admin_session_data');
define("USER_SESSION_DATA", SITE_TITLE . '_user_session_data');
define("USER_REMEBER_LOGIN", md5(SITE_TITLE . '_user_remember_login'));
define("ADMIN_REMEBER_LOGIN", md5(SITE_TITLE . '_admin_remember_login'));



define("SUCCESSFUL", 'success');
define("MESSAGE", 'message');

define('PASSWORD_RESET_CODE', 'password_reset_code');
define("NUM_OF_ATTEMPTS", "num_of_attempts");
define("EXPIRY_TIME", 'expiry_time');
define("IS_STILL_VALID", 'is_still_valid');
define("CITY", 'city');
define("STATE", 'state');

define("IMEI", md5('imei'));
define("APP_TOKEN", md5('token'));


/**
 * from pagination
 * @var unknown
 */
define("PAGE_NUM", "pageNum");

// Queue table
define("IS_ACTIVE", 'is_active');
define("IS_APPROVED", 'is_approved');
define("IS_DUPLICATE", 'is_duplicate');
define("IS_VERIFIED", 'is_verified');

//Used to specify the url a user will be redirected to after log in
define("REDIRECT_TO", "redirect_to");
define("PASSWORD_CONFIRMATION", "password_confirmation");
define("HASH_KEY", "(`1~.()3,ckjd2*846@sa7@)+>?%ds$*dw7ew,.;]");
define("SECRET_KEY_JWT", SITE_TITLE.HASH_KEY);
define("TOKEN", 'token');
define("COMMAND", 'command');
define("REFERRAL", 'referral');


define("STATUS_ACTIVE", 'active');
define("STATUS_PAUSED", 'paused');
define("STATUS_APPROVED", 'approved');
define("STATUS_INACTIVE", 'inactive');
define("STATUS_WON", 'won');
define("STATUS_LOST", 'lost');
define("STATUS_CANCELLED", 'cancelled');
define("STATUS_ENDED", 'ended');
define("STATUS_EXPIRED", 'expired');
define("STATUS_PENDING", 'pending');
define("STATUS_INVALID", 'invalid');
define("STATUS_CREDITED", 'credited');
define("STATUS_ABANDONED", 'abandoned');
define("STATUS_DELIVERED", 'delivered');
define("STATUS_TRANSFERED", 'transfered');
define("STATUS_PAID", 'paid');
define("STATUS_PRINTED", 'printed');
define("STATUS_USED", 'used');
define("STATUS_SOLD", 'sold');
define("STATUS_IN_USE", 'in_use');
define("STATUS_PROCESSING", 'processing');
define("STATUS_SUSPENDED", 'suspended');

define("MERCHANT", 'merchant');
define("MERCHANT_PAYSTACK", 'paystack');
define("MERCHANT_RAVE", 'rave');
define("MERCHANT_CHEETAHPAY", 'cheetahpay');
define("MERCHANT_MONNIFY", 'monnify');

define("LICENSE_PRICE", 1000);
define("MINIMUM_DEPOSIT", 10000);
define("MINIMUM_DEPOSIT_CHARGE", 50);

define("DEPOSIT_METHOD_BANK", 'bank deposits');
define("DEPOSIT_METHOD_ONLINE", 'online deposits');
define("DEPOSIT_METHOD_BITCOIN", 'bitcoin deposits');

define("OUR_ACCOUNT_NAME", 'Examscholars Limited');
define("OUR_BANK_NAME", 'GT Bank');
define("OUR_ACCOUNT_NUMBER", '0657550955');

define("NETWORK_MTN", 'MTN');
define("NETWORK_GLO", 'GLO');
define("NETWORK_AIRTEL", 'AIRTEL');
define("NETWORK_9_MOBILE", '9 MOBILE');

define("CHOICE_PLATFORM_API", 'API');
define("CHOICE_PLATFORM_WEBSITE", 'WEBSITE');
define("CHOICE_PLATFORM_APP", 'APP');
define("CHOICE_PLATFORM_DATA_PIN", 'DATA PIN');
define("CHOICE_PLATFORM_SMS", 'SMS');

define("DEVELOPER_MODE_LIVE", 'live');
define("DEVELOPER_MODE_TEST", 'test');

define("REFERRER", 'referrer');
define("REFEREE", 'referee');
define("VAL_ERRORS", 'val_errors');

/** Tag to mark a row as deleted */
define("DELETED", "deleted");

define("NAIRA_SIGN", '₦');
define("CURRENCY_SIGN", NAIRA_SIGN);

define("INJECTED", 'injected');

define("RATE_TV_DSTV", 'DSTV');
define("RATE_TV_GOTV", 'GOTV');
define("RATE_TV_STARTIMES", 'StarTimes');
define("RATE_ELECTRICITY_KADUNA", 'Kaduna');
define("RATE_ELECTRICITY_JOS", 'JOS');
define("RATE_ELECTRICITY_IKEJA", 'Ikeja Electric');
define("RATE_ELECTRICITY_EKO", 'Eko PHCN');
define("RATE_ELECTRICITY_PH_PREPAID", 'Port Harcourt Prepaid');
define("RATE_ELECTRICITY_PH_POSTPAID", 'Port Harcourt Postpaid');
define("RATE_ELECTRICITY_ABUJA_PREPAID", 'Abuja Prepaid');
define("RATE_ELECTRICITY_ABUJA_POSTPAID", 'Abuja Postpaid');
define("RATE_ELECTRICITY_ENUGU", 'Enugu Distribution');
define("RATE_ELECTRICITY_IBADAN", 'Ibadan Distribution');
define("RATE_ELECTRICITY_KANO_PREPAID", 'Kano Prepaid');
define("RATE_ELECTRICITY_KANO_POSTPAID", 'Kano Postpaid');
define("RATE_INTERNET_SPECTRANET", 'Spectranet');
define("RATE_INTERNET_SMILE", 'Smile');
define("RATE_EDUCATION_WAEC", 'WAEC');

define("CASHOUT_METHOD_BANK", 'bank');
define("CASHOUT_METHOD_WALLET", 'wallet');
define("CASHOUT_METHOD_API_PAYMENT", 'api payment');


define("CUSTOMER_CARE_NUMBER", '08133744803');
define("WHATSAPP_NO", '+234'.ltrim(CUSTOMER_CARE_NUMBER, '0'));
define("WHATSAPP_LINK", 'https://api.whatsapp.com/send?phone='.trim(WHATSAPP_NO, '+'));
define("WHATSAPP_GROUP_LINK", '');
define("FACEBOOK_PAGE", 'https://web.facebook.com/ExamScholars.CBT.software');
define("YOUTUBE_PAGE", 'https://www.youtube.com/channel/UChz6VwVqZ3K2YjvLy-8ZW8g');
define("TWITTER_PAGE", 'https://twitter.com/');
define("INSTAGRAM_PAGE", '#');
define("GOOGLE_PLUS_PAGE", '#');
define("SITE_EMAIL", 'support@examscholar.com');

define("APP_VERSION", '0.1');

